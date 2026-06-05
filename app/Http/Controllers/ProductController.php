<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ProductRepository;
use App\Models\Banner;
use App\Models\Category;
use App\Models\ProductStat;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Conversation;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private const RECENT_MESSAGES_LIMIT = 50;

    public function __construct(protected ProductRepository $products) {}

    /* ============================================================
     | 🛍️ Главная витрина
     ============================================================ */
    public function index(Request $request)
    {
        $products = $this->products->getFilteredProducts($request);
        $bannerItems = Cache::remember('slides_home', 3600, function () {
            return Banner::where('active', true)
                ->orderBy('sort_order')
                ->get(['image_desktop', 'image_tablet', 'image_mobile', 'link']);
        });

        return view('shop.index', compact('products', 'bannerItems'));
    }

    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([
                'products' => [],
                'categories' => [],
                'shops' => [],
            ]);
        }

        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';

        $products = Product::query()
            ->active()
            ->with(['category:id,name,slug'])
            ->where(function ($query) use ($like, $q) {
                $query->where('title', 'like', $like);

                if (ctype_digit($q)) {
                    $query->orWhere('id', (int) $q);
                }
            })
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (Product $product) => [
                'title' => $product->title,
                'subtitle' => $product->category?->name ?? 'Товар',
                'url' => route('product.show', $product->slug),
                'image' => $product->image_thumb_url,
            ]);

        $categories = Category::query()
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(4)
            ->get(['name', 'slug'])
            ->map(fn (Category $category) => [
                'title' => $category->name,
                'subtitle' => 'Категория',
                'url' => route('category.show', $category->slug),
            ]);

        $shops = Shop::query()
            ->where('name', 'like', $like)
            ->orderBy('name')
            ->limit(4)
            ->get(['name', 'slug', 'description'])
            ->map(fn (Shop $shop) => [
                'title' => $shop->name,
                'subtitle' => Str::limit(strip_tags($shop->description ?: 'Магазин продавца'), 48),
                'url' => route('seller.show', $shop->slug),
            ]);

        return response()->json(compact('products', 'categories', 'shops'));
    }

    /* ============================================================
     | 📄 Страница товара
     ============================================================ */
    public function show($key, Request $request)
    {
        /* --------------------------------------
         | 📌 Получение товара (кэшируется)
         -------------------------------------- */
        $product = $this->products->getProductBySlugOrId($key);

        if ($product instanceof \Illuminate\Http\RedirectResponse) {
            return $product;
        }

        /* --------------------------------------
         | 📸 Исправление галереи
         -------------------------------------- */
        if (!is_array($product->gallery)) {
            $decoded = json_decode($product->gallery, true);
            $product->gallery = is_array($decoded) ? $decoded : [];
        }

        /* --------------------------------------
         | ❗ Защита: категория может быть удалена
         -------------------------------------- */
        if (!$product->category) {
            $product->category = null; // защита для хлебных крошек
        }

        /* --------------------------------------
         | 👁 Уникальный просмотр товара
         -------------------------------------- */
        $viewer = auth()->id()
            ? 'user:' . auth()->id()
            : 'ip:' . $request->ip();

        $cacheKey = "product_viewed:{$product->id}:{$viewer}";

        $userId = auth()->id();

        // ❗ Пропускаем увеличение просмотров для автора
        if ($product->user_id !== $userId && !Cache::has($cacheKey)) {

            DB::transaction(function () use ($product) {
                // +1 просмотр товара
                $product->increment('views_count');

                // +1 в статистике / день
                ProductStat::updateOrCreate(
                    ['product_id' => $product->id, 'date' => today()],
                    ['views' => DB::raw('views + 1')]
                );
            });

            Cache::put($cacheKey, true, now()->addHour());
        }

/* --------------------------------------
 | ⭐ Отзывы (пагинация)
 -------------------------------------- */
$reviews = $product->reviews()
    ->where('status', 'approved')
    ->with(['user:id,name', 'images:id,review_id,path'])
    ->latest()
    ->paginate(10);

$reviewStats = $product->reviews()
    ->where('status', 'approved')
    ->selectRaw('COUNT(*) as total')
    ->selectRaw('COALESCE(AVG(rating), 0) as average')
    ->selectRaw('SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as star_5')
    ->selectRaw('SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as star_4')
    ->selectRaw('SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as star_3')
    ->selectRaw('SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as star_2')
    ->selectRaw('SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as star_1')
    ->first();

/* --------------------------------------
 | 👤 Мой отзыв (отдельный запрос)
 -------------------------------------- */
$myReview = null;
if (auth()->check()) {
    $myReview = $product->reviews()
        ->where('user_id', auth()->id())
        ->with('images')
        ->first();
}

        /* --------------------------------------
         | 🔄 Похожие товары (кэшируется в репозитории)
         -------------------------------------- */
        $related = $this->products->getRelatedProducts($product);

        $chatConversation = null;
        $chatMessages = collect();
        $chatHasOlderMessages = false;
        $chatOldestMessageId = null;
        $chatLatestMessageId = null;
        $chatLatestReadOutgoingMessageId = 0;

        if (auth()->check() && request()->filled('chat')) {
            $chatConversation = Conversation::with(['buyer', 'seller', 'product'])
                ->findOrFail(request()->integer('chat'));

            abort_unless($chatConversation->includes(auth()->user()), 403);
            abort_unless($chatConversation->product_id === $product->id, 404);

            $chatConversation->messages()
                ->where('sender_id', '!=', auth()->id())
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $chatMessages = $chatConversation->recentMessages(self::RECENT_MESSAGES_LIMIT);
            $chatHasOlderMessages = $chatConversation->hasMoreThanRecentMessages(self::RECENT_MESSAGES_LIMIT);
            $chatOldestMessageId = $chatMessages->first()?->id;
            $chatLatestMessageId = $chatMessages->last()?->id;
            $chatLatestReadOutgoingMessageId = $chatConversation->latestReadOutgoingMessageIdFor(auth()->user());
        }

        /* --------------------------------------
         | 📘 Кэш хлебных крошек (быстро + безопасно)
         -------------------------------------- */
        if ($product->category) {
            $breadcrumbs = Cache::remember(
                "product.breadcrumbs:{$product->id}",
                3600,
                function () use ($product) {
                    $arr = [];
                    $cat = $product->category;

                    while ($cat) {
                        $arr[] = $cat;
                        $cat = $cat->parent;
                    }

                    return array_reverse($arr);
                }
            );
        } else {
            $breadcrumbs = [];
        }

        /* --------------------------------------
         | 📤 Возврат готовых данных
         -------------------------------------- */
        return view('shop.product-show', [
            'product'     => $product,
            'related'     => $related,
            'reviews'     => $reviews,
            'reviewStats'  => $reviewStats,
            'myReview'    => $myReview,  // ← мой отзыв
            'breadcrumbs' => $breadcrumbs,
            'chatConversation' => $chatConversation,
            'chatMessages' => $chatMessages,
            'chatHasOlderMessages' => $chatHasOlderMessages,
            'chatOldestMessageId' => $chatOldestMessageId,
            'chatLatestMessageId' => $chatLatestMessageId,
            'chatLatestReadOutgoingMessageId' => $chatLatestReadOutgoingMessageId,
        ]);
    }
}
