<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Http\Controllers\ChatController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

try {
    $input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    $app = Tests\Support\TestWorkerBootstrap::boot($input['database']);
    config([
        'app.key' => $input['key'],
        'filesystems.disks.local.root' => $input['private_root'],
        'filesystems.disks.public.root' => $input['public_root'],
    ]);
    $sender = User::findOrFail($input['sender_id']);
    $target = User::findOrFail($input['target_id']);
    $conversation = Conversation::findOrFail($input['conversation_id']);
    Auth::guard('web')->setUser($sender);
    $request = Request::create('/chat', 'POST', ['body' => 'concurrent image'], files: [
        'image' => UploadedFile::fake()->image('chat.jpg'),
    ]);
    $request->setUserResolver(fn () => $sender);
    $request->setLaravelSession($app['session']->driver());
    $app->instance('request', $request);
    echo 'CONNECTION:'.DB::selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
    echo "BOUND\n";
    flush();

    $probed = false;
    DB::connection()->beforeExecuting(function ($sql, $bindings, $connection) use ($input, &$probed): void {
        if ($input['pause'] || $probed || ! str_contains($sql, 'from `'.$input['conflict_table'].'`')
            || ! str_contains($sql, 'for update')) {
            return;
        }
        $probed = true;
        if (! $connection->getPdo()->inTransaction()) {
            throw new RuntimeException('Expected a real transaction.');
        }
        // Probe the exact production SELECT on the same session and transaction.
        $statement = $connection->getPdo()->prepare($sql.' nowait');
        try {
            $statement->execute($connection->prepareBindings($bindings));
            throw new RuntimeException('Expected MySQL lock conflict.');
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
    $paused = false;
    DB::listen(function ($query) use ($input, &$paused): void {
        if ($input['pause'] && ! $paused && str_contains($query->sql, 'from `conversations`')
            && str_contains($query->sql, 'for update')) {
            $paused = true;
            echo "LOCKED\n";
            flush();
            if (trim(fgets(STDIN)) !== 'release') {
                throw new RuntimeException('Expected release signal.');
            }
        }
    });

    try {
        DB::transaction(function () use ($input, $app, $request, $conversation, $target): void {
            if ($input['pause']) {
                // Announce DB completion before deferred notification delivery:
                // worker 2 may already own the recipient User row at this point.
                $done = static function (): void {
                    echo "TX_DONE\n";
                    flush();
                };
                DB::afterCommit($done);
                foreach (app('db.transactions')->getPendingTransactions() as $transaction) {
                    if ($transaction->connection === DB::connection()->getName()) {
                        $transaction->addCallbackForRollback($done);
                    }
                }
            }
            // Exercise locking reads with an already established RR snapshot.
            Conversation::whereKey($conversation->id)->first();
            match ($input['action']) {
                'send' => $app->make($input['admin'] ? AdminChatController::class : ChatController::class)->store($request, $conversation),
                'conversation' => $conversation->delete(),
                'account' => $target->delete(),
            };
            if ($input['rollback']) {
                throw new RuntimeException('intentional rollback');
            }
        });
        echo "RESULT:ok\n";
    } catch (ModelNotFoundException $exception) {
        if (! in_array($exception->getModel(), [User::class, Conversation::class], true)) {
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
