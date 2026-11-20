<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const NEUTRAL = 'Если аккаунт с таким адресом существует, мы отправим инструкции по восстановлению пароля';

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status');
        
        // Проверяем что статус есть (текст может быть на любом языке)
        $this->assertNotNull(session('status'));
        $response->assertSessionHas('status', self::NEUTRAL);
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_broker_throttle_keeps_neutral_response_and_sends_only_once(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', [
            'email' => $user->email,
        ])->assertSessionHasNoErrors();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status', self::NEUTRAL);
        Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
    }

    public function test_reset_password_route_throttle_does_not_block_other_emails(): void
    {
        Notification::fake();
        $users = User::factory()->count(6)->create();

        foreach ($users as $user) {
            $this->post('/forgot-password', [
                'email' => $user->email,
            ])->assertSessionHasNoErrors();
        }
    }

    public function test_transport_error_is_neutral_and_log_contains_no_email_or_transport_message(): void
    {
        User::factory()->create(['email' => 'buyer@example.com']);

        Password::shouldReceive('sendResetLink')
            ->once()
            ->andThrow(new TransportException('SMTP secret-password buyer@example.com'));
        Log::shouldReceive('warning')->once()->with('Password reset email delivery failed.', [
            'exception' => TransportException::class,
        ]);

        $response = $this->from('/forgot-password')->post('/forgot-password', [
            'email' => 'buyer@example.com',
        ]);

        $response
            ->assertStatus(302)
            ->assertRedirect('/forgot-password')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', self::NEUTRAL);
    }

    public function test_existing_missing_and_broker_throttled_emails_have_identical_responses(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $responses = [];
        foreach ([$user->email, 'missing@example.test', $user->email] as $email) {
            $response = $this->from('/forgot-password')->post('/forgot-password', ['email' => $email]);
            $response->assertStatus(302)->assertRedirect('/forgot-password')
                ->assertSessionHasNoErrors()->assertSessionHas('status', self::NEUTRAL);
            $responses[] = [$response->getStatusCode(), $response->headers->get('Location'), session('status')];
        }
        $this->assertSame($responses[0], $responses[1]);
        $this->assertSame($responses[0], $responses[2]);
        Notification::assertCount(1);
    }

    public function test_invalid_email_is_rejected_without_notification(): void
    {
        Notification::fake();
        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'invalid'])
            ->assertRedirect('/forgot-password')->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    public function test_email_ip_limit_is_five_and_another_ip_has_its_own_budget(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
                ->post('/forgot-password', ['email' => 'missing@example.test'])->assertStatus(302);
        }
        $this->post('/forgot-password', ['email' => 'missing@example.test'])->assertStatus(429);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.11'])
            ->post('/forgot-password', ['email' => 'missing@example.test'])->assertStatus(302);
    }

    public function test_ip_limit_covers_different_emails_and_expires_after_one_minute(): void
    {
        $this->freezeTime();
        for ($i = 0; $i < 20; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])
                ->post('/forgot-password', ['email' => "missing-$i@example.test"])->assertStatus(302);
        }
        $this->post('/forgot-password', ['email' => 'another@example.test'])->assertStatus(429);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.21'])
            ->post('/forgot-password', ['email' => 'another@example.test'])->assertStatus(302);
        $this->travel(61)->seconds();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])
            ->post('/forgot-password', ['email' => 'another@example.test'])->assertStatus(302);
    }

    public function test_untrusted_forwarded_header_does_not_change_ip_budget(): void
    {
        config(['trustedproxy.proxies' => []]);
        for ($i = 0; $i < 20; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.30'])
                ->withHeader('X-Forwarded-For', '198.51.100.'.($i + 1))
                ->post('/forgot-password', ['email' => "missing-$i@example.test"])->assertStatus(302);
        }
        $this->withHeader('X-Forwarded-For', '198.51.100.99')
            ->post('/forgot-password', ['email' => 'another@example.test'])->assertStatus(429);
    }

    public function test_trusted_proxy_uses_client_ip_for_independent_budgets(): void
    {
        config(['trustedproxy.proxies' => ['192.0.2.40']]);
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.40'])
                ->withHeader('X-Forwarded-For', '198.51.100.40')
                ->post('/forgot-password', ['email' => 'missing@example.test'])->assertStatus(302);
        }
        $this->post('/forgot-password', ['email' => 'missing@example.test'])->assertStatus(429);
        $this->withHeader('X-Forwarded-For', '198.51.100.41')
            ->post('/forgot-password', ['email' => 'missing@example.test'])->assertStatus(302);
    }

    public function test_delivered_reset_link_changes_password_and_cannot_be_reused(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status', self::NEUTRAL);
        $notification = Notification::sent($user, ResetPasswordNotification::class)->sole();
        $url = $notification->toMail($user)->viewData['url'];
        $this->get($url)->assertOk();
        $payload = ['token' => $notification->token, 'email' => $user->email,
            'password' => 'newpassword123', 'password_confirmation' => 'newpassword123'];
        $this->post('/reset-password', $payload)->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $user->fresh()->password));
        Notification::assertSentTo($user, PasswordChangedNotification::class);
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        // Создаем пользователя
        $user = User::factory()->create();
        
        // Создаем токен сброса пароля через Password broker
        $token = Password::createToken($user);
        
        // Проверяем страницу сброса пароля
        $response = $this->get('/reset-password/' . $token);
        
        $response->assertStatus(200);
    }

    public function test_password_can_be_reset_and_notification_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        // Создаем токен сброса пароля
        $token = Password::createToken($user);

        // Сбрасываем пароль
        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        // Проверяем что пароль действительно изменился
        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $user->password));

        // Проверяем что кастомное уведомление было отправлено
        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }
}
