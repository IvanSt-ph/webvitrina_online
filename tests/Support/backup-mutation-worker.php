<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$configuration = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
Tests\TestCase::assertSafeTestDatabaseConfiguration($app->environment(), $configuration['database']);
$app['config']->set('database', $configuration['database']);
$app['config']->set('backup.lock_path', $configuration['lock']);
$app['config']->set('backup.lock_timeout', 15);
Illuminate\Support\Facades\DB::purge();
echo "READY\n";
flush();
$request = Illuminate\Http\Request::create('/test-file-mutation', 'POST');
(new App\Http\Middleware\CoordinateBackupWrites)->handle($request, function () use ($configuration) {
    Illuminate\Support\Facades\DB::transaction(function () use ($configuration) {
        Illuminate\Support\Facades\DB::table('backup_file_reference')->update(['path' => 'new.txt']);
        foreach (['public', 'private/chat-images'] as $directory) {
            file_put_contents($configuration['root'] . '/' . $directory . '/new.txt', 'new image');
            unlink($configuration['root'] . '/' . $directory . '/old.txt');
        }
    });
    echo "MUTATED\n";
});
