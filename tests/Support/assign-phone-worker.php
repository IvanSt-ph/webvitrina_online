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
$service = $app->make(App\Services\PhoneAssignmentService::class);
$user = App\Models\User::findOrFail((int) $argv[1]);
$phone = $argv[2];
$mode = $argv[3];
$outerLock = false;
$lockName = $service->lockName($service->normalize($phone));

echo 'CONNECTION:'.$connection->selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
flush();

try {
    if ($mode === 'first') {
        $outerLock = (int) $connection->selectOne('SELECT GET_LOCK(?, 2) AS acquired', [$lockName])->acquired === 1;
        if (! $outerLock) {
            throw new RuntimeException('First worker could not acquire the coordinating phone lock.');
        }

        echo "LOCKED\n";
        flush();

        if (trim(fgets(STDIN)) !== 'release') {
            throw new RuntimeException('Expected release signal.');
        }
    }

    echo "ATTEMPT\n";
    flush();

    try {
        $service->assignToUser($user, $phone);
        echo "RESULT:success\n";
    } catch (Illuminate\Validation\ValidationException $exception) {
        echo "RESULT:conflict\n";
    }
} finally {
    if ($outerLock) {
        $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
    }
}
