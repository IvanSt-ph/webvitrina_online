<?php

namespace Tests\Support;

use Illuminate\Database\Connection;
use PDO;
use RuntimeException;
use Tests\TestCase;

final class TestDatabaseSafety
{
    public static function assertConfiguration(string $environment, array $database): void
    {
        // Keep the original allowlist as the first, mandatory check.
        TestCase::assertSafeTestDatabaseConfiguration($environment, $database);
        $mysql = $database['connections']['mysql'];
        if (($mysql['database'] !== DisposableTestEnvironment::DATABASE && ! empty($mysql['unix_socket'])) || ! empty($mysql['prefix'])
            || ! empty($mysql['options'][PDO::ATTR_PERSISTENT])
            || (defined('PDO::MYSQL_ATTR_INIT_COMMAND')
                && isset($mysql['options'][constant('PDO::MYSQL_ATTR_INIT_COMMAND')]))) {
            throw new RuntimeException('Unsafe test connection socket, prefix or PDO options.');
        }
    }

    public static function assertMetadata(string $environment, array $database, Connection $connection): void
    {
        self::assertConfiguration($environment, $database);
        self::assertConfiguration($environment, [
            'default' => 'mysql', 'connections' => ['mysql' => $connection->getConfig()],
        ]);
        if ($connection->getDatabaseName() !== $database['connections']['mysql']['database']
            || $connection->getTablePrefix() !== '') {
            throw new RuntimeException('Resolved test connection does not match the approved database.');
        }
    }

    public static function assertConnected(
        string $environment, array $database, Connection $connection, ?PDO $expectedPdo = null,
    ): PDO {
        self::assertMetadata($environment, $database, $connection);
        // Cleanup must never open/reconnect a connection to discover its target.
        $pdo = $connection->getRawPdo();
        if (! $pdo instanceof PDO || ($expectedPdo !== null && $pdo !== $expectedPdo)) {
            throw new RuntimeException('Test cleanup requires the original connected PDO.');
        }
        if ($database['connections']['mysql']['database'] === DisposableTestEnvironment::DATABASE) {
            DisposableTestEnvironment::assertPdo($pdo);

            return $pdo;
        }
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            throw new RuntimeException('Test cleanup requires a MySQL PDO.');
        }
        // Execute only when tests are explicitly run; unit regressions use PDO doubles.
        $statement = $pdo->query('SELECT DATABASE()');
        if ($statement === false) {
            throw new RuntimeException('Cannot verify the actual test database.');
        }
        try {
            if ($statement->fetchColumn() !== 'webv3_testing') {
                throw new RuntimeException('Actual PDO database is not webv3_testing.');
            }
        } finally {
            $statement->closeCursor();
        }

        // CURRENT_USER identifies the authenticated privilege account, not the supplied login name.
        $statement = $pdo->query('SELECT CURRENT_USER()');
        if ($statement === false) {
            throw new RuntimeException('Cannot verify the actual test MySQL account.');
        }
        try {
            if ($statement->fetchColumn() !== 'webvitrina_test@localhost') {
                throw new RuntimeException('Actual PDO MySQL account is not webvitrina_test@localhost.');
            }
        } finally {
            $statement->closeCursor();
        }

        return $pdo;
    }
}
