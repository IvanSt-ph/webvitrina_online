<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PickupReceiptReminderTest extends TestCase
{
    use RefreshDatabase;

    private int $initialNotificationCount;

    protected function setUp(): void
    {
        parent::setUp();

        // Providers may already have resolved route limiters; reject shared cache.
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('cache.limiter') ?? config('cache.default'));

        // Keep the actual write barrier on a test-owned inode through rollback.
        $lock = tmpfile();
        $this->assertIsResource($lock);
        $this->beforeApplicationDestroyed(static fn () => fclose($lock));
        $path = stream_get_meta_data($lock)['uri'];
        $this->assertFileExists($path);
        $this->assertFalse(is_link($path));
        config([
            'backup.lock_path' => $path,
            'cache.default' => 'array', 'cache.limiter' => 'array',
            'session.driver' => 'array', 'queue.default' => 'sync',
            'mail.default' => 'array', 'logging.default' => 'null',
        ]);

        // The cooldown cases distinguish one second before and after 24 hours.
        $this->travelTo(now()->startOfSecond());
        $this->initialNotificationCount = UserNotification::count();
    }

    private function assertBuyerNotifications(User $buyer, int $expected): void
    {
        $this->assertSame($expected, UserNotification::where('user_id', $buyer->id)->count());
        // Also detect accidental notifications to another recipient.
        $this->assertSame($this->initialNotificationCount + $expected, UserNotification::count());
    }

    public function test_seller_reminder_records_one_event_and_notification_without_changing_confirmations(): void
    {
        [$order, $buyer, $seller] = $this->order();
        $before = $order->fresh();

        $initialHtml = $this->actingAs($seller)->get(route('seller.orders.show', $order))->assertOk()->getContent();
        $this->assertStringContainsString('Напомнить покупателю', $initialHtml);
        $this->assertStringContainsString(route('seller.orders.requestReceiptConfirmation', $order), $initialHtml);
        $this->assertStringContainsString('name="_token"', $initialHtml);

        $this->post(route('seller.orders.requestReceiptConfirmation', $order))
            ->assertRedirect()->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Напоминание создано для покупателя.');

        $after = $order->fresh();
        $this->assertNotNull($after->confirmation_requested_at);
        $this->assertSame($before->status, $after->status);
        $this->assertSame($before->payment_status, $after->payment_status);
        $this->assertSame($before->paid_at, $after->paid_at);
        $this->assertSame($before->buyer_confirmed_at, $after->buyer_confirmed_at);
        $this->assertSame($before->completed_at, $after->completed_at);
        $event = $order->events()->sole();
        $this->assertSame('pickup_confirmation_requested', $event->event_type);
        $this->assertSame($seller->id, $event->actor_id);
        $this->assertSame('seller', $event->actor_role);
        $this->assertStringStartsWith('pickup-confirmation-', $event->event_key);
        $this->assertBuyerNotifications($buyer, 1);
        $notification = UserNotification::where('user_id', $buyer->id)->sole();
        $this->assertSame($buyer->id, $notification->user_id);
        $this->assertSame('pickup_confirmation_requested', $notification->type);
        $this->assertSame($order->id, $notification->data['order_id']);

        $html = $this->get(route('seller.orders.show', $order))->assertOk()->getContent();
        $this->assertStringContainsString('Следующее напоминание доступно', $html);
        $this->assertStringNotContainsString(route('seller.orders.requestReceiptConfirmation', $order), $html);
    }

    public function test_repeated_post_during_24_hour_cooldown_has_no_new_event_or_notification(): void
    {
        [$order, $buyer, $seller] = $this->order();
        $this->actingAs($seller)->post(route('seller.orders.requestReceiptConfirmation', $order))->assertSessionHasNoErrors();
        $firstAt = $order->fresh()->confirmation_requested_at;
        $firstKey = $order->events()->sole()->event_key;
        $firstNotifications = UserNotification::where('user_id', $buyer->id)->orderBy('id')->get()->toArray();

        $this->post(route('seller.orders.requestReceiptConfirmation', $order))
            ->assertSessionHas('success', 'Повторное напоминание пока недоступно; новое уведомление не создавалось.');
        $this->assertTrue($firstAt->equalTo($order->fresh()->confirmation_requested_at));
        $this->assertSame($firstKey, $order->events()->sole()->event_key);
        $this->assertBuyerNotifications($buyer, 1);
        $this->assertSame($firstNotifications, UserNotification::where('user_id', $buyer->id)->orderBy('id')->get()->toArray());

        DB::table('orders')->where('id', $order->id)->update(['confirmation_requested_at' => now()->subDay()->addSecond()]);
        $this->post(route('seller.orders.requestReceiptConfirmation', $order))->assertSessionHasNoErrors();
        $this->assertSame(1, $order->events()->count());
        $this->assertBuyerNotifications($buyer, 1);
        $this->assertSame($firstNotifications, UserNotification::where('user_id', $buyer->id)->orderBy('id')->get()->toArray());

        DB::table('orders')->where('id', $order->id)->update(['confirmation_requested_at' => now()->subDay()->subSecond()]);
        $this->post(route('seller.orders.requestReceiptConfirmation', $order))->assertSessionHasNoErrors();
        $this->assertSame(2, $order->events()->count());
        $this->assertBuyerNotifications($buyer, 2);
    }

    public function test_other_seller_and_buyer_cannot_send_reminder(): void
    {
        [$order, $buyer] = $this->order();
        $otherSeller = User::factory()->create(['role' => 'seller']);
        foreach ([$otherSeller, $buyer] as $actor) {
            $this->actingAs($actor)->post(route('seller.orders.requestReceiptConfirmation', $order))->assertForbidden();
        }
        $this->assertNull($order->fresh()->confirmation_requested_at);
        $this->assertSame(0, $order->events()->count());
        $this->assertBuyerNotifications($buyer, 0);
    }

    public function test_legacy_non_pickup_and_other_statuses_are_rejected(): void
    {
        // This case checks workflow validation across more requests than the route's 5/minute throttle.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        [$order, , $seller] = $this->order();
        $this->actingAs($seller);
        foreach ([Order::STATUS_PENDING, Order::STATUS_PROCESSING, Order::STATUS_COMPLETED, Order::STATUS_CANCELED] as $status) {
            DB::table('orders')->where('id', $order->id)->update(['status' => $status]);
            $this->postJson(route('seller.orders.requestReceiptConfirmation', $order))
                ->assertUnprocessable()->assertJsonValidationErrors('status');
        }
        DB::table('orders')->where('id', $order->id)->update([
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'workflow_version' => null,
        ]);
        $this->postJson(route('seller.orders.requestReceiptConfirmation', $order))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        DB::table('orders')->where('id', $order->id)->update([
            'workflow_version' => Order::WORKFLOW_PICKUP,
            'delivery_method' => 'courier',
        ]);
        $this->postJson(route('seller.orders.requestReceiptConfirmation', $order))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        DB::table('orders')->where('id', $order->id)->update([
            'delivery_method' => 'pickup',
            'payment_method' => 'bank_transfer',
        ]);
        $this->postJson(route('seller.orders.requestReceiptConfirmation', $order))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertNull($order->fresh()->confirmation_requested_at);
        $this->assertSame(0, $order->events()->count());
    }

    public function test_delivered_without_buyer_confirmation_is_allowed_but_confirmed_receipt_is_not(): void
    {
        [$order, $buyer, $seller] = $this->order();
        DB::table('orders')->where('id', $order->id)->update(['status' => Order::STATUS_DELIVERED]);
        $this->actingAs($seller)->post(route('seller.orders.requestReceiptConfirmation', $order))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, $order->events()->count());

        DB::table('orders')->where('id', $order->id)->update([
            'buyer_confirmed_at' => now(),
            'confirmation_requested_at' => null,
        ]);
        $this->postJson(route('seller.orders.requestReceiptConfirmation', $order))
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertSame(1, $order->events()->count());
        $this->assertBuyerNotifications($buyer, 1);
    }

    public function test_failed_event_insert_rolls_back_timestamp_and_notification(): void
    {
        [$order, $buyer, $seller] = $this->order();
        $injected = false;
        DB::listen(function ($query) use (&$injected): void {
            if (! $injected && str_starts_with(strtolower($query->sql), 'insert into `order_events`')) {
                $injected = true;
                throw new \RuntimeException('Injected reminder event failure');
            }
        });
        $this->withoutExceptionHandling();
        $this->actingAs($seller);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Injected reminder event failure');
        try {
            $this->post(route('seller.orders.requestReceiptConfirmation', $order));
        } finally {
            $this->assertTrue($injected);
            $this->assertNull($order->fresh()->confirmation_requested_at);
            $this->assertSame(Order::STATUS_READY_FOR_PICKUP, $order->fresh()->status);
            $this->assertSame(0, $order->events()->count());
            $this->assertBuyerNotifications($buyer, 0);
        }
    }

    public function test_active_order_prevents_buyer_deletion_and_reminder_remains_available(): void
    {
        [$order, $buyer, $seller] = $this->order();
        $before = $order->getRawOriginal();
        $this->actingAs($buyer)->deleteJson(route('profile.destroy'), ['password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('user');
        $this->assertFalse(User::withTrashed()->findOrFail($buyer->id)->trashed());
        $this->assertSame($buyer->email, $buyer->fresh()->email);
        $this->assertSame($before, $order->fresh()->getRawOriginal());
        $this->assertNull($order->fresh()->confirmation_requested_at);
        $this->assertSame(0, $order->events()->count());
        $this->assertBuyerNotifications($buyer, 0);

        $this->actingAs($seller)->post(route('seller.orders.requestReceiptConfirmation', $order))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($order->fresh()->confirmation_requested_at);
        $this->assertSame('pickup_confirmation_requested', $order->events()->sole()->event_type);
        $this->assertBuyerNotifications($buyer, 1);
        $notification = UserNotification::where('user_id', $buyer->id)->sole();
        $this->assertSame('pickup_confirmation_requested', $notification->type);
        $this->assertSame($order->id, $notification->data['order_id']);
    }

    private function order(): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create(['role' => 'seller']);
        $order = Order::create([
            'user_id' => $buyer->id, 'seller_id' => $seller->id,
            'number' => Order::generateNumber(), 'status' => Order::STATUS_PENDING,
            'payment_method' => 'cash', 'delivery_method' => 'pickup',
            'total_price' => 100, 'currency' => 'PRB',
        ]);
        DB::table('orders')->where('id', $order->id)->update([
            'status' => Order::STATUS_READY_FOR_PICKUP,
            'workflow_version' => Order::WORKFLOW_PICKUP,
            'payment_status' => Order::PAYMENT_UNPAID,
            'ready_for_pickup_at' => now(),
        ]);

        return [$order->fresh(), $buyer, $seller];
    }
}
