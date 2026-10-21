<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

try {
    $input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    // Existing allowlist and connected-PDO checks reject webv3 before any work.
    $app = Tests\Support\TestWorkerBootstrap::boot($input['database']);
    config(['app.key' => $input['key'], 'filesystems.disks.public.root' => $input['public_root']]);
    $actor = User::findOrFail($input['actor_id']);
    $target = User::findOrFail($input['target_id']); // Deliberately stale route binding.
    $authenticated = $input['action'] === 'profile' ? $target : $actor;
    Auth::guard('web')->setUser($authenticated);
    $request = Request::create('/admin/users', 'POST', [
        'name' => $target->name, 'email' => $target->email, 'role' => $target->role, 'phone' => $target->phone,
    ], files: ['avatar' => UploadedFile::fake()->image('avatar.jpg')]);
    if ($input['action'] === 'profile') {
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    }
    $request->setUserResolver(fn () => $authenticated);
    $request->setLaravelSession($app['session']->driver());
    $app->instance('request', $request);
    echo 'CONNECTION:'.DB::selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
    flush();

    $probed = false;
    DB::connection()->beforeExecuting(function ($sql, $bindings, $connection) use ($input, &$probed): void {
        if ($input['first'] || $probed || (! str_contains($sql, 'GET_LOCK(')
            && ! (str_contains($sql, 'from `users`') && str_contains($sql, 'for update')))) {
            return;
        }
        $probed = true;
        if (! $connection->getPdo()->inTransaction()) {
            throw new RuntimeException('Expected a real transaction.');
        }
        // Probe before GET_LOCK: membership serialization can precede the User
        // SELECT. Same session/transaction; no extra User lock is retained.
        $statement = $connection->getPdo()->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE NOWAIT');
        try {
            $statement->execute([$input['target_id']]);
            throw new RuntimeException('Expected User row contention.');
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 3572) {
                throw $exception;
            }
        } finally {
            $statement->closeCursor();
        }
        echo "CONFLICT\n";
        flush();
        if (trim(fgets(STDIN)) !== 'continue') {
            throw new RuntimeException('Expected continue signal.');
        }
        echo "CONTINUING\n";
        flush();
    });

    try {
        DB::transaction(function () use ($input, $app, $request, $target): void {
            User::whereKey($target->id)->first(); // Establish a REPEATABLE READ snapshot.
            match ($input['action']) {
                'admin' => $app->make(UserController::class)->update($request, $target),
                'profile' => $app->make(ProfileController::class)->update($request),
                'delete' => $target->delete(),
            };
            echo 'AVATAR:'.(User::withTrashed()->findOrFail($target->id)->avatar ?? 'NULL').PHP_EOL;
            if ($input['first']) {
                echo "HELD\n";
                flush();
                if (trim(fgets(STDIN)) !== 'release') {
                    throw new RuntimeException('Expected release signal.');
                }
            }
            if ($input['rollback']) {
                throw new RuntimeException('intentional rollback');
            }
        });
        echo "RESULT:ok\n";
    } catch (ModelNotFoundException $exception) {
        if ($exception->getModel() !== User::class) {
            throw $exception;
        }
        echo "RESULT:deleted\n";
    } catch (RuntimeException $exception) {
        if (! $input['rollback'] || $exception->getMessage() !== 'intentional rollback') {
            throw $exception;
        }
        echo "RESULT:rollback\n";
    }
    if (DB::transactionLevel() !== 0 || DB::connection()->getPdo()->inTransaction()) {
        throw new RuntimeException('Leaked transaction.');
    }
    DB::disconnect();
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.PHP_EOL);
    exit(1);
}
