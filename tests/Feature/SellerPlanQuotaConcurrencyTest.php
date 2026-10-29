<?php

namespace Tests\Feature;

use App\Models\{Product, SellerPlanRequest, User};
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\{InputStream, Process};
use Tests\Support\{ConcurrencyLockProbe, ProductQuotaFixtures, SafeDatabaseTruncation};
use Tests\TestCase;

class SellerPlanQuotaConcurrencyTest extends TestCase
{
    use SafeDatabaseTruncation, ProductQuotaFixtures;

    protected function truncateDatabaseTables(): void
    {
        // Invoke-WvTarget supplies the existing schema; never migrate or seed.
        $this->truncateTablesForAllConnections();
    }

    public static function races(): array
    {
        $cases = [];
        foreach (['user', 'request'] as $path) {
            foreach (['create', 'transfer'] as $mutation) {
                foreach ([true, false] as $planFirst) {
                    foreach ([9, 10] as $used) {
                        $cases[] = [$path, $mutation, $planFirst, $used];
                    }
                }
            }
        }
        return $cases;
    }

    #[DataProvider('races')]
    public function test_plan_assignment_serializes_with_catalog_growth(string $path, string $mutation, bool $planFirst, int $used): void
    {
        Storage::fake('public');
        [$source] = $this->quotaFixture(1);
        [$seller, $data] = $this->quotaFixture($used, 'basic');
        $admin = User::factory()->admin()->create();
        $product = Product::where('user_id', $source->id)->firstOrFail();
        $planRequest = SellerPlanRequest::create([
            'user_id' => $seller->id, 'current_plan' => 'basic', 'requested_plan' => 'starter',
            'status' => SellerPlanRequest::STATUS_PENDING,
        ]);
        $processes = $inputs = [];
        try {
            foreach ($planFirst ? ['plan', $mutation] : [$mutation, 'plan'] as $i => $action) {
                $input = new InputStream();
                $input->write(json_encode([
                    'database' => config('database'), 'key' => config('app.key'),
                    'public_root' => Storage::disk('public')->path(''),
                    'admin' => $admin->id, 'seller' => $seller->id, 'plan_request' => $planRequest->id,
                    'product' => $product->id, 'data' => $data, 'path' => $path,
                    'action' => $action, 'first' => $i === 0,
                ], JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/seller-plan-quota-worker.php')], base_path(), timeout: 45);
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
            $inputs[1]->write("continue\n");
            $this->assertSame(0, $processes[1]->wait(), $processes[1]->getErrorOutput());
            $this->assertStringContainsString('RESULT:'.($used === 9 ? 'ok' : 'limited'), $processes[1]->getOutput());

            $assigned = $planFirst || $used === 9;
            $grew = ! $planFirst || $used === 9;
            $this->assertSame($assigned ? 'starter' : 'basic', $seller->fresh()->seller_plan);
            $this->assertSame($used + (int) $grew, Product::where('user_id', $seller->id)->count());
            $this->assertSame($path === 'request' && $assigned ? SellerPlanRequest::STATUS_APPROVED : SellerPlanRequest::STATUS_PENDING,
                $planRequest->fresh()->status);
            $this->assertSame($mutation === 'transfer' && $grew ? $seller->id : $source->id, $product->fresh()->user_id);
            $this->assertSame($used + 1 + (int) ($mutation === 'create' && $grew), Product::count());
            $this->assertCount($mutation === 'create' && $grew ? 4 : 0, Storage::disk('public')->allFiles());
            $this->assertDatabaseCount('attribute_values', 0);
            $this->assertDatabaseCount('product_slugs', 0);
        } finally {
            foreach ($inputs as $input) {
                $input->close();
            }
            foreach ($processes as $process) {
                $process->stop(0);
            }
            ConcurrencyLockProbe::assertReleased(['users' => [$source->id, $seller->id], 'products' => [$product->id]], $processes);
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
