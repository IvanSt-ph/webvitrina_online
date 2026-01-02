<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:buyer,seller'],
            'phone' => ['nullable', 'string'], // телефон необязательный
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ];

        // Если пользователь указал телефон — нормализуем его
        if ($request->filled('phone')) {
            $phone = preg_replace('/[^0-9+]/', '', $request->phone); // оставляем только цифры и плюс

            // Если нет плюса в начале — добавляем
            if (!str_starts_with($phone, '+')) {
                $phone = '+'.$phone;
            }

            // Проверяем длину номера (от 7 до 15 цифр)
            $digits = preg_replace('/\D/', '', $phone);
            if (strlen($digits) < 7 || strlen($digits) > 15) {
                return back()->withErrors(['phone' => 'Телефон указан неверно.'])->withInput();
            }

            $data['phone'] = $phone;
        }

        $user = User::create($data);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('home', absolute: false));
    }
}
