<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_seller_cancellation_restores_each_item_once_and_preserves_money(): void
    {
        [$buyer, $seller, $products] = $this->cart();
        $order = $this->checkout($buyer);
        $this->assertSame([8, 7], $products->map(fn ($p) => $p->fresh()->stock)->all());
        $items = $order->items()->get()->map->getRawOriginal()->all();
        // Read the persisted DECIMAL(10,2), not the in-memory float representation of the fixture.
        $money = $order->fresh()->only(['total_price', 'currency']);
        $this->assertSame('500.00', $money['total_price']);
        $this->assertSame('PRB', $money['currency']);

        $this->actingAs($buyer)->post(route('orders.requestCancellation', $order), [
            'cancellation_reason' => 'Прошу отменить',
        ])->assertSessionHasNoErrors();
        $this->assertSame([8, 7], $products->map(fn ($p) => $p->fresh()->stock)->all());
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);

        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($seller)->post(route('seller.orders.updateStatus', $order), [
                'status' => Order::STATUS_CANCELED,
            ])->assertSessionHasNoErrors()->assertSessionHas('success');
            $this->assertSame([10, 10], $products->map(fn ($p) => $p->fresh()->stock)->all());
        }
        $this->assertSame($money, $order->fresh()->only(['total_price', 'currency']));
        $this->assertSame($items, $order->items()->get()->map->getRawOriginal()->all());
    }

    public function test_admin_cancel_is_idempotent_and_cannot_reactivate(): void
    {
        [$buyer, , $products] = $this->cart();
        $order = $this->checkout($buyer);
        $admin = User::factory()->create(['role' => 'admin']);
        $order->setStatus(Order::STATUS_PROCESSING);
        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($admin)->post(route('admin.orders.updateStatus', $order), [
                'status' => Order::STATUS_CANCELED, 'change_reason' => 'Отмена',
            ])->assertSessionHasNoErrors();
            $this->assertSame([10, 10], $products->map(fn ($p) => $p->fresh()->stock)->all());
        }
        foreach (array_diff(Order::allStatuses(), [Order::STATUS_CANCELED]) as $status) {
            $this->postJson(route('admin.orders.updateStatus', $order), ['status' => $status])
                ->assertUnprocessable()->assertJsonValidationErrors('status');
        }
        $this->assertSame(Order::STATUS_CANCELED, $order->fresh()->status);
        $this->assertSame([10, 10], $products->map(fn ($p) => $p->fresh()->stock)->all());
    }

    public function test_legacy_paid_order_cannot_be_canceled_and_restocked(): void
    {
        $this->assertLegacyIssuedOrderCannotRestock(Order::STATUS_PAID);
    }

    public function test_legacy_shipped_order_cannot_be_canceled_and_restocked(): void
    {
        $this->assertLegacyIssuedOrderCannotRestock(Order::STATUS_SHIPPED);
    }

    public function test_stale_models_cannot_restore_twice_or_reactivate_canceled_order(): void
    {
        [$buyer, , $products] = $this->cart();
        $order = $this->checkout($buyer);
        $stale = $order->fresh();
        $order->setStatus(Order::STATUS_CANCELED);
        $at = $order->canceled_at->toDateTimeString();
        $stale->setStatus(Order::STATUS_CANCELED);
        $this->assertSame($at, $stale->canceled_at->toDateTimeString());
        $this->assertSame([10, 10], $products->map(fn ($p) => $p->fresh()->stock)->all());
        $this->expectException(ValidationException::class);
        $stale->setStatus(Order::STATUS_PROCESSING);
    }

    public function test_mid_operation_failure_rolls_back_all_stock_and_status_then_retry_succeeds(): void
    {
        [$buyer, , $products] = $this->cart();
        $order = $this->checkout($buyer);
        $failed = false;
        // Throw after the second UPDATE really executed, so rollback must undo both writes.
        DB::listen(function ($query) use ($products, &$failed) {
            if (! $failed && str_starts_with($query->sql, 'update `products` set `stock`')
                && (int) end($query->bindings) === $products[1]->id) {
                $failed = true;
                throw new \RuntimeException('Injected inventory failure');
            }
        });
        try {
            $order->setStatus(Order::STATUS_CANCELED);
            $this->fail('Expected inventory failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Injected inventory failure', $exception->getMessage());
        }
        $this->assertTrue($failed);
        $this->assertSame([8, 7], $products->map(fn ($p) => $p->fresh()->stock)->all());
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
        $this->assertNull($order->fresh()->canceled_at);
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $order->setStatus(Order::STATUS_CANCELED);
        $this->assertSame([10, 10], $products->map(fn ($p) => $p->fresh()->stock)->all());
    }

    public function test_account_deletion_does_not_restore_stock_and_later_cancel_handles_withdrawn_product(): void
    {
        [$buyer, $seller, $products] = $this->cart();
        $order = $this->checkout($buyer);
        $buyer->delete();
        $seller->delete();
        $this->assertSame([8, 7], $products->map(fn ($p) => $p->fresh()->stock)->all());
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
        $products[0]->delete();
        $order->setStatus(Order::STATUS_CANCELED);
        $this->assertSame(10, Product::withTrashed()->findOrFail($products[0]->id)->stock);
        $this->assertSame(10, $products[1]->fresh()->stock);
    }

    public function test_seller_cancel_rechecks_allowed_source_under_lock(): void
    {
        [$buyer, $seller, $products] = $this->cart();
        $order = $this->checkout($buyer);
        $stale = $order->fresh();
        $order->setStatus(Order::STATUS_SHIPPED);
        $this->actingAs($seller)->post(route('seller.orders.updateStatus', $order), ['status' => Order::STATUS_CANCELED])
            ->assertSessionHasErrors('status');
        try {
            $stale->setStatus(Order::STATUS_CANCELED, [Order::STATUS_PENDING, Order::STATUS_PROCESSING, Order::STATUS_PAID]);
            $this->fail('Stale cancellation must not bypass seller transition rules');
        } catch (ValidationException) {
            $this->assertSame(Order::STATUS_SHIPPED, $order->fresh()->status);
            $this->assertSame([8, 7], $products->map(fn ($p) => $p->fresh()->stock)->all());
        }
    }

    private function cart(): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $products = collect([2, 3])->map(function ($qty, $index) use ($buyer, $seller) {
            $product = Product::create([
                'user_id' => $seller->id, 'title' => 'Stock '.$index, 'slug' => 'stock-'.$index,
                'price' => 100, 'currency_base' => 'PRB', 'stock' => 10, 'status' => Product::STATUS_ACTIVE,
            ]);
            CartItem::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'qty' => $qty]);
            return $product;
        });
        return [$buyer, $seller, $products];
    }

    private function assertLegacyIssuedOrderCannotRestock(string $status): void
    {
        [$buyer, , $products] = $this->cart();
        $order = $this->checkout($buyer);
        $admin = User::factory()->create(['role' => 'admin']);
        $order->setStatus($status);
        $this->actingAs($admin)->postJson(route('admin.orders.updateStatus', $order), [
            'status' => Order::STATUS_CANCELED, 'change_reason' => 'Проверка безопасности',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertSame($status, $order->fresh()->status);
        $this->assertSame([8, 7], $products->map(fn ($p) => $p->fresh()->stock)->all());
    }

    private function checkout(User $buyer): Order
    {
        // Simulate inventory already reserved by a historical order; checkout now creates v2 only.
        $cart = CartItem::where('user_id', $buyer->id)->with('product')->get();
        $order = Order::create([
            'user_id' => $buyer->id, 'seller_id' => $cart->first()->product->user_id,
            'number' => Order::generateNumber(), 'status' => Order::STATUS_PENDING,
            'total_price' => $cart->sum(fn ($item) => $item->qty * $item->product->price),
            'currency' => 'PRB', 'payment_method' => 'cash', 'delivery_method' => 'pickup',
        ]);
        foreach ($cart as $cartItem) {
            $product = $cartItem->product;
            $product->decrement('stock', $cartItem->qty);
            OrderItem::create([
                'order_id' => $order->id, 'product_id' => $product->id,
                'quantity' => $cartItem->qty, 'price' => $product->price,
                'total' => $product->price * $cartItem->qty,
                'source_price' => $product->price, 'source_currency' => 'PRB',
                'exchange_rate' => 1,
            ]);
        }

        return $order;
    }
}
