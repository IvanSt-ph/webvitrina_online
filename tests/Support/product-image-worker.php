<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$configuration = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
Tests\TestCase::assertSafeTestDatabaseConfiguration($app->environment(), $configuration['database']);
$app['config']->set('database', $configuration['database']);
$app['config']->set('filesystems.disks.public', [
    'driver' => 'local', 'root' => storage_path('framework/testing/disks/public'), 'throw' => true,
]);
Illuminate\Support\Facades\DB::purge();
Illuminate\Support\Facades\Storage::forgetDisk('public');
$connection = Illuminate\Support\Facades\DB::connection();
$connection->beforeExecuting(function (string $query): void {
    if (str_contains(strtolower($query), 'for update')) {
        echo "LOCK_QUERY\n";
        flush();
    }
});
$product = App\Models\Product::findOrFail((int) $argv[1]);
$service = app(App\Services\ProductService::class);
$mainImage = Illuminate\Http\UploadedFile::fake()->image('main.jpg', 24, 24);
$galleryImage = Illuminate\Http\UploadedFile::fake()->image('gallery.jpg', 24, 24);
echo 'CONNECTION:' . $connection->selectOne('SELECT CONNECTION_ID() AS id')->id . PHP_EOL;
if ($argv[2] === 'first') {
    $connection->beginTransaction();
}
echo "ATTEMPT\n";
flush();
$updated = $service->update($product, [], $mainImage, [$galleryImage]);
echo 'IMAGE:' . $updated->image . PHP_EOL;
if ($argv[2] === 'first') {
    echo "LOCKED\n";
    flush();
    if (trim(fgets(STDIN)) !== 'release') {
        throw new RuntimeException('Expected release signal');
    }
    $connection->commit();
}
echo "DONE\n";
