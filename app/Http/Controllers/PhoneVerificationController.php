<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PhoneVerificationController extends Controller
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
        // Защита от частых запросов
        $throttleKey = 'phone-verify:' . Auth::id();
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'phone' => "Пожалуйста, подождите {$seconds} секунд перед следующей попыткой"
            ]);
        }

        RateLimiter::hit($throttleKey, 300); // 5 минут блокировки

        $user = Auth::user();
        
        // Проверяем, есть ли телефон
        if (!$user->phone) {
            return back()->withErrors(['phone' => 'Сначала укажите номер телефона']);
        }

        try {
            $this->twilio()
                ->verify
                ->v2
                ->services(config('services.twilio.verify_sid'))
                ->verifications
                ->create($user->phone, 'sms');

            // Устанавливаем флаг в сессии для показа формы ввода кода
            session(['phone_verification_sent' => true]);
            
            return back()->with([
                'phone_sent' => true,
                'message' => 'SMS с кодом отправлено на ваш телефон'
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

        $user = Auth::user();

        // Защита от подбора кода
        $verifyThrottleKey = 'phone-verify-attempt:' . Auth::id();
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
                    'to' => $user->phone,
                    'code' => $request->code
                ]);

            if ($check->status === 'approved') {
                $user->phone_verified_at = now();
                $user->save();

                // Очищаем сессию
                session()->forget('phone_verification_sent');
                
                // Сбрасываем счетчики
                RateLimiter::clear('phone-verify:' . Auth::id());
                RateLimiter::clear($verifyThrottleKey);

                return back()->with('success', 'Телефон успешно подтверждён!');
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