<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Notifications\ContactChangedNotification;
use App\Notifications\MarketplaceEventNotification;
use App\Notifications\PasswordChangedNotification;
use App\Services\SupportReplyNotificationService;
use App\Services\UserNotificationService;
use App\Support\NotificationPreferences;
use Illuminate\Support\Facades\Notification;
use Tests\Support\CheckoutTestCase;

class NotificationPreferencesTest extends CheckoutTestCase
{
    public function test_every_event_obeys_independent_channel_preferences(): void
    {
        foreach (NotificationPreferences::EVENTS as $event => $category) {
            foreach ([false, true] as $site) {
                foreach ([false, true] as $email) {
                    Notification::fake();
                    $user = User::factory()->create(['notification_preferences' => [
                        'site_'.$category => $site, 'email_'.$category => $email,
                        'email_orders' => $category === 'orders' ? $email : true,
                        'email_messages' => $category === 'messages' ? $email : true,
                    ]]);
                    app(UserNotificationService::class)->create($user, $event, 'Event');
                    $this->assertSame((int) $site, $user->notifications()->count(), $event);
                    Notification::assertSentToTimes($user, MarketplaceEventNotification::class,
                        $category !== 'support' && $email ? 1 : 0);
                }
            }
        }
    }

    public function test_defaults_and_history_remain_consistent_after_disabling(): void
    {
        Notification::fake();
        $user = User::factory()->create(['notification_preferences' => null]);
        $service = app(UserNotificationService::class);
        $old = $service->create($user, 'order_created', 'Historical order');
        Notification::assertNothingSent();
        $this->actingAs($user)->patch(route('notifications.settings.update'), ['site_orders' => 0])
            ->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertFalse($user->notification_preferences['site_orders']);
        $this->assertTrue($user->notification_preferences['site_messages']);
        $this->assertFalse($user->notification_preferences['email_orders']);
        $html = $this->get(route('notifications.settings'))->assertOk()->getContent();
        $this->assertStringNotContainsString('name="email_security"', $html);
        foreach (NotificationPreferences::DEFAULTS as $key => $default) {
            preg_match('/<input id="notification-'.preg_quote($key, '/').'"[^>]*>/', $html, $input);
            $this->assertNotEmpty($input, $key);
            $this->assertSame((bool) $user->notification_preferences[$key], str_contains($input[0], 'checked'), $key);
        }
        $this->assertNull($service->create($user, 'order_created', 'Disabled order'));
        $this->assertSame(1, $user->notifications()->whereNull('read_at')->count());
        $this->get(route('notifications.index'))->assertOk()->assertSee('Historical order')->assertDontSee('Disabled order');
        $this->post(route('notifications.read', $old))->assertRedirect();
        $this->assertSame(0, $user->notifications()->whereNull('read_at')->count());
    }

    public function test_admin_reply_uses_support_preference_and_is_processed_once(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        foreach ([true, false] as $enabled) {
            $user = User::factory()->create(['notification_preferences' => [
                'site_support' => $enabled, 'site_messages' => ! $enabled,
                'email_orders' => true, 'email_messages' => true,
            ]]);
            $conversation = Conversation::create([
                'buyer_id' => $user->id, 'seller_id' => $admin->id,
                'conversation_type' => Conversation::TYPE_SUPPORT,
                'context_key' => 'support:'.$user->id,
            ]);
            $this->actingAs($admin)->post(route('admin.chats.messages.store', $conversation), ['body' => 'Support reply'])
                ->assertRedirect()->assertSessionHasNoErrors();
            $message = $conversation->messages()->sole();
            app(SupportReplyNotificationService::class)->send($message);
            $this->assertSame((int) $enabled, $user->notifications()->count());
            $this->assertNotNull($message->fresh()->support_notified_at);
            $user->update(['notification_preferences' => ['site_support' => true]]);
            app(SupportReplyNotificationService::class)->send($message);
            $this->assertSame((int) $enabled, $user->notifications()->count());
            $this->assertSame(1, $conversation->messages()->count());
        }
        Notification::assertNothingSent();
    }

    public function test_security_changes_are_mandatory_and_noop_saves_do_not_duplicate_them(): void
    {
        Notification::fake();
        $user = User::factory()->create(['notification_preferences' => ['email_security' => false]]);
        $oldEmail = $user->email;
        $user->update(['email' => 'changed@example.test', 'phone' => '+37377712345']);
        $user->save();
        Notification::assertSentOnDemandTimes(ContactChangedNotification::class, 2);
        Notification::assertSentOnDemand(ContactChangedNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $oldEmail);
        $user->update(['password' => 'new-password-123']);
        $user->save();
        Notification::assertSentToTimes($user, PasswordChangedNotification::class, 1);
        Notification::assertSentTo($user, PasswordChangedNotification::class,
            fn ($notification) => $notification->recipientEmail === 'changed@example.test');
        $user->sendEmailVerificationNotification();
        $user->sendPasswordResetNotification('test-token');
        Notification::assertSentTo($user, \App\Notifications\VerifyEmail::class);
        Notification::assertSentTo($user, \App\Notifications\ResetPasswordNotification::class);
    }

    public function test_checkout_with_only_email_enabled_sends_once(): void
    {
        Notification::fake();
        [, $sellers] = $this->prepareCart(email: true);
        $sellers[0]->update(['notification_preferences' => ['site_orders' => false, 'email_orders' => true]]);
        $snapshot = session()->only(['checkout_cart', 'checkout_token', 'checkout_version']);
        $this->submit()->assertSessionHasNoErrors();
        $this->withSession($snapshot)->submit()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('user_notifications', 0);
        Notification::assertSentToTimes($sellers[0], MarketplaceEventNotification::class, 1);
    }

    public function test_pickup_confirmation_request_survives_disabled_notifications(): void
    {
        Notification::fake();
        [$buyer, $sellers] = $this->prepareCart();
        $buyer->update(['notification_preferences' => ['site_orders' => false, 'email_orders' => false]]);
        $this->submit()->assertSessionHasNoErrors();
        $order = \App\Models\Order::sole();
        $workflow = app(\App\Services\OrderPickupWorkflow::class);
        $workflow->sellerUpdate($order, $sellers[0], \App\Models\Order::STATUS_PROCESSING);
        $workflow->sellerUpdate($order, $sellers[0], \App\Models\Order::STATUS_READY_FOR_PICKUP);
        $this->assertTrue($workflow->sellerRequestReceiptConfirmation($order, $sellers[0]));
        $this->assertFalse($workflow->sellerRequestReceiptConfirmation($order, $sellers[0]));
        $this->assertNotNull($order->fresh()->confirmation_requested_at);
        $this->assertSame(0, $buyer->notifications()->count());
        Notification::assertNothingSent();
    }
}
