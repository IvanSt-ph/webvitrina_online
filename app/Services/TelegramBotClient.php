<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramBotClient
{
    public function send(array $message): bool
    {
        try {
            $response = Http::connectTimeout(3)->timeout(8)->post(
                'https://api.telegram.org/bot'.config('services.telegram.token').'/sendMessage', $message,
            );

            return $response->successful() && $response->json('ok') === true;
        } catch (Throwable) {
            // HTTP exceptions can contain the bot token in the request URL.
            return false;
        }
    }
}
