<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    private ?Support\ExistingSchemaFileIsolation $fileIsolation = null;

    protected function tearDown(): void
    {
        $database = Support\ExistingSchemaMode::enabled() && $this->app && $this->app->resolved('db')
            ? $this->app['db'] : null;
        try {
            parent::tearDown();
        } finally {
            // Run AFTER every application-destroyed callback, including worker and
            // transaction cleanup. Do not open/reconnect a PDO during teardown.
            try {
                foreach ($database?->getConnections() ?? [] as $connection) {
                    $connection->disconnect();
                }
            } finally {
                $this->fileIsolation?->close();
                $this->fileIsolation = null;
            }
        }
    }

    public function createApplication()
    {
        if (! Support\ExistingSchemaMode::enabled()) {
            return parent::createApplication();
        }
        Support\ExistingSchemaMode::assertTestAllowed(static::class);
        Support\ExistingSchemaMode::lockRun();
        // One scope per test instance/data set; application refreshes retain its files.
        $isolation = $this->fileIsolation ??= Support\ExistingSchemaFileIsolation::create();
        $app = null;
        try {
            $app = require dirname(__DIR__).'/bootstrap/app.php';
            $isolation->attach($app);
            $this->traitsUsedByTest = array_flip(class_uses_recursive(static::class));
            $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
                static function ($app) use ($isolation): void {
                    Support\ExistingSchemaMode::install($app);
                    $isolation->configure($app);
                });
            $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

            return $app;
        } catch (\Throwable $exception) {
            try {
                // Failed bootstrap never returns an application to normal teardown.
                if ($app && $app->resolved('db')) {
                    foreach ($app['db']->getConnections() as $connection) {
                        $connection->disconnect();
                    }
                }
            } finally {
                $isolation->close();
                $this->fileIsolation = null;
            }
            throw $exception;
        }
    }

    protected function rollBackTestTransactionsBeforeApplicationDestroyed(): void
    {
        $connection = $this->app['db']->connection();
        $pdo = Support\TestDatabaseSafety::assertConnected(
            $this->app->environment(), $this->app['config']->get('database'), $connection,
        );
        $this->beforeApplicationDestroyed(function () use ($connection, $pdo): void {
            Support\TestDatabaseSafety::assertConnected(
                $this->app->environment(), $this->app['config']->get('database'), $connection, $pdo,
            );
            while ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
        });
    }

    public function be(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        parent::be($user, $guard);

        // actingAs bypasses Login events; supply the same credential snapshot.
        if (($guard ?? config('auth.defaults.guard')) === 'web') {
            app(\App\Services\PasswordSecurityService::class)->bindSession(session()->driver(), $user);
        }

        return $this;
    }

    protected function setUpTraits()
    {
        if (Support\ExistingSchemaMode::enabled()) {
            Support\ExistingSchemaMode::assertTestAllowed(static::class);
            Support\ExistingSchemaMode::prepare($this->app);
        }
        self::assertSafeTestDatabaseConfiguration(
            $this->app->environment(),
            $this->app['config']->get('database'),
        );

        Support\TestDatabaseSafety::assertConfiguration($this->app->environment(), $this->app['config']->get('database'));
        $traits = class_uses_recursive(static::class);
        if (array_intersect($traits, [
            \Illuminate\Foundation\Testing\RefreshDatabase::class,
            \Illuminate\Foundation\Testing\DatabaseTruncation::class,
            \Illuminate\Foundation\Testing\DatabaseMigrations::class,
        ])) {
            $connection = $this->app['db']->connection();
            Support\TestDatabaseSafety::assertMetadata($this->app->environment(), $this->app['config']->get('database'), $connection);
            $connection->getPdo();
            Support\TestDatabaseSafety::assertConnected($this->app->environment(), $this->app['config']->get('database'), $connection);
        }

        return parent::setUpTraits();
    }

    public static function assertSafeTestDatabaseConfiguration(string $environment, array $databaseConfig): void
    {
        $connection = $databaseConfig['connections']['mysql'] ?? [];

        if ($environment !== 'testing'
            || ($databaseConfig['default'] ?? null) !== 'mysql'
            || ($connection['driver'] ?? null) !== 'mysql'
            || ($connection['database'] ?? null) !== 'webv3_testing'
            || ($connection['username'] ?? null) !== 'webvitrina_test'
            || ($connection['host'] ?? null) !== '127.127.126.9'
            || (string) ($connection['port'] ?? '') !== '3306'
            || ! empty($connection['url'])
            || ! empty($connection['read'])
            || ! empty($connection['write'])) {
            throw new \RuntimeException('Tests require the explicitly approved local MySQL database webv3_testing as webvitrina_test on 127.127.126.9:3306 with APP_ENV=testing and no DB_URL or read/write overrides.');
        }
    }
}
