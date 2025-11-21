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
            return redirect()->route('login')->with('error', 'Ошибка Google авторизации.');
        }

        // Ищем по provider/provider_id
        $user = User::where('provider', 'google')
            ->where('provider_id', $googleUser->id)
            ->first();

        if (!$user) {
            // Ищем по email (существующий аккаунт)
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // привязываем Google к старому аккаунту
                $user->provider       = 'google';
                $user->provider_id    = $googleUser->id;
                $user->provider_token = $googleUser->token;
                $user->save();
            } else {
                // создаём нового
                $user = User::create([
                    'name'            => $googleUser->name,
                    'email'           => $googleUser->email,
                    'provider'        => 'google',
                    'provider_id'     => $googleUser->id,
                    'provider_token'  => $googleUser->token,
                    'password'        => bcrypt(str()->random(16)),
                ]);
            }
        }

        // Логиним
        Auth::login($user, true);

        return redirect()->route('home');
    }
}
