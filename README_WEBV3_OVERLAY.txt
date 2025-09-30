Laravel WebV3 Shop — Overlay

1) Создай новый проект:
   composer create-project laravel/laravel webv3
   cd webv3

2) Breeze (Blade):
   composer require laravel/breeze --dev
   php artisan breeze:install blade
   npm install
   npm run build  (или npm run dev)

3) Скопируй содержимое ZIP в корень проекта, согласись на замену файлов.

4) В app/Http/Kernel.php добавь middleware:
   protected $routeMiddleware = [
       // ...
       'role' => \App\Http\Middleware\EnsureRole::class,
   ];

5) В app/Providers/AuthServiceProvider.php зарегистрируй политику:
   protected $policies = [
       \App\Models\CartItem::class => \App\Policies\CartItemPolicy::class,
   ];

6) Миграции/сиды:
   cp .env.example .env
   php artisan key:generate
   # укажи DB_* в .env
   php artisan migrate --seed
   php artisan storage:link

7) Запусти:
   php artisan serve

Готово: / — каталог, регистрация с выбором роли, /seller/products — панель продавца.
