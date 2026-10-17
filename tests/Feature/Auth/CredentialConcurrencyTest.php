<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\PasswordSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class CredentialConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        // Fixtures must be committed for independent processes. Rebuild next test.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public static function interleavings(): array
    {
        return [
            'rotation wins issuance' => ['rotate', 'issue', 'rotated', 'stale'],
            'rotation wins redemption' => ['rotate', 'redeem', 'rotated', 'rejected'],
            'rotation wins password login' => ['rotate', 'login', 'rotated', 'stale'],
            'issuance wins rotation' => ['issue', 'rotate', 'issued', 'rotated'],
            'redemption wins rotation' => ['redeem', 'rotate', 'authorized', 'rotated'],
            'rotation wins reset' => ['rotate', 'reset', 'rotated', Password::INVALID_TOKEN],
            'reset wins rotation' => ['reset', 'rotate', Password::PASSWORD_RESET, 'stale'],
            'reset token consumed once' => ['reset', 'reset', Password::PASSWORD_RESET, Password::INVALID_TOKEN],
            'rollback permits issuance' => ['rollback', 'issue', 'rolled-back', 'issued'],
        ];
    }

    #[DataProvider('interleavings')]
    public function test_credential_operations_serialize_on_user_row_and_preserve_http_revocation(
        string $first, string $second, string $firstStatus, string $secondStatus,
    ): void {
        Notification::fake();
        config(['session.driver' => 'database']);
        Route::middleware(['web', 'auth'])->get('/credential-concurrency-probe', fn () => response('authenticated'));
        $user = User::factory()->create();
        $credential = app(PasswordSecurityService::class)->issueRememberedDevice($user);
        $token = Password::createToken($user);
        $processes = [];
        $inputs = [];
        $results = [];
        try {
            foreach ([$first, $second] as $index => $action) {
                $input = new InputStream();
                $input->write(json_encode([
                    'database' => config('database'), 'key' => config('app.key'), 'hashing' => config('hashing'),
                    'user_id' => $user->id, 'credential' => $credential, 'reset_token' => $token,
                    'action' => $action, 'pause' => $index === 0,
                ], JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/credential-lifecycle-worker.php')], base_path(), timeout: 30);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                $this->awaitOutput($process, $index === 0 ? 'LOCKED' : 'USER_LOCK_ATTEMPT');
            }
            preg_match('/CONNECTION:(\d+)/', $processes[1]->getOutput(), $match);
            $this->assertNotEmpty($match);
            $deadline = microtime(true) + 8;
            $waiting = false;
            $lastState = 'not observed';
            $polls = 0;
            do {
                // INNODB_TRX refresh requires >100 ms since its LAST READ, not
                // its last refresh. Polling every 20 ms can keep an early empty
                // snapshot alive forever. Leave an idle interval before EVERY
                // read, including the first; this is cadence, not lock proof.
                // MySQL: storage/innobase/trx/trx0i_s.cc,
                // can_cache_be_updated() and trx_i_s_cache_end_read().
                usleep(200000);
                if (! $processes[0]->isRunning() || ! $processes[1]->isRunning()
                    || str_contains($processes[1]->getOutput(), 'USER_LOCK_ACQUIRED')
                    || str_contains($processes[1]->getOutput(), 'RESULT:')) {
                    break;
                }
                $transaction = DB::table('information_schema.INNODB_TRX')
                    ->where('trx_mysql_thread_id', $match[1])
                    ->first(['trx_state', 'trx_query']);
                $polls++;
                $lastState = $transaction?->trx_state ?? 'absent';
                $query = strtolower((string) ($transaction?->trx_query));
                $waiting = $lastState === 'LOCK WAIT'
                    && str_contains($query, 'from `users`') && str_contains($query, 'for update');
                if ($waiting) {
                    break;
                }
            } while (microtime(true) < $deadline);
            $diagnostic = $first.' -> '.$second.'; polls='.$polls.'; last_state='.$lastState
                .'; holder='.$this->workerState($processes[0]).'; contender='.$this->workerState($processes[1]);
            $this->assertTrue($waiting, 'The second MySQL connection must actually wait for the user row lock. '.$diagnostic);
            $this->assertTrue($processes[0]->isRunning() && $processes[1]->isRunning(), $diagnostic);
            $this->assertStringNotContainsString('USER_LOCK_ACQUIRED', $processes[1]->getOutput(), $diagnostic);
            $this->assertStringNotContainsString('RESULT:', $processes[1]->getOutput());
            $inputs[0]->write("release\n");
            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput());
                preg_match('/RESULT:([^\r\n]+)/', $process->getOutput(), $result);
                $this->assertNotEmpty($result);
                $results[] = json_decode($result[1], true, flags: JSON_THROW_ON_ERROR);
            }
        } finally {
            foreach ($inputs as $input) {
                $input->close();
            }
            foreach ($processes as $process) {
                $process->stop(0);
                $this->assertFalse($process->isRunning());
            }
        }
        $this->assertSame($firstStatus, $results[0]['status']);
        $this->assertSame($secondStatus, $results[1]['status']);
        $this->assertSame(0, DB::transactionLevel());

        if ($first === 'rollback') {
            $this->assertSame($user->password, $user->fresh()->password);
            $this->assertTrue(Password::tokenExists($user, $token));
            foreach ([$credential, $results[1]['credential']] as $valid) {
                $response = $this->browser(null, 'POST', route('login.remembered'), $valid)->assertSessionHasNoErrors();
                $this->probe($response)->assertOk();
            }
        } else {
            $this->assertDatabaseMissing('user_remembered_devices', ['user_id' => $user->id]);
            $this->assertFalse(Password::tokenExists($user, $token));
            foreach ([$credential, $results[0]['credential'] ?? $credential] as $revoked) {
                $response = $this->browser(null, 'POST', route('login.remembered'), $revoked)->assertSessionHasErrors('login');
                $this->probe($response)->assertRedirect(route('login'));
            }
            if ($first === 'redeem') {
                $this->assertSame($user->password, $results[0]['hash']);
                // Recreate the delayed Login event using exactly the authorized
                // snapshot, not a fresh user with the post-rotation password.
                Route::middleware('web')->get('/delayed-device-login', function () use ($user) {
                    Auth::login($user, false);
                    return response('login completed');
                });
                $this->probe($this->browser(null, 'GET', '/delayed-device-login'))->assertRedirect(route('login'));
            }
        }
        $password = $first === 'rollback' ? 'password' : ($first === 'reset' ? 'reset-password' : 'rotated-password');
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
        $response = $this->browser(null, 'POST', route('login'), [
            'login' => $user->email, 'password' => $password, 'remember' => true,
        ])->assertSessionHasNoErrors();
        $this->probe($response)->assertOk();
    }

    private function awaitOutput(Process $process, string $marker): void
    {
        $deadline = microtime(true) + 8;
        while (! str_contains($process->getOutput(), $marker) && $process->isRunning() && microtime(true) < $deadline) {
            usleep(20000);
        }
        $this->assertTrue(str_contains($process->getOutput(), $marker), 'Worker did not reach '.$marker.'; '.$this->workerState($process));
    }

    private function workerState(Process $process): string
    {
        // Never dump stdout: RESULT may contain a device token or password hash.
        $output = $process->getOutput();
        preg_match('/CONNECTION:(\d+)/', $output, $connection);
        preg_match('/RESULT:([^\r\n]+)/', $output, $result);
        $status = isset($result[1]) ? (json_decode($result[1], true)['status'] ?? 'invalid result') : 'none';

        return json_encode([
            'connection' => $connection[1] ?? null,
            'running' => $process->isRunning(), 'exit' => $process->getExitCode(),
            'lock_attempt' => str_contains($output, 'USER_LOCK_ATTEMPT'),
            'lock_acquired' => str_contains($output, 'USER_LOCK_ACQUIRED'),
            'paused' => str_contains($output, 'LOCKED'), 'result_status' => $status,
            'error' => trim($process->getErrorOutput()),
        ], JSON_THROW_ON_ERROR);
    }

    private function probe(TestResponse $response): TestResponse
    {
        $cookie = $response->getCookie(config('session.cookie'));
        $this->assertNotNull($cookie);

        return $this->browser($cookie->getValue(), 'GET', '/credential-concurrency-probe');
    }

    private function browser(?string $session, string $method, string $uri, array $data = []): TestResponse
    {
        Auth::forgetGuards();
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        $this->app['redirect']->setSession($this->app['session']->driver());
        $this->defaultCookies = $session ? [config('session.cookie') => $session] : [];

        return $this->call($method, $uri, $data, $this->prepareCookiesForRequest());
    }
}
