<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SellerSalesAfterCommitTest extends TestCase
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

        // afterCommit must be exercised against real commits, not a test wrapper.
        $this->beforeApplicationDestroyed(function (): void {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    public function test_real_commit_updates_sales_count_once_for_an_order_with_multiple_items(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $shop = Shop::create(['user_id' => $seller->id, 'name' => 'Committed sales shop']);
        $order = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => Order::generateNumber(),
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'cash',
            'delivery_method' => 'pickup',
            'total_price' => 200,
            'currency' => 'PRB',
        ]);

        DB::table('orders')->where('id', $order->id)->update([
            'workflow_version' => Order::WORKFLOW_PICKUP,
            'payment_status' => Order::PAYMENT_UNPAID,
        ]);
        $order->refresh();

        foreach (['committed-sale-one', 'committed-sale-two'] as $slug) {
            $product = Product::create([
                'user_id' => $seller->id,
                'title' => $slug,
                'slug' => $slug,
                'sku' => strtoupper($slug),
                'price' => 100,
                'currency_base' => 'PRB',
                'stock' => 10,
                'status' => Product::STATUS_ACTIVE,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 100,
                'total' => 100,
                'source_price' => 100,
                'source_currency' => 'PRB',
                'exchange_rate' => 1,
            ]);
        }

        $this->assertSame(0, (int) $shop->fresh()->sales_count);

        $this->actingAs($seller)->post(route('seller.orders.updateStatus', $order), [
            'status' => Order::STATUS_PROCESSING,
        ])->assertSessionHasNoErrors();
        $this->actingAs($seller)->post(route('seller.orders.updateStatus', $order), [
            'status' => Order::STATUS_READY_FOR_PICKUP,
        ])->assertSessionHasNoErrors();
        $this->actingAs($seller)
            ->post(route('seller.orders.confirmPayment', $order))
            ->assertSessionHasNoErrors();
        $this->actingAs($buyer)
            ->post(route('orders.confirmDelivery', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
        $this->assertSame(1, (int) $shop->fresh()->sales_count);

        $this->actingAs($seller)
            ->post(route('seller.orders.confirmPayment', $order))
            ->assertSessionHasNoErrors();
        $this->actingAs($buyer)
            ->post(route('orders.confirmDelivery', $order))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, (int) $shop->fresh()->sales_count);
    }
}
