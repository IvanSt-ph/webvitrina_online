<?php

namespace Tests\Feature;

use App\Services\BackupWriteBarrier;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BackupWriteBarrierTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/backup-barrier-' . uniqid());
        File::ensureDirectoryExists($this->root);
        config()->set('backup.lock_path', $this->root . '/barrier.lock');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_backup_waits_for_inflight_writer_and_process_death_releases_lock(): void
    {
        $script = '$h=fopen($argv[1],"c+b");flock($h,LOCK_SH);echo "READY\n";flush();usleep(600000);fclose($h);';
        $writer = new Process([PHP_BINARY, '-r', $script, config('backup.lock_path')]);
        try {
            $writer->start();
            $this->assertTrue($writer->waitUntil(fn ($type, $out) => str_contains($out, 'READY')));
            $started = microtime(true);
            app(BackupWriteBarrier::class)->run(fn () => null, exclusive: true);
            $this->assertGreaterThan(0.3, microtime(true) - $started);
            $this->assertSame(0, $writer->wait());
        } finally {
            $writer->stop();
        }
        $backup = new Process([PHP_BINARY, '-r', '$h=fopen($argv[1],"c+b");flock($h,LOCK_EX);echo "READY\n";flush();sleep(30);', config('backup.lock_path')]);
        try {
            $backup->start();
            $this->assertTrue($backup->waitUntil(fn ($type, $out) => str_contains($out, 'READY')));
        } finally {
            $backup->stop(0);
        }
        app(BackupWriteBarrier::class)->run(fn () => $this->assertTrue(true), exclusive: true);
    }

    public function test_errors_release_the_lock_and_nested_backup_fails_without_upgrade(): void
    {
        $barrier = app(BackupWriteBarrier::class);
        try {
            $barrier->run(fn () => throw new \Error('injected'), exclusive: true);
        } catch (\Error $exception) {
            $this->assertSame('injected', $exception->getMessage());
        }
        $other = new BackupWriteBarrier;
        $other->run(fn () => $this->assertTrue(true), exclusive: true);
        try {
            $barrier->run(fn () => $barrier->run(fn () => null, exclusive: true));
            $this->fail('Lock upgrade must not be attempted.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('active file mutation', $exception->getMessage());
        }
        $other->run(fn () => $this->assertTrue(true), exclusive: true);
    }

    public function test_read_requests_remain_available_during_backup(): void
    {
        app(BackupWriteBarrier::class)->run(function () {
            $request = \Illuminate\Http\Request::create('/catalog', 'GET');
            $this->assertSame('read', (new \App\Http\Middleware\CoordinateBackupWrites)->handle($request, fn () => 'read'));
        }, exclusive: true);
    }

    public function test_mutation_timeout_returns_retryable_response_without_running_controller(): void
    {
        config(['backup.lock_timeout' => 0]);
        $handle = fopen(config('backup.lock_path'), 'c+b');
        try {
            $this->assertTrue(flock($handle, LOCK_EX | LOCK_NB));
            $request = \Illuminate\Http\Request::create('/upload', 'POST');
            $response = (new \App\Http\Middleware\CoordinateBackupWrites)->handle($request, function () {
                $this->fail('Controller must not run without the barrier.');
            });
            $this->assertSame(503, $response->getStatusCode());
            $this->assertSame('60', $response->headers->get('Retry-After'));
        } finally {
            fclose($handle);
        }
        app(BackupWriteBarrier::class)->run(fn () => $this->assertTrue(true), exclusive: true);
    }
}
