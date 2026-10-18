<?php

namespace Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\MySqlConnection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Testing\ParallelTesting;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use Tests\Feature\PurgeOrderHistoryTest;
use Tests\Support\TestDatabaseSafety;
use Tests\Support\TestFileSafety;
use Tests\Support\TestWorkerBootstrap;

// Deliberately plain PHPUnit: no Laravel bootstrap, DB traits or real PDO constructors.
class TestCleanupSafetyTest extends TestCase
{
    private function database(string $name = 'webv3_testing'): array
    {
        return ['default' => 'mysql', 'connections' => ['mysql' => [
            'driver' => 'mysql', 'host' => '127.127.126.9', 'port' => '3306',
            'database' => $name, 'prefix' => '', 'url' => null,
            'username' => 'webvitrina_test',
        ]]];
    }

    public static function cleanupClasses(): array
    {
        return array_map(static fn ($class) => [$class], [
            PurgeOrderHistoryTest::class,
            \Tests\Feature\BackupAtomicityTest::class,
            \Tests\Feature\BackupSnapshotTest::class,
            \Tests\Feature\BackupWriteBarrierTest::class,
            \Tests\Feature\SafeRestoreTest::class,
            \Tests\Feature\PrivateBackupTest::class,
            \Tests\Feature\OperationalHealthTest::class,
            ProductionHealthTest::class,
        ]);
    }

    #[DataProvider('cleanupClasses')]
    public function test_failed_parent_setup_never_connects_or_cleans_files(string $class): void
    {
        $previous = Container::getInstance();
        $previousFacadeApp = Facade::getFacadeApplication();
        $app = new Application(dirname(__DIR__, 2));
        $app->instance('env', 'testing');
        $app->instance('config', new Repository(['database' => $this->database('webv3')]));
        $db = $this->createMock(DatabaseManager::class);
        $db->expects($this->never())->method('connection');
        $app->instance('db', $db);
        $disks = $this->createMock(FilesystemManager::class);
        $disks->expects($this->never())->method('disk');
        $disks->expects($this->never())->method('forgetDisk');
        $app->instance('filesystem', $disks);
        $files = $this->createMock(Filesystem::class);
        $files->expects($this->never())->method('deleteDirectory');
        $app->instance('files', $files);
        $app->instance(ParallelTesting::class, $this->createStub(ParallelTesting::class));

        $subject = $this->getMockBuilder($class)
            ->setConstructorArgs(['unused'])
            ->onlyMethods(['createApplication'])->getMock();
        $subject->method('createApplication')->willReturnCallback(static function () use ($app) {
            Facade::setFacadeApplication($app);

            return $app;
        });
        try {
            try {
                (new ReflectionMethod($class, 'setUp'))->invoke($subject);
                $this->fail('The original database guard must reject webv3.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('explicitly approved', $exception->getMessage());
            }
            // Reproduce PHPUnit 11's lifecycle, not the destructive SQL itself.
            (new ReflectionMethod($class, 'tearDown'))->invoke($subject);
            $this->assertNull((new ReflectionProperty(\Tests\TestCase::class, 'app'))->getValue($subject));
        } finally {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($previousFacadeApp);
            Container::setInstance($previous);
        }
    }

    public function test_purge_rechecks_configuration_even_when_setup_was_marked_successful(): void
    {
        $previous = Container::getInstance();
        $previousFacadeApp = Facade::getFacadeApplication();
        $app = new Application(dirname(__DIR__, 2));
        $app->instance('env', 'testing');
        $app->instance('config', new Repository(['database' => $this->database('webv3')]));
        $db = $this->createMock(DatabaseManager::class);
        $db->expects($this->never())->method('connection');
        $app->instance('db', $db);
        $disks = $this->createMock(FilesystemManager::class);
        $disks->expects($this->never())->method('disk');
        $app->instance('filesystem', $disks);
        $app->instance(ParallelTesting::class, $this->createStub(ParallelTesting::class));
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
        $subject = new PurgeOrderHistoryTest('unused');
        (new ReflectionProperty(\Tests\TestCase::class, 'app'))->setValue($subject, $app);
        (new ReflectionProperty(PurgeOrderHistoryTest::class, 'cleanupReady'))->setValue($subject, true);
        try {
            try {
                (new ReflectionMethod(PurgeOrderHistoryTest::class, 'tearDown'))->invoke($subject);
                $this->fail('Cleanup accepted changed production configuration.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('explicitly approved', $exception->getMessage());
            }
            $this->assertFalse((new ReflectionProperty(PurgeOrderHistoryTest::class, 'cleanupReady'))->getValue($subject));
        } finally {
            Facade::clearResolvedInstances();
            Facade::setFacadeApplication($previousFacadeApp);
            Container::setInstance($previous);
        }
    }

    public function test_safe_config_cannot_hide_a_cached_production_connection(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->never())->method('query');
        $connection = new MySqlConnection($pdo, 'webv3', '', $this->database('webv3')['connections']['mysql']);
        $this->expectException(RuntimeException::class);
        TestDatabaseSafety::assertConnected('testing', $this->database(), $connection);
    }

    public function test_safe_metadata_cannot_hide_the_actual_production_schema(): void
    {
        $pdo = $this->pdoReporting('webv3');
        $connection = new MySqlConnection($pdo, 'webv3_testing', '', $this->database()['connections']['mysql']);
        $this->expectExceptionMessage('Actual PDO database is not webv3_testing');
        TestDatabaseSafety::assertConnected('testing', $this->database(), $connection);
    }

    public function test_original_test_pdo_is_accepted_without_mutating_queries(): void
    {
        $pdo = $this->pdoReporting('webv3_testing');
        $connection = new MySqlConnection($pdo, 'webv3_testing', '', $this->database()['connections']['mysql']);
        $this->assertSame($pdo, TestDatabaseSafety::assertConnected('testing', $this->database(), $connection, $pdo));
    }

    public function test_safe_config_cannot_hide_a_cached_root_connection(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->never())->method('query');
        $metadata = $this->database()['connections']['mysql'];
        $metadata['username'] = 'root';
        $connection = new MySqlConnection($pdo, 'webv3_testing', '', $metadata);
        $this->expectExceptionMessage('as webvitrina_test');
        TestDatabaseSafety::assertConnected('testing', $this->database(), $connection);
    }

