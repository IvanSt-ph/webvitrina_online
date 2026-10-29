<?php

namespace Tests\Feature;

use App\Models\{AdminActivityLog, SellerPlanRequest, User};
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\{InputStream, Process};
use Tests\Support\{ConcurrencyLockProbe, SafeDatabaseTruncation};
use Tests\TestCase;

class SellerPlanRequestStateConcurrencyTest extends TestCase
{
    use SafeDatabaseTruncation;

    protected function truncateDatabaseTables(): void
    {
        // Existing schema only, including when invoked via Invoke-WvTarget.
        $this->truncateTablesForAllConnections();
    }

    public static function decisions(): array
    {
        return [['approve', 'approve'], ['approve', 'reject'], ['reject', 'approve'], ['reject', 'reject']];
    }

    #[DataProvider('decisions')]
    public function test_only_one_concurrent_decision_changes_the_plan_state_and_audit(string $first, string $second): void
    {
        $seller = User::factory()->seller()->create(['seller_plan' => 'starter']);
        $admins = [User::factory()->admin()->create(), User::factory()->admin()->create()];
        $request = SellerPlanRequest::create([
            'user_id' => $seller->id, 'current_plan' => 'starter', 'requested_plan' => 'basic',
            'status' => SellerPlanRequest::STATUS_PENDING,
        ]);
        $processes = $inputs = [];
        try {
            foreach ([$first, $second] as $i => $action) {
                $input = new InputStream();
                $input->write(json_encode([
                    'database' => config('database'), 'key' => config('app.key'),
                    'admin' => $admins[$i]->id, 'request' => $request->id,
                    'action' => $action, 'first' => $i === 0,
                ], JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/seller-plan-decision-worker.php')], base_path(), timeout: 45);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                $this->awaitOutput($process, $i === 0 ? 'LOCKED' : 'LOCK_CONFLICT');
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
            $inputs[0]->write("release\n");
            $this->assertSame(0, $processes[0]->wait(), $processes[0]->getErrorOutput());
            $this->assertStringContainsString('RESULT:ok', $processes[0]->getOutput());
            $before = $request->fresh()->getAttributes();
            $inputs[1]->write("continue\n");
            $this->assertSame(0, $processes[1]->wait(), $processes[1]->getErrorOutput());
            $this->assertStringContainsString('RESULT:processed', $processes[1]->getOutput());
            $request->refresh();
            $this->assertSame($before, $request->getAttributes());
            $this->assertSame($first === 'approve' ? 'approved' : 'rejected', $request->status);
            $this->assertSame($admins[0]->id, $request->reviewed_by);
            $this->assertSame('Winner', $request->admin_note);
            $this->assertNotNull($request->reviewed_at);
            $this->assertSame($first === 'approve' ? 'basic' : 'starter', $seller->fresh()->seller_plan);
            $logs = AdminActivityLog::where('subject_type', SellerPlanRequest::class)->where('subject_id', $request->id)->get();
            $this->assertCount(1, $logs);
            $this->assertSame('seller_plan_request.'.$request->status, $logs[0]->action);
            $this->assertSame($admins[0]->id, $logs[0]->admin_id);
        } finally {
            foreach ($inputs as $input) {
                $input->close();
            }
            foreach ($processes as $process) {
                $process->stop(0);
            }
            ConcurrencyLockProbe::assertReleased(['users' => [$seller->id]], $processes);
        }
    }

    private function awaitOutput(Process $process, string $marker): void
    {
        $deadline = microtime(true) + 10;
        while (! str_contains($process->getOutput(), $marker) && $process->isRunning() && microtime(true) < $deadline) {
            usleep(20000);
        }
        $this->assertStringContainsString($marker, $process->getOutput(), $process->getErrorOutput());
    }
}
