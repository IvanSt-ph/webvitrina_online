<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Shop;
use App\Models\AdminActivityLog;
use App\Models\Order;
use App\Models\SellerPlanRequest;
use App\Services\SellerPlanService;
use App\Services\AdminActivityLogger;
use App\Services\ImageService;
use App\Services\UserTrustService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserController extends Controller
{
    public function __construct(
        private readonly UserTrustService $trustService,
        private readonly SellerPlanService $sellerPlans,
        private readonly AdminActivityLogger $activity,
        private readonly ImageService $images
    ) {
    }

    // 📋 Список пользователей
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', 'in:admin,seller,buyer'],
            'state' => ['nullable', 'in:email_verified,phone_verified,no_password,social,sellers_without_shop'],
            'sort' => ['nullable', 'in:latest,oldest,name,orders_desc,products_desc'],
        ]);

        $roleCounts = User::query()
            ->select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        $summary = [
            'total' => User::count(),
            'verified_email' => User::whereNotNull('email_verified_at')->count(),
            'verified_phone' => User::whereNotNull('phone_verified_at')->count(),
            'sellers_without_shop' => User::where('role', 'seller')->doesntHave('shop')->count(),
        ];

        $query = User::query()
            ->with('shop')
            ->withCount(['orders', 'products', 'followedShops']);

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('shop', fn ($shopQuery) => $shopQuery->where('name', 'like', "%{$search}%"));

                if (ctype_digit($search)) {
                    $sub->orWhere('id', (int) $search);
                }
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->toString());
        }

        match ($request->input('state')) {
            'email_verified' => $query->whereNotNull('email_verified_at'),
            'phone_verified' => $query->whereNotNull('phone_verified_at'),
            'no_password' => $query->whereNull('password_set_at'),
            'social' => $query->whereNotNull('provider'),
            'sellers_without_shop' => $query->where('role', 'seller')->doesntHave('shop'),
            default => null,
        };

        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('name'),
            'orders_desc' => $query->orderByDesc('orders_count'),
            'products_desc' => $query->orderByDesc('products_count'),
            default => $query->latest(),
        };

        $users = $query->paginate(12)->withQueryString();
        $trustProfiles = $this->trustService->profilesFor($users->getCollection());

        return view('admin.users.index', compact('users', 'roleCounts', 'summary', 'sort', 'trustProfiles'));
    }

    // 👁️ Показать одного пользователя
    public function show(User $user)
    {
        $user->load([
            'shop' => fn ($query) => $query->withCount('followers'),
        ])
            ->loadCount([
                'orders',
                'salesOrders',
                'products',
                'followedShops',
                'favorites',
                'buyerConversations',
                'sellerConversations',
                'addresses',
            ]);

        $recentOrders = ($user->isSeller() ? $user->salesOrders() : $user->orders())
            ->with(['user', 'seller', 'items.product'])
            ->latest()
            ->limit(5)
            ->get();

        $recentProducts = $user->products()
            ->latest()
            ->limit(5)
            ->get();

        $trustProfile = $this->trustService->profileFor($user);
        $sellerPlanProfile = $user->isSeller() ? $this->sellerPlans->profileFor($user) : null;
        $sellerPlanOptions = $user->isSeller() ? $this->sellerPlans->plans() : [];
        $pendingPlanRequest = $user->isSeller()
            ? $user->sellerPlanRequests()
                ->where('status', SellerPlanRequest::STATUS_PENDING)
                ->latest()
                ->first()
            : null;
        $sellerPlanRequestIds = $user->isSeller()
            ? $user->sellerPlanRequests()->pluck('id')
            : collect();
        $orderStatusCounts = ($user->isSeller() ? $user->salesOrders() : $user->orders())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $commerceSummary = [
            'needs_action_orders' => (int) ($orderStatusCounts[Order::STATUS_PENDING] ?? 0),
            'fulfillment_orders' => (int) collect([
                Order::STATUS_PROCESSING,
                Order::STATUS_PAID,
                Order::STATUS_SHIPPED,
            ])->sum(fn (string $status) => $orderStatusCounts[$status] ?? 0),
            'active_orders' => (int) collect([
                Order::STATUS_PENDING,
                Order::STATUS_PROCESSING,
                Order::STATUS_PAID,
                Order::STATUS_SHIPPED,
            ])->sum(fn (string $status) => $orderStatusCounts[$status] ?? 0),
            'completed_orders' => (int) collect([
                Order::STATUS_DELIVERED,
                Order::STATUS_COMPLETED,
            ])->sum(fn (string $status) => $orderStatusCounts[$status] ?? 0),
            'canceled_orders' => (int) ($orderStatusCounts[Order::STATUS_CANCELED] ?? 0),
            'active_products' => $user->isSeller() ? $user->products()->where('status', 'active')->count() : 0,
            'draft_products' => $user->isSeller() ? $user->products()->where('status', 'draft')->count() : 0,
            'out_of_stock_products' => $user->isSeller()
                ? $user->products()->where('status', 'active')->where('stock', 0)->count()
                : 0,
        ];
        $recentAdminActivity = AdminActivityLog::query()
            ->with('admin')
            ->where(function ($query) use ($user, $sellerPlanRequestIds) {
                $query->where(function ($userQuery) use ($user) {
                    $userQuery->where('subject_type', User::class)
                        ->where('subject_id', $user->id);
                });

                if ($sellerPlanRequestIds->isNotEmpty()) {
                    $query->orWhere(function ($planQuery) use ($sellerPlanRequestIds) {
                        $planQuery->where('subject_type', SellerPlanRequest::class)
                            ->whereIn('subject_id', $sellerPlanRequestIds);
                    });
                }
            })
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.users.show', compact(
            'user', 'recentOrders', 'recentProducts', 'trustProfile', 'sellerPlanProfile',
            'sellerPlanOptions', 'pendingPlanRequest', 'commerceSummary', 'recentAdminActivity'
        ));
    }

    // ✏️ Форма редактирования
    public function edit(User $user)
    {
        $sellerPlans = $this->sellerPlans->plans();
        $sellerPlanProfile = $user->isSeller() ? $this->sellerPlans->profileFor($user) : null;

        return view('admin.users.edit', compact('user', 'sellerPlans', 'sellerPlanProfile'));
    }

    // 💾 Обновить пользователя
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'phone'    => 'nullable|string|max:20',
            'role'     => 'required|in:admin,seller,buyer',
            'seller_plan' => ['nullable', 'in:' . implode(',', $this->sellerPlans->allowedKeys())],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'avatar'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($this->wouldRemoveLastAdmin($user, $validated['role'])) {
            throw ValidationException::withMessages([
                'role' => 'Нельзя убрать роль администратора у последнего администратора.',
            ]);
        }

        // Нормализация телефона (единый метод)
        $phone = $this->normalizePhone($request->phone);
        
        // Проверка уникальности телефона после нормализации
        if ($phone && (
            User::where('phone', $phone)->where('id', '!=', $user->id)->exists()
            || Shop::where('phone', $phone)->where('user_id', '!=', $user->id)->exists()
        )) {
            return back()->withErrors(['phone' => 'Этот телефон уже используется'])->withInput();
        }

        $sellerPlan = $request->role === 'seller'
            ? ($validated['seller_plan'] ?? SellerPlanService::STARTER)
            : SellerPlanService::STARTER;

        if ($request->role === 'seller' && ! $this->sellerPlans->canAssignPlan($user, $sellerPlan)) {
            throw ValidationException::withMessages([
                'seller_plan' => $this->sellerPlans->assignmentLimitMessage($user, $sellerPlan),
            ]);
        }

        $userData = [
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $phone,
            'role'  => $request->role,
            'seller_plan' => $sellerPlan,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
            $userData['password_set_at'] = now();
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                $this->images->delete($user->avatar);
            }

            $userData['avatar'] = $this->images->upload($request->file('avatar'), 'avatars');
        }

        $before = $user->only(['name', 'email', 'phone', 'role', 'seller_plan']);
        $user->update($userData);
        $after = $user->fresh()->only(['name', 'email', 'phone', 'role', 'seller_plan']);

        $this->activity->log('user.updated', $user, 'Администратор изменил пользователя.', [
            'before' => $before,
            'after' => $after,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Пользователь обновлён');
    }

    // 🗑️ Удалить пользователя
    public function destroy(User $user)
    {
        abort_if($user->is(auth()->user()), 403, 'Нельзя удалить собственный аккаунт через админку.');

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'Нельзя удалить последнего администратора.',
            ]);
        }

        $user->delete();

        $this->activity->log('user.deleted', $user, 'Администратор удалил пользователя.', [
            'deleted_user_email_hash' => hash('sha256', (string) $user->email),
            'deleted_user_role' => $user->role,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Пользователь удалён');
    }

    // ➕ Форма добавления
    public function create()
    {
        return view('admin.users.create');
    }

    // ✅ Сохранить нового пользователя
    public function store(Request $request)
    {
        // Валидация БЕЗ unique для phone (проверим после нормализации)
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'phone'    => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role'     => 'required|in:admin,seller,buyer',
            'avatar'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // Нормализация телефона (единый метод)
            $phone = $this->normalizePhone($request->phone);
            
            // Проверка уникальности телефона после нормализации
            if ($phone && (
                User::where('phone', $phone)->exists()
                || Shop::where('phone', $phone)->exists()
            )) {
                throw ValidationException::withMessages([
                    'phone' => 'Этот телефон уже используется',
                ]);
            }
            
            // Подготовка данных
            $userData = [
                'name'     => $request->name,
                'email'    => $request->email,
                'phone'    => $phone,
                'password' => Hash::make($request->password),
                'password_set_at' => now(),
                'role'     => $request->role,
                'seller_plan' => SellerPlanService::STARTER,
            ];

            if ($request->hasFile('avatar')) {
                $userData['avatar'] = $this->images->upload($request->file('avatar'), 'avatars');
            }

            // Создание пользователя
            $user = User::create($userData);

            // Если продавец - создаем магазин
            if ($user->role === 'seller') {
                $this->createShopForSeller($user, $phone);
            }

            $this->activity->log('user.created', $user, 'Администратор создал пользователя.', [
                'role' => $user->role,
            ]);

            DB::commit();

            return redirect()
                ->route('admin.users.index')
                ->with('success', "Пользователь {$user->name} успешно создан");

        } catch (ValidationException $e) {
            DB::rollBack();

            throw $e;

        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Admin user creation failed', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Не удалось создать пользователя. Проверьте данные и попробуйте позже.']);
        }
    }

    // 🔧 Единый метод нормализации телефона
    private function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Удаляем все нецифровые символы
        $digits = preg_replace('/\D+/', '', $phone);
        
        // Проверка длины (7-15 цифр)
        if (strlen($digits) < 7 || strlen($digits) > 15) {
            return null;
        }

        // Всегда храним в формате E.164 (с +)
        return '+' . $digits;
    }

    private function wouldRemoveLastAdmin(User $user, string $newRole): bool
    {
        return $user->role === 'admin'
            && $newRole !== 'admin'
            && User::where('role', 'admin')->count() <= 1;
    }

    /**
     * Создание магазина для продавца
     */
    private function createShopForSeller(User $user, ?string $phone): Shop
    {
        $shopName = !empty($user->name) 
            ? "Магазин {$user->name}" 
            : "Мой магазин";

        return Shop::create([
            'user_id'   => $user->id,
            'name'      => $shopName,
            'phone'     => $phone,
        ]);
    }
}
