<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Models\{Product, SellerPlanRequest, User};
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Validation\ValidationException;
use Tests\Support\{ConcurrencyLockProbe, ProductQuotaCreation, ProductQuotaTransfer, SellerPlanAssignment, TestWorkerBootstrap};

try {
    $input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    TestWorkerBootstrap::boot($input['database']);
    config(['app.key' => $input['key'], 'filesystems.disks.public.root' => $input['public_root']]);
    Storage::forgetDisk('public');
    $admin = User::findOrFail($input['admin']);
    $seller = User::findOrFail($input['seller']);
    $planRequest = $input['path'] === 'request' ? SellerPlanRequest::findOrFail($input['plan_request']) : null;
    $product = Product::findOrFail($input['product']);
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
        DB::transaction(function () use ($input, $admin, $seller, $planRequest, $product): void {
            // Deliberately retain an older RR snapshot across the seller lock wait.
            Product::where('user_id', $seller->id)->count();
            match ($input['action']) {
                'plan' => SellerPlanAssignment::run($admin, $seller, 'starter', $planRequest),
                'create' => ProductQuotaCreation::run($seller, $input['data'], 'service'),
                'transfer' => ProductQuotaTransfer::run($admin, $product, $seller->id),
            };
        });
        echo "RESULT:ok\n";
    } catch (ValidationException $exception) {
        $field = $input['action'] === 'plan' ? ($input['path'] === 'request' ? 'requested_plan' : 'seller_plan') : 'user_id';
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
