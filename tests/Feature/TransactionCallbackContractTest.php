<?php

namespace Tests\Feature;

use App\Database\SafeDatabaseTransactionsManager;
use App\Models\User;
use App\Services\AdminMembershipService;
use App\Services\BackupWriteBarrier;
use App\Services\ImageService;
use App\Services\ProfileAvatarOperation;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\TestDatabaseSafety;
use Tests\TestCase;

/** Real MySQL commits using the production provider, without a test transaction manager. */
class TransactionCallbackContractTest extends TestCase
{
    use RefreshDatabase;

    private array $cleanupActions = [];
    private array $ownedConnections = [];

    protected function tearDown(): void
    {
        $failures = [];
        try {
            // Resolve unfinished PDO transactions before releasing their resources.
            // Every action is attempted even if a safety check or cleanup fails.
            usort($this->cleanupActions, fn ($a, $b) => $a[0] <=> $b[0]);
            foreach ($this->cleanupActions as [, $cleanup]) {
                try {
                    $cleanup();
                } catch (\Throwable $exception) {
                    $failures[] = $exception;
                }
            }
        } finally {
            $this->cleanupActions = [];
            $this->ownedConnections = []; // Drop all captured original PDO references.
            try {
                parent::tearDown();
            } catch (\Throwable $exception) {
                $failures[] = $exception;
            }
        }
        if ($failures !== []) {
            throw new \RuntimeException('F10 test cleanup failed: '.implode('; ', array_map(
                fn ($exception) => $exception::class.': '.$exception->getMessage(), $failures,
            )), 0, $failures[0]);
        }
    }

    public function beginDatabaseTransaction(): void
    {
        // Use real root commits and the production manager, not simulated callbacks.
        $this->beforeApplicationDestroyed(fn () => RefreshDatabaseState::$migrated = false);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('12.71.0', Application::VERSION, 'Re-audit these contracts on framework upgrades.');
        $this->assertSame(SafeDatabaseTransactionsManager::class, app('db.transactions')::class);
        $this->protectConnectionCleanup(DB::connection());
        $this->assertProductionManager(DB::connection());
        $this->assertSame(0, DB::transactionLevel());
        $this->assertFalse(DB::connection()->getRawPdo()->inTransaction());
        $this->cleanupActions[] = [50, function (): void {
            // Failure-path teardown may have used raw PDO rollback. Discard its
            // records only AFTER observations; never replay callbacks during cleanup.
            $manager = new SafeDatabaseTransactionsManager();
            foreach ($this->ownedConnections as $owned) {
                $owned['connection']->setTransactionManager($manager);
            }
            $this->app->instance('db.transactions', $manager);
            $this->assertCount(0, $manager->getPendingTransactions());
            $this->assertCount(0, $manager->getCommittedTransactions());
        }];
    }

    public static function resourceOutcomes(): array
    {
        return [
            'commit control' => [true, false],
            'commit callback failure' => [true, true],
            'rollback control' => [false, false],
            'rollback callback failure' => [false, true],
            'nested commit callback failure' => [true, true, true],
            'savepoint rollback callback failure' => [false, true, true],
        ];
    }

