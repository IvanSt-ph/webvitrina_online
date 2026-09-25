<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_and_seller_cancellation_restore_each_item_once_and_preserve_money(): void
    {
        [$buyer, $seller, $products] = $this->cart();
        $order = $this->checkout($buyer);
        $this->assertSame([8, 7], $products->map(fn ($p) => $p->fresh()->stock)->all());
        $items = $order->items()->get()->map->getRawOriginal()->all();
        $money = $order->only(['total_price', 'currency']);

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
        foreach ([Order::STATUS_PROCESSING, Order::STATUS_PAID, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED] as $status) {
            $order->setStatus($status);
            $this->assertSame([8, 7], $products->map(fn ($p) => $p->fresh()->stock)->all());
        }
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
            ->assertSessionHas('error');
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

    private function checkout(User $buyer): Order
    {
        $this->actingAs($buyer)->withSession(['currency' => 'MDL'])->post(route('checkout.prepare'))->assertRedirect(route('checkout.confirm'));
        $this->get(route('checkout.confirm'))->assertOk();
        $this->post(route('checkout.create'), [
            'payment_method' => 'cash', 'delivery_method' => 'pickup', 'checkout_token' => session('checkout_token'),
        ])->assertRedirect()->assertSessionHasNoErrors();
        return Order::query()->sole();
    }
}
