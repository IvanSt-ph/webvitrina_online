<?php

namespace Tests\Support;

use Illuminate\Database\Connection;
use PDOException;
use RuntimeException;

/** Test-only protocol; the conflict is proven by MySQL, never by elapsed time. */
final class ConcurrencyLockProbe
{
    public static function install(Connection $connection, string $table): void
    {
        $probed = false;
        $connection->beforeExecuting(function ($sql, $bindings, $connection) use ($table, &$probed): void {
            if ($probed || ! str_contains(strtolower($sql), 'from `'.$table.'`')
                || ! str_contains(strtolower($sql), 'for update')) {
                return;
            }
            $probed = true;
            $pdo = TestDatabaseSafety::assertConnected(app()->environment(), config('database'), $connection);
            if ($connection->transactionLevel() < 1 || ! $pdo->inTransaction()) {
                throw new RuntimeException('Lock probe requires the real operation transaction.');
            }
            if (ExistingSchemaMode::enabled()) { ExistingSchemaMode::assertSqlAllowed($sql.' NOWAIT'); }
            $statement = $pdo->prepare($sql.' NOWAIT');
            try {
                $statement->execute($connection->prepareBindings($bindings));
                throw new RuntimeException('Expected a NOWAIT conflict on '.$table);
            } catch (PDOException $exception) {
                if ((int) ($exception->errorInfo[1] ?? 0) !== 3572) {
                    throw $exception;
                }
            } finally {
                $statement->closeCursor();
            }
            echo "LOCK_CONFLICT\n";
            flush();
            if (trim(fgets(STDIN)) !== 'continue') {
                throw new RuntimeException('Expected continue signal after confirmed lock conflict.');
            }
            if (! $pdo->inTransaction()) {
                throw new RuntimeException('NOWAIT must preserve the operation transaction.');
            }
            echo "LOCK_RESUMING\n";
            flush();
            // Returning executes the unmodified production locking SELECT.
        });
    }

    /** Probe all fixture rows on a separate, guarded PDO after the workers stop. */
    public static function assertReleased(array $rows, array $processes): void
    {
        $database = config('database');
        $environment = app()->environment();
        TestDatabaseSafety::assertConfiguration($environment, $database);
        $name = 'concurrency_probe_'.bin2hex(random_bytes(8));
        $probe = app('db')->connectUsing($name, $database['connections']['mysql']);
        try {
            TestDatabaseSafety::assertMetadata($environment, $database, $probe);
            $probe->getPdo();
            $pdo = TestDatabaseSafety::assertConnected($environment, $database, $probe);
            $ids = [];
            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    throw new RuntimeException('Worker survived cleanup.');
                }
                if (preg_match('/CONNECTION:(\d+)/', $process->getOutput(), $match)) {
                    $ids[] = (int) $match[1];
                }
            }
            $deadline = microtime(true) + 5;
            do {
                $conflict = false;
                TestDatabaseSafety::assertConnected($environment, $database, $probe, $pdo);
                $pdo->beginTransaction();
                try {
                    foreach ($rows as $table => $rowIds) {
                        if (! in_array($table, ['orders', 'products', 'shops', 'reviews', 'users'], true)) {
                            throw new RuntimeException('Unexpected lock probe table.');
                        }
                        foreach ($rowIds as $id) {
                            $sql = 'SELECT id FROM `'.$table.'` WHERE id = ? FOR UPDATE NOWAIT';
                            if (ExistingSchemaMode::enabled()) { ExistingSchemaMode::assertSqlAllowed($sql); }
                            $statement = $pdo->prepare($sql);
                            try {
                                $statement->execute([$id]);
                                if ($statement->fetchColumn() === false) {
                                    throw new RuntimeException('Lock probe fixture row is missing.');
                                }
                            } finally {
                                $statement->closeCursor();
                            }
                        }
                    }
                } catch (PDOException $exception) {
                    if ((int) ($exception->errorInfo[1] ?? 0) !== 3572) {
                        throw $exception;
                    }
                    $conflict = true;
                } finally {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                }
                // Without PROCESS MySQL exposes this account's own sessions,
                // which includes every guarded test worker. Preserve the session check.
                if (ExistingSchemaMode::enabled()) { ExistingSchemaMode::assertSqlAllowed('SHOW PROCESSLIST'); }
                $statement = $pdo->query('SHOW PROCESSLIST');
                try {
                    $alive = array_intersect($ids, array_column($statement->fetchAll(\PDO::FETCH_ASSOC), 'Id'));
                } finally {
                    $statement->closeCursor();
                }
                if (! $conflict && $alive === []) {
                    return;
                }
                usleep(20000); // Polling cadence only; success requires actual NOWAIT acquisition.
            } while (microtime(true) < $deadline);
            throw new RuntimeException('Worker session or fixture row lock survived cleanup.');
        } finally {
            app('db')->purge($name);
        }
    }
}
