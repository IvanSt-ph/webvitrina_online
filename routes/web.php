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
    SellerController
};
use App\Http\Controllers\Seller\ProductManageController as SellerProducts;
use App\Models\Category;
use App\Models\Country;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;

/*
|--------------------------------------------------------------------------
| 🌍 Публичные маршруты
|--------------------------------------------------------------------------
*/

// Главная
Route::get('/', [ProductController::class, 'index'])->name('home');

// Товар
Route::get('/p/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/p/{key}', [ProductController::class, 'show'])->name('product.short');

// Категории
Route::get('/category', [CategoryController::class, 'index'])->name('category.index');
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show');

// 🌎 Страны → Города
Route::get('/countries/{country}/cities', fn (Country $country) =>
    $country->cities()->select('id','name')->orderBy('name')->get()
)->name('countries.cities');

// 🔹 Публичный JSON для продавцов (переопределяет админский)
Route::get('/categories/{id}/children', function ($id) {
    return \App\Models\Category::where('parent_id', $id)
        ->orderBy('name')
        ->get(['id', 'name']);
})->name('categories.children.public');


// 🔹 Публичный API для каскадных категорий (для продавцов и редактирования товаров)
Route::get('/categories/{id}/children', [AdminCategoryController::class, 'children'])
    ->name('categories.children.public');

Route::get('/categories/{id}/parent', function ($id) {
    $category = \App\Models\Category::find($id);
    if (!$category) {
        return response()->json(null, 404);
    }
    return response()->json([
        'id' => $category->id,
        'parent_id' => $category->parent_id,
    ]);
})->name('categories.parent.public');

// 🔹 Страница "В разроботке" (Заглушка)
Route::view('/coming-soon', 'errors.coming-soon')->name('coming.soon');




// Кабинет пользователя (общая точка входа)
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

    // ⭐ Избранное
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{product}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // 🛒 Корзина
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{item}', [CartController::class, 'remove'])->name('cart.remove');

    // 📦 Заказы
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/checkout', [OrderController::class, 'checkout'])->name('checkout');

    // 📝 Отзывы
    Route::post('/review/{product}', [ReviewController::class, 'store'])->name('review.store');

    /*
    |--------------------------------------------------------------------------
    | 🏪 Панель продавца
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:seller')->prefix('seller')->name('seller.')->group(function () {
        // 📦 Товары
        Route::get('/products', [SellerProducts::class, 'index'])->name('products.index');
        Route::get('/products/create', [SellerProducts::class, 'create'])->name('products.create');
        Route::post('/products', [SellerProducts::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [SellerProducts::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [SellerProducts::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [SellerProducts::class, 'destroy'])->name('products.destroy');



        
            // 🧾 Заглушки для будущих разделов
        Route::view('/orders', 'seller.orders.index')->name('orders.index');
        Route::view('/finance', 'seller.finance.index')->name('finance.index');
        Route::view('/analytics', 'seller.analytics.index')->name('analytics.index');



        

        // 🖼️ Удаление фото из галереи (для продавца)
        Route::delete('/products/{product}/gallery', [SellerProducts::class, 'deleteGalleryImage'])
            ->name('products.gallery.delete');
    });
    
});

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

Route::prefix('admin')->name('admin.')->middleware(['auth', AdminMiddleware::class])->group(function () {

    // 🏠 Главная
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // 👤 Пользователи
    Route::resource('users', AdminUserController::class);

    // 📂 Категории
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    // 📂 AJAX для каскадных категорий
    Route::get('/categories/{id}/children', [AdminCategoryController::class, 'children'])->name('categories.children');
    Route::get('/categories/root', [AdminCategoryController::class, 'root'])->name('categories.root');
    Route::get('/categories/{id}/parent', [AdminCategoryController::class, 'parent'])->name('categories.parent');


    // Сначала поиск — чтобы не конфликтовал с {product}
    Route::get('/products/search', [AdminProductController::class, 'search'])->name('products.search');
    Route::resource('products', AdminProductController::class);




    // 🖼️ Удаление фото из галереи (админ)
    Route::delete('/products/{product}/gallery', [AdminProductController::class, 'deleteGalleryImage'])
        ->name('products.gallery.delete');

    // 📦 Заказы
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');

    // ⚙ Настройки профиля
    Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');

    // 🖼️ Баннеры
    Route::resource('banners', BannerController::class)->except(['show']);

    // 📝 Модерация отзывов
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{review}/reject', [AdminReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::get('/reviews/{review}', [AdminReviewController::class, 'show'])->name('reviews.show');
});
