<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class UserAddressConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        // Workers must see committed fixtures, not a parent test transaction.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public static function interleavings(): array
    {
        return [
            'default then default' => ['default', 'x', 'default', 'y', false, false],
            'create then create on empty book' => ['store', null, 'store', null, true, false],
            'create then default' => ['store', null, 'default', 'y', false, false],
            'default then create' => ['default', 'x', 'store', null, false, false],
            'update then default' => ['update', 'x', 'default', 'y', false, false],
            'default then update' => ['default', 'x', 'update', 'y', false, false],
            'delete then default same target' => ['destroy', 'x', 'default', 'x', false, false],
            'default then delete same target' => ['default', 'x', 'destroy', 'x', false, false],
            'delete old default then select another' => ['destroy', 'old', 'default', 'y', false, false],
            'select another then delete old default' => ['default', 'y', 'destroy', 'old', false, false],
            'delete then update same target' => ['destroy', 'x', 'update', 'x', false, false],
            'delete then delete same target' => ['destroy', 'x', 'destroy', 'x', false, false],
            'stale update omits default' => ['default', 'y', 'update-keep', 'old', false, false],
            'rollback default then default' => ['default', 'x', 'default', 'y', false, true],
            'rollback create then create' => ['store', null, 'store', null, true, true],
        ];
    }

    #[DataProvider('interleavings')]
    public function test_writes_serialize_on_the_user_row(
        string $first, ?string $firstTarget, string $second, ?string $secondTarget, bool $empty, bool $rollback,
    ): void {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $foreign = $other->addresses()->create($this->data('foreign') + ['is_default' => true]);
        $addresses = [];
        $expected = [];
        if (! $empty) {
            foreach (['old', 'x', 'y'] as $name) {
                $addresses[$name] = $user->addresses()->create($this->data($name) + ['is_default' => $name === 'old']);
                $expected[$name] = ['default' => $name === 'old', 'house' => 'initial'];
            }
        }
        $firstInput = $this->input($user, $first, $firstTarget, $addresses, 'first') + ['rollback' => $rollback];
        $secondInput = $this->input($user, $second, $secondTarget, $addresses, 'second');
        $results = $this->runPair($firstInput, $secondInput);
        $this->assertSame($rollback ? 'rolled-back' : 'ok', $results[0]['status']);
        if (! $rollback) {
            $this->applyExpected($expected, $first, $firstTarget, 'first');
        }
        $missing = $second !== 'store' && ! isset($expected[$secondTarget]);
        $this->assertSame($missing ? 'missing' : 'ok', $results[1]['status']);
        if (! $missing) {
            $this->applyExpected($expected, $second, $secondTarget, 'second');
        }
        ksort($expected);
        $actual = $user->addresses()->orderBy('street')->get()->mapWithKeys(fn ($address) => [
            $address->street => ['default' => (bool) $address->is_default, 'house' => $address->house],
        ])->all();
        $this->assertSame($expected, $actual);
        $this->assertLessThanOrEqual(1, $user->addresses()->where('is_default', true)->count());
        $this->assertSame(1, $other->addresses()->count());
        $this->assertTrue((bool) $foreign->fresh()->is_default);
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_different_users_do_not_wait_for_each_others_user_lock(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $results = $this->runPair(
            $this->input($first, 'store', null, [], 'first'),
            $this->input($second, 'store', null, [], 'second'),
            false,
        );
        $this->assertSame(['ok', 'ok'], array_column($results, 'status'));
        foreach ([$first, $second] as $user) {
            $this->assertTrue((bool) $user->addresses()->sole()->is_default);
        }
    }

    private function data(string $street): array
    {
        return ['country' => 'Moldova', 'city' => 'Chisinau', 'street' => $street, 'house' => 'initial'];
    }

    private function input(User $user, string $action, ?string $target, array $addresses, string $phase): array
    {
        $data = array_replace($this->data($target ?? $phase), ['house' => $phase]);
        if ($action !== 'update-keep') {
            $data['is_default'] = '1';
        }

        return ['user_id' => $user->id, 'action' => $action, 'address_id' => $target ? $addresses[$target]->id : null, 'data' => $data];
    }

    private function applyExpected(array &$book, string $action, ?string $target, string $phase): void
    {
        if ($action === 'destroy') {
            unset($book[$target]);

            return;
        }
        if ($action !== 'update-keep') {
            foreach ($book as &$address) {
                $address['default'] = false;
            }
            unset($address);
            $book[$target ?? $phase]['default'] = true;
        }
        if (in_array($action, ['store', 'update', 'update-keep'], true)) {
            $book[$target ?? $phase]['house'] = $phase;
        }
    }

    private function runPair(array $first, array $second, bool $mustWait = true): array
    {
        $processes = $inputs = $results = $connections = [];
        try {
            foreach ([$first, $second] as $i => $operation) {
                $input = new InputStream();
                $input->write(json_encode($operation + [
                    'database' => config('database'), 'key' => config('app.key'), 'pause' => $i === 0,
                ], JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/user-address-worker.php')], base_path(), timeout: 30);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                $this->awaitOutput($process, $i === 0 ? 'LOCKED' : 'USER_LOCK_ATTEMPT');
            }
            foreach ($processes as $i => $process) {
                preg_match('/CONNECTION:(\d+)/', $process->getOutput(), $match);
                $this->assertNotEmpty($match, $this->diagnostic($processes));
                $connections[$i] = $match[1];
            }
            $this->assertNotSame($connections[0], $connections[1]);
            if ($mustWait) {
                $deadline = microtime(true) + 8;
                $waiting = false;
                $lastState = 'not observed';
                do {
                    // INNODB_TRX needs >100 ms idle since its last read to refresh.
                    // Delay controls observation cadence; only LOCK WAIT proves contention.
                    usleep(200000);
                    if (! $processes[0]->isRunning() || ! $processes[1]->isRunning()
                        || str_contains($processes[1]->getOutput(), 'USER_LOCK_ACQUIRED')) {
                        break;
                    }
                    $transaction = DB::table('information_schema.INNODB_TRX')
                        ->where('trx_mysql_thread_id', $connections[1])->first(['trx_state', 'trx_query']);
                    $lastState = $transaction?->trx_state ?? 'absent';
                    $sql = strtolower((string) ($transaction?->trx_query));
                    $waiting = $lastState === 'LOCK WAIT' && str_contains($sql, 'from `users`') && str_contains($sql, 'for update');
                } while (! $waiting && microtime(true) < $deadline);
                $this->assertTrue($waiting, 'Expected real User row LOCK WAIT; state='.$lastState.'; '.$this->diagnostic($processes));
                $this->assertStringNotContainsString('USER_LOCK_ACQUIRED', $processes[1]->getOutput());
                $this->assertStringNotContainsString('RESULT:', $processes[1]->getOutput());
                $this->assertTrue($processes[1]->isRunning());
            } else {
                // Independent owner must commit while the first owner is still paused.
                $this->awaitOutput($processes[1], 'RESULT:');
            }
            $this->assertTrue($processes[0]->isRunning());
            $this->assertStringNotContainsString('RESULT:', $processes[0]->getOutput());
            $inputs[0]->write("release\n");
            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $this->diagnostic($processes));
                $this->assertStringContainsString('USER_LOCK_ACQUIRED', $process->getOutput());
                preg_match('/RESULT:([^\r\n]+)/', $process->getOutput(), $match);
                $this->assertNotEmpty($match);
                $results[] = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
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

        return $results;
    }

    private function awaitOutput(Process $process, string $marker): void
    {
        $deadline = microtime(true) + 8;
        while (! str_contains($process->getOutput(), $marker) && $process->isRunning() && microtime(true) < $deadline) {
            usleep(20000);
        }
        $this->assertStringContainsString($marker, $process->getOutput(), $this->diagnostic([$process]));
    }

    private function diagnostic(array $processes): string
    {
        // Worker output contains only protocol markers, connection ID and status.
        return implode(' | ', array_map(fn (Process $process) => json_encode([
            'running' => $process->isRunning(), 'exit' => $process->getExitCode(),
            'output' => $process->getOutput(), 'error' => $process->getErrorOutput(),
        ], JSON_THROW_ON_ERROR), $processes));
    }
}
