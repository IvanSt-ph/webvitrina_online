<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PickupOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_then_buyer_receipt_completes_exactly_once(): void
    {
        [$order, $buyer, $seller] = $this->pickup();
        $this->ready($order, $seller);

        $this->actingAs($seller)->post(route('seller.orders.confirmPayment', $order))->assertSessionHasNoErrors();
        $this->assertSame(Order::STATUS_READY_FOR_PICKUP, $order->fresh()->status);
        $this->assertSame(Order::PAYMENT_SELLER_CONFIRMED, $order->fresh()->payment_status);
        $this->actingAs($buyer)->post(route('orders.confirmDelivery', $order))->assertSessionHasNoErrors();

        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);
        $this->assertNotNull($order->fresh()->buyer_confirmed_at);
        $this->assertNotNull($order->fresh()->completed_at);
        $this->assertSame(5, $order->events()->count());
        $this->assertSame(['seller', 'seller', 'seller', 'buyer', 'system'], $order->events()->orderBy('id')->pluck('actor_role')->all());
        $notifications = UserNotification::count();
        $this->actingAs($seller)->post(route('seller.orders.confirmPayment', $order))->assertSessionHasNoErrors();
        $this->actingAs($buyer)->post(route('orders.confirmDelivery', $order))->assertSessionHasNoErrors();
        $this->assertSame(5, $order->events()->count());
        $this->assertSame($notifications, UserNotification::count());
    }

    public function test_buyer_receipt_then_payment_completes_only_after_payment(): void
    {
        [$order, $buyer, $seller] = $this->pickup();
        $this->ready($order, $seller);

        $this->actingAs($buyer)->post(route('orders.confirmDelivery', $order))->assertSessionHasNoErrors();
        $this->assertSame(Order::STATUS_DELIVERED, $order->fresh()->status);
        $this->assertSame(Order::PAYMENT_UNPAID, $order->fresh()->payment_status);
        $this->assertNull($order->fresh()->completed_at);

        $this->actingAs($seller)->post(route('seller.orders.confirmPayment', $order))->assertSessionHasNoErrors();
        $this->assertSame(Order::STATUS_COMPLETED, $order->fresh()->status);
        $this->assertSame(5, $order->events()->count());
    }

    public function test_direct_status_requests_and_public_model_methods_cannot_complete_pickup(): void
    {
        [$order, , $seller] = $this->pickup();
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([Order::STATUS_DELIVERED, Order::STATUS_COMPLETED, Order::STATUS_PAID] as $status) {
            $this->actingAs($seller)->postJson(route('seller.orders.updateStatus', $order), ['status' => $status])
                ->assertUnprocessable()->assertJsonValidationErrors('status');
            $this->actingAs($admin)->postJson(route('admin.orders.updateStatus', $order), ['status' => $status])
                ->assertUnprocessable()->assertJsonValidationErrors('status');
        }
        try {
            $order->setStatus(Order::STATUS_COMPLETED);
            $this->fail('setStatus must reject pickup lifecycle changes');
        } catch (ValidationException) {
        }
        try {
            $order->update(['status' => Order::STATUS_COMPLETED]);
            $this->fail('Direct model update must reject pickup lifecycle changes');
        } catch (\LogicException) {
        }
        try {
            $order->markAsPaid();
            $this->fail('markAsPaid must reject pickup lifecycle changes');
        } catch (ValidationException) {
        }

        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
        $this->assertSame(0, $order->events()->count());
    }

    public function test_other_users_cannot_act_on_pickup_order(): void
    {
        [$order] = $this->pickup();
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $this->actingAs($otherBuyer)->post(route('orders.confirmDelivery', $order))->assertForbidden();
        $this->actingAs($otherBuyer)->post(route('orders.requestCancellation', $order), [
            'cancellation_reason' => 'Чужой заказ',
        ])->assertForbidden();
        $this->actingAs($otherSeller)->post(route('seller.orders.updateStatus', $order), [
            'status' => Order::STATUS_PROCESSING,
        ])->assertForbidden();
        $this->actingAs($otherSeller)->post(route('seller.orders.confirmPayment', $order))->assertForbidden();
        $this->assertSame(Order::STATUS_PENDING, $order->fresh()->status);
        $this->assertSame(0, $order->events()->count());
    }

    public function test_cancellation_restocks_once_but_never_after_ready_payment_or_receipt(): void
    {
        [$order, , $seller] = $this->pickup();
        $product = Product::create([
            'user_id' => $seller->id, 'title' => 'Pickup stock', 'slug' => 'pickup-stock-'.$order->id,
            'price' => 100, 'currency_base' => 'PRB', 'stock' => 3, 'status' => Product::STATUS_ACTIVE,
        ]);
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2,
            'price' => 100, 'total' => 200, 'source_price' => 100,
            'source_currency' => 'PRB', 'exchange_rate' => 1,
        ]);

        $this->actingAs($seller)->postJson(route('seller.orders.updateStatus', $order), [
            'status' => Order::STATUS_CANCELED,
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->actingAs($seller)->post(route('seller.orders.updateStatus', $order), [
            'status' => Order::STATUS_CANCELED, 'cancellation_reason' => 'Товара нет',
        ])->assertSessionHasNoErrors();
        $this->assertSame(5, $product->fresh()->stock);
        $this->actingAs($seller)->post(route('seller.orders.updateStatus', $order), [
            'status' => Order::STATUS_CANCELED, 'cancellation_reason' => 'Товара нет',
        ])->assertSessionHasNoErrors();
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(1, $order->events()->count());

        [$otherOrder, $buyer] = $this->pickup();
        $this->ready($otherOrder, $seller = $otherOrder->seller);
        $this->actingAs($seller)->postJson(route('seller.orders.updateStatus', $otherOrder), [
            'status' => Order::STATUS_CANCELED, 'cancellation_reason' => 'После готовности',
        ])->assertUnprocessable();
        $this->actingAs($buyer)->post(route('orders.confirmDelivery', $otherOrder))->assertSessionHasNoErrors();
        $this->actingAs($seller)->postJson(route('seller.orders.updateStatus', $otherOrder), [
            'status' => Order::STATUS_CANCELED, 'cancellation_reason' => 'После выдачи',
        ])->assertUnprocessable();
        $this->assertSame(Order::STATUS_DELIVERED, $otherOrder->fresh()->status);
    }

    public function test_legacy_seller_cannot_confirm_delivery_but_buyer_can_once(): void
    {
        [$order, $buyer, $seller] = $this->pickup(false);
        $order->setStatus(Order::STATUS_SHIPPED);
        $this->actingAs($seller)->postJson(route('seller.orders.updateStatus', $order), [
            'status' => Order::STATUS_DELIVERED,
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->actingAs($buyer)->post(route('orders.confirmDelivery', $order))->assertSessionHasNoErrors();
        $this->actingAs($buyer)->post(route('orders.confirmDelivery', $order))->assertSessionHasNoErrors();
        $this->assertSame(Order::STATUS_DELIVERED, $order->fresh()->status);
        $this->assertNull($order->fresh()->completed_at);
        $this->assertSame(1, $order->events()->count());
    }

    public function test_cancellation_request_and_rejection_are_idempotent_and_keep_reason_in_event(): void
    {
        [$order, $buyer, $seller] = $this->pickup();
        $this->actingAs($buyer)->post(route('orders.requestCancellation', $order), [
            'cancellation_reason' => 'Не смогу получить',
        ])->assertSessionHasNoErrors();
        $notifications = UserNotification::count();
        $this->actingAs($buyer)->post(route('orders.requestCancellation', $order), [
            'cancellation_reason' => 'Повтор',
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, $order->events()->count());
        $this->assertSame($notifications, UserNotification::count());

        $this->actingAs($seller)->post(route('seller.orders.rejectCancellation', $order), [
            'reason' => 'Товар уже зарезервирован',
        ])->assertSessionHasNoErrors();
        $this->actingAs($seller)->post(route('seller.orders.rejectCancellation', $order), [
            'reason' => 'Повтор',
        ])->assertSessionHasNoErrors();
        $this->assertSame(2, $order->events()->count());
        $this->assertNull($order->fresh()->cancellation_requested_at);
        $this->assertSame('Не смогу получить', $order->events()->firstOrFail()->metadata['reason']);
        $this->assertSame('Товар уже зарезервирован', $order->events()->orderByDesc('id')->firstOrFail()->metadata['reason']);
    }

    public function test_paid_pickup_order_cannot_be_canceled_normally(): void
    {
        [$order, , $seller] = $this->pickup();
        $this->ready($order, $seller);
        $this->actingAs($seller)->post(route('seller.orders.confirmPayment', $order))->assertSessionHasNoErrors();
        $eventCount = $order->events()->count();
        $this->actingAs($seller)->postJson(route('seller.orders.updateStatus', $order), [
            'status' => Order::STATUS_CANCELED, 'cancellation_reason' => 'Оплаченный заказ',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertSame(Order::STATUS_READY_FOR_PICKUP, $order->fresh()->status);
        $this->assertSame($eventCount, $order->events()->count());
    }

    public function test_failed_event_insert_rolls_back_status_change(): void
    {
        [$order, , $seller] = $this->pickup();
        $injected = false;
        DB::listen(function ($query) use (&$injected): void {
            if (! $injected && str_starts_with(strtolower($query->sql), 'insert into `order_events`')) {
                $injected = true;
                throw new \RuntimeException('Injected event failure');
            }
        });
        $this->withoutExceptionHandling();
        $this->actingAs($seller);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Injected event failure');
        try {
            $this->post(route('seller.orders.updateStatus', $order), ['status' => Order::STATUS_PROCESSING]);
        } finally {
            $this->assertTrue($injected, 'The order_events insert must reach the injected failure.');
            $fresh = $order->fresh();
            $this->assertSame(Order::STATUS_PENDING, $fresh->status);
            $this->assertSame(Order::PAYMENT_UNPAID, $fresh->payment_status);
            $this->assertNull($fresh->accepted_at);
            $this->assertNull($fresh->ready_for_pickup_at);
            $this->assertNull($fresh->paid_at);
            $this->assertNull($fresh->buyer_confirmed_at);
            $this->assertNull($fresh->delivered_at);
            $this->assertNull($fresh->completed_at);
            $this->assertSame(0, $order->events()->count());
        }
    }

    private function pickup(bool $v2 = true): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $order = Order::create([
            'user_id' => $buyer->id, 'seller_id' => $seller->id,
            'number' => Order::generateNumber(), 'status' => Order::STATUS_PENDING,
            'payment_method' => 'cash', 'delivery_method' => 'pickup',
            'total_price' => 200, 'currency' => 'PRB',
        ]);
        if ($v2) {
            // Stage 2 does not opt checkout into v2; fixtures explicitly model a future v2 order.
            DB::table('orders')->where('id', $order->id)->update([
                'workflow_version' => Order::WORKFLOW_PICKUP,
                'payment_status' => Order::PAYMENT_UNPAID,
            ]);
            $order->refresh();
        }

        return [$order, $buyer, $seller];
    }

    private function ready(Order $order, User $seller): void
    {
        $this->actingAs($seller)->post(route('seller.orders.updateStatus', $order), [
            'status' => Order::STATUS_PROCESSING,
        ])->assertSessionHasNoErrors();
        $this->actingAs($seller)->post(route('seller.orders.updateStatus', $order), [
            'status' => Order::STATUS_READY_FOR_PICKUP,
        ])->assertSessionHasNoErrors();
    }
}
