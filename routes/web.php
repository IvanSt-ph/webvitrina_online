<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProductController,
    FavoriteController,
    CartController,
    OrderController,
    ReviewController,
    ProfileController,
    CategoryController,
    UserAddressController,
    SellerController,
    CheckoutController
};
use App\Http\Controllers\Seller\ProductManageController as SellerProducts;
use App\Models\Country;

use App\Http\Controllers\Seller\CabinetController;
use App\Http\Controllers\Seller\AnalyticsController;

use App\Http\Controllers\Seller\HelpController as SellerHelpController;

use App\Http\Controllers\Seller\CategoryController as SellerCategoryController;







/*
|--------------------------------------------------------------------------
| 🌍 Публичные маршруты
|--------------------------------------------------------------------------
*/

// 🏠 Главная
Route::get('/', [ProductController::class, 'index'])->name('home');

// 🛍️ Товар
Route::get('/p/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/p/{key}', [ProductController::class, 'show'])->name('product.short');

// 📂 Категории
Route::get('/category', [CategoryController::class, 'index'])->name('category.index');
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show');

// 🌎 Страны → Города
Route::get('/countries/{country}/cities', fn (Country $country) =>
    $country->cities()->select('id','name')->orderBy('name')->get()
)->name('countries.cities');

// 🔹 Установка валюты
Route::post('/currency', [\App\Http\Controllers\CurrencyController::class, 'set'])->name('currency.set');

// 🔹 Заглушка
Route::view('/coming-soon', 'errors.coming-soon')->name('coming.soon');

// 👤 Общая точка входа в кабинет
Route::get('/cabinet', [ProfileController::class, 'cabinet'])->name('cabinet');












