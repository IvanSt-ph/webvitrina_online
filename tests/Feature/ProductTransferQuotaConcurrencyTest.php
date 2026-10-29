<?php

namespace Tests\Feature;

use App\Models\{Product, User};
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\{InputStream, Process};
use Tests\Support\{ConcurrencyLockProbe, ProductQuotaFixtures, SafeDatabaseTruncation};
use Tests\TestCase;

class ProductTransferQuotaConcurrencyTest extends TestCase
{
    use SafeDatabaseTruncation, ProductQuotaFixtures;

    protected function truncateDatabaseTables(): void
    {
        // Existing schema only; compatible with Invoke-WvTarget, no migrations/seeding.
        $this->truncateTablesForAllConnections();
    }

    public static function races(): array
    {
        return [['create-first'], ['transfer-first'], ['two-transfers'], ['opposing-transfers'], ['stale-source']];
    }

    #[DataProvider('races')]
    public function test_transfers_and_creation_share_capacity_without_reversing_user_lock_order(string $race): void
    {
        Storage::fake('public');
        [$source, $sourceData] = $this->quotaFixture(2);
        [$recipient, $recipientData] = $this->quotaFixture(9);
        $admin = User::factory()->admin()->create();
        $sourceProducts = Product::where('user_id', $source->id)->orderBy('id')->get();
        $recipientProduct = Product::where('user_id', $recipient->id)->firstOrFail();
        $third = null;
        $thirdData = [];
        if ($race === 'stale-source') {
            [$third, $thirdData] = $this->quotaFixture(9);
        }
        $create = ['actor' => $recipient->id, 'data' => $recipientData];
        $transfer = ['actor' => $admin->id, 'data' => $recipientData, 'product' => $sourceProducts[0]->id];
        $operations = match ($race) {
            'create-first' => [$create, $transfer],
            'transfer-first' => [$transfer, $create],
            'two-transfers' => [$transfer, ['actor' => $admin->id, 'data' => $recipientData, 'product' => $sourceProducts[1]->id]],
            'opposing-transfers' => [$transfer, ['actor' => $admin->id, 'data' => $sourceData, 'product' => $recipientProduct->id]],
            'stale-source' => [$transfer, ['actor' => $admin->id, 'data' => $thirdData, 'product' => $sourceProducts[0]->id]],
        };
        $inputs = $processes = [];
        try {
            foreach ($operations as $i => $operation) {
                $input = new InputStream();
                $input->write(json_encode(array_merge($operation, [
                    'database' => config('database'), 'key' => config('app.key'),
                    'public_root' => Storage::disk('public')->path(''), 'first' => $i === 0,
                ]), JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/product-transfer-quota-worker.php')], base_path(), timeout: 45);
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
            $opposing = $race === 'opposing-transfers';
            $secondResult = $race === 'stale-source' ? 'stale' : ($opposing ? 'ok' : 'limited');
            $this->assertStringContainsString('RESULT:'.$secondResult, $processes[1]->getOutput());
            $this->assertSame($opposing ? 9 : 10, Product::where('user_id', $recipient->id)->count());
            $this->assertSame($opposing || $race === 'create-first' ? 2 : 1, Product::where('user_id', $source->id)->count());
            $this->assertSame($race === 'create-first' ? $source->id : $recipient->id, $sourceProducts[0]->fresh()->user_id);
            $this->assertSame($source->id, $sourceProducts[1]->fresh()->user_id);
            $this->assertSame($opposing ? $source->id : $recipient->id, $recipientProduct->fresh()->user_id);
            $this->assertSame(($race === 'create-first' ? 12 : 11) + ($third ? 9 : 0), Product::count());
            if ($third) {
                $this->assertSame(9, Product::where('user_id', $third->id)->count());
            }
            $this->assertCount($race === 'create-first' ? 4 : 0, Storage::disk('public')->allFiles());
            $this->assertDatabaseCount('product_slugs', 0);
            $this->assertDatabaseCount('attribute_values', 0);
        } finally {
            foreach ($inputs as $input) {
                $input->close();
            }
            foreach ($processes as $process) {
                $process->stop(0);
            }
            ConcurrencyLockProbe::assertReleased([
                'users' => array_values(array_filter([$source->id, $recipient->id, $third?->id])),
                'products' => [$sourceProducts[0]->id, $sourceProducts[1]->id, $recipientProduct->id],
            ], $processes);
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
