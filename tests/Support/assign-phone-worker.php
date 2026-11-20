<?php

// Separate MySQL sessions for the integration test; never use the development DB.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$database = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
$app = Tests\Support\TestWorkerBootstrap::boot($database);

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
    if ($mode === 'second') {
        $acquired = $connection->selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lockName])->acquired;
        if ((int) $acquired === 1) {
            $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
            throw new RuntimeException('Expected a real phone lock conflict.');
        }
        if ($acquired === null) { throw new RuntimeException('Phone lock probe failed.'); }
        echo "LOCK_CONFLICT\n";
        flush();
        if (trim(fgets(STDIN)) !== 'continue') { throw new RuntimeException('Expected continue signal.'); }
        echo "LOCK_RESUMING\n";
        flush();
    }
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
    try {
        if ($outerLock) {
            $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
        }
    } finally {
        $connection->disconnect();
    }
}
