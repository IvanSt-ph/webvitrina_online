<?php

namespace Tests\Unit;

use App\Jobs\QueueHealthCheckJob;
use App\Support\ProductionHealth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class ProductionHealthTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-16 12:00:00');
        config(['cache.default' => 'array']);
        Cache::clear();

        $this->temporaryDirectory = storage_path('framework/testing/production-health');
        File::deleteDirectory($this->temporaryDirectory);
        File::ensureDirectoryExists($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->temporaryDirectory);
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_queue_is_not_green_without_a_recent_worker_confirmation(): void
    {
        config(['queue.default' => 'database']);

        $stopped = $this->invoke('queue', false);

        $this->assertSame(ProductionHealth::FAIL, $stopped['status']);
        $this->assertSame('worker не подтверждён', $stopped['value']);

        Cache::put(QueueHealthCheckJob::LAST_SUCCESS_CACHE_KEY, now()->subMinutes(16)->toIso8601String());

        $stale = $this->invoke('queue', false);

        $this->assertSame(ProductionHealth::FAIL, $stale['status']);
        $this->assertStringContainsString('проверка устарела', $stale['value']);
        $this->assertStringContainsString('16.09.2026 11:44:00', $stale['detail']);
    }

    public function test_queue_reports_a_newer_failed_health_check_immediately(): void
    {
        config(['queue.default' => 'database']);
        Cache::put(QueueHealthCheckJob::LAST_SUCCESS_CACHE_KEY, now()->subMinute()->toIso8601String());
        Cache::put(QueueHealthCheckJob::LAST_FAILURE_CACHE_KEY, now()->toIso8601String());

        $queue = $this->invoke('queue', false);

        $this->assertSame(ProductionHealth::FAIL, $queue['status']);
        $this->assertSame('worker не отвечает', $queue['value']);
        $this->assertStringContainsString('16.09.2026 11:59:00', $queue['detail']);
    }

    public function test_queue_job_records_a_fresh_worker_confirmation(): void
    {
        config(['queue.default' => 'database']);
        Cache::put(QueueHealthCheckJob::LAST_FAILURE_CACHE_KEY, now()->subMinute()->toIso8601String());

        (new QueueHealthCheckJob('test-token'))->handle();

        $queue = $this->invoke('queue', false);

        $this->assertSame(ProductionHealth::PASS, $queue['status']);
        $this->assertSame(now()->toIso8601String(), $queue['last_success_at']);
        $this->assertFalse(Cache::has(QueueHealthCheckJob::LAST_FAILURE_CACHE_KEY));
    }

    public function test_storage_link_distinguishes_correct_and_incorrect_targets(): void
    {
        $correct = $this->invoke('classifyStorageLink', 'public/storage', true, 'C:\\app\\storage\\app\\public', 'C:\\app\\storage\\app\\public');
        $incorrect = $this->invoke('classifyStorageLink', 'public/storage', true, 'C:\\other', 'C:\\app\\storage\\app\\public');

        $this->assertSame(ProductionHealth::PASS, $correct['status']);
        $this->assertSame('correct', $correct['value']);
        $this->assertSame(ProductionHealth::FAIL, $incorrect['status']);
        $this->assertSame('incorrect target', $incorrect['value']);
    }

    public function test_daily_logs_include_recent_errors_from_the_dated_file(): void
    {
        $basePath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'laravel.log';
        $dailyPath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'laravel-2026-09-16.log';
        File::put($dailyPath, "[2026-09-16 11:55:00] production.ERROR: daily failure\n");
        config([
            'logging.default' => 'daily',
            'logging.channels.daily.driver' => 'daily',
            'logging.channels.daily.path' => $basePath,
        ]);

        $errors = $this->invoke('recentErrors');

        $this->assertFalse($errors['ok']);
        $this->assertSame('1 записей', $errors['value']);
        $this->assertStringContainsString('1 файлов', $errors['detail']);
    }

    public function test_database_failure_does_not_crash_the_health_report(): void
    {
        config(['queue.default' => 'database']);
        DB::shouldReceive('select')->once()->andThrow(new RuntimeException('database unavailable'));

        $health = ProductionHealth::make();
        $database = collect($health['cards'])->firstWhere('title', 'База данных');

        $this->assertSame(ProductionHealth::FAIL, $database['status']);
        $this->assertSame('нет ответа', $database['value']);
    }

    public function test_manual_checks_are_not_checked_and_are_not_counted_as_failures(): void
    {
        config(['queue.default' => 'sync']);
        DB::shouldReceive('select')->once()->andReturn([]);

        $health = ProductionHealth::make();
        $businessChecks = collect($health['checks'])->firstWhere('group', 'Бизнес-логика')['items'];

        $this->assertNotEmpty($businessChecks);
        $this->assertSame(
            [ProductionHealth::NOT_CHECKED],
            collect($businessChecks)->pluck('status')->unique()->values()->all(),
        );
        $this->assertSame(0, collect($businessChecks)->where('status', ProductionHealth::FAIL)->count());
    }

    private function invoke(string $method, mixed ...$arguments): mixed
    {
        return (new ReflectionMethod(ProductionHealth::class, $method))->invoke(null, ...$arguments);
    }
}
