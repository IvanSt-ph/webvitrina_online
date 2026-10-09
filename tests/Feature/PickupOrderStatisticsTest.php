<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PickupOrderStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_finance_separates_pickup_and_legacy_states_without_counting_other_sellers(): void
    {
        [$buyer, $seller] = $this->orders();
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $this->order($otherBuyer, $otherSeller, Order::STATUS_COMPLETED, 999, true);

        $response = $this->actingAs($seller)->get(route('seller.finance.index'))->assertOk()
            ->assertSee('Операционные суммы заказов магазина')
            ->assertSee('не подтверждение оплаты');
        $this->assertSame('610.00', $this->money($response->viewData('completedTotal')));
        $this->assertSame('1100.00', $this->money($response->viewData('inProgressTotal')));
        $this->assertSame('670.00', $this->money($response->viewData('canceledTotal')));
        $this->assertSame('2380.00', $this->money(
            $response->viewData('completedTotal') + $response->viewData('inProgressTotal') + $response->viewData('canceledTotal')
        ));
        $this->assertSame('PRB', $response->viewData('currency'));
    }

    public function test_admin_user_summary_counts_pickup_delivered_as_active_and_preserves_legacy_scope(): void
    {
        [$buyer, $seller] = $this->orders();
        $otherSeller = User::factory()->create(['role' => 'seller']);
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $this->order($otherBuyer, $otherSeller, Order::STATUS_COMPLETED, 999, true);
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([$seller, $buyer] as $subject) {
            $summary = $this->actingAs($admin)->get(route('admin.users.show', $subject))
                ->assertOk()->viewData('commerceSummary');
            $this->assertSame(2, $summary['needs_action_orders']);
            $this->assertSame(6, $summary['fulfillment_orders']);
            $this->assertSame(8, $summary['active_orders']);
            $this->assertSame(3, $summary['completed_orders']);
            $this->assertSame(2, $summary['canceled_orders']);
            $this->assertSame(13, $summary['active_orders'] + $summary['completed_orders'] + $summary['canceled_orders']);
        }

        $otherSummary = $this->actingAs($admin)->get(route('admin.users.show', $otherSeller))
            ->assertOk()->viewData('commerceSummary');
        $this->assertSame(0, $otherSummary['active_orders']);
        $this->assertSame(1, $otherSummary['completed_orders']);
    }

    private function orders(): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        foreach ([
            [Order::STATUS_PENDING, 100],
            [Order::STATUS_PROCESSING, 200],
            [Order::STATUS_READY_FOR_PICKUP, 300],
            [Order::STATUS_DELIVERED, 400],
            [Order::STATUS_COMPLETED, 500],
            [Order::STATUS_CANCELED, 600],
        ] as [$status, $amount]) {
            $this->order($buyer, $seller, $status, $amount, true);
        }
        foreach ([
            [Order::STATUS_PENDING, 10],
            [Order::STATUS_PROCESSING, 20],
            [Order::STATUS_PAID, 30],
            [Order::STATUS_SHIPPED, 40],
            [Order::STATUS_DELIVERED, 50],
            [Order::STATUS_COMPLETED, 60],
            [Order::STATUS_CANCELED, 70],
        ] as [$status, $amount]) {
            $this->order($buyer, $seller, $status, $amount, false);
        }

        return [$buyer, $seller];
    }

    private function order(User $buyer, User $seller, string $status, int $amount, bool $pickup): void
    {
        $order = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => Order::generateNumber(),
            'status' => Order::STATUS_PENDING,
            'payment_method' => 'cash',
            'delivery_method' => 'pickup',
            'total_price' => $amount,
            'currency' => 'PRB',
        ]);
        DB::table('orders')->where('id', $order->id)->update([
            'status' => $status,
            'workflow_version' => $pickup ? Order::WORKFLOW_PICKUP : null,
        ]);
    }

    private function money(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
