<?php

namespace Tests\Feature;

use App\Jobs\QueueHealthCheckJob;
use App\Support\BackupHealth;
use App\Support\DiskSpace;
use App\Support\ProductionHealth;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use ReflectionMethod;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class OperationalHealthTest extends TestCase
{
    use RefreshDatabase;

    private string $root;
    private string $originalStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = storage_path('framework/testing/operational-'.uniqid());
        File::ensureDirectoryExists($this->root);
        $this->originalStorage = storage_path();
        app()->useStoragePath($this->root.'/runtime');
        config(['backup.lock_path' => $this->root.'/barrier.lock']);
        config(['backup.path' => $this->root.'/backups', 'cache.stores.file.path' => $this->root.'/cache', 'cache.stores.file.lock_path' => $this->root.'/cache']);
        File::ensureDirectoryExists(config('backup.path'));
    }

    protected function tearDown(): void
    {
        app()->useStoragePath($this->originalStorage);
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    private function probe(string $method, mixed ...$arguments): array
    {
        return (new ReflectionMethod(ProductionHealth::class, $method))->invoke(null, ...$arguments);
    }

    public function test_scheduled_heartbeat_survives_cache_reopening_and_expires(): void
    {
        config(['cache.default' => 'file']);
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => $event->description === 'scheduler-heartbeat');
        $this->assertSame('* * * * *', $event->expression);
        $this->assertFalse($this->probe('scheduler')['ok']);
        $event->run(app());
        Cache::forgetDriver('file');
        $this->assertTrue($this->probe('scheduler')['ok']);
        $this->travel(6)->minutes();
        $this->assertFalse($this->probe('scheduler')['ok']);
        $this->travelBack();
    }

    public function test_invalid_or_process_local_scheduler_storage_is_not_healthy(): void
    {
        Cache::forever(ProductionHealth::SCHEDULER_CACHE_KEY, now()->toIso8601String());
        $this->assertFalse($this->probe('scheduler')['ok']);
        config(['cache.default' => 'file']);
        Cache::forever(ProductionHealth::SCHEDULER_CACHE_KEY, 'invalid timestamp');
        $this->assertFalse($this->probe('scheduler')['ok']);
    }

    public function test_queue_probe_is_scheduled_regularly(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'queue:health-check'));
        $this->assertNotNull($event);
        $this->assertSame('*/5 * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertTrue($event->runInBackground);
    }

    public function test_enqueued_probe_is_not_worker_success_and_times_out(): void
    {
        config(['queue.default' => 'database', 'cache.default' => 'file']);
        Queue::fake();
        $this->artisan('queue:health-check --timeout=1')->assertFailed();
        Queue::assertPushed(QueueHealthCheckJob::class);
        $this->assertNull(Cache::get(QueueHealthCheckJob::LAST_SUCCESS_CACHE_KEY));
        $this->assertFalse($this->probe('queue')['ok']);
    }

    public function test_worker_execution_missing_stale_and_failed_states(): void
    {
        config(['queue.default' => 'database']);
        $this->assertFalse($this->probe('queue')['ok']);
        $job = new QueueHealthCheckJob('operational-test');
        $job->handle();
        $this->assertTrue($this->probe('queue')['ok']);
        $this->travel(16)->minutes();
        $this->assertFalse($this->probe('queue')['ok']);
        $job->failed(new \RuntimeException('worker failed'));
        $this->assertSame('worker не отвечает', $this->probe('queue')['value']);
        $this->travelBack();
    }

    public function test_failed_jobs_are_detected_and_preserved(): void
    {
        $this->assertTrue($this->probe('failedJobs')['ok']);
        DB::table('failed_jobs')->insert([
            'uuid' => 'operational-test', 'connection' => 'database', 'queue' => 'default',
            'payload' => '{}', 'exception' => 'test failure', 'failed_at' => now(),
        ]);
        $this->assertFalse($this->probe('failedJobs')['ok']);
        $this->assertSame(1, DB::table('failed_jobs')->count());
        config(['queue.failed.table' => 'missing_failed_jobs']);
        $this->assertFalse($this->probe('failedJobs')['ok']);
    }

    public function test_backup_missing_recent_stale_and_corrupt(): void
    {
        $this->assertFalse($this->probe('backup')['ok']);
        $backup = $this->createBackup();
        $this->assertTrue($this->probe('backup')['ok']);
        touch($backup, time() - 31 * 3600);
        clearstatcache();
        $this->assertFalse($this->probe('backup')['ok']);
        touch($backup);
        File::put($backup.'/database.sql.gz', 'corrupted');
        clearstatcache();
        $this->assertFalse($this->probe('backup')['ok']);
        File::delete($backup.'/storage-private-chat-images.tar.gz');
        $this->assertFalse($this->probe('backup')['ok']);
    }

    private function createBackup(): string
    {
        $backup = config('backup.path').'/recent';
        File::ensureDirectoryExists($backup);
        foreach (BackupHealth::REQUIRED_FILES as $file) {
            File::put($backup.'/'.$file, 'fixture');
        }
        File::put($backup.'/manifest.json', json_encode(['version' => 2, 'storage' => [
            'private_chat_images' => ['archive' => 'storage-private-chat-images.tar.gz', 'root' => 'private/chat-images'],
        ]]));
        $hashes = [];
        foreach (array_diff(BackupHealth::REQUIRED_FILES, ['SHA256SUMS']) as $file) {
            $hashes[] = hash_file('sha256', $backup.'/'.$file).' '.$file;
        }
        File::put($backup.'/SHA256SUMS', implode("\n", $hashes));

        return $backup;
    }

    public function test_scheduler_and_health_command_share_database_and_file_heartbeats_across_processes(): void
    {
        foreach (['database', 'file'] as $store) {
            $configuration = ['database' => config('database'), 'store' => $store,
                'prefix' => 'prod09-'.uniqid().':', 'root' => $this->root];
            try {
                $this->healthProcess('write', $configuration);
                $read = $this->healthProcess('read', $configuration);
                $this->assertTrue(json_decode($read, true, flags: JSON_THROW_ON_ERROR)['ok']);
            } finally {
                $this->healthProcess('cleanup', $configuration);
            }
        }
    }

    private function healthProcess(string $mode, array $configuration): string
    {
        $process = new Process([PHP_BINARY, base_path('tests/Support/health-process.php'), $mode], base_path(), timeout: 25);
        $process->setInput(json_encode($configuration, JSON_THROW_ON_ERROR)."\n");
        try {
            $this->assertSame(0, $process->run(), $process->getOutput().$process->getErrorOutput());

            return $process->getOutput();
        } finally {
            $process->stop(0);
            $this->assertFalse($process->isRunning());
        }
    }

    public function test_dead_database_worker_does_not_accumulate_probe_jobs_and_can_recover(): void
    {
        config(['queue.default' => 'database', 'cache.default' => 'file']);
        $this->artisan('queue:health-check --timeout=1')->assertFailed();
        $this->assertSame(1, DB::table('jobs')->count());
        $this->artisan('queue:health-check --timeout=1')->assertFailed();
        $this->assertSame(1, DB::table('jobs')->count());
        $this->assertFalse($this->probe('queue')['ok']);
        // Run the real database queue worker against the test transaction's job.
        $this->artisan('queue:work database --once --sleep=0 --tries=1')->assertSuccessful();
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertTrue($this->probe('queue')['ok']);
        $this->assertSame(0, DB::table('failed_jobs')->count());
    }

    public function test_queue_dispatch_failure_and_nonpersistent_cache_are_detectable(): void
    {
        config(['queue.default' => 'database']);
        $this->artisan('queue:health-check --timeout=1')
            ->expectsOutput('Queue probe requires a shared persistent cache store.')->assertFailed();
        config(['cache.default' => 'file', 'queue.connections.database.table' => 'missing_probe_jobs']);
        $this->artisan('queue:health-check --timeout=1')
            ->expectsOutput('Queue health-check unavailable: dispatch or cache failed.')->assertFailed();
        $this->assertNotNull(Cache::get(QueueHealthCheckJob::LAST_FAILURE_CACHE_KEY));
    }

    public function test_dispatch_lock_prevents_a_concurrent_probe_from_adding_a_job(): void
    {
        config(['queue.default' => 'database', 'cache.default' => 'file']);
        $lock = Cache::lock('queue-health-check:dispatch:'.hash('sha256', 'database'), 60);
        $this->assertTrue($lock->get());
        try {
            $this->artisan('queue:health-check --timeout=1')
                ->expectsOutput('Another queue probe is dispatching.')->assertFailed();
            $this->assertSame(0, DB::table('jobs')->count());
        } finally {
            $lock->release();
        }
    }

    public function test_fully_healthy_report_and_cli_with_isolated_runtime_files(): void
    {
        $originalStorage = storage_path();
        $originalPublic = public_path();
        $link = $this->root.'/public/storage';
        try {
            app()->useStoragePath($this->root.'/storage');
            app()->usePublicPath($this->root.'/public');
            foreach (['app/public', 'app/private', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $directory) {
                File::ensureDirectoryExists(storage_path($directory));
            }
            File::ensureDirectoryExists(public_path());
            File::link(storage_path('app/public'), $link);
            config(['logging.default' => 'daily', 'logging.channels.daily.path' => storage_path('logs/laravel.log'),
                'logging.channels.daily.days' => 14, 'queue.default' => 'database', 'cache.default' => 'file']);
            $this->createBackup();
            Cache::forever(ProductionHealth::SCHEDULER_CACHE_KEY, now()->toIso8601String());
            (new QueueHealthCheckJob('healthy'))->handle();
            $this->mock(DiskSpace::class, fn ($mock) => $mock->shouldReceive('free')->andReturn(2147483648.0));
            $checks = (new ProductionHealth)->operational();
            foreach ($checks as $name => $check) {
                $this->assertSame(ProductionHealth::PASS, $check['status'], $name.': '.$check['detail']);
            }
            $this->assertSame(0, Artisan::call('production:health', ['--json' => true]));
            $this->assertSame('ok', json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR)['status']);
            $this->assertSame([], glob(storage_path('logs/.health-*')));
            $this->assertSame([], glob(base_path('bootstrap/cache/.health-*')));
            File::deleteDirectory(storage_path('framework/views'));
            $this->assertFalse($this->probe('writable')['ok']);
            $this->assertSame(2, Artisan::call('production:health', ['--json' => true]));
        } finally {
            if (is_link($link) || @readlink($link) !== false) {
                // Remove only our junction/link, never recursively traverse it.
                DIRECTORY_SEPARATOR === '\\' ? rmdir($link) : unlink($link);
            }
            app()->useStoragePath($originalStorage);
            app()->usePublicPath($originalPublic);
        }
    }

    public function test_disk_thresholds_and_unavailable_do_not_depend_on_host_free_space(): void
    {
        foreach ([2147483648.0 => ProductionHealth::PASS, 536870912 => ProductionHealth::WARNING, 1024 => ProductionHealth::FAIL] as $bytes => $status) {
            $this->mock(DiskSpace::class, fn ($mock) => $mock->shouldReceive('free')->andReturn((float) $bytes));
            $this->assertSame($status, $this->probe('disk')['status']);
        }
        $this->mock(DiskSpace::class, fn ($mock) => $mock->shouldReceive('free')->andReturn(false));
        $this->assertSame(ProductionHealth::FAIL, $this->probe('disk')['status']);
        $this->mock(DiskSpace::class, fn ($mock) => $mock->shouldReceive('free')->andThrow(new \RuntimeException('unavailable')));
        $this->assertSame(ProductionHealth::FAIL, $this->probe('disk')['status']);
    }

    public function test_unavailable_database_does_not_abort_other_checks(): void
    {
        DB::shouldReceive('select')->once()->andThrow(new \RuntimeException('database unavailable'));
        $report = (new ProductionHealth)->operational();
        $this->assertSame(ProductionHealth::FAIL, $report['database']['status']);
        $this->assertSame(ProductionHealth::FAIL, $report['failed_jobs']['status']);
        $this->assertArrayHasKey('logging', $report);
    }

    public function test_writable_check_detects_missing_directory_without_touching_logs(): void
    {
        config(['backup.path' => $this->root.'/missing']);
        $this->assertFalse($this->probe('writable')['ok']);
    }

    public function test_logging_rejects_single_and_accepts_daily_retention(): void
    {
        config(['logging.default' => 'stack', 'logging.channels.stack.channels' => ['single']]);
        $this->assertFalse($this->probe('logging')['ok']);
        config(['logging.channels.stack.channels' => ['daily'], 'logging.channels.daily.days' => 14]);
        $this->assertTrue($this->probe('logging')['ok']);
        config(['logging.channels.daily.days' => 0]);
        $this->assertFalse($this->probe('logging')['ok']);
        config(['logging.default' => 'null']);
        $this->assertFalse($this->probe('logging')['ok']);
        config(['logging.default' => 'stack', 'logging.channels.stack.channels' => ['stack']]);
        $this->assertFalse($this->probe('logging')['ok']);
    }

    public function test_command_json_exit_codes_for_healthy_warning_and_critical(): void
    {
        foreach (['pass' => 0, 'warning' => 1, 'fail' => 2, 'not_checked' => 2] as $status => $exit) {
            $this->mock(ProductionHealth::class, fn ($mock) => $mock->shouldReceive('operational')->once()->andReturn([
                'fixture' => ['status' => $status, 'ok' => $status === 'pass', 'detail' => 'Fixture result'],
            ]));
            $this->assertSame($exit, Artisan::call('production:health-check', ['--json' => true]));
            $json = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame($status, $json['checks']['fixture']['status']);
            $this->assertSame(['ok', 'warning', 'critical'][$exit], $json['status']);
        }
    }

    public function test_public_liveness_never_exposes_diagnostics_even_in_debug(): void
    {
        $this->get('/up')->assertOk()->assertExactJson(['status' => 'ok']);
        Event::listen(DiagnosingHealth::class, fn () => throw new \RuntimeException('DB_HOST secret /private/backups trace'));
        config(['app.debug' => true]);
        $this->get('/up')->assertStatus(503)->assertExactJson(['status' => 'unavailable']);
        $this->get('/admin/production-checklist')->assertRedirect('/login');
    }
}
