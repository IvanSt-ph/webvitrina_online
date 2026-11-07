<?php

namespace App\Services;

use App\Models\TelegramPhoneVerification as Verification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TelegramPhoneVerificationService
{
    public static function enabled(): bool
    {
        return config('services.telegram.enabled')
            && config('services.telegram.token') && config('services.telegram.webhook_secret')
            && preg_match('/^[A-Za-z0-9_]+$/', (string) config('services.telegram.username'));
    }

    public static function invalidate(int $userId): void
    {
        Verification::where('user_id', $userId)->whereNull('consumed_at')->whereNull('invalidated_at')
            ->update(['invalidated_at' => now(), 'active_telegram_user_id' => null]);
    }

    public function create(User $actor): string
    {
        return DB::transaction(function () use ($actor) {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($user->role, ['buyer', 'seller'], true), 403);
            if (! $user->phone || $user->phone_verified_at) {
                throw ValidationException::withMessages(['phone' => 'Сохраните неподтверждённый номер телефона.']);
            }
            // Serialized with creation, including requests from different browser sessions.
            if (Verification::where('user_id', $user->id)->where('created_at', '>', now()->subMinutes(10))->count() >= 3) {
                throw ValidationException::withMessages(['phone' => 'Слишком много заявок. Повторите через 10 минут.']);
            }
            self::invalidate($user->id);
            $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            Verification::create([
                'user_id' => $user->id, 'phone' => app(PhoneNormalizer::class)->normalize($user->phone),
                'token_hash' => hash('sha256', $token), 'expires_at' => now()->addMinutes(10),
            ]);

            return 'https://t.me/'.config('services.telegram.username').'?start='.$token;
        }, 3);
    }

    /** Returns only a safe bot response; never includes input or credentials. */
    public function handle(array $update): ?array
    {
        $message = $update['message'] ?? null;
        if (! is_array($message) || ! is_int($update['update_id'] ?? null)
            || ! is_int($message['message_id'] ?? null) || ! is_int($message['date'] ?? null)
            || ! is_int($message['from']['id'] ?? null) || $message['from']['id'] <= 0
            || ($message['chat']['type'] ?? null) !== 'private'
            || ($message['chat']['id'] ?? null) !== $message['from']['id']
            || isset($message['forward_origin']) || isset($message['sender_chat'])) {
            return null;
        }
        $sender = $message['from']['id'];
        $start = is_string($message['text'] ?? null)
            && preg_match('/^\/start ([A-Za-z0-9_-]{43})$/D', $message['text'], $matches);
        if (! $start && ! isset($message['contact'])) {
            return null;
        }
        // Release expired reservations in a separate transaction before taking User locks.
        Verification::whereNotNull('active_telegram_user_id')->where('expires_at', '<=', now())
            ->update(['active_telegram_user_id' => null, 'invalidated_at' => now()]);
        $candidate = $start
            ? Verification::where('token_hash', hash('sha256', $matches[1]))->first()
            : Verification::where('active_telegram_user_id', $sender)->first();
        if (! $candidate && ! $start) {
            $completed = Verification::where('contact_update_id', $update['update_id'])
                ->where('telegram_user_id', $sender)->whereNotNull('consumed_at')->exists();
            if ($completed) {
                return null; // Acknowledgement only; never replay writes or misleading bot messages.
            }
        }
        $response = ['chat_id' => $sender, 'text' => 'Заявка недействительна. Создайте новую в настройках WebVitrina.'];
        if (! $candidate) {
            return $response;
        }

        return DB::transaction(function () use ($candidate, $message, $update, $sender, $start, $response) {
            // Same ordering as assignment/deletion: User -> verification.
            $user = User::whereKey($candidate->user_id)->lockForUpdate()->first();
            $request = Verification::whereKey($candidate->id)->lockForUpdate()->first();
            if (! $user || ! in_array($user->role, ['buyer', 'seller'], true) || ! $request
                || ! $request->pending() || $user->phone !== $request->phone) {
                return $response;
            }
            if ($start) {
                if ($request->telegram_user_id && (int) $request->telegram_user_id !== $sender) {
                    return $response;
                }
                if (! $request->telegram_user_id) {
                    if (Verification::where('active_telegram_user_id', $sender)->whereKeyNot($request->id)->exists()) {
                        return ['chat_id' => $sender, 'text' => 'У вас уже есть активная заявка. Завершите её или дождитесь истечения 10 минут.'];
                    }
                    $request->forceFill([
                        'telegram_user_id' => $sender, 'active_telegram_user_id' => $sender,
                        'start_update_id' => $update['update_id'], 'start_message_id' => $message['message_id'],
                        'bound_at' => now(),
                    ])->save();
                }

                return ['chat_id' => $sender, 'text' => 'Подтвердите личный телефон аккаунта WebVitrina: отправьте свой контакт.',
                    'reply_markup' => ['keyboard' => [[['text' => 'Отправить мой контакт', 'request_contact' => true]]],
                        'resize_keyboard' => true, 'one_time_keyboard' => true]];
            }
            $contact = $message['contact'];
            if (! is_array($contact) || ($contact['user_id'] ?? null) !== $sender
                || (int) $request->active_telegram_user_id !== $sender
                || $message['message_id'] <= $request->start_message_id
                || $update['update_id'] <= $request->start_update_id
                || $message['date'] < $request->bound_at->timestamp
                || ! is_string($contact['phone_number'] ?? null)) {
                return $response;
            }
            try {
                $phone = app(PhoneNormalizer::class)->normalize($contact['phone_number']);
            } catch (ValidationException) {
                return $response;
            }
            if ($phone !== $request->phone) {
                return $response;
            }
            if (! $user->phone_verified_at) {
                $user->forceFill(['phone_verified_at' => now(), 'phone_verification_code' => null])->save();
            }
            $request->forceFill(['consumed_at' => now(), 'contact_update_id' => $update['update_id'],
                'active_telegram_user_id' => null])->save();

            return ['chat_id' => $sender, 'text' => 'Личный телефон подтверждён. Вернитесь в профиль WebVitrina.',
                'reply_markup' => ['remove_keyboard' => true]];
        }, 3);
    }
}
