<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AdminMembershipConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public static function interleavings(): array
    {
        $cases = [];
        foreach (['delete', 'demote', 'save'] as $first) {
            foreach (['delete', 'demote', 'save'] as $second) {
                $cases[$first.' then '.$second] = [$first, $second, false];
            }
            $cases[$first.' rollback then delete'] = [$first, 'delete', true];
        }

        return $cases;
    }

    #[DataProvider('interleavings')]
    public function test_two_administrators_cannot_both_be_removed(string $first, string $second, bool $rollback): void
    {
        $users = [User::factory()->admin()->create(), User::factory()->admin()->create()];
        $processes = $inputs = $connections = [];
        try {
            foreach ([$first, $second] as $i => $action) {
                $input = new InputStream();
                $input->write(json_encode([
                    'database' => config('database'), 'key' => config('app.key'),
                    'user_id' => $users[$i]->id, 'action' => $action,
                    'pause' => $i === 0, 'rollback' => $i === 0 && $rollback,
                ], JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/admin-membership-worker.php')], base_path(), timeout: 30);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                $this->awaitOutput($process, $i === 0 ? 'HELD' : 'CONFLICT');
                preg_match('/CONNECTION:(\d+)/', $process->getOutput(), $match);
                $this->assertNotEmpty($match);
                $connections[] = $match[1];
            }
            $this->assertNotSame($connections[0], $connections[1]);
            $this->assertStringContainsString('OWNER:'.$connections[0].PHP_EOL, $processes[1]->getOutput());
            $inputs[0]->write("release\n");
            $this->assertSame(0, $processes[0]->wait(), $processes[0]->getErrorOutput());
            $this->assertStringContainsString('RESULT:'.($rollback ? 'rollback' : 'ok'), $processes[0]->getOutput());
            $inputs[1]->write("continue\n");
            $this->assertSame(0, $processes[1]->wait(), $processes[1]->getErrorOutput());
            $this->assertStringContainsString('RESULT:'.($rollback ? 'ok' : 'protected'), $processes[1]->getOutput());
            $this->assertSame(1, User::where('role', 'admin')->count());
            $survivor = $users[$rollback ? 0 : 1]->fresh();
            $this->assertNotNull($survivor);
            $this->assertSame('admin', $survivor->role);
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
            // Poll output only; the database's lock result proves contention.
            usleep(20000);
        }
        $this->assertStringContainsString($marker, $process->getOutput(), $process->getErrorOutput());
    }
}
