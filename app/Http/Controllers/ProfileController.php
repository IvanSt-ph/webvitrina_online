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
     * Обновление личной информации пользователя.
     */
public function update(ProfileUpdateRequest $request): RedirectResponse
{
    $user = $request->user();

    $data = $request->validate([
        'name'   => 'required|string|max:255',
        'email'  => 'required|email|max:255',
        'avatar' => 'nullable|image|max:2048',
        'phone'  => 'nullable|string|max:50',
    ]);

    // Сброс email-верификации
    if ($user->email !== $data['email']) {
        $user->email_verified_at = null;
    }

    // Аватар
    if ($request->hasFile('avatar')) {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
    }

    $user->update($data);

    // 👇 ВОТ САМОЕ ГЛАВНОЕ 👇
    // Проверяем: находится ли пользователь на покупательской странице?
    if ($request->routeIs('buyer.profile.update')) {
        return Redirect::route('buyer.profile')->with('status', 'profile-updated');
    }

    // Иначе продавец
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

        // Если магазин уже есть — ok, если нет — создаём
        $shop = $user->shop ?? $user->shop()->create([]);

        /**
         * 🗑 Удаление баннера
         */
        if ($request->boolean('remove_banner')) {

            if ($shop->banner) {
                Storage::disk('public')->delete($shop->banner);
            }

            $shop->update(['banner' => null]);

            return Redirect::route('profile.edit')->with('status', 'shop-updated');
        }

        /**
         * 🖼 Загрузка новой картинки
         */
        if ($request->hasFile('banner')) {

            if ($shop->banner) {
                Storage::disk('public')->delete($shop->banner);
            }

            $path = $request->file('banner')->store('banners', 'public');

            $shop->update(['banner' => $path]);

            return Redirect::route('profile.edit')->with('status', 'shop-updated');
        }

        /**
         * 🏪 Обновление остальных данных магазина
         */
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

        // Каскадно удалится shop, адреса и т. д.
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


        /**
         * 📦 Продавец — заказы его магазина
         */
        if ($user->isSeller()) {
            $orders = \App\Models\Order::whereHas('items.product', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->with([
                    'items.product.category',
                    'items.product.city.country',
                    'address',
                ])
                ->latest()
                ->paginate(10);

            return view('seller.cabinet', compact('user', 'orders'));
        }


        /**
         * 🛒 Покупатель — 3 последних заказа
         */
        $latestOrders = $user->orders()
            ->with([
                'items.product.category',
                'items.product.city.country'
            ])
            ->latest()
            ->take(3)
            ->get();


        /**
         * 🎁 Рекомендации (заглушка)
         */
        $recommendations = [
            ['title' => 'Товар 1', 'price' => rand(800, 2500)],
            ['title' => 'Товар 2', 'price' => rand(800, 2500)],
            ['title' => 'Товар 3', 'price' => rand(800, 2500)],
        ];

        return view('profile.buyer-cabinet', compact(
            'user',
            'latestOrders',
            'recommendations'
        ));
    }
}
