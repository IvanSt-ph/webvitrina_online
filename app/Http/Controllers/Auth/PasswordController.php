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
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], $messages);

        // Обновляем пароль
        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }
}
