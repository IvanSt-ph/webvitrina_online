<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Tests\Support\TestDatabaseSafety;

// The only schema target allowed by this runner is the existing test database.
require dirname(__DIR__).'/vendor/autoload.php';
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->afterBootstrapping(LoadConfiguration::class, function ($app): void {
    TestDatabaseSafety::assertConfiguration('testing', $app['config']->get('database'));
    $app['config']->set('cache.default', 'array');
    $app['config']->set('session.driver', 'array');
    $app['config']->set('mail.default', 'array');
});
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
$connection = $app['db']->connection();
TestDatabaseSafety::assertMetadata($app->environment(), config('database'), $connection);
$connection->getPdo();
TestDatabaseSafety::assertConnected($app->environment(), config('database'), $connection);
echo 'Verified SELECT DATABASE(): webv3_testing'.PHP_EOL;
$exit = $kernel->call('migrate', ['--database' => 'mysql', '--path' => 'database/migrations/2026_11_07_000001_archive_product_attribute_values.php', '--force' => true]);
echo $kernel->output();
exit($exit);
