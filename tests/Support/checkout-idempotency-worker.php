<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Database\SafeDatabaseTransactionsManager;
use App\Http\Controllers\CheckoutController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Support\TestDatabaseSafety;
use Tests\Support\TestWorkerBootstrap;

$input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
$app = TestWorkerBootstrap::boot($input['database']);
if (! app('db.transactions') instanceof SafeDatabaseTransactionsManager) {
    throw new RuntimeException('Production manager required.');
}
$connection = DB::connection();
$pdo = TestDatabaseSafety::assertConnected(app()->environment(), config('database'), $connection);
echo 'CONNECTION:'.$connection->selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
echo "BOOTED\n";
flush();
auth()->setUser(User::findOrFail($input['buyer']));
session()->start();
session()->put($input['snapshot']);
$mode = $input['mode'];
$armed = true;
if ($mode === 'first') {
    if (str_starts_with($input['outcome'], 'unknown ')) {
        // Exercise both durable outcomes while Laravel never receives a successful
        // PDO acknowledgement. This is a controlled boundary fault, not a network test.
        Illuminate\Support\Facades\Event::listen(Illuminate\Database\Events\TransactionCommitting::class,
            function () use ($pdo, $input, &$armed) {
                if ($armed) { return; } // Ignore the preliminary result lookup transaction.
                if ($input['outcome'] === 'unknown committed') { $pdo->commit(); }
                else { $pdo->rollBack(); }
                throw new RuntimeException('lost commit acknowledgement');
            });
    }
    DB::listen(function ($query) use (&$armed, $input) {
        if (! $armed || ! str_starts_with($query->sql, 'insert into `order_items`')) {
            return;
        }
        $armed = false;
        echo "LOCKED\n";
        flush();
        if (trim(fgets(STDIN)) !== 'release') {
            throw new RuntimeException('Expected release');
        }
        echo "RELEASE_RECEIVED\n";
        flush();
        if ($input['outcome'] === 'rollback') {
            echo "ROLLBACK_REQUESTED\n";
            flush();
            throw new RuntimeException('injected rollback');
        }
        if ($input['outcome'] === 'crash after commit') {
            DB::afterCommit(function () {
                echo "COMMITTED\n";
                flush();
                fgets(STDIN); // Parent terminates this process before any response.
            });
        }
    });
} else {
    $table = $input['different'] ? 'users' : 'checkout_attempts';
    $connection->beforeExecuting(function ($sql, $bindings, $connection) use (&$armed, $table, $pdo) {
        if (! $armed || ! str_contains($sql, 'from `'.$table.'`') || ! str_contains(strtolower($sql), 'for update')) {
            return;
        }
        $armed = false;
        TestDatabaseSafety::assertConnected(app()->environment(), config('database'), $connection, $pdo);
        $statement = $pdo->prepare($sql.' NOWAIT');
        try {
            $statement->execute($connection->prepareBindings($bindings));
            throw new RuntimeException('Expected lock conflict');
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 3572) {
                throw $exception;
            }
        } finally {
            $statement->closeCursor();
        }
        echo "LOCK_CONFLICT\n";
        flush();
        if (trim(fgets(STDIN)) !== 'continue') {
            throw new RuntimeException('Expected continue');
        }
        if (! $pdo->inTransaction() || $connection->transactionLevel() < 1) {
            throw new RuntimeException('Lock probe must preserve the production transaction');
        }
        echo "CONTINUING\n";
        flush();
    });
}

$request = Request::create('/checkout/create', 'POST', [
    'payment_method' => 'cash', 'delivery_method' => 'pickup',
    'checkout_token' => $input['snapshot']['checkout_token'],
]);
$request->setLaravelSession(session()->driver());
try {
    app(CheckoutController::class)->create($request);
    echo "DONE\n";
} catch (Illuminate\Validation\ValidationException $exception) {
    if (! $input['different'] || ! isset($exception->errors()['stock'])) {
        throw $exception;
    }
    echo "STOCK_REJECTED\n";
} catch (RuntimeException $exception) {
    if ($mode === 'first' && str_starts_with($input['outcome'], 'unknown ')
        && $exception->getMessage() === 'lost commit acknowledgement') {
        echo "UNKNOWN\n";
    } elseif ($mode === 'first' && $input['outcome'] === 'rollback' && $exception->getMessage() === 'injected rollback') {
        echo "ROLLED_BACK\n";
    } else {
        throw $exception;
    }
} finally {
    echo "CLEANUP\n";
    flush();
    TestDatabaseSafety::assertConnected(app()->environment(), config('database'), $connection, $pdo);
    while ($connection->transactionLevel() > 0) {
        $connection->rollBack();
    }
    $connection->disconnect();
    echo "CLOSED\n";
    flush();
}
