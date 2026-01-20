<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\User;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /* =========================
     * ПРОФИЛЬ ПОЛЬЗОВАТЕЛЯ
     * ========================= */

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|max:255',
            'phone'  => 'nullable|string|max:50',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $changed = false;

        // Имя
        if ($data['name'] !== $user->name) {
            $user->name = $data['name'];
            $changed = true;
        }

        // Email
        if ($data['email'] !== $user->email) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
            $changed = true;
        }

        // Телефон
        if (array_key_exists('phone', $data)) {

            // Нормализуем телефон
            $phone = $data['phone'] ? '+' . preg_replace('/\D+/', '', $data['phone']) : null;

            if ($phone !== $user->phone) {

                // Проверка уникальности среди пользователей
                $userExists = User::where('phone', $phone)
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($userExists) {
                    return back()->withErrors(['phone' => 'Этот номер уже используется другим пользователем'])->withInput();
                }

                // Проверка уникальности среди магазинов
                $shopExists = Shop::where('phone', $phone)
                    ->where('user_id', '!=', $user->id)
                    ->exists();

                if ($shopExists) {
                    return back()->withErrors(['phone' => 'Этот номер уже используется другим магазином'])->withInput();
                }

                $user->phone = $phone;
                $user->phone_verified_at = null;
                $user->phone_verification_code = null;
                $changed = true;
            }
        }

        // Аватар
        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
            $changed = true;
        }

        if ($changed) {
            $user->save();
            return back()->with('status', 'profile-updated');
        }

        return back();
    }

    /* =========================
     * ПОДТВЕРЖДЕНИЕ ТЕЛЕФОНА
     * ========================= */

    public function sendPhoneVerification(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user->phone) {
            return back()->withErrors(['phone' => 'Укажите номер телефона']);
        }

        $code = random_int(100000, 999999);

        $user->update([
            'phone_verification_code' => $code
        ]);

        // Здесь должна быть реальная SMS-интеграция
        // Http::get(...)

        return back()->with('phone_sent', true);
    }

    public function verifyPhone(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $user = $request->user();

        if ($request->code !== $user->phone_verification_code) {
            return back()->withErrors(['code' => 'Неверный код']);
        }

        $user->update([
            'phone_verified_at' => now(),
            'phone_verification_code' => null,
        ]);

        return back()->with('status', 'phone-verified');
    }

    /* =========================
     * МАГАЗИН ПРОДАВЦА
     * ========================= */

    public function updateShop(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone'       => 'nullable|string|max:50',
            'banner'      => 'nullable|image|max:4096',

            'facebook'    => 'nullable|url|max:255',
            'instagram'   => 'nullable|url|max:255',
            'telegram'    => 'nullable|url|max:255',
            'whatsapp'    => 'nullable|url|max:255',

            'remove_banner' => 'nullable|boolean',
        ]);

        $shop = $request->user()->shop ?? $request->user()->shop()->create([]);

        // Проверка и удаление баннера
        if ($request->boolean('remove_banner') && $shop->banner) {
            Storage::disk('public')->delete($shop->banner);
            $shop->update(['banner' => null]);
        }

        if ($request->hasFile('banner')) {
            if ($shop->banner) {
                Storage::disk('public')->delete($shop->banner);
            }
            $data['banner'] = $request->file('banner')->store('banners', 'public');
        }

        // Проверка телефона магазина
        if (!empty($data['phone'])) {
            $phone = '+' . preg_replace('/\D+/', '', $data['phone']);
            $data['phone'] = $phone;

            // Проверка уникальности среди магазинов
            $shopExists = Shop::where('phone', $phone)
                ->where('id', '!=', $shop->id)
                ->exists();

            if ($shopExists) {
                return back()->withErrors(['phone' => 'Этот номер уже используется другим магазином'])->withInput();
            }

            // Проверка уникальности среди пользователей
            $userExists = User::where('phone', $phone)
                ->where('id', '!=', auth()->id())
                ->exists();

            if ($userExists) {
                return back()->withErrors(['phone' => 'Этот номер уже привязан к аккаунту пользователя'])->withInput();
            }
        }

        $shop->update($data);

        return Redirect::route('profile.edit')->with('status', 'shop-updated');
    }

    /* =========================
     * КАБИНЕТ
     * ========================= */

    public function cabinet()
    {
        $user = auth()->user();

        if (!$user) {
            return view('profile.guest-cabinet');
        }

        if ($user->isSeller()) {
            $orders = Order::whereHas('items.product', fn ($q) =>
                $q->where('user_id', $user->id)
            )
            ->with(['items.product.category', 'items.product.city.country', 'address'])
            ->latest()
            ->paginate(10);

            return view('seller.cabinet', compact('user', 'orders'));
        }

        $latestOrders = $user->orders()
            ->with(['items.product.category', 'items.product.city.country'])
            ->latest()
            ->limit(3)
            ->get();

        $recommendations = $this->getRecommendations($user);

        return view('profile.buyer-cabinet', compact(
            'user',
            'latestOrders',
            'recommendations'
        ));
    }

    private function getRecommendations($user): array
    {
        $categoryIds = OrderItem::whereHas('order', fn ($q) =>
            $q->where('user_id', $user->id)
        )
        ->join('products', 'order_items.product_id', '=', 'products.id')
        ->pluck('products.category_id')
        ->unique()
        ->toArray();

        $shownIds = OrderItem::whereHas('order', fn ($q) =>
            $q->where('user_id', $user->id)
        )
        ->pluck('product_id')
        ->unique()
        ->toArray();

        $products = Product::whereIn('category_id', $categoryIds)
            ->whereNotIn('id', $shownIds)
            ->where('user_id', '!=', $user->id)
            ->inRandomOrder()
            ->limit(8)
            ->get();

        return $products->map(fn ($p) => [
            'id'    => $p->id,
            'title' => $p->title,
            'price' => $p->price,
            'image' => $p->image,
            'link'  => route('product.show', $p->id),
        ])->toArray();
    }

    /* =========================
     * УДАЛЕНИЕ АККАУНТА
     * ========================= */

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
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
}
