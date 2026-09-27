<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Record scheduler execution before potentially slow daily tasks.
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::forever(
    \App\Support\ProductionHealth::SCHEDULER_CACHE_KEY,
    now()->toIso8601String(),
))->name('scheduler-heartbeat')->everyMinute();

Schedule::command('queue:health-check --timeout=15')
    ->everyFiveMinutes()
    ->withoutOverlapping(5)
    ->runInBackground();

Schedule::command('products:purge-old')->dailyAt('03:30');

Schedule::command('backup:run')
    ->dailyAt((string) config('backup.daily_at', '03:15'))
    ->withoutOverlapping();
