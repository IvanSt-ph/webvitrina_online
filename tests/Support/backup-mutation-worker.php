<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';
$configuration = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$app = Tests\Support\TestWorkerBootstrap::boot($configuration['database']);
Tests\Support\TestFileSafety::assertDirectory($configuration['root']);
$app['config']->set('backup.lock_path', $configuration['lock']);
$app['config']->set('backup.lock_timeout', 15);
echo "READY\n";
flush();
$request = Illuminate\Http\Request::create('/test-file-mutation', 'POST');
$response = (new App\Http\Middleware\CoordinateBackupWrites)->handle($request, function () use ($configuration) {
    Illuminate\Support\Facades\DB::transaction(function () use ($configuration) {
        Illuminate\Support\Facades\DB::table('backup_file_reference')->update(['path' => 'new.txt']);
        foreach (['public', 'private/chat-images', 'private/review-images'] as $directory) {
            file_put_contents($configuration['root'] . '/' . $directory . '/new.txt', 'new image');
            unlink($configuration['root'] . '/' . $directory . '/old.txt');
        }
    });
    return new Symfony\Component\HttpFoundation\Response('', 204);
});
if (! $response instanceof Symfony\Component\HttpFoundation\Response) {
    fwrite(STDERR, "Backup mutation failed: middleware returned no HTTP response.\n");
    exit(1);
}
if (! $response->isSuccessful()) {
    fwrite(STDERR, 'Backup mutation failed: HTTP '.$response->getStatusCode().': '.$response->getContent()."\n");
    exit(1);
}
echo "MUTATED\n";
flush();
