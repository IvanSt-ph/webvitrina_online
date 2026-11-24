<?php

namespace Tests\Support;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;

final class TestWorkerBootstrap
{
    public static function boot(array $database): Application
    {
        // Reject input before loading .env, providers or opening a connection.
        TestDatabaseSafety::assertConfiguration('testing', $database);
        // Before Application/LoadConfiguration can resolve cached manifests.
        $isolation = ExistingSchemaMode::enabled() ? ExistingSchemaFileIsolation::inherit() : null;
        putenv('APP_ENV=testing');
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $isolation?->attach($app);
        $app->afterBootstrapping(LoadConfiguration::class, static function ($app) use ($database, $isolation): void {
            TestDatabaseSafety::assertConfiguration($app->environment(), $database);
            $database['connections'] = ['mysql' => $database['connections']['mysql']];
            $app['config']->set('database', $database);
            if (getenv('WV_DISPOSABLE_FULL_TEST') === '1') {
                DisposableDatabaseMode::install($app);
            }
            if (ExistingSchemaMode::enabled()) {
                ExistingSchemaMode::install($app);
            }
            $isolation?->configure($app);
            $app['config']->set([
                'cache.default' => 'array', 'session.driver' => 'array',
                'queue.default' => 'sync', 'mail.default' => 'array',
            ]);
        });
        $app->make(Kernel::class)->bootstrap();
        TestDatabaseSafety::assertConfiguration($app->environment(), $app['config']->get('database'));
        $connection = $app['db']->connection();
        TestDatabaseSafety::assertMetadata($app->environment(), $app['config']->get('database'), $connection);
        $connection->getPdo();
        TestDatabaseSafety::assertConnected($app->environment(), $app['config']->get('database'), $connection);

        return $app;
    }
}
