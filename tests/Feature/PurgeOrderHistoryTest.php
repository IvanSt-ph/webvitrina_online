<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\BackupWriteBarrier;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeOrderHistoryTest extends TestCase
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
        Storage::fake('public');
        config(['backup.lock_path' => storage_path('framework/testing/prod11-purge.lock')]);
    }

    protected function tearDown(): void
    {
        try {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            // Real commits exercise file callbacks; remove fixtures after the final test too.
            $this->truncateTablesForAllConnections();
            Storage::disk('public')->deleteDirectory('products');
            app(BackupWriteBarrier::class)->run(fn () => $this->assertTrue(true), exclusive: true);
        } finally {
            parent::tearDown();
        }
    }

    public function test_historical_product_is_skipped_with_files_and_order_intact_and_next_product_is_purged(): void
    {
        $historical = $this->oldProduct('historical');
        $order = Order::create([
            'user_id' => User::factory()->create()->id, 'seller_id' => $historical->user_id,
            'number' => Order::generateNumber(), 'status' => Order::STATUS_PENDING,
            'currency' => 'MDL', 'total_price' => 100, 'payment_method' => 'cash', 'delivery_method' => 'pickup',
        ]);
        $item = $order->items()->create(['product_id' => $historical->id, 'quantity' => 1, 'price' => 100, 'total' => 100]);
        $next = $this->oldProduct('eligible');
        Log::spy();

        foreach ([1, 0] as $purged) {
            $this->artisan('products:purge-old')->expectsOutput("Удалено товаров: $purged")->assertSuccessful();
            $this->assertSoftDeleted($historical);
            $this->assertDatabaseHas('orders', ['id' => $order->id]);
            $this->assertDatabaseHas('order_items', ['id' => $item->id, 'order_id' => $order->id, 'product_id' => $historical->id]);
            $this->assertSame($historical->id, $item->fresh()->product->id);
            foreach ([$historical->image, ...$historical->gallery] as $path) {
                Storage::disk('public')->assertExists([$path, ImageService::thumbPath($path)]);
            }
            $this->assertDatabaseMissing('products', ['id' => $next->id]);
            foreach ([$next->image, ...$next->gallery] as $path) {
                Storage::disk('public')->assertMissing([$path, ImageService::thumbPath($path)]);
            }
        }
        Log::shouldHaveReceived('info')->with('Product purge skipped: required by order history', ['product_id' => $historical->id])->twice();
    }

    public function test_unexpected_delete_failure_propagates_and_preserves_files(): void
    {
        $product = $this->oldProduct('failure');
        Log::spy();
        $events = Product::getEventDispatcher();
        Product::setEventDispatcher(clone $events);
        Product::forceDeleting(fn () => throw new \RuntimeException('unexpected purge failure'));
        try {
            $this->artisan('products:purge-old')->run();
            $this->fail('Unexpected failure must not become a successful historical skip.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('unexpected purge failure', $exception->getMessage());
        } finally {
            Product::setEventDispatcher($events);
        }
        $this->assertSoftDeleted($product);
        Storage::disk('public')->assertExists([$product->image, ImageService::thumbPath($product->image)]);
        Log::shouldNotHaveReceived('info', ['Product purge skipped: required by order history', ['product_id' => $product->id]]);
    }

    public function test_purge_cannot_mutate_while_backup_holds_barrier(): void
    {
        $product = $this->oldProduct('barrier');
        config(['backup.lock_timeout' => 0]);
        $handle = fopen(config('backup.lock_path'), 'c+b');
        try {
            $this->assertTrue(flock($handle, LOCK_EX | LOCK_NB));
            try {
                $this->artisan('products:purge-old')->run();
                $this->fail('Purge must wait for backup.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('Backup write barrier busy', $exception->getMessage());
            }
            $this->assertSoftDeleted($product);
            Storage::disk('public')->assertExists($product->image);
        } finally {
            fclose($handle);
        }
        $this->artisan('products:purge-old')->assertSuccessful();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing($product->image);
    }

    private function oldProduct(string $name): Product
    {
        $paths = ["products/medium/$name.webp", "products/gallery/medium/$name.webp"];
        foreach ($paths as $path) {
            Storage::disk('public')->put($path, 'fixture');
            Storage::disk('public')->put(ImageService::thumbPath($path), 'fixture');
        }
        $product = Product::create([
            'user_id' => User::factory()->create(['role' => 'seller'])->id,
            'title' => $name, 'price' => 100, 'stock' => 5, 'status' => 'draft',
            'image' => $paths[0], 'gallery' => [$paths[1]],
        ]);
        $product->delete();
        $product->forceFill(['deleted_at' => now()->subDays(100)])->save();

        return $product;
    }
}
