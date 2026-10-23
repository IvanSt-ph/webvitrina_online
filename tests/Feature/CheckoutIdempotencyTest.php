<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CheckoutIdempotencyService;
use App\Services\UserNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\Support\CheckoutTestCase;
use Tests\Support\TestDatabaseSafety;

class CheckoutIdempotencyTest extends CheckoutTestCase
{
    private function snapshot(): array
    {
        return session()->only(['checkout_cart', 'checkout_token', 'checkout_version']);
    }

    public function test_multi_seller_replay_survives_cache_loss_and_changed_stock(): void
    {
        $this->prepareCart(2);
        $snapshot = $this->snapshot();
        $this->get(route('checkout.confirm'))->assertOk();
        $this->assertSame($snapshot['checkout_token'], session('checkout_token'));
        $this->submit()->assertRedirect(route('orders.index'));
        $orders = Order::orderBy('id')->get()->toArray();
        Product::query()->update(['stock' => 0]);
        $this->travel(11)->minutes();
        $this->withSession($snapshot)->submit()->assertRedirect(route('orders.index'));
        $this->withSession($snapshot)->get(route('checkout.confirm'))->assertRedirect(route('orders.index'));
        $this->assertSame($orders, Order::orderBy('id')->get()->toArray());
        $this->assertDatabaseCount('checkout_attempts', 1);
        $this->assertDatabaseCount('order_events', 2);
        $this->assertDatabaseCount('user_notifications', 2);
        $this->assertSame([0, 0], Product::orderBy('id')->pluck('stock')->all());
    }

    public function test_notification_and_logger_failure_do_not_fail_committed_checkout(): void
    {
        $this->prepareCart();
        $snapshot = $this->snapshot();
        $this->mock(UserNotificationService::class)->shouldReceive('create')->once()->andThrow(new \RuntimeException('notification'));
        Log::shouldReceive('error')->once()->andThrow(new \RuntimeException('logger'));
        $this->submit()->assertSessionHas('success');
        $this->withSession($snapshot)->submit()->assertRedirect(route('orders.show', Order::sole()));
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(4, Product::sole()->stock);
    }

    public function test_throwing_after_commit_keeps_key_and_orders(): void
    {
        $this->prepareCart();
        $snapshot = $this->snapshot();
        $original = new \RuntimeException('after commit');
        $armed = true;
        DB::listen(function ($query) use (&$armed, $original) {
            if ($armed && str_starts_with($query->sql, 'insert into `checkout_attempts`')) {
                $armed = false;
                DB::afterCommit(fn () => throw $original);
            }
        });
        $this->withoutExceptionHandling();
        $observed = null;
        try { $this->submit(); } catch (\Throwable $exception) { $observed = $exception; }
        $this->assertSame($original, $observed);
        $this->assertSame(0, DB::transactionLevel());
        $probe = app('db.factory')->make(config('database.connections.mysql'), 'mysql');
        TestDatabaseSafety::assertMetadata(app()->environment(), config('database'), $probe);
        $probe->getPdo();
        try {
            TestDatabaseSafety::assertConnected(app()->environment(), config('database'), $probe);
            $this->assertSame(1, $probe->table('checkout_attempts')->count());
            $this->assertSame(1, $probe->table('orders')->count());
            $this->assertSame(4, (int) $probe->table('products')->value('stock'));
        } finally {
            $probe->disconnect();
        }
        $this->withSession($snapshot)->submit()->assertRedirect(route('orders.show', Order::sole()));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_events', 1);
        $this->assertDatabaseCount('user_notifications', 1);
        $this->assertSame(4, Product::sole()->stock);
    }

