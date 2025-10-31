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
     * Обновление личной информации пользователя (имя, email, аватар).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|max:255',
            'avatar' => 'nullable|image|max:2048',
        ]);

        // Если email изменён — сбрасываем верификацию
        if ($user->email !== $data['email']) {
            $user->email_verified_at = null;
        }

        // Загрузка аватара
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Обновление информации о магазине.
     */
  public function updateShop(Request $request): RedirectResponse
{
    $user = $request->user();

    $data = $request->validate([
        'name'           => 'nullable|string|max:255',
        'city'           => 'nullable|string|max:255',
        'description'    => 'nullable|string|max:1000',
        'phone'          => 'nullable|string|max:50',
        'banner'         => 'nullable|image|max:4096',
        'remove_banner'  => 'nullable|boolean',
    ]);

    // ✅ если магазин есть — обновляем, если нет — создаём
    $shop = $user->shop ?? $user->shop()->create([]);

    // ❌ Удаление баннера
    if ($request->has('remove_banner')) {
        if ($shop->banner) {
            Storage::disk('public')->delete($shop->banner);
        }
        $shop->update(['banner' => null]);
        return Redirect::route('profile.edit')->with('status', 'shop-updated');
    }

    // 🖼️ Загрузка нового баннера
    if ($request->hasFile('banner')) {
        if ($shop->banner) {
            Storage::disk('public')->delete($shop->banner);
        }

        $path = $request->file('banner')->store('banners', 'public');
        $shop->update(['banner' => $path]);
        return Redirect::route('profile.edit')->with('status', 'shop-updated');
    }

    // 🏪 Обновляем остальные данные
    $shop->update($data);

    return Redirect::route('profile.edit')->with('status', 'shop-updated');
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

        // каскадно удалится shop
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Личный кабинет продавца или покупателя.
     */
    public function cabinet()
    {
        $user = auth()->user();

        if (!$user) {
            return view('profile.guest-cabinet');
        }

        if ($user->isSeller()) {
            $orders = \App\Models\Order::whereHas('items.product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->latest()->paginate(10);

            return view('seller.cabinet', compact('user', 'orders'));
        }

        return view('profile.buyer-cabinet', compact('user'));
    }
}
