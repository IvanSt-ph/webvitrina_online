<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Показ формы редактирования профиля.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Обновление информации профиля пользователя.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Заполняем модель новыми данными из формы (имя, email и т.д.)
        $user->fill($request->validated());

        // Если email был изменён — сбрасываем подтверждение
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Проверяем — был ли загружен новый аватар
        if ($request->hasFile('avatar')) {
            // Если у пользователя уже есть аватар — удаляем старый файл
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Сохраняем новый аватар в папку storage/app/public/avatars
            $path = $request->file('avatar')->store('avatars', 'public');

            // Записываем путь в базу данных
            $user->avatar = $path;
        }

        // Сохраняем изменения в БД
        $user->save();

        // Возвращаем обратно на страницу профиля с уведомлением
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Удаление аккаунта пользователя.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Проверка пароля перед удалением
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Выходим из аккаунта
        Auth::logout();

        // Удаляем аватар, если он есть
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Удаляем пользователя из базы
        $user->delete();

        // Чистим сессию
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Личный кабинет (продавца или покупателя).
     */
    public function cabinet()
    {
        $user = auth()->user();

        // Если пользователь не авторизован — показываем гостевую страницу
        if (!$user) {
            return view('profile.guest-cabinet');
        }

        // Если это продавец
        if ($user->isSeller()) {
            // Получаем все заказы, в которых есть товары данного продавца
            $orders = \App\Models\Order::whereHas('items.product', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->latest()->paginate(10);

            return view('seller.cabinet', compact('user', 'orders'));
        }

        // Если это покупатель — показываем его кабинет
        return view('profile.buyer-cabinet', compact('user'));
    }
}
