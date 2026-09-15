<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const GOOGLE_LOGIN_ERROR = 'Не удалось войти через Google. Попробуйте снова или используйте другой способ входа.';

    public function test_google_callback_does_not_link_an_attacker_registered_local_account_by_email(): void
    {
        Notification::fake();

        $email = 'victim@example.com';
        $password = 'Attacker-password-123';

        $this->post('/register', [
            'name' => 'Attacker controlled account',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'seller',
            'terms' => '1',
        ])->assertRedirect();

        $attackerAccount = User::where('email', $email)->firstOrFail();
        $sensitiveState = $this->sensitiveState($attackerAccount);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        $this->fakeGoogleUser([
            'id' => 'google-victim-subject',
            'email' => $email,
            'name' => 'Victim',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', self::GOOGLE_LOGIN_ERROR);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($sensitiveState, $this->sensitiveState($attackerAccount->refresh()));
        $this->assertTrue(Hash::check($password, $attackerAccount->password));

        $this->post('/login', [
            'login' => $email,
            'password' => $password,
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($attackerAccount);
    }

    public function test_google_callback_does_not_link_a_verified_local_account_by_email(): void
    {
        $localUser = User::factory()->create([
            'email' => 'verified-local@example.com',
            'provider' => null,
            'provider_id' => null,
        ]);
        $sensitiveState = $this->sensitiveState($localUser);

        $this->fakeGoogleUser([
            'id' => 'different-google-subject',
            'email' => $localUser->email,
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', self::GOOGLE_LOGIN_ERROR);

        $this->assertGuest();
        $this->assertSame($sensitiveState, $this->sensitiveState($localUser->refresh()));
    }

    public function test_already_linked_google_account_can_log_in_by_provider_identity(): void
    {
        $user = User::factory()->create([
            'email' => 'linked@example.com',
            'provider' => 'google',
            'provider_id' => 'linked-google-subject',
            'password' => Hash::make(Str::random(64)),
            'password_set_at' => null,
            'remember_token' => null,
        ]);

        $this->fakeGoogleUser([
            'id' => 'linked-google-subject',
            'email' => $user->email,
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_verified_google_user_can_register_when_email_has_no_local_conflict(): void
    {
        Notification::fake();

        $this->fakeGoogleUser([
            'id' => 'new-google-subject',
            'email' => 'new-google-user@example.com',
            'name' => 'New Google User',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'new-google-user@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google', $user->provider);
        $this->assertSame('new-google-subject', $user->provider_id);
        $this->assertSame('buyer', $user->role);
        $this->assertNull($user->password_set_at);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_ambiguous_google_provider_identity_fails_closed(): void
    {
        User::factory()->create([
            'email' => 'first-linked@example.com',
            'provider' => 'google',
            'provider_id' => 'duplicated-google-subject',
        ]);
        User::factory()->create([
            'email' => 'second-linked@example.com',
            'provider' => 'google',
            'provider_id' => 'duplicated-google-subject',
        ]);

        $this->fakeGoogleUser([
            'id' => 'duplicated-google-subject',
            'email' => 'first-linked@example.com',
        ]);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error', self::GOOGLE_LOGIN_ERROR);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 2);
    }

    private function fakeGoogleUser(array $attributes): void
    {
        Socialite::fake('google', SocialiteUser::fake(array_merge([
            'email_verified' => true,
        ], $attributes)));
    }

    private function sensitiveState(User $user): array
    {
        return collect([
            'provider',
            'provider_id',
            'password',
            'password_set_at',
            'role',
            'email_verified_at',
            'remember_token',
        ])->mapWithKeys(fn (string $field) => [$field => $user->getRawOriginal($field)])->all();
    }
}
