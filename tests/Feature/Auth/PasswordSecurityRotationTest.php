<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordSecurityRotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_remembered_device_is_revoked_after_user_changes_password(): void
    {
        $user = User::factory()->create();
        $credential = $this->issueRememberedDevice($user);

        $this->post(route('login.remembered'), $credential)->assertRedirect();
        $this->assertAuthenticatedAs($user);

        $oldRememberToken = $user->refresh()->getRememberToken();

        $this->from('/profile')->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect('/profile')->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertCredentialWasRevoked($user, $credential, $oldRememberToken);

        $this->post(route('logout'));
        $this->assertRememberedLoginIsRejected($credential);
        $this->assertLocalLoginWorks($user, 'new-password-123');
    }

    public function test_remembered_device_is_revoked_after_password_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $credential = $this->issueRememberedDevice($user);
        $oldRememberToken = $user->refresh()->getRememberToken();
        $resetToken = Password::createToken($user);

        $this->post(route('password.store'), [
            'token' => $resetToken,
            'email' => $user->email,
            'password' => 'reset-password-123',
            'password_confirmation' => 'reset-password-123',
        ])->assertRedirect(route('login'))->assertSessionHasNoErrors();

        $this->assertCredentialWasRevoked($user, $credential, $oldRememberToken);
        $this->assertRememberedLoginIsRejected($credential);
        $this->assertLocalLoginWorks($user, 'reset-password-123');
        Notification::assertSentTo($user, PasswordChangedNotification::class);
    }

    public function test_remembered_device_is_revoked_when_admin_changes_user_password(): void
    {
        $user = User::factory()->create(['role' => 'buyer']);
        $credential = $this->issueRememberedDevice($user);
        $oldRememberToken = $user->refresh()->getRememberToken();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'password' => 'admin-set-password-123',
                'password_confirmation' => 'admin-set-password-123',
            ])->assertRedirect(route('admin.users.index'));

        $this->assertCredentialWasRevoked($user, $credential, $oldRememberToken);

        $this->post(route('logout'));
        $this->assertRememberedLoginIsRejected($credential);
        $this->assertLocalLoginWorks($user, 'admin-set-password-123');
    }

    public function test_remembered_device_is_revoked_when_admin_changes_own_password(): void
    {
        $admin = User::factory()->admin()->create();
        $credential = $this->issueRememberedDevice($admin);
        $oldRememberToken = $admin->refresh()->getRememberToken();

        $this->actingAs($admin)
            ->put(route('admin.profile.update'), [
                'name' => $admin->name,
                'email' => $admin->email,
                'current_password' => 'password',
                'password' => 'admin-new-password-123',
                'password_confirmation' => 'admin-new-password-123',
            ])->assertRedirect(route('admin.profile'));

        $this->assertAuthenticatedAs($admin);
        $this->assertCredentialWasRevoked($admin, $credential, $oldRememberToken);

        $this->post(route('logout'));
        $this->assertRememberedLoginIsRejected($credential);
        $this->assertLocalLoginWorks($admin, 'admin-new-password-123');
    }

    private function issueRememberedDevice(User $user): array
    {
        $response = $this->post(route('login'), [
            'login' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ])->assertSessionHasNoErrors();

        $cookie = $response->getCookie(AuthenticatedSessionController::REMEMBERED_DEVICES_COOKIE);

        $this->assertNotNull($cookie);
        $this->assertDatabaseHas('user_remembered_devices', ['user_id' => $user->id]);

        $this->withCookie($cookie->getName(), $cookie->getValue())
            ->post(route('logout'))
            ->assertRedirect('/');

        $loginPage = $this->withCookie($cookie->getName(), $cookie->getValue())
            ->get(route('login'))
            ->assertOk();

        preg_match('/name="selector" value="([^"]+)"/', $loginPage->getContent(), $selectorMatch);
        preg_match('/name="token" value="([^"]+)"/', $loginPage->getContent(), $tokenMatch);

        $this->assertNotEmpty($selectorMatch[1] ?? null);
        $this->assertNotEmpty($tokenMatch[1] ?? null);
        $this->assertGuest();

        return [
            'selector' => $selectorMatch[1],
            'token' => $tokenMatch[1],
        ];
    }

    private function assertCredentialWasRevoked(User $user, array $credential, ?string $oldRememberToken): void
    {
        $user->refresh();

        $this->assertDatabaseMissing('user_remembered_devices', [
            'user_id' => $user->id,
            'selector' => $credential['selector'],
        ]);
        $this->assertNotSame($oldRememberToken, $user->getRememberToken());
    }

    private function assertRememberedLoginIsRejected(array $credential): void
    {
        $this->post(route('login.remembered'), $credential)
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    private function assertLocalLoginWorks(User $user, string $password): void
    {
        $this->post(route('login'), [
            'login' => $user->email,
            'password' => $password,
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check($password, $user->refresh()->password));
    }
}
