<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

try {
    $input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    $app = Tests\Support\TestWorkerBootstrap::boot($input['database']);
    config(['app.key' => $input['key'], 'filesystems.disks.public.root' => $input['public_root']]);
    $user = User::findOrFail($input['user_id']);
    Auth::guard('web')->setUser($user);
    $action = $input['action'];
    $request = Request::create('/profile', 'PATCH', [
        'profile_section' => $action, 'name' => 'Concurrent name',
        'email' => 'changed@example.com', 'current_password' => 'password',
        'password' => $action === 'admin-password' ? 'another-password-123' : 'password',
        'password_confirmation' => 'another-password-123',
    ]);
    if ($action === 'verify') {
        $request = \Illuminate\Foundation\Auth\EmailVerificationRequest::create('/verify-email', 'GET');
        $route = new \Illuminate\Routing\Route('GET', 'verify-email/{id}/{hash}', fn () => null);
        $route->bind(Request::create('/verify-email/'.$user->id.'/'.sha1($user->email)));
        $request->setRouteResolver(fn () => $route);
    }
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession($app['session']->driver());
    $app->instance('request', $request);
    // Reproduce a request that passed session middleware before the competing write.
    app(\App\Services\PasswordSecurityService::class)->bindSession($request->session(), $user);
    app(\App\Http\Middleware\EnsurePasswordSessionIsCurrent::class)->handle($request, fn () => null);
    if ($action === 'verify' && ! $request->authorize()) {
        throw new RuntimeException('Expected initial verification authorization.');
    }
    if ($action !== 'admin-password' && str_starts_with($action, 'admin')) {
        $request->request->remove('password');
    }
    echo 'CONNECTION:'.DB::selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
    echo "BOUND\n";
    flush();

    $probed = false;
    DB::connection()->beforeExecuting(function ($sql, $bindings, $connection) use ($input, $user, &$probed): void {
        if (str_contains($sql, 'from `users`') && str_contains($sql, 'for update')) {
            if ($connection->transactionLevel() < 1 || ! $connection->getPdo()->inTransaction()
                || ! (in_array($user->id, $bindings, true) || in_array($user->email, $bindings, true))) {
                throw new RuntimeException('Expected transactional lock on the fixture User.');
            }
            echo "USER_LOCK_ATTEMPT\n";
            flush();
            if (! $input['pause'] && ! $probed) {
                $probed = true;
                // Probe the exact production SELECT and bindings on the same
                // transaction/connection. PDO avoids recursively invoking this hook.
                // NOWAIT needs no global monitoring privileges and does not roll
                // back the transaction when another session owns the row lock.
                $statement = $connection->getPdo()->prepare($sql.' nowait');
                try {
                    $statement->execute($connection->prepareBindings($bindings));
                    throw new RuntimeException('Expected User row lock conflict.');
                } catch (PDOException $exception) {
                    // MySQL ER_LOCK_NOWAIT only: unrelated SQL errors must fail.
                    if ((int) ($exception->errorInfo[1] ?? 0) !== 3572) {
                        throw $exception;
                    }
                } finally {
                    $statement->closeCursor();
                }
                echo "USER_LOCK_CONFLICT\n";
                flush();
                if (trim(fgets(STDIN)) !== 'continue') {
                    throw new RuntimeException('Expected continue signal.');
                }
                if (! $connection->getPdo()->inTransaction()) {
                    throw new RuntimeException('Lock probe must preserve the transaction.');
                }
            }
        }
    });
    $paused = false;
    $verifiedEvents = 0;
    \Illuminate\Support\Facades\Event::listen(\Illuminate\Auth\Events\Verified::class, function () use (&$verifiedEvents): void {
        $verifiedEvents++;
    });
    DB::listen(function ($query) use ($input, &$paused): void {
        if ($input['pause'] && ! $paused && str_contains($query->sql, 'from `users`') && str_contains($query->sql, 'for update')) {
            $paused = true;
            echo "LOCKED\n";
            flush();
            if (trim(fgets(STDIN)) !== 'release') {
                throw new RuntimeException('Expected release signal.');
            }
        }
    });
    try {
        $security = app(\App\Services\PasswordSecurityService::class);
        if ($action === 'rotate') {
            $security->rotate($user, 'rotated-password-123');
        } elseif ($action === 'reset') {
            $status = $security->resetPassword([
                'email' => $user->email, 'token' => $input['token'],
                'password' => 'rotated-password-123', 'password_confirmation' => 'rotated-password-123',
            ], fn (User $current, string $password) => $security->rotate($current, $password));
            if ($status !== \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
                throw new RuntimeException('Password reset failed.');
            }
        } elseif ($action === 'verify') {
            app(\App\Http\Controllers\Auth\VerifyEmailController::class)($request);
        } elseif (str_starts_with($action, 'admin')) {
            app(\App\Http\Controllers\Admin\AdminProfileController::class)->update($request);
        } elseif ($action === 'delete') {
            app(ProfileController::class)->destroy($request);
        } else {
            app(ProfileController::class)->update($request);
        }
        echo "RESULT:ok\n";
    } catch (\Illuminate\Validation\ValidationException $exception) {
        $field = $action === 'delete' ? 'password' : 'current_password';
        if (! array_key_exists($field, $exception->errors())) {
            throw $exception;
        }
        echo "RESULT:rejected\n";
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
        if ($action !== 'verify' || $exception->getStatusCode() !== 403) {
            throw $exception;
        }
        echo "RESULT:rejected\n";
    }
    echo 'VERIFIED:'.$verifiedEvents.PHP_EOL;
    if (DB::transactionLevel() !== 0 || DB::connection()->getPdo()->inTransaction()) {
        throw new RuntimeException('Leaked transaction.');
    }
    DB::disconnect();
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.PHP_EOL);
    exit(1);
}