    #[DataProvider('resourceOutcomes')]
    public function test_deferred_resources_are_released_despite_early_callback_failure(bool $commit, bool $throw, bool $savepoint = false): void
    {
        $connection = DB::connection();
        $probe = $this->probeConnection();
        $this->assertNotSame($connection->getRawPdo(), $probe->getRawPdo());
        $this->assertNotSame(
            (int) $connection->selectOne('SELECT CONNECTION_ID() AS id')->id,
            (int) $probe->selectOne('SELECT CONNECTION_ID() AS id')->id,
        );
        $name = 'wv:admins:'.substr(hash('sha256', $connection->getDatabaseName()), 0, 48);
        $this->cleanupActions[] = [5, function () use ($connection, $name): void {
            $pdo = $this->approvedPdo($connection);
            $statement = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            try {
                $statement->execute([$name]); // One acquisition by this test; no RELEASE_ALL_LOCKS.
                $result = $statement->fetchColumn();
                $this->assertTrue(in_array($result, [null, 0, 1, '0', '1'], true));
            } finally {
                $statement->closeCursor();
            }
        }];
        // Dedicated instance and inode: do not leave the singleton or real backup lock contaminated.
        $lockPath = tempnam(sys_get_temp_dir(), 'wv-f10-');
        $this->assertNotFalse($lockPath);
        $this->cleanupActions[] = [30, function () use ($lockPath): void {
            if (file_exists($lockPath)) {
                $this->assertTrue(unlink($lockPath));
            }
        }];
        $previousLockPath = config('backup.lock_path');
        $this->cleanupActions[] = [35, fn () => config(['backup.lock_path' => $previousLockPath])];
        config(['backup.lock_path' => $lockPath]);
        $this->assertSame($lockPath, config('backup.lock_path'));
        $barrier = new BackupWriteBarrier();
        $this->cleanupActions[] = [10, fn () => $barrier->release()];
        $handle = fopen($lockPath, 'c+b');
        $this->assertNotFalse($handle);
        $this->cleanupActions[] = [20, function () use ($handle): void {
            if (is_resource($handle)) {
                $this->assertTrue(fclose($handle));
            }
        }];
        $this->cleanupActions[] = [15, fn () => $this->assertLockAvailable($probe, $name, true)];
        $this->cleanupActions[] = [16, fn () => $this->assertBarrierAvailable($handle, true)];
        $trace = [];
        $this->assertBarrierAvailable($handle, true);
        // BackupWriteBarrier::acquire reads this same key and opens the existing inode.
        $barrier->run(fn () => $this->assertBarrierAvailable($handle, false));
        $this->assertBarrierAvailable($handle, true);
        $connection->beginTransaction();
        if ($savepoint) {
            $connection->beginTransaction();
        }
        $callback = function () use (&$trace, $throw): void {
            $trace[] = 'first';
            if ($throw) {
                throw new \RuntimeException('early callback');
            }
        };
        if ($commit) {
            $connection->afterCommit($callback);
        } else {
            $connection->afterRollBack($callback);
        }
        app(AdminMembershipService::class)->transaction(fn () => null);
        $barrier->transaction(fn () => null);
        $last = function () use (&$trace): void { $trace[] = 'last'; };
        if ($commit) {
            $connection->afterCommit($last);
        } else {
            $connection->afterRollBack($last);
        }
        $this->assertSame($savepoint ? 2 : 1, $connection->transactionLevel());
        $this->assertLockAvailable($probe, $name, false);
        $this->assertBarrierAvailable($handle, false);

        $caught = null;
        try {
            if ($savepoint) {
                $commit ? $connection->commit() : $connection->rollBack();
                $this->assertSame(1, $connection->transactionLevel());
                $this->assertTrue($connection->getRawPdo()->inTransaction());
                $this->assertLockAvailable($probe, $name, ! $commit);
                $this->assertBarrierAvailable($handle, ! $commit);
            }
            $commit ? $connection->commit() : $connection->rollBack();
        } catch (\RuntimeException $exception) {
            $caught = $exception->getMessage();
        }
        $this->assertSame($commit && $throw ? 'early callback' : null, $caught);
        $this->assertSame(['first', 'last'], $trace);
        $this->assertSame(0, $connection->transactionLevel());
        $this->assertFalse($connection->getRawPdo()->inTransaction());
        // Both resources must be released before a commit exception reaches the caller.
        $this->assertLockAvailable($probe, $name, true);
        $this->assertBarrierAvailable($handle, true);
        $this->assertManagerEmpty();
    }

    public static function nestedOutcomes(): array
    {
        return ['outer commit' => ['commit'], 'outer rollback' => ['rollback'], 'savepoint rollback' => ['savepoint']];
    }

