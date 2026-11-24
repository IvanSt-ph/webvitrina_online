<?php

namespace Tests\Support;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use RuntimeException;

trait SafeDatabaseTruncation
{
    use DatabaseTruncation {
        truncateTablesForAllConnections as private frameworkTruncateTables;
        getAllTablesForConnection as private frameworkGetAllTables;
    }

    protected function truncateTablesForAllConnections(): void
    {
        TestDatabaseSafety::assertConfiguration($this->app->environment(), $this->app['config']->get('database'));
        // Validate all targets before disabling FK checks or truncating the first table.
        foreach ($this->connectionsToTruncate() as $name) {
            if ($name !== null && $name !== 'mysql') {
                throw new RuntimeException('Unapproved connection requested for test truncation.');
            }
            $connection = $this->app['db']->connection($name);
            TestDatabaseSafety::assertMetadata($this->app->environment(), $this->app['config']->get('database'), $connection);
            $connection->getPdo();
            TestDatabaseSafety::assertConnected($this->app->environment(), $this->app['config']->get('database'), $connection);
        }
        $this->frameworkTruncateTables();
    }

    protected function getAllTablesForConnection(ConnectionInterface $connection, ?string $name): array
    {
        $database = 'webv3_testing';
        if ($connection->getDatabaseName() === DisposableTestEnvironment::DATABASE) {
            TestDatabaseSafety::assertMetadata($this->app->environment(), $this->app['config']->get('database'), $connection);
            $connection->getPdo();
            TestDatabaseSafety::assertConnected($this->app->environment(), $this->app['config']->get('database'), $connection);
            $database = DisposableTestEnvironment::DATABASE;
        }
        // The framework cache is keyed by connection name, not database identity.
        unset(static::$allTables[$name]);
        $tables = $this->frameworkGetAllTables($connection, $name);
        foreach ($tables as $table) {
            if (($table['schema'] ?? null) !== $database
                || ($table['schema_qualified_name'] ?? null) !== $database.'.'.($table['name'] ?? '')) {
                throw new RuntimeException('Unapproved schema returned for test truncation.');
            }
        }

        return $tables;
    }
}
