<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserNotification;
use App\Notifications\MarketplaceEventNotification;
use App\Services\UserNotificationService;
use App\Database\SafeDatabaseTransactionsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckoutNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // fake() cleans its root; validate it before isolating checkout image snapshots.
        $fakeRoot = storage_path('framework/testing/disks/public');
        if ($token = \Illuminate\Support\Facades\ParallelTesting::token()) {
            $fakeRoot .= '_test_'.$token;
        }
        \Tests\Support\TestFileSafety::assertDirectory($fakeRoot, mustExist: false);
        Storage::fake('public');
    }

    public function beginDatabaseTransaction(): void
    {
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
        $this->assertSame(SafeDatabaseTransactionsManager::class, app('db.transactions')::class);
        $this->rollBackTestTransactionsBeforeApplicationDestroyed();
    }

    public function test_checkout_creates_unread_seller_notification_with_own_order_link(): void
    {
        Notification::fake();
        [$buyer, $sellers] = $this->prepareCart();
        $this->submit()->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::sole();
        $notification = UserNotification::where('type', 'order_created')->sole();
        $this->assertSame($sellers[0]->id, $notification->user_id);
        $this->assertSame('Новый заказ', $notification->title);
        $this->assertSame("Поступил новый заказ {$order->number}.", $notification->body);
        $this->assertSame(['order_id' => $order->id], $notification->data);
        $this->assertSame(route('seller.orders.show', $order, false), $notification->url);
        $this->assertNull($notification->read_at);
        $this->assertSame(0, $buyer->notifications()->count());
        $this->actingAs($sellers[0])->get(route('notifications.index'))->assertOk()->assertSee($order->number);
        $this->post(route('notifications.read', $notification))->assertRedirect($notification->url);
        $this->assertNotNull($notification->fresh()->read_at);
        $this->get($notification->url)->assertOk();
    }

    public function test_multi_seller_checkout_notifies_each_seller_only_about_own_order(): void
    {
        Notification::fake();
        [$buyer, $sellers] = $this->prepareCart(2, true);
        $this->submit()->assertSessionHasNoErrors()->assertRedirect(route('orders.index'));
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('user_notifications', 2);
        foreach ($sellers as $seller) {
            $order = Order::where('seller_id', $seller->id)->sole();
            $notification = $seller->notifications()->sole();
            $this->assertSame(['order_id' => $order->id], $notification->data);
            $this->assertSame(route('seller.orders.show', $order, false), $notification->url);
            Notification::assertSentToTimes($seller, MarketplaceEventNotification::class, 1);
            Notification::assertSentTo($seller, MarketplaceEventNotification::class,
                fn ($mail) => $mail->body === "Поступил новый заказ {$order->number}." && $mail->url === $notification->url);
        }
        Notification::assertNotSentTo($buyer, MarketplaceEventNotification::class);
        $this->assertSame(0, $buyer->notifications()->count());
        $other = $sellers[1]->notifications()->sole();
        $this->actingAs($sellers[0])->get($other->url)->assertForbidden();
        $this->post(route('notifications.read', $other))->assertForbidden();
    }

    public function test_replay_does_not_duplicate_orders_or_notifications(): void
    {
        Notification::fake();
        $this->prepareCart();
        $snapshot = session()->only(['checkout_cart', 'checkout_token', 'checkout_version']);
        $this->submit()->assertSessionHasNoErrors();
        // Simulate the stale session snapshot of a concurrent/replayed submit.
        $this->withSession($snapshot)->submit()->assertRedirect(route('orders.show', Order::sole()))->assertSessionHas('success');
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('user_notifications', 1);
    }

    public function test_email_preference_off_still_creates_database_notification(): void
    {
        Notification::fake();
        $this->prepareCart(email: false);
        $this->submit()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('user_notifications', 1);
        Notification::assertNothingSent();
    }

    public function test_mid_checkout_failure_rolls_back_all_sellers_without_notifications(): void
    {
        Notification::fake();
        $this->prepareCart(2, true);
        $writes = 0;
        DB::listen(function ($query) use (&$writes) {
            if (str_starts_with($query->sql, 'insert into `order_items`') && ++$writes === 2) {
                throw new \RuntimeException('Injected checkout failure');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->submit();
            $this->fail('Expected rollback');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Injected checkout failure', $exception->getMessage());
        }
        $this->assertSame(2, $writes);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('user_notifications', 0);
        $this->assertSame([5, 5], Product::orderBy('id')->pluck('stock')->all());
        Notification::assertNothingSent();

        $this->submit()->assertSessionHasNoErrors()->assertRedirect(route('orders.index'));
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('order_items', 2);
        $this->assertSame([4, 4], Product::orderBy('id')->pluck('stock')->all());
    }

    public function test_email_failure_propagates_and_other_seller_is_still_notified(): void
    {
        Notification::fake();
        Log::spy();
        [, $sellers] = $this->prepareCart(2, true);
        $real = new UserNotificationService;
        $mock = \Mockery::mock(UserNotificationService::class)->makePartial();
        $mock->shouldReceive('sendEmail')->twice()->andReturnUsing(function (...$arguments) use ($real, $sellers) {
            if ($arguments[0]->id === $sellers[0]->id) {
                throw new \RuntimeException('Injected email failure');
            }
            $real->sendEmail(...$arguments);
        });
        $this->app->instance(UserNotificationService::class, $mock);
        $this->assertDeliveryFails();
        $this->assertDatabaseCount('orders', 2);
        $this->assertSame(1, $sellers[0]->notifications()->count());
        $this->assertSame(1, $sellers[1]->notifications()->count());
        Notification::assertNotSentTo($sellers[0], MarketplaceEventNotification::class);
        Notification::assertSentToTimes($sellers[1], MarketplaceEventNotification::class, 1);

    }

    public function test_queue_failure_preserves_database_notification_and_replay_protection(): void
    {
        config(['queue.default' => 'database', 'queue.connections.database.table' => 'missing_notification_jobs']);
        Log::spy();
        $this->prepareCart(email: true);
        $snapshot = session()->only(['checkout_cart', 'checkout_token', 'checkout_version']);
        $this->assertDeliveryFails();
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('user_notifications', 1);
        $this->withSession($snapshot)->submit()->assertSessionHas('success');
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('user_notifications', 1);
    }

    public function test_sync_mail_failure_propagates_after_commit(): void
    {
        config(['queue.default' => 'sync']);
        Log::spy();
        $this->prepareCart(email: true);
        $attempted = false;
        Event::listen(\Illuminate\Mail\Events\MessageSending::class, function () use (&$attempted) {
            $attempted = true;
            throw new \RuntimeException('Injected mail failure');
        });
        $this->assertDeliveryFails();
        $this->assertTrue($attempted);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('user_notifications', 1);
    }

    public function test_mail_rendering_failure_propagates_after_commit(): void
    {
        config(['queue.default' => 'sync']);
        Log::spy();
        $this->prepareCart(email: true);
        $this->mock(\Illuminate\Mail\Markdown::class, function ($mock) {
            $mock->shouldReceive('theme')->andReturnSelf();
            $mock->shouldReceive('render')->once()->andThrow(new \RuntimeException('Injected render failure'));
        });
        $this->assertDeliveryFails();
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('user_notifications', 1);

    }

    private function assertDeliveryFails(): void
    {
        $this->withoutExceptionHandling();
        $error = null;
        try { $this->submit(); } catch (\Throwable $exception) { $error = $exception; }
        $this->assertNotNull($error, 'Delivery failure must not be reported as success');
    }

    public function test_real_outer_commit_and_rollback_control_database_notification_and_email_job(): void
    {
        // Use actual MySQL commits and production callback semantics, not the
        // RefreshDatabase manager that executes callbacks inside its test wrapper.
        $this->assertSame(SafeDatabaseTransactionsManager::class, app('db.transactions')::class);
        config(['queue.default' => 'database']);
        $this->prepareCart(2, true);
        $snapshot = session()->only(['checkout_cart', 'checkout_token', 'checkout_version']);
        DB::beginTransaction();
        $this->submit()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('user_notifications', 2);
        $this->assertDatabaseCount('jobs', 0);
        DB::rollBack();
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('user_notifications', 0);
        $this->assertDatabaseCount('jobs', 0);

        $this->withSession($snapshot)->get(route('checkout.confirm'))->assertOk();
        $siteLevels = $jobLevels = [];
        DB::listen(function ($query) use (&$siteLevels, &$jobLevels) {
            if (str_starts_with($query->sql, 'insert into `user_notifications`')) {
                $siteLevels[] = DB::transactionLevel();
            }
            if (str_starts_with($query->sql, 'insert into `jobs`')) {
                $jobLevels[] = DB::transactionLevel();
            }
        });
        DB::beginTransaction();
        $this->submit()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('user_notifications', 2);
        $this->assertDatabaseCount('jobs', 0);
        DB::commit();
        $this->assertSame([2, 2], $siteLevels);
        $this->assertSame([0, 0], $jobLevels);
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseCount('user_notifications', 2);
        $jobs = DB::table('jobs')->get();
        $this->assertCount(2, $jobs);
        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);
            $queued = unserialize($payload['data']['command']);
            $this->assertInstanceOf(SendQueuedNotifications::class, $queued);
            $this->assertInstanceOf(MarketplaceEventNotification::class, $queued->notification);
            $this->assertSame(['mail'], $queued->channels);
            $seller = $queued->notifiables->sole();
            $order = Order::where('seller_id', $seller->id)->sole();
            $this->assertSame(route('seller.orders.show', $order, false), $queued->notification->url);
            $this->assertSame(url($queued->notification->url), $queued->notification->toMail($seller)->actionUrl);
        }
    }

    protected function prepareCart(int $sellerCount = 1, bool $email = false): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $sellers = [];
        for ($i = 0; $i < $sellerCount; $i++) {
            $seller = User::factory()->create([
                'role' => 'seller', 'notification_preferences' => ['email_orders' => $email, 'site_orders' => true],
            ]);
            $sellers[] = $seller;
            $product = Product::create([
                'user_id' => $seller->id, 'title' => 'Notification product '.$i,
                'slug' => 'notification-product-'.$i, 'price' => 100, 'currency_base' => 'PRB',
                'stock' => 5, 'status' => Product::STATUS_ACTIVE,
            ]);
            CartItem::create(['user_id' => $buyer->id, 'product_id' => $product->id, 'qty' => 1]);
        }
        $this->actingAs($buyer)->withSession(['currency' => 'PRB'])->post(route('checkout.prepare'))->assertRedirect();
        $this->get(route('checkout.confirm'))->assertOk();

        return [$buyer, $sellers];
    }

    protected function submit()
    {
        return $this->post(route('checkout.create'), [
            'payment_method' => 'cash', 'delivery_method' => 'pickup',
            'checkout_token' => session('checkout_token'),
        ]);
    }
}