    public static function unapprovedAccounts(): array
    {
        return [
            'root' => ['root@localhost'],
            'different host account' => ['webvitrina_test@%'],
            'anonymous account' => ['@localhost'],
            'missing result' => [null],
        ];
    }

    #[DataProvider('unapprovedAccounts')]
    public function test_safe_metadata_cannot_hide_an_unapproved_actual_account(?string $account): void
    {
        $pdo = $this->pdoReporting('webv3_testing', $account);
        $connection = new MySqlConnection($pdo, 'webv3_testing', '', $this->database()['connections']['mysql']);
        $this->expectExceptionMessage('Actual PDO MySQL account is not webvitrina_test@localhost');
        TestDatabaseSafety::assertConnected('testing', $this->database(), $connection);
    }

    public static function failedAccountProbes(): array
    {
        return ['query returns false' => [false], 'query throws' => [true]];
    }

    #[DataProvider('failedAccountProbes')]
    public function test_failed_account_probe_refuses_cleanup(bool $throws): void
    {
        $failure = $throws ? new \PDOException('Account probe failed.') : false;
        $pdo = $this->pdoReporting('webv3_testing', $failure);
        $connection = new MySqlConnection($pdo, 'webv3_testing', '', $this->database()['connections']['mysql']);
        $this->expectExceptionMessage($throws ? 'Account probe failed.' : 'Cannot verify the actual test MySQL account');
        TestDatabaseSafety::assertConnected('testing', $this->database(), $connection);
    }

    public function test_cleanup_does_not_resolve_a_lazy_or_disconnected_pdo(): void
    {
        $connection = new MySqlConnection(static function () {
            throw new \LogicException('A cleanup must not connect.');
        }, 'webv3_testing', '', $this->database()['connections']['mysql']);
        $this->expectExceptionMessage('original connected PDO');
        TestDatabaseSafety::assertConnected('testing', $this->database(), $connection);
    }

    public function test_replaced_pdo_is_rejected_before_querying_it(): void
    {
        $original = $this->createMock(PDO::class);
        $replacement = $this->createMock(PDO::class);
        $replacement->expects($this->never())->method('query');
        $connection = new MySqlConnection($replacement, 'webv3_testing', '', $this->database()['connections']['mysql']);
        $this->expectExceptionMessage('original connected PDO');
        TestDatabaseSafety::assertConnected('testing', $this->database(), $connection, $original);
    }

