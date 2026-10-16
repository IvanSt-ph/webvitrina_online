<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SellerMetricsConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->beforeApplicationDestroyed(function (): void {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_parallel_moderation_serializes_reputation_after_commit(): void
    {
        [$seller, $shop] = $this->sellerWithShop('Concurrent reputation');
        $reviews = collect([5, 3])->map(function (int $rating, int $index) use ($seller): Review {
            $product = $this->product($seller, 'concurrent-review-'.$index);

            return Review::create([
                'user_id' => User::factory()->create(['role' => 'buyer'])->id,
                'product_id' => $product->id,
                'rating' => $rating,
                'status' => Review::STATUS_PENDING,
            ]);
        });

        $this->runWhileShopLocked($shop, $reviews
            ->map(fn (Review $review) => ['review', (string) $review->id])
            ->all());

        $this->assertSame(4.0, (float) $shop->fresh()->rating);
        $this->assertSame(2, (int) $shop->fresh()->reviews_count);
    }

    public function test_parallel_completed_orders_are_counted_once_each(): void
    {
        [$seller, $shop] = $this->sellerWithShop('Concurrent sales');
        $workers = [];

        foreach ([1, 2] as $index) {
            $buyer = User::factory()->create(['role' => 'buyer']);
            $order = Order::create([
                'user_id' => $buyer->id,
                'seller_id' => $seller->id,
                'number' => Order::generateNumber(),
                'status' => Order::STATUS_PENDING,
                'payment_method' => 'cash',
                'delivery_method' => 'pickup',
                'paid_at' => now(),
                'total_price' => 100,
                'currency' => 'PRB',
            ]);
            DB::table('orders')->where('id', $order->id)->update([
                'workflow_version' => Order::WORKFLOW_PICKUP,
                'status' => Order::STATUS_READY_FOR_PICKUP,
                'payment_status' => Order::PAYMENT_SELLER_CONFIRMED,
                'ready_for_pickup_at' => now(),
            ]);
            $order->refresh();
            $product = $this->product($seller, 'concurrent-sale-'.$index);
            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $index,
                'price' => 100,
                'total' => 100 * $index,
                'source_price' => 100,
                'source_currency' => 'PRB',
                'exchange_rate' => 1,
            ]);
            $workers[] = ['sale', (string) $order->id, (string) $buyer->id];
        }

        $this->runWhileShopLocked($shop, $workers);

        $this->assertSame(2, (int) $shop->fresh()->sales_count);
        $this->assertSame(2, Order::where('seller_id', $seller->id)
            ->where('status', Order::STATUS_COMPLETED)->count());
    }

    private function runWhileShopLocked(Shop $shop, array $workerArguments): void
    {
        $processes = [];
        $inputs = [];
        $monitorConnectionName = 'seller_metrics_monitor';
        $defaultConnectionName = config('database.default');
        config([
            "database.connections.{$monitorConnectionName}" => config("database.connections.{$defaultConnectionName}"),
        ]);
        DB::purge($monitorConnectionName);
        $monitor = DB::connection($monitorConnectionName);

        DB::beginTransaction();
        Shop::whereKey($shop->id)->lockForUpdate()->firstOrFail();

        try {
            foreach ($workerArguments as $arguments) {
                $input = new InputStream();
                $input->write(json_encode(config('database'), JSON_THROW_ON_ERROR)."\n");
                $process = new Process([
                    PHP_BINARY,
                    base_path('tests/Support/shop-metrics-worker.php'),
                    ...$arguments,
                ], base_path(), timeout: 30);
                $process->setInput($input);
                $process->start();
                $this->awaitOutput($process, 'READY');
                $inputs[] = $input;
                $processes[] = $process;
            }

            foreach ($inputs as $input) {
                $input->write("go\n");
            }
            foreach ($processes as $process) {
                $this->awaitOutput($process, 'ATTEMPT');
            }

            // ATTEMPT only means the domain operation has started. Wait until
            // both independent connections are immediately before the real
            // SELECT ... FOR UPDATE on the same shop row.
            foreach ($processes as $process) {
                $this->awaitOutput($process, 'SHOP_LOCK_READY');
            }
            foreach ($inputs as $input) {
                $input->write("lock\n");
            }
            foreach ($processes as $process) {
                $this->awaitOutput($process, 'SHOP_LOCK_ATTEMPT');
            }

            $this->awaitAllLockWaits($processes, $monitor);
            foreach ($processes as $process) {
                $this->assertStringNotContainsString('DONE', $process->getOutput());
            }

            DB::commit();

            foreach ($processes as $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput());
                $this->assertStringContainsString('DONE', $process->getOutput());
            }
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            foreach ($inputs as $input) {
                $input->close();
            }
            foreach ($processes as $process) {
                $process->stop(0);
            }
            DB::purge($monitorConnectionName);
            config(["database.connections.{$monitorConnectionName}" => null]);
        }
    }

    private function awaitAllLockWaits(array $processes, ConnectionInterface $monitor): void
    {
        $connectionIds = collect($processes)->map(function (Process $process): int {
            preg_match('/CONNECTION:(\d+)/', $process->getOutput(), $matches);
            $this->assertNotEmpty($matches);

            return (int) $matches[1];
        })->all();
        $this->assertCount(count($connectionIds), array_unique($connectionIds),
            'Metrics workers must use independent MySQL connections.');
        $deadline = microtime(true) + 8;
        $waitingCount = 0;

        do {
            foreach ($processes as $index => $process) {
                if (!$process->isRunning()) {
                    $this->fail(sprintf(
                        "Metrics worker %d exited before the parent released the shop lock.\nSTDOUT:\n%s\nSTDERR:\n%s",
                        $index + 1,
                        $process->getOutput(),
                        $process->getErrorOutput(),
                    ));
                }
            }

            $waitingCount = $monitor->table('information_schema.INNODB_TRX')
                ->whereIn('trx_mysql_thread_id', $connectionIds)
                ->where('trx_state', 'LOCK WAIT')
                ->count();
            if ($waitingCount !== count($connectionIds)) {
                usleep(50000);
            }
        } while ($waitingCount !== count($connectionIds) && microtime(true) < $deadline);

        $this->assertSame(
            count($connectionIds),
            $waitingCount,
            'Every metrics worker must wait on the same locked shop row.'
        );
    }

    private function awaitOutput(Process $process, string $text): void
    {
        $deadline = microtime(true) + 8;
        while (!str_contains($process->getOutput(), $text) && $process->isRunning() && microtime(true) < $deadline) {
            usleep(20000);
        }

        $this->assertStringContainsString($text, $process->getOutput(), $process->getErrorOutput());
    }

    private function sellerWithShop(string $name): array
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $shop = Shop::create(['user_id' => $seller->id, 'name' => $name]);

        return [$seller, $shop];
    }

    private function product(User $seller, string $slug): Product
    {
        return Product::create([
            'user_id' => $seller->id,
            'title' => $slug,
            'slug' => $slug,
            'sku' => strtoupper($slug),
            'price' => 100,
            'currency_base' => 'PRB',
            'stock' => 10,
            'status' => Product::STATUS_ACTIVE,
        ]);
    }
}
