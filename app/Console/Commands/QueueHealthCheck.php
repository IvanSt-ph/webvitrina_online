<?php

namespace App\Console\Commands;

use App\Jobs\QueueHealthCheckJob;
use App\Support\ProductionHealth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QueueHealthCheck extends Command
{
    protected $signature = 'queue:health-check
        {--timeout=10 : Seconds to wait for the worker}
        {--allow-sync : Allow sync queue for local debugging}
        {--allow-sync-local : Alias for --allow-sync used in local checks}';

    protected $description = 'Dispatch a small queue job and verify that a worker processes it.';

    public function handle(): int
    {
        try {
            return $this->probe();
        } catch (\Throwable) {
            try {
                Cache::forever(QueueHealthCheckJob::LAST_FAILURE_CACHE_KEY, now()->toIso8601String());
            } catch (\Throwable) {
                // Cache failure must still produce a detectable non-zero result.
            }
            $this->error('Queue health-check unavailable: dispatch or cache failed.');

            return self::FAILURE;
        }
    }

    private function probe(): int
    {
        $connection = (string) config('queue.default');
        $timeout = min(60, max(1, (int) $this->option('timeout')));

        if ($connection === 'sync' && ! ($this->option('allow-sync') || $this->option('allow-sync-local'))) {
            $this->error('QUEUE_CONNECTION=sync. This does not verify a real worker.');
            $this->line('Set QUEUE_CONNECTION=database and run php artisan queue:work database --sleep=3 --tries=3 --timeout=60.');

            return self::FAILURE;
        }

        $token = (string) Str::uuid();
        $cacheKey = QueueHealthCheckJob::cacheKey($token);
        $previousToken = Cache::get(QueueHealthCheckJob::LAST_TOKEN_CACHE_KEY);
        Cache::forget($cacheKey);
        if ($connection === 'sync') {
            QueueHealthCheckJob::dispatch($token);
        } else {
            if (config("queue.connections.{$connection}.driver") !== 'database') {
                $this->error('Automatic queue probe requires the database queue driver.');

                return self::FAILURE;
            }
            if (! ProductionHealth::persistentCache()) {
                $this->error('Queue probe requires a shared persistent cache store.');

                return self::FAILURE;
            }
            // The short dispatch lock serializes check + enqueue. The queue row itself
            // prevents unlimited accumulation even if the worker is down for weeks.
            $lock = Cache::lock('queue-health-check:dispatch:'.hash('sha256', $connection), 60);
            if (! $lock->get()) {
                $this->error('Another queue probe is dispatching.');

                return self::FAILURE;
            }
            try {
                $pending = DB::connection(config("queue.connections.{$connection}.connection"))
                    ->table(config("queue.connections.{$connection}.table", 'jobs'))
                    ->where('queue', config("queue.connections.{$connection}.queue", 'default'))
                    ->where('payload->displayName', QueueHealthCheckJob::class)
                    ->exists();
                if (! $pending) {
                    QueueHealthCheckJob::dispatch($token)->beforeCommit();
                } else {
                    $this->line('A health-check job is already pending; waiting for worker acknowledgement.');
                }
            } finally {
                $lock->release();
            }
        }

        $deadline = microtime(true) + $timeout;
        while (microtime(true) < $deadline) {
            $acknowledgedToken = Cache::get(QueueHealthCheckJob::LAST_TOKEN_CACHE_KEY);
            if (Cache::has($cacheKey) || ($acknowledgedToken !== null && $acknowledgedToken !== $previousToken)) {
                $processedAt = Cache::pull($cacheKey) ?? Cache::get(QueueHealthCheckJob::LAST_SUCCESS_CACHE_KEY);
                $this->info('Queue worker processed the health-check job.');
                $this->line('Connection: ' . $connection);
                $this->line('Processed at: ' . $processedAt);

                return self::SUCCESS;
            }

            usleep(250000);
        }

        Cache::forever(QueueHealthCheckJob::LAST_FAILURE_CACHE_KEY, now()->toIso8601String());
        $this->error('Queue worker did not process the health-check job within ' . $timeout . ' seconds.');
        $this->line('Check that the worker is running: php artisan queue:work ' . $connection . ' --sleep=3 --tries=3 --timeout=60');

        return self::FAILURE;
    }
}
