<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\ProductRepository;

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

public function show($key)
{
    $product = $this->products->getProductBySlugOrId($key);

    // если метод getProductBySlugOrId() вернул redirect — Laravel его выполнит
    if ($product instanceof \Illuminate\Http\RedirectResponse) {
        return $product;
    }

    // 🧩 Страховка: если gallery — строка, конвертируем в массив
    if (!is_array($product->gallery)) {
        $decoded = json_decode($product->gallery, true);
        $product->gallery = is_array($decoded) ? $decoded : [];
    }

    // ✅ Загружаем только одобренные отзывы с пользователями
    $reviews = $product->reviews()
        ->where('status', 'approved')
        ->latest()
        ->with('user')
        ->get();

    // ✅ Подгружаем похожие товары
    $related = $this->products->getRelatedProducts($product);

    // ✅ Передаём всё в шаблон
    return view('shop.product-show', compact('product', 'related', 'reviews'));
}

}
