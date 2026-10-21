<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AdminAvatarConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function beginDatabaseTransaction(): void
    {
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    public static function interleavings(): array
    {
        $cases = [];
        foreach ([['admin', 'admin'], ['admin', 'profile'], ['profile', 'admin'], ['admin', 'delete'], ['delete', 'admin']] as [$first, $second]) {
            foreach ([false, true] as $rollbackFirst) {
                foreach ([false, true] as $rollbackSecond) {
                    $cases["$first then $second ".($rollbackFirst ? 'rollback' : 'commit').' '.($rollbackSecond ? 'rollback' : 'commit')]
                        = [$first, $second, $rollbackFirst, $rollbackSecond];
                }
            }
        }
        return $cases;
    }

    #[DataProvider('interleavings')]
    public function test_concurrent_operations_preserve_both_avatar_versions(string $first, string $second, bool $rollbackFirst, bool $rollbackSecond): void
    {
        Storage::fake('public');
        $actor = User::factory()->admin()->create(); // F07 permits deleting the target admin.
        $target = User::factory()->admin()->create([
            'avatar' => 'avatars/medium/old.webp', 'phone' => '+37377999113',
        ]);
        $old = $target->avatar;
        Storage::disk('public')->put($old, 'old');
        Storage::disk('public')->put(ImageService::thumbPath($old), 'old thumb');
        $processes = $inputs = $connections = [];
        try {
            foreach ([$first, $second] as $i => $action) {
                $input = new InputStream();
                $input->write(json_encode([
                    'database' => config('database'), 'key' => config('app.key'),
                    'public_root' => Storage::disk('public')->path(''),
                    'actor_id' => $actor->id, 'target_id' => $target->id, 'action' => $action,
                    'first' => $i === 0, 'rollback' => $i === 0 ? $rollbackFirst : $rollbackSecond,
                ], JSON_THROW_ON_ERROR)."\n");
                $process = new Process([PHP_BINARY, base_path('tests/Support/admin-avatar-worker.php')], base_path(), timeout: 40);
                $process->setInput($input);
                $inputs[] = $input;
                $processes[] = $process;
                $process->start();
                $this->awaitOutput($process, $i === 0 ? 'HELD' : 'CONFLICT');
                preg_match('/CONNECTION:(\d+)/', $process->getOutput(), $match);
                $this->assertNotEmpty($match);
                $connections[] = $match[1];
            }
            $this->assertNotSame($connections[0], $connections[1]);
            $inputs[0]->write("release\n");
            $this->assertSame(0, $processes[0]->wait(), $processes[0]->getErrorOutput());
            $inputs[1]->write("continue\n");
            $this->awaitOutput($processes[1], 'CONTINUING');
            $this->assertSame(0, $processes[1]->wait(), $processes[1]->getErrorOutput());
            $expectedPath = $old;
            foreach ([$first, $second] as $i => $action) {
                $deletedBeforeSecond = $i === 1 && $first === 'delete' && ! $rollbackFirst;
                $rollback = $i === 0 ? $rollbackFirst : $rollbackSecond;
                $this->assertStringContainsString('RESULT:'.($deletedBeforeSecond ? 'deleted' : ($rollback ? 'rollback' : 'ok')), $processes[$i]->getOutput());
                if (! $rollback && ! $deletedBeforeSecond) {
                    preg_match('/AVATAR:([^\r\n]+)/', $processes[$i]->getOutput(), $match);
                    $this->assertNotEmpty($match);
                    $expectedPath = $match[1] === 'NULL' ? null : $match[1];
                }
            }
            $current = User::withTrashed()->findOrFail($target->id);
            $this->assertSame($expectedPath, $current->avatar);
            $this->assertSame($expectedPath === null, $current->trashed());
            $expected = $expectedPath === null ? [] : [$expectedPath, ImageService::thumbPath($expectedPath)];
            $actual = Storage::disk('public')->allFiles('avatars');
            sort($expected);
            sort($actual);
            $this->assertSame($expected, $actual, 'No missing medium/thumb and no orphan versions.');
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
        $deadline = microtime(true) + 10;
        while (! str_contains($process->getOutput(), $marker) && $process->isRunning() && microtime(true) < $deadline) {
            usleep(20000); // Pipe polling only. NOWAIT error 3572 proves contention.
        }
        $this->assertStringContainsString($marker, $process->getOutput(), $process->getErrorOutput());
    }
}
