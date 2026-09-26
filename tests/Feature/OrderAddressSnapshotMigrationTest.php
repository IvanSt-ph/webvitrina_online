<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderAddressSnapshotMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public function test_backfill_preserves_existing_text_and_available_fields_without_mutating_other_order_data(): void
    {
        $buyer = User::factory()->create();
        $address = $buyer->addresses()->create(['country' => 'Legacy Country', 'city' => 'Legacy City',
            'street' => 'Legacy Street', 'house' => '7', 'entrance' => '2', 'apartment' => '8',
            'postal_code' => '3300', 'comment' => 'Legacy comment']);
        $orders = collect([
            ['address_id' => $address->id],
            ['address_id' => $address->id, 'delivery_address' => 'Older order-local address'],
            ['delivery_address' => 'Text only address'],
            [],
        ])->map(fn ($data) => Order::create(array_merge([
            'user_id' => $buyer->id, 'number' => Order::generateNumber(), 'currency' => 'MDL', 'total_price' => 123,
        ], $data)));
        Schema::table('orders', fn ($table) => $table->dropColumn('address_snapshot'));
        $before = DB::table('orders')->orderBy('id')->get()->map(fn ($row) => (array) $row);
        $migration = require database_path('migrations/2026_09_25_000001_add_address_snapshot_to_orders.php');
        $migration->up();
        foreach ($orders as $index => $order) {
            $row = (array) DB::table('orders')->find($order->id);
            unset($row['address_snapshot']);
            $this->assertSame($before[$index], $row);
        }
        $this->assertSame($address->full, $orders[0]->fresh()->address_snapshot['full']);
        foreach (['country', 'city', 'street', 'house', 'entrance', 'apartment', 'postal_code', 'comment'] as $field) {
            $this->assertSame($address->$field, $orders[0]->fresh()->address_snapshot[$field]);
        }
        $this->assertSame('Older order-local address', $orders[1]->fresh()->address_snapshot['full']);
        $this->assertSame('Text only address', $orders[2]->fresh()->address_snapshot['full']);
        $this->assertSame('', $orders[3]->fresh()->address_snapshot['full']);
        $captured = $orders[0]->fresh()->address_snapshot;
        $address->update(['street' => 'Changed later']);
        $migration->up();
        $address->delete();
        $this->assertSame($captured, $orders[0]->fresh()->address_snapshot);
        $this->expectException(\RuntimeException::class);
        $migration->down();
    }
}
