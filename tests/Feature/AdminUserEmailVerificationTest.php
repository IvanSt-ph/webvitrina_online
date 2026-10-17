<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\VerifyEmail;
use App\Services\PhoneAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminUserEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public static function updateMethods(): array
    {
        return [
            'PUT buyer same phone' => ['PUT', 'buyer', null, false],
            'PATCH buyer same phone' => ['PATCH', 'buyer', null, false],
            'PUT seller changed phone' => ['PUT', 'seller', '+37377123456', false],
            'PUT admin' => ['PUT', 'admin', null, false],
            'PATCH email and password' => ['PATCH', 'buyer', null, true],
        ];
    }

    #[DataProvider('updateMethods')]
    public function test_changed_email_loses_verification_and_requires_a_new_signed_link(
        string $method, string $role, ?string $phone, bool $changePassword,
    ): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['email' => 'old-address@example.com', 'role' => $role]);
        $oldLink = $this->verificationLink($user);
        $data = [
            'name' => $user->name, 'email' => 'new-address@example.com', 'role' => $role, 'phone' => $phone,
            'email_verified_at' => now()->toDateTimeString(),
        ];
        if ($changePassword) {
            $data += ['password' => 'changed-password-123', 'password_confirmation' => 'changed-password-123'];
        }
        $this->actingAs($admin)->call($method, route('admin.users.update', $user), $data)
            ->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertSame('new-address@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertSame($phone, $user->phone);
        if ($changePassword) {
            $this->assertTrue(Hash::check('changed-password-123', $user->password));
        }

        $this->actingAs($user)->post('/addresses', [])->assertRedirect(route('verification.notice'));
        $this->assertDatabaseMissing('user_addresses', ['user_id' => $user->id]);
        $this->get($oldLink)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
        $this->post(route('verification.send'))->assertRedirect()->assertSessionHasNoErrors();
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get($this->verificationLink($user))->assertRedirect();
        $this->assertNotNull($user->fresh()->email_verified_at);
        // Verified middleware now admits the request to address validation.
        $this->postJson('/addresses', [])->assertStatus(422);
    }

    public static function existingStates(): array
    {
        return ['verified' => [true], 'unverified' => [false]];
    }

    #[DataProvider('existingStates')]
    public function test_unchanged_email_preserves_exact_verification_state(bool $verified): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['email_verified_at' => $verified ? now()->subDay()->startOfSecond() : null]);
        $before = $user->getRawOriginal('email_verified_at');
        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'Renamed', 'email' => $user->email, 'role' => 'buyer',
            'email_verified_at' => now()->toDateTimeString(),
        ])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();
        $this->assertSame($before, $user->fresh()->getRawOriginal('email_verified_at'));
        $this->assertSame('Renamed', $user->fresh()->name);
    }

    public function test_admin_creation_cannot_inject_email_verification(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Created buyer', 'email' => 'created-buyer@example.com', 'role' => 'buyer',
            'password' => 'created-password-123', 'password_confirmation' => 'created-password-123',
            'email_verified_at' => now()->toDateTimeString(),
        ])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();
        $user = User::where('email', 'created-buyer@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->actingAs($user)->post('/addresses', [])->assertRedirect(route('verification.notice'));
    }

    public function test_email_comparison_uses_the_fresh_user_saved_by_the_admin_service(): void
    {
        $stale = User::factory()->create(['email' => 'original@example.com']);
        $current = $stale->fresh();
        $current->email = 'intervening@example.com';
        $current->save();
        $current->markEmailAsVerified();
        $this->assertNotNull($current->fresh()->email_verified_at);

        // This email equals the route-bound snapshot, but differs from the row
        // re-read under lock by the service used by admin.users.update.
        app(PhoneAssignmentService::class)->updateUser($stale, null, ['email' => $stale->email]);
        $this->assertSame('original@example.com', $stale->fresh()->email);
        $this->assertNull($stale->fresh()->email_verified_at);
    }

    private function verificationLink(User $user): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);
    }
}
