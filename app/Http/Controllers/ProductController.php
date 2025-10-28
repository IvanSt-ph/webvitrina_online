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

    public function index(Request $request)
    {
        $products = $this->products->getFilteredProducts($request);
        return view('shop.index', compact('products'));
    }



public function show($key, Request $request)
{
    $product = $this->products->getProductBySlugOrId($key);

    if ($product instanceof \Illuminate\Http\RedirectResponse) {
        return $product;
    }

    // 🧩 Если gallery — строка, конвертируем в массив
    if (!is_array($product->gallery)) {
        $decoded = json_decode($product->gallery, true);
        $product->gallery = is_array($decoded) ? $decoded : [];
    }

    // ✅ Защита от накрутки просмотров
    $viewer = auth()->id() ? 'user:' . auth()->id() : 'ip:' . $request->ip();
    $cacheKey = 'product_viewed:' . $product->id . ':' . $viewer;

    if (!Cache::has($cacheKey)) {
        // 1️⃣ Увеличиваем глобальный счётчик в products
        $product->increment('views_count');

        // 2️⃣ Добавляем/обновляем статистику за текущий день
        ProductStat::updateOrCreate(
            ['product_id' => $product->id, 'date' => today()],
            ['views' => \DB::raw('views + 1')]
        );

        // 3️⃣ Антифлуд — запоминаем, что пользователь уже смотрел
        Cache::put($cacheKey, true, now()->addHour());
    }

    // ✅ Отзывы и похожие товары
    $reviews = $product->reviews()
        ->where('status', 'approved')
        ->latest()
        ->with('user')
        ->get();

    $related = $this->products->getRelatedProducts($product);

    return view('shop.product-show', compact('product', 'related', 'reviews'));
}


}