    #[DataProvider('nestedOutcomes')]
    public function test_savepoint_callbacks_wait_for_or_are_discarded_by_parent(string $outcome): void
    {
        $trace = [];
        $user = User::factory()->create(['name' => 'before']);
        DB::beginTransaction();
        DB::afterCommit(function () use (&$trace): void { $trace[] = 'root commit'; });
        DB::afterRollBack(function () use (&$trace): void { $trace[] = 'root rollback'; });
        DB::beginTransaction();
        DB::table('users')->where('id', $user->id)->update(['name' => 'child']);
        DB::afterCommit(function () use (&$trace): void { $trace[] = 'child commit'; });
        DB::afterRollBack(function () use (&$trace): void { $trace[] = 'child rollback'; });
        if ($outcome === 'savepoint') {
            DB::rollBack();
            $this->assertSame(['child rollback'], $trace);
        } else {
            DB::commit();
            $this->assertSame([], $trace, 'A child commit must not execute afterCommit callbacks.');
        }
        $this->assertSame(1, DB::transactionLevel());
        $this->assertTrue(DB::connection()->getRawPdo()->inTransaction());
        $outcome === 'rollback' ? DB::rollBack() : DB::commit();
        $this->assertSame(match ($outcome) {
            'commit' => ['child commit', 'root commit'],
            'rollback' => ['root rollback'], // Committed child rollback callbacks are discarded.
            'savepoint' => ['child rollback', 'root commit'],
        }, $trace);
        $this->assertSame($outcome === 'commit' ? 'child' : 'before', $user->fresh()->name);
        $this->assertManagerEmpty();
    }

    public static function failurePhases(): array
    {
        return ['after commit' => [true], 'after rollback' => [false]];
    }

    #[DataProvider('failurePhases')]
    public function test_callback_failure_effect_on_next_transaction_is_explicit(bool $commit): void
    {
        $trace = [];
        $user = User::factory()->create(['name' => 'before']);
        DB::beginTransaction();
        DB::table('users')->where('id', $user->id)->update(['name' => 'first']);
        $failure = fn () => throw new \RuntimeException('first transaction callback');
        if ($commit) {
            DB::afterCommit($failure);
        } else {
            DB::afterRollBack($failure);
        }
        DB::afterCommit(function () use (&$trace): void { $trace[] = 'old commit'; });
        try {
            $commit ? DB::commit() : DB::rollBack();
            if ($commit) {
                $this->fail('Expected commit callback failure.');
            }
        } catch (\RuntimeException $exception) {
            $this->assertTrue($commit, 'Rollback cleanup must not throw.');
            $this->assertSame('first transaction callback', $exception->getMessage());
        }
        $this->assertSame(0, DB::transactionLevel());
        $this->assertFalse(DB::connection()->getRawPdo()->inTransaction());
        $this->assertSame($commit ? 'first' : 'before', $user->fresh()->name);
        $this->assertSame($commit ? ['old commit'] : [], $trace);
        // No reset/disconnect between transactions.
        $this->assertManagerEmpty();
        DB::transaction(function () use (&$trace, $user): void {
            DB::table('users')->where('id', $user->id)->update(['name' => 'second']);
            DB::afterCommit(function () use (&$trace): void { $trace[] = 'new commit'; });
        });
        $this->assertSame($commit ? ['old commit', 'new commit'] : ['new commit'], $trace);
        $this->assertSame('second', $user->fresh()->name);
        $this->assertManagerEmpty();
    }

