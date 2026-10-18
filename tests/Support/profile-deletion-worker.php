<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Http\Controllers\ProfileController;
use App\Models\User;
use App\Services\PhoneAssignmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

try {
    $input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    $app = Tests\Support\TestWorkerBootstrap::boot($input['database']);
    config(['app.key' => $input['key'], 'filesystems.disks.public.root' => $input['public_root']]);
    $user = User::findOrFail($input['user_id']);
    Auth::guard('web')->setUser($user);
    $request = Request::create('/profile', 'PATCH', [
        'profile_section' => $input['action'] === 'phone' ? 'phone' : 'personal',
        'name' => 'Concurrent name', 'phone' => '+37377999112',
    ], files: $input['action'] === 'avatar' ? ['avatar' => UploadedFile::fake()->image('avatar.jpg')] : []);
    if ($input['action'] === 'avatar') {
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    }
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession($app['session']->driver());
    $app->instance('request', $request);
    echo 'CONNECTION:'.DB::selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
    echo "BOUND\n";
    flush();

    $probed = false;
    DB::connection()->beforeExecuting(function ($sql, $bindings, $connection) use ($input, $user, &$probed): void {
        if (str_contains($sql, 'from `users`') && str_contains($sql, 'for update')) {
            if ($connection->transactionLevel() < 1 || ! $connection->getPdo()->inTransaction()
                || ! in_array($user->id, $bindings, true)) {
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
        match ($input['action']) {
            'delete' => $user->delete(),
            'service' => $app->make(PhoneAssignmentService::class)->updateUser($user, '+37377999112', ['name' => 'Concurrent name']),
            default => $app->make(ProfileController::class)->update($request),
        };
        echo "RESULT:ok\n";
    } catch (ModelNotFoundException $exception) {
        if ($exception->getModel() !== User::class) {
            throw $exception;
        }
        echo "RESULT:deleted\n";
    }
    if (DB::transactionLevel() !== 0 || DB::connection()->getPdo()->inTransaction()) {
        throw new RuntimeException('Leaked transaction.');
    }
    DB::disconnect();
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.PHP_EOL);
    exit(1);
}
