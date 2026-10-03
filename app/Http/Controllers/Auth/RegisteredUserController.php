<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Shop;
use App\Services\PhoneAssignmentService;
use App\Services\SellerPlanService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;


class RegisteredUserController extends Controller
{
    private const ROLE_BUYER = 'buyer';
    private const ROLE_SELLER = 'seller';
    private const DEFAULT_SHOP_NAME = 'Мой магазин';

    public function __construct(private readonly PhoneAssignmentService $phones)
    {
    }

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

        try {
            $validatedData = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'role' => ['required', 'in:' . self::ROLE_BUYER . ',' . self::ROLE_SELLER],
                'terms' => ['accepted'],
            ]);

            $user = $this->phones->run(
                $request->input('phone'),
                function (?string $phone, PhoneAssignmentService $phones) use ($validatedData): User {
                    $user = $this->createUser($validatedData);

                    if ($phone !== null) {
                        $phones->assertAvailableForUser($phone, $user->id, $user->id);
                        $user->forceFill(['phone' => $phone])->save();
                    }

                    if ($user->role === self::ROLE_SELLER) {
                        $phones->assertAvailableForShop($phone, $user->id);
                        $this->createShopForSeller($user, $phone);
                    }

                    event(new Registered($user));
                    Auth::login($user);

                    return $user;
                },
            );

            // 8. Логирование успеха
            $this->logSuccessfulRegistration($user);

            // 9. Редирект с сообщением
            return redirect()->route('home')
                ->with('success', $this->getWelcomeMessage($user->role));

        } catch (ValidationException $e) {
            throw $e;
            
        } catch (\Exception $e) {
            $this->logRegistrationError($e, $request);
            
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors([
                    'error' => 'Произошла ошибка при регистрации. Пожалуйста, попробуйте позже.'
                ]);
        }
    }

    private function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'password_set_at' => now(),
            'role' => $data['role'],
            'seller_plan' => SellerPlanService::STARTER,
            'phone' => null,
        ]);
    }



private function createShopForSeller(User $user, ?string $phone): Shop
{
    $shopName = !empty($user->name) 
        ? "Магазин {$user->name}" 
        : self::DEFAULT_SHOP_NAME;

    return Shop::create([
        'user_id' => $user->id,
        'name' => $shopName,
        'phone' => $phone,
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
