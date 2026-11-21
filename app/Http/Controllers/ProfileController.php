<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\Shop;
use App\Rules\ImageUploadConstraints;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use App\Services\ImageService;
use App\Services\PhoneAssignmentService;
use App\Services\ShopBannerOperation;
use App\Services\ProfileAvatarOperation;
use App\Services\BackupWriteBarrier;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProfileController extends Controller
{
    private const AVATAR_MAX_FILE_KILOBYTES = ImageUploadConstraints::MAX_FILE_KILOBYTES;

    public function __construct(private readonly PhoneAssignmentService $phones)
    {
    }

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
        $section = $request->input('profile_section');
        $hasPhone = ! ($request->ajax() && $request->hasFile('avatar'))
            && ($section === 'phone'
                || ($section === 'contacts' && $request->boolean('phone_dirty'))
                || (! in_array($section, ['personal', 'email', 'contacts'], true) && $request->has('phone')));
        $phone = null;
        if ($hasPhone) {
            $data = $request->validate([
                'phone' => 'nullable|string|max:50',
                'phone_full' => 'nullable|string|max:50',
            ]);
            $phone = ($data['phone_full'] ?? null) ?: ($data['phone'] ?? null);
        }

        // Preserve phone advisory -> User row lock ordering. Deletion takes
        // the same row lock, so all fields in a combined update stay atomic.
        return $this->phones->run($phone, fn () => app(BackupWriteBarrier::class)->transaction(function () use ($request) {
            $user = User::query()->whereKey($request->user()->getKey())->lockForUpdate()->firstOrFail();
            $files = new ProfileAvatarOperation(app(ImageService::class), DB::connection());

            return $this->updateActive($request, $user, $files);
        }));
    }

    private function updateActive(Request $request, User $user, ProfileAvatarOperation $files)
    {
        $updatedFields = [];
        $section = $request->input('profile_section');

        if ($request->ajax() && $request->hasFile('avatar')) {
            $request->validate([
                'avatar' => $this->avatarValidationRules(required: true),
            ], $this->avatarValidationMessages());

            $oldAvatar = $user->avatar;
            $path = $this->storeAvatar($request->file('avatar'), $files);

            $user->avatar = $path;
            $user->save();

            if ($oldAvatar) {
                $files->deleteAfterCommit($oldAvatar);
            }

            return response()->json([
                'success' => true,
                'message' => 'Аватар успешно обновлен',
                'avatar_url' => $user->avatar_url,
            ]);
        }

        if ($section === 'personal') {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'avatar' => $this->avatarValidationRules(),
            ], $this->avatarValidationMessages());

            $oldAvatar = null;

            if ($request->hasFile('avatar')) {
                $oldAvatar = $user->avatar;
                $path = $this->storeAvatar($request->file('avatar'), $files);

                $user->avatar = $path;
                $updatedFields[] = 'avatar';
            }

            if ($data['name'] !== $user->name) {
                $user->name = $data['name'];
                $updatedFields[] = 'name';
            }

            if (!empty($updatedFields)) {
                $user->save();

                if ($oldAvatar) {
                    $files->deleteAfterCommit($oldAvatar);
                }

                return back()->with('updated_fields', $updatedFields);
            }

            return back();
        }

        if ($section === 'email') {
            $data = $request->validate([
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            ]);

            if ($data['email'] !== $user->email) {
                $this->requireCurrentPassword($request, $user);

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
            $phoneChanged = $this->phones->assignToUser($user, $submittedPhone);

            if ($phoneChanged) {
                $request->session()->forget('phone_verification_sent');

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
                $this->requireCurrentPassword($request, $user);

                $user->email = $data['email'];
                $user->email_verified_at = null;
                $updatedFields[] = 'email';
                $changed = true;
            }

            if ($request->boolean('phone_dirty')) {
                $submittedPhone = ($data['phone_full'] ?? null) ?: $data['phone'];
                $normalizedPhone = $this->phones->normalize($submittedPhone);

                if ($normalizedPhone !== null && ! preg_match('/^\+\d{8,15}$/', $normalizedPhone)) {
                    return back()->withErrors(['phone' => 'Введите полный номер с кодом страны'])->withInput();
                }

                $phoneChanged = $this->phones->assignToUser($user, $normalizedPhone);

                if ($phoneChanged) {
                    $updatedFields[] = 'phone';
                    $request->session()->forget('phone_verification_sent');
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
            'avatar' => $this->avatarValidationRules(),
            'phone'  => 'nullable|string|max:50',
            'phone_full' => 'nullable|string|max:50',
        ], $this->avatarValidationMessages());

        $changed = false;
        $oldAvatar = null;

        if ($request->hasFile('avatar')) {
            $oldAvatar = $user->avatar;
            $path = $this->storeAvatar($request->file('avatar'), $files);

            $user->avatar = $path;
            $updatedFields[] = 'avatar';
            $changed = true;
        }

        if ($data['name'] !== $user->name) {
            $user->name = $data['name'];
            $updatedFields[] = 'name';
            $changed = true;
        }

        if ($data['email'] !== $user->email) {
            $this->requireCurrentPassword($request, $user);

            $user->email = $data['email'];
            $user->email_verified_at = null;
            $updatedFields[] = 'email';
            $changed = true;
        }

        if ($request->has('phone')) {
            $submittedPhone = ($data['phone_full'] ?? null) ?: $data['phone'];
            $phoneChanged = $this->phones->assignToUser($user, $submittedPhone);

            if ($phoneChanged) {
                $updatedFields[] = 'phone';
                $request->session()->forget('phone_verification_sent');
            }
        }

        if ($changed) {
            $user->save();

            if ($oldAvatar) {
                $files->deleteAfterCommit($oldAvatar);
            }
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
        abort(410, 'SMS-подтверждение временно отключено.');
    }

    public function verifyPhone(Request $request): RedirectResponse
    {
        abort(410, 'SMS-подтверждение временно отключено.');
    }

    /* =========================
     * ПОДТВЕРЖДЕНИЕ ТЕЛЕФОНА МАГАЗИНА (НОВЫЕ МЕТОДЫ)
     * ========================= */
    public function sendShopPhoneVerification(Request $request): RedirectResponse
    {
        abort(410, 'SMS-подтверждение временно отключено.');
    }

    public function verifyShopPhone(Request $request): RedirectResponse
    {
        abort(410, 'SMS-подтверждение временно отключено.');
    }

    /* =========================
     * МАГАЗИН ПРОДАВЦА
     * ========================= */

public function updateShop(Request $request): RedirectResponse
{
    abort_unless($request->user()->role === 'seller', 403);

    $data = $request->validate([
        'name'        => 'nullable|string|max:255',
        'city'        => 'nullable|string|max:255',
        'description' => 'nullable|string|max:1000',
        'phone'       => 'nullable|string|max:50',
        'banner'      => ImageUploadConstraints::rules(4096),

        'facebook'    => $this->externalUrlRules(),
        'instagram'   => $this->externalUrlRules(),
        'telegram'    => $this->externalUrlRules(),
        'whatsapp'    => $this->externalUrlRules(),

        'remove_banner' => 'nullable|boolean',
    ]);

    $hasPhone = array_key_exists('phone', $data);
    $submittedPhone = $hasPhone ? $data['phone'] : null;
    unset($data['phone'], $data['remove_banner']);

    $update = function (?string $normalizedPhone = null, ?PhoneAssignmentService $phones = null) use (
        $request,
        $data,
        $hasPhone,
    ): void {
        // The seller row serializes first-shop creation and establishes one lock order
        // for repeated requests: seller -> shop. Phone updates acquire their advisory
        // phone lock before entering this transaction through PhoneAssignmentService.
        $seller = User::query()->whereKey($request->user()->getKey())->lockForUpdate()->firstOrFail();
        $shop = Shop::query()->where('user_id', $seller->getKey())->lockForUpdate()->first();

        if (!$shop) {
            $shop = $seller->shop()->create([]);
        }

        $files = new ShopBannerOperation(
            app(ImageService::class),
            DB::connection(),
        );
        $oldBanner = $shop->banner;
        $attributes = $data;

        if ($request->hasFile('banner')) {
            $attributes['banner'] = $files->upload($request->file('banner'));
        } elseif ($request->boolean('remove_banner')) {
            $attributes['banner'] = null;
        }

        $shop->fill($attributes);

        if ($hasPhone && $shop->phone !== $normalizedPhone) {
            $phones?->assertAvailableForShop($normalizedPhone, (int) $shop->user_id, (int) $shop->getKey());
            $shop->forceFill([
                'phone' => $normalizedPhone,
                'phone_verified_at' => null,
                'phone_verification_code' => null,
                'phone_verification_expires_at' => null,
            ]);
        }

        $shop->save();

        if ($oldBanner && $oldBanner !== $shop->banner) {
            $files->deleteAfterCommit($oldBanner);
        }
    };

    if ($hasPhone) {
        $this->phones->run($submittedPhone, $update);
    } else {
        DB::transaction(fn () => $update(), 3);
    }

    return Redirect::route('profile.edit')->with('status', 'shop-updated');
}

    private function externalUrlRules(): array
{
    return [
        'nullable',
        'string',
        'max:255',
        function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $scheme = parse_url($value, PHP_URL_SCHEME);

            if (in_array($scheme, ['http', 'https'], true) && filter_var($value, FILTER_VALIDATE_URL)) {
                return;
            }

            $fail('Ссылка должна быть URL с http или https.');
        },
    ];
}

private function requireCurrentPassword(Request $request, User $user): void
{
    if (! $user->hasLocalPassword()) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'current_password' => 'Сначала установите пароль в разделе безопасности, чтобы менять контактные данные.',
        ]);
    }

    $request->validate([
        'current_password' => ['bail', 'required', 'string', new \App\Rules\ThrottledCurrentPassword($user)],
    ], [
        'current_password.required' => 'Для изменения контактов подтвердите текущий пароль.',
        'current_password.current_password' => 'Текущий пароль введён неверно.',
    ]);
}

