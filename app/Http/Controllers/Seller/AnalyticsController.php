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
    // 📊 Данные по дню
    public function dayStats($date)
    {
        $user = Auth::user();
        $cacheKey = "seller_analytics_day_{$user->id}_{$date}";

        if (cache()->has($cacheKey)) {
            return response()->json(cache($cacheKey));
        }

        $productIds = Product::where('user_id', $user->id)->pluck('id');

        $stats = ProductStat::whereIn('product_id', $productIds)
            ->whereDate('date', $date)
            ->with('product:id,title')
            ->get(['product_id', 'views', 'favorites', 'carts']);

        $data = $stats->map(fn($s) => [
            'id'        => (int) $s->product_id,
            'title'     => $s->product->title ?? 'Без названия',
            'views'     => (int) $s->views,
            'favorites' => (int) $s->favorites,
            'carts'     => (int) $s->carts,
        ])->values();

        cache()->put($cacheKey, $data, now()->addMinutes(5));
        return response()->json($data);
    }

    // 🛒 Товары, добавленные в конкретный день
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

    // 📈 Главная аналитика
    public function index()
    {
        $user = Auth::user();

        // 1️⃣ Получаем товары продавца
        $products = Product::where('user_id', $user->id)->get();
        $productIds = $products->pluck('id');

        if ($productIds->isEmpty()) {
            return view('seller.analytics.index', [
                'summary'     => ['views' => 0, 'favorites' => 0, 'cart_adds' => 0, 'total' => 0, 'engagement' => 0],
                'stats'       => collect([]),
                'topProducts' => collect([]),
                'products'    => collect([]),
                'categories'  => collect([]),
            ]);
        }

        // 2️⃣ Сводная информация
        $summary = [
            'views'     => ProductStat::whereIn('product_id', $productIds)->sum('views'),
            'favorites' => Favorite::whereIn('product_id', $productIds)->count(),
            'cart_adds' => CartItem::whereIn('product_id', $productIds)->count(),
            'total'     => $products->count(),
        ];

        // 3️⃣ Вовлечённость
        $views = max($summary['views'], 1); // чтобы не делить на 0
        $summary['engagement'] = round((($summary['favorites'] + $summary['cart_adds']) / $views) * 100, 1);

        // 4️⃣ Динамика активности (7 дней)
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
                $day->total_products = Product::where('user_id', $user->id)
                    ->whereDate('created_at', '<=', $day->date)
                    ->count();
                return $day;
            });

        // 5️⃣ Топ-5 товаров по активности
        $topProducts = Product::whereIn('id', $productIds)
            ->withCount([
                'views as views' => fn($q) => $q->select(\DB::raw('SUM(views)')),
                'favorites as favs' => fn($q) => $q->select(\DB::raw('COUNT(*)')),
                'cartItems as carts' => fn($q) => $q->select(\DB::raw('COUNT(*)')),
            ])
            ->get()
            ->map(fn($p) => [
                'id'    => $p->id,
                'title' => $p->title ?? 'Без названия',
                'image' => $p->image ? asset('storage/' . $p->image) : asset('img/no-image.png'),
                'views' => (int) $p->views,
                'favs'  => (int) $p->favs,
                'carts' => (int) $p->carts,
                'score' => (int) ($p->views + $p->favs * 2 + $p->carts * 3),
            ])
            ->sortByDesc('score')
            ->take(5)
            ->values();

        // 6️⃣ Распределение по категориям
        $categoryData = Product::where('user_id', $user->id)
            ->with('category:id,name')
            ->select('category_id')
            ->get()
            ->groupBy('category_id')
            ->map(fn($g) => $g->count());

        $categories = [];
        foreach ($categoryData as $id => $count) {
            $catName = \App\Models\Category::find($id)?->name ?? 'Без категории';
            $categories[] = ['name' => $catName, 'count' => $count];
        }

        // 7️⃣ Передаём во view
        return view('seller.analytics.index', [
            'summary'     => $summary,
            'stats'       => $stats,
            'topProducts' => $topProducts,
            'products'    => $products,
            'categories'  => $categories,
        ]);
    }
}
