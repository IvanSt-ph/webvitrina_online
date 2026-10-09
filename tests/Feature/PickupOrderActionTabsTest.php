<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PickupOrderActionTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_action_tab_and_counter_include_only_confirmable_pickup_and_legacy_shipped(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $ready = $this->order($buyer, $seller, Order::STATUS_READY_FOR_PICKUP, true, [
            'ready_for_pickup_at' => now(),
        ]);
        $legacy = $this->order($buyer, $seller, Order::STATUS_SHIPPED);
        $withoutTime = $this->order($buyer, $seller, Order::STATUS_READY_FOR_PICKUP, true);
        $alreadyReceived = $this->order($buyer, $seller, Order::STATUS_DELIVERED, true, [
            'buyer_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($buyer)->get(route('orders.index', ['tab' => 'action']))->assertOk();
        $this->assertSame(2, $response->viewData('orders')->total());
        $this->assertSame(2, $response->viewData('actionCount'));
        $response->assertSee($ready->number)->assertSee($legacy->number)
            ->assertDontSee($withoutTime->number)->assertDontSee($alreadyReceived->number)
            ->assertSee('Подтвердите получение товара');

        $buyer->forceFill(['email_verified_at' => null])->save();
        $response = $this->actingAs($buyer)->get(route('orders.index', ['tab' => 'action']))->assertOk();
        $this->assertSame(1, $response->viewData('orders')->total());
        $this->assertSame(1, $response->viewData('actionCount'));
        $response->assertDontSee($ready->number)->assertSee($legacy->number);
    }

    public function test_seller_action_tab_and_counter_match_available_pickup_and_legacy_transitions(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $pickupPending = $this->order($buyer, $seller, Order::STATUS_PENDING, true);
        $pickupProcessing = $this->order($buyer, $seller, Order::STATUS_PROCESSING, true);
        $pickupReady = $this->order($buyer, $seller, Order::STATUS_READY_FOR_PICKUP, true);
        $pickupDelivered = $this->order($buyer, $seller, Order::STATUS_DELIVERED, true);
        $legacyPaid = $this->order($buyer, $seller, Order::STATUS_PAID);
        $paid = $this->order($buyer, $seller, Order::STATUS_READY_FOR_PICKUP, true, [
            'payment_status' => Order::PAYMENT_SELLER_CONFIRMED, 'paid_at' => now(),
        ]);
        $invalid = $this->order($buyer, $seller, Order::STATUS_PROCESSING, true, [
            'delivery_method' => 'courier',
        ]);
        $completed = $this->order($buyer, $seller, Order::STATUS_COMPLETED, true);

        $response = $this->actingAs($seller)->get(route('seller.orders.index', ['action' => 'needs_action']))->assertOk();
        $expected = [$pickupPending, $pickupProcessing, $pickupReady, $pickupDelivered, $legacyPaid];
        $this->assertSame(count($expected), $response->viewData('orders')->total());
        $this->assertSame(count($expected), $response->viewData('actionCounts')['needs_action']);
        foreach ($expected as $order) {
            $response->assertSee($order->number);
        }
        foreach ([$paid, $invalid, $completed] as $order) {
            $response->assertDontSee($order->number);
        }
    }

    public function test_seller_cancellation_request_tab_and_counter_exclude_unactionable_orders(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $pickup = $this->order($buyer, $seller, Order::STATUS_READY_FOR_PICKUP, true, [
            'cancellation_requested_at' => now(), 'cancellation_reason' => 'Отмена',
        ]);
        $legacy = $this->order($buyer, $seller, Order::STATUS_PROCESSING, false, [
            'cancellation_requested_at' => now(), 'cancellation_reason' => 'Отмена',
        ]);
        $legacyShipped = $this->order($buyer, $seller, Order::STATUS_SHIPPED, false, [
            'cancellation_requested_at' => now(), 'cancellation_reason' => 'Отмена',
        ]);
        $completed = $this->order($buyer, $seller, Order::STATUS_COMPLETED, true, [
            'cancellation_requested_at' => now(), 'cancellation_reason' => 'Отмена',
        ]);

        $response = $this->actingAs($seller)->get(route('seller.orders.index', ['action' => 'cancel_request']))->assertOk();
        $this->assertSame(2, $response->viewData('orders')->total());
        $this->assertSame(2, $response->viewData('actionCounts')['cancel_request']);
        $response->assertSee($pickup->number)->assertSee($legacy->number)
            ->assertDontSee($legacyShipped->number)->assertDontSee($completed->number);
    }

    private function order(User $buyer, User $seller, string $status, bool $pickup = false, array $extra = []): Order
    {
        $order = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => Order::generateNumber(),
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'cash',
            'delivery_method' => 'pickup',
            'total_price' => 100,
            'currency' => 'PRB',
        ]);
        DB::table('orders')->where('id', $order->id)->update(array_merge([
            'status' => $status,
            'workflow_version' => $pickup ? Order::WORKFLOW_PICKUP : null,
            'payment_status' => $pickup ? Order::PAYMENT_UNPAID : null,
        ], $extra));

        return $order->refresh();
    }
}
