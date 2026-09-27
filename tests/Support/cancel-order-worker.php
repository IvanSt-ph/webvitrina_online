<?php

// Separate MySQL sessions for the integration test; never use the development DB.
require dirname(__DIR__, 2).'/vendor/autoload.php';
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$database = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
Tests\TestCase::assertSafeTestDatabaseConfiguration($app->environment(), $database);
$app['config']->set('database', $database);
Illuminate\Support\Facades\DB::purge();
$connection = Illuminate\Support\Facades\DB::connection();
$order = App\Models\Order::findOrFail((int) $argv[1]);
echo 'CONNECTION:'.$connection->selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
flush();
if ($argv[2] === 'first') {
    $connection->beginTransaction();
    App\Models\Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
    echo "LOCKED\n";
    flush();
    if (trim(fgets(STDIN)) !== 'release') {
        throw new RuntimeException('Expected release signal');
    }
}
echo "ATTEMPT\n";
flush();
$order->setStatus(App\Models\Order::STATUS_CANCELED);
if ($argv[2] === 'first') {
    $connection->commit();
}
echo "DONE\n";
