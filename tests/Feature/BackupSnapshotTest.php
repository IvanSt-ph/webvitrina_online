<?php

namespace Tests\Feature;

use App\Console\Commands\RunBackup;
use App\Models\User;
use App\Support\BackupHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class BackupSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    public function beginDatabaseTransaction(): void
    {
        // Real commits must be visible to a second PDO. Rebuild the guarded test DB
        // for the next test instead of wrapping this one in a rollback transaction.
        $this->beforeApplicationDestroyed(function () {
            RefreshDatabaseState::$migrated = false;
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/backup-snapshot-' . uniqid());
        File::ensureDirectoryExists($this->root . '/public');
        File::ensureDirectoryExists($this->root . '/private/chat-images');
        config()->set([
            'filesystems.disks.public.root' => $this->root . '/public',
            'filesystems.disks.local.root' => $this->root . '/private',
        ]);
        DB::statement('CREATE TABLE aaa_snapshot_parent (id INT PRIMARY KEY, value INT NOT NULL) ENGINE=InnoDB');
        DB::statement('CREATE TABLE aaa_snapshot_child (id INT PRIMARY KEY, parent_id INT NOT NULL, value INT NOT NULL, FOREIGN KEY (parent_id) REFERENCES aaa_snapshot_parent(id)) ENGINE=InnoDB');
        DB::table('aaa_snapshot_parent')->insert(['id' => 1, 'value' => 100]);
        DB::table('aaa_snapshot_child')->insert(['id' => 1, 'parent_id' => 1, 'value' => 100]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_dump_and_manifest_keep_the_initial_snapshot_during_committed_writes(): void
    {
        $writer = DB::connection()->getPdo();
        $writer->exec('SET SESSION innodb_lock_wait_timeout = 2');
        $userCount = User::count();
        $connections = [];
        $command = $this->command(function ($pdo, $table, $after) use ($writer, &$connections) {
            $connections[] = $pdo;
            $this->assertNotSame($writer, $pdo);
            $this->assertTrue($pdo->inTransaction());
            // Commit once before the first exported table, then again between related tables.
            if ($table === 'aaa_snapshot_child') {
                $value = $after ? 300 : 200;
                $writer->beginTransaction();
                $writer->exec('UPDATE aaa_snapshot_parent SET value = ' . $value);
                $writer->exec('UPDATE aaa_snapshot_child SET value = ' . $value);
                $writer->commit();
                if ($after) {
                    User::factory()->create();
                }
            }
        });

        $this->assertSame(0, $this->runBackup($command));
        $this->assertNotEmpty($connections);
        foreach ($connections as $pdo) {
            $this->assertSame($connections[0], $pdo);
        }
        $this->assertFalse($connections[0]->inTransaction());
        $backup = File::directories($this->root . '/backups')[0];
        $sql = gzdecode(file_get_contents($backup . '/database.sql.gz'));
        $this->assertIsString($sql);
        $this->assertStringContainsString("INSERT INTO `aaa_snapshot_child` (`id`, `parent_id`, `value`) VALUES\n(1, 1, 100);", $sql);
        $this->assertStringContainsString("INSERT INTO `aaa_snapshot_parent` (`id`, `value`) VALUES\n(1, 100);", $sql);
        $this->assertSame(300, (int) DB::table('aaa_snapshot_parent')->value('value'));
        $this->assertSame(300, (int) DB::table('aaa_snapshot_child')->value('value'));
        $this->assertSame($userCount + 1, User::count());
        $health = BackupHealth::inspectDirectory($backup);
        $this->assertTrue($health['ok'], implode(' ', $health['issues']));
        $this->assertSame($userCount, $health['manifest']['row_counts']['users']);
        $this->assertSame(2, $health['manifest']['version']);
    }

    public function test_dump_error_rolls_back_snapshot_and_does_not_publish_backup(): void
    {
        $snapshot = null;
        $command = $this->command(function ($pdo, $table, $after) use (&$snapshot) {
            $snapshot = $pdo;
            if ($after) {
                $pdo->query('SELECT * FROM `missing_snapshot_failure_table`');
            }
        });
        $this->assertSame(1, $this->runBackup($command));
        $this->assertNotNull($snapshot);
        $this->assertFalse($snapshot->inTransaction());
        $this->assertSame([], File::directories($this->root . '/backups'));
        $this->assertSame(0, $this->runBackup(new RunBackup));
    }

    public function test_non_innodb_table_is_rejected_without_publishing_backup(): void
    {
        DB::statement('CREATE TABLE snapshot_nontransactional (id INT PRIMARY KEY) ENGINE=MyISAM');
        $output = new BufferedOutput;
        $this->assertSame(1, $this->runBackup(new RunBackup, $output));
        $this->assertStringContainsString('snapshot_nontransactional uses MyISAM', $output->fetch());
        $this->assertSame([], File::directories($this->root . '/backups'));
    }

    public function test_backup_does_not_commit_the_callers_transaction(): void
    {
        DB::beginTransaction();
        try {
            DB::table('aaa_snapshot_parent')->update(['value' => 999]);
            $this->assertSame(0, $this->runBackup(new RunBackup));
            $this->assertTrue(DB::connection()->getPdo()->inTransaction());
        } finally {
            DB::rollBack();
        }
        $this->assertSame(100, (int) DB::table('aaa_snapshot_parent')->value('value'));
    }

    private function command(\Closure $observe): RunBackup
    {
        return new class($observe) extends RunBackup
        {
            public function __construct(private \Closure $observe)
            {
                parent::__construct();
            }

            protected function dumpTable(\PDO $pdo, mixed $handle, string $table): void
            {
                ($this->observe)($pdo, $table, false);
                parent::dumpTable($pdo, $handle, $table);
                ($this->observe)($pdo, $table, true);
            }
        };
    }

    private function runBackup(RunBackup $command, ?BufferedOutput $output = null): int
    {
        $command->setLaravel($this->app);

        return $command->run(new ArrayInput(['--path' => $this->root . '/backups']), $output ?? new BufferedOutput);
    }
}
