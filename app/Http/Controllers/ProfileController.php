<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Models\Product;
use App\Models\OrderItem;

use Illuminate\Support\Facades\Http; // для SMS API




class ProfileController extends Controller
{



// Отправка кода на телефон
public function sendPhoneVerification(Request $request)
{
    $user = $request->user();

    if (!$user->phone) {
        return back()->withErrors(['phone' => 'Сначала укажите телефон.']);
    }

    // Генерируем случайный 6-значный код
    $code = rand(100000, 999999);
    $user->phone_verification_code = $code;
    $user->save();

    // Пример отправки через бесплатный сервис для теста (нужна реальная интеграция для настоящих SMS)
    // Http::get('https://sms.ru/sms/send', [
    //     'api_id' => env('SMS_RU_KEY'),
    //     'to'     => $user->phone,
    //     'msg'    => "Ваш код подтверждения: $code",
    //     'json'   => 1
    // ]);

    return back()->with('phone_sent', true);
}

// Проверка кода
public function verifyPhone(Request $request)
{
    $request->validate([
        'code' => 'required|digits:6',
    ]);

    $user = $request->user();

    if ($request->code == $user->phone_verification_code) {
        $user->phone_verified_at = now();
        $user->phone_verification_code = null; // очищаем код
        $user->save();

        return back()->with('status', 'phone-verified');
    }

    return back()->withErrors(['code' => 'Неверный код.']);
}

    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

public function update(Request $request): RedirectResponse
{
    $user = $request->user();

    $validated = $request->validate([
        'name'   => 'required|string|max:255',
        'email'  => 'required|email|max:255',
        'phone'  => 'nullable|string|max:50',
        'avatar' => 'nullable|image|max:2048',
    ]);

    $updatedFields = [];

    // Проверяем изменения полей
    foreach (['name', 'email', 'phone'] as $field) {
        if ($validated[$field] !== $user->$field) {
            $user->$field = $validated[$field];
            $updatedFields[] = $field;
        }
    }

    // Если менялся email — сбрасываем подтверждение
    if (in_array('email', $updatedFields)) {
        $user->email_verified_at = null;
    }

        // Если менялся телефон — сбрасываем подтверждение
    if (in_array('phone', $updatedFields)) {
        $user->phone_verified_at = null;
    }


    // Аватар
    if ($request->hasFile('avatar')) {
        if ($user->avatar) Storage::disk('public')->delete($user->avatar);
        $user->avatar = $request->file('avatar')->store('avatars', 'public');
        $updatedFields[] = 'avatar';
    }

    if (!empty($updatedFields)) {
        $user->save();
        return back()->with('updated_fields', $updatedFields);
    }

    return back();
}

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

            
        // Социальные сети
        'facebook'       => 'nullable|url|max:255',
        'instagram'      => 'nullable|url|max:255',
        'telegram'       => 'nullable|url|max:255',
        'whatsapp'       => 'nullable|url|max:255',
        ]);

        $shop = $user->shop ?? $user->shop()->create([]);

        if ($request->boolean('remove_banner') && $shop->banner) {
            Storage::disk('public')->delete($shop->banner);
            $shop->update(['banner' => null]);
            return Redirect::route('profile.edit')->with('status', 'shop-updated');
        }

        if ($request->hasFile('banner')) {
            if ($shop->banner) Storage::disk('public')->delete($shop->banner);
            $shop->update(['banner' => $request->file('banner')->store('banners', 'public')]);
            return Redirect::route('profile.edit')->with('status', 'shop-updated');
        }

        $shop->update($data);
        return Redirect::route('profile.edit')->with('status', 'shop-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', ['password' => ['required', 'current_password']]);

        $user = $request->user();
        Auth::logout();
        if ($user->avatar) Storage::disk('public')->delete($user->avatar);
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function cabinet()
    {
        $user = auth()->user();
        if (!$user) return view('profile.guest-cabinet');

        if ($user->isSeller()) {
            $orders = \App\Models\Order::whereHas('items.product', fn($q) => $q->where('user_id', $user->id))
                ->with(['items.product.category', 'items.product.city.country', 'address'])
                ->latest()
                ->paginate(10);
            return view('seller.cabinet', compact('user', 'orders'));
        }

        $latestOrders = $user->orders()
            ->with(['items.product.category', 'items.product.city.country'])
            ->latest()
            ->take(3)
            ->get();

        $recommendations = $this->getRecommendations($user);

        return view('profile.buyer-cabinet', compact('user', 'latestOrders', 'recommendations'));
    }

    private function getRecommendations($user)
    {
        // Получаем категории из последних заказов
        $categoryIds = OrderItem::whereHas('order', fn($q) => $q->where('user_id', $user->id))
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->pluck('products.category_id')
            ->unique()
            ->toArray();

        $shownIds = OrderItem::whereHas('order', fn($q) => $q->where('user_id', $user->id))
            ->pluck('product_id')
            ->unique()
            ->toArray();

        // Рекомендации из этих категорий
        $recommendations = Product::whereIn('category_id', $categoryIds)
            ->where('user_id', '!=', $user->id)
            ->whereNotIn('id', $shownIds)
            ->inRandomOrder()
            ->limit(5)
            ->get(['id','title','price','image'])
            ->map(fn($p) => [
                'id'=>$p->id,
                'title'=>$p->title,
                'price'=>$p->price,
                'image'=>$p->image,
                'link'=>route('product.show',$p->id),
            ])
            ->toArray();

        // Если мало — добавляем новые случайные товары
        if (count($recommendations) < 8) {
            $existing = array_column($recommendations,'id');
            $new = Product::where('user_id','!=',$user->id)
                ->whereNotIn('id',$existing)
                ->inRandomOrder()
                ->limit(8 - count($recommendations))
                ->get(['id','title','price','image'])
                ->map(fn($p) => [
                    'id'=>$p->id,
                    'title'=>$p->title,
                    'price'=>$p->price,
                    'image'=>$p->image,
                    'link'=>route('product.show',$p->id),
                ])
                ->toArray();
            $recommendations = array_merge($recommendations,$new);
        }

        return $recommendations;
    }
}
