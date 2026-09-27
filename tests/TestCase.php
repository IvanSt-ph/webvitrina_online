<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
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
        self::assertSafeTestDatabaseConfiguration(
            $this->app->environment(),
            $this->app['config']->get('database'),
        );

        return parent::setUpTraits();
    }

    public static function assertSafeTestDatabaseConfiguration(string $environment, array $databaseConfig): void
    {
        $connection = $databaseConfig['connections']['mysql'] ?? [];

        if ($environment !== 'testing'
            || ($databaseConfig['default'] ?? null) !== 'mysql'
            || ($connection['driver'] ?? null) !== 'mysql'
            || ($connection['database'] ?? null) !== 'webv3_testing'
            || ($connection['host'] ?? null) !== '127.127.126.9'
            || (string) ($connection['port'] ?? '') !== '3306'
            || ! empty($connection['url'])
            || ! empty($connection['read'])
            || ! empty($connection['write'])) {
            throw new \RuntimeException('Tests require the explicitly approved local MySQL database webv3_testing on 127.127.126.9:3306 with APP_ENV=testing and no DB_URL or read/write overrides.');
        }
    }
}
