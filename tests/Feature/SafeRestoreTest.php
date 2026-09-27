<?php

namespace Tests\Feature;

use App\Services\BackupStorageService;
use App\Services\RestoreDestination;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SafeRestoreTest extends TestCase
{
    private string $root;
    private string $backup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/safe-restore-' . uniqid());
        $this->backup = $this->root . '/backup';
        foreach (['live/public', 'live/private/chat-images', 'source/public', 'source/private/chat-images', 'backup', 'drill'] as $dir) {
            File::ensureDirectoryExists($this->root . '/' . $dir);
        }
        config()->set([
            'filesystems.disks.public.root' => $this->root . '/live/public',
            'filesystems.disks.local.root' => $this->root . '/live/private',
            'backup.lock_path' => $this->root . '/barrier.lock',
        ]);
        file_put_contents($this->root . '/live/public/old.txt', 'old public');
        file_put_contents($this->root . '/live/private/chat-images/old.txt', 'old private');
        file_put_contents($this->root . '/source/public/new.txt', 'new public');
        file_put_contents($this->root . '/source/private/chat-images/new.txt', 'new private');
        $storage = app(BackupStorageService::class);
        $public = $storage->archiveDirectory($this->root . '/source/public', 'public', $this->backup . '/storage-public.tar', $this->backup . '/storage-public.tar.gz');
        $private = $storage->archiveDirectory($this->root . '/source/private/chat-images', 'private/chat-images', $this->backup . '/storage-private-chat-images.tar', $this->backup . '/storage-private-chat-images.tar.gz');
        file_put_contents($this->backup . '/database.sql.gz', gzencode('SQL fixture'));
        file_put_contents($this->backup . '/manifest.json', json_encode(['version' => 2, 'storage' => [
            'public' => ['archive' => 'storage-public.tar.gz', 'root' => 'public', ...$public],
            'private_chat_images' => ['archive' => 'storage-private-chat-images.tar.gz', 'root' => 'private/chat-images', ...$private],
        ]]));
        $files = ['database.sql.gz', 'storage-public.tar.gz', 'storage-private-chat-images.tar.gz', 'manifest.json'];
        file_put_contents($this->backup . '/SHA256SUMS', implode("\n", array_map(fn ($f) => hash_file('sha256', $this->backup . '/' . $f) . '  ' . $f, $files)) . "\n");
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_drill_restores_both_roots_without_changing_live_files(): void
    {
        $this->artisan('backup:restore-files', ['backup' => $this->backup, '--drill' => $this->root . '/drill'])->assertSuccessful();
        $this->assertSame('new public', file_get_contents($this->root . '/drill/public/new.txt'));
        $this->assertSame('new private', file_get_contents($this->root . '/drill/private/chat-images/new.txt'));
        $this->assertOldFiles();
    }

    public function test_production_restore_requires_confirmation_and_rejects_mixed_modes(): void
    {
        $this->artisan('backup:restore-files', ['backup' => $this->backup])->assertFailed();
        $this->artisan('backup:restore-files', ['backup' => $this->backup, '--drill' => $this->root . '/drill', '--force' => true])->assertFailed();
        $this->assertOldFiles();
        $this->artisan('backup:restore-files', ['backup' => $this->backup, '--force' => true])->assertSuccessful();
        $this->assertFileExists($this->root . '/live/public/new.txt');
        $this->assertFileExists($this->root . '/live/private/chat-images/new.txt');
    }

    public function test_unsafe_drill_destinations_fail_closed(): void
    {
        foreach (['', '/', dirname(base_path()), base_path(), $this->root . '/live', $this->root . '/live/public',
            $this->root . '/live/private', $this->root . '/live/private/chat-images', $this->root,
            $this->backup, $this->root . '/drill/../live', 'relative/path', $this->root . '/absent'] as $destination) {
            try {
                app(RestoreDestination::class)->isolated($destination, $this->backup);
                $this->fail('Accepted unsafe path: ' . $destination);
            } catch (\RuntimeException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }
        $this->assertOldFiles();
    }

    public function test_identical_roots_are_rejected(): void
    {
        $this->expectException(\RuntimeException::class);
        app(BackupStorageService::class)->restore($this->backup, $this->root . '/drill', $this->root . '/drill');
    }

    public function test_symlink_alias_is_rejected(): void
    {
        $link = $this->root . '/alias';
        if (! @symlink($this->root . '/live/public', $link)) {
            if (PHP_OS_FAMILY !== 'Windows') {
                $this->fail('Cannot create symlink fixture.');
            }
            $process = new \Symfony\Component\Process\Process([
                'powershell.exe', '-NoProfile', '-NonInteractive', '-Command',
                'New-Item -ItemType Junction -Path $env:RESTORE_TEST_LINK -Target $env:RESTORE_TEST_TARGET | Out-Null',
            ], env: ['RESTORE_TEST_LINK' => $link, 'RESTORE_TEST_TARGET' => $this->root . '/live/public']);
            $process->mustRun();
        }
        try {
            $this->expectException(\RuntimeException::class);
            app(RestoreDestination::class)->isolated($link, $this->backup);
        } finally {
            PHP_OS_FAMILY === 'Windows' ? rmdir($link) : unlink($link);
        }
    }

    public static function failures(): array
    {
        return [['extract'], ['public-activate'], ['private-activate'], ['permissions'], ['public-rollback'], ['private-rollback'], ['cleanup']];
    }

    #[DataProvider('failures')]
    public function test_failures_preserve_old_data_or_recoverable_artifacts(string $failure): void
    {
        $storage = new class($failure) extends BackupStorageService {
            public function __construct(private string $failure) {}
            protected function extractArchive(string $archivePath, string $destination, string $requiredRoot): void
            {
                if ($this->failure === 'extract' && $requiredRoot === 'private/chat-images') {
                    throw new \RuntimeException('injected extraction');
                }
                parent::extractArchive($archivePath, $destination, $requiredRoot);
            }
            protected function moveDirectory(string $source, string $target): void
            {
                $source = str_replace('\\', '/', $source);
                $target = str_replace('\\', '/', $target);
                if (($this->failure === 'public-activate' && str_ends_with($target, '/live/public'))
                    || ($this->failure === 'private-activate' && str_ends_with($target, '/live/private/chat-images'))
                    || ($this->failure === 'public-rollback' && str_ends_with($source, '/.previous-public'))
                    || ($this->failure === 'private-rollback' && str_ends_with($source, '/.previous-chat-images'))) {
                    // Activation fails only for staged new files, not the old rollback copy.
                    if (str_contains($this->failure, 'rollback') || ! str_contains($source, '/.previous-')) {
                        throw new \RuntimeException('injected rename ' . $source);
                    }
                }
                parent::moveDirectory($source, $target);
            }
            protected function applyPrivatePermissions(string $directory): void
            {
                if (in_array($this->failure, ['permissions', 'public-rollback', 'private-rollback'], true)) {
                    throw new \RuntimeException('injected permissions');
                }
                parent::applyPrivatePermissions($directory);
            }
            protected function removeDirectory(string $directory): void
            {
                if ($this->failure === 'cleanup') {
                    throw new \RuntimeException('injected cleanup');
                }
                parent::removeDirectory($directory);
            }
        };
        try {
            $storage->restore($this->backup, $this->root . '/live/public', $this->root . '/live/private');
            $this->fail('Expected restore failure');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('injected', $exception->getMessage());
            $stages = glob($this->root . '/live/.backup-restore-*');
            if (str_contains($failure, 'rollback')) {
                $this->assertStringContainsString('ROLLBACK FAILED', $exception->getMessage());
                $this->assertStringContainsString('injected permissions', $exception->getPrevious()->getMessage());
                $this->assertCount(1, $stages);
                $suffix = $failure === 'public-rollback' ? '/.previous-public/old.txt' : '/.previous-chat-images/old.txt';
                $this->assertFileExists($stages[0] . $suffix);
            } elseif ($failure === 'cleanup') {
                $this->assertStringContainsString('installed successfully', $exception->getMessage());
                $this->assertFileExists($this->root . '/live/public/new.txt');
                $this->assertFileExists($this->root . '/live/private/chat-images/new.txt');
                $this->assertCount(1, $stages);
                $this->assertFileExists($stages[0] . '/.previous-public/old.txt');
                $this->assertFileExists($stages[0] . '/.previous-chat-images/old.txt');
            } else {
                $this->assertOldFiles();
                $this->assertSame([], $stages);
            }
        }
    }

    private function assertOldFiles(): void
    {
        $this->assertSame('old public', file_get_contents($this->root . '/live/public/old.txt'));
        $this->assertSame('old private', file_get_contents($this->root . '/live/private/chat-images/old.txt'));
    }
}