    public function test_insert_failure_rolls_back_all_sellers_and_allows_same_key(): void
    {
        $this->prepareCart(2);
        $writes = 0;
        DB::listen(function ($query) use (&$writes) {
            if (str_starts_with($query->sql, 'insert into `order_items`') && ++$writes === 2) {
                throw new \RuntimeException('injected insert failure');
            }
        });
        $this->withoutExceptionHandling();
        $observed = null;
        try { $this->submit(); } catch (\RuntimeException $exception) { $observed = $exception; }
        $this->assertSame('injected insert failure', $observed?->getMessage());
        foreach (['orders', 'order_items', 'order_events', 'checkout_attempts', 'user_notifications'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertSame([5, 5], Product::orderBy('id')->pluck('stock')->all());
        $this->submit()->assertSessionHas('success');
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_savepoint_and_parent_rollback_remove_attempt_and_allow_retry(): void
    {
        $this->prepareCart();
        $snapshot = $this->snapshot();
        DB::beginTransaction();
        DB::beginTransaction();
        $this->submit()->assertSessionHas('success');
        $this->assertDatabaseCount('user_notifications', 0);
        DB::rollBack();
        $this->assertDatabaseCount('checkout_attempts', 0);
        $this->withSession($snapshot)->submit()->assertSessionHas('success');
        DB::rollBack();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('checkout_attempts', 0);
        $this->assertSame(5, Product::sole()->stock);
        $this->withSession($snapshot)->submit()->assertSessionHas('success');
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_outer_commit_persists_attempt_and_notifies_once(): void
    {
        $this->prepareCart();
        $snapshot = $this->snapshot();
        DB::beginTransaction();
        $this->submit()->assertSessionHas('success');
        $this->assertDatabaseCount('user_notifications', 0);
        DB::commit();
        $this->withSession($snapshot)->submit()->assertSessionHas('success');
        $this->assertDatabaseCount('checkout_attempts', 1);
        $this->assertDatabaseCount('user_notifications', 1);
    }

    public function test_foreign_owner_and_corrupt_result_fail_closed(): void
    {
        [$buyer] = $this->prepareCart();
        $token = session('checkout_token');
        $this->submit();
        $service = app(CheckoutIdempotencyService::class);
        $observed = null;
        try { $service->result($token, User::factory()->create()->id); } catch (\Throwable $exception) { $observed = $exception; }
        $this->assertInstanceOf(ValidationException::class, $observed);
        DB::table('checkout_attempts')->update(['order_ids' => '[]']);
        $observed = null;
        try { $service->result($token, $buyer->id); } catch (\Throwable $exception) { $observed = $exception; }
        $this->assertInstanceOf(\RuntimeException::class, $observed);
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(4, Product::sole()->stock);
    }

    public function test_unique_insert_collision_returns_result_without_running_creation(): void
    {
        [$buyer] = $this->prepareCart();
        $token = session('checkout_token');
        $this->submit();
        $called = false;
        DB::beginTransaction();
        $orders = app(CheckoutIdempotencyService::class)->execute($token, $buyer->id,
            function () use (&$called) { $called = true; return []; });
        DB::commit();
        $this->assertFalse($called);
        $this->assertSame([Order::sole()->id], array_map(fn ($order) => $order->id, $orders));
        $this->assertDatabaseCount('checkout_attempts', 1);
        $this->assertDatabaseCount('order_events', 1);
        $this->assertDatabaseCount('user_notifications', 1);
        $this->assertSame(4, Product::sole()->stock);
    }

    public function test_foreign_key_cannot_be_replayed_through_http(): void
    {
        $this->prepareCart();
        $snapshot = $this->snapshot();
        $this->submit();
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $this->withSession($snapshot)->actingAs($otherBuyer);
        // StartSession reloads the stored session on the next HTTP request.
        // Persist the new authenticated fingerprint, not the previous buyer's.
        session()->save();
        $this->from(route('checkout.confirm'))->submit()
            ->assertRedirect(route('checkout.confirm'))
            ->assertSessionHasErrors([
                'checkout_token' => 'Это оформление принадлежит другому покупателю.',
            ]);
        $this->assertAuthenticatedAs($otherBuyer);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('checkout_attempts', 1);
        $this->assertDatabaseCount('order_events', 1);
        $this->assertDatabaseCount('user_notifications', 1);
        $this->assertSame(4, Product::sole()->stock);
    }

    public function test_legacy_session_cannot_acquire_new_key_and_new_intent_can(): void
    {
        $this->prepareCart();
        $token = session('checkout_token');
        $product = Product::sole();
        $this->post(route('checkout.quick', $product), ['qty' => 1])->assertRedirect();
        $this->assertNotSame($token, session('checkout_token'));
        session()->forget('checkout_version');
        $this->submit()->assertRedirect(route('orders.index'))->assertSessionHas('error');
        $this->assertDatabaseCount('orders', 0);
        $this->assertNull(session('checkout_token'));
    }

    public function test_explicit_new_purchase_uses_new_key_after_success(): void
    {
        $this->prepareCart();
        $product = Product::sole();
        $token = session('checkout_token');
        $this->submit()->assertSessionHas('success');
        $this->post(route('checkout.quick', $product), ['qty' => 1])->assertRedirect();
        $this->assertNotSame($token, session('checkout_token'));
        $this->get(route('checkout.confirm'))->assertOk();
        $this->submit()->assertSessionHas('success');
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('checkout_attempts', 2);
        $this->assertSame(3, $product->fresh()->stock);
    }
}
