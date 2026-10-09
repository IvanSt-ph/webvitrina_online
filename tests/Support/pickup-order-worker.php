<?php

// Separate MySQL session for the pickup workflow concurrency test.
require dirname(__DIR__, 2).'/vendor/autoload.php';
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$database = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
Tests\TestCase::assertSafeTestDatabaseConfiguration($app->environment(), $database);
$app['config']->set('database', $database);
Illuminate\Support\Facades\DB::purge();

$order = App\Models\Order::findOrFail((int) $argv[1]);
$actor = App\Models\User::findOrFail((int) $argv[2]);
echo 'CONNECTION:'.Illuminate\Support\Facades\DB::selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
echo "ATTEMPT\n";
flush();
$workflow = $app->make(App\Services\OrderPickupWorkflow::class);
if ($argv[3] === 'payment') {
    $workflow->sellerConfirmPayment($order, $actor);
} elseif ($argv[3] === 'receipt') {
    $workflow->buyerConfirmReceipt($order, $actor);
} else {
    throw new RuntimeException('Unexpected worker action');
}
echo "DONE\n";
