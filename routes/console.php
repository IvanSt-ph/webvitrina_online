<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('products:purge-old')->dailyAt('03:30');

Schedule::command('backup:run')
    ->dailyAt((string) config('backup.daily_at', '03:15'))
    ->withoutOverlapping();
