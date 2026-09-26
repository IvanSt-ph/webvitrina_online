<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
$configuration = json_decode(trim(fgets(STDIN)), true, flags: JSON_THROW_ON_ERROR);
Tests\TestCase::assertSafeTestDatabaseConfiguration('testing', $configuration['database']);
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app['config']->set('database', $configuration['database']);
$app['config']->set([
    'cache.default' => $configuration['store'],
    'cache.prefix' => $configuration['prefix'],
    'cache.stores.database.connection' => 'mysql',
    'cache.stores.database.lock_connection' => 'mysql',
    'cache.stores.file.path' => $configuration['root'].'/cache',
    'cache.stores.file.lock_path' => $configuration['root'].'/cache',
    'backup.path' => $configuration['root'].'/backups',
    'logging.default' => 'null',
    'queue.default' => 'database',
]);
$app->useStoragePath($configuration['root'].'/storage');
Illuminate\Support\Facades\DB::purge();
Illuminate\Support\Facades\Cache::forgetDriver();

if ($argv[1] === 'write') {
    $event = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())
        ->first(fn ($event) => $event->description === 'scheduler-heartbeat');
    $event->run($app);
    echo 'written';
} elseif ($argv[1] === 'read') {
    Illuminate\Support\Facades\Artisan::call('production:health', ['--json' => true]);
    $report = json_decode(Illuminate\Support\Facades\Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    echo json_encode($report['checks']['scheduler'], JSON_THROW_ON_ERROR);
    exit($report['checks']['scheduler']['ok'] ? 0 : 1);
} elseif ($argv[1] === 'cleanup') {
    Illuminate\Support\Facades\Cache::forget(App\Support\ProductionHealth::SCHEDULER_CACHE_KEY);
    exit(Illuminate\Support\Facades\Cache::has(App\Support\ProductionHealth::SCHEDULER_CACHE_KEY) ? 1 : 0);
} else {
    throw new RuntimeException('Unknown test mode');
}
