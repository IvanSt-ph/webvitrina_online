<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UserController;
use App\Models\User;
use App\Services\ImageService;
use App\Services\ProfileAvatarOperation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAvatarLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        // Real root commits and production callbacks, not the testing transaction manager.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public static function transactions(): array
    {
        $cases = [];
        foreach (['update', 'store'] as $action) {
            foreach (['commit', 'outer rollback', 'ancestor rollback', 'savepoint rollback'] as $outcome) {
                $cases["$action $outcome"] = [$action, $outcome];
            }
        }
        return $cases;
    }

    #[DataProvider('transactions')]
    public function test_files_follow_final_transaction_outcome(string $action, string $outcome): void
    {
        [$actor, $target] = $this->fixture();
        $old = $target->avatar;
        DB::beginTransaction();
        try {
            if (in_array($outcome, ['ancestor rollback', 'savepoint rollback'], true)) {
                DB::beginTransaction();
            }
            $this->perform($action, $actor, $target);
            $saved = $action === 'store' ? User::where('email', 'new-avatar@example.com')->sole() : $target->fresh();
            $new = $saved->avatar;
            $this->assertNotSame($old, $new);
            $this->assertPair($old, true);
            $this->assertPair($new, true);
            if ($outcome === 'savepoint rollback') {
                DB::rollBack();
                $this->assertPair($new, false);
                DB::commit();
            } elseif ($outcome === 'ancestor rollback') {
                DB::commit(); // Child succeeds, but its parent must still compensate.
                $this->assertPair($new, true);
                DB::rollBack();
            } elseif ($outcome === 'commit') {
                DB::commit();
            } else {
                DB::rollBack();
            }
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
        $committed = $outcome === 'commit';
        $this->assertPair($new, $committed);
        $this->assertPair($old, $action === 'store' || ! $committed);
        if ($action === 'store') {
            $this->assertSame($committed ? 1 : 0, User::where('email', 'new-avatar@example.com')->count());
        } else {
            $this->assertSame($committed ? $new : $old, $target->fresh()->avatar);
        }
        $this->assertFilesMatchUsers();
    }

    public static function failures(): array
    {
        $cases = [];
        foreach (['update', 'store'] as $action) {
            foreach (['sql', 'after save', 'cancel'] as $failure) {
                $cases["$action $failure"] = [$action, $failure];
            }
        }
        return $cases;
    }

    #[DataProvider('failures')]
    public function test_failed_save_preserves_old_pair_and_removes_new_pair(string $action, string $failure): void
    {
        [$actor, $target] = $this->fixture();
        $before = $target->fresh()->getAttributes();
        $dispatcher = User::getEventDispatcher();
        User::setEventDispatcher(clone $dispatcher);
        $event = $failure === 'after save' ? 'saved' : 'saving';
        User::{$event}(function (User $user) use ($failure, $actor) {
            if (! $user->isDirty('avatar') && ! $user->wasChanged('avatar')) {
                return null;
            }
            $this->assertPair($user->avatar, true);
            if ($failure === 'sql') {
                $user->email = $actor->email; // Actual unique-index violation.
                return null;
            }
            if ($failure === 'cancel') {
                return false;
            }
            throw new \RuntimeException('intentional after save');
        });
        try {
            try {
                $this->perform($action, $actor, $target);
                $this->assertSame('store', $action); // store deliberately returns validation errors.
                $this->assertTrue(session('errors')->has('error'));
            } catch (\Throwable $exception) {
                if ($exception instanceof \PHPUnit\Framework\AssertionFailedError) {
                    throw $exception;
                }
                $this->assertSame('update', $action);
                if ($failure === 'sql') {
                    $this->assertInstanceOf(\Illuminate\Database\QueryException::class, $exception);
                    $this->assertSame(1062, (int) $exception->errorInfo[1]);
                } else {
                    $this->assertSame($failure === 'cancel' ? 'Avatar assignment was cancelled.' : 'intentional after save', $exception->getMessage());
                }
            }
        } finally {
            User::setEventDispatcher($dispatcher);
        }
        $this->assertSame($before, $target->fresh()->getAttributes());
        $this->assertSame(0, User::where('email', 'new-avatar@example.com')->count());
        $this->assertFilesMatchUsers();
    }

    public function test_stale_model_replacement_removes_the_current_avatar(): void
    {
        [$actor, $target] = $this->fixture();
        $stale = clone $target;
        $this->perform('update', $actor, $target);
        $first = $target->fresh()->avatar;
        $this->perform('update', $actor, $stale);
        $this->assertPair($first, false);
        $this->assertFilesMatchUsers();
    }

    public function test_post_commit_error_keeps_committed_avatar(): void
    {
        [$actor, $target] = $this->fixture();
        $connection = DB::connection();
        $database = config('database');
        $environment = app()->environment();
        $pdo = \Tests\Support\TestDatabaseSafety::assertConnected($environment, $database, $connection);
        $dispatcher = User::getEventDispatcher();
        User::setEventDispatcher(clone $dispatcher);
        User::saved(function (User $user): void {
            if ($user->wasChanged('avatar')) {
                DB::afterCommit(fn () => throw new \RuntimeException('after commit'));
            }
        });
        try {
            try {
                $this->perform('update', $actor, $target);
                $this->fail('Expected afterCommit failure.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('after commit', $exception->getMessage());
            }
            $this->assertPair($target->fresh()->avatar, true);
            $this->assertSame(0, DB::transactionLevel());
        } finally {
            User::setEventDispatcher($dispatcher);
            // The injected callback can skip deferred RELEASE_LOCK callbacks.
            // Close only the original approved test session, without rollback.
            \Tests\Support\TestDatabaseSafety::assertConnected($environment, $database, $connection, $pdo);
            $connection->disconnect();
            unset($pdo); // Do not keep the underlying MySQL session alive locally.
        }
    }

    public function test_unknown_commit_retains_files_and_logs_reconciliation(): void
    {
        [$actor, $target] = $this->fixture();
        $connection = DB::connection();
        $dispatcher = $connection->getEventDispatcher();
        $isolated = clone $dispatcher;
        $connection->setEventDispatcher($isolated);
        $isolated->listen(\Illuminate\Database\Events\TransactionCommitting::class,
            fn () => throw new \RuntimeException('commit interrupted'));
        Log::spy();
        try {
            $this->perform('update', $actor, $target);
            $this->fail('Expected commit failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('commit interrupted', $exception->getMessage());
            $this->assertCount(4, Storage::disk('public')->allFiles('avatars'));
            Log::shouldHaveReceived('error')->with('Profile avatar transaction outcome unknown; reconciliation required',
                \Mockery::on(fn ($context) => count($context['paths']) === 1))->once();
        } finally {
            $connection->setEventDispatcher($dispatcher);
            if ($connection->getPdo()->inTransaction()) {
                $connection->getPdo()->rollBack();
            }
            app('db.transactions')->rollback($connection->getName(), 0);
        }
        $this->assertFilesMatchUsers();
    }

    public function test_helper_requires_a_transaction(): void
    {
        $this->expectException(\LogicException::class);
        new ProfileAvatarOperation(app(ImageService::class), DB::connection());
    }

    public static function barrierOutcomes(): array
    {
        return ['commit' => [true], 'rollback' => [false]];
    }

    #[DataProvider('barrierOutcomes')]
    public function test_backup_barrier_covers_outer_transaction_and_file_cleanup(bool $commit): void
    {
        [$actor, $target] = $this->fixture();
        $handle = fopen(config('backup.lock_path'), 'c+b');
        $this->assertNotFalse($handle);
        $images = new ImageService();
        $this->partialMock(ImageService::class)->shouldReceive('delete')->once()
            ->andReturnUsing(function ($path, $throwOnFailure = false) use ($handle, $images): void {
                $this->assertFalse(flock($handle, LOCK_EX | LOCK_NB), 'Cleanup must still hold the shared barrier.');
                $images->delete($path, $throwOnFailure);
            });
        try {
            DB::beginTransaction();
            $this->perform('update', $actor, $target);
            $this->assertFalse(flock($handle, LOCK_EX | LOCK_NB));
            if ($commit) {
                DB::commit();
            } else {
                DB::rollBack();
            }
            $this->assertTrue(flock($handle, LOCK_EX | LOCK_NB), 'Barrier must be released after cleanup.');
            flock($handle, LOCK_UN);
            $this->assertFilesMatchUsers();
        } finally {
            fclose($handle);
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
    }

    public function test_failed_old_file_cleanup_is_logged_without_removing_new_avatar(): void
    {
        [$actor, $target] = $this->fixture();
        $old = $target->avatar;
        $this->partialMock(ImageService::class)->shouldReceive('delete')->with($old, true)->once()
            ->andThrow(new \RuntimeException('cleanup unavailable'));
        Log::spy();
        $this->perform('update', $actor, $target);
        $this->assertPair($old, true);
        $this->assertPair($target->fresh()->avatar, true);
        $this->assertNotSame($old, $target->fresh()->avatar);
        Log::shouldHaveReceived('error')->with('Profile avatar cleanup failed; retry required', \Mockery::on(
            fn ($context) => $context['path'] === $old && $context['phase'] === 'after_commit',
        ))->once();
    }

    public function test_partial_thumb_write_is_compensated_and_old_pair_survives(): void
    {
        [$actor, $target] = $this->fixture();
        $old = $target->avatar;
        $disk = Storage::disk('public');
        $proxy = \Mockery::mock($disk)->makePartial();
        $proxy->shouldReceive('put')->twice()->andReturnUsing(function ($path, $bytes) use ($disk): bool {
            $disk->put($path, $bytes);
            return ! str_contains($path, '/thumb/');
        });
        Storage::set('public', $proxy);
        try {
            $this->perform('update', $actor, $target);
            $this->fail('Expected partial write failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Не удалось сохранить обработанное изображение.', $exception->getMessage());
        }
        $this->assertSame($old, $target->fresh()->avatar);
        $this->assertFilesMatchUsers();
    }

    public function test_admin_route_keeps_authorization_and_upload_limit(): void
    {
        [$actor, $target] = $this->fixture();
        $data = ['name' => $target->name, 'email' => $target->email, 'role' => 'admin',
            'avatar' => UploadedFile::fake()->image('large.jpg')->size(2049)];
        $this->actingAs(User::factory()->create(['role' => 'buyer']))
            ->patch(route('admin.users.update', $target), $data)->assertForbidden();
        $this->actingAs($actor)->patch(route('admin.users.update', $target), $data)->assertSessionHasErrors('avatar');
        $this->assertFilesMatchUsers();
    }

    public function test_last_admin_rejection_after_upload_preserves_avatar(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create(['avatar' => 'avatars/medium/old.webp']);
        Storage::disk('public')->put($admin->avatar, 'old');
        Storage::disk('public')->put(ImageService::thumbPath($admin->avatar), 'old thumb');
        $before = $admin->fresh()->getAttributes();
        $stale = clone $admin;
        $stale->role = 'buyer'; // Skip the stale-form precheck; exercise the F07 locked check.
        $this->actingAs($admin);
        $request = Request::create('/admin/users', 'POST', [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'buyer',
        ], files: ['avatar' => UploadedFile::fake()->image('avatar.jpg')]);
        $request->setUserResolver(fn () => $admin);
        $request->setLaravelSession(app('session')->driver());
        app()->instance('request', $request);
        $images = new ImageService();
        $this->partialMock(ImageService::class)->shouldReceive('upload')->once()
            ->andReturnUsing(fn ($file, $dir) => $images->upload($file, $dir));
        try {
            app(UserController::class)->update($request, $stale);
            $this->fail('Expected F07 rejection.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('role', $exception->errors());
        }
        $this->assertSame($before, $admin->fresh()->getAttributes());
        $this->assertFilesMatchUsers();
    }

    private function fixture(): array
    {
        Storage::fake('public');
        $actor = User::factory()->admin()->create();
        $target = User::factory()->admin()->create(['avatar' => 'avatars/medium/old.webp']);
        Storage::disk('public')->put($target->avatar, 'old');
        Storage::disk('public')->put(ImageService::thumbPath($target->avatar), 'old thumb');
        return [$actor, $target];
    }

    private function perform(string $action, User $actor, User $target)
    {
        $this->actingAs($actor);
        $data = ['name' => $target->name, 'email' => $action === 'store' ? 'new-avatar@example.com' : $target->email,
            'role' => 'admin', 'phone' => null];
        if ($action === 'store') {
            $data += ['password' => 'Valid-password-123!', 'password_confirmation' => 'Valid-password-123!'];
        }
        $request = Request::create('/admin/users', 'POST', $data, files: ['avatar' => UploadedFile::fake()->image('avatar.jpg')]);
        $request->setUserResolver(fn () => $actor);
        $request->setLaravelSession(app('session')->driver());
        app()->instance('request', $request);
        return $action === 'store' ? app(UserController::class)->store($request) : app(UserController::class)->update($request, $target);
    }

    private function assertPair(string $path, bool $exists): void
    {
        foreach ([$path, ImageService::thumbPath($path)] as $file) {
            $this->assertSame($exists, Storage::disk('public')->exists($file), $file);
        }
    }

    private function assertFilesMatchUsers(): void
    {
        $expected = User::whereNotNull('avatar')->pluck('avatar')->flatMap(fn ($path) => [$path, ImageService::thumbPath($path)])->all();
        $actual = Storage::disk('public')->allFiles('avatars');
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);
    }
}