private function avatarValidationRules(bool $required = false): array
{
    return ImageUploadConstraints::rules(self::AVATAR_MAX_FILE_KILOBYTES, $required);
}

private function avatarValidationMessages(): array
{
    return [
        'avatar.required' => 'Выберите изображение для аватара.',
        'avatar.uploaded' => 'Не удалось загрузить файл. Проверьте его размер и попробуйте снова.',
        'avatar.image' => 'Файл должен быть корректным изображением JPG, PNG или WebP.',
        'avatar.mimes' => 'Поддерживаются только изображения JPG, PNG и WebP.',
        'avatar.max' => 'Размер файла аватара не должен превышать 8 МБ.',
    ];
}

private function storeAvatar(UploadedFile $avatar, ProfileAvatarOperation $files): string
{
    try {
        return $files->upload($avatar);
    } catch (Throwable $exception) {
        report($exception);

        throw ValidationException::withMessages([
            'avatar' => 'Не удалось обработать изображение. Убедитесь, что файл не повреждён и имеет формат JPG, PNG или WebP.',
        ]);
    }
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
            ->with(['items.product.category', 'items.product.city.country'])
            ->latest()
            ->paginate(10);

            return view('seller.cabinet', compact('user', 'orders'));
        }

        $latestOrders = $user->orders()
            ->with(['items.product.category', 'items.product.city.country', 'seller.shop'])
            ->latest()
            ->limit(3)
            ->get();

        $unreadMessagesCount = Message::visibleToParticipants()->whereHas('conversation', fn ($query) => $query
                ->where('buyer_id', $user->id)
                ->orWhere('seller_id', $user->id))
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();

        $confirmationOrdersCount = $user->orders()
            ->whereNull('workflow_version')
            ->where('status', Order::STATUS_SHIPPED)
            ->count();

        $reviewableOrdersCount = $user->orders()
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->where(fn ($workflow) => $workflow->whereNull('workflow_version')
                ->orWhere(fn ($pickup) => $pickup->where('workflow_version', Order::WORKFLOW_PICKUP)
                    ->whereNotNull('buyer_confirmed_at')))
            ->whereHas('items.product', fn ($query) => $query->whereDoesntHave(
                'reviews',
                fn ($reviews) => $reviews->where('user_id', $user->id)
            ))
            ->count();

        $supportUnreadCount = Message::visibleToParticipants()->whereHas('conversation', fn ($query) => $query
                ->where('conversation_type', Conversation::TYPE_SUPPORT)
                ->where('buyer_id', $user->id))
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();

        $followedShopsCount = $user->followedShops()->count();
        $latestFollowedShops = $user->followedShops()
            ->with('user')
            ->withCount('followers')
            ->latest('shop_followers.created_at')
            ->limit(3)
            ->get();

        $recommendations = $this->getRecommendations($user);

        return view('profile.buyer-cabinet', compact(
            'user',
            'latestOrders',
            'unreadMessagesCount',
            'confirmationOrdersCount',
            'reviewableOrdersCount',
            'supportUnreadCount',
            'followedShopsCount',
            'latestFollowedShops',
            'recommendations'
        ));
    }

    public function subscriptions()
    {
        $user = auth()->user();
        $search = trim((string) request('q', ''));

        $shops = $user->followedShops()
            ->with('user')
            ->withCount(['followers', 'products'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('shops.name', 'like', "%{$search}%")
                        ->orWhere('shops.city', 'like', "%{$search}%");
                });
            })
            ->latest('shop_followers.created_at')
            ->paginate(12)
            ->withQueryString();

        $subscriptionsCount = $user->followedShops()->count();

        return view('profile.subscriptions', compact('user', 'shops', 'search', 'subscriptionsCount'));
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

        $products = Product::active()
            ->whereIn('category_id', $categoryIds)
            ->whereNotIn('id', $shownIds)
            ->where('user_id', '!=', $user->id)
            ->latest('id')
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
        // Match User::delete(): membership -> backup barrier -> User -> dependents.
        app(\App\Services\AdminMembershipService::class)->transaction(fn () =>
            app(BackupWriteBarrier::class)->transaction(function () use ($request): void {
                $user = User::query()->whereKey($request->user()->getKey())->lockForUpdate()->firstOrFail();
                $request->validate([
                    'password' => ['bail', 'required', 'string', new \App\Rules\ThrottledCurrentPassword($user)],
                ]);
                $user->delete();
            })
        );

        // Deletion already revoked all sessions and devices and cleared the token.
        // The guard still holds the pre-deletion model: logout() would persist
        // a newly rotated remember token from that stale instance.
        Auth::guard('web')->logoutCurrentDevice();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
