<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PickupBuyerOrderActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ready_pickup_shows_receipt_form_before_and_after_seller_payment(): void
    {
        [$order, $buyer] = $this->order();

        foreach ([
            [Order::PAYMENT_UNPAID, null],
            [Order::PAYMENT_SELLER_CONFIRMED, now()],
        ] as [$paymentStatus, $paidAt]) {
            $this->setOrder($order, [
                'status' => Order::STATUS_READY_FOR_PICKUP,
                'ready_for_pickup_at' => now(),
                'payment_status' => $paymentStatus,
                'paid_at' => $paidAt,
                'buyer_confirmed_at' => null,
            ]);
            $html = $this->actingAs($buyer)->get(route('orders.show', $order))
                ->assertOk()->assertSee('Подтвердить получение товара')
                ->assertSee('Готовность к самовывозу отмечена продавцом')
                ->getContent();
            $forms = $this->receiptForms($html, $order);
            $this->assertCount(1, $forms);
            $this->assertMatchesRegularExpression('/\bmethod="POST"/i', $forms[0]['tag']);
            $this->assertMatchesRegularExpression('/\bname="_token"\s+value="[^"]+"/', $forms[0]['body']);
            $this->assertStringContainsString("confirm('Подтвердить, что вы лично получили товар?')", $forms[0]['body']);
        }
    }

    public function test_pickup_receipt_form_is_absent_when_workflow_or_verification_disallows_it(): void
    {
        [$order, $buyer] = $this->order();
        $ready = [
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'ready_for_pickup_at' => now(),
            'payment_status' => Order::PAYMENT_UNPAID,
            'buyer_confirmed_at' => null,
            'payment_method' => 'cash',
            'delivery_method' => 'pickup',
        ];

        foreach ([
            ['status' => Order::STATUS_PENDING],
            ['status' => Order::STATUS_PROCESSING],
            ['status' => Order::STATUS_DELIVERED],
            ['status' => Order::STATUS_COMPLETED],
            ['status' => Order::STATUS_CANCELED],
            ['ready_for_pickup_at' => null],
            ['buyer_confirmed_at' => now()],
            ['payment_status' => 'unknown'],
            ['payment_method' => 'bank_transfer'],
            ['delivery_method' => 'courier'],
        ] as $invalid) {
            $this->setOrder($order, array_merge($ready, $invalid));
            $html = $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()->getContent();
            $this->assertSame([], $this->receiptForms($html, $order));
        }

        $this->setOrder($order, $ready);
        $buyer->forceFill(['email_verified_at' => null])->save();
        $html = $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()->getContent();
        $this->assertSame([], $this->receiptForms($html, $order));
    }

    public function test_legacy_shipped_receipt_form_and_delivered_absence_are_preserved(): void
    {
        [$order, $buyer] = $this->order(false);
        $this->setOrder($order, ['status' => Order::STATUS_SHIPPED]);
        $html = $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()->getContent();
        $forms = $this->receiptForms($html, $order);
        $this->assertCount(1, $forms);
        $this->assertMatchesRegularExpression('/\bmethod="POST"/i', $forms[0]['tag']);
        $this->assertMatchesRegularExpression('/\bname="_token"\s+value="[^"]+"/', $forms[0]['body']);
        $this->assertStringContainsString('Подтвердить получение', $forms[0]['body']);
        $this->assertStringNotContainsString('Подтвердить получение товара', $forms[0]['body']);

        $this->setOrder($order, ['status' => Order::STATUS_DELIVERED]);
        $html = $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()->getContent();
        $this->assertSame([], $this->receiptForms($html, $order));
    }

    public function test_pickup_payment_guidance_tracks_both_independent_confirmations(): void
    {
        [$order, $buyer] = $this->order();
        $ready = [
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'ready_for_pickup_at' => now(),
            'payment_status' => Order::PAYMENT_UNPAID,
            'paid_at' => null,
            'buyer_confirmed_at' => null,
            'completed_at' => null,
        ];

        $this->setOrder($order, $ready);
        $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()
            ->assertSee('Оплата производится непосредственно продавцу при получении.');

        $this->setOrder($order, array_merge($ready, [
            'payment_status' => Order::PAYMENT_SELLER_CONFIRMED,
            'paid_at' => now(),
        ]));
        $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()
            ->assertSee('Продавец подтвердил получение денег. Подтвердите получение товара после фактической передачи.')
            ->assertDontSee('Оплата производится непосредственно продавцу при получении.');

        $this->setOrder($order, array_merge($ready, [
            'status' => Order::STATUS_DELIVERED,
            'buyer_confirmed_at' => now(),
        ]));
        $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()
            ->assertSee('Завершение ожидает подтверждения оплаты продавцом.');

        $this->setOrder($order, array_merge($ready, [
            'status' => Order::STATUS_COMPLETED,
            'buyer_confirmed_at' => now(),
            'payment_status' => Order::PAYMENT_SELLER_CONFIRMED,
            'paid_at' => now(),
            'completed_at' => now(),
        ]));
        $this->actingAs($buyer)->get(route('orders.show', $order))->assertOk()
            ->assertSee('Заказ завершён: продавец отметил оплату, покупатель подтвердил получение.');
    }

    private function order(bool $pickup = true): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
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
            $this->setOrder($order, [
                'workflow_version' => Order::WORKFLOW_PICKUP,
                'payment_status' => Order::PAYMENT_UNPAID,
            ]);
        }

        return [$order, $buyer];
    }

    private function setOrder(Order $order, array $values): void
    {
        DB::table('orders')->where('id', $order->id)->update($values);
        $order->refresh();
    }

    private function receiptForms(string $html, Order $order): array
    {
        preg_match_all('/<form\b([^>]*)>(.*?)<\/form>/s', $html, $matches, PREG_SET_ORDER);

        return array_values(array_filter(array_map(function ($match) use ($order) {
            preg_match('/\baction="([^"]+)"/', $match[1], $url);

            return isset($url[1]) && html_entity_decode($url[1]) === route('orders.confirmDelivery', $order)
                ? ['tag' => $match[1], 'body' => $match[2]]
                : null;
        }, $matches)));
    }
}
