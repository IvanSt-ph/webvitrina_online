<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Models\Country;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\PhoneVerificationController;
use App\Http\Controllers\ShopPhoneVerificationController;


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
    OrderStatusController,
    ChatController,
    PublicUserController,
    ShopFollowController,
    UserSettingsController,
    UserNotificationController,
    OrderDisputeController,
    ProductReportController
};

use App\Http\Controllers\Seller\ProductManageController as SellerProducts;
use App\Http\Controllers\Seller\{
    CabinetController,
    AnalyticsController,
    FinanceController,
    PlanController as SellerPlanController,
    HelpController as SellerHelpController,
    CategoryController as SellerCategoryController,
    FollowerController as SellerFollowerController,
    OrderController as SellerOrderController
};

use App\Http\Controllers\Admin\{
    DashboardController,
    UserController as AdminUserController,
    ProductController as AdminProductController,
    OrderController as AdminOrderController,
    AdminProfileController,
    ActivityLogController,
    BannerController,
    AdCampaignController,
    ReviewController as AdminReviewController,
    ProductReportController as AdminProductReportController,
    OrderDisputeController as AdminOrderDisputeController,
    ChatController as AdminChatController,
    CategoryAttributeController,
    CategoryController as AdminCategoryController,
    SellerPlanRequestController
};

use App\Http\Controllers\CurrencyProxyController;
use App\Http\Controllers\Auth\GoogleController;



/*
|--------------------------------------------------------------------------
| 🌍 PUBLIC ROUTES
|--------------------------------------------------------------------------
*/


// Временный редирект для тестов
Route::get('/dashboard', function() {
    if (!auth()->check()) {
        return redirect('/login');
    }

    $user = auth()->user();

    return match($user->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'seller' => redirect()->route('seller.cabinet'),
        default => redirect()->route('cabinet'),
    };
})->name('dashboard')->middleware('auth');





Route::middleware(['auth', 'role:seller'])->group(function () {
    Route::post('/shop/phone/send', [ShopPhoneVerificationController::class, 'send'])->name('shop.phone.send');
    Route::post('/shop/phone/verify', [ShopPhoneVerificationController::class, 'verify'])->name('shop.phone.verify');
});



Route::post('/phone/send', [PhoneVerificationController::class, 'send'])
    ->name('phone.send')
    ->middleware(['auth', 'throttle:3,10']);

Route::post('/phone/verify', [PhoneVerificationController::class, 'verify'])
    ->name('phone.verify')
    ->middleware(['auth', 'throttle:3,1']);


// 💱 Валюты
Route::get('/internal/currency/agroprombank', [
    CurrencyProxyController::class, 'agroprombank'
]);

// 🏠 Главная
Route::get('/', [ProductController::class, 'index'])->name('home');
Route::get('/search/suggest', [ProductController::class, 'suggest'])->name('search.suggest');
Route::get('/search', [ProductController::class, 'index'])->name('search');
Route::view('/about', 'legal.about')->name('about');
Route::view('/contacts', 'legal.contacts')->name('contacts');

Route::get('/robots.txt', function () {
    return response("User-agent: *\nAllow: /\nSitemap: " . url('/sitemap.xml') . "\n", 200)
        ->header('Content-Type', 'text/plain');
})->name('robots');

Route::get('/sitemap.xml', function () {
    $products = \App\Models\Product::query()->active()->latest('updated_at')->limit(1000)->get(['slug', 'updated_at']);
    $categories = \App\Models\Category::query()->where('is_active', true)->latest('updated_at')->limit(1000)->get(['slug', 'updated_at']);
    $shops = \App\Models\Shop::query()->whereNotNull('slug')->latest('updated_at')->limit(1000)->get(['slug', 'updated_at']);

    return response()
        ->view('sitemap', compact('products', 'categories', 'shops'))
        ->header('Content-Type', 'application/xml');
})->name('sitemap');


// 🛍 Товар
// 🛍 Товар - универсальный (работает и с ID, и со slug)
Route::get('/p/{identifier}', [ProductController::class, 'show'])
    ->name('product.show');


    
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

