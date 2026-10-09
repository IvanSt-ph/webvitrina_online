<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PickupOrderEventTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_pickup_timeline_shows_ordered_events_roles_and_escaped_historical_reasons(): void
    {
        [$order, $buyer, $seller, $admin] = $this->order(true);
        $types = [
            ['order_created', 'buyer'],
            ['order_accepted', 'seller'],
            ['pickup_ready', 'seller'],
            ['cancellation_requested', 'buyer'],
            ['cancellation_rejected', 'seller'],
            ['payment_seller_confirmed', 'seller'],
            ['buyer_receipt_confirmed', 'buyer'],
            ['internal_case_note', 'admin'],
            ['order_completed', 'system'],
        ];
        foreach ($types as $index => [$type, $role]) {
            $event = $order->events()->create([
                'actor_id' => match ($role) {
                    'buyer' => $buyer->id,
                    'seller' => $seller->id,
                    'admin' => $admin->id,
                    default => null,
                },
                'actor_role' => $role,
                'event_type' => $type,
                'metadata' => $type === 'cancellation_requested'
                    ? ['reason' => '<script>alert(1)</script>', 'private' => 'HIDDEN_METADATA']
                    : ($type === 'cancellation_rejected' ? ['reason' => 'Причина отклонения'] : null),
            ]);
            DB::table('order_events')->where('id', $event->id)->update([
                'created_at' => now()->subMinutes(20 - $index),
            ]);
        }
        // Current columns no longer contain the old cancellation request.
        DB::table('orders')->where('id', $order->id)->update([
            'status' => Order::STATUS_COMPLETED,
            'cancellation_requested_at' => null,
            'cancellation_reason' => null,
        ]);

        foreach ([[$buyer, 'orders.show'], [$seller, 'seller.orders.show'], [$admin, 'admin.orders.show']] as [$actor, $route]) {
            $response = $this->actingAs($actor)->get(route($route, $order))->assertOk();
            $response->assertSeeInOrder([
                'Заказ создан', 'Принят продавцом', 'Готов к самовывозу',
                'Покупатель запросил отмену', 'Продавец отклонил запрос отмены',
                'Продавец отметил оплату при получении', 'Покупатель подтвердил получение',
                'Заказ завершён системой',
            ])->assertSee('Инициатор: Покупатель')
                ->assertSee('Инициатор: Продавец')
                ->assertSee('Инициатор: Система')
                ->assertSee('Инициатор: Администратор')
                ->assertSee('Событие заказа')
                ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
                ->assertSee('Причина отклонения')
                ->assertDontSee('<script>alert(1)</script>', false)
                ->assertDontSee('HIDDEN_METADATA')
                ->assertDontSee('internal_case_note');
            $this->assertMatchesRegularExpression('/\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}/', $response->getContent());
        }
    }

    public function test_legacy_timeline_keeps_timestamp_history_without_event_roles(): void
    {
        [$order, $buyer] = $this->order(false);
        DB::table('orders')->where('id', $order->id)->update([
            'status' => Order::STATUS_SHIPPED,
            'accepted_at' => now()->subDay(),
            'shipped_at' => now(),
        ]);
        $order->events()->create(['actor_role' => 'admin', 'event_type' => 'internal_event']);

        $this->actingAs($buyer)->get(route('orders.show', $order))
            ->assertOk()->assertSee('Передан в доставку')
            ->assertDontSee('Инициатор:')
            ->assertDontSee('internal_event');
    }

    private function order(bool $pickup): array
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
        if ($pickup) {
            DB::table('orders')->where('id', $order->id)->update([
                'workflow_version' => Order::WORKFLOW_PICKUP,
                'payment_status' => Order::PAYMENT_UNPAID,
            ]);
        }

        return [$order->refresh(), $buyer, $seller, $admin];
    }
}
