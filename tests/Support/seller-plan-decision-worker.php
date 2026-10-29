<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Models\{SellerPlanRequest, User};
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\{ConcurrencyLockProbe, SellerPlanDecision, TestWorkerBootstrap};

try {
    $input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    TestWorkerBootstrap::boot($input['database']);
    config(['app.key' => $input['key']]);
    $admin = User::findOrFail($input['admin']);
    $request = SellerPlanRequest::findOrFail($input['request']);
    echo 'CONNECTION:'.DB::selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
    if ($input['first']) {
        $paused = false;
        DB::listen(function ($query) use (&$paused): void {
            if (! $paused && str_contains($query->sql, 'from `users`') && str_contains($query->sql, 'for update')) {
                $paused = true;
                echo "LOCKED\n";
                flush();
                if (trim(fgets(STDIN)) !== 'release') {
                    throw new RuntimeException('Expected release signal.');
                }
            }
        });
    } else {
        ConcurrencyLockProbe::install(DB::connection(), 'users');
    }
    try {
        DB::transaction(function () use ($input, $admin, $request): void {
            // Force an old RR snapshot as well as the stale route-bound model.
            SellerPlanRequest::whereKey($request->id)->value('status');
            SellerPlanDecision::run($admin, $request, $input['action'], $input['first'] ? 'Winner' : 'Loser');
        });
        echo "RESULT:ok\n";
    } catch (HttpException $exception) {
        if ($exception->getStatusCode() !== 422 || $exception->getMessage() !== 'Эта заявка уже обработана.') {
            throw $exception;
        }
        echo "RESULT:processed\n";
    }
    if (DB::transactionLevel() !== 0 || DB::connection()->getPdo()->inTransaction()) {
        throw new RuntimeException('Leaked transaction.');
    }
    DB::disconnect();
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage().PHP_EOL);
    exit(1);
}
