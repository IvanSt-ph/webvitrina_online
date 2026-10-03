<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use App\Models\Shop;

class ShopPhoneVerificationController extends Controller
{
    protected function twilio()
    {
        return new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    protected function verifySid(): ?string
    {
        return config('services.twilio.verify_sid');
    }

    protected function ensureTwilioConfigured(): ?\Illuminate\Http\RedirectResponse
    {
        if (!config('services.twilio.sid') || !config('services.twilio.token') || !$this->verifySid()) {
            return back()->withErrors([
                'phone' => 'SMS-подтверждение не настроено. Проверьте TWILIO_SID, TWILIO_AUTH_TOKEN и TWILIO_VERIFY_SERVICE_SID в .env.'
            ]);
        }

        return null;
    }

    public function send(Request $request)
    {
        $shop = Auth::user()->shop;

        if (!$shop) {
            return back()->withErrors(['shop' => 'У вас нет магазина']);
        }

        if ($response = $this->ensureTwilioConfigured()) {
            return $response;
        }

        // Защита от частых запросов
        $throttleKey = 'shop-phone-verify:' . $shop->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'phone' => "Пожалуйста, подождите {$seconds} секунд перед следующей попыткой"
            ]);
        }

        RateLimiter::hit($throttleKey, 300); // 5 минут блокировки

        if (!$shop->phone) {
            return back()->withErrors(['phone' => 'Сначала укажите номер телефона магазина']);
        }

        try {
            $this->twilio()
                ->verify
                ->v2
                ->services($this->verifySid())
                ->verifications
                ->create($shop->phone, 'sms');

            session(['shop_phone_verification_sent' => true]);

            return back()->with([
                'phone_sent' => true,
                'message' => 'SMS с кодом отправлено на телефон магазина'
            ]);

        } catch (\Exception $e) {
            Log::channel('twilio')->warning('Shop phone verification SMS send failed', [
                'user_id' => Auth::id(),
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'phone' => 'Не удалось отправить SMS. Попробуйте позже.'
            ]);
        }
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|digits:6'
        ]);

        $shop = Auth::user()->shop;

        if (!$shop) {
            throw ValidationException::withMessages([
                'shop' => 'У вас нет магазина'
            ]);
        }

        $expectedPhone = $this->normalizePhone($shop->phone);

        if ($expectedPhone === null) {
            throw ValidationException::withMessages([
                'code' => 'Номер телефона магазина изменился или указан неверно. Запросите новый код.',
            ]);
        }

        if (!config('services.twilio.sid') || !config('services.twilio.token') || !$this->verifySid()) {
            throw ValidationException::withMessages([
                'code' => 'SMS-подтверждение не настроено. Проверьте настройки Twilio.'
            ]);
        }

        $verifyThrottleKey = 'shop-phone-verify-attempt:' . $shop->id;
        if (RateLimiter::tooManyAttempts($verifyThrottleKey, 5)) {
            $seconds = RateLimiter::availableIn($verifyThrottleKey);
            throw ValidationException::withMessages([
                'code' => "Слишком много попыток. Подождите {$seconds} секунд"
            ]);
        }

        RateLimiter::hit($verifyThrottleKey, 300);

        try {
            $check = $this->twilio()
                ->verify
                ->v2
                ->services($this->verifySid())
                ->verificationChecks
                ->create([
                    'to' => $expectedPhone,
                    'code' => $request->code
                ]);

            if ($check->status === 'approved') {
                $updated = Shop::query()
                    ->whereKey($shop->getKey())
                    ->where('phone', $expectedPhone)
                    ->update([
                        'phone_verified_at' => now(),
                        'phone_verification_code' => null,
                        'phone_verification_expires_at' => null,
                    ]);

                if ($updated !== 1) {
                    return back()->withErrors([
                        'code' => 'Номер телефона магазина изменился. Запросите новый код для текущего номера.',
                    ]);
                }

                session()->forget('shop_phone_verification_sent');

                RateLimiter::clear('shop-phone-verify:' . $shop->id);
                RateLimiter::clear($verifyThrottleKey);

                return back()->with('success', 'Телефон магазина успешно подтверждён!');
            }

            throw ValidationException::withMessages([
                'code' => 'Неверный код подтверждения'
            ]);

        } catch (\Exception $e) {
            Log::channel('twilio')->warning('Shop phone verification check failed', [
                'user_id' => Auth::id(),
                'shop_id' => $shop->id,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'code' => 'Не удалось проверить код. Попробуйте позже.'
            ]);
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === null || strlen($digits) < 7 || strlen($digits) > 15) {
            return null;
        }

        return '+' . $digits;
    }
}
