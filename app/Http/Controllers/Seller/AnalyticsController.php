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
    $productIds = Product::where('user_id', $user->id)->pluck('id');

    $stats = ProductStat::whereIn('product_id', $productIds)
        ->where('date', $date)
        ->with('product:id,title')
        ->get(['product_id','views','favorites','carts']);

    $data = $stats->map(fn($s) => [
        'title' => $s->product->title ?? 'Без названия',
        'views' => $s->views,
        'favorites' => $s->favorites,
        'carts' => $s->carts,
    ]);

    return response()->json($data);
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
            ->get();

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
