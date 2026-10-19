<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Models\User;
use App\Services\PhoneAssignmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

try {
    $input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    $app = Tests\Support\TestWorkerBootstrap::boot($input['database']);
    config(['app.key' => $input['key']]);
    $user = User::findOrFail($input['user_id']);
    echo 'CONNECTION:'.DB::selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
    $probed = false;
    DB::connection()->beforeExecuting(function ($sql, $bindings, $connection) use ($input, &$probed): void {
        if ($input['pause'] || $probed || ! str_contains($sql, 'GET_LOCK')
            || ! str_starts_with((string) ($bindings[0] ?? ''), 'wv:admins:')) {
            return;
        }
        $probed = true;
        if (! $connection->getPdo()->inTransaction()) {
            throw new RuntimeException('Expected enclosing transaction.');
        }
        // No PROCESS privilege and no timing inference: MySQL must report that
        // this exact membership lock is unavailable to this independent session.
        $probe = $connection->getPdo()->prepare('SELECT GET_LOCK(?, 0)');
        $probe->execute([$bindings[0]]);
        $acquired = $probe->fetchColumn();
        $probe->closeCursor();
        if ((string) $acquired !== '0') {
            throw new RuntimeException('Expected membership lock conflict.');
        }
        $owner = $connection->getPdo()->prepare('SELECT IS_USED_LOCK(?)');
        $owner->execute([$bindings[0]]);
        echo 'OWNER:'.$owner->fetchColumn().PHP_EOL;
        $owner->closeCursor();
        echo "CONFLICT\n";
        flush();
        if (trim(fgets(STDIN)) !== 'continue') {
            throw new RuntimeException('Expected continue signal.');
        }
    });

    try {
        DB::transaction(function () use ($input, $user, $app): void {
            // Establish a snapshot before waiting; the guard must use current
            // membership, not this snapshot after the first worker commits.
            User::where('role', 'admin')->count();
            match ($input['action']) {
                'delete' => $user->delete(),
                'demote' => $app->make(PhoneAssignmentService::class)->updateUser($user, null, ['role' => 'buyer']),
                'save' => $user->update(['role' => 'buyer']),
            };
            if ($input['pause']) {
                // Service transactions have returned, but the enclosing
                // transaction must still own the membership lock.
                echo "HELD\n";
                flush();
                if (trim(fgets(STDIN)) !== 'release') {
                    throw new RuntimeException('Expected release signal.');
                }
                if ($input['rollback']) {
                    throw new RuntimeException('intentional rollback');
                }
            }
        });
        echo "RESULT:ok\n";
    } catch (ValidationException $exception) {
        if (! isset($exception->errors()[$input['action'] === 'delete' ? 'user' : 'role'])) {
            throw $exception;
        }
        echo "RESULT:protected\n";
    } catch (RuntimeException $exception) {
        if (! $input['rollback'] || $exception->getMessage() !== 'intentional rollback') {
            throw $exception;
        }
        echo "RESULT:rollback\n";
    }
    if (DB::transactionLevel() !== 0 || DB::connection()->getPdo()->inTransaction()) {
        throw new RuntimeException('Leaked transaction.');
    }
    $name = 'wv:admins:'.substr(hash('sha256', DB::connection()->getDatabaseName()), 0, 48);
    $owner = DB::selectOne('SELECT IS_USED_LOCK(?) AS owner', [$name])->owner;
    $connectionId = DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
    if ($owner !== null && (int) $owner === (int) $connectionId) {
        throw new RuntimeException('Membership lock leaked past commit or rollback.');
    }
    DB::disconnect();
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.PHP_EOL);
    exit(1);
}
