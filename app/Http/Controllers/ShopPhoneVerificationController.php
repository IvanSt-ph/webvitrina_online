<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Auth;
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

    public function send(Request $request)
    {
        $shop = Auth::user()->shop;

        if (!$shop) {
            return back()->withErrors(['shop' => 'У вас нет магазина']);
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
                ->services(config('services.twilio.verify_sid'))
                ->verifications
                ->create($shop->phone, 'sms');

            session(['shop_phone_verification_sent' => true]);

            return back()->with([
                'phone_sent' => true,
                'message' => 'SMS с кодом отправлено на телефон магазина'
            ]);

        } catch (\Exception $e) {
            return back()->withErrors([
                'phone' => 'Ошибка отправки SMS: ' . $e->getMessage()
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
                ->services(config('services.twilio.verify_sid'))
                ->verificationChecks
                ->create([
                    'to' => $shop->phone,
                    'code' => $request->code
                ]);

            if ($check->status === 'approved') {
                $shop->phone_verified_at = now();
                $shop->save();

                session()->forget('shop_phone_verification_sent');

                RateLimiter::clear('shop-phone-verify:' . $shop->id);
                RateLimiter::clear($verifyThrottleKey);

                return back()->with('success', 'Телефон магазина успешно подтверждён!');
            }

            throw ValidationException::withMessages([
                'code' => 'Неверный код подтверждения'
            ]);

        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'code' => 'Ошибка проверки кода: ' . $e->getMessage()
            ]);
        }
    }
}
