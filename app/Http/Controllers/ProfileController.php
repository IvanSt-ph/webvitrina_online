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

    public function update(Request $request)
    {
        $user = $request->user();
        $updatedFields = [];
        $section = $request->input('profile_section');

        if ($request->ajax() && $request->hasFile('avatar')) {
            $request->validate([
                'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Аватар успешно обновлен',
                'avatar_url' => Storage::url($path)
            ]);
        }

        if ($section === 'personal') {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($request->hasFile('avatar')) {
                if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }

                $path = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = $path;
                $updatedFields[] = 'avatar';
            }

            if ($data['name'] !== $user->name) {
                $user->name = $data['name'];
                $updatedFields[] = 'name';
            }

            if (!empty($updatedFields)) {
                $user->save();
                return back()->with('updated_fields', $updatedFields);
            }

            return back();
        }

        if ($section === 'email') {
            $data = $request->validate([
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            ]);

            if ($data['email'] !== $user->email) {
                $user->email = $data['email'];
                $user->email_verified_at = null;
                $user->save();

                return back()
                    ->with('updated_fields', ['email'])
                    ->with('status', 'profile-updated');
            }

            return back();
        }

        if ($section === 'phone') {
            $data = $request->validate([
                'phone' => 'nullable|string|max:50',
                'phone_full' => 'nullable|string|max:50',
            ]);

            $submittedPhone = ($data['phone_full'] ?? null) ?: $data['phone'];
            $phone = $submittedPhone ? '+' . preg_replace('/\D+/', '', $submittedPhone) : null;
            
            if ($phone !== $user->phone) {
                $userExists = User::where('phone', $phone)
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($userExists && $phone) {
                    return back()->withErrors(['phone' => 'Этот номер уже используется'])->withInput();
                }

                $user->phone = $phone;
                $user->phone_verified_at = null;
                $user->phone_verification_code = null;
                $user->save();

                return back()->with('updated_fields', ['phone']);
            }

            return back();
        }

        if ($section === 'contacts') {
            $data = $request->validate([
                'email'  => 'required|email|max:255|unique:users,email,' . $user->id,
                'phone'  => 'nullable|string|max:50',
                'phone_full' => 'nullable|string|max:50',
                'phone_dirty' => 'nullable|boolean',
            ]);

            $changed = false;

            if ($data['email'] !== $user->email) {
                $user->email = $data['email'];
                $user->email_verified_at = null;
                $updatedFields[] = 'email';
                $changed = true;
            }

            if ($request->boolean('phone_dirty')) {
                $submittedPhone = ($data['phone_full'] ?? null) ?: $data['phone'];
                $phone = $submittedPhone ? '+' . preg_replace('/\D+/', '', $submittedPhone) : null;

                if ($phone && !preg_match('/^\+\d{8,15}$/', $phone)) {
                    return back()->withErrors(['phone' => 'Введите полный номер с кодом страны'])->withInput();
                }
                
                if ($phone !== $user->phone) {
                    $userExists = User::where('phone', $phone)
                        ->where('id', '!=', $user->id)
                        ->exists();

                    if ($userExists && $phone) {
                        return back()->withErrors(['phone' => 'Этот номер уже используется'])->withInput();
                    }

                    $user->phone = $phone;
                    $user->phone_verified_at = null;
                    $user->phone_verification_code = null;
                    $updatedFields[] = 'phone';
                    $changed = true;
                }
            }

            if ($changed) {
                $user->save();
            }

            if (!empty($updatedFields)) {
                return back()->with('updated_fields', $updatedFields);
            }

            return back();
        }

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'phone'  => 'nullable|string|max:50',
            'phone_full' => 'nullable|string|max:50',
        ]);

        $changed = false;

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $user->avatar = $request->file('avatar')->store('avatars', 'public');
            $updatedFields[] = 'avatar';
            $changed = true;
        }

        if ($data['name'] !== $user->name) {
            $user->name = $data['name'];
            $updatedFields[] = 'name';
            $changed = true;
        }

        if ($data['email'] !== $user->email) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
            $updatedFields[] = 'email';
            $changed = true;
        }

        if ($request->has('phone')) {
            $submittedPhone = ($data['phone_full'] ?? null) ?: $data['phone'];
            $phone = $submittedPhone ? '+' . preg_replace('/\D+/', '', $submittedPhone) : null;
            
            if ($phone !== $user->phone) {
                $userExists = User::where('phone', $phone)
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($userExists && $phone) {
                    return back()->withErrors(['phone' => 'Этот номер уже используется'])->withInput();
                }

                $user->phone = $phone;
                $user->phone_verified_at = null;
                $user->phone_verification_code = null;
                $updatedFields[] = 'phone';
                $changed = true;
            }
        }

        if ($changed) {
            $user->save();
        }

        if (!empty($updatedFields)) {
            return back()->with('updated_fields', $updatedFields);
        }

        return back();
    }

    /* =========================
     * ПОДТВЕРЖДЕНИЕ ТЕЛЕФОНА ПОЛЬЗОВАТЕЛЯ
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
     * ПОДТВЕРЖДЕНИЕ ТЕЛЕФОНА МАГАЗИНА (НОВЫЕ МЕТОДЫ)
     * ========================= */
