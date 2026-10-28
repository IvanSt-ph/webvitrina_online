<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Models\{Product, User};
use App\Services\ProductService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\{DB, Gate, Storage};
use Tests\Support\{ConcurrencyLockProbe, SellerProductMutation, TestWorkerBootstrap};

try {
    $input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    TestWorkerBootstrap::boot($input['database']);
    config(['app.key' => $input['key'], 'filesystems.disks.public.root' => $input['public_root']]);
    Storage::forgetDisk('public');
    $seller = User::findOrFail($input['seller']);
    $product = Product::findOrFail($input['product']);
    // Both requests retain their original route model while the competing write commits.
    if ($input['actor'] === 'seller') {
        Gate::forUser($seller)->authorize($input['action'] === 'delete' ? 'delete' : 'update', $product);
    }
    echo 'CONNECTION:'.DB::selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
    if (! $input['first']) {
        ConcurrencyLockProbe::install(DB::connection(), 'products');
    } else {
        $paused = false;
        DB::listen(function ($query) use (&$paused): void {
            if (! $paused && str_contains($query->sql, 'from `products`') && str_contains($query->sql, 'for update')) {
                $paused = true;
                echo "LOCKED\n";
                flush();
                if (trim(fgets(STDIN)) !== 'release') {
                    throw new RuntimeException('Expected release signal.');
                }
            }
        });
    }
    try {
        if ($input['actor'] === 'admin') {
            $data = $input['change'] === 'block'
                ? ['status' => Product::STATUS_BLOCKED]
                : ['user_id' => $input['new_owner']];
            app(ProductService::class)->update($product, $data, actor: User::findOrFail($input['admin']));
        } else {
            SellerProductMutation::run($seller, $product, $input['action']);
        }
        echo "RESULT:ok\n";
    } catch (AuthorizationException $exception) {
        if ($input['actor'] !== 'seller' || $input['change'] !== 'reassign' || $input['first']) {
            throw $exception;
        }
        echo "RESULT:forbidden\n";
    }
    if (DB::transactionLevel() !== 0 || DB::connection()->getPdo()->inTransaction()) {
        throw new RuntimeException('Leaked transaction.');
    }
    DB::disconnect();
} catch (Throwable $exception) {
    fwrite(STDERR, $exception::class.': '.$exception->getMessage().PHP_EOL);
    exit(1);
}
