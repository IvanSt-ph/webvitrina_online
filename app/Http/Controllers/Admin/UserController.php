<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class UserController extends Controller
{
    // 📋 Список пользователей
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%$q%")
                    ->orWhere('email', 'like', "%$q%")
                    ->orWhere('phone', 'like', "%$q%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(10)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    // 👁️ Показать одного пользователя
    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    // ✏️ Форма редактирования
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    // 💾 Обновить пользователя
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'phone'    => 'nullable|string|max:20',
            'role'     => 'required|in:admin,seller,buyer',
            'password' => 'nullable|string|min:6|confirmed',
            'avatar'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Нормализация телефона (единый метод)
        $phone = $this->normalizePhone($request->phone);
        
        // Проверка уникальности телефона после нормализации
        if ($phone && User::where('phone', $phone)->where('id', '!=', $user->id)->exists()) {
            return back()->withErrors(['phone' => 'Этот телефон уже используется'])->withInput();
        }

        $userData = [
            'name'  => $request->name,
            'email' => $request->email,
            'phone' => $phone,
            'role'  => $request->role,
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        if ($request->hasFile('avatar')) {
            $userData['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($userData);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Пользователь обновлён');
    }

    // 🗑️ Удалить пользователя
    public function destroy(User $user)
    {
        $user->delete();

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
            'password' => 'required|string|min:6|confirmed',
            'role'     => 'required|in:admin,seller,buyer',
            'avatar'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // Нормализация телефона (единый метод)
            $phone = $this->normalizePhone($request->phone);
            
            // Проверка уникальности телефона после нормализации
            if ($phone && User::where('phone', $phone)->exists()) {
                throw new \Exception('Этот телефон уже используется');
            }
            
            // Подготовка данных
            $userData = [
                'name'     => $request->name,
                'email'    => $request->email,
                'phone'    => $phone,
                'password' => Hash::make($request->password),
                'role'     => $request->role,
            ];

            if ($request->hasFile('avatar')) {
                $userData['avatar'] = $request->file('avatar')->store('avatars', 'public');
            }

            // Создание пользователя
            $user = User::create($userData);

            // Если продавец - создаем магазин
            if ($user->role === 'seller') {
                $this->createShopForSeller($user, $phone);
            }

            DB::commit();

            return redirect()
                ->route('admin.users.index')
                ->with('success', "Пользователь {$user->name} успешно создан");

        } catch (Throwable $e) {
            DB::rollBack();
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Ошибка при создании пользователя: ' . $e->getMessage()]);
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

    /**
     * Создание магазина для продавца
     */
    private function createShopForSeller(User $user, ?string $phone): Shop
    {
        $shopName = !empty($user->name) 
            ? "Магазин {$user->name}" 
            : "Мой магазин";

        $baseSlug = Str::slug($shopName) ?: 'shop-' . $user->id;

        $slug = $baseSlug;
        $counter = 1;
        while (Shop::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        return Shop::create([
            'user_id'   => $user->id,
            'name'      => $shopName,
            'slug'      => $slug,
            'phone'     => $phone,
            'is_active' => false,
        ]);
    }
}