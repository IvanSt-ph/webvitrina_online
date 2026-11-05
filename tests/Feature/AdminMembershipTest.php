<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PhoneAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminMembershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Limiters may already be resolved by providers; fail closed on shared cache.
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', config('cache.limiter') ?? config('cache.default'));

        // Retain the real write barrier on a test-owned inode through rollback.
        $lock = tmpfile();
        $this->assertIsResource($lock);
        $this->beforeApplicationDestroyed(static fn () => fclose($lock));
        $path = stream_get_meta_data($lock)['uri'];
        $this->assertFileExists($path);
        $this->assertFalse(is_link($path));
        config([
            'backup.lock_path' => $path,
            'cache.default' => 'array', 'cache.limiter' => 'array',
            'session.driver' => 'array', 'queue.default' => 'sync',
            'mail.default' => 'array', 'logging.default' => 'null',
        ]);
    }

    public function test_last_admin_self_delete_is_rejected_without_logout_or_anonymization(): void
    {
        $admin = User::factory()->admin()->create();
        $before = $admin->fresh()->getAttributes();
        $this->actingAs($admin)->from('/profile')->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/profile')->assertSessionHasErrors('user');
        $this->assertAuthenticatedAs($admin);
        $this->assertSame($before, $admin->fresh()->getAttributes());
    }

    public function test_self_delete_is_allowed_when_another_active_admin_remains(): void
    {
        $admin = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $this->actingAs($admin)->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/')->assertSessionHasNoErrors();
        $this->assertGuest();
        $this->assertSoftDeleted($admin);
        $this->assertSame('admin', $other->fresh()->role);
    }

    public static function reductions(): array
    {
        return [['delete'], ['service'], ['save']];
    }

    #[DataProvider('reductions')]
    public function test_shared_boundary_rejects_last_admin_even_with_stale_buyer_snapshot(string $operation): void
    {
        $stale = User::factory()->create(['role' => 'buyer']);
        $current = User::findOrFail($stale->id);
        $current->update(['role' => 'admin']);
        // An old buyer snapshot cannot bypass the current-role check.
        try {
            match ($operation) {
                'delete' => $stale->delete(),
                'service' => app(PhoneAssignmentService::class)->updateUser($stale, null, ['role' => 'buyer']),
                'save' => $stale->update(['role' => 'seller']),
            };
            $this->fail('Expected the last active administrator to be protected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($operation === 'delete' ? 'user' : 'role', $exception->errors());
        }
        $this->assertSame('admin', User::findOrFail($stale->id)->role);
    }

    public function test_deleted_admin_does_not_count_and_safe_promotion_and_demotion_work(): void
    {
        $first = User::factory()->admin()->create();
        $second = User::factory()->admin()->create();
        $second->delete();
        try {
            $first->update(['role' => 'buyer']);
            $this->fail('Deleted administrators must not count.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('role', $exception->errors());
        }
        $replacement = User::factory()->create();
        $replacement->update(['role' => 'admin']);
        $first->update(['role' => 'buyer']);
        $this->assertSame('buyer', $first->fresh()->role);
        $this->assertSame('admin', $replacement->fresh()->role);
    }

    public function test_admin_panel_allows_safe_role_changes_and_deletion(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();
        $this->actingAs($actor)->patch(route('admin.users.update', $target), [
            'name' => $target->name, 'email' => $target->email, 'role' => 'buyer', 'phone' => null,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('buyer', $target->fresh()->role);
        $target->update(['role' => 'admin']);
        $this->delete(route('admin.users.destroy', $target))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSoftDeleted($target);
        $this->assertSame('admin', $actor->fresh()->role);
    }
}