/*
|--------------------------------------------------------------------------
| 👤 Авторизованные маршруты
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // 👤 Профиль
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/shop', [ProfileController::class, 'updateShop'])->name('profile.shop.update');


    // 🔐 Смена пароля
    Route::put('/password', [\App\Http\Controllers\Auth\PasswordController::class, 'update'])
    ->name('password.update');

    
    // 👤 Профиль покупателя
    Route::middleware(['verified'])->group(function () {
        Route::get('/buyer/profile', fn() => view('buyer.profile'))->name('buyer.profile');
    });

    // 📝 Мои отзывы (Покупатель)
    Route::get('/my-reviews', [ReviewController::class, 'userReviews'])
    ->name('reviews.index');
    // ❓ Вопросы и ответы (покупатель)
    Route::get('/my-questions', function () {
        return view('buyer.questions.index');
    })->name('questions.index');
    // 💬 Чаты покупателя
    Route::get('/my-chats', function () {
        return view('buyer.chats.index');
    })->name('chats.index');
    // 🔔 Настройки уведомлений (покупателя)
    Route::get('/notifications/settings', function () {
        return view('buyer.notifications.settings');
    })->name('notifications.settings');
    // 🌐 Выбор языка интерфейса
    Route::get('/settings/language', function () {
        return view('buyer.settings.language');
    })->name('settings.language');
    // 💱 Выбор валюты
    Route::get('/settings/currency', function () {
        return view('buyer.settings.currency');
    })->name('settings.currency');
    // 🆘 Служба поддержки (покупатель)
    Route::get('/support', function () {
        return view('buyer.support.index');
    })->name('support');
    // 📘 Справка WebVitrina (покупатель)
    Route::get('/help', function () {
        return view('buyer.help.index');
    })->name('help');
    // 🛍 Стать продавцом (информация + ссылка на регистрацию)
    Route::get('/seller/register', function () {
        return view('buyer.seller.register');
    })->name('seller.register');
    // ℹ️ О сайте / О приложении
    Route::get('/about', function () {
        return view('buyer.about.index');
    })->name('about');






    // ⭐ Избранное
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{product}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

   
    // 🛒 Корзина
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{item}', [CartController::class, 'remove'])->name('cart.remove');

    // 🔢 AJAX-счётчик товаров в корзине
Route::get('/cart-count', function () {
    return [
        'count' => \App\Models\CartItem::where('user_id', auth()->id())->sum('qty')
    ];
});



    // 📦 Заказы
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');



    /*
    |--------------------------------------------------------------------------
    | ⚡ CHECKOUT — новый, правильный, рабочий
    |--------------------------------------------------------------------------
    */

    // ⚡ Купить сейчас (POST)
    Route::post('/checkout/quick/{product}', [CheckoutController::class, 'quick'])
        ->name('checkout.quick');

    // 🛠 Подготовка данных (выбранные / вся корзина) — только POST
    Route::post('/checkout/confirm', [CheckoutController::class, 'prepare'])
        ->name('checkout.prepare');

    // 📄 Страница подтверждения заказа — GET
    Route::get('/checkout/confirm', [CheckoutController::class, 'confirm'])
        ->name('checkout.confirm');

    // 🧾 Создание заказа — POST
    Route::post('/checkout/create', [CheckoutController::class, 'create'])
        ->name('checkout.create');










    // 📬 Адреса доставки
    Route::get('/addresses', [UserAddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses', [UserAddressController::class, 'store'])->name('addresses.store');
    Route::put('/addresses/{address}', [UserAddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{address}', [UserAddressController::class, 'destroy'])->name('addresses.destroy');
    Route::post('/addresses/{address}/default', [UserAddressController::class, 'makeDefault'])->name('addresses.default');

    // 📝 Отзывы
    Route::post('/review/{product}', [ReviewController::class, 'store'])->name('review.store');







/*
|--------------------------------------------------------------------------
| 🏪 Панель продавца
|--------------------------------------------------------------------------
*/
Route::middleware('role:seller')->prefix('seller')->name('seller.')->group(function () {

    // 🔹 AJAX: подкатегории
    Route::get('/categories/{parent}/children', [SellerCategoryController::class, 'children'])
        ->name('categories.children');

    // 🔹 AJAX: цепочка категорий
    Route::get('/categories/chain/{id}', [SellerCategoryController::class, 'chain'])
        ->name('categories.chain');

    // 🔹 AJAX: атрибуты по категории
    Route::get('/categories/{category}/attributes', [SellerProducts::class, 'getCategoryAttributes'])
        ->name('categories.attributes');

    // 📰 Справка и новости
    Route::get('/help/{slug}', [SellerHelpController::class, 'show'])->name('help');
    Route::get('/help', [SellerHelpController::class, 'index'])->name('help.index');

    // 🏠 Кабинет
    Route::get('/cabinet', [CabinetController::class, 'index'])->name('cabinet');

    // 📦 Товары
    Route::get('/products', [SellerProducts::class, 'index'])->name('products.index');
    Route::get('/products/create', [SellerProducts::class, 'create'])->name('products.create');
    Route::post('/products', [SellerProducts::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [SellerProducts::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [SellerProducts::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [SellerProducts::class, 'destroy'])->name('products.destroy');

    // 🖼️ Галерея
    Route::delete('/products/{product}/gallery', [SellerProducts::class, 'deleteGalleryImage'])
        ->name('products.gallery.delete');

    // 🧾 Заглушки
    Route::view('/orders', 'seller.orders.index')->name('orders.index');
    Route::view('/finance', 'seller.finance.index')->name('finance.index');

    // 📊 Аналитика
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/day/{date}', [AnalyticsController::class, 'dayStats'])->name('analytics.day');
    Route::get('/analytics/products-on/{date}', [AnalyticsController::class, 'productsOn']);
});

}); // конец блока авторизации (auth)




/*
|--------------------------------------------------------------------------
| 🧾 Магазин продавца (публичный)
|--------------------------------------------------------------------------
*/
Route::get('/seller/{user}', [SellerController::class, 'show'])->name('seller.show');


/*
|--------------------------------------------------------------------------
| Dashboard и аутентификация
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', fn() => redirect()->route('home'))->name('dashboard');
require __DIR__ . '/auth.php';



/*
|--------------------------------------------------------------------------
| 🛠 Админка
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Admin\{
    DashboardController,
    UserController as AdminUserController,
    ProductController as AdminProductController,
    OrderController as AdminOrderController,
    AdminProfileController,
    BannerController,
    ReviewController as AdminReviewController
};
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\Admin\CategoryAttributeController;




Route::prefix('admin')->name('admin.')->middleware(['auth', AdminMiddleware::class])->group(function () {


// маршруты от изменения Атрибутов категорий
Route::get('/categories/{category}/attributes', [CategoryAttributeController::class, 'index'])->name('categories.attributes');
Route::post('/categories/{category}/attributes', [CategoryAttributeController::class, 'store'])->name('categories.attributes.store');
Route::delete('/categories/{category}/attributes/{attribute}', [CategoryAttributeController::class, 'destroy'])->name('categories.attributes.destroy');


    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('users', AdminUserController::class);
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/categories/{id}/children', [AdminCategoryController::class, 'children'])->name('categories.children');
    Route::get('/categories/root', [AdminCategoryController::class, 'root'])->name('categories.root');
    Route::get('/categories/{id}/parent', [AdminCategoryController::class, 'parent'])->name('categories.parent');

    Route::get('/products/search', [AdminProductController::class, 'search'])->name('products.search');
    Route::resource('products', AdminProductController::class);

    Route::delete('/products/{product}/gallery', [AdminProductController::class, 'deleteGalleryImage'])
        ->name('products.gallery.delete');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
    Route::resource('banners', BannerController::class)->except(['show']);
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{review}/reject', [AdminReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::get('/reviews/{review}', [AdminReviewController::class, 'show'])->name('reviews.show');
});
