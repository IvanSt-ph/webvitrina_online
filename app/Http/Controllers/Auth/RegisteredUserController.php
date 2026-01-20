<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Shop;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request)
    {
        /**
         * 1️⃣ Нормализация телефона
         */
        $phone = null;

        if ($request->filled('phone')) {
            $phone = '+' . preg_replace('/\D+/', '', $request->phone);

            // базовая защита от мусора
            $digits = preg_replace('/\D+/', '', $phone);
            if (strlen($digits) < 7 || strlen($digits) > 15) {
                return back()
                    ->withErrors(['phone' => 'Неверный формат телефона'])
                    ->withInput();
            }
        }

        $request->merge(['phone' => $phone]);

        /**
         * 2️⃣ Проверка уникальности телефона среди всех аккаунтов
         */
        if ($phone) {
            $existsInUsers = User::where('phone', $phone)->exists();
            $existsInShops = Shop::where('phone', $phone)->exists();

            if ($existsInUsers || $existsInShops) {
                return back()
                    ->withErrors(['phone' => 'Этот телефон уже используется другим пользователем или магазином'])
                    ->withInput();
            }
        }

        /**
         * 3️⃣ Валидация остальных данных
         */
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:buyer,seller'],
        ]);

        /**
         * 4️⃣ Создание пользователя
         */
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'phone' => $phone,
        ]);

        /**
         * 5️⃣ Событие регистрации
         */
        event(new Registered($user));

        /**
         * 6️⃣ Авторизация
         */
        Auth::login($user);

        /**
         * 7️⃣ Редирект
         */
        return redirect()->route('home');
    }
}
