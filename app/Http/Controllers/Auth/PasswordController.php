<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        // Пользовательские сообщения ошибок
        $messages = [
            'current_password.current_password' => 'Текущий пароль введён неверно.',
            'password.required' => 'Введите новый пароль.',
            'password.min' => 'Пароль должен быть минимум :min символов.',
            'password.confirmed' => 'Подтверждение пароля не совпадает.',
        ];

        // Валидация
        $rules = [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if ($request->user()->hasLocalPassword()) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $validated = $request->validateWithBag('updatePassword', $rules, $messages);

        // Обновляем пароль
        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'password_set_at' => now(),
        ]);

        return back()->with('status', 'password-updated');
    }
}
