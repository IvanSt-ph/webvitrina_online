<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\UserTrustService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UnsupportedOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_version_rejects_every_order_transition_without_side_effects(): void
    {
        [$order, $buyer, $seller, $admin] = $this->order(3);

        $this->actingAs($seller)->postJson(route('seller.orders.updateStatus', $order), [
            'status' => Order::STATUS_PROCESSING,
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->actingAs($seller)->postJson(route('seller.orders.updateStatus', $order), [
            'status' => Order::STATUS_CANCELED,
            'cancellation_reason' => 'Отмена',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->actingAs($seller)->postJson(route('seller.orders.confirmPayment', $order))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->actingAs($buyer)->postJson(route('orders.confirmDelivery', $order))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->actingAs($buyer)->postJson(route('orders.requestCancellation', $order), [
            'cancellation_reason' => 'Отмена',
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->actingAs($admin)->postJson(route('admin.orders.updateStatus', $order), [
            'status' => Order::STATUS_PROCESSING,
        ])->assertUnprocessable()->assertJsonValidationErrors('status');

        $fresh = $order->fresh();
        $this->assertSame(3, $fresh->workflow_version);
        $this->assertSame(Order::STATUS_PENDING, $fresh->status);
        $this->assertSame(Order::PAYMENT_UNPAID, $fresh->payment_status);
        $this->assertNull($fresh->paid_at);
        $this->assertNull($fresh->buyer_confirmed_at);
        $this->assertNull($fresh->cancellation_requested_at);
        $this->assertSame(0, $order->events()->count());
    }

    public function test_unknown_version_cannot_use_public_model_mutators_while_null_legacy_remains_valid(): void
    {
        [$unknown, $buyer, $seller] = $this->order(3);
        try {
            $unknown->setStatus(Order::STATUS_PROCESSING);
            $this->fail('Unknown workflow must not use legacy setStatus.');
        } catch (ValidationException) {
            $this->assertSame(Order::STATUS_PENDING, $unknown->fresh()->status);
        }
        try {
            $unknown->update(['status' => Order::STATUS_PROCESSING]);
            $this->fail('Unknown workflow must not update lifecycle fields directly.');
        } catch (\LogicException) {
            $this->assertSame(Order::STATUS_PENDING, $unknown->fresh()->status);
        }

        $legacy = $this->order(null, $buyer, $seller)[0];
        $legacy->setStatus(Order::STATUS_PROCESSING);
        $this->assertSame(Order::STATUS_PROCESSING, $legacy->fresh()->status);
    }

    public function test_unknown_version_is_visible_without_legacy_actions_or_history(): void
    {
        [$order, $buyer, $seller, $admin] = $this->order(3);

        $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()
            ->assertSee('Версия процесса заказа не поддерживается')
            ->assertDontSee('action="'.route('orders.confirmDelivery', $order).'"', false)
            ->assertDontSee('action="'.route('orders.requestCancellation', $order).'"', false);
        $this->actingAs($seller)->get(route('seller.orders.show', $order))->assertOk()
            ->assertSee('Версия процесса заказа не поддерживается')
            ->assertDontSee('action="'.route('seller.orders.updateStatus', $order).'"', false)
            ->assertDontSee('action="'.route('seller.orders.confirmPayment', $order).'"', false);
        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()
            ->assertSee('Версия процесса заказа не поддерживается')
            ->assertDontSee('action="'.route('admin.orders.updateStatus', $order).'"', false);

        $this->actingAs($buyer)->get(route('orders.index', ['tab' => 'action']))
            ->assertOk()->assertDontSee($order->number);
        $this->actingAs($buyer)->get(route('orders.index', ['tab' => 'unsupported']))
            ->assertOk()->assertSee($order->number);
        $this->actingAs($buyer)->get(route('orders.index', ['tab' => 'active']))
            ->assertOk()->assertDontSee($order->number);
        $this->actingAs($seller)->get(route('seller.orders.index', ['action' => 'needs_action']))
            ->assertOk()->assertDontSee($order->number);
        $this->actingAs($seller)->get(route('seller.orders.index', ['status' => Order::STATUS_PENDING]))
            ->assertOk()->assertDontSee($order->number)
            ->assertViewHas('statusCounts', fn ($counts) => ! isset($counts[Order::STATUS_PENDING]));
        $this->actingAs($admin)->get(route('admin.orders.index'))
            ->assertOk()->assertSee($order->number)->assertSee('Версия процесса заказа не поддерживается');
        $this->actingAs($admin)->get(route('admin.orders.index', ['status' => Order::STATUS_PENDING]))
            ->assertOk()->assertDontSee($order->number)
            ->assertViewHas('statusCounts', fn ($counts) => ! isset($counts[Order::STATUS_PENDING]));
    }

    public function test_unknown_version_is_excluded_from_financial_and_operational_summaries(): void
    {
        [$unsupported, $buyer, $seller, $admin] = $this->order(3);
        DB::table('orders')->where('id', $unsupported->id)->update(['status' => Order::STATUS_COMPLETED]);
        $unknownActive = $this->order(3, $buyer, $seller)[0];
        DB::table('orders')->where('id', $unknownActive->id)->update(['status' => Order::STATUS_PROCESSING]);
        $unknownCanceled = $this->order(3, $buyer, $seller)[0];
        DB::table('orders')->where('id', $unknownCanceled->id)->update(['status' => Order::STATUS_CANCELED]);
        $legacy = $this->order(null, $buyer, $seller)[0];
        DB::table('orders')->where('id', $legacy->id)->update(['status' => Order::STATUS_COMPLETED]);
        $pickup = $this->order(Order::WORKFLOW_PICKUP, $buyer, $seller)[0];
        DB::table('orders')->where('id', $pickup->id)->update(['status' => Order::STATUS_COMPLETED]);

        $finance = $this->actingAs($seller)->get(route('seller.finance.index'))->assertOk();
        $this->assertSame('200.00', number_format((float) $finance->viewData('completedTotal'), 2, '.', ''));
        $this->assertSame('0.00', number_format((float) $finance->viewData('inProgressTotal'), 2, '.', ''));
        $this->assertSame('0.00', number_format((float) $finance->viewData('canceledTotal'), 2, '.', ''));
        $summary = $this->actingAs($admin)->get(route('admin.users.show', $seller))
            ->assertOk()->viewData('commerceSummary');
        $this->assertSame(2, $summary['completed_orders']);
        $this->assertSame(0, $summary['active_orders']);
        $this->assertSame(0, $summary['canceled_orders']);
        $this->actingAs($admin)->get(route('admin.orders.index'))
            ->assertOk()->assertViewHas('summary', fn ($summary) => (float) $summary['revenue'] === 200.0);
        $this->get(route('users.public.show', $buyer))->assertOk()
            ->assertViewHas('publicStats', fn ($stats) => $stats['completed_orders'] === 2);
    }

    public function test_unknown_version_cannot_unlock_reviews_or_action_counters(): void
    {
        [$order, $buyer, $seller, $admin] = $this->order(3);
        $product = Product::create([
            'user_id' => $seller->id,
            'title' => 'Order workflow review product',
            'slug' => 'order-workflow-review-product',
            'price' => 100,
            'currency_base' => 'PRB',
            'stock' => 1,
            'status' => Product::STATUS_ACTIVE,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 100,
            'total' => 100,
        ]);
        DB::table('orders')->where('id', $order->id)->update([
            'status' => Order::STATUS_DELIVERED,
            'cancellation_requested_at' => now(),
        ]);

        $this->actingAs($buyer)->postJson(route('review.store', $product), ['rating' => 5])
            ->assertUnprocessable()->assertJsonValidationErrors('review');
        $this->assertDatabaseMissing('reviews', ['user_id' => $buyer->id, 'product_id' => $product->id]);
        $this->actingAs($buyer)->get(route('cabinet'))->assertOk()
            ->assertViewHas('reviewableOrdersCount', 0);
        $this->actingAs($seller)->get(route('seller.cabinet'))->assertOk()
            ->assertViewHas('actionCards', fn ($cards) => $cards[1]['value'] === 0)
            ->assertViewHas('actionOrders', fn ($orders) => $orders->isEmpty());
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('workQueue', fn ($queue) => $queue['orders'] === 0);
        $this->assertSame('0 завершённых', collect(app(UserTrustService::class)->profileFor($buyer)['signals'])
            ->firstWhere('label', 'Сделки')['value']);
    }

    private function order(?int $version, ?User $buyer = null, ?User $seller = null): array
    {
        $buyer ??= User::factory()->create(['role' => 'buyer']);
        $seller ??= User::factory()->create(['role' => 'seller']);
        $admin = User::factory()->create(['role' => 'admin']);
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
        DB::table('orders')->where('id', $order->id)->update([
            'workflow_version' => $version,
            'payment_status' => Order::PAYMENT_UNPAID,
        ]);

        return [$order->refresh(), $buyer, $seller, $admin];
    }
}