Route::view('/voprosy-i-otvety', 'legal.faq')->name('faq');
Route::view('/rules', 'legal.rules')->name('legal.rules');
Route::view('/privacy', 'legal.privacy')->name('legal.privacy');
Route::view('/delivery-returns', 'legal.delivery-returns')->name('legal.delivery-returns');
Route::view('/seller-terms', 'legal.seller-terms')->name('legal.seller-terms');

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
Route::get('/profile',  [ProfileController::class, 'redirectToRoleProfile'])->name('profile.redirect');
Route::get('/profile/edit',  [ProfileController::class, 'edit'])
    ->name('profile.edit')
    ->middleware('role:seller'); // 👈 ТОЛЬКО ДЛЯ ПРОДАВЦОВ
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Shop info (продавец)
    Route::patch('/profile/shop', [ProfileController::class, 'updateShop'])
        ->name('profile.shop.update')
        ->middleware('role:seller');



/*
|--------------------------------------------------------------------------
| 👤 BUYER AREA (ТОЛЬКО ДЛЯ ПОКУПАТЕЛЕЙ)
|--------------------------------------------------------------------------
*/

Route::middleware('role:buyer')->group(function () {
    // Основная инфа
    Route::get('/buyer/profile', fn() => view('buyer.profile.general'))
        ->name('buyer.profile');

    // Безопасность
    Route::get('/buyer/profile/security', fn() => view('buyer.profile.security'))
        ->name('buyer.profile.security');

    // Обновление именно покупательского профиля
    Route::patch('/buyer/profile/update', [ProfileController::class, 'update'])
        ->name('buyer.profile.update');

    Route::get('/my-subscriptions', [ProfileController::class, 'subscriptions'])
        ->name('subscriptions.index');
});

    // Прочие buyer-странички
    Route::view('/my-questions', 'buyer.questions.index')->name('questions.index');
    Route::get('/my-chats', [ChatController::class, 'index'])->name('chats.index');
    Route::post('/seller/{shop:slug}/chat', [ChatController::class, 'start'])
        ->middleware('throttle:20,1')
        ->name('chats.start');
    Route::post('/shops/{shop:slug}/follow', [ShopFollowController::class, 'toggle'])
        ->middleware('throttle:30,1')
        ->name('shops.follow');
    Route::post('/p/{product:slug}/chat', [ChatController::class, 'startForProduct'])
        ->middleware('throttle:20,1')
        ->name('chats.product.start');
    Route::post('/orders/{order}/chat/{product}', [ChatController::class, 'startForOrderProduct'])
        ->middleware('throttle:20,1')
        ->name('orders.chat.product');
    Route::get('/my-chats/{conversation}', [ChatController::class, 'show'])->name('chats.show');
    Route::post('/my-chats/{conversation}/pin', [ChatController::class, 'togglePin'])
        ->middleware('throttle:30,1')
        ->name('chats.pin');
    Route::delete('/my-chats/{conversation}', [ChatController::class, 'destroy'])->name('chats.destroy');
    Route::get('/my-chats/{conversation}/messages/older', [ChatController::class, 'olderMessages'])
        ->middleware('throttle:60,1')
        ->name('chats.messages.older');
    Route::get('/my-chats/{conversation}/messages/newer', [ChatController::class, 'newerMessages'])
        ->middleware('throttle:120,1')
        ->name('chats.messages.newer');
    Route::get('/my-chats/{conversation}/messages/{message}/image', [ChatController::class, 'image'])
        ->middleware('throttle:120,1')
        ->name('chats.messages.image');
    Route::post('/my-chats/{conversation}/messages', [ChatController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('chats.messages.store');
    Route::post('/my-chats/{conversation}/support', [ChatController::class, 'openSupportFromConversation'])
        ->middleware('throttle:10,1')
        ->name('chats.support.dispute');
    Route::get('/notifications', [UserNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [UserNotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::post('/notifications/{notification}/read', [UserNotificationController::class, 'markRead'])->name('notifications.read');
    Route::view('/notifications/settings', 'buyer.notifications.settings')->name('notifications.settings');
    Route::patch('/notifications/settings', [UserSettingsController::class, 'updateNotifications'])->name('notifications.settings.update');
    Route::view('/settings/language', 'buyer.settings.language')->name('settings.language');
    Route::patch('/settings/language', [UserSettingsController::class, 'updateLanguage'])->name('settings.language.update');
    Route::view('/settings/currency', 'buyer.settings.currency')->name('settings.currency');
    Route::patch('/settings/currency', [UserSettingsController::class, 'updateCurrency'])->name('settings.currency.update');
    Route::get('/support', [ChatController::class, 'support'])->name('support');
    Route::post('/support/start', [ChatController::class, 'startSupport'])
        ->middleware('throttle:10,1')
        ->name('support.start');
    Route::post('/orders/{order}/support', [ChatController::class, 'startSupportForOrder'])
        ->middleware('throttle:10,1')
        ->name('orders.support');
    Route::delete('/favorites/{favorite}', [FavoriteController::class, 'remove'])
        ->name('favorites.remove');
    Route::view('/help', 'buyer.help.index')->name('help');

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

    Route::post('/cart/add-favorites', [CartController::class, 'addFavorites'])
    ->name('cart.addFavorites')
    ->middleware('throttle:10,1');

    Route::post('/cart/add/{product}', [CartController::class, 'add'])
    ->name('cart.add')
    ->middleware('throttle:20,1');

    Route::patch('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{item}', [CartController::class, 'remove'])->name('cart.remove');

    Route::get('/cart-count', fn() => ['count' => \App\Models\CartItem::where('user_id', auth()->id())->sum('qty')])
    ->middleware('auth')
    ->name('cart.count');

    Route::get('/cart-quantities', function () {
        $response = response()->json([
            'quantities' => \App\Models\CartItem::where('user_id', auth()->id())
                ->pluck('qty', 'product_id')
                ->map(fn ($qty) => (int) $qty),
        ]);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

        return $response;
    })
    ->middleware('auth')
    ->name('cart.quantities');
    


    /*
    |--------------------------------------------------------------------------
    | 📦 ORDERS (BUYER)
    |--------------------------------------------------------------------------
    */
    Route::get('/orders',        [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}',[OrderController::class, 'show'])->name('orders.show');
    Route::get('/my-disputes', [OrderDisputeController::class, 'index'])->name('disputes.index');


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
    Route::post('/orders/{order}/request-cancellation',
        [OrderStatusController::class, 'requestCancellation']
    )->middleware('throttle:5,1')->name('orders.requestCancellation');
    Route::post('/orders/{order}/disputes', [OrderDisputeController::class, 'store'])
        ->middleware('throttle:5,10')
        ->name('orders.disputes.store');

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
    Route::post('/products/{product}/report', [ProductReportController::class, 'store'])
        ->middleware('throttle:5,10')
        ->name('products.report');


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
        Route::get('/followers', [SellerFollowerController::class, 'index'])->name('followers.index');
        Route::get('/plans', [SellerPlanController::class, 'index'])->name('plans.index');
        Route::post('/plans/request', [SellerPlanController::class, 'requestUpgrade'])
            ->middleware('throttle:5,10')
            ->name('plans.request');

        // Товары
        Route::resource('products', SellerProducts::class)->except(['show']);
        Route::delete('/products/{product}/gallery',
            [SellerProducts::class, 'deleteGalleryImage']
        )->name('products.gallery.delete');

        // Заказы продавца
        Route::get('/orders', [SellerOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [SellerOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/chat', [SellerOrderController::class, 'startBuyerConversation'])
            ->middleware('throttle:20,1')
            ->name('orders.chat.buyer');
        Route::post('/orders/{order}/status', [OrderStatusController::class, 'sellerUpdate'])
            ->name('orders.updateStatus');

        // Финансы
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');

        // Аналитика
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/day/{date}', [AnalyticsController::class, 'dayStats'])->name('analytics.day');
    });

}); // END AUTH GROUP



/*
|--------------------------------------------------------------------------
| 🧾 PUBLIC SELLER PAGE
|--------------------------------------------------------------------------
*/
Route::get('/seller/{identifier}', [SellerController::class, 'show'])->name('seller.show');
Route::get('/u/{user}', [PublicUserController::class, 'show'])->name('users.public.show');


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

    Route::resource('colors', ColorController::class)->except(['show', 'create', 'edit']);
    // Очистка старых товаров из корзины
    Route::post('/products/purge-old', function () {
    Artisan::call('products:purge-old');
    return back()->with('success', 'Удалены товары, находившиеся в корзине более 90 дней.');
    })->name('products.purge-old');

    

    Route::resource('users', AdminUserController::class);
    Route::get('/seller-plan-requests', [SellerPlanRequestController::class, 'index'])->name('seller-plan-requests.index');
    Route::post('/seller-plan-requests/{planRequest}/approve', [SellerPlanRequestController::class, 'approve'])->name('seller-plan-requests.approve');
    Route::post('/seller-plan-requests/{planRequest}/reject', [SellerPlanRequestController::class, 'reject'])->name('seller-plan-requests.reject');
    Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');

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

    Route::resource('products', AdminProductController::class)->except(['show']);

    Route::delete('/products/{product}/gallery',
        [AdminProductController::class, 'deleteGalleryImage']
    )->name('products.gallery.delete');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');

    Route::post('/orders/{order}/status',
        [OrderStatusController::class, 'adminUpdate']
    )->name('orders.updateStatus');

    Route::get('/disputes', [AdminOrderDisputeController::class, 'index'])->name('disputes.index');
    Route::post('/disputes/{dispute}/resolve', [AdminOrderDisputeController::class, 'resolve'])->name('disputes.resolve');
    Route::post('/disputes/{dispute}/close', [AdminOrderDisputeController::class, 'close'])->name('disputes.close');

    Route::get('/chats', [AdminChatController::class, 'index'])->name('chats.index');
    Route::get('/chats/{conversation}', [AdminChatController::class, 'show'])->name('chats.show');
    Route::delete('/chats/{conversation}', [AdminChatController::class, 'destroy'])->name('chats.destroy');
    Route::post('/chats/support/{user}', [AdminChatController::class, 'startSupport'])->name('chats.support.start');
    Route::post('/chats/{conversation}/messages', [AdminChatController::class, 'store'])->name('chats.messages.store');
    Route::post('/chats/{conversation}/system', [AdminChatController::class, 'system'])->name('chats.system');
    Route::post('/chats/{conversation}/note', [AdminChatController::class, 'note'])->name('chats.note');
    Route::post('/chats/{conversation}/lock', [AdminChatController::class, 'lock'])->name('chats.lock');
    Route::post('/chats/{conversation}/unlock', [AdminChatController::class, 'unlock'])->name('chats.unlock');
    Route::get('/chats/{conversation}/messages/{message}/image', [AdminChatController::class, 'image'])
        ->scopeBindings()
        ->name('chats.messages.image');

    Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
    Route::view('/production-checklist', 'admin.production-checklist')->name('production-checklist');

    Route::resource('banners', BannerController::class)->except(['show']);
    Route::get('/ads/search/products', [AdCampaignController::class, 'searchProducts'])->name('ads.search.products');
    Route::get('/ads/search/shops', [AdCampaignController::class, 'searchShops'])->name('ads.search.shops');
    Route::resource('ads', AdCampaignController::class)->except(['show'])->parameters(['ads' => 'ad']);

    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/bulk', [AdminReviewController::class, 'bulk'])->name('reviews.bulk');
    Route::post('/reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{review}/reject',  [AdminReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{review}',       [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::get('/reviews/{review}',          [AdminReviewController::class, 'show'])->name('reviews.show');

    Route::get('/product-reports', [AdminProductReportController::class, 'index'])->name('product-reports.index');
    Route::post('/product-reports/{report}/resolve', [AdminProductReportController::class, 'resolve'])->name('product-reports.resolve');
    Route::post('/product-reports/{report}/hide-product', [AdminProductReportController::class, 'hideProduct'])->name('product-reports.hide-product');
    Route::post('/product-reports/{report}/restore-product', [AdminProductReportController::class, 'restoreProduct'])->name('product-reports.restore-product');
    Route::post('/product-reports/{report}/dismiss', [AdminProductReportController::class, 'dismiss'])->name('product-reports.dismiss');



});

/*
|--------------------------------------------------------------------------
| 🔐 AUTH ROUTES
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
