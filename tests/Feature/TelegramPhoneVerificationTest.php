<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\TelegramPhoneVerification as Verification;
use App\Models\User;
use App\Services\PhoneAssignmentService;
use App\Services\TelegramPhoneVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramPhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    private int $telegramApiStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();
        $lock = tmpfile();
        $this->assertIsResource($lock);
        $this->beforeApplicationDestroyed(static fn () => fclose($lock));
        config([
            'backup.lock_path' => stream_get_meta_data($lock)['uri'],
            'cache.default' => 'array', 'cache.limiter' => 'array', 'session.driver' => 'array',
            'queue.default' => 'sync', 'mail.default' => 'array', 'logging.default' => 'null',
            'services.telegram.enabled' => true, 'services.telegram.username' => 'test_bot',
            'services.telegram.token' => 'fake-token', 'services.telegram.webhook_secret' => 'test-secret',
            'services.twilio.enabled' => false,
        ]);
        Http::preventStrayRequests();
        // Keep one stub: this Laravel version uses the first matching fake.
        Http::fake(['https://api.telegram.org/*' => fn () => Http::response(
            ['ok' => $this->telegramApiStatus === 200], $this->telegramApiStatus,
        )]);
    }

    private function user(string $role = 'buyer'): User
    {
        return User::factory()->create(['role' => $role, 'phone' => '+37377111222',
            'phone_verified_at' => null, 'email_verified_at' => null]);
    }

    private function start(User $user, int $id = 100): Verification
    {
        $response = $this->actingAs($user)->post(route('phone.telegram.store'));
        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame(43, strlen($query['start']));
        $row = Verification::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertSame(hash('sha256', $query['start']), $row->token_hash);
        $this->webhook($this->event($id, ['text' => '/start '.$query['start']]))->assertOk();

        return $row->refresh();
    }

    private function event(int $id, array $fields): array
    {
        return ['update_id' => $id, 'message' => array_merge([
            'message_id' => $id, 'date' => now()->timestamp,
            'from' => ['id' => 12345], 'chat' => ['id' => 12345, 'type' => 'private'],
        ], $fields)];
    }

    private function contact(int $id = 101): array
    {
        return $this->event($id, ['contact' => ['user_id' => 12345, 'phone_number' => '37377111222']]);
    }

    private function webhook(array $event)
    {
        // Webhook works without a browser cookie/session or CSRF token.
        return $this->postJson(route('telegram.webhook'), $event,
            ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret']);
    }

    public function test_contact_confirms_only_account_phone_and_replay_does_not_change_timestamp(): void
    {
        $user = $this->user('seller');
        $shop = Shop::create(['user_id' => $user->id, 'name' => 'Test shop', 'phone' => $user->phone, 'phone_verified_at' => null]);
        $row = $this->start($user);
        auth()->logout();
        $event = $this->contact();
        $this->webhook($event)->assertOk();
        $verified = $user->fresh()->phone_verified_at;
        $this->assertNotNull($verified);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertNull($shop->fresh()->phone_verified_at);
        $this->assertNotNull($row->fresh()->consumed_at);
        $this->travel(2)->minutes();
        $this->webhook($event)->assertOk();
        $this->assertTrue($verified->equalTo($user->fresh()->phone_verified_at));
    }

    public function test_secret_is_required_even_with_authenticated_browser(): void
    {
        $user = $this->user();
        $this->start($user);
        $this->postJson(route('telegram.webhook'), $this->contact())->assertForbidden();
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_text_foreign_missing_forwarded_and_wrong_phone_contacts_are_rejected(): void
    {
        $user = $this->user();
        $this->start($user);
        $events = [
            $this->event(101, ['text' => '+37377111222']),
            $this->event(102, ['contact' => ['user_id' => 999, 'phone_number' => $user->phone]]),
            $this->event(103, ['contact' => ['phone_number' => $user->phone]]),
            $this->event(104, ['contact' => ['user_id' => 12345, 'phone_number' => '+37377999999']]),
            $this->event(105, ['contact' => ['user_id' => 12345, 'phone_number' => $user->phone], 'forward_origin' => ['type' => 'user']]),
        ];
        foreach ($events as $event) {
            $this->webhook($event)->assertOk();
            $this->assertNull($user->fresh()->phone_verified_at);
        }
    }

    public function test_old_contact_cannot_complete_replacement_request(): void
    {
        $user = $this->user();
        $old = $this->start($user);
        $delayed = $this->contact(101);
        $new = $this->start($user, 200);
        $this->assertNotNull($old->fresh()->invalidated_at);
        $this->webhook($delayed)->assertOk();
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->assertNull($new->fresh()->consumed_at);
        $this->webhook($this->contact(201))->assertOk();
        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_changing_phone_and_changing_back_does_not_restore_request(): void
    {
        $user = $this->user();
        $row = $this->start($user);
        app(PhoneAssignmentService::class)->assignToUser($user, '+37377999999');
        app(PhoneAssignmentService::class)->assignToUser($user, '+37377111222');
        $this->webhook($this->contact())->assertOk();
        $this->assertNotNull($row->fresh()->invalidated_at);
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_expiry_at_ten_minutes_prevents_confirmation(): void
    {
        $user = $this->user();
        $row = $this->start($user);
        $this->travelTo($row->expires_at);
        $this->webhook($this->contact())->assertOk();
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_completion_rechecks_database_phone_and_deleted_account(): void
    {
        $user = $this->user();
        $this->start($user);
        User::whereKey($user->id)->update(['phone' => '+37377999999']);
        $this->webhook($this->contact())->assertOk();
        $this->assertNull($user->fresh()->phone_verified_at);
        User::whereKey($user->id)->update(['phone' => '+37377111222', 'deleted_at' => now()]);
        $this->webhook($this->contact(102))->assertOk();
        $this->assertNull(User::withTrashed()->findOrFail($user->id)->phone_verified_at);
    }

    public function test_creation_is_authorized_rate_limited_and_status_is_owner_scoped(): void
    {
        $this->post(route('phone.telegram.store'))->assertRedirect(route('login'));
        $user = $this->user();
        $this->start($user);
        $this->start($user, 200);
        $this->start($user, 300);
        $this->actingAs($user)->post(route('phone.telegram.store'))->assertStatus(429);
        $other = User::factory()->create(['role' => 'buyer', 'phone' => null, 'phone_verified_at' => null]);
        $this->actingAs($other)->getJson(route('phone.telegram.status'))->assertExactJson(['status' => 'inactive']);
    }

    public function test_telegram_is_primary_and_sms_requires_explicit_configuration(): void
    {
        $user = $this->user();
        $this->actingAs($user)->get(route('buyer.profile'))->assertOk()
            ->assertSee('Подтвердить через Telegram')->assertDontSee('Подтвердить через SMS');
        config(['services.twilio.enabled' => true, 'services.twilio.sid' => 'fake',
            'services.twilio.token' => 'fake', 'services.twilio.verify_sid' => 'fake']);
        $this->get(route('buyer.profile'))->assertSee('Подтвердить через SMS');
    }

    public function test_provider_failure_is_generic_and_committed_confirmation_is_preserved(): void
    {
        $user = $this->user();
        $this->start($user);
        $this->telegramApiStatus = 500;
        $this->webhook($this->contact())->assertStatus(503)->assertExactJson(['ok' => false]);
        Http::assertSentCount(2); // Start reply, then the failed confirmation reply.
        $this->assertNotNull($user->fresh()->phone_verified_at);
        $this->webhook($this->contact())->assertOk();
        Http::assertSentCount(2); // A duplicate acknowledges the committed result without sending again.
    }

    public function test_failed_consumption_rolls_back_phone_confirmation_atomically(): void
    {
        $user = $this->user();
        $row = $this->start($user);
        Verification::saving(function (Verification $request): void {
            if ($request->isDirty('consumed_at')) {
                throw new \RuntimeException('Simulated write failure');
            }
        });
        $this->webhook($this->contact())->assertStatus(503)->assertExactJson(['ok' => false]);
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->assertNull($row->fresh()->consumed_at);
    }

    public function test_old_token_and_another_sender_cannot_rebind_a_request(): void
    {
        $user = $this->user();
        $url = app(TelegramPhoneVerificationService::class)->create($user);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $start = $this->event(100, ['text' => '/start '.$query['start']]);
        $this->webhook($start)->assertOk();
        $foreign = $this->event(101, ['text' => '/start '.$query['start'],
            'from' => ['id' => 999], 'chat' => ['id' => 999, 'type' => 'private']]);
        $this->webhook($foreign)->assertOk();
        $this->assertSame(12345, (int) Verification::firstOrFail()->telegram_user_id);
        $this->start($user, 200);
        $this->webhook($start)->assertOk();
        $this->webhook($this->contact(102))->assertOk();
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_existing_sms_confirmation_is_not_overwritten(): void
    {
        $user = $this->user();
        $this->start($user);
        $smsTime = now()->subMinute()->startOfSecond();
        User::whereKey($user->id)->update(['phone_verified_at' => $smsTime]);
        $this->webhook($this->contact())->assertOk();
        $this->assertTrue($smsTime->equalTo($user->fresh()->phone_verified_at));
    }

    public function test_private_chat_is_required_and_disabled_provider_fails_closed(): void
    {
        $user = $this->user();
        $this->start($user);
        $event = $this->contact();
        $event['message']['chat'] = ['id' => -12345, 'type' => 'group'];
        $this->webhook($event)->assertOk();
        $this->assertNull($user->fresh()->phone_verified_at);
        config(['services.telegram.webhook_secret' => '']);
        $this->webhook($this->contact())->assertForbidden();
        $this->actingAs($user)->post(route('phone.telegram.store'))->assertNotFound();
    }
}
