<?php

namespace Tests\Feature\Auth;

use App\Http\Controllers\Admin\AdminProfileController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CredentialMutationRaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_must_confirm_password_when_submitted_email_only_matches_the_cached_user(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $request = $this->request($admin, ['name' => $admin->name, 'email' => $admin->email]);
        $admin->fresh()->update(['email' => 'concurrent@example.com']);

        try {
            app(AdminProfileController::class)->update($request);
            $this->fail('Changing the current email requires password confirmation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('current_password', $exception->errors());
        }
        $this->assertSame('concurrent@example.com', $admin->fresh()->email);
        $this->assertNull($admin->fresh()->email_verified_at);
    }

    public function test_admin_can_change_email_and_password_together_and_bind_the_rotated_session(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->put(route('admin.profile.update'), [
            'name' => 'Updated admin', 'email' => 'updated@example.com',
            'current_password' => 'password', 'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('admin.profile'))->assertSessionHasNoErrors();

        $current = $admin->fresh();
        $this->assertSame('updated@example.com', $current->email);
        $this->assertNull($current->email_verified_at);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-password-123', $current->password));
        $security = app(\App\Services\PasswordSecurityService::class);
        $this->assertSame($security->fingerprint($current), session($security::SESSION_KEY));
        $this->assertDatabaseHas('admin_activity_logs', ['admin_id' => $admin->id, 'action' => 'profile.updated']);
    }

    public function test_admin_rotation_still_rejects_a_revoked_session_even_with_the_new_current_password(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $request = $this->request($admin, [
            'name' => $admin->name, 'email' => 'must-not-save@example.com',
            'current_password' => 'rotated-password-123', 'password' => 'another-password-123',
            'password_confirmation' => 'another-password-123',
        ]);
        $security = app(\App\Services\PasswordSecurityService::class);
        $oldFingerprint = session($security::SESSION_KEY);
        $security->rotate($admin->fresh(), 'rotated-password-123');

        try {
            app(AdminProfileController::class)->update($request);
            $this->fail('A revoked authenticated snapshot must not authorize another rotation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('login', $exception->errors());
        }
        $this->assertSame($admin->email, $admin->fresh()->email);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('rotated-password-123', $admin->fresh()->password));
        $this->assertSame($oldFingerprint, session($security::SESSION_KEY));
        $this->assertDatabaseMissing('admin_activity_logs', ['admin_id' => $admin->id, 'action' => 'profile.updated']);
    }

    private function request(User $user, array $data): Request
    {
        $request = Request::create('/admin/profile', 'PUT', $data);
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session')->driver());
        app()->instance('request', $request);

        return $request;
    }
}
