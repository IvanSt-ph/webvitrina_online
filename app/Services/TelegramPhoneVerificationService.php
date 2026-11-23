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

    public static function sessionHash(string $sessionId): string
    {
        if ($sessionId === '' || ! config('app.key')) {
            throw new \LogicException('Session binding is unavailable.');
        }

        return hash_hmac('sha256', 'telegram-phone:'.$sessionId, (string) config('app.key'));
    }

    public function revokeSession(User $actor, string $sessionId): void
    {
        $hash = self::sessionHash($sessionId);
        DB::transaction(function () use ($actor, $hash): void {
            User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            Verification::where('user_id', $actor->id)->where('session_hash', $hash)
                ->whereNull('consumed_at')->whereNull('invalidated_at')
                ->update(['invalidated_at' => now(), 'active_telegram_user_id' => null]);
        }, 3);
    }

    public function finalize(User $actor, string $sessionId, int $verificationId): void
    {
        $hash = self::sessionHash($sessionId);
        DB::transaction(function () use ($actor, $hash, $verificationId): void {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            abort_unless(self::enabled() && $user->role === 'seller', 403);
            $request = Verification::where('user_id', $user->id)->whereKey($verificationId)
                ->lockForUpdate()->firstOrFail();
            abort_unless($request->session_hash && hash_equals($request->session_hash, $hash), 403);
            if (! $user->hasVerifiedEmail() || ! $request->pending() || ! $request->contact_verified_at
                || ! $user->phone || $user->phone !== $request->phone) {
                throw ValidationException::withMessages([
                    'phone' => 'Подтвердите email и проверьте актуальность заявки. При необходимости создайте новую.',
                ]);
            }
            if (! $user->phone_verified_at) {
                $user->forceFill(['phone_verified_at' => now(), 'phone_verification_code' => null])->save();
            }
            $request->forceFill(['consumed_at' => now(), 'active_telegram_user_id' => null])->save();
        }, 3);
    }

    public function create(User $actor, string $sessionId): string
    {
        $hash = self::sessionHash($sessionId);
        return DB::transaction(function () use ($actor, $hash) {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            abort_unless(self::enabled() && $user->role === 'seller', 403);
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
                'session_hash' => $hash,
            ]);

            return 'https://t.me/'.config('services.telegram.username').'?start='.$token;
        }, 3);
    }

    /** Returns only a safe bot response; never includes input or credentials. */
    public function handle(array $update): ?array
    {
        if (! self::enabled()) {
            return null;
        }
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
                ->where('telegram_user_id', $sender)->whereNotNull('contact_verified_at')->exists();
            if ($completed) {
                return null; // Acknowledgement only; never replay writes or misleading bot messages.
            }
        }
        $response = ['chat_id' => $sender, 'text' => 'Заявка истекла или недействительна. Начните подтверждение заново в WebVitrina.'];
        if (! $candidate) {
            return $response;
        }

        return DB::transaction(function () use ($candidate, $message, $update, $sender, $start, $response) {
            // Same ordering as assignment/deletion: User -> verification.
            $user = User::whereKey($candidate->user_id)->lockForUpdate()->first();
            $request = Verification::whereKey($candidate->id)->lockForUpdate()->first();
            if (! $user || $user->role !== 'seller' || ! $request
                || ! $request->session_hash || ! $request->pending() || $user->phone !== $request->phone) {
                return $response;
            }
            if ($request->contact_verified_at) {
                return null; // Contact is already checked; only the originating browser can finish.
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

                return ['chat_id' => $sender, 'verification_banner' => true,
                    'text' => "🔐 <b>Подтверждение телефона WebVitrina</b>\n\nДля защиты аккаунта подтвердите свой номер телефона.\n\n📱 Нажмите кнопку ниже, чтобы поделиться своим контактом через Telegram.\n\n🛡️ Мы никогда не запрашиваем пароли и коды входа.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => ['keyboard' => [[['text' => '📱 Поделиться моим номером', 'request_contact' => true]]],
                        'resize_keyboard' => true, 'one_time_keyboard' => true]];
            }
            $contact = $message['contact'];
            if (! is_array($contact) || ($contact['user_id'] ?? null) !== $sender
                || (int) $request->active_telegram_user_id !== $sender
                || $message['message_id'] <= $request->start_message_id
                || $update['update_id'] <= $request->start_update_id
                || $message['date'] < $request->bound_at->timestamp
                || ! is_string($contact['phone_number'] ?? null)) {
                return ['chat_id' => $sender, 'text' => 'Не удалось проверить контакт. Нажмите «📱 Поделиться моим номером» и отправьте свой контакт, а не чужой.'];
            }
            try {
                $phone = app(PhoneNormalizer::class)->normalize($contact['phone_number']);
            } catch (ValidationException) {
                return ['chat_id' => $sender, 'text' => 'Не удалось распознать номер. Отправьте свой контакт кнопкой «📱 Поделиться моим номером».'];
            }
            if ($phone !== $request->phone) {
                return ['chat_id' => $sender, 'text' => 'Номер Telegram не совпадает с номером аккаунта. Проверьте сохранённый номер в WebVitrina.'];
            }
            $request->forceFill(['contact_verified_at' => now(), 'contact_update_id' => $update['update_id'],
                'active_telegram_user_id' => null])->save();

            return ['chat_id' => $sender,
                'text' => "✅ <b>Телефон проверен!</b>\n\nВернитесь во вкладку WebVitrina и нажмите «Завершить подтверждение».",
                'parse_mode' => 'HTML',
                'reply_markup' => ['remove_keyboard' => true]];
        }, 3);
    }
}
