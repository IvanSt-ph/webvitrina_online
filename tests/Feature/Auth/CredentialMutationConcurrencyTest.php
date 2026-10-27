<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class CredentialMutationConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        // Workers need committed fixtures; the next test must rebuild the guarded test DB.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public static function races(): array
    {
        $cases = [
            'email before verification' => ['email', 'verify', 'rejected'],
            'verification before email' => ['verify', 'email', 'ok'],
            'duplicate verification' => ['verify', 'verify', 'ok'],
        ];
        foreach (['rotate', 'reset'] as $rotation) {
            foreach (['email', 'contacts', 'legacy', 'delete', 'admin', 'admin-password'] as $action) {
                $cases[$rotation.' before '.$action] = [$rotation, $action, 'rejected'];
            }
        }

        return $cases;
    }

    public static function throttledWrites(): array
    {
        return [['email'], ['delete'], ['admin']];
    }

    #[DataProvider('throttledWrites')]
    public function test_database_attempt_budget_survives_rejected_write_rollback(string $action): void
    {
        config([
            'cache.limiter' => 'credential-regression',
            'cache.stores.credential-regression' => array_merge(config('cache.stores.database'), [
                'prefix' => 'credential-regression-'.bin2hex(random_bytes(8)).':',
            ]),
        ]);
        $this->travelTo(now()->startOfMinute());
        $user = User::factory()->create(['role' => $action === 'admin' ? 'admin' : 'buyer']);
        $this->actingAs($user);
        try {
            for ($i = 0; $i < 6; $i++) {
                $data = ['name' => $user->name, 'email' => 'changed@example.com',
                    'profile_section' => 'email', 'current_password' => 'wrong', 'password' => 'wrong'];
                if ($action === 'delete') {
                    $response = $this->deleteJson('/profile', $data);
                } elseif ($action === 'admin') {
                    unset($data['password']);
                    $response = $this->putJson(route('admin.profile.update'), $data);
                } else {
                    $response = $this->patchJson('/profile', $data);
                }
                $response->assertStatus($i < 5 ? 422 : 429);
                if ($i < 5) {
                    $response->assertJsonValidationErrors($action === 'delete' ? 'password' : 'current_password');
                }
            }
            $this->assertSame($user->email, $user->fresh()->email);
            $this->assertNull($user->fresh()->deleted_at);
        } finally {
            $window = intdiv(now()->getTimestamp(), \App\Rules\ThrottledCurrentPassword::WINDOW_SECONDS);
            Cache::store('credential-regression')->forget('current-password:web:'.$user->id.':'.$window);
            $this->travelBack();
        }
    }

    #[DataProvider('races')]
    public function test_authorization_uses_the_row_after_the_competing_commit(string $first, string $second, string $expected): void
    {
        Storage::fake('public');
        $user = User::factory()->unverified()->create([
            'role' => str_starts_with($second, 'admin') ? 'admin' : 'buyer',
        ]);
        $originalEmail = $user->email;
        $token = $first === 'reset' ? Password::createToken($user) : null;
        $processes = $inputs = [];
        try {
            foreach ([$first, $second] as $i => $action) {
                $input = new InputStream();
                $input->write(json_encode([
                    'database' => config('database'), 'key' => config('app.key'),
                    'public_root' => Storage::disk('public')->path(''),
                    'user_id' => $user->id, 'action' => $action, 'pause' => $i === 0,
                    'token' => $token,
                ], JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/credential-race-worker.php')], base_path(), timeout: 30);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                $this->awaitOutput($process, $i === 0 ? 'LOCKED' : 'USER_LOCK_CONFLICT');
            }
            $connections = [];
            foreach ($processes as $process) {
                preg_match('/CONNECTION:(\d+)/', $process->getOutput(), $match);
                $this->assertNotEmpty($match);
                $connections[] = $match[1];
                $this->assertTrue($process->isRunning());
                $this->assertStringNotContainsString('RESULT:', $process->getOutput());
            }
            $this->assertNotSame($connections[0], $connections[1]);
            // NOWAIT proves contention on the production locking query, not timing.
            $inputs[0]->write("release\n");
            $this->assertSame(0, $processes[0]->wait(), $processes[0]->getErrorOutput());
            $this->assertStringContainsString('RESULT:ok', $processes[0]->getOutput());
            $inputs[1]->write("continue\n");
            $this->assertSame(0, $processes[1]->wait(), $processes[1]->getErrorOutput());
            $this->assertStringContainsString('RESULT:'.$expected, $processes[1]->getOutput());
            if ($first === 'verify' && $second === 'verify') {
                $this->assertStringContainsString('VERIFIED:1', $processes[0]->getOutput());
                $this->assertStringContainsString('VERIFIED:0', $processes[1]->getOutput());
            } elseif ($second === 'verify') {
                $this->assertStringContainsString('VERIFIED:0', $processes[1]->getOutput());
            }

            $current = $user->fresh();
            $this->assertNotNull($current);
            if (in_array($first, ['rotate', 'reset'], true)) {
                $this->assertSame($originalEmail, $current->email);
                $this->assertSame($user->name, $current->name);
                $this->assertTrue(Hash::check('rotated-password-123', $current->password));
                $this->assertNull($current->email_verified_at);
            } elseif ($first === 'verify' && $second === 'verify') {
                $this->assertNotNull($current->email_verified_at);
            } else {
                $this->assertSame('changed@example.com', $current->email);
                $this->assertNull($current->email_verified_at);
            }
        } finally {
            foreach ($inputs as $input) {
                $input->close();
            }
            foreach ($processes as $process) {
                $process->stop(0);
            }
        }
    }

    private function awaitOutput(Process $process, string $marker): void
    {
        $deadline = microtime(true) + 8;
        while (! str_contains($process->getOutput(), $marker) && $process->isRunning() && microtime(true) < $deadline) {
            usleep(20000);
        }
        $this->assertStringContainsString($marker, $process->getOutput(), $process->getErrorOutput());
    }
}