    public function test_failed_schema_probe_refuses_cleanup(): void
    {
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->willReturn('mysql');
        $pdo->expects($this->once())->method('query')->with('SELECT DATABASE()')->willReturn(false);
        $connection = new MySqlConnection($pdo, 'webv3_testing', '', $this->database()['connections']['mysql']);
        $this->expectExceptionMessage('Cannot verify');
        TestDatabaseSafety::assertConnected('testing', $this->database(), $connection);
    }

    public function test_truncation_rejects_cached_production_connection_before_disabling_foreign_keys(): void
    {
        $previous = Container::getInstance();
        $app = new Application(dirname(__DIR__, 2));
        $app->instance('env', 'testing');
        $app->instance('config', new Repository(['database' => $this->database()]));
        $connection = $this->createMock(MySqlConnection::class);
        $connection->method('getConfig')->willReturn($this->database('webv3')['connections']['mysql']);
        $connection->expects($this->never())->method('getPdo');
        $connection->expects($this->never())->method('getSchemaBuilder');
        $db = $this->createMock(DatabaseManager::class);
        $db->method('connection')->willReturn($connection);
        $app->instance('db', $db);
        $subject = new class($app)
        {
            use \Tests\Support\SafeDatabaseTruncation;

            public function __construct(public Application $app) {}

            public function cleanup(): void
            {
                $this->truncateTablesForAllConnections();
            }
        };
        try {
            $this->expectException(RuntimeException::class);
            $subject->cleanup();
        } finally {
            Container::setInstance($previous);
        }
    }

    public function test_worker_rejects_production_input_before_bootstrap(): void
    {
        $previous = Container::getInstance();
        try {
            TestWorkerBootstrap::boot($this->database('webv3'));
            $this->fail('Unsafe worker configuration was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('explicitly approved', $exception->getMessage());
        }
        $this->assertSame($previous, Container::getInstance());
    }

    public function test_socket_override_is_rejected(): void
    {
        $database = $this->database();
        $database['connections']['mysql']['unix_socket'] = '/production/mysql.sock';
        $this->expectException(RuntimeException::class);
        TestDatabaseSafety::assertConfiguration('testing', $database);
    }

    public static function unapprovedWorkerUsernames(): array
    {
        return ['root' => ['root'], 'missing username' => [null]];
    }

    #[DataProvider('unapprovedWorkerUsernames')]
    public function test_worker_rejects_unapproved_username_before_bootstrap(?string $username): void
    {
        $database = $this->database();
        if ($username === null) {
            unset($database['connections']['mysql']['username']);
        } else {
            $database['connections']['mysql']['username'] = $username;
        }
        $previous = Container::getInstance();
        try {
            TestWorkerBootstrap::boot($database);
            $this->fail('Unsafe worker username was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('as webvitrina_test', $exception->getMessage());
        }
        $this->assertSame($previous, Container::getInstance());
    }

    public function test_real_public_storage_is_not_a_cleanup_target(): void
    {
        $this->expectException(RuntimeException::class);
        TestFileSafety::assertDirectory(dirname(__DIR__, 2).'/storage/app/public');
    }

    private function pdoReporting(string $database, string|false|\Throwable|null $account = 'webvitrina_test@localhost'): PDO
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())->method('fetchColumn')->willReturn($database);
        $statement->expects($this->once())->method('closeCursor')->willReturn(true);
        $accountStatement = null;
        if ($database === 'webv3_testing' && $account !== false && ! $account instanceof \Throwable) {
            $accountStatement = $this->createMock(PDOStatement::class);
            $accountStatement->expects($this->once())->method('fetchColumn')->willReturn($account);
            $accountStatement->expects($this->once())->method('closeCursor')->willReturn(true);
        }
        $pdo = $this->createMock(PDO::class);
        $pdo->method('getAttribute')->with(PDO::ATTR_DRIVER_NAME)->willReturn('mysql');
        $queries = $database === 'webv3_testing' ? ['SELECT DATABASE()', 'SELECT CURRENT_USER()'] : ['SELECT DATABASE()'];
        $index = 0;
        $pdo->expects($this->exactly(count($queries)))->method('query')->willReturnCallback(
            function (string $query) use ($queries, &$index, $statement, $accountStatement, $account): PDOStatement|false {
                $this->assertSame($queries[$index++], $query);
                if ($query === 'SELECT DATABASE()') {
                    return $statement;
                }
                if ($account instanceof \Throwable) {
                    throw $account;
                }

                return $account === false ? false : $accountStatement;
            },
        );
        $pdo->expects($this->never())->method('exec');
        $pdo->expects($this->never())->method('prepare');

        return $pdo;
    }
}
