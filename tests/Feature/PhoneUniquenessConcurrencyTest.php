<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PhoneAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PhoneUniquenessConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        // Workers must see committed fixtures. The next test rebuilds the guarded DB.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public function test_two_mysql_processes_cannot_claim_one_free_phone_for_different_users(): void
    {
        $phone = '+37377999111';
        $users = [
            User::factory()->create(['phone' => null]),
            User::factory()->create(['phone' => null]),
        ];
        $database = config('database');
        $processes = [];
        $inputs = [];

        try {
            foreach (['first', 'second'] as $index => $mode) {
                $input = new InputStream();
                $input->write(json_encode($database, JSON_THROW_ON_ERROR)."\n");
                $process = new Process([
                    PHP_BINARY,
                    base_path('tests/Support/assign-phone-worker.php'),
                    (string) $users[$index]->id,
                    $phone,
                    $mode,
                ], base_path(), timeout: 20);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                $this->awaitOutput($process, $mode === 'first' ? 'LOCKED' : 'ATTEMPT');
            }

            preg_match('/CONNECTION:(\d+)/', $processes[0]->getOutput(), $matches);
            $this->assertNotEmpty($matches);
            $lockName = app(PhoneAssignmentService::class)->lockName($phone);
            $this->assertSame(
                (int) $matches[1],
                (int) DB::selectOne('SELECT IS_USED_LOCK(?) AS owner', [$lockName])->owner,
                'The first independent MySQL session must own the phone lock.',
            );
            $this->assertStringNotContainsString('RESULT:', $processes[1]->getOutput());

            $inputs[0]->write("release\n");

            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput());
            }

            $this->assertStringContainsString('RESULT:success', $processes[0]->getOutput());
            $this->assertStringContainsString('RESULT:conflict', $processes[1]->getOutput());
            $this->assertSame(1, User::where('phone', $phone)->count());
        } finally {
            foreach ($inputs as $input) {
                $input->close();
            }
            foreach ($processes as $process) {
                $process->stop(0);
            }
        }
    }

    private function awaitOutput(Process $process, string $text): void
    {
        $deadline = microtime(true) + 8;
        while (! str_contains($process->getOutput(), $text) && $process->isRunning() && microtime(true) < $deadline) {
            usleep(20000);
        }

        $this->assertStringContainsString($text, $process->getOutput(), $process->getErrorOutput());
    }
}
