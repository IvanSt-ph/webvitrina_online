<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProductController,
    FavoriteController,
    CartController,
    OrderController,
    ReviewController,
    ProfileController,
    CategoryController
};
use App\Http\Controllers\Seller\ProductManageController as SellerProducts;
use App\Http\Controllers\SellerController;
use App\Models\Category;
use App\Models\Country;

/*
|--------------------------------------------------------------------------
| Публичные маршруты
|--------------------------------------------------------------------------
*/

// Главная
Route::get('/', [ProductController::class, 'index'])->name('home');

// Товар
Route::get('/p/{product:slug}', [ProductController::class, 'show'])->name('product.show');

// Категория
Route::get('/category/{slug}', [CategoryController::class, 'show'])->name('category.show');

// Кабинет пользователя
Route::get('/cabinet', [ProfileController::class, 'cabinet'])->name('cabinet');

/*
|--------------------------------------------------------------------------
| Авторизованные маршруты
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

    // 🏪 Панель продавца
    Route::middleware('role:seller')->prefix('seller')->name('seller.')->group(function () {
        Route::get('/products', [SellerProducts::class, 'index'])->name('products.index');
        Route::get('/products/create', [SellerProducts::class, 'create'])->name('products.create');
        Route::post('/products', [SellerProducts::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [SellerProducts::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [SellerProducts::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [SellerProducts::class, 'destroy'])->name('products.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Вспомогательные API
|--------------------------------------------------------------------------
*/
Route::get('/categories/{parent}/children', fn(Category $parent) =>
    $parent->children()->select('id','name')->orderBy('name')->get()
)->name('categories.children');

Route::get('/countries/{country}/cities', fn(Country $country) =>
    $country->cities()->select('id','name')->orderBy('name')->get()
)->name('countries.cities');

/*
|--------------------------------------------------------------------------
| Магазин продавца
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
| Админка
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\AdminProfileController;

use App\Http\Middleware\AdminMiddleware;

Route::prefix('admin')->name('admin.')->middleware(['auth', AdminMiddleware::class])->group(function () {


    // 🏠 Главная
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // 👤 Пользователи
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');

    // 📂 Категории
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    // 📂 AJAX для каскадных категорий
    Route::get('/categories/{id}/children', [AdminCategoryController::class, 'children'])->name('categories.children');
    Route::get('/categories/{id}/parent', [AdminCategoryController::class, 'parent'])->name('categories.parent');

    // 🛒 Товары
    Route::resource('products', AdminProductController::class);

    // 📦 Заказы
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');

    // ⚙ Настройки профиля
    Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
});
