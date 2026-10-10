<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PickupSellerOrderActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pickup_pending_and_processing_show_allowed_progress_and_cancellation_forms(): void
    {
        [$order, $seller] = $this->order();

        foreach ([
            [Order::STATUS_PENDING, Order::STATUS_PROCESSING, 'Принять заказ'],
            [Order::STATUS_PROCESSING, Order::STATUS_READY_FOR_PICKUP, 'Готов к самовывозу'],
        ] as [$current, $target, $label]) {
            $this->setOrder($order, ['status' => $current]);
            $html = $this->actingAs($seller)->get(route('seller.orders.show', $order))
                ->assertOk()->assertSee($label)->getContent();
            $statusForms = $this->formsFor($html, route('seller.orders.updateStatus', $order));
            $this->assertCount(2, $statusForms);
            foreach ($statusForms as $form) {
                $this->assertPostWithCsrf($form);
            }
            $this->assertSame([$target, Order::STATUS_CANCELED], $this->submittedStatuses($statusForms));
            $this->assertStringContainsString('name="cancellation_reason" required', $statusForms[1]['body']);
            $this->assertSame([], $this->formsFor($html, route('seller.orders.confirmPayment', $order)));
        }
    }

    public function test_pickup_payment_form_matches_ready_and_delivered_server_conditions(): void
    {
        [$order, $seller] = $this->order();

        foreach ([Order::STATUS_READY_FOR_PICKUP, Order::STATUS_DELIVERED] as $status) {
            foreach (['cash', 'card'] as $method) {
                $this->setOrder($order, [
                    'status' => $status,
                    'payment_method' => $method,
                    'payment_status' => Order::PAYMENT_UNPAID,
                    'paid_at' => null,
                    'buyer_confirmed_at' => $status === Order::STATUS_DELIVERED ? now() : null,
                ]);
                $html = $this->actingAs($seller)->get(route('seller.orders.show', $order))
                    ->assertOk()->assertSee('Подтвердить получение оплаты')->getContent();
                $paymentForms = $this->formsFor($html, route('seller.orders.confirmPayment', $order));
                $this->assertCount(1, $paymentForms);
                $this->assertPostWithCsrf($paymentForms[0]);
                $this->assertSame([], $this->formsFor($html, route('seller.orders.updateStatus', $order)));
            }
        }

        foreach ([
            ['payment_status' => Order::PAYMENT_SELLER_CONFIRMED, 'paid_at' => now()],
            ['payment_status' => Order::PAYMENT_UNPAID, 'paid_at' => now()],
            ['payment_status' => Order::PAYMENT_SELLER_CONFIRMED, 'paid_at' => null],
            ['payment_method' => 'bank_transfer'],
            ['delivery_method' => 'courier'],
        ] as $invalid) {
            $this->setOrder($order, array_merge([
                'status' => Order::STATUS_READY_FOR_PICKUP,
                'payment_status' => Order::PAYMENT_UNPAID,
                'paid_at' => null,
                'payment_method' => 'cash',
                'delivery_method' => 'pickup',
            ], $invalid));
            $html = $this->actingAs($seller)->get(route('seller.orders.show', $order))->assertOk()->getContent();
            $this->assertSame([], $this->formsFor($html, route('seller.orders.confirmPayment', $order)));
        }
    }

    public function test_pickup_terminal_states_have_no_seller_status_or_payment_forms(): void
    {
        [$order, $seller] = $this->order();

        foreach ([Order::STATUS_COMPLETED, Order::STATUS_CANCELED] as $status) {
            $this->setOrder($order, ['status' => $status]);
            $html = $this->actingAs($seller)->get(route('seller.orders.show', $order))->assertOk()->getContent();
            $this->assertSame([], $this->formsFor($html, route('seller.orders.updateStatus', $order)));
            $this->assertSame([], $this->formsFor($html, route('seller.orders.confirmPayment', $order)));
        }
    }

    public function test_legacy_processing_keeps_its_existing_paid_transition_without_pickup_payment_form(): void
    {
        [$order, $seller] = $this->order(false);
        $this->setOrder($order, ['status' => Order::STATUS_PROCESSING]);

        $html = $this->actingAs($seller)->get(route('seller.orders.show', $order))
            ->assertOk()->assertSee('Отметить как оплаченный')->getContent();
        $statusForms = $this->formsFor($html, route('seller.orders.updateStatus', $order));
        $this->assertCount(2, $statusForms); // Existing legacy cancellation form is also present.
        $this->assertSame([Order::STATUS_PAID, Order::STATUS_CANCELED], $this->submittedStatuses($statusForms));
        foreach ($statusForms as $form) {
            $this->assertPostWithCsrf($form);
        }
        $this->assertSame([], $this->formsFor($html, route('seller.orders.confirmPayment', $order)));
    }

    public function test_pickup_cancellation_form_matches_unpaid_preparation_conditions(): void
    {
        [$order, $seller] = $this->order();

        foreach ([Order::STATUS_PENDING, Order::STATUS_PROCESSING] as $status) {
            $this->setOrder($order, ['status' => $status]);
            $html = $this->actingAs($seller)->get(route('seller.orders.show', $order))->assertOk()->getContent();
            $forms = array_values(array_filter($this->formsFor($html, route('seller.orders.updateStatus', $order)),
                fn ($form) => $this->submittedStatuses([$form]) === [Order::STATUS_CANCELED]));
            $this->assertCount(1, $forms);
            $this->assertPostWithCsrf($forms[0]);
            $this->assertStringContainsString('name="cancellation_reason" required', $forms[0]['body']);
        }

        foreach ([
            ['status' => Order::STATUS_READY_FOR_PICKUP],
            ['status' => Order::STATUS_DELIVERED, 'buyer_confirmed_at' => now()],
            ['status' => Order::STATUS_COMPLETED],
            ['status' => Order::STATUS_CANCELED],
            ['status' => Order::STATUS_PENDING, 'paid_at' => now()],
            ['status' => Order::STATUS_PENDING, 'payment_status' => Order::PAYMENT_SELLER_CONFIRMED],
            ['status' => Order::STATUS_PENDING, 'payment_method' => 'bank_transfer'],
            ['status' => Order::STATUS_PENDING, 'delivery_method' => 'courier'],
            ['status' => Order::STATUS_PENDING, 'workflow_version' => 3],
        ] as $invalid) {
            $this->setOrder($order, array_merge([
                'workflow_version' => Order::WORKFLOW_PICKUP,
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_UNPAID,
                'paid_at' => null,
                'buyer_confirmed_at' => null,
                'delivered_at' => null,
                'payment_method' => 'cash',
                'delivery_method' => 'pickup',
            ], $invalid));
            $html = $this->actingAs($seller)->get(route('seller.orders.show', $order))->assertOk()->getContent();
            $this->assertNotContains(Order::STATUS_CANCELED,
                $this->submittedStatuses($this->formsFor($html, route('seller.orders.updateStatus', $order))));
        }
    }

    public function test_pickup_buyer_cancellation_request_shows_accept_and_reject_forms(): void
    {
        [$order, $seller] = $this->order();
        $this->setOrder($order, ['cancellation_requested_at' => now(), 'cancellation_reason' => 'Не смогу получить']);

        $html = $this->actingAs($seller)->get(route('seller.orders.show', $order))
            ->assertOk()->assertSee('Не смогу получить')->assertSee('Подтвердить отмену')->getContent();
        $rejectForms = $this->formsFor($html, route('seller.orders.rejectCancellation', $order));
        $this->assertCount(1, $rejectForms);
        $this->assertPostWithCsrf($rejectForms[0]);
        $this->assertStringContainsString('name="reason" required', $rejectForms[0]['body']);

        $this->setOrder($order, ['status' => Order::STATUS_READY_FOR_PICKUP]);
        $html = $this->actingAs($seller)->get(route('seller.orders.show', $order))->assertOk()->getContent();
        $this->assertStringNotContainsString('Подтвердить отмену', $html);
        $this->assertCount(1, $this->formsFor($html, route('seller.orders.rejectCancellation', $order)));

        $this->post(route('seller.orders.rejectCancellation', $order), ['reason' => 'Заказ уже подготовлен'])
            ->assertSessionHasNoErrors();
        $this->get(route('seller.orders.show', $order))->assertOk()
            ->assertDontSee('Покупатель запросил отмену заказа');

        $this->setOrder($order, [
            'status' => Order::STATUS_PENDING,
            'cancellation_requested_at' => now(),
            'cancellation_reason' => 'Не смогу получить',
        ]);
        $this->post(route('seller.orders.updateStatus', $order), [
            'status' => Order::STATUS_CANCELED,
            'cancellation_reason' => 'Подтверждаю запрос покупателя',
        ])->assertSessionHasNoErrors();
        $this->get(route('seller.orders.show', $order))->assertOk()
            ->assertSee('Заказ уже отменён')
            ->assertDontSee('Покупатель запросил отмену заказа');
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

        return [$order, $seller];
    }

    private function setOrder(Order $order, array $values): void
    {
        DB::table('orders')->where('id', $order->id)->update($values);
        $order->refresh();
    }

    private function formsFor(string $html, string $action): array
    {
        preg_match_all('/<form\b([^>]*)>(.*?)<\/form>/s', $html, $matches, PREG_SET_ORDER);

        return array_values(array_filter(array_map(function ($match) use ($action) {
            preg_match('/\baction="([^"]+)"/', $match[1], $url);

            return isset($url[1]) && html_entity_decode($url[1]) === $action
                ? ['tag' => $match[1], 'body' => $match[2]]
                : null;
        }, $matches)));
    }

    private function assertPostWithCsrf(array $form): void
    {
        $this->assertMatchesRegularExpression('/\bmethod="POST"/i', $form['tag']);
        $this->assertMatchesRegularExpression('/\bname="_token"\s+value="[^"]+"/', $form['body']);
    }

    private function submittedStatuses(array $forms): array
    {
        return array_map(function ($form) {
            preg_match('/\bname="status"\s+value="([^"]+)"/', $form['body'], $match);

            return $match[1] ?? null;
        }, $forms);
    }
}
