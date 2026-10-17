<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Rules\ThrottledCurrentPassword;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

class CurrentPasswordThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->startOfMinute());
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public static function endpoints(): array
    {
        $names = [
            'confirm', 'password', 'profile', 'email', 'contacts', 'buyer', 'delete', 'admin',
        ];

        return array_combine($names, array_map(fn ($name) => [$name], $names));
    }

    #[DataProvider('endpoints')]
    public function test_each_password_oracle_consumes_the_shared_budget(string $endpoint): void
    {
        $user = User::factory()->create(['role' => $endpoint === 'admin' ? 'admin' : 'buyer']);
        $before = $user->only(['email', 'password', 'email_verified_at']);
        $this->actingAs($user);

        for ($i = 0; $i < ThrottledCurrentPassword::MAX_ATTEMPTS; $i++) {
            $this->attempt($endpoint, $user, 'wrong-password')->assertStatus(422)
                ->assertJsonValidationErrors(in_array($endpoint, ['confirm', 'delete']) ? 'password' : 'current_password');
        }
        // A different endpoint cannot provide even a correct-password oracle.
        $this->attempt('confirm', $user, 'password')->assertStatus(429)->assertHeader('Retry-After', '60');
        $this->attempt($endpoint, $user, 'password')->assertStatus(429);
        $this->assertEquals($before, $user->fresh()->only(array_keys($before)));
        $this->assertNull($user->fresh()->deleted_at);
        $this->assertNull(session('auth.password_confirmed_at'));
    }

    public function test_alternating_endpoints_ips_and_sessions_does_not_reset_attempts(): void
    {
        $admin = User::factory()->admin()->create();
        foreach (['confirm', 'password', 'email', 'contacts', 'admin'] as $i => $endpoint) {
            session()->invalidate();
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.($i + 1)])->actingAs($admin);
            $this->attempt($endpoint, $admin, 'wrong-password')->assertStatus(422);
        }
        $this->attempt('delete', $admin, 'password')->assertStatus(429);
        $this->assertNotNull($admin->fresh());
    }

    public function test_success_refunds_only_its_own_attempt_and_does_not_clear_failures(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        for ($i = 0; $i < 4; $i++) {
            $this->attempt('confirm', $user, 'wrong-password')->assertStatus(422);
        }
        for ($i = 0; $i < 8; $i++) {
            $this->attempt('confirm', $user, 'password')->assertRedirect();
        }
        $this->assertNotNull(session('auth.password_confirmed_at'));
        $this->attempt('confirm', $user, 'wrong-password')->assertStatus(422);
        $this->attempt('password', $user, 'password')->assertStatus(429);
    }

    public function test_expiry_allows_confirmation_and_password_change_again(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->exhaust($user);
        $this->attempt('confirm', $user, 'password')->assertStatus(429);
        $this->travel(61)->seconds();
        $this->attempt('confirm', $user, 'password')->assertRedirect();
        $this->attempt('password', $user, 'password')->assertRedirect();
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_html_lockout_has_retry_after_and_does_not_extend_the_window(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->exhaust($user);
        $this->travel(59)->seconds();
        $this->post('/confirm-password', ['password' => 'password'])->assertStatus(429)->assertHeader('Retry-After', '1');
        $this->travel(1)->seconds();
        $this->post('/confirm-password', ['password' => 'password'])->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_named_limiter_store_is_used_across_rule_instances(): void
    {
        config(['cache.limiter' => 'credential-test', 'cache.stores.credential-test' => ['driver' => 'array']]);
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->exhaust($user);
        // Clearing the unrelated default store must not clear the limiter store.
        Cache::store()->flush();
        $this->attempt('password', $user, 'password')->assertStatus(429);
    }

    public function test_process_local_cache_is_rejected_outside_testing_before_hashing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Hash::shouldReceive('check')->never();
        $environment = app('env');
        try {
            app()->instance('env', 'production');
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('requires a shared database or Redis cache');
            (new ThrottledCurrentPassword())->validate('password', 'password', fn () => $this->fail('Unexpected validation'));
        } finally {
            app()->instance('env', $environment);
        }
    }

    public static function cacheFailures(): array
    {
        return ['failed increment' => [false], 'unavailable cache' => [true]];
    }

    #[DataProvider('cacheFailures')]
    public function test_cache_failure_cannot_bypass_password_confirmation(bool $throws): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $repository = \Mockery::mock(Repository::class);
        $repository->shouldReceive('getStore')->once()->andReturn(new ArrayStore());
        $repository->shouldReceive('add')->once()->andReturn(true);
        if ($throws) {
            $repository->shouldReceive('increment')->once()->andThrow(new \RuntimeException('Cache unavailable'));
        } else {
            $repository->shouldReceive('increment')->once()->andReturn(false);
        }
        Cache::shouldReceive('store')->once()->with(config('cache.limiter'))->andReturn($repository);
        Hash::shouldReceive('check')->never();
        $this->expectException(\RuntimeException::class);
        (new ThrottledCurrentPassword())->validate('password', 'password', fn () => $this->fail('Unexpected validation'));
    }

    public function test_lockout_does_not_block_other_users_login_or_nonsensitive_profile_changes(): void
    {
        $user = User::factory()->create(['email' => 'throttle-owner@example.com']);
        $other = User::factory()->create();
        $this->actingAs($user);
        $this->exhaust($user);
        $this->patch('/profile', ['name' => 'Updated name', 'email' => $user->email])->assertRedirect();
        $this->assertSame('Updated name', $user->fresh()->name);
        $this->post('/logout')->assertRedirect();
        $this->post('/login', ['login' => $user->email, 'password' => 'password'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->attempt('confirm', $user, 'password')->assertStatus(429);
        $this->actingAs($other);
        $this->attempt('confirm', $other, 'password')->assertRedirect();
    }

    public function test_admin_nonsensitive_edit_and_google_first_password_do_not_require_confirmation(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $this->exhaust($admin);
        $this->put('/admin/profile', ['name' => 'Renamed admin', 'email' => $admin->email])->assertRedirect();
        $this->assertSame('Renamed admin', $admin->fresh()->name);

        $google = User::factory()->create(['provider' => 'google', 'provider_id' => 'throttle-google', 'password_set_at' => null]);
        $this->actingAs($google);
        $this->exhaust($google);
        $this->put('/password', ['password' => 'first-password-123', 'password_confirmation' => 'first-password-123'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('first-password-123', $google->fresh()->password));
    }

    public function test_reservation_happens_before_hashing_and_blocked_attempt_does_not_hash(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        for ($i = 0; $i < 4; $i++) {
            $this->attempt('confirm', $user, 'wrong-password')->assertStatus(422);
        }
        // Controlled overlap at the password-check boundary; no sleeps or DB workers.
        Hash::shouldReceive('check')->once()->andReturnUsing(function () {
            try {
                (new ThrottledCurrentPassword())->validate('password', 'candidate', fn () => $this->fail('Unexpected password check'));
                $this->fail('An overlapping sixth attempt must be throttled');
            } catch (TooManyRequestsHttpException $exception) {
                $this->assertSame(429, $exception->getStatusCode());
            }

            return false;
        });
        $failed = false;
        (new ThrottledCurrentPassword())->validate('password', 'candidate', function () use (&$failed) {
            $failed = true;
        });
        $this->assertTrue($failed);
    }

    private function exhaust(User $user): void
    {
        for ($i = 0; $i < ThrottledCurrentPassword::MAX_ATTEMPTS; $i++) {
            $this->attempt('confirm', $user, 'wrong-password')->assertStatus(422);
        }
    }

    private function attempt(string $endpoint, User $user, string $password): TestResponse
    {
        [$method, $url, $data] = match ($endpoint) {
            'confirm' => ['POST', '/confirm-password', ['password' => $password]],
            'delete' => ['DELETE', '/profile', ['password' => $password]],
            'password' => ['PUT', '/password', ['current_password' => $password, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123']],
            'admin' => ['PUT', '/admin/profile', ['name' => $user->name, 'email' => 'changed@example.com', 'current_password' => $password]],
            default => ['PATCH', $endpoint === 'buyer' ? '/buyer/profile/update' : '/profile', [
                'name' => $user->name, 'email' => 'changed@example.com', 'current_password' => $password,
                'profile_section' => in_array($endpoint, ['email', 'contacts']) ? $endpoint : null,
            ]],
        };

        return $this->json($method, $url, $data);
    }
}
