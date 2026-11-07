<?php

namespace App\Http\Middleware;

use App\Services\TelegramPhoneVerificationService;
use Closure;
use Illuminate\Http\Request;

class VerifyTelegramWebhookSecret
{
    public function handle(Request $request, Closure $next): mixed
    {
        $secret = (string) config('services.telegram.webhook_secret');
        if (! TelegramPhoneVerificationService::enabled() || $secret === ''
            || ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            return response()->json(['ok' => false], 403);
        }

        return $next($request);
    }
}
