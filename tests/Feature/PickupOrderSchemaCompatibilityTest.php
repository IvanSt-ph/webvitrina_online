<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderAdminDecision;
use App\Models\OrderEvent;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PickupOrderSchemaCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        // This class exercises MySQL DDL; rebuild the guarded test schema for the next test.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public function test_upgrade_keeps_existing_order_and_item_history_unchanged(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $seller->shop()->create(['name' => 'Original shop']);
        $product = Product::create([
            'user_id' => $seller->id, 'title' => 'Original product', 'slug' => 'pickup-legacy-product',
            'sku' => 'LEGACY-1', 'price' => 125, 'currency_base' => 'PRB',
            'stock' => 4, 'status' => Product::STATUS_ACTIVE,
        ]);

        $order = Order::create([
            'user_id' => $buyer->id, 'seller_id' => $seller->id,
            'number' => Order::generateNumber(), 'status' => Order::STATUS_SHIPPED,
            'payment_method' => 'bank_transfer', 'delivery_method' => 'courier',
            'total_price' => 280, 'currency' => 'PRB', 'paid_at' => now()->subDay(),
        ]);
        $item = new OrderItem([
            'order_id' => $order->id, 'product_id' => $product->id,
            'quantity' => 1, 'price' => 125, 'total' => 125,
            'source_price' => 125, 'source_currency' => 'PRB', 'exchange_rate' => 1,
        ]);
        $item->forceFill([
            'product_title' => 'Original product', 'product_sku' => 'LEGACY-1',
            'identity_snapshot_source' => 'checkout',
        ])->save();

        // Reconstruct the immediately preceding schema inside webv3_testing only.
        Schema::drop('order_admin_decisions');
        Schema::drop('order_events');
        DB::statement('ALTER TABLE orders
            DROP COLUMN workflow_version,
            DROP COLUMN payment_status,
            DROP COLUMN ready_for_pickup_at,
            DROP COLUMN buyer_confirmed_at,
            DROP COLUMN completed_at,
            DROP COLUMN confirmation_requested_at');
        DB::statement("ALTER TABLE orders MODIFY COLUMN status
            ENUM('pending','processing','paid','shipped','delivered','completed','canceled')
            NOT NULL DEFAULT 'pending'");

        $beforeOrder = (array) DB::table('orders')->find($order->id);
        $beforeItem = (array) DB::table('order_items')->find($item->id);
        $beforeStock = $product->fresh()->stock;

        $migration = require database_path('migrations/2026_10_09_000001_prepare_pickup_order_workflow.php');
        $migration->up();

        $afterOrder = (array) DB::table('orders')->find($order->id);
        foreach ([
            'workflow_version', 'payment_status', 'ready_for_pickup_at',
            'buyer_confirmed_at', 'completed_at', 'confirmation_requested_at',
        ] as $column) {
            $this->assertArrayHasKey($column, $afterOrder);
            $this->assertNull($afterOrder[$column]);
            unset($afterOrder[$column]);
        }

        $this->assertSame($beforeOrder, $afterOrder);
        $this->assertSame($beforeItem, (array) DB::table('order_items')->find($item->id));
        $this->assertSame($beforeStock, $product->fresh()->stock);
        $this->assertSame('Original shop', $order->fresh()->seller_snapshot['shop_name']);
        $this->assertSame('Original product', $item->fresh()->historical_title);
        $this->assertSame(0, OrderEvent::count());
        $this->assertSame(0, OrderAdminDecision::count());

        $statusType = DB::selectOne(
            'SELECT COLUMN_TYPE AS type FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['orders', 'status'],
        )->type;
        $this->assertSame(
            "enum('pending','processing','paid','shipped','delivered','completed','canceled','ready_for_pickup')",
            $statusType,
        );
        $this->assertNotContains(Order::STATUS_READY_FOR_PICKUP, Order::allStatuses());
    }

    public function test_new_history_tables_keep_actors_and_cannot_be_changed_through_models(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::create([
            'user_id' => $buyer->id, 'seller_id' => $seller->id,
            'number' => Order::generateNumber(), 'status' => Order::STATUS_PENDING,
            'currency' => 'PRB', 'total_price' => 50,
        ]);

        $event = $order->events()->create([
            'actor_id' => $seller->id, 'actor_role' => 'seller',
            'event_type' => 'pickup_confirmation_requested',
            'from_status' => Order::STATUS_PENDING,
            'event_key' => 'pickup-event-'.$order->id,
        ]);
        $decision = $order->adminDecisions()->create([
            'admin_id' => $admin->id, 'decision' => 'request_evidence',
            'reason' => 'Need proof of handover.',
        ]);

        $this->assertSame($seller->id, $order->events()->firstOrFail()->actor->id);
        $this->assertSame($admin->id, $order->adminDecisions()->firstOrFail()->admin->id);
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
        $this->assertNull($order->fresh()->buyer_confirmed_at);

        foreach ([$event, $decision] as $record) {
            try {
                $record->saveQuietly();
                $this->fail('Existing history must not be saved again.');
            } catch (\LogicException) {
                // Expected for both event and decision models.
            }
            try {
                $record->delete();
                $this->fail('Existing history must not be deleted.');
            } catch (\LogicException) {
                // Expected for both event and decision models.
            }
        }

        $this->assertSame(1, $order->events()->count());
        $this->assertSame(1, $order->adminDecisions()->count());
    }
}
