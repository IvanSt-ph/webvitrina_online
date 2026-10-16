<?php

namespace App\Jobs;

use App\Observers\ReviewObserver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateSellerReputation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public readonly int $sellerId)
    {
    }

    public function backoff(): array
    {
        return [5, 30, 120, 300];
    }

    public function handle(ReviewObserver $observer): void
    {
        $observer->recalculateSellerByUserId($this->sellerId);
    }
}
