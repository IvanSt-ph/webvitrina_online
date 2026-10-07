<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\UserRememberedDevice;
use App\Services\PasswordSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PasswordSessionRevocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['session.driver' => 'database']);
        Route::middleware(['web', 'auth'])->get('/session-security-probe', fn () => response('authenticated'));
    }

    public function test_password_change_preserves_session_a_and_revokes_session_b(): void
    {
        $user = User::factory()->create();
        $a = $this->loginBrowser($user);
        $b = $this->loginBrowser($user);
        $this->browser($a, 'GET', '/session-security-probe')->assertOk();
        $this->browser($b, 'GET', '/session-security-probe')->assertOk();

        $response = $this->browser($a, 'PUT', route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasNoErrors();
        $a = $response->getCookie(config('session.cookie'))->getValue();

        $this->browser($b, 'GET', '/session-security-probe')->assertRedirect(route('login'));
        $this->browser($a, 'GET', '/session-security-probe')->assertOk();
        $fresh = $this->loginBrowser($user, 'new-password-123');
        $this->browser($fresh, 'GET', '/session-security-probe')->assertOk();
        $this->browser(null, 'POST', route('login'), [
            'login' => $user->email, 'password' => 'password',
        ])->assertSessionHasErrors('login');
    }

    public function test_password_change_revokes_file_backed_sessions(): void
    {
        $directory = storage_path('framework/testing-password-sessions-'.bin2hex(random_bytes(8)));
        $files = new Filesystem;
        $files->makeDirectory($directory, 0755, true);
        config(['session.driver' => 'file', 'session.files' => $directory]);
        try {
            $this->test_password_change_preserves_session_a_and_revokes_session_b();
        } finally {
            $files->deleteDirectory($directory);
        }
    }

    public function test_reset_revokes_existing_session(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $old = $this->loginBrowser($user);
        $this->browser(null, 'POST', route('password.store'), [
            'token' => Password::createToken($user), 'email' => $user->email,
            'password' => 'reset-password-123', 'password_confirmation' => 'reset-password-123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $this->browser($old, 'GET', '/session-security-probe')->assertRedirect(route('login'));
        $this->browser($this->loginBrowser($user, 'reset-password-123'), 'GET', '/session-security-probe')->assertOk();
    }

    public function test_admin_reset_revokes_target_sessions_and_preserves_admin(): void
    {
        $user = User::factory()->create();
        $old = $this->loginBrowser($user);
        $admin = User::factory()->admin()->create();
        $adminSession = $this->loginBrowser($admin);
        $this->browser($adminSession, 'PUT', route('admin.users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'phone' => $user->phone,
            'role' => $user->role, 'password' => 'admin-password-123',
            'password_confirmation' => 'admin-password-123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        $this->browser($old, 'GET', '/session-security-probe')->assertRedirect(route('login'));
        $this->browser($adminSession, 'GET', '/session-security-probe')->assertOk();
        $this->browser($this->loginBrowser($user, 'admin-password-123'), 'GET', '/session-security-probe')->assertOk();
    }

    public function test_admin_self_change_preserves_only_initiating_session(): void
    {
        $admin = User::factory()->admin()->create();
        $a = $this->loginBrowser($admin);
        $b = $this->loginBrowser($admin);
        $response = $this->browser($a, 'PUT', route('admin.profile.update'), [
            'name' => $admin->name, 'email' => $admin->email, 'current_password' => 'password',
            'password' => 'admin-password-123', 'password_confirmation' => 'admin-password-123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.profile'));
        $a = $response->getCookie(config('session.cookie'))->getValue();
        $this->browser($b, 'GET', '/session-security-probe')->assertRedirect(route('login'));
        $this->browser($a, 'GET', '/session-security-probe')->assertOk();
    }

    public function test_legacy_session_without_marker_is_rejected(): void
    {
        $old = $this->loginBrowser(User::factory()->create());
        $payload = unserialize(base64_decode(DB::table('sessions')->where('id', $old)->value('payload')));
        unset($payload[PasswordSecurityService::SESSION_KEY]);
        DB::table('sessions')->where('id', $old)->update(['payload' => base64_encode(serialize($payload))]);
        $this->browser($old, 'GET', '/session-security-probe')->assertRedirect(route('login'));
    }

    public function test_same_second_rotations_do_not_share_a_marker(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $security = app(PasswordSecurityService::class);
        $security->rotate($user, 'new-password-123');
        $old = $this->loginBrowser($user, 'new-password-123');
        $timestamp = $user->password_set_at;
        $security->rotate($user, 'new-password-123');
        $this->assertTrue($timestamp->equalTo($user->password_set_at));
        $this->browser($old, 'GET', '/session-security-probe')->assertRedirect(route('login'));
        $this->browser($this->loginBrowser($user, 'new-password-123'), 'GET', '/session-security-probe')->assertOk();
    }

    public function test_stale_login_snapshot_cannot_adopt_rotated_credentials(): void
    {
        $user = User::factory()->create();
        $stale = $user->fresh();
        // Model the gap between credential validation and the Login event.
        app(PasswordSecurityService::class)->rotate($user, 'new-password-123');
        Route::middleware('web')->get('/stale-login-probe', function () use ($stale) {
            Auth::login($stale);
            return response('logged in');
        });
        $response = $this->browser(null, 'GET', '/stale-login-probe')->assertOk();
        $old = $response->getCookie(config('session.cookie'))->getValue();
        $this->browser($old, 'GET', '/session-security-probe')->assertRedirect(route('login'));
    }

    public function test_in_flight_request_does_not_refresh_its_marker_after_rotation(): void
    {
        $user = User::factory()->create();
        $old = $this->loginBrowser($user);
        Route::middleware(['web', 'auth'])->get('/in-flight-rotation-probe', function () use ($user) {
            app(PasswordSecurityService::class)->rotate($user->fresh(), 'new-password-123');
            return response('already authorized');
        });
        $response = $this->browser($old, 'GET', '/in-flight-rotation-probe')->assertOk();
        $old = $response->getCookie(config('session.cookie'))->getValue();
        $this->browser($old, 'GET', '/session-security-probe')->assertRedirect(route('login'));
    }

    public function test_admin_reset_of_self_does_not_preserve_privileged_session(): void
    {
        $admin = User::factory()->admin()->create();
        $old = $this->loginBrowser($admin);
        $response = $this->browser($old, 'PUT', route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'phone' => $admin->phone,
            'role' => 'admin', 'password' => 'admin-password-123',
            'password_confirmation' => 'admin-password-123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.users.index'));
        $old = $response->getCookie(config('session.cookie'))->getValue();
        $this->browser($old, 'GET', '/session-security-probe')->assertRedirect(route('login'));
    }

    public function test_rollback_preserves_credentials_and_session(): void
    {
        $user = User::factory()->create();
        $old = $this->loginBrowser($user);
        $hash = $user->password;
        try {
            DB::transaction(function () use ($user) {
                app(PasswordSecurityService::class)->rotate($user, 'new-password-123');
                throw new \RuntimeException('Rollback probe');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('Rollback probe', $exception->getMessage());
        }
        $this->assertSame($hash, $user->fresh()->password);
        $this->browser($old, 'GET', '/session-security-probe')->assertOk();
    }

    public function test_revoked_custom_device_cannot_be_bypassed_by_legacy_native_recaller(): void
    {
        $user = User::factory()->create(['remember_token' => Str::random(60)]);
        $oldRememberToken = $user->getRememberToken();
        $name = Auth::guard('web')->getRecallerName();
        $recaller = $user->getAuthIdentifier().'|'.$oldRememberToken.'|'.Auth::guard('web')->hashPasswordForCookie($user->getAuthPassword());

        $device = UserRememberedDevice::create([
            'user_id' => $user->id,
            'selector' => 'revoked-device-selector',
            'token_hash' => hash('sha256', 'revoked-device-token'),
            'expires_at' => now()->addDay(),
            'last_used_at' => now(),
        ]);
        $device->delete();

        $response = $this->browser(null, 'GET', '/session-security-probe', [], [$name => $recaller])
            ->assertRedirect(route('login'));

        $response->assertCookieExpired($name);
        $this->assertGuest();
        $this->assertNotSame($oldRememberToken, $user->fresh()->getRememberToken());
    }

    private function loginBrowser(User $user, string $password = 'password'): string
    {
        $response = $this->browser(null, 'POST', route('login'), [
            'login' => $user->email, 'password' => $password,
        ])->assertSessionHasNoErrors()->assertRedirect();

        return $response->getCookie(config('session.cookie'))->getValue();
    }

    private function browser(?string $session, string $method, string $uri, array $data = [], array $cookies = []): TestResponse
    {
        // Independent browser requests: no cached guard user or in-memory session.
        Auth::forgetGuards();
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        $this->app['redirect']->setSession($this->app['session']->driver());
        $this->defaultCookies = $cookies + ($session ? [config('session.cookie') => $session] : []);

        return $this->call($method, $uri, $data, $this->prepareCookiesForRequest());
    }
}
