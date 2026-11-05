<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\PasswordSecurityService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Validated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CredentialLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Providers may already have resolved route limiters; reject shared cache.
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('cache.limiter') ?? config('cache.default'));

        // Keep the actual write barrier on a test-owned inode through rollback.
        $lock = tmpfile();
        $this->assertIsResource($lock);
        $this->beforeApplicationDestroyed(static fn () => fclose($lock));
        $path = stream_get_meta_data($lock)['uri'];
        $this->assertFileExists($path);
        $this->assertFalse(is_link($path));
        config([
            'backup.lock_path' => $path,
            'cache.default' => 'array', 'cache.limiter' => 'array',
            'session.driver' => 'database', 'session.connection' => 'mysql', 'session.table' => 'sessions', 'queue.default' => 'sync',
            'mail.default' => 'array', 'logging.default' => 'null',
        ]);

        Notification::fake();
        Route::middleware(['web', 'auth'])->get('/credential-probe', fn () => response('authenticated'));
    }

    public function test_rotation_between_password_authentication_and_device_issue_rejects_stale_login(): void
    {
        $user = User::factory()->create();
        $rotated = false;
        Event::listen(Login::class, function (Login $event) use ($user, &$rotated): void {
            if (! $rotated && $event->user->getAuthIdentifier() === $user->id) {
                $rotated = true;
                app(PasswordSecurityService::class)->rotate($user->fresh(), 'rotated-password');
            }
        });

        $response = $this->browser(null, 'POST', route('login'), [
            'login' => $user->email, 'password' => 'password', 'remember' => true,
        ])->assertSessionHasErrors('login');
        $this->assertTrue($rotated);
        $response->assertCookieMissing(AuthenticatedSessionController::REMEMBERED_DEVICES_COOKIE);
        $this->assertDatabaseMissing('user_remembered_devices', ['user_id' => $user->id]);
        $this->browser($this->sessionCookie($response), 'GET', '/credential-probe')->assertRedirect(route('login'));
        $this->browser($this->login($user, 'rotated-password'), 'GET', '/credential-probe')->assertOk();
    }

    public function test_rotation_after_device_validation_cannot_bind_the_new_password_to_old_authorization(): void
    {
        $user = User::factory()->create();
        $credential = app(PasswordSecurityService::class)->issueRememberedDevice($user);
        $rotated = false;
        Event::listen(Login::class, function () use ($user, &$rotated): void {
            if (! $rotated) {
                $rotated = true;
                app(PasswordSecurityService::class)->rotate($user->fresh(), 'rotated-password');
            }
        });

        $response = $this->browser(null, 'POST', route('login.remembered'), $credential)->assertSessionHasNoErrors();
        $this->assertTrue($rotated);
        $this->browser($this->sessionCookie($response), 'GET', '/credential-probe')->assertRedirect(route('login'));
        $this->browser(null, 'POST', route('login.remembered'), $credential)->assertSessionHasErrors('login');
    }

    public function test_password_rehash_runs_under_user_lock_and_binds_the_rehashed_credentials(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password', ['rounds' => 4])]);
        Hash::driver('bcrypt')->setRounds(5);
        $this->assertTrue(Hash::needsRehash($user->password));
        $level = DB::transactionLevel();
        $locked = false;
        $validated = false;
        DB::listen(function ($query) use (&$locked): void {
            if (str_contains($query->sql, 'from `users`') && str_contains($query->sql, 'for update')) {
                $locked = true;
            }
        });
        Event::listen(Validated::class, function () use ($level, &$locked, &$validated): void {
            $this->assertTrue($locked);
            $this->assertGreaterThan($level, DB::transactionLevel());
            $validated = true;
        });
        $session = $this->login($user);
        $this->assertTrue($validated);
        $this->assertNotSame($user->password, $user->fresh()->password);
        $this->assertFalse(Hash::needsRehash($user->fresh()->password));
        $this->browser($session, 'GET', '/credential-probe')->assertOk();
        $this->assertSame($level, DB::transactionLevel());
    }

    public static function rotationPaths(): array
    {
        return [['self'], ['reset'], ['admin-target'], ['admin-self'], ['admin-email-and-password']];
    }

    #[DataProvider('rotationPaths')]
    public function test_rotation_revokes_old_reset_links_and_devices_but_fresh_reset_works(string $path): void
    {
        $user = User::factory()->create(['role' => $path === 'admin-self' ? 'admin' : 'buyer']);
        $oldEmail = $user->email;
        $token = Password::createToken($user);
        $device = app(PasswordSecurityService::class)->issueRememberedDevice($user);
        $oldSession = $this->login($user);
        $newPassword = 'rotated-password';
        $data = ['password' => $newPassword, 'password_confirmation' => $newPassword];

        if ($path === 'self') {
            $response = $this->browser($oldSession, 'PUT', route('password.update'), $data + ['current_password' => 'password']);
        } elseif ($path === 'reset') {
            $response = $this->browser(null, 'POST', route('password.store'), $data + ['email' => $oldEmail, 'token' => $token]);
        } elseif ($path === 'admin-self') {
            $response = $this->browser($oldSession, 'PUT', route('admin.profile.update'), $data + [
                'name' => $user->name, 'email' => $oldEmail, 'current_password' => 'password',
            ]);
        } else {
            $adminSession = $this->login(User::factory()->admin()->create());
            $response = $this->browser($adminSession, 'PUT', route('admin.users.update', $user), $data + [
                'name' => $user->name, 'phone' => $user->phone, 'role' => $user->role,
                'email' => $path === 'admin-email-and-password' ? 'changed@example.test' : $oldEmail,
            ])->assertSessionHasNoErrors();
            $this->browser($adminSession, 'GET', '/credential-probe')->assertOk();
        }
        $response->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $oldEmail]);
        $this->assertDatabaseMissing('user_remembered_devices', ['user_id' => $user->id]);

        $this->browser($oldSession, 'GET', '/credential-probe')->assertRedirect(route('login'));
        if (in_array($path, ['self', 'admin-self'], true)) {
            $this->browser($this->sessionCookie($response), 'GET', '/credential-probe')->assertOk();
        }
        $this->browser(null, 'POST', route('login.remembered'), $device)->assertSessionHasErrors('login');
        $hash = $user->password;
        $this->reset($oldEmail, $token)->assertSessionHasErrors('email');
        $this->assertSame($hash, $user->fresh()->password);

        $this->browser(null, 'POST', route('password.email'), ['email' => $user->email])->assertSessionHasNoErrors();
        $freshToken = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use (&$freshToken): bool {
            $freshToken = $notification->token;
            return true;
        });
        $this->assertIsString($freshToken);
        $this->reset($user->email, $freshToken)->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $this->browser($this->login($user, 'reset-password'), 'GET', '/credential-probe')->assertOk();
        $this->reset($user->email, $freshToken)->assertSessionHasErrors('email');
    }

    public function test_outer_rollback_restores_password_reset_token_device_and_existing_session(): void
    {
        $user = User::factory()->create();
        $hash = $user->password;
        $token = Password::createToken($user);
        $security = app(PasswordSecurityService::class);
        $device = $security->issueRememberedDevice($user);
        $session = $this->login($user);
        $level = DB::transactionLevel();
        try {
            DB::transaction(function () use ($security, $user): void {
                $security->rotate($user, 'rolled-back-password');
                throw new \RuntimeException('rollback');
            });
            $this->fail('Expected rollback');
        } catch (\RuntimeException $exception) {
            $this->assertSame('rollback', $exception->getMessage());
        }
        $this->assertSame($level, DB::transactionLevel());
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertTrue(Password::tokenExists($user->fresh(), $token));
        $this->browser($session, 'GET', '/credential-probe')->assertOk();
        $response = $this->browser(null, 'POST', route('login.remembered'), $device)->assertSessionHasNoErrors();
        $this->browser($this->sessionCookie($response), 'GET', '/credential-probe')->assertOk();
        $this->reset($user->email, $token)->assertSessionHasNoErrors();
    }

    public function test_reset_callback_failure_rolls_back_rotation_and_preserves_reset_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $device = app(PasswordSecurityService::class)->issueRememberedDevice($user);
        $hash = $user->password;
        try {
            app(PasswordSecurityService::class)->resetPassword([
                'email' => $user->email, 'token' => $token, 'password' => 'new-password',
            ], function (User $current, string $password): void {
                app(PasswordSecurityService::class)->rotate($current, $password);
                throw new \RuntimeException('reset rollback');
            });
            $this->fail('Expected reset rollback');
        } catch (\RuntimeException $exception) {
            $this->assertSame('reset rollback', $exception->getMessage());
        }
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertTrue(Password::tokenExists($user, $token));
        $response = $this->browser(null, 'POST', route('login.remembered'), $device)->assertSessionHasNoErrors();
        $this->browser($this->sessionCookie($response), 'GET', '/credential-probe')->assertOk();
    }

    public function test_rolled_back_device_issue_cannot_authenticate(): void
    {
        $user = User::factory()->create();
        $credential = null;
        try {
            DB::transaction(function () use ($user, &$credential): void {
                $credential = app(PasswordSecurityService::class)->issueRememberedDevice($user);
                throw new \RuntimeException('issue rollback');
            });
            $this->fail('Expected issuance rollback');
        } catch (\RuntimeException $exception) {
            $this->assertSame('issue rollback', $exception->getMessage());
        }
        $this->assertIsArray($credential);
        $response = $this->browser(null, 'POST', route('login.remembered'), $credential)->assertSessionHasErrors('login');
        $this->browser($this->sessionCookie($response), 'GET', '/credential-probe')->assertRedirect(route('login'));
        $this->assertDatabaseMissing('user_remembered_devices', ['user_id' => $user->id]);
    }

    public function test_invalid_expired_and_wrong_user_reset_tokens_do_not_change_credentials(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $token = Password::createToken($user);
        $hash = $user->password;
        $this->reset($user->email, 'invalid-token')->assertSessionHasErrors('email');
        $this->reset($other->email, $token)->assertSessionHasErrors('email');
        $this->travel(61)->minutes();
        $this->reset($user->email, $token)->assertSessionHasErrors('email');
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame($other->password, $other->fresh()->password);
    }

    private function reset(string $email, string $token): TestResponse
    {
        return $this->browser(null, 'POST', route('password.store'), [
            'email' => $email, 'token' => $token,
            'password' => 'reset-password', 'password_confirmation' => 'reset-password',
        ]);
    }

    private function login(User $user, string $password = 'password'): string
    {
        return $this->sessionCookie($this->browser(null, 'POST', route('login'), [
            'login' => $user->email, 'password' => $password,
        ])->assertSessionHasNoErrors());
    }

    private function sessionCookie(TestResponse $response): string
    {
        $cookie = $response->getCookie(config('session.cookie'));
        $this->assertNotNull($cookie);

        return $cookie->getValue();
    }

    private function browser(?string $session, string $method, string $uri, array $data = []): TestResponse
    {
        Auth::forgetGuards();
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        $this->app['redirect']->setSession($this->app['session']->driver());
        $this->assertInstanceOf(\Illuminate\Session\DatabaseSessionHandler::class,
            $this->app['session']->driver()->getHandler());
        $this->assertSame(1, DB::transactionLevel(), 'Browser requests must remain inside the test transaction.');
        $this->defaultCookies = $session ? [config('session.cookie') => $session] : [];

        return $this->call($method, $uri, $data, $this->prepareCookiesForRequest());
    }
}
