<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Models\{Product, User};
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\{ConcurrencyLockProbe, ProductQuotaCreation, ProductQuotaTransfer, TestWorkerBootstrap};

try {
    $input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    TestWorkerBootstrap::boot($input['database']);
    config(['app.key' => $input['key'], 'filesystems.disks.public.root' => $input['public_root']]);
    Storage::forgetDisk('public');
    $actor = User::findOrFail($input['actor']);
    $product = isset($input['product']) ? Product::findOrFail($input['product']) : null;
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
        DB::transaction(function () use ($input, $actor, $product): void {
            // Retain a pre-winner RR snapshot: quota must use a locking current read.
            Product::where('user_id', $input['data']['user_id'])->count();
            if ($product) {
                ProductQuotaTransfer::run($actor, $product, $input['data']['user_id']);
            } else {
                ProductQuotaCreation::run($actor, $input['data'], 'service');
            }
        });
        echo "RESULT:ok\n";
    } catch (ValidationException $exception) {
        if (! array_key_exists('user_id', $exception->errors())) {
            throw $exception;
        }
        echo "RESULT:limited\n";
    } catch (HttpException $exception) {
        if ($exception->getStatusCode() !== 409) {
            throw $exception;
        }
        echo "RESULT:stale\n";
    }
    if (DB::transactionLevel() !== 0 || DB::connection()->getPdo()->inTransaction()) {
        throw new RuntimeException('Leaked transaction.');
    }
    DB::disconnect();
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage().PHP_EOL);
    exit(1);
}
