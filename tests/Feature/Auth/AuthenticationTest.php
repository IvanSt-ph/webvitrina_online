<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'login' => $user->email, // ← ИСПРАВЛЕНО: было 'email', стало 'login'
            'password' => 'password',
        ]);
        
        $response->assertSessionHasNoErrors();

        $this->assertAuthenticated();
        $response->assertRedirect();
    }

    public function test_remember_me_creates_recaller_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertCookie(Auth::guard('web')->getRecallerName());
        $response->assertCookie(AuthenticatedSessionController::REMEMBERED_DEVICES_COOKIE);
        $this->assertAuthenticatedAs($user);
    }

    public function test_remembered_account_can_login_without_password_after_logout(): void
    {
        $user = User::factory()->create(['name' => 'Remembered Buyer']);

        $loginResponse = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $trustedCookie = $loginResponse->getCookie(AuthenticatedSessionController::REMEMBERED_DEVICES_COOKIE);

        $this->assertNotNull($trustedCookie);
        $this->assertDatabaseHas('user_remembered_devices', [
            'user_id' => $user->id,
        ]);

        $this->withCookie($trustedCookie->getName(), $trustedCookie->getValue())
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();

        $loginPage = $this->withCookie($trustedCookie->getName(), $trustedCookie->getValue())
            ->get('/login')
            ->assertOk()
            ->assertSee('Запомненные аккаунты')
            ->assertSee('Remembered Buyer');

        preg_match('/name="selector" value="([^"]+)"/', $loginPage->getContent(), $selectorMatch);
        preg_match('/name="token" value="([^"]+)"/', $loginPage->getContent(), $tokenMatch);

        $this->assertNotEmpty($selectorMatch[1] ?? null);
        $this->assertNotEmpty($tokenMatch[1] ?? null);

        $this->withCookie($trustedCookie->getName(), $trustedCookie->getValue())
            ->post(route('login.remembered'), [
                'selector' => $selectorMatch[1],
                'token' => $tokenMatch[1],
            ])
            ->assertRedirect(route('cabinet'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_forget_remembered_account_from_login_page(): void
    {
        $user = User::factory()->create(['name' => 'Forget Me']);

        $loginResponse = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ]);

        $trustedCookie = $loginResponse->getCookie(AuthenticatedSessionController::REMEMBERED_DEVICES_COOKIE);
        $this->assertNotNull($trustedCookie);

        $this->withCookie($trustedCookie->getName(), $trustedCookie->getValue())
            ->post('/logout')
            ->assertRedirect('/');

        $loginPage = $this->withCookie($trustedCookie->getName(), $trustedCookie->getValue())
            ->get('/login')
            ->assertOk()
            ->assertSee('Forget Me');

        preg_match('/name="selector" value="([^"]+)"/', $loginPage->getContent(), $selectorMatch);
        $selector = $selectorMatch[1] ?? null;

        $this->assertNotEmpty($selector);

        $this->withCookie($trustedCookie->getName(), $trustedCookie->getValue())
            ->delete(route('login.remembered.forget', $selector), [
                'selector' => $selector,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('user_remembered_devices', [
            'selector' => $selector,
        ]);

        $this->withCookie($trustedCookie->getName(), $trustedCookie->getValue())
            ->get('/login')
            ->assertOk()
            ->assertDontSee('Forget Me');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'login' => $user->email, // ← ИСПРАВЛЕНО
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
