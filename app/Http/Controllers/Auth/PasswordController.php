<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasswordController extends Controller
{
    public function __construct(private readonly PasswordSecurityService $passwordSecurity)
    {
    }

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

        $this->passwordSecurity->rotate($request->user(), $validated['password']);

        return back()->with('status', 'password-updated');
    }
}
