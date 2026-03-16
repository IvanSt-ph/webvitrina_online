<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status');
        
        // Проверяем что статус есть (текст может быть на любом языке)
        $this->assertNotNull(session('status'));
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