    public function test_successful_commit_keeps_live_avatar_despite_early_callback_failure(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $files = null;
        $path = null;
        try {
            DB::transaction(function () use ($user, &$files, &$path): void {
                // Before even the helper's finished=true callback.
                DB::afterCommit(fn () => throw new \RuntimeException('committed callback failure'));
                $files = new ProfileAvatarOperation(app(ImageService::class), DB::connection());
                $path = $files->upload(UploadedFile::fake()->image('avatar.jpg'));
                $user->avatar = $path;
                $user->save();
            });
            $this->fail('Expected callback failure after PDO commit.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('committed callback failure', $exception->getMessage());
            $files?->handleFailure(); // Must retain potentially committed files.
        }
        $this->assertNotNull($path);
        $this->assertSame($path, $user->fresh()->avatar);
        $probe = $this->probeConnection();
        $this->assertSame($path, $probe->table('users')->where('id', $user->id)->value('avatar'));
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertExists(ImageService::thumbPath($path));
        $this->assertSame(0, DB::transactionLevel());
        $this->assertFalse(DB::connection()->getRawPdo()->inTransaction());
        $this->assertManagerEmpty();
    }

    public function test_rollback_cleanup_preserves_the_original_application_exception(): void
    {
        Storage::fake('public');
        Log::shouldReceive('error')->once()->andThrow(new \RuntimeException('logger failure'));
        $user = User::factory()->create(['name' => 'before']);
        $original = new \RuntimeException('application failure');
        $trace = [];
        $path = null;
        try {
            DB::transaction(function () use ($user, $original, &$trace, &$path): void {
                DB::afterRollBack(fn () => throw new \RuntimeException('cleanup failure'));
                $files = new ProfileAvatarOperation(app(ImageService::class), DB::connection());
                $path = $files->upload(UploadedFile::fake()->image('rollback.jpg'));
                $user->update(['name' => 'changed', 'avatar' => $path]);
                DB::afterRollBack(function () use (&$trace): void { $trace[] = 'last'; });
                throw $original;
            });
            $this->fail('Expected original application exception.');
        } catch (\RuntimeException $exception) {
            $this->assertSame($original, $exception);
        }
        $this->assertSame(['last'], $trace);
        $this->assertSame('before', $user->fresh()->name);
        $this->assertNotNull($path);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertMissing(ImageService::thumbPath($path));
        $this->assertManagerEmpty();
        $this->assertSame(0, DB::transactionLevel());
        $this->assertFalse(DB::connection()->getRawPdo()->inTransaction());
    }

    #[DataProvider('nestedOutcomes')]
    public function test_real_queue_after_commit_is_enqueued_once_and_never_replayed(string $outcome): void
    {
        $connection = DB::connection();
        $probe = $this->probeConnection();
        $queueName = 'f10-'.bin2hex(random_bytes(12));
        $this->cleanupActions[] = [25, function () use ($connection, $queueName): void {
            $this->approvedPdo($connection);
            $connection->table('jobs')->where('queue', $queueName)->delete();
        }];
        // Use Laravel's actual enqueueUsing/afterCommit path, without a worker or Queue::fake.
        $queue = new DatabaseQueue($connection, 'jobs', $queueName, 60, true);
        $queue->setContainer(app());
        $error = new \RuntimeException('early queue callback');
        $connection->beginTransaction();
        $connection->beginTransaction();
        $connection->afterCommit(fn () => throw $error);
        $connection->afterRollBack(fn () => throw new \RuntimeException('queue rollback cleanup'));
        $queue->push('F10ContractProbe@handle', ['marker' => 'first']);
        $this->assertSame(0, $probe->table('jobs')->where('queue', $queueName)->count());
        if ($outcome === 'savepoint') {
            $connection->rollBack();
        } else {
            $connection->commit();
        }
        $this->assertSame(0, $probe->table('jobs')->where('queue', $queueName)->count());
        $caught = null;
        try {
            $outcome === 'rollback' ? $connection->rollBack() : $connection->commit();
        } catch (\RuntimeException $exception) {
            $caught = $exception;
        }
        $this->assertSame($outcome === 'commit' ? $error : null, $caught);
        $this->assertSame($outcome === 'commit' ? 1 : 0, $probe->table('jobs')->where('queue', $queueName)->count());
        $this->assertManagerEmpty();
        $connection->transaction(fn () => $queue->push('F10ContractProbe@handle', ['marker' => 'second']));
        $payloads = $probe->table('jobs')->where('queue', $queueName)->orderBy('id')->pluck('payload')
            ->map(fn ($payload) => json_decode($payload, true, flags: JSON_THROW_ON_ERROR)['data']['marker'])->all();
        $this->assertSame($outcome === 'commit' ? ['first', 'second'] : ['second'], $payloads);
        $this->assertManagerEmpty();
    }

    private function assertProductionManager(Connection $connection): void
    {
        // Connection has no public getter. Read identity without replacing its manager.
        $property = new \ReflectionProperty(Connection::class, 'transactionsManager');
        $this->assertSame(app('db.transactions'), $property->getValue($connection));
        $this->assertSame(SafeDatabaseTransactionsManager::class, app('db.transactions')::class);
    }

    private function assertManagerEmpty(): void
    {
        $this->assertCount(0, app('db.transactions')->getPendingTransactions());
        $this->assertCount(0, app('db.transactions')->getCommittedTransactions());
    }

    private function probeConnection(): Connection
    {
        $database = config('database');
        TestDatabaseSafety::assertConfiguration(app()->environment(), $database);
        // Independent PDO with the SAME approved config; no config overrides or alternate DB.
        $connection = app('db')->connectUsing('f10_probe_'.count($this->ownedConnections), $database['connections']['mysql']);
        try {
            TestDatabaseSafety::assertMetadata(app()->environment(), $database, $connection);
            $connection->getPdo();
            $this->protectConnectionCleanup($connection);
            $this->assertProductionManager($connection);
            return $connection;
        } catch (\Throwable $exception) {
            $connection->disconnect();
            throw $exception;
        }
    }

    private function protectConnectionCleanup(Connection $connection): void
    {
        $database = config('database');
        $environment = app()->environment();
        $pdo = TestDatabaseSafety::assertConnected($environment, $database, $connection);
        $this->ownedConnections[spl_object_id($connection)] = compact('connection', 'database', 'environment', 'pdo');
        $this->cleanupActions[] = [0, function () use ($connection): void {
            $pdo = $this->approvedPdo($connection);
            // If an assertion aborts mid-transaction, clean only that still-active
            // PDO transaction. Do not invoke deliberately broken callback chains.
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->assertFalse($pdo->inTransaction());
        }];
        $this->cleanupActions[] = [40, function () use ($connection): void {
            // Disconnect is local and must still run if rollback/safety validation
            // failed. It opens no connection and executes no SQL on an unknown DB.
            $connection->disconnect();
            $connection->unsetTransactionManager();
            $this->assertNull($connection->getRawPdo());
            $this->assertSame(0, $connection->transactionLevel());
        }];
    }

    private function approvedPdo(Connection $connection): \PDO
    {
        $owned = $this->ownedConnections[spl_object_id($connection)];
        return TestDatabaseSafety::assertConnected(
            $owned['environment'], $owned['database'], $connection, $owned['pdo'],
        );
    }

    private function assertLockAvailable(Connection $probe, string $name, bool $available): void
    {
        $this->approvedPdo($probe);
        $result = $probe->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$name])->acquired;
        $this->assertNotNull($result, 'NULL is an advisory-lock error, not contention.');
        $acquired = (int) $result;
        try {
            $this->assertSame($available ? 1 : 0, $acquired);
        } finally {
            if ($acquired === 1) {
                $this->approvedPdo($probe);
                $this->assertSame(1, (int) $probe->selectOne('SELECT RELEASE_LOCK(?) AS released', [$name])->released);
            }
        }
    }

    private function assertBarrierAvailable($handle, bool $available): void
    {
        $acquired = flock($handle, LOCK_EX | LOCK_NB);
        try {
            $this->assertSame($available, $acquired);
        } finally {
            if ($acquired) {
                $this->assertTrue(flock($handle, LOCK_UN));
            }
        }
    }
}
