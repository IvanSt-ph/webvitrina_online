<?php

namespace Tests\Feature;

use App\Console\Commands\RunBackup;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;
use Tests\Support\TestFileSafety;
use Tests\TestCase;

/** Filesystem-only: never runs backup:run, restore, migrations or SQL. */
class BackupRetentionTest extends TestCase
{
    private string $root;
    private string $backups;
    private array $links = [];
    private RunBackup $command;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/backup-retention-' . bin2hex(random_bytes(12)));
        TestFileSafety::assertDirectory($this->root, false);
        $this->backups = $this->root . '/backups';
        File::ensureDirectoryExists($this->backups);
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-23 12:00:00'));
        $this->output = new BufferedOutput();
        $this->command = new RunBackup();
        $this->command->setOutput(new OutputStyle(new ArrayInput([]), $this->output));
    }

    protected function tearDown(): void
    {
        try {
            // Remove known links themselves before recursive fixture cleanup.
            foreach (array_reverse($this->links) as [$link, $directory]) {
                if (is_link($link) || file_exists($link)) {
                    $removed = PHP_OS_FAMILY === 'Windows' && $directory ? rmdir($link) : unlink($link);
                    if (! $removed) {
                        throw new \RuntimeException('Cannot unlink retention test fixture; refusing recursive cleanup.');
                    }
                }
            }
            if (isset($this->root) && is_dir($this->root)) {
                TestFileSafety::assertDirectory($this->root);
                File::deleteDirectory($this->root);
            }
        } finally {
            parent::tearDown();
        }
    }

    private function prune(?string $root = null, int $days = 14): void
    {
        (new \ReflectionMethod(RunBackup::class, 'removeOldBackups'))->invoke($this->command, $root ?? $this->backups, $days);
    }

    private function backup(string $name, int $version = 3): string
    {
        $path = $this->backups . '/' . $name;
        File::ensureDirectoryExists($path);
        $storage = [
            'public' => ['root' => 'public', 'archive' => 'storage-public.tar.gz'],
            'private_chat_images' => ['root' => 'private/chat-images', 'archive' => 'storage-private-chat-images.tar.gz'],
        ];
        if ($version >= 3) {
            $storage['private_review_images'] = ['root' => 'private/review-images', 'archive' => 'storage-private-review-images.tar.gz'];
        }
        file_put_contents($path . '/database.sql.gz', gzencode('-- isolated retention fixture'));
        foreach ($storage as $entry) {
            // Retention never opens archives; content validity is restore's job.
            file_put_contents($path . '/' . $entry['archive'], gzencode('isolated archive fixture'));
        }
        file_put_contents($path . '/manifest.json', json_encode([
            'version' => $version, 'created_at' => '2026-09-01T00:00:00+00:00',
            'database' => 'fixture', 'tables_total' => 0, 'row_counts' => [], 'storage' => $storage,
        ], JSON_THROW_ON_ERROR));
        $this->checksums($path);
        touch($path, now()->subDays(30)->getTimestamp());
        return $path;
    }

    private function checksums(string $path): void
    {
        $lines = [];
        foreach (array_diff(scandir($path), ['.', '..', 'SHA256SUMS']) as $file) {
            $lines[] = hash_file('sha256', $path . '/' . $file) . '  ' . $file;
        }
        file_put_contents($path . '/SHA256SUMS', implode("\n", $lines) . "\n");
    }

    public function test_only_expired_complete_v2_and_v3_backups_are_removed(): void
    {
        $oldV2 = $this->backup('20260901-000000', 2);
        $oldV3 = $this->backup('20260902-000000');
        $recent = $this->backup('20260903-000000');
        touch($recent, now()->subDays(2)->getTimestamp());
        $boundary = $this->backup('20260904-000000');
        touch($boundary, now()->subDays(14)->getTimestamp());
        $this->prune();
        $this->assertDirectoryDoesNotExist($oldV2);
        $this->assertDirectoryDoesNotExist($oldV3);
        $this->assertFileExists($recent . '/manifest.json');
        $this->assertFileExists($boundary . '/manifest.json');
        $this->assertSame('', $this->output->fetch());
    }

    public function test_foreign_directories_files_temporary_and_invalid_dates_are_untouched(): void
    {
        foreach (['unrelated', '20260230-000000', '20260901-250000', '20260901-000000.tmp'] as $name) {
            $path = $this->backup($name);
            file_put_contents($path . '/precious.txt', 'keep');
            touch($path, now()->subDays(30)->getTimestamp());
        }
        file_put_contents($this->backups . '/20260905-000000', 'not a directory');
        touch($this->backups . '/20260905-000000', now()->subDays(30)->getTimestamp());
        $this->prune();
        foreach (['unrelated', '20260230-000000', '20260901-250000', '20260901-000000.tmp'] as $name) {
            $this->assertSame('keep', file_get_contents($this->backups . '/' . $name . '/precious.txt'));
        }
        $this->assertSame('not a directory', file_get_contents($this->backups . '/20260905-000000'));
    }

    public static function invalidBackups(): array
    {
        return array_map(fn ($case) => [$case], [
            'extra file', 'nested directory', 'missing file', 'malformed manifest', 'unknown version',
            'wrong storage root', 'bad hash', 'missing checksum', 'duplicate checksum', 'checksum traversal',
        ]);
    }

    #[DataProvider('invalidBackups')]
    public function test_unrecognized_or_damaged_backup_is_preserved_and_reported(string $case): void
    {
        $path = $this->backup('20260901-000000');
        switch ($case) {
            case 'extra file': file_put_contents($path . '/keep.txt', 'keep'); break;
            case 'nested directory': mkdir($path . '/nested'); file_put_contents($path . '/nested/keep.txt', 'keep'); break;
            case 'missing file': unlink($path . '/storage-public.tar.gz'); break;
            case 'malformed manifest': file_put_contents($path . '/manifest.json', '{broken'); break;
            case 'unknown version':
            case 'wrong storage root':
                $manifest = json_decode(file_get_contents($path . '/manifest.json'), true);
                if ($case === 'unknown version') { $manifest['version'] = 99; }
                else { $manifest['storage']['public']['root'] = '../public'; }
                file_put_contents($path . '/manifest.json', json_encode($manifest));
                $this->checksums($path);
                break;
            case 'bad hash': file_put_contents($path . '/database.sql.gz', 'changed'); break;
            case 'missing checksum': file_put_contents($path . '/SHA256SUMS', ''); break;
            case 'duplicate checksum':
                file_put_contents($path . '/SHA256SUMS', file_get_contents($path . '/SHA256SUMS'), FILE_APPEND);
                break;
            case 'checksum traversal':
                file_put_contents($path . '/SHA256SUMS', str_repeat('a', 64) . "  ../outside\n");
                break;
        }
        touch($path, now()->subDays(30)->getTimestamp());
        $before = File::allFiles($path);
        $snapshot = [];
        foreach ($before as $file) { $snapshot[$file->getPathname()] = hash_file('sha256', $file->getPathname()); }
        $this->prune();
        $this->assertStringContainsString('Backup retention skipped', $this->output->fetch());
        $this->assertDirectoryExists($path);
        foreach ($snapshot as $file => $hash) {
            $this->assertFileExists($file);
            $this->assertSame($hash, hash_file('sha256', $file));
        }
    }

    public function test_invalid_roots_fail_without_touching_backups(): void
    {
        $path = $this->backup('20260901-000000');
        file_put_contents($this->root . '/file', 'keep');
        foreach (['', '.', '/', 'relative/backups', $this->root . '/missing', $this->root . '/file',
            $this->backups . '/../backups', base_path(), "bad\0path"] as $root) {
            $observed = null;
            try { $this->prune($root); } catch (\RuntimeException $exception) { $observed = $exception; }
            $this->assertInstanceOf(\RuntimeException::class, $observed, 'Unsafe root accepted');
            $this->assertFileExists($path . '/manifest.json');
        }
    }

    public function test_non_positive_retention_does_not_delete_anything(): void
    {
        $path = $this->backup('20260901-000000');
        $this->prune(days: 0);
        $this->prune(days: -1);
        $this->assertFileExists($path . '/manifest.json');
    }

    private function linkDirectory(string $target, string $link): void
    {
        TestFileSafety::assertDirectory($target);
        if (! @symlink($target, $link)) {
            if (PHP_OS_FAMILY !== 'Windows') { $this->fail('Cannot create isolated symlink fixture.'); }
            (new Process(['powershell.exe', '-NoProfile', '-NonInteractive', '-Command',
                'New-Item -ItemType Junction -Path $env:RETENTION_TEST_LINK -Target $env:RETENTION_TEST_TARGET | Out-Null',
            ], env: ['RETENTION_TEST_LINK' => $link, 'RETENTION_TEST_TARGET' => $target]))->mustRun();
        }
        $this->links[] = [$link, true];
    }

    public function test_linked_backup_and_linked_contents_never_touch_external_target(): void
    {
        $outside = $this->root . '/outside';
        mkdir($outside);
        file_put_contents($outside . '/keep.txt', 'precious');
        $this->linkDirectory($outside, $this->backups . '/20260901-000000');
        $path = $this->backup('20260902-000000');
        unlink($path . '/storage-public.tar.gz');
        $this->linkDirectory($outside, $path . '/storage-public.tar.gz');
        touch($path, now()->subDays(30)->getTimestamp());
        $this->prune();
        $this->assertSame('precious', file_get_contents($outside . '/keep.txt'));
        $this->assertFileExists($path . '/database.sql.gz');
        $this->assertStringContainsString('Backup retention skipped', $this->output->fetch());
    }

    public function test_linked_root_or_ancestor_is_rejected(): void
    {
        $path = $this->backup('20260901-000000');
        $alias = $this->root . '/alias';
        $this->linkDirectory($this->backups, $alias);
        $observed = null;
        try { $this->prune($alias); } catch (\RuntimeException $exception) { $observed = $exception; }
        $this->assertInstanceOf(\RuntimeException::class, $observed);
        $this->assertFileExists($path . '/manifest.json');
        mkdir($this->backups . '/child');
        $observed = null;
        try { $this->prune($alias . '/child'); } catch (\RuntimeException $exception) { $observed = $exception; }
        $this->assertInstanceOf(\RuntimeException::class, $observed);
    }

    public function test_failed_backup_cleanup_is_flat_and_refuses_unknown_contents(): void
    {
        $path = $this->backups . '/20260901-000000.tmp';
        mkdir($path);
        file_put_contents($path . '/database.sql', 'partial dump');
        mkdir($path . '/unknown');
        file_put_contents($path . '/unknown/keep.txt', 'keep');
        $method = new \ReflectionMethod(RunBackup::class, 'removeDirectory');
        $observed = null;
        try { $method->invoke($this->command, $path); } catch (\RuntimeException $exception) { $observed = $exception; }
        $this->assertInstanceOf(\RuntimeException::class, $observed);
        $this->assertSame('partial dump', file_get_contents($path . '/database.sql'));
        $this->assertSame('keep', file_get_contents($path . '/unknown/keep.txt'));
        unlink($path . '/unknown/keep.txt');
        rmdir($path . '/unknown');
        $method->invoke($this->command, $path);
        $this->assertDirectoryDoesNotExist($path);
    }

    public function test_disappeared_file_aborts_before_deleting_other_files(): void
    {
        $path = $this->backup('20260901-000000');
        $files = array_values(array_diff(scandir($path), ['.', '..']));
        unlink($path . '/storage-public.tar.gz');
        $observed = null;
        try {
            (new \ReflectionMethod(RunBackup::class, 'removeFlatDirectory'))
                ->invoke($this->command, $this->backups, $path, $files);
        } catch (\RuntimeException $exception) {
            $observed = $exception;
        }
        $this->assertInstanceOf(\RuntimeException::class, $observed);
        foreach (array_diff($files, ['storage-public.tar.gz']) as $file) {
            $this->assertFileExists($path . '/' . $file);
        }
    }

    public function test_flat_deletion_rejects_a_sibling_outside_backup_root(): void
    {
        $outside = $this->root . '/outside';
        mkdir($outside);
        file_put_contents($outside . '/database.sql.gz', 'keep');
        $observed = null;
        try {
            (new \ReflectionMethod(RunBackup::class, 'removeFlatDirectory'))
                ->invoke($this->command, $this->backups, $outside, ['database.sql.gz']);
        } catch (\RuntimeException $exception) {
            $observed = $exception;
        }
        $this->assertInstanceOf(\RuntimeException::class, $observed);
        $this->assertSame('keep', file_get_contents($outside . '/database.sql.gz'));
    }
}
