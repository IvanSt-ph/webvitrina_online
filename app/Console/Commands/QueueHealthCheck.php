<?php

namespace App\Console\Commands;

use App\Jobs\QueueHealthCheckJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class QueueHealthCheck extends Command
{
    protected $signature = 'queue:health-check {--timeout=10 : Seconds to wait for the worker} {--allow-sync : Allow sync queue for local debugging}';

    protected $description = 'Dispatch a small queue job and verify that a worker processes it.';

    public function handle(): int
    {
        $connection = (string) config('queue.default');
        $timeout = max(1, (int) $this->option('timeout'));

        if ($connection === 'sync' && ! $this->option('allow-sync')) {
            $this->error('QUEUE_CONNECTION=sync. This does not verify a real worker.');
            $this->line('Set QUEUE_CONNECTION=database and run php artisan queue:work database --sleep=3 --tries=3 --timeout=90.');

            return self::FAILURE;
        }

        $token = (string) Str::uuid();
        $cacheKey = QueueHealthCheckJob::cacheKey($token);

        Cache::forget($cacheKey);
        QueueHealthCheckJob::dispatch($token);

        $deadline = microtime(true) + $timeout;
        while (microtime(true) < $deadline) {
            if (Cache::has($cacheKey)) {
                $processedAt = Cache::pull($cacheKey);
                $this->info('Queue worker processed the health-check job.');
                $this->line('Connection: ' . $connection);
                $this->line('Processed at: ' . $processedAt);

                return self::SUCCESS;
            }

            usleep(250000);
        }

        $this->error('Queue worker did not process the health-check job within ' . $timeout . ' seconds.');
        $this->line('Check that the worker is running: php artisan queue:work ' . $connection . ' --sleep=3 --tries=3 --timeout=90');

        return self::FAILURE;
    }
}
