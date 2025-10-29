<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\ProductStat;
use App\Models\Favorite;
use App\Models\CartItem;
use Carbon\Carbon;

class AnalyticsController extends Controller
{

public function dayStats($date)
{
    $user = Auth::user();

    // создаём уникальный ключ для кэша
    $cacheKey = "seller_analytics_day_{$user->id}_{$date}";

    // если данные уже есть в кэше — возвращаем сразу
    if (cache()->has($cacheKey)) {
        return response()->json(cache($cacheKey));
    }

    // товары продавца
    $productIds = Product::where('user_id', $user->id)->pluck('id');

    // статистика за указанный день
    $stats = ProductStat::whereIn('product_id', $productIds)
        ->whereDate('date', $date)
        ->with('product:id,title')
        ->get(['product_id', 'views', 'favorites', 'carts']);

    // формируем результат
    $data = $stats->map(fn($s) => [
        'id'        => (int) $s->product_id,
        'title'     => $s->product->title ?? 'Без названия',
        'views'     => (int) $s->views,
        'favorites' => (int) $s->favorites,
        'carts'     => (int) $s->carts,
    ])->values();

    // кладём в кэш на 5 минут (можно поменять)
    cache()->put($cacheKey, $data, now()->addMinutes(5));

    return response()->json($data);
}


public function productsOn($date)
{
    $user = auth()->user();

    $products = Product::where('user_id', $user->id)
        ->whereDate('created_at', $date)

        ->select('id', 'title', 'image', 'created_at')

        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($products);
}



    public function index()
    {
        $user = Auth::user();

        // === 1️⃣ Получаем все товары продавца ===
        $products = Product::where('user_id', $user->id)->get();
        $productIds = $products->pluck('id');

        // Если нет товаров — возвращаем пустые данные
        if ($productIds->isEmpty()) {
            return view('seller.analytics.index', [
                'summary'     => ['views' => 0, 'favorites' => 0, 'cart_adds' => 0, 'total' => 0],
                'stats'       => collect([]),
                'topProducts' => collect([]),
                'products'    => collect([]),
            ]);
        }

        // === 2️⃣ Сводная информация ===
        $summary = [
            'views'     => ProductStat::whereIn('product_id', $productIds)->sum('views'),
            'favorites' => Favorite::whereIn('product_id', $productIds)->count(),
            'cart_adds' => CartItem::whereIn('product_id', $productIds)->count(),
            'total'     => $products->count(),
        ];

        // === 3️⃣ Динамика активности за 7 дней (из таблицы product_stats) ===
// === 3️⃣ Динамика активности + количество товаров за 7 дней ===
$stats = ProductStat::selectRaw('
        date,
        SUM(views) as views,
        SUM(favorites) as favs,
        SUM(carts) as carts
    ')
    ->whereIn('product_id', $productIds)
    ->where('date', '>=', Carbon::now()->subDays(6)->toDateString())
    ->groupBy('date')
    ->orderBy('date')
    ->get()
    ->map(function ($day) use ($user) {
        // считаем количество активных товаров на этот день
        $day->total_products = Product::where('user_id', $user->id)
            ->whereDate('created_at', '<=', $day->date)
            ->count();
        return $day;
    });


        // === 4️⃣ Топ-5 товаров по активности ===
        $topProducts = Product::whereIn('id', $productIds)
            ->withCount([
                // просмотры
                'views as views' => function ($q) {
                    $q->select(\DB::raw('SUM(views)'));
                },
                // избранное
                'favorites as favs' => function ($q) {
                    $q->select(\DB::raw('COUNT(*)'));
                },
                // корзина
                'cartItems as carts' => function ($q) {
                    $q->select(\DB::raw('COUNT(*)'));
                },
            ])
            ->get()
            ->map(function ($p) {
                return [
                    'id'    => $p->id,
                    'title' => $p->title ?? 'Без названия',
                    'image' => $p->image
                        ? asset('storage/' . $p->image)
                        : asset('img/no-image.png'),
                    'views' => (int) $p->views,
                    'favs'  => (int) $p->favs,
                    'carts' => (int) $p->carts,
                    'score' => (int) ($p->views + $p->favs * 2 + $p->carts * 3),
                ];
            })
            ->sortByDesc('score')
            ->take(5)
            ->values();

        // === 5️⃣ Передаём данные во View ===
        return view('seller.analytics.index', [
            'summary'     => $summary,
            'stats'       => $stats,
            'topProducts' => $topProducts,
            'products'    => $products,
        ]);
    }
}
