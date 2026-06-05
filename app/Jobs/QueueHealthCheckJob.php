<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class QueueHealthCheckJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $token,
    ) {
        $this->afterCommit();
    }

    public function handle(): void
    {
        Cache::put($this->cacheKey($this->token), now()->toIso8601String(), now()->addMinutes(5));
    }

    public static function cacheKey(string $token): string
    {
        return 'queue-health-check:' . hash('sha256', $token);
    }
}
