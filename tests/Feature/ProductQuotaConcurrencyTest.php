<?php

namespace Tests\Feature;

use App\Models\{Product, User};
use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\{InputStream, Process};
use Tests\Support\{ConcurrencyLockProbe, ProductQuotaFixtures, SafeDatabaseTruncation};
use Tests\TestCase;

class ProductQuotaConcurrencyTest extends TestCase
{
    use SafeDatabaseTruncation, ProductQuotaFixtures;

    protected function truncateDatabaseTables(): void
    {
        // Invoke-WvTarget uses an existing schema. Never invoke migrate/seed here.
        $this->truncateTablesForAllConnections();
    }

    public static function races(): array
    {
        return [
            'seller pair' => [['seller', 'seller'], 9],
            'admin pair' => [['admin', 'admin'], 9],
            'mixed routes' => [['seller', 'admin'], 9],
            'direct service' => [['service', 'service'], 9],
            'four requests one slot' => [['seller', 'admin', 'service', 'seller'], 9],
            'four requests two slots' => [['admin', 'seller', 'service', 'admin'], 8],
        ];
    }

    #[DataProvider('races')]
    public function test_concurrent_creations_cannot_overrun_the_quota(array $paths, int $used): void
    {
        Storage::fake('public');
        [$seller, $data] = $this->quotaFixture($used);
        $admin = User::factory()->admin()->create();
        $processes = $inputs = [];
        try {
            foreach ($paths as $i => $path) {
                [$process, $input] = $this->worker($path === 'admin' ? $admin : $seller,
                    array_merge($data, ['title' => 'Concurrent '.$i]), $path, $i === 0 ? 'hold' : 'contend');
                $processes[] = $process;
                $inputs[] = $input;
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
            $this->assertCount(count($paths), array_unique($connections));
            foreach ($processes as $i => $process) {
                $inputs[$i]->write($i === 0 ? "release\n" : "continue\n");
                $this->assertSame(0, $process->wait(), $process->getErrorOutput());
                $this->assertStringContainsString('RESULT:'.($i < 10 - $used ? 'created' : 'limited'), $process->getOutput());
            }
            $products = Product::where('user_id', $seller->id)->get();
            $this->assertCount(10, $products);
            $created = $products->filter(fn ($product) => str_starts_with($product->title, 'Concurrent '));
            $this->assertCount(10 - $used, $created);
            $expectedFiles = [];
            foreach ($created as $product) {
                $this->assertCount(1, $product->gallery);
                foreach ([$product->image, ...$product->gallery] as $path) {
                    $expectedFiles[] = $path;
                    $expectedFiles[] = ImageService::thumbPath($path);
                }
            }
            $this->assertEqualsCanonicalizing($expectedFiles, Storage::disk('public')->allFiles());
            $this->assertDatabaseCount('attribute_values', 0);
            $this->assertDatabaseCount('product_slugs', 0);
            for ($i = 10 - $used; $i < count($paths); $i++) {
                $this->assertDatabaseMissing('products', ['user_id' => $seller->id, 'title' => 'Concurrent '.$i]);
            }
        } finally {
            $this->stopWorkers($processes, $inputs, [$seller->id]);
        }
    }

    public function test_independent_sellers_do_not_wait_for_each_others_quota_lock(): void
    {
        Storage::fake('public');
        [$first, $firstData] = $this->quotaFixture();
        [$second, $secondData] = $this->quotaFixture();
        $processes = $inputs = [];
        try {
            foreach ([[$first, $firstData, 'hold'], [$second, $secondData, 'free']] as [$seller, $data, $mode]) {
                [$process, $input] = $this->worker($seller, $data, 'service', $mode);
                $processes[] = $process;
                $inputs[] = $input;
                $process->start();
                $this->awaitOutput($process, $mode === 'hold' ? 'LOCKED' : 'RESULT:created');
            }
            $this->assertSame(0, $processes[1]->wait(), $processes[1]->getErrorOutput());
            $this->assertTrue($processes[0]->isRunning());
            $this->assertStringNotContainsString('RESULT:', $processes[0]->getOutput());
            $inputs[0]->write("release\n");
            $this->assertSame(0, $processes[0]->wait(), $processes[0]->getErrorOutput());
            $this->assertStringContainsString('RESULT:created', $processes[0]->getOutput());
            foreach ([$first, $second] as $seller) {
                $this->assertSame(10, Product::where('user_id', $seller->id)->count());
            }
        } finally {
            $this->stopWorkers($processes, $inputs, [$first->id, $second->id]);
        }
    }

    private function worker(User $actor, array $data, string $path, string $mode): array
    {
        $input = new InputStream();
        $input->write(json_encode([
            'database' => config('database'), 'key' => config('app.key'),
            'public_root' => Storage::disk('public')->path(''),
            'actor' => $actor->id, 'data' => $data, 'path' => $path, 'mode' => $mode,
        ], JSON_THROW_ON_ERROR)."\n");
        $process = new Process([PHP_BINARY, base_path('tests/Support/product-quota-worker.php')], base_path(), timeout: 45);
        $process->setInput($input);
        return [$process, $input];
    }

    private function awaitOutput(Process $process, string $marker): void
    {
        $deadline = microtime(true) + 10;
        while (! str_contains($process->getOutput(), $marker) && $process->isRunning() && microtime(true) < $deadline) {
            usleep(20000);
        }
        $this->assertStringContainsString($marker, $process->getOutput(), $process->getErrorOutput());
    }

    private function stopWorkers(array $processes, array $inputs, array $sellerIds): void
    {
        foreach ($inputs as $input) {
            $input->close();
        }
        foreach ($processes as $process) {
            $process->stop(0);
        }
        ConcurrencyLockProbe::assertReleased(['users' => $sellerIds], $processes);
    }
}
