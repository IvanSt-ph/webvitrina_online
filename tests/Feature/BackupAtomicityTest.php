<?php

namespace Tests\Feature;

use App\Services\BackupStorageService;
use App\Support\BackupHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use Symfony\Component\Process\Process;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Console\Commands\RunBackup;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class BackupAtomicityTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    public function beginDatabaseTransaction(): void
    {
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/backup-atomicity-' . uniqid());
        File::ensureDirectoryExists($this->root . '/public');
        File::ensureDirectoryExists($this->root . '/private/chat-images');
        config()->set([
            'filesystems.disks.public.root' => $this->root . '/public',
            'filesystems.disks.local.root' => $this->root . '/private',
            'backup.lock_path' => $this->root . '/barrier.lock',
        ]);
        DB::statement('CREATE TABLE backup_file_reference (id INT PRIMARY KEY, path VARCHAR(255)) ENGINE=InnoDB');
        DB::table('backup_file_reference')->insert(['id' => 1, 'path' => 'old.txt']);
        file_put_contents($this->root . '/public/old.txt', 'old image');
        file_put_contents($this->root . '/private/chat-images/old.txt', 'old image');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_concurrent_request_cannot_remove_snapshot_files_before_both_archives_complete(): void
    {
        $worker = new Process([PHP_BINARY, base_path('tests/Support/backup-mutation-worker.php')], base_path(), input: json_encode([
            'database' => config('database'), 'lock' => config('backup.lock_path'), 'root' => $this->root,
        ]), timeout: 30);
        $this->app->instance(BackupStorageService::class, new class($worker, $this) extends BackupStorageService
        {
            public function __construct(private Process $worker, private BackupAtomicityTest $test) {}
            public function archiveDirectory(string $source, string $archiveRoot, string $tarPath, string $tarGzPath): array
            {
                if ($archiveRoot === 'public') {
                    $this->worker->start();
                    $this->test->assertTrue($this->worker->waitUntil(fn ($type, $out) => str_contains($out, 'READY')));
                    usleep(200000);
                }
                $this->test->assertStringNotContainsString('MUTATED', $this->worker->getOutput());
                $this->test->assertSame('old.txt', DB::table('backup_file_reference')->value('path'));
                return parent::archiveDirectory($source, $archiveRoot, $tarPath, $tarGzPath);
            }
        });
        try {
            $output = new BufferedOutput;
            $exit = \Illuminate\Support\Facades\Artisan::call('backup:run', ['--path' => $this->root . '/backups'], $output);
            $this->assertSame(0, $exit, $output->fetch() . $worker->getErrorOutput());
            $this->assertSame(0, $worker->wait(), $worker->getErrorOutput());
            $this->assertStringContainsString('MUTATED', $worker->getOutput());
        } finally {
            $worker->stop();
        }
        $backup = File::directories($this->root . '/backups')[0];
        $this->assertTrue(BackupHealth::inspectDirectory($backup)['ok']);
        $this->assertStringContainsString("(1, 'old.txt')", gzdecode(file_get_contents($backup . '/database.sql.gz')));
        $archive = new \PharData($backup . '/storage-public.tar.gz');
        $this->assertTrue(isset($archive['public/old.txt']));
        $this->assertFalse(isset($archive['public/new.txt']));
        $private = new \PharData($backup . '/storage-private-chat-images.tar.gz');
        $this->assertTrue(isset($private['private/chat-images/old.txt']));
        $this->assertSame('new.txt', DB::table('backup_file_reference')->value('path'));
        $this->assertFileDoesNotExist($this->root . '/public/old.txt');
        $this->assertFileDoesNotExist($this->root . '/private/chat-images/old.txt');
    }

    public function test_direct_account_deletion_holds_barrier_through_outer_commit_cleanup(): void
    {
        $user = \App\Models\User::factory()->create(['avatar' => 'avatar.webp']);
        \Illuminate\Support\Facades\Storage::disk('public')->put('avatar.webp', 'avatar');
        $handle = fopen(config('backup.lock_path'), 'c+b');
        try {
            DB::beginTransaction();
            $user->delete();
            $this->assertFalse(flock($handle, LOCK_EX | LOCK_NB));
            $this->assertFileExists($this->root . '/public/avatar.webp');
            $disk = \Illuminate\Support\Facades\Storage::disk('local');
            $proxy = \Mockery::mock($disk)->makePartial();
            $proxy->shouldReceive('delete')->once()->andReturnUsing(function () use ($handle) {
                $this->assertFalse(flock($handle, LOCK_EX | LOCK_NB));
                $this->assertFileDoesNotExist($this->root . '/public/avatar.webp');
                throw new \RuntimeException('injected private account cleanup failure');
            });
            \Illuminate\Support\Facades\Storage::set('local', $proxy);
            DB::commit();
            $this->assertTrue(flock($handle, LOCK_EX | LOCK_NB));
        } finally {
            fclose($handle);
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
    }

    public static function backupFailures(): array
    {
        return [['db'], ['public'], ['private/chat-images'], ['manifest.json'], ['SHA256SUMS']];
    }

    #[DataProvider('backupFailures')]
    public function test_failures_release_barrier_and_do_not_publish(string $failure): void
    {
        $storage = new class($failure) extends BackupStorageService {
            public function __construct(private string $failure) {}
            public function archiveDirectory(string $source, string $archiveRoot, string $tarPath, string $tarGzPath): array
            {
                if ($archiveRoot === $this->failure) {
                    throw new \RuntimeException('injected archive failure');
                }
                return parent::archiveDirectory($source, $archiveRoot, $tarPath, $tarGzPath);
            }
        };
        $this->app->instance(BackupStorageService::class, $storage);
        $command = new class($failure) extends RunBackup {
            public function __construct(private string $failure) { parent::__construct(); }
            protected function dumpTable(\PDO $pdo, mixed $handle, string $table): void
            {
                if ($this->failure === 'db') {
                    throw new \RuntimeException('injected DB failure');
                }
                parent::dumpTable($pdo, $handle, $table);
            }
            protected function writeFile(string $path, string $contents): void
            {
                if (basename($path) === $this->failure) {
                    throw new \RuntimeException('injected manifest/hash failure');
                }
                parent::writeFile($path, $contents);
            }
        };
        $command->setLaravel($this->app);
        foreach ([false, true] as $down) {
            $maintenance = \Mockery::mock(\Illuminate\Contracts\Foundation\MaintenanceMode::class);
            $maintenance->shouldReceive('active')->andReturn($down);
            $maintenance->shouldNotReceive('activate');
            $maintenance->shouldNotReceive('deactivate');
            $this->app->instance(\Illuminate\Contracts\Foundation\MaintenanceMode::class, $maintenance);
            $this->assertSame(1, $command->run(new ArrayInput(['--path' => $this->root . '/backups']), new BufferedOutput));
            $this->assertSame([], File::directories($this->root . '/backups'));
            // Independent handle proves release, rather than singleton reentrancy.
            $handle = fopen(config('backup.lock_path'), 'c+b');
            try {
                $this->assertTrue(flock($handle, LOCK_EX | LOCK_NB));
            } finally {
                fclose($handle);
            }
            $this->assertSame($down, app()->isDownForMaintenance());
        }
    }
}
