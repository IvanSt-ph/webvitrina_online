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
    private bool $rejectPhoto = false;
    private string $browserSessionId;

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
        Http::fake(['https://api.telegram.org/*' => fn ($request) => Http::response(
            ['ok' => $this->telegramApiStatus === 200 && ! ($this->rejectPhoto && str_ends_with($request->url(), '/sendPhoto'))], $this->telegramApiStatus,
        )]);
        $this->browserSessionId = \Illuminate\Support\Str::random(40);
        $this->withCredentials();
        $this->withCookie(config('session.cookie'), $this->browserSessionId);
    }

    private function browserAs(User $user, ?string $sessionId = null): static
    {
        $sessionId ??= $this->browserSessionId;
        $this->withCookie(config('session.cookie'), $sessionId);
        $session = session()->driver();
        $session->flush();
        $session->setId($sessionId);
        $session->start();
        // Persist the normal credential marker in the chosen browser's session.
        parent::actingAs($user);
        $session->save();

        return $this;
    }

    private function user(string $role = 'seller'): User
    {
        return User::factory()->create(['role' => $role, 'phone' => '+37377111222',
            'phone_verified_at' => null, 'email_verified_at' => now()]);
    }

    private function start(User $user, int $id = 100): Verification
    {
        $response = $this->browserAs($user)->post(route('phone.telegram.store'));
        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame(43, strlen($query['start']));
        $row = Verification::where('user_id', $user->id)->latest('id')->firstOrFail();
        $this->assertSame(hash('sha256', $query['start']), $row->token_hash);
        $this->assertSame(TelegramPhoneVerificationService::sessionHash($this->browserSessionId), $row->session_hash);
        $this->assertNotSame($this->browserSessionId, $row->session_hash);
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
        $this->withCredentials = false;
        try {
            return $this->postJson(route('telegram.webhook'), $event,
                ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret']);
        } finally {
            $this->withCredentials = true;
        }
    }

    private function finalize(Verification $row)
    {
        return $this->postJson(route('phone.telegram.finalize'), ['verification_id' => $row->id]);
    }

    public function test_contact_confirms_only_account_phone_and_replay_does_not_change_timestamp(): void
    {
        $user = $this->user('seller');
        $shop = Shop::create(['user_id' => $user->id, 'name' => 'Test shop', 'phone' => $user->phone, 'phone_verified_at' => null]);
        $row = $this->start($user);
        $event = $this->contact();
        $this->webhook($event)->assertOk();
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->assertNull($row->fresh()->consumed_at);
        $this->assertNotNull($row->fresh()->contact_verified_at);
        $this->getJson(route('phone.telegram.status'))->assertExactJson([
            'status' => 'contact_verified', 'verification_id' => $row->id,
        ]);
        $this->finalize($row)->assertRedirect(route('profile.edit'));
        $verified = $user->fresh()->phone_verified_at;
        $this->assertNotNull($verified);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($shop->fresh()->phone_verified_at);
        $this->assertNotNull($row->fresh()->consumed_at);
        $this->travel(2)->minutes();
        $this->webhook($event)->assertOk();
        $this->assertTrue($verified->equalTo($user->fresh()->phone_verified_at));
        $this->finalize($row)->assertUnprocessable();
        $this->assertTrue($verified->equalTo($user->fresh()->phone_verified_at));
    }

    public function test_another_session_of_same_user_cannot_finalize_or_read_pending_request(): void
    {
        $user = $this->user();
        $row = $this->start($user);
        $this->webhook($this->contact())->assertOk();
        $this->browserAs($user, \Illuminate\Support\Str::random(40));
        $this->getJson(route('phone.telegram.status'))->assertExactJson(['status' => 'inactive']);
        $this->finalize($row)->assertForbidden();
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->browserAs($user);
        $this->finalize($row)->assertRedirect();
    }

    public function test_logout_revokes_request_even_after_contact_was_checked(): void
    {
        $user = $this->user();
        $row = $this->start($user);
        $this->webhook($this->contact())->assertOk();
        $this->post(route('logout'))->assertRedirect('/');
        $this->assertNotNull($row->fresh()->invalidated_at);
        $this->finalize($row)->assertUnauthorized();
        // Even replaying the old session identity with a newly authenticated user cannot finish it.
        $this->browserAs($user)->withCookie(config('session.cookie'), $this->browserSessionId);
        $this->finalize($row)->assertUnprocessable();
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_contact_after_logout_cannot_prepare_or_confirm_phone(): void
    {
        $user = $this->user();
        $row = $this->start($user);
        $this->post(route('logout'))->assertRedirect('/');
        $this->webhook($this->contact())->assertOk();
        $this->assertNull($row->fresh()->contact_verified_at);
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_logout_from_another_session_does_not_revoke_original_request(): void
    {
        $user = $this->user();
        $row = $this->start($user);
        $this->webhook($this->contact())->assertOk();
        $this->browserAs($user, \Illuminate\Support\Str::random(40));
        $this->post(route('logout'))->assertRedirect('/');
        $this->assertNull($row->fresh()->invalidated_at);
        $this->browserAs($user)->withCookie(config('session.cookie'), $this->browserSessionId);
        $this->finalize($row)->assertRedirect();
    }

    public function test_finalization_requires_contact_and_unexpired_request(): void
    {
        $user = $this->user();
        $row = $this->start($user);
        $this->finalize($row)->assertUnprocessable();
        $this->webhook($this->contact())->assertOk();
        $this->travelTo($row->expires_at);
        $this->finalize($row)->assertUnprocessable();
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->assertNull($row->fresh()->consumed_at);
    }

    public function test_finalization_rechecks_phone_email_and_user(): void
    {
        $user = $this->user();
        $row = $this->start($user);
        $this->webhook($this->contact())->assertOk();
        $other = User::factory()->verifiedSeller()->create();
        $this->browserAs($other);
        $this->finalize($row)->assertNotFound();
        $this->browserAs($user);
        $user->forceFill(['email_verified_at' => null])->save();
        $this->finalize($row)->assertForbidden();
        $user->forceFill(['email_verified_at' => now()])->save();
        app(PhoneAssignmentService::class)->assignToUser($user, '+37377999999');
        app(PhoneAssignmentService::class)->assignToUser($user, '+37377111222');
        $this->finalize($row)->assertUnprocessable();
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_new_request_revokes_previously_checked_contact(): void
    {
        $user = $this->user();
        $old = $this->start($user);
        $this->webhook($this->contact())->assertOk();
        $new = $this->start($user, 200);
        $this->finalize($old)->assertUnprocessable();
        $this->finalize($new)->assertUnprocessable();
        $this->assertNotNull($old->fresh()->invalidated_at);
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_finalization_is_csrf_protected(): void
    {
        $user = $this->user();
        $row = $this->start($user);
        $this->webhook($this->contact())->assertOk();
        $this->app->bind(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, fn ($app) =>
            new class($app, $app['encrypter']) extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken {
                protected function runningUnitTests() { return false; }
            });
        $this->finalize($row)->assertStatus(419);
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->withHeader('X-CSRF-TOKEN', session()->token());
        $this->finalize($row)->assertRedirect();
    }

    public function test_legacy_unbound_pending_request_cannot_be_completed(): void
    {
        $user = $this->user();
        $row = $this->start($user);
        $row->forceFill(['session_hash' => null])->save();
        $this->webhook($this->contact())->assertOk();
        $this->assertNull($row->fresh()->contact_verified_at);
        $this->finalize($row)->assertForbidden();
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_secret_is_required_even_with_authenticated_browser(): void
    {
        $user = $this->user();
        $this->start($user);
        $this->postJson(route('telegram.webhook'), $this->contact())->assertForbidden();
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->assertNull(Verification::firstOrFail()->contact_verified_at);
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
            $this->assertNull(Verification::firstOrFail()->contact_verified_at);
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
        $this->assertNull($new->fresh()->contact_verified_at);
        $this->webhook($this->contact(201))->assertOk();
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->finalize($new)->assertRedirect();
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
        $this->assertNull($row->fresh()->contact_verified_at);
    }

    public function test_status_becomes_inactive_at_expiry_without_a_webhook(): void
    {
        $user = $this->user();
        $this->browserAs($user)->post(route('phone.telegram.store'))->assertRedirect();
        $row = Verification::firstOrFail();
        $this->getJson(route('phone.telegram.status'))->assertExactJson(['status' => 'pending']);
        $this->travelTo($row->expires_at->copy()->subSecond());
        $this->getJson(route('phone.telegram.status'))->assertExactJson(['status' => 'pending']);
        $this->travelTo($row->expires_at);
        $this->getJson(route('phone.telegram.status'))->assertExactJson(['status' => 'inactive']);
        $this->post(route('phone.telegram.store'))->assertRedirect();
        $this->assertSame(2, Verification::count());
        $this->getJson(route('phone.telegram.status'))->assertExactJson(['status' => 'pending']);
        Http::assertNothingSent();
    }

    public function test_completion_rechecks_database_phone_and_deleted_account(): void
    {
        $user = $this->user();
        $this->start($user);
        User::whereKey($user->id)->update(['phone' => '+37377999999']);
        $this->webhook($this->contact())->assertOk();
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->assertNull(Verification::firstOrFail()->contact_verified_at);
        User::whereKey($user->id)->update(['phone' => '+37377111222', 'deleted_at' => now()]);
        $this->webhook($this->contact(102))->assertOk();
        $this->assertNull(User::withTrashed()->findOrFail($user->id)->phone_verified_at);
        $this->assertNull(Verification::firstOrFail()->contact_verified_at);
    }

    public function test_creation_is_authorized_rate_limited_and_status_is_owner_scoped(): void
    {
        $this->post(route('phone.telegram.store'))->assertRedirect(route('login'));
        $user = $this->user();
        $this->start($user);
        $this->start($user, 200);
        $this->start($user, 300);
        $this->browserAs($user)->post(route('phone.telegram.store'))->assertStatus(429);
        $other = User::factory()->create(['role' => 'seller', 'phone' => null, 'phone_verified_at' => null]);
        $this->browserAs($other)->getJson(route('phone.telegram.status'))->assertExactJson(['status' => 'inactive']);
    }

    public function test_seller_sees_telegram_and_no_sms_even_with_old_credentials(): void
    {
        $user = $this->user();
        $this->browserAs($user)->get(route('profile.edit'))->assertOk()
            ->assertSee('Подтвердить через Telegram')->assertDontSee('Подтвердить через SMS');
        config(['services.twilio.enabled' => true, 'services.twilio.sid' => 'fake',
            'services.twilio.token' => 'fake', 'services.twilio.verify_sid' => 'fake']);
        $this->get(route('profile.edit'))->assertDontSee('Подтвердить через SMS');
    }

    public function test_start_form_preserves_post_csrf_and_opens_a_separate_tab(): void
    {
        $html = $this->browserAs($this->user())->get(route('profile.edit'))->assertOk()->getContent();
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);
        $form = $xpath->query('//form[@data-phone-start]')->item(0);
        $this->assertNotNull($form);
        $this->assertSame('POST', $form->getAttribute('method'));
        $this->assertSame(route('phone.telegram.store'), $form->getAttribute('action'));
        $this->assertSame('_blank', $form->getAttribute('target'));
        $this->assertSame('noopener noreferrer', $form->getAttribute('rel'));
        $this->assertSame(session()->token(), $xpath->query('.//input[@name="_token"]', $form)->item(0)->getAttribute('value'));
    }

    public function test_verified_phone_has_no_start_or_finalize_form(): void
    {
        $user = $this->user();
        $user->forceFill(['phone_verified_at' => now()])->save();
        $this->browserAs($user)->get(route('profile.edit'))->assertOk()
            ->assertSee('Подтверждён')->assertDontSee('data-phone-start>', false)
            ->assertDontSee('Подтвердить через Telegram')->assertDontSee('data-phone-finalize hidden', false);
        $this->getJson(route('phone.telegram.status'))->assertExactJson(['status' => 'verified']);
    }

    public function test_bot_messages_and_contact_keyboard(): void
    {
        $user = $this->user();
        $this->start($user);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendPhoto')
            && $request->hasFile('photo', file_get_contents(resource_path('telegram/phone-verification.png')), 'phone-verification.png')
            && array_column($request->data(), 'contents', 'name')['caption'] ===
                "🔐 <b>Подтверждение телефона WebVitrina</b>\n\nДля защиты аккаунта подтвердите свой номер телефона.\n\n📱 Нажмите кнопку ниже, чтобы поделиться своим контактом через Telegram.\n\n🛡️ Мы никогда не запрашиваем пароли и коды входа."
            && array_column($request->data(), 'contents', 'name')['parse_mode'] === 'HTML'
            && json_decode(array_column($request->data(), 'contents', 'name')['reply_markup'], true) === [
                'keyboard' => [[['text' => '📱 Поделиться моим номером', 'request_contact' => true]]],
                'resize_keyboard' => true, 'one_time_keyboard' => true,
            ]);
        $this->webhook($this->contact())->assertOk();
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage') && $request->data() === [
            'chat_id' => 12345,
            'text' => "✅ <b>Телефон проверен!</b>\n\nВернитесь во вкладку WebVitrina и нажмите «Завершить подтверждение».",
            'parse_mode' => 'HTML', 'reply_markup' => ['remove_keyboard' => true],
        ]);
        $this->assertNull($user->fresh()->phone_verified_at);
        Http::assertSentCount(2);
    }

    public function test_photo_failure_falls_back_without_rebinding_or_confirming_request(): void
    {
        $this->rejectPhoto = true;
        $user = $this->user();
        $row = $this->start($user);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && ($request['reply_markup']['keyboard'][0][0]['request_contact'] ?? false) === true
            && ! isset($request['verification_banner']));
        $this->assertSame(1, Verification::count());
        $this->assertSame(100, (int) $row->start_update_id);
        $this->assertNull($row->contact_verified_at);
        $this->assertNull($row->consumed_at);
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->webhook($this->contact())->assertOk();
        $this->finalize($row)->assertRedirect();
        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_invalid_and_expired_start_never_send_a_banner(): void
    {
        $this->webhook($this->event(100, ['text' => '/start '.str_repeat('a', 43)]))->assertOk();
        $url = app(TelegramPhoneVerificationService::class)->create($this->user(), $this->browserSessionId);
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->travelTo(Verification::firstOrFail()->expires_at);
        $this->webhook($this->event(101, ['text' => '/start '.$query['start']]))->assertOk();
        Http::assertSentCount(2);
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/sendPhoto'));
    }

    public function test_bot_explains_foreign_contact_and_expired_request(): void
    {
        $row = $this->start($this->user());
        $this->webhook($this->event(101, ['contact' => ['user_id' => 999, 'phone_number' => $row->phone]]))->assertOk();
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && $request['text'] === 'Не удалось проверить контакт. Нажмите «📱 Поделиться моим номером» и отправьте свой контакт, а не чужой.');
        $this->assertNull($row->fresh()->contact_verified_at);
        $this->travelTo($row->expires_at);
        $this->webhook($this->contact(102))->assertOk();
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && $request['text'] === 'Заявка истекла или недействительна. Начните подтверждение заново в WebVitrina.');
        $this->assertNull($row->fresh()->contact_verified_at);
    }

    public function test_buyer_cannot_start_or_check_telegram_verification(): void
    {
        $buyer = $this->user('buyer');
        $this->browserAs($buyer)->post(route('phone.telegram.store'))->assertForbidden();
        $this->getJson(route('phone.telegram.status'))->assertForbidden();
        $this->get(route('buyer.profile'))->assertOk()->assertDontSee('Подтвердить через Telegram');
        $this->assertSame(0, Verification::count());
    }

    public function test_role_change_prevents_completion_of_pending_verification(): void
    {
        $seller = $this->user();
        $this->start($seller);
        $seller->update(['role' => 'buyer']);
        $this->webhook($this->contact())->assertOk();
        $this->assertNull($seller->fresh()->phone_verified_at);
    }

    public function test_disabled_sms_routes_preserve_existing_verification(): void
    {
        $seller = $this->user();
        $seller->forceFill(['phone_verified_at' => now()->subDay()])->save();
        $shop = Shop::create(['user_id' => $seller->id, 'name' => 'Test shop',
            'phone' => $seller->phone, 'phone_verified_at' => now()->subDay()]);
        $userTime = $seller->phone_verified_at;
        $shopTime = $shop->phone_verified_at;
        config(['services.twilio.sid' => 'fake', 'services.twilio.token' => 'fake',
            'services.twilio.verify_sid' => 'fake', 'services.twilio.enabled' => false]);
        foreach (['phone.send', 'phone.verify', 'shop.phone.send', 'shop.phone.verify'] as $route) {
            $this->browserAs($seller)->post(route($route), ['code' => '123456'])->assertStatus(410);
        }
        Http::assertNothingSent();
        $this->assertTrue($userTime->equalTo($seller->fresh()->phone_verified_at));
        $this->assertTrue($shopTime->equalTo($shop->fresh()->phone_verified_at));
    }

    public function test_provider_failure_preserves_intermediate_contact_without_confirming_phone(): void
    {
        $user = $this->user();
        $row = $this->start($user);
        $this->telegramApiStatus = 500;
        $this->webhook($this->contact())->assertStatus(503)->assertExactJson(['ok' => false]);
        Http::assertSentCount(2); // Start reply, then the failed confirmation reply.
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->assertNotNull($row->fresh()->contact_verified_at);
        $this->webhook($this->contact())->assertOk();
        Http::assertSentCount(2); // A duplicate acknowledges the committed result without sending again.
        $this->finalize($row)->assertRedirect();
        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_failed_consumption_rolls_back_phone_confirmation_atomically(): void
    {
        $user = $this->user();
        $row = $this->start($user);
        $this->webhook($this->contact())->assertOk();
        Verification::saving(function (Verification $request): void {
            if ($request->isDirty('consumed_at')) {
                throw new \RuntimeException('Simulated write failure');
            }
        });
        $this->finalize($row)->assertStatus(503);
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->assertNull($row->fresh()->consumed_at);
    }

    public function test_old_token_and_another_sender_cannot_rebind_a_request(): void
    {
        $user = $this->user();
        $url = app(TelegramPhoneVerificationService::class)->create($user, $this->browserSessionId);
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
        $row = $this->start($user);
        $smsTime = now()->subMinute()->startOfSecond();
        User::whereKey($user->id)->update(['phone_verified_at' => $smsTime]);
        $this->webhook($this->contact())->assertOk();
        $this->finalize($row)->assertRedirect();
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
        $this->assertNull(Verification::firstOrFail()->contact_verified_at);
        config(['services.telegram.webhook_secret' => '']);
        $this->webhook($this->contact())->assertForbidden();
        $this->browserAs($user)->post(route('phone.telegram.store'))->assertNotFound();
    }
}
