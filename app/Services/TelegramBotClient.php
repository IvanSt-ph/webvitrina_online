<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramBotClient
{
    public function send(array $message): bool
    {
        $withBanner = ($message['verification_banner'] ?? false) === true;
        unset($message['verification_banner']);
        if ($withBanner && $this->sendVerificationPhoto($message)) {
            return true;
        }

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

    protected function verificationBannerPath(): string
    {
        return resource_path('telegram/phone-verification.png');
    }

    private function sendVerificationPhoto(array $message): bool
    {
        try {
            // Only this bundled asset is allowed; never resolve a path from an update.
            $path = $this->verificationBannerPath();
            if (! is_file($path) || ! is_readable($path) || filesize($path) > 10 * 1024 * 1024) {
                return false;
            }
            $photo = @file_get_contents($path);
            if ($photo === false || @getimagesizefromstring($photo) === false) {
                return false;
            }
            $response = Http::connectTimeout(3)->timeout(8)
                ->attach('photo', $photo, 'phone-verification.png', ['Content-Type' => 'image/png'])
                ->post('https://api.telegram.org/bot'.config('services.telegram.token').'/sendPhoto', [
                    'chat_id' => (string) $message['chat_id'],
                    'caption' => $message['text'],
                    'parse_mode' => $message['parse_mode'],
                    'reply_markup' => json_encode($message['reply_markup'], JSON_THROW_ON_ERROR),
                ]);

            return $response->successful() && $response->json('ok') === true;
        } catch (Throwable) {
            // Fall back to text without logging exceptions that may contain the bot token.
            return false;
        }
    }
}
