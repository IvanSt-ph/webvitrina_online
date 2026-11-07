<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotClient;
use App\Services\TelegramPhoneVerificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Throwable;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramPhoneVerificationService $verification, TelegramBotClient $bot)
    {
        try {
            $message = $verification->handle($request->json()->all());
            if ($message && ! $bot->send($message)) {
                return response()->json(['ok' => false], 503);
            }

            return response()->json(['ok' => true]);
        } catch (QueryException $exception) {
            // Uniqueness races must not expose SQL bindings (phone, hash or Telegram ID).
            return response()->json(['ok' => (int) ($exception->errorInfo[1] ?? 0) === 1062],
                (int) ($exception->errorInfo[1] ?? 0) === 1062 ? 200 : 503);
        } catch (Throwable) {
            return response()->json(['ok' => false], 503);
        }
    }
}
