<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Models\{Product, User};
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\ValidationException;
use Tests\Support\{ConcurrencyLockProbe, ProductQuotaCreation, TestWorkerBootstrap};

try {
    $input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    // Reject webv3/incorrect credentials before providers or any connection are loaded.
    TestWorkerBootstrap::boot($input['database']);
    config(['app.key' => $input['key'], 'filesystems.disks.public.root' => $input['public_root']]);
    Storage::forgetDisk('public');
    $actor = User::findOrFail($input['actor']);
    echo 'CONNECTION:'.DB::selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;

    if ($input['mode'] === 'hold') {
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
    } elseif ($input['mode'] === 'contend') {
        ConcurrencyLockProbe::install(DB::connection(), 'users');
    }

    try {
        // Establish an older RR snapshot before waiting for another creation to commit.
        // The authoritative check must still see the winning insert.
        DB::transaction(function () use ($input, $actor): void {
            Product::where('user_id', $input['data']['user_id'])->count();
            ProductQuotaCreation::run($actor, $input['data'], $input['path']);
        });
        echo "RESULT:created\n";
    } catch (ValidationException $exception) {
        $field = $input['path'] === 'seller' ? 'product_limit' : 'user_id';
        if (! array_key_exists($field, $exception->errors())) {
            throw $exception;
        }
        echo "RESULT:limited\n";
    }
    if (DB::transactionLevel() !== 0 || DB::connection()->getPdo()->inTransaction()) {
        throw new RuntimeException('Leaked transaction.');
    }
    DB::disconnect();
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage().PHP_EOL);
    exit(1);
}
