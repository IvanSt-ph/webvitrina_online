<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Models\Country;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\PhoneVerificationController;

/*
|--------------------------------------------------------------------------
| 📦 CONTROLLERS
|--------------------------------------------------------------------------
*/
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
    CheckoutController,
    OrderStatusController
};

use App\Http\Controllers\Seller\ProductManageController as SellerProducts;
use App\Http\Controllers\Seller\{
    CabinetController,
    AnalyticsController,
    HelpController as SellerHelpController,
    CategoryController as SellerCategoryController,
    OrderController as SellerOrderController
};

use App\Http\Controllers\Admin\{
    DashboardController,
    UserController as AdminUserController,
    ProductController as AdminProductController,
    OrderController as AdminOrderController,
    AdminProfileController,
    BannerController,
    ReviewController as AdminReviewController,
    CategoryAttributeController,
    CategoryController as AdminCategoryController
};

use App\Http\Controllers\CurrencyProxyController;
use App\Http\Controllers\Auth\GoogleController;



/*
|--------------------------------------------------------------------------
| 🌍 PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/phone/send', [PhoneVerificationController::class, 'send'])
    ->name('phone.send')
    ->middleware('throttle:3,10');

Route::post('/phone/verify', [PhoneVerificationController::class, 'verify'])
    ->name('phone.verify')
    ->middleware('throttle:3,1');


// 💱 Валюты
Route::get('/internal/currency/agroprombank', [
    CurrencyProxyController::class, 'agroprombank'
]);

// 🏠 Главная
Route::get('/', [ProductController::class, 'index'])->name('home');


// 🛍 Товар
// Slug: минимум 3 символа, может содержать дефисы
Route::get('/p/{slug}', [ProductController::class, 'show'])
    ->where('slug', '[a-z0-9\-]{3,}')  // минимум 3 символа
    ->name('product.show');

// Key: только заглавные+цифры, фиксированная длина
Route::get('/p/{key}', [ProductController::class, 'show'])
    ->where('key', '[A-Z0-9]{6,15}')  // от 6 до 10 символов
    ->name('product.short');


    
// 📂 Категории
Route::get('/category',        [CategoryController::class, 'index'])->name('category.index');
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show');

// AJAX загрузка товаров по фильтрам
Route::get('/category-ajax/{slug}', [CategoryController::class, 'ajax'])
    ->name('category.ajax');


// 🌎 Города
Route::get('/countries/{country}/cities', function (Country $country) {
    return $country->cities()->select('id','name')->orderBy('name')->get();
})->name('countries.cities');

// 💱 Смена валюты
Route::post('/currency', [\App\Http\Controllers\CurrencyController::class, 'set'])
    ->name('currency.set');

// 🚧 Coming Soon
Route::view('/coming-soon', 'errors.coming-soon')->name('coming.soon');

// Кабинет входа
Route::get('/cabinet', [ProfileController::class, 'cabinet'])
    ->middleware('auth')
    ->name('cabinet');



/*
|--------------------------------------------------------------------------
| 🔐 AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    /*--------------------------------------------------------------------------
    | 🛍 PRODUCTS & CATEGORIES (общий доступ)
    |--------------------------------------------------------------------------*/
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/categories', [CategoryController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | 👤 PROFILE (основной)
    |--------------------------------------------------------------------------
    */
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Shop info (продавец)
    Route::patch('/profile/shop', [ProfileController::class, 'updateShop'])
        ->name('profile.shop.update');


    /*
    |--------------------------------------------------------------------------
    | 👤 BUYER AREA
    |--------------------------------------------------------------------------
    */

    // Основная инфа
    Route::get('/buyer/profile', fn() => view('buyer.profile.general'))
        ->name('buyer.profile');

    // Безопасность
    Route::get('/buyer/profile/security', fn() => view('buyer.profile.security'))
        ->name('buyer.profile.security');

    // Обновление именно покупательского профиля
    Route::patch('/buyer/profile/update', [ProfileController::class, 'update'])
        ->name('buyer.profile.update');

    // Прочие buyer-странички
    Route::view('/my-questions', 'buyer.questions.index')->name('questions.index');
    Route::view('/my-chats', 'buyer.chats.index')->name('chats.index');
    Route::view('/notifications/settings', 'buyer.notifications.settings')->name('notifications.settings');
    Route::view('/settings/language', 'buyer.settings.language')->name('settings.language');
    Route::view('/settings/currency', 'buyer.settings.currency')->name('settings.currency');
    Route::view('/support', 'buyer.support.index')->name('support');
    Route::view('/help', 'buyer.help.index')->name('help');
    Route::view('/seller/register', 'buyer.seller.register')->name('seller.register');
    Route::view('/about', 'buyer.about.index')->name('about');

    // Мои отзывы
    Route::get('/my-reviews', [ReviewController::class, 'userReviews'])->name('reviews.index');


    /*
    |--------------------------------------------------------------------------
    | ⭐ FAVORITES
    |--------------------------------------------------------------------------
    */
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{product}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');


    /*
    |--------------------------------------------------------------------------
    | 🛒 CART
    |--------------------------------------------------------------------------
    */
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])
    ->name('cart.add')
    ->middleware('throttle:20,1');

    Route::patch('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{item}', [CartController::class, 'remove'])->name('cart.remove');

    Route::get('/cart-count', fn() => ['count' => \App\Models\CartItem::where('user_id', auth()->id())->sum('qty')])
    ->middleware('auth')
    ->name('cart.count');
    


    /*
    |--------------------------------------------------------------------------
    | 📦 ORDERS (BUYER)
    |--------------------------------------------------------------------------
    */
    Route::get('/orders',        [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}',[OrderController::class, 'show'])->name('orders.show');


    /*
    |--------------------------------------------------------------------------
    | ⚡ CHECKOUT
    |--------------------------------------------------------------------------
    */
    Route::post('/checkout/quick/{product}', [CheckoutController::class, 'quick'])
        ->name('checkout.quick');

    Route::post('/checkout/confirm', [CheckoutController::class, 'prepare'])
        ->name('checkout.prepare');

    Route::get('/checkout/confirm',  [CheckoutController::class, 'confirm'])
        ->name('checkout.confirm');

    Route::post('/checkout/create',  [CheckoutController::class, 'create'])
        ->name('checkout.create');


    /*
    |--------------------------------------------------------------------------
    | 🔄 ORDER STATUS
    |--------------------------------------------------------------------------
    */
    Route::post('/orders/{order}/confirm-delivery',
        [OrderStatusController::class, 'confirmDelivery']
    )->name('orders.confirmDelivery');

    Route::post('/seller/orders/{order}/status',
        [OrderStatusController::class, 'sellerUpdate']
    )->name('seller.orders.updateStatus');

    Route::post('/admin/orders/{order}/status',
        [OrderStatusController::class, 'adminUpdate']
    )->name('admin.orders.updateStatus');


    /*
    |--------------------------------------------------------------------------
    | 📬 ADDRESSES
    |--------------------------------------------------------------------------
    */
    Route::get('/addresses',               [UserAddressController::class, 'index'])->name('addresses.index');
    Route::post('/addresses',              [UserAddressController::class, 'store'])->name('addresses.store');
    Route::put('/addresses/{address}',     [UserAddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{address}',  [UserAddressController::class, 'destroy'])->name('addresses.destroy');

    Route::post('/addresses/{address}/default',
        [UserAddressController::class, 'makeDefault']
    )->name('addresses.default');


    /*
    |--------------------------------------------------------------------------
    | 📝 REVIEWS
    |--------------------------------------------------------------------------
    */
    Route::post('/review/{product}', [ReviewController::class, 'store'])->name('review.store');


    /*
    |--------------------------------------------------------------------------
    | 🏪 SELLER PANEL
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:seller')
        ->prefix('seller')
        ->name('seller.')
        ->group(function () {

        // Категории
        Route::get('/categories/{parent}/children', [SellerCategoryController::class, 'children'])
            ->name('categories.children');

        Route::get('/categories/chain/{id}', [SellerCategoryController::class, 'chain'])
            ->name('categories.chain');

        Route::get('/categories/{category}/attributes', [SellerProducts::class, 'getCategoryAttributes'])
            ->name('categories.attributes');

        // Справка
        Route::get('/help/{slug}',  [SellerHelpController::class, 'show'])->name('help');
        Route::get('/help',         [SellerHelpController::class, 'index'])->name('help.index');

        // Кабинет
        Route::get('/cabinet', [CabinetController::class, 'index'])->name('cabinet');

        // Товары
        Route::resource('products', SellerProducts::class)->except(['show']);
        Route::delete('/products/{product}/gallery',
            [SellerProducts::class, 'deleteGalleryImage']
        )->name('products.gallery.delete');

        // Заказы продавца
        Route::get('/orders', [SellerOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [SellerOrderController::class, 'show'])->name('orders.show');

        // Финансы
        Route::view('/finance', 'seller.finance.index')->name('finance.index');

        // Аналитика
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/day/{date}', [AnalyticsController::class, 'dayStats'])->name('analytics.day');
        Route::get('/analytics/products-on/{date}', [AnalyticsController::class, 'productsOn']);
    });

}); // END AUTH GROUP



/*
|--------------------------------------------------------------------------
| 🧾 PUBLIC SELLER PAGE
|--------------------------------------------------------------------------
*/
Route::get('/seller/{user}', [SellerController::class, 'show'])->name('seller.show');





/*
|--------------------------------------------------------------------------
| 🌐 GOOGLE LOGIN
|--------------------------------------------------------------------------
*/
Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])
    ->name('auth.google.redirect');

Route::get('/auth/google/callback', [GoogleController::class, 'callback'])
    ->name('auth.google.callback');



/*
|--------------------------------------------------------------------------
| 🛠 ADMIN PANEL
|--------------------------------------------------------------------------
*/



Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', AdminMiddleware::class])
    ->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('colors', ColorController::class)->except(['show']);
    // Очистка старых товаров из корзины
    Route::post('/products/purge-old', function () {
    Artisan::call('products:purge-old');
    return back()->with('success', 'Удалены товары, находившиеся в корзине более 90 дней.');
    })->name('products.purge-old');

    

    Route::resource('users', AdminUserController::class);

    Route::resource('categories', AdminCategoryController::class)->except(['show']);
    Route::get('/categories/{id}/children', [AdminCategoryController::class, 'children'])->name('categories.children');
    Route::get('/categories/root',          [AdminCategoryController::class, 'root'])->name('categories.root');
    Route::get('/categories/{id}/parent',   [AdminCategoryController::class, 'parent'])->name('categories.parent');

    Route::get('/categories/{category}/attributes',  [CategoryAttributeController::class, 'index'])
        ->name('categories.attributes');

        Route::put('/categories/{category}/attributes/{attribute}', 
    [CategoryAttributeController::class, 'update'])
->name('categories.attributes.update');
        
    Route::post('/categories/{category}/attributes', [CategoryAttributeController::class, 'store'])
        ->name('categories.attributes.store');

    Route::delete('/categories/{category}/attributes/{attribute}',
        [CategoryAttributeController::class, 'destroy']
    )->name('categories.attributes.destroy');

    Route::get('/products/search', [AdminProductController::class, 'search'])
        ->name('products.search');

    Route::resource('products', AdminProductController::class);

    Route::delete('/products/{product}/gallery',
        [AdminProductController::class, 'deleteGalleryImage']
    )->name('products.gallery.delete');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');

    Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');

    Route::resource('banners', BannerController::class)->except(['show']);

    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{review}/reject',  [AdminReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{review}',       [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::get('/reviews/{review}',          [AdminReviewController::class, 'show'])->name('reviews.show');



});

/*
|--------------------------------------------------------------------------
| 🔐 AUTH ROUTES
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
