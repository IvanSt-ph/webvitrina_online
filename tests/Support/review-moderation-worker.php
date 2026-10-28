<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\ReviewController;
use App\Models\{Product, Review, User};
use Illuminate\Http\{Request, UploadedFile};
use Illuminate\Support\Facades\{Auth, DB, Storage};
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\{ConcurrencyLockProbe, TestWorkerBootstrap};

try {
    $input = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
    TestWorkerBootstrap::boot($input['database']);
    config(['app.key' => $input['key'], 'filesystems.disks.public.root' => $input['public_root'],
        'filesystems.disks.review_images.root' => $input['review_root']]);
    Storage::forgetDisk('public');
    Storage::forgetDisk('review_images');
    $review = Review::findOrFail($input['review']);
    $product = Product::findOrFail($review->product_id);
    $user = User::findOrFail($input[$input['actor']]);
    Auth::guard('web')->setUser($user);
    $files = [];
    if ($input['actor'] === 'buyer') {
        $data = ['body' => 'Inspected text', 'rating' => 2];
        if ($input['change'] === 'images') {
            $files = ['images' => [UploadedFile::fake()->image('new.jpg', 24, 24)]];
        } else {
            $data[$input['change']] = $input['change'] === 'rating' ? 5 : 'Changed text';
        }
    } else {
        $data = $input['bulk']
            ? ['ids' => [$review->id], 'action' => 'approve', 'fingerprints' => [$review->id => $input['fingerprint']]]
            : ['content_fingerprint' => $input['fingerprint']];
    }
    $request = Request::create('/reviews', 'POST', $data, files: $files);
    $request->setUserResolver(fn () => $user);
    $request->setLaravelSession(app('session')->driver());
    app()->instance('request', $request);
    echo 'CONNECTION:'.DB::selectOne('SELECT CONNECTION_ID() AS id')->id.PHP_EOL;
    if ($input['first']) {
        $paused = false;
        DB::listen(function ($query) use (&$paused): void {
            if (! $paused && str_contains($query->sql, 'from `reviews`') && str_contains($query->sql, 'for update')) {
                $paused = true;
                echo "LOCKED\n";
                flush();
                if (trim(fgets(STDIN)) !== 'release') {
                    throw new RuntimeException('Expected release signal.');
                }
            }
        });
    } else {
        ConcurrencyLockProbe::install(DB::connection(), 'reviews');
    }
    try {
        if ($input['actor'] === 'buyer') {
            app(ReviewController::class)->store($request, $product);
        } elseif ($input['bulk']) {
            app(AdminReviewController::class)->bulk($request);
        } else {
            app(AdminReviewController::class)->approve($request, $review);
        }
        echo "RESULT:ok\n";
    } catch (HttpException $exception) {
        if ($exception->getStatusCode() !== 409 || $input['actor'] !== 'admin' || $input['first']) {
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
