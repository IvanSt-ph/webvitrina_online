<?php

// Separate MySQL session for reputation and sales lock-order tests.
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
$shopLockPaused = false;

$connection->beforeExecuting(function (string $query) use (&$shopLockPaused): void {
    $normalized = strtolower($query);

    if ($shopLockPaused
        || !str_contains($normalized, 'from `shops`')
        || !str_contains($normalized, 'for update')) {
        return;
    }

    $shopLockPaused = true;
    echo "SHOP_LOCK_READY\n";
    flush();

    if (trim(fgets(STDIN)) !== 'lock') {
        throw new RuntimeException('Expected shop lock signal');
    }

    echo "SHOP_LOCK_ATTEMPT\n";
    flush();
});

echo 'CONNECTION:'.$connection->selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
echo "READY\n";
flush();

if (trim(fgets(STDIN)) !== 'go') {
    throw new RuntimeException('Expected go signal');
}

echo "ATTEMPT\n";
flush();

if ($argv[1] === 'review') {
    Illuminate\Support\Facades\DB::transaction(function () use ($argv): void {
        $review = App\Models\Review::findOrFail((int) $argv[2]);
        $review->update([
            'status' => App\Models\Review::STATUS_APPROVED,
            'moderated_at' => now(),
        ]);
    });
} elseif ($argv[1] === 'sale') {
    $order = App\Models\Order::findOrFail((int) $argv[2]);
    $buyer = App\Models\User::findOrFail((int) $argv[3]);
    app(App\Services\OrderPickupWorkflow::class)->buyerConfirmReceipt($order, $buyer);
} else {
    throw new RuntimeException('Unknown worker mode');
}

echo "DONE\n";
