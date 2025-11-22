<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Ошибка Google авторизации.');
        }

        // 1) Ищем пользователя по provider_id
        $user = User::where('provider', 'google')
            ->where('provider_id', $googleUser->id)
            ->first();

        // 2) Если нет — ищем по email
        if (!$user) {
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // Привязываем Google к существующему аккаунту
                $user->provider = 'google';
                $user->provider_id = $googleUser->id;
                $user->provider_token = $googleUser->token;
            } else {
                // Создаём нового
                $user = User::create([
                    'name'            => $googleUser->name ?? 'User',
                    'email'           => $googleUser->email,
                    'provider'        => 'google',
                    'provider_id'     => $googleUser->id,
                    'provider_token'  => $googleUser->token,
                    'password'        => bcrypt(str()->random(16)),
                    'role'            => 'buyer',
                ]);
            }
        }

        // 3) Если почта НЕ подтверждена — генерируем письмо
        if (!$user->email_verified_at) {
            $user->sendEmailVerificationNotification();
        }

        // 4) Логиним
        Auth::login($user, true);

        // 5) Если НЕ подтвержден → отправим на страницу verify
        if (!$user->email_verified_at) {
            return redirect()->route('verification.notice');
        }

        // 6) Иначе → домой
        return redirect()->route('home');
    }
}
