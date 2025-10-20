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

        // ✅ Добавляем дополнительные поля для продавцов
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'avatar' => 'nullable|image|max:2048',
            'shop_name' => 'nullable|string|max:255',
            'shop_description' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:50',
        ]);

        // Заполняем модель
        $user->fill($data);

        // Если email был изменён — сбрасываем подтверждение
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Если загружен новый аватар — сохраняем
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        // Сохраняем
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Удаление аккаунта пользователя.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

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

        if (!$user) {
            return view('profile.guest-cabinet');
        }

        if ($user->isSeller()) {
            $orders = \App\Models\Order::whereHas('items.product', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->latest()->paginate(10);

            return view('seller.cabinet', compact('user', 'orders'));
        }

        return view('profile.buyer-cabinet', compact('user'));
    }
}
