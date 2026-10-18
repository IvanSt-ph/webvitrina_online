<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ProfileDeletionConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        // Independent workers need committed fixtures in the guarded test DB.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public static function interleavings(): array
    {
        $cases = [];
        foreach (['personal', 'phone', 'avatar', 'service'] as $write) {
            $cases['delete then '.$write] = ['delete', $write];
            $cases[$write.' then delete'] = [$write, 'delete'];
        }

        return $cases;
    }

    public function test_active_profile_avatar_replacement_commits_and_removes_old_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/old.webp', 'old');
        $user = User::factory()->create(['avatar' => 'avatars/old.webp']);

        $this->actingAs($user)->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->patch('/profile', ['avatar' => UploadedFile::fake()->image('avatar.jpg')])
            ->assertOk()->assertJson(['success' => true]);

        $new = $user->fresh()->avatar;
        $this->assertNotSame('avatars/old.webp', $new);
        Storage::disk('public')->assertExists($new);
        Storage::disk('public')->assertMissing('avatars/old.webp');
    }

    #[DataProvider('interleavings')]
    public function test_deletion_serializes_with_profile_writes(string $first, string $second): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $processes = $inputs = [];
        try {
            foreach ([$first, $second] as $i => $action) {
                $input = new InputStream();
                $input->write(json_encode([
                    'database' => config('database'), 'key' => config('app.key'),
                    'public_root' => Storage::disk('public')->path(''),
                    'user_id' => $user->id, 'action' => $action, 'pause' => $i === 0,
                ], JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/profile-deletion-worker.php')], base_path(), timeout: 30);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                $this->awaitOutput($process, $i === 0 ? 'LOCKED' : 'USER_LOCK_CONFLICT');
            }
            $connections = [];
            foreach ($processes as $process) {
                preg_match('/CONNECTION:(\d+)/', $process->getOutput(), $match);
                $this->assertNotEmpty($match);
                $connections[] = $match[1];
                $this->assertTrue($process->isRunning());
            }
            $this->assertNotSame($connections[0], $connections[1]);
            // The second session has received MySQL's NOWAIT lock conflict
            // for the actual User locking query, while the first holds its lock.
            $this->assertStringNotContainsString('RESULT:', $processes[0]->getOutput());
            $this->assertStringNotContainsString('RESULT:', $processes[1]->getOutput());
            $inputs[0]->write("release\n");
            $this->assertSame(0, $processes[0]->wait(), $processes[0]->getErrorOutput());
            $this->assertStringContainsString('RESULT:ok', $processes[0]->getOutput());
            // Resume the original query only after the first transaction commits.
            // The second worker keeps its cached User and transaction throughout.
            $inputs[1]->write("continue\n");
            foreach ($processes as $i => $process) {
                $this->assertSame(0, $process->wait(), $process->getErrorOutput());
                $expected = $i === 1 && $first === 'delete' ? 'deleted' : 'ok';
                $this->assertStringContainsString('RESULT:'.$expected, $process->getOutput());
            }
            $deleted = User::withTrashed()->findOrFail($user->id);
            $this->assertTrue($deleted->trashed());
            $this->assertSame('Удалённый аккаунт', $deleted->name);
            foreach (['email', 'phone', 'avatar', 'phone_verified_at', 'phone_verification_code'] as $field) {
                $this->assertNull($deleted->$field, $field);
            }
            $this->assertSame([], Storage::disk('public')->allFiles('avatars'));
        } finally {
            foreach ($inputs as $input) {
                $input->close();
            }
            foreach ($processes as $process) {
                $process->stop(0);
            }
        }
    }

    private function awaitOutput(Process $process, string $marker): void
    {
        $deadline = microtime(true) + 8;
        while (! str_contains($process->getOutput(), $marker) && $process->isRunning() && microtime(true) < $deadline) {
            // Poll protocol output; elapsed time is never proof of contention.
            usleep(20000);
        }
        $this->assertStringContainsString($marker, $process->getOutput(), $process->getErrorOutput());
    }
}
