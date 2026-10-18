<?php

namespace Tests\Feature;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use App\Services\ImageService;
use App\Services\PhoneAssignmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProfileDeletionRaceTest extends TestCase
{
    use RefreshDatabase;

    public static function sections(): array
    {
        return array_map(fn ($section) => [$section], ['personal', 'email', 'phone', 'contacts', 'legacy', 'ajax']);
    }

    #[DataProvider('sections')]
    public function test_cached_authenticated_user_cannot_write_after_deletion(string $section): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $request = $this->request($user, $section);
        User::findOrFail($user->id)->delete();
        $this->mock(ImageService::class)->shouldNotReceive('upload');

        try {
            app(ProfileController::class)->update($request);
            $this->fail('A deleted account must be rejected.');
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(User::class, $exception->getModel());
        }
        $this->assertAnonymized($user);
    }

    public function test_phone_service_rejects_deleted_user_and_admin_attributes(): void
    {
        $user = User::factory()->create();
        User::findOrFail($user->id)->delete();
        foreach ([null, '+37377999112'] as $phone) {
            try {
                app(PhoneAssignmentService::class)->updateUser($user, $phone, [
                    'name' => 'Restored', 'email' => 'restored@example.com', 'avatar' => 'avatars/restored.webp',
                ]);
                $this->fail('Phone service must reject deleted users, including unchanged null phone.');
            } catch (ModelNotFoundException $exception) {
                $this->assertSame(User::class, $exception->getModel());
            }
        }
        $this->assertAnonymized($user);
    }

    public function test_uploaded_avatar_is_cleaned_on_outer_rollback_and_old_avatar_survives(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.webp', 'old');
        $user = User::factory()->create(['avatar' => 'avatars/old.webp']);
        $this->actingAs($user);
        DB::beginTransaction();
        try {
            app(ProfileController::class)->update($this->request($user, 'ajax'));
            $new = $user->fresh()->avatar;
            $this->assertNotSame('avatars/old.webp', $new);
            Storage::disk('public')->assertExists($new);
            Storage::disk('public')->assertExists('avatars/old.webp');
        } finally {
            DB::rollBack();
        }
        $this->assertSame('avatars/old.webp', $user->fresh()->avatar);
        $this->assertSame(['avatars/old.webp'], Storage::disk('public')->allFiles('avatars'));
    }

    public function test_admin_self_profile_rejects_a_deleted_cached_user(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $request = $this->request($user, 'personal');
        $request->merge(['email' => $user->email]);
        User::findOrFail($user->id)->delete();
        try {
            app(AdminProfileController::class)->update($request);
            $this->fail('Deleted admin must not save their profile.');
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(User::class, $exception->getModel());
        }
        $this->assertAnonymized($user);
    }

    public function test_admin_upload_is_compensated_if_deletion_wins_before_assignment(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'buyer', 'avatar' => null]);
        $this->actingAs($admin);
        $request = $this->request($admin, 'personal');
        $request->merge(['role' => 'buyer', 'email' => $user->email]);
        $images = $this->partialMock(ImageService::class);
        $images->shouldReceive('upload')->once()->andReturnUsing(function () use ($user): string {
            User::findOrFail($user->id)->delete();
            Storage::disk('public')->put('avatars/rejected.webp', 'new upload');

            return 'avatars/rejected.webp';
        });
        try {
            app(UserController::class)->update($request, $user);
            $this->fail('Deleted target must not receive the new avatar.');
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(User::class, $exception->getModel());
        }
        $this->assertAnonymized($user);
        Storage::disk('public')->assertMissing('avatars/rejected.webp');
    }

    public function test_upload_is_cleaned_when_user_save_fails(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user);
        $events = User::getEventDispatcher();
        User::setEventDispatcher(clone $events);
        User::saving(function (User $saving) use ($user): void {
            if ($saving->is($user) && $saving->isDirty('avatar')) {
                throw new \RuntimeException('intentional save failure');
            }
        });
        try {
            app(ProfileController::class)->update($this->request($user, 'ajax'));
            $this->fail('Expected save failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('intentional save failure', $exception->getMessage());
        } finally {
            User::setEventDispatcher($events);
        }
        $this->assertNull($user->fresh()->avatar);
        $this->assertSame([], Storage::disk('public')->allFiles('avatars'));
    }

    private function request(User $user, string $section): Request
    {
        $request = Request::create('/profile', 'PATCH', [
            'profile_section' => $section, 'name' => 'Changed', 'email' => 'changed@example.com',
            'phone' => '+37377999112', 'phone_dirty' => '1', 'current_password' => 'password',
        ], files: in_array($section, ['personal', 'legacy', 'ajax'], true)
            ? ['avatar' => UploadedFile::fake()->image('avatar.jpg')] : []);
        if ($section === 'ajax') {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }
        $request->setUserResolver(fn () => $user);
        $request->setLaravelSession(app('session')->driver());
        app()->instance('request', $request);

        return $request;
    }

    private function assertAnonymized(User $user): void
    {
        $deleted = User::withTrashed()->findOrFail($user->id);
        $this->assertTrue($deleted->trashed());
        $this->assertSame('Удалённый аккаунт', $deleted->name);
        foreach (['email', 'phone', 'avatar', 'phone_verified_at', 'phone_verification_code'] as $field) {
            $this->assertNull($deleted->$field, $field);
        }
    }
}
