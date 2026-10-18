<?php

// Independent controller execution, never a production maintenance command.
require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Http\Controllers\UserAddressController;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

$stage = 'input';
try {
    $input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    // Check before bootstrapping providers or opening any connection.
    Tests\TestCase::assertSafeTestDatabaseConfiguration('testing', $input['database']);
    putenv('APP_ENV=testing');
    $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
    $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
    $app->afterBootstrapping(Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) use ($input): void {
        Tests\TestCase::assertSafeTestDatabaseConfiguration($app->environment(), $input['database']);
        // No alternative connection can accidentally resolve to a production DB.
        $database = $input['database'];
        $database['connections'] = ['mysql' => $database['connections']['mysql']];
        $app['config']->set('database', $database);
        $app['config']->set('app.key', $input['key']);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('mail.default', 'array');
    });
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    Tests\TestCase::assertSafeTestDatabaseConfiguration($app->environment(), config('database'));

    $stage = 'binding';
    $user = User::findOrFail($input['user_id']);
    Auth::guard('web')->setUser($user);
    $bound = isset($input['address_id']) ? UserAddress::findOrFail($input['address_id']) : null;
    $request = Illuminate\Http\Request::create('/addresses', 'POST', $input['data']);
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession($app['session']->driver());
    $app->instance('request', $request);
    echo 'CONNECTION:'.DB::selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
    echo "BOUND\n";
    flush();

    DB::connection()->beforeExecuting(function ($sql, $bindings, $connection) use ($user, &$stage): void {
        if (str_contains($sql, 'from `users`') && str_contains($sql, 'for update')) {
            $stage = 'user-lock-attempt';
            if ($connection->transactionLevel() < 1 || ! $connection->getPdo()->inTransaction()
                || ! in_array($user->id, $bindings, true)) {
                throw new RuntimeException('Expected transactional lock on the fixture user');
            }
            echo "USER_LOCK_ATTEMPT\n";
            flush();
        }
    });
    $paused = false;
    DB::listen(function ($query) use ($input, &$paused, &$stage): void {
        if (str_contains($query->sql, 'from `users`') && str_contains($query->sql, 'for update')) {
            $stage = 'user-lock-acquired';
            echo "USER_LOCK_ACQUIRED\n";
            if ($input['pause'] && ! $paused) {
                $paused = true;
                echo "LOCKED\n";
                flush();
                if (trim(fgets(STDIN)) !== 'release') {
                    throw new RuntimeException('Missing release signal');
                }
            }
            flush();
        }
    });
    $controller = $app->make(UserAddressController::class);
    $operation = fn () => match ($input['action']) {
        'store' => $controller->store($request),
        'update', 'update-keep' => $controller->update($request, $bound),
        'destroy' => $controller->destroy($bound),
        'default' => $controller->makeDefault($bound),
        default => throw new RuntimeException('Unknown worker action'),
    };
    try {
        if ($input['rollback'] ?? false) {
            DB::transaction(function () use ($operation): void {
                $operation();
                throw new RuntimeException('intentional rollback');
            });
        } else {
            $operation();
        }
        $status = 'ok';
    } catch (ModelNotFoundException $exception) {
        if ($exception->getModel() !== UserAddress::class) {
            throw $exception;
        }
        $status = 'missing';
    } catch (RuntimeException $exception) {
        if (! ($input['rollback'] ?? false) || $exception->getMessage() !== 'intentional rollback') {
            throw $exception;
        }
        $status = 'rolled-back';
    }
    if (DB::transactionLevel() !== 0 || DB::connection()->getPdo()->inTransaction()) {
        throw new RuntimeException('Leaked transaction');
    }
    echo 'RESULT:'.json_encode(['status' => $status], JSON_THROW_ON_ERROR).PHP_EOL;
    DB::disconnect();
} catch (Throwable $exception) {
    // Avoid emitting configuration credentials, SQL bindings or personal data.
    fwrite(STDERR, $exception::class.'; stage='.$stage.PHP_EOL);
    exit(1);
}
