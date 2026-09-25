<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountDeletionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        // MySQL DDL commits implicitly. Rebuild only the guarded testing DB next time.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public function test_existing_orders_and_currency_snapshots_survive_the_schema_upgrade(): void
    {
        $buyer = User::factory()->create(['name' => 'Legacy Buyer', 'phone' => '+37369123456']);
        $seller = User::factory()->create(['role' => 'seller']);
        $product = Product::create(['user_id' => $seller->id, 'title' => 'Legacy', 'slug' => 'legacy-history', 'price' => 100, 'stock' => 4]);
        $order = Order::create(['user_id' => $buyer->id, 'seller_id' => $seller->id, 'number' => Order::generateNumber(), 'status' => Order::STATUS_COMPLETED, 'total_price' => 50, 'currency' => 'MDL']);
        $item = $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 50, 'total' => 50, 'source_price' => 100, 'source_currency' => 'PRB', 'exchange_rate' => 0.5]);

        // Reconstruct the pre-PROD-04 schema, keeping real existing rows in place.
        DB::statement('ALTER TABLE orders DROP FOREIGN KEY orders_buyer_history_foreign, DROP FOREIGN KEY orders_seller_history_foreign,
            ADD CONSTRAINT orders_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            ADD CONSTRAINT orders_seller_id_foreign FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE SET NULL');
        Schema::table('orders', fn ($table) => $table->dropColumn('buyer_contact'));
        Schema::table('users', fn ($table) => $table->dropColumn('deleted_at'));
        $oldOrder = (array) DB::table('orders')->find($order->id);
        $oldItem = (array) DB::table('order_items')->find($item->id);

        $migration = require database_path('migrations/2026_09_23_000002_preserve_orders_when_accounts_are_deleted.php');
        $migration->up();
        $newOrder = (array) DB::table('orders')->find($order->id);
        $contact = json_decode($newOrder['buyer_contact'], true);
        unset($newOrder['buyer_contact']);
        $this->assertSame($oldOrder, $newOrder);
        $this->assertSame($oldItem, (array) DB::table('order_items')->find($item->id));
        $this->assertSame($buyer->name, $contact['name']);
        $this->assertSame($buyer->email, $contact['email']);
        $this->assertSame($buyer->phone, $contact['phone']);
        $rules = DB::table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::connection()->getDatabaseName())
            ->whereIn('CONSTRAINT_NAME', ['orders_buyer_history_foreign', 'orders_seller_history_foreign'])
            ->pluck('DELETE_RULE')->all();
        $this->assertSame(['RESTRICT', 'RESTRICT'], $rules);
        $buyer->delete();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'user_id' => $buyer->id]);
        $this->assertSame($oldItem, (array) DB::table('order_items')->find($item->id));

        $this->expectException(\RuntimeException::class);
        $migration->down();
    }
}
