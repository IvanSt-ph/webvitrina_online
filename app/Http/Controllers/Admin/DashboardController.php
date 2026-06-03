<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\Category;
use App\Models\Banner;
use App\Models\Conversation;
use App\Models\Review;
use App\Models\ProductReport;
use App\Models\OrderDispute;
use App\Models\SellerPlanRequest;
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

        $attentionOrdersQuery = fn () => Order::where(function ($query) {
            $query->where(function ($cancel) {
                $cancel->whereNotNull('cancellation_requested_at')
                    ->whereNotIn('status', [Order::STATUS_CANCELED, Order::STATUS_COMPLETED]);
            })->orWhere(function ($stuck) {
                $stuck->where(function ($pending) {
                    $pending->where('status', Order::STATUS_PENDING)
                        ->where('created_at', '<=', now()->subDay());
                })->orWhere(function ($processing) {
                    $processing->where('status', Order::STATUS_PROCESSING)
                        ->where(function ($dates) {
                            $dates->where('accepted_at', '<=', now()->subDays(2))
                                ->orWhere(function ($fallback) {
                                    $fallback->whereNull('accepted_at')
                                        ->where('created_at', '<=', now()->subDays(2));
                                });
                        });
                });
            });
        });

        $workQueue = [
            'orders' => $attentionOrdersQuery()->count(),
            'chats' => Conversation::query()
                ->whereNull('admin_deleted_at')
                ->whereHas('messages', fn ($query) => $query
                    ->where('sender_id', '!=', auth()->id())
                    ->whereNull('admin_read_at'))
                ->count(),
            'reviews' => Review::where('status', Review::STATUS_PENDING)->count(),
            'productReports' => ProductReport::where('status', ProductReport::STATUS_OPEN)->count(),
            'disputes' => OrderDispute::where('status', OrderDispute::STATUS_OPEN)->count(),
            'plans' => SellerPlanRequest::where('status', SellerPlanRequest::STATUS_PENDING)->count(),
            'banners' => Banner::whereNull('image_mobile')->count(),
        ];

        $attentionOrders = $attentionOrdersQuery()
            ->with(['user', 'seller'])
            ->oldest()
            ->limit(5)
            ->get();

        $pendingPlans = SellerPlanRequest::with('user')
            ->where('status', SellerPlanRequest::STATUS_PENDING)
            ->latest()
            ->limit(4)
            ->get();

        $todayQueue = [
            'productReports' => ProductReport::with(['product.seller', 'user'])
                ->where('status', ProductReport::STATUS_OPEN)
                ->whereDate('created_at', now()->toDateString())
                ->latest()
                ->limit(4)
                ->get(),
            'disputes' => OrderDispute::with(['order', 'user', 'seller'])
                ->where('status', OrderDispute::STATUS_OPEN)
                ->whereDate('created_at', now()->toDateString())
                ->latest()
                ->limit(4)
                ->get(),
            'reviews' => Review::with(['product', 'user'])
                ->where('status', Review::STATUS_PENDING)
                ->whereDate('created_at', now()->toDateString())
                ->latest()
                ->limit(4)
                ->get(),
            'plans' => SellerPlanRequest::with('user')
                ->where('status', SellerPlanRequest::STATUS_PENDING)
                ->whereDate('created_at', now()->toDateString())
                ->latest()
                ->limit(4)
                ->get(),
            'orders' => $attentionOrdersQuery()
                ->with(['user', 'seller'])
                ->whereDate('created_at', now()->toDateString())
                ->latest()
                ->limit(4)
                ->get(),
        ];

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
            'topSellers',
            'workQueue',
            'attentionOrders',
            'pendingPlans',
            'todayQueue'
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
