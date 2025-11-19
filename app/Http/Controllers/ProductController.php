<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ProductRepository;
use App\Models\ProductStat;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    protected $products;

    public function __construct(ProductRepository $products)
    {
        $this->products = $products;
    }

    /**
     * 🛍️ Главная витрина товаров + фильтры
     */
    public function index(Request $request)
    {
        // Репозиторий уже делает with()
        $products = $this->products->getFilteredProducts($request);

        return view('shop.index', compact('products'));
    }

    /**
     * 📄 Страница товара
     */
    public function show($key, Request $request)
    {
        // Репозиторий сам подгружает нужные связи
        $product = $this->products->getProductBySlugOrId($key);

        // Если редирект — вернуть его
        if ($product instanceof \Illuminate\Http\RedirectResponse) {
            return $product;
        }

        /** -----------------------------------------
         * 📸 Исправление галереи (защита от ошибок)
         * -----------------------------------------
         */
        if (!is_array($product->gallery)) {
            $decoded = json_decode($product->gallery, true);
            $product->gallery = is_array($decoded) ? $decoded : [];
        }

        /** -----------------------------------------
         * 👁 Защита от накрутки просмотров
         * -----------------------------------------
         */

        // Уникальный идентификатор пользователя или IP
        $viewer = auth()->id()
            ? 'user:' . auth()->id()
            : 'ip:' . $request->ip();

        $cacheKey = "product_viewed:{$product->id}:{$viewer}";

        if (!Cache::has($cacheKey)) {
            // +1 просмотр в таблице products
            $product->increment('views_count');

            // +1 в ежедневной статистике
            ProductStat::updateOrCreate(
                ['product_id' => $product->id, 'date' => today()],
                ['views' => \DB::raw('views + 1')]
            );

            // Антифлуд — 1 час
            Cache::put($cacheKey, true, now()->addHour());
        }

        /** -----------------------------------------
         * ⭐ Отзывы (+ юзер, фото)
         * -----------------------------------------
         */
        $reviews = $product->reviews()
            ->where('status', 'approved')
            ->with(['user', 'images'])
            ->latest()
            ->get();

        /** -----------------------------------------
         * 🔄 Похожие товары
         * -----------------------------------------
         */
        $related = $this->products->getRelatedProducts($product);

        return view('shop.product-show', compact(
            'product',
            'related',
            'reviews'
        ));
    }
}
