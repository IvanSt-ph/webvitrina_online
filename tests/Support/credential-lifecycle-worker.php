<?php

// Controlled independent MySQL session; configuration is accepted only for the
// same guarded test database as the parent suite. Never run against webv3.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
$app = Tests\Support\TestWorkerBootstrap::boot($input['database']);
$app['config']->set('app.key', $input['key']);
$app['config']->set('hashing', $input['hashing']);
$app['config']->set('session.driver', 'array');
$app['config']->set('cache.default', 'array');

use App\Models\User;
use App\Services\PasswordSecurityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

$stage = 'initializing';
try {
    $service = $app->make(PasswordSecurityService::class);
    $snapshot = User::findOrFail($input['user_id']);
    echo 'CONNECTION:'.DB::selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
    flush();

    $paused = false;
    $userLockAcquired = false;
    DB::connection()->beforeExecuting(function ($sql, $bindings, $connection) use ($snapshot, &$stage): void {
        if (str_contains($sql, 'from `users`') && str_contains($sql, 'for update')) {
            $stage = 'before-user-lock';
            if ($connection->transactionLevel() < 1 || ! $connection->getPdo()->inTransaction()) {
                throw new RuntimeException('User lock must belong to a transaction');
            }
            if (! in_array($snapshot->getKey(), $bindings, true) && ! in_array($snapshot->email, $bindings, true)) {
                throw new RuntimeException('User lock must target the fixture');
            }
            // ATTEMPT alone preceded validation/guard initialization. This marker
            // proves the real business query is about to execute in a transaction.
            echo "USER_LOCK_ATTEMPT\n";
            flush();
        }
    });
    DB::listen(function ($query) use ($input, &$paused, &$userLockAcquired, &$stage): void {
        if (! str_contains($query->sql, 'from `users`') || ! str_contains($query->sql, 'for update')) {
            return;
        }
        $userLockAcquired = true;
        $stage = 'user-lock-acquired';
        echo "USER_LOCK_ACQUIRED\n";
        flush();
        if ($input['pause'] && ! $paused) {
            $paused = true;
            $stage = 'waiting-for-release';
            echo "LOCKED\n";
            flush();
            if (trim(fgets(STDIN)) !== 'release') {
                throw new RuntimeException('Expected release signal');
            }
            $stage = 'released';
        }
    });

    echo "ATTEMPT\n";
    flush();
    $result = [];
    try {
        switch ($input['action']) {
            case 'login':
                $request = App\Http\Requests\Auth\LoginRequest::create('/login', 'POST', [
                    'login' => $snapshot->email, 'password' => 'password',
                ]);
                $request->setContainer($app);
                $request->authenticate();
                $result = ['status' => 'logged-in'];
                break;
            case 'rotate':
                $service->rotate($snapshot, 'rotated-password');
                $result = ['status' => 'rotated'];
                break;
            case 'rollback':
                try {
                    DB::transaction(function () use ($service, $snapshot): void {
                        $service->rotate($snapshot, 'rolled-back-password');
                        throw new RuntimeException('intentional rollback');
                    });
                } catch (RuntimeException $exception) {
                    if ($exception->getMessage() !== 'intentional rollback') {
                        throw $exception;
                    }
                    $result = ['status' => 'rolled-back'];
                }
                break;
            case 'issue':
                $result = ['status' => 'issued', 'credential' => $service->issueRememberedDevice($snapshot)];
                break;
            case 'redeem':
                $user = $service->rememberedUser($input['credential']['selector'], $input['credential']['token']);
                $result = ['status' => $user ? 'authorized' : 'rejected', 'hash' => $user?->password];
                break;
            case 'reset':
                $status = $service->resetPassword([
                    'email' => $snapshot->email, 'token' => $input['reset_token'], 'password' => 'reset-password',
                ], fn (User $user, string $password) => $service->rotate($user, $password));
                $result = ['status' => $status];
                break;
            default:
                throw new RuntimeException('Unknown worker action');
        }
    } catch (ValidationException $exception) {
        // An invalid fixture, throttle or swallowed query failure before acquiring
        // the lock is NOT proof of credential revocation after serialization.
        if (! $userLockAcquired) {
            throw new RuntimeException('Validation failed before acquiring the user lock', previous: $exception);
        }
        $result = ['status' => 'stale'];
    }
    if (DB::transactionLevel() !== 0 || DB::connection()->getPdo()->inTransaction()) {
        throw new RuntimeException('Leaked transaction');
    }
    echo 'RESULT:'.json_encode($result, JSON_THROW_ON_ERROR).PHP_EOL;
    DB::disconnect();
} catch (Throwable $exception) {
    // Do not print connection credentials, SQL bindings or reset/device tokens.
    fwrite(STDERR, $exception::class.'; stage='.$stage
        .'; previous='.($exception->getPrevious() ? $exception->getPrevious()::class : 'none').PHP_EOL);
    exit(1);
}
