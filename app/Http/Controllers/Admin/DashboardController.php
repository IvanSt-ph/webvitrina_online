<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\Category;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
public function index()
{
    $data = Cache::remember('admin.dashboard.summary', 60, function () {

        // 📊 Основная статистика
        $stats = [
            'products'   => Product::count(),
            'categories' => Category::count(),
            'orders'     => Order::count(),
            'users'      => User::count(),
        ];

        $statDeltas = [
            'products' => $this->weeklyDelta(Product::query()),
            'categories' => $this->weeklyDelta(Category::query()),
            'orders' => $this->weeklyDelta(Order::query()),
            'users' => $this->weeklyDelta(User::query()),
        ];

        // 📈 Активность заказов (14 дней)
        $ordersActivityRaw = Order::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->whereDate('created_at', '>=', now()->subDays(13)->toDateString())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $ordersActivity = $this->fillDailySeries($ordersActivityRaw, 13);

        // 🥧 Распределение по категориям (ТОП-5)
        $categoryData = Category::select('name')
            ->withCount('products')
            ->orderByDesc('products_count')
            ->take(5)
            ->get();

        // 👥 Рост пользователей (7 дней)
        $userGrowthRaw = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->whereDate('created_at', '>=', now()->subDays(6)->toDateString())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->toArray();
        
        $userGrowth = $this->fillDailySeries($userGrowthRaw, 6);

        // 🧺 Последние заказы
        $orders = Order::with('user')->latest()->take(8)->get();

        // 👤 Последние пользователи
        $newUsers = User::latest()->take(8)->get();

        // 🔝 ТОП-5 популярных товаров, категорий и продавцов
        $topProducts = Product::withCount([
                'orders' => fn ($query) => $query->where('orders.status', '!=', Order::STATUS_CANCELED),
            ])
            ->orderByDesc('orders_count')
            ->take(5)
            ->get();

        $topCategories = Category::withCount('products')
            ->orderByDesc('products_count')
            ->take(5)
            ->get();

        $topSellers = User::where('role', 'seller')
            ->withCount('products')
            ->orderByDesc('products_count')
            ->take(5)
            ->get();

        // ✅ Возвращаем одним массивом
        return compact(
            'stats',
            'statDeltas',
            'ordersActivity',
            'categoryData',
            'userGrowth',
            'orders',
            'newUsers',
            'topProducts',
            'topCategories',
            'topSellers'
        );
    });

    // 🧾 Последние товары не кешируем вместе с summary, чтобы пагинация не застревала на первой странице.
    $data['products'] = Product::with(['category', 'seller'])
        ->latest()
        ->paginate(10);

    // ✅ Передаем всё в Blade
    return view('admin.dashboard', $data);
}

private function fillDailySeries(array $raw, int $daysBack): array
{
    $start = now()->subDays($daysBack)->startOfDay();
    $end = now()->startOfDay();
    $series = [];

    foreach (CarbonPeriod::create($start, $end) as $date) {
        $key = $date->toDateString();
        $series[$key] = (int) ($raw[$key] ?? 0);
    }

    return $series;
}

private function weeklyDelta($query): array
{
    $currentStart = now()->subDays(6)->startOfDay();
    $previousStart = now()->subDays(13)->startOfDay();

    $current = (clone $query)
        ->where('created_at', '>=', $currentStart)
        ->count();

    $previous = (clone $query)
        ->whereBetween('created_at', [$previousStart, $currentStart->copy()->subSecond()])
        ->count();

    if ($previous === 0) {
        $percent = $current > 0 ? 100 : 0;
    } else {
        $percent = round((($current - $previous) / $previous) * 100);
    }

    return [
        'percent' => $percent,
        'positive' => $percent >= 0,
        'label' => ($percent > 0 ? '+' : '') . $percent . '%',
    ];
}


}
