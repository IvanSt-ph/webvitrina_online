<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ProductRepository;
use App\Models\ProductStat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Conversation;

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
        return view('shop.index', compact('products'));
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
