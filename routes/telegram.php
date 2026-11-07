<?php

use App\Http\Controllers\TelegramWebhookController;
use App\Http\Middleware\CoordinateBackupWrites;
use App\Http\Middleware\VerifyTelegramWebhookSecret;
use Illuminate\Support\Facades\Route;

// Deliberately outside web: no cookies, session, auth or CSRF exemption needed.
Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->middleware([VerifyTelegramWebhookSecret::class, CoordinateBackupWrites::class])
    ->name('telegram.webhook');
