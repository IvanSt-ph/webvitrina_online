<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PickupOrderInterfaceCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_ready_pickup_is_visible_in_buyer_seller_and_admin_lists_and_details(): void
    {
        [$order, $buyer, $seller, $admin] = $this->order();
        DB::table('orders')->where('id', $order->id)->update([
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'ready_for_pickup_at' => now(),
        ]);

        foreach ([
            [$buyer, 'orders.index', 'orders.show'],
            [$seller, 'seller.orders.index', 'seller.orders.show'],
            [$admin, 'admin.orders.index', 'admin.orders.show'],
        ] as [$actor, $index, $show]) {
            $this->actingAs($actor)->get(route($index))
                ->assertOk()->assertSee($order->number)->assertSee('Готов к самовывозу')
                ->assertSee('Оплата продавцом не подтверждена')
                ->assertSee($actor->id === $seller->id
                    ? 'Получение не подтверждено покупателем'
                    : 'Получение покупателем не подтверждено');
            $this->get(route($show, $order))
                ->assertOk()->assertSee('Готов к самовывозу')
                ->assertSee('Оплата продавцом не подтверждена');
        }
    }

    public function test_payment_receipt_and_completion_are_displayed_as_separate_facts(): void
    {
        [$order, $buyer, $seller, $admin] = $this->order();
        DB::table('orders')->where('id', $order->id)->update([
            'status' => Order::STATUS_DELIVERED,
            'payment_status' => Order::PAYMENT_SELLER_CONFIRMED,
            'paid_at' => now(),
            'buyer_confirmed_at' => null,
        ]);
        $order->refresh();
        $this->assertSame('Получение требует проверки', $order->status_ru);

        foreach ([[$buyer, 'orders.show'], [$seller, 'seller.orders.show'], [$admin, 'admin.orders.show']] as [$actor, $route]) {
            $this->actingAs($actor)->get(route($route, $order))
                ->assertOk()->assertSee('Получение требует проверки')
                ->assertSee('Продавец отметил оплату при получении')
                ->assertDontSee('Заказ завершён');
        }

        DB::table('orders')->where('id', $order->id)->update(['status' => Order::STATUS_COMPLETED]);
        $order->refresh();
        $this->assertSame('Завершение требует проверки', $order->status_ru);
        $this->actingAs($buyer)->get(route('orders.show', $order))
            ->assertOk()->assertSee('Завершение требует проверки');
    }

    public function test_received_pickup_uses_historical_delivery_label_only_for_legacy(): void
    {
        [$order, $buyer, $seller, $admin] = $this->order();
        DB::table('orders')->where('id', $order->id)->update([
            'status' => Order::STATUS_DELIVERED,
            'buyer_confirmed_at' => now(),
            'delivered_at' => now(),
        ]);
        $order->refresh();
        $this->assertSame('Получен покупателем', $order->status_ru);

        foreach ([
            [$buyer, 'orders.index', 'orders.show'],
            [$seller, 'seller.orders.index', 'seller.orders.show'],
            [$admin, 'admin.orders.index', 'admin.orders.show'],
        ] as [$actor, $index, $show]) {
            $this->actingAs($actor)->get(route($index))->assertOk()->assertSee('Получен покупателем');
            $this->get(route($show, $order))->assertOk()->assertSee('Получен покупателем');
        }

        DB::table('orders')->where('id', $order->id)->update(['workflow_version' => null]);
        $this->assertSame('Доставлен', $order->fresh()->status_ru);
    }

    public function test_ready_filter_and_admin_active_counter_include_pickup_without_legacy_transition_change(): void
    {
        [$ready, $buyer, $seller, $admin] = $this->order();
        DB::table('orders')->where('id', $ready->id)->update(['status' => Order::STATUS_READY_FOR_PICKUP]);
        $legacy = Order::create([
            'user_id' => $buyer->id,
            'seller_id' => $seller->id,
            'number' => Order::generateNumber(),
            'status' => Order::STATUS_SHIPPED,
            'payment_method' => 'cash',
            'delivery_method' => 'courier',
            'total_price' => 100,
            'currency' => 'PRB',
        ]);

        $this->assertNotContains(Order::STATUS_READY_FOR_PICKUP, Order::allStatuses());
        $this->assertContains(Order::STATUS_READY_FOR_PICKUP, Order::filterStatuses());
        $this->actingAs($seller)->get(route('seller.orders.index', ['status' => Order::STATUS_READY_FOR_PICKUP]))
            ->assertOk()->assertSee($ready->number)->assertDontSee($legacy->number);
        $this->actingAs($admin)->get(route('admin.orders.index', ['status' => Order::STATUS_READY_FOR_PICKUP]))
            ->assertOk()->assertSee($ready->number)->assertDontSee($legacy->number)
            ->assertViewHas('summary', fn ($summary) => $summary['active'] === 1);
        $this->get(route('admin.orders.index', ['focus' => 'active']))
            ->assertOk()->assertSee($ready->number)
            ->assertViewHas('summary', fn ($summary) => $summary['active'] === 2);
        $this->actingAs($buyer)->get(route('orders.show', $legacy))
            ->assertOk()->assertSee('В пути')->assertDontSee('Оплата продавцом не подтверждена');

        $this->actingAs($admin)->postJson(route('admin.orders.updateStatus', $legacy), [
            'status' => Order::STATUS_READY_FOR_PICKUP,
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertSame(Order::STATUS_SHIPPED, $legacy->fresh()->status);
    }

    private function order(): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
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
            'workflow_version' => Order::WORKFLOW_PICKUP,
            'payment_status' => Order::PAYMENT_UNPAID,
        ]);

        return [$order->refresh(), $buyer, $seller, $admin];
    }
}
