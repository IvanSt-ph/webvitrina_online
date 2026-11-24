<?php

namespace Tests\Support;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Foundation\Application;
use RuntimeException;

final class DisposableDatabaseMode
{
    public static function install(Application $app): void
    {
        DisposableTestEnvironment::assertConfiguration($app->environment(), $app['config']->get('database'));
        $app['config']->set('database.connections', [
            'mysql' => $app['config']->get('database.connections.mysql'),
        ]);
        $app->bind('db.connector.mysql', static fn () => new DisposableMySqlConnector);
        foreach (['sqlite', 'pgsql', 'sqlsrv', 'mariadb'] as $driver) {
            $app->bind('db.connector.'.$driver, static function () {
                throw new RuntimeException('Only the disposable MySQL connector is permitted.');
            });
        }
        $app['events']->listen(ConnectionEstablished::class, static function (ConnectionEstablished $event) use ($app): void {
            $check = static function ($connection) use ($app): void {
                DisposableTestEnvironment::assertConfiguration($app->environment(), $app['config']->get('database'));
                DisposableTestEnvironment::assertConfiguration($app->environment(), [
                    'default' => 'mysql', 'connections' => ['mysql' => $connection->getConfig()],
                ]);
            };
            $check($event->connection);
            $event->connection->beforeExecuting(static function ($sql, $bindings, $connection) use ($check): void {
                if (preg_match('/\bUSE\s|\b(?:CREATE|DROP|ALTER)\s+(?:DATABASE|SCHEMA)\b/i', $sql)) {
                    throw new RuntimeException('Database switching/creation is forbidden in disposable tests.');
                }
                self::prepareQueryConnection($connection, $check);
            });
        });
    }

    /** Query lifecycle only: cleanup must keep requiring its original raw PDO. */
    public static function prepareQueryConnection(Connection $connection, Closure $check): void
    {
        // Laravel invokes beforeExecuting before its own reconnect step.
        // Validate both application and connection configuration before any I/O.
        $check($connection);
        $connection->reconnectIfMissingConnection();
        $check($connection);
        // The installed disposable connector also verifies every new PDO.
        DisposableTestEnvironment::assertPdo($connection->getPdo());
    }
}
