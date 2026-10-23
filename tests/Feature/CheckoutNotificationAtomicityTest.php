<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Services\UserNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CheckoutTestCase;
use Tests\Support\TestDatabaseSafety;

class CheckoutNotificationAtomicityTest extends CheckoutTestCase
{
    public function test_second_site_insert_failure_rolls_back_every_seller_and_same_key_is_retryable(): void
    {
        Notification::fake();
        $this->prepareCart(2, true);
        $token = session('checkout_token');
        $writes = 0;
        $original = new \RuntimeException('Injected site notification insert failure');
        DB::listen(function ($query) use (&$writes, $original): void {
            if (str_starts_with($query->sql, 'insert into `user_notifications`') && ++$writes === 2) {
                throw $original;
            }
        });
        $this->withoutExceptionHandling();
        $observed = null;
        try {
            $this->submit();
        } catch (\Throwable $exception) {
            $observed = $exception;
        }
        $this->assertSame($original, $observed);
        $this->assertSame(2, $writes);
        $this->assertSame($token, session('checkout_token'));
        foreach (['orders', 'order_items', 'order_events', 'checkout_attempts', 'user_notifications'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertDatabaseCount('cart_items', 2);
        $this->assertSame([5, 5], Product::orderBy('id')->pluck('stock')->all());
        Notification::assertNothingSent();

        $this->submit()->assertRedirect(route('orders.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('checkout_attempts', 1);
        $this->assertDatabaseCount('user_notifications', 2);
        $this->assertSame([4, 4], Product::orderBy('id')->pluck('stock')->all());
    }

    public function test_missing_site_recipient_cannot_leave_a_committed_order(): void
    {
        Notification::fake();
        $this->prepareCart();
        $this->partialMock(UserNotificationService::class)->shouldReceive('createSite')->once()->andReturnNull();
        $this->submit()->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('checkout_attempts', 0);
        $this->assertDatabaseCount('user_notifications', 0);
        $this->assertSame(5, Product::sole()->stock);
        Notification::assertNothingSent();
    }

    public function test_independent_connection_sees_orders_and_notifications_only_after_final_commit(): void
    {
        config(['queue.default' => 'database']);
        [$buyer, $sellers] = $this->prepareCart(2, true);
        $snapshot = session()->only(['checkout_cart', 'checkout_token', 'checkout_version']);
        $probe = app('db.factory')->make(config('database.connections.mysql'), 'mysql');
        TestDatabaseSafety::assertMetadata(app()->environment(), config('database'), $probe);
        $probe->getPdo();
        try {
            TestDatabaseSafety::assertConnected(app()->environment(), config('database'), $probe);
            DB::beginTransaction();
            $this->submit()->assertSessionHasNoErrors();
            $this->assertDatabaseCount('orders', 2);
            $this->assertDatabaseCount('user_notifications', 2);
            $this->assertDatabaseCount('jobs', 0);
            foreach (['orders', 'user_notifications', 'checkout_attempts', 'jobs'] as $table) {
                $this->assertSame(0, $probe->table($table)->count());
            }
            DB::commit();
            $this->assertSame(2, $probe->table('orders')->count());
            $this->assertSame(2, $probe->table('user_notifications')->count());
            $this->assertSame(1, $probe->table('checkout_attempts')->count());
            $this->assertSame(2, $probe->table('jobs')->count());
            foreach ($sellers as $seller) {
                $order = Order::where('seller_id', $seller->id)->sole();
                $notification = $seller->notifications()->sole();
                $this->assertSame(['order_id' => $order->id], $notification->data);
                $this->assertSame(route('seller.orders.show', $order, false), $notification->url);
            }
            $this->assertSame(0, $buyer->notifications()->count());
            $this->withSession($snapshot)->submit()->assertRedirect(route('orders.index'));
            $this->assertSame(2, $probe->table('orders')->count());
            $this->assertSame(2, $probe->table('user_notifications')->count());
            $this->assertSame(2, $probe->table('jobs')->count());
        } finally {
            $probe->disconnect();
        }
    }
}
