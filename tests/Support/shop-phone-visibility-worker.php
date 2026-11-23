<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

try {
    $database = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    $app = Tests\Support\TestWorkerBootstrap::boot($database);
    $connection = $app['db']->connection();
    $lock = tmpfile();
    $app['config']->set('backup.lock_path', stream_get_meta_data($lock)['uri']);
    echo 'CONNECTION:'.$connection->selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
    $userId = (int) $argv[1];
    $shopId = (int) $argv[2];
    $action = $argv[3];
    $holder = $argv[4] === 'holder';
    if (! in_array($action, ['publish', 'change'], true)) {
        throw new RuntimeException('Unknown action.');
    }

    if (! $holder) {
        Tests\Support\ConcurrencyLockProbe::install($connection, 'users');
    }

    $operation = function () use ($userId, $shopId, $action, $holder, $app): void {
        $owner = App\Models\User::whereKey($userId)->lockForUpdate()->firstOrFail();
        if ($holder) {
            echo "LOCKED\n";
            flush();
            if (trim(fgets(STDIN)) !== 'release') {
                throw new RuntimeException('Missing release.');
            }
        }
        if ($action === 'change') {
            $app->make(App\Services\PhoneAssignmentService::class)->assignToUser($owner, '+37377555666');
        } else {
            $shop = App\Models\Shop::where('user_id', $owner->id)->whereKey($shopId)->lockForUpdate()->firstOrFail();
            $app->make(App\Services\ShopPhoneContact::class)->setVisibility($shop, $owner, true, '+37377111222');
            $shop->save();
        }
    };
    try {
        if ($action === 'change') {
            // Acquire the phone advisory lock BEFORE User, including the outer transaction.
            $app->make(App\Services\PhoneAssignmentService::class)->run('+37377555666', $operation);
        } else {
            $connection->transaction($operation);
        }
        echo "RESULT:success\n";
    } catch (Illuminate\Validation\ValidationException) {
        echo "RESULT:rejected\n";
    }
    $connection->disconnect();
} catch (Throwable $exception) {
    // Never expose SQL bindings or connection credentials from a failed worker.
    fwrite(STDERR, 'Worker failed: '.get_class($exception).PHP_EOL);
    exit(1);
}
