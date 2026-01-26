<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Shop;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;


class RegisteredUserController extends Controller
{
    private const PHONE_MIN_LENGTH = 7;
    private const PHONE_MAX_LENGTH = 15;
    private const ROLE_BUYER = 'buyer';
    private const ROLE_SELLER = 'seller';
    private const DEFAULT_SHOP_NAME = 'Мой магазин';

    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        // Предварительная валидация телефона (опционально)
        $request->validate([
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        DB::beginTransaction();

        try {
            // 1. Нормализация телефона
            $phone = $this->normalizePhone($request->input('phone'));

            if ($request->filled('phone') && $phone === null) {
                throw ValidationException::withMessages([
                    'phone' => 'Неверный формат телефона'
                ]);
            }

            // 2. Проверка уникальности телефона
            if ($phone && $this->isPhoneAlreadyUsed($phone)) {
                throw ValidationException::withMessages([
                    'phone' => 'Этот телефон уже используется другим пользователем или магазином'
                ]);
            }

            // 3. Валидация основных данных
                $validatedData = $request->validate([
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
                    'password' => ['required', 'confirmed', Rules\Password::defaults()],
                    'role' => ['required', 'in:' . self::ROLE_BUYER . ',' . self::ROLE_SELLER],
                    'terms' => ['accepted'],
                ]);


            // 4. Создание пользователя
            $user = $this->createUser($validatedData, $phone);

            // 5. Создание магазина для продавца
            if ($user->role === self::ROLE_SELLER) {
                $this->createShopForSeller($user, $phone);
            }

            // 6. Событие регистрации
            event(new Registered($user));

            // 7. Авторизация
            Auth::login($user);

            DB::commit();

            // 8. Логирование успеха
            $this->logSuccessfulRegistration($user);

            // 9. Редирект с сообщением
            return redirect()->route('home')
                ->with('success', $this->getWelcomeMessage($user->role));

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logRegistrationError($e, $request);
            
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors([
                    'error' => 'Произошла ошибка при регистрации. Пожалуйста, попробуйте позже.'
                ]);
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        
        if (strlen($digits) < self::PHONE_MIN_LENGTH || 
            strlen($digits) > self::PHONE_MAX_LENGTH) {
            return null;
        }

        return '+' . $digits;
    }

    private function isPhoneAlreadyUsed(string $phone): bool
    {
        return User::where('phone', $phone)->exists() || 
               Shop::where('phone', $phone)->exists();
    }

    private function createUser(array $data, ?string $phone): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'phone' => $phone,
        ]);
    }



private function createShopForSeller(User $user, ?string $phone): Shop
{
    // Формируем имя магазина
    $shopName = !empty($user->name) 
        ? "Магазин {$user->name}" 
        : self::DEFAULT_SHOP_NAME;

    // Генерируем базовый slug
    $baseSlug = Str::slug($shopName) ?: 'shop-' . $user->id;


    // Проверка уникальности slug
    $slug = $baseSlug;
    $counter = 1;
    while (Shop::where('slug', $slug)->exists()) {
        $slug = $baseSlug . '-' . $counter++;
    }

    // Создаём магазин
    return Shop::create([
        'user_id' => $user->id,
        'name' => $shopName,
        'slug' => $slug,
        'phone' => $phone,
        'is_active' => false,
    ]);
}


    private function getWelcomeMessage(string $role): string
    {
        return $role === self::ROLE_SELLER
            ? 'Добро пожаловать! Ваш магазин создан. Заполните информацию в личном кабинете.'
            : 'Добро пожаловать! Регистрация успешно завершена.';
    }

    private function logSuccessfulRegistration(User $user): void
    {
        Log::channel('registration')->info('User registered', [
            'user_id' => $user->id,
            'role' => $user->role,
             'email_hash' => hash('sha256', $user->email),
            'phone_provided' => !empty($user->phone),
        ]);
    }

    private function logRegistrationError(\Exception $e, Request $request): void
    {
        $logData = [
            'error' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'input' => [
                'email' => $request->input('email'),
                'role' => $request->input('role'),
                'has_phone' => $request->filled('phone'),
            ],
        ];

        // Полный trace только в режиме отладки
        if (config('app.debug')) {
            $logData['trace'] = $e->getTraceAsString();
        }

        Log::channel('registration')->error('Registration failed', $logData);
    }
}