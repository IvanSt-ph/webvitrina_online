<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
public function index()
{
    $data = Cache::remember('admin.dashboard', 60, function () {

        // 📊 Основная статистика
        $stats = [
            'products'   => Product::count(),
            'categories' => Category::count(),
            'orders'     => Order::count(),
            'users'      => User::count(),
        ];

        // 📈 Активность заказов (14 дней)
        $ordersActivity = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // 🥧 Распределение по категориям (ТОП-5)
        $categoryData = Category::select('name')
            ->withCount('products')
            ->orderByDesc('products_count')
            ->take(5)
            ->get();

        // 👥 Рост пользователей (7 дней)
        $userGrowth = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // 🧾 Последние товары
        $products = Product::with(['category', 'seller'])
            ->latest()
            ->paginate(10);

        // 🧺 Последние заказы
        $orders = Order::with('user')->latest()->take(8)->get();

        // 👤 Последние пользователи
        $newUsers = User::latest()->take(8)->get();

        // 🔝 ТОП-5 популярных товаров, категорий и продавцов
        $topProducts = Product::withCount('orders')
            ->orderByDesc('orders_count')
            ->take(5)
            ->get();

        $topCategories = Category::withCount('products')
            ->orderByDesc('products_count')
            ->take(5)
            ->get();

        $topSellers = User::withCount('products')
            ->orderByDesc('products_count')
            ->take(5)
            ->get();

        // ✅ Возвращаем одним массивом
        return compact(
            'stats',
            'ordersActivity',
            'categoryData',
            'userGrowth',
            'products',
            'orders',
            'newUsers',
            'topProducts',
            'topCategories',
            'topSellers'
        );
    });

    // ✅ Передаем всё в Blade
    return view('admin.dashboard', $data);
}


}
