<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class QueueHealthCheckJob implements ShouldQueue
{
    use Queueable;

    public const LAST_SUCCESS_CACHE_KEY = 'queue-health-check:last-success';

    public const LAST_FAILURE_CACHE_KEY = 'queue-health-check:last-failure';

    public const LAST_TOKEN_CACHE_KEY = 'queue-health-check:last-processed-token';

    public function __construct(
        private string $token,
    ) {
        $this->afterCommit();
    }

    public function handle(): void
    {
        $processedAt = now()->toIso8601String();

        Cache::put($this->cacheKey($this->token), $processedAt, now()->addMinutes(5));
        Cache::forever(self::LAST_SUCCESS_CACHE_KEY, $processedAt);
        Cache::forever(self::LAST_TOKEN_CACHE_KEY, $this->token);
        Cache::forget(self::LAST_FAILURE_CACHE_KEY);
    }

    public static function cacheKey(string $token): string
    {
        return 'queue-health-check:' . hash('sha256', $token);
    }

    public function failed(?\Throwable $exception): void
    {
        Cache::forever(self::LAST_FAILURE_CACHE_KEY, now()->toIso8601String());
    }
}
