<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderAddressSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private function legacyOrder(int $sellerCount = 1): array
    {
        $buyer = User::factory()->create(['role' => 'buyer', 'name' => 'Original Recipient', 'phone' => '+37369123456']);
        $address = $buyer->addresses()->create([
            'country' => 'Original Country', 'city' => 'Original City', 'street' => 'Original Street',
            'house' => '12', 'entrance' => '3', 'apartment' => '45', 'postal_code' => '3300',
            'comment' => 'Original delivery instructions',
        ]);
        for ($i = 0; $i < $sellerCount; $i++) {
            $seller = User::factory()->create(['role' => 'seller']);
            $product = Product::create(['user_id' => $seller->id, 'title' => 'Snapshot product',
                'slug' => 'snapshot-product-'.$i, 'price' => 100, 'currency_base' => 'MDL',
                'stock' => 5, 'status' => Product::STATUS_ACTIVE]);
            // A legacy delivery order remains readable; new checkout offers pickup only.
            $order = Order::create([
                'user_id' => $buyer->id, 'seller_id' => $seller->id,
                'address_id' => $address->id, 'number' => Order::generateNumber(),
                'status' => Order::STATUS_PENDING, 'payment_method' => 'cash',
                'delivery_method' => 'courier', 'total_price' => 100, 'currency' => 'MDL',
            ]);
            OrderItem::create([
                'order_id' => $order->id, 'product_id' => $product->id,
                'quantity' => 1, 'price' => 100, 'total' => 100,
                'source_price' => 100, 'source_currency' => 'MDL', 'exchange_rate' => 1,
            ]);
        }

        return [$buyer, $address, Order::orderBy('id')->get()];
    }

    public function test_legacy_order_keeps_every_address_field_for_every_seller(): void
    {
        [, $address, $orders] = $this->legacyOrder(2);
        $this->assertCount(2, $orders);
        foreach ($orders as $order) {
            foreach (['country', 'city', 'street', 'house', 'entrance', 'apartment', 'postal_code', 'comment'] as $field) {
                $this->assertSame($address->$field, $order->address_snapshot[$field]);
            }
            $this->assertSame($address->full, $order->address_snapshot['full']);
            $this->assertSame($address->id, $order->address_id);
        }
    }

    public function test_address_and_profile_edits_and_address_deletion_preserve_all_views_and_money(): void
    {
        [$buyer, $address, $orders] = $this->legacyOrder();
        $order = $orders->sole();
        $snapshot = $order->address_snapshot;
        $money = $order->only(['total_price', 'currency']);
        $item = $order->items()->sole()->getRawOriginal();
        $this->put(route('addresses.update', $address), [
            'country' => 'Changed Country', 'city' => 'Changed City', 'street' => 'Changed Street',
            'house' => '99', 'comment' => 'Changed instructions',
        ])->assertSessionHasNoErrors();
        $buyer->update(['name' => 'Changed Recipient', 'phone' => '+37369999999']);
        $this->assertSame($snapshot, $order->fresh()->address_snapshot);
        $this->assertSame('Original Recipient', $order->fresh()->buyer_name);
        $this->assertSame('+37369123456', $order->fresh()->buyer_phone);
        $this->assertViews($buyer, $order);
        $this->actingAs($buyer)->delete(route('addresses.destroy', $address))->assertRedirect();
        $this->assertDatabaseMissing('user_addresses', ['id' => $address->id]);
        $this->assertNull($order->fresh()->address_id);
        $this->assertViews($buyer, $order);
        $this->actingAs($order->seller)->post(route('seller.orders.updateStatus', $order), [
            'status' => Order::STATUS_PROCESSING,
        ])->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.orders.updateStatus', $order), [
                'status' => Order::STATUS_CANCELED, 'change_reason' => 'Snapshot preservation test',
            ])->assertSessionHasNoErrors();
        $this->assertSame(Order::STATUS_CANCELED, $order->fresh()->status);
        $this->assertSame($snapshot, $order->fresh()->address_snapshot);
        $this->assertSame($money, $order->fresh()->only(['total_price', 'currency']));
        $this->assertSame($item, $order->items()->sole()->getRawOriginal());
    }

    private function assertViews(User $buyer, Order $order): void
    {
        foreach ([[$buyer, 'orders.show'], [$order->seller, 'seller.orders.show'],
            [User::factory()->create(['role' => 'admin']), 'admin.orders.show']] as [$viewer, $route]) {
            $this->actingAs($viewer)->get(route($route, $order))->assertOk()
                ->assertSee($order->address_snapshot['full'])->assertSee('Original delivery instructions')
                ->assertDontSee('Changed Street')->assertDontSee('Changed instructions');
        }
    }

    public function test_snapshot_cannot_be_replaced_even_by_quiet_model_save(): void
    {
        [, , $orders] = $this->legacyOrder();
        $order = $orders->sole();
        $original = $order->address_snapshot;
        foreach ([false, true] as $quiet) {
            $order->address_snapshot = ['full' => 'Replacement'];
            try {
                $quiet ? $order->saveQuietly() : $order->save();
                $this->fail('Snapshot replacement must fail.');
            } catch (\LogicException $exception) {
                $this->assertSame('Order address snapshot is immutable.', $exception->getMessage());
            }
            $this->assertSame($original, $order->fresh()->address_snapshot);
        }
    }

    public function test_missing_snapshot_never_falls_back_to_mutable_relation(): void
    {
        [$buyer, , $orders] = $this->legacyOrder();
        $order = $orders->sole();
        \Illuminate\Support\Facades\DB::table('orders')->where('id', $order->id)->update(['address_snapshot' => null]);
        $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()->assertDontSee('Original Street');
    }
}