public function sendShopPhoneVerification(Request $request): RedirectResponse
{
    $shop = $request->user()->shop;
    
    if (!$shop || !$shop->phone) {
        return back()->withErrors(['phone' => 'Укажите номер телефона магазина']);
    }
    
    // Используем метод модели для генерации кода
    $code = $shop->generateVerificationCode();
    
    // Здесь отправка SMS
    // log или отправка
    
    return back()->with('shop_phone_verification_sent', true);
}

public function verifyShopPhone(Request $request): RedirectResponse
{
    $request->validate([
        'code' => 'required|digits:6',
    ]);
    
    $shop = $request->user()->shop;
    
    if (!$shop) {
        return back()->withErrors(['shop' => 'Магазин не найден']);
    }
    
    // Используем метод модели для верификации
    if ($shop->verifyPhone($request->code)) {
        return back()->with('status', 'shop-phone-verified');
    }
    
    return back()->withErrors(['code' => 'Неверный или просроченный код']);
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
    $phoneChanged = false;
    if (!empty($data['phone'])) {
        $phone = '+' . preg_replace('/\D+/', '', $data['phone']);
        
        // Если телефон изменился
        if ($phone !== $shop->phone) {
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
            
            $data['phone'] = $phone;
            $phoneChanged = true;
        } else {
            unset($data['phone']); // не меняем, если тот же
        }
    }

    // Обновляем основные поля
    $shop->update($data);

    // Если телефон изменился, сбрасываем верификацию через метод модели
    if ($phoneChanged) {
        $shop->update([
            'phone_verified_at' => null,
            'phone_verification_code' => null,
            'phone_verification_expires_at' => null,
        ]);
    }

    return Redirect::route('profile.edit')->with('status', 'shop-updated');
}

    /* =========================
     * КАБИНЕТ
     * ========================= */


/**
 * Редирект на профиль согласно роли (старый метод, может пригодиться для отдельных страниц профиля)
 */
// public function redirectToRoleProfile()
// {
//     $user = auth()->user();
    
//     if ($user->role === 'buyer') {
//         return redirect()->route('buyer.profile');
//     }
    
//     if ($user->role === 'admin') {
//         return redirect()->route('admin.profile.edit');
//     }
    
//     // seller - оставляем на существующей странице
//     return redirect()->route('profile.edit');
// }



// ридирект на профиль согласно роли (новый метод, с учетом существующих маршрутов)
public function redirectToRoleProfile()
{
    $user = auth()->user();
    
    if ($user->role === 'buyer') {
        return redirect()->route('buyer.profile');
    }
    
    if ($user->role === 'admin') {
        // Используем существующий маршрут админа
        return redirect()->route('admin.dashboard'); // или redirect('/admin')
    }
    
    return redirect()->route('profile.edit');
}




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
          'link' => route('product.show', $p->slug),
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
