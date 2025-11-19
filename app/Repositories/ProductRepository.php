<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductRepository
{
    /* ============================================================
     |  ФИЛЬТРАЦИЯ ТОВАРОВ ДЛЯ ВИТРИНЫ
     ============================================================ */
    public function getFilteredProducts($request)
    {
        $query = Product::query()
            ->with([
                'category',
                'seller',
                'city.country',
                'reviews',
            ])
            ->withAvg([
                'reviews as reviews_avg_rating' => fn($q) => $q->where('status', 'approved'),
            ], 'rating')
            ->withCount([
                'reviews as reviews_count' => fn($q) => $q->where('status', 'approved'),
            ]);

        // 🔎 Поиск
        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        // 📦 Продавец
        if ($request->filled('user_id')) {
            $query->where('user_id', (int)$request->user_id);
        }

        // 🌍 Страна/город
        if ($request->filled('country_id')) {
            $query->whereHas('city', function ($q) use ($request) {
                $q->where('country_id', (int)$request->country_id);
            });

            if ($request->filled('city_id')) {
                $query->where('city_id', (int)$request->city_id);
            }
        }

        // 📂 Категория
        if ($request->filled('category_id')) {
            $query->where('category_id', (int)$request->category_id);
        }

        // 📊 Сортировка
        match ($request->get('sort', 'new')) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating'     => $query->orderBy('reviews_avg_rating', 'desc'),
            'benefit'    => $query->orderByRaw('(price / greatest(stock,1)) asc'),
            default      => $query->latest(),
        };

        return $query->paginate(
            (int)($request->get('per_page', 20))
        )->withQueryString();
    }

    /* ============================================================
     |  ПОЛУЧЕНИЕ ТОВАРА ПО SLUG ИЛИ ID
     ============================================================ */
    public function getProductBySlugOrId(string|int $key)
    {
        // Если ID — редирект на slug
        if (is_numeric($key)) {
            $product = Product::with([
                'city.country',
                'category.parent',
                'seller',
            ])->find($key);

            if ($product) {
                return redirect()->route('product.show', $product->slug, 301);
            }
        }

        $cacheKey = "product_page:{$key}";

        return Cache::remember($cacheKey, 600, function () use ($key) {

            $product = Product::with([
                'city.country',
                'category.parent',
                'seller',
                'reviews' => fn($q) =>
                    $q->where('status', 'approved')
                        ->with(['user', 'images'])
                        ->latest()
            ])
                ->withCount([
                    'reviews as reviews_count' => fn($q) => $q->where('status', 'approved'),
                ])
                ->withAvg([
                    'reviews as reviews_avg_rating' => fn($q) => $q->where('status', 'approved'),
                ], 'rating')
                ->where('slug', $key)
                ->first();

            if (!$product) {
                $old = \App\Models\ProductSlug::where('slug', $key)->first();
                if ($old && $old->product) {
                    return redirect()->route('product.show', $old->product->slug, 301);
                }

                abort(404);
            }

            return $product;
        });
    }

    /* ============================================================
     |  ПОХОЖИЕ ТОВАРЫ
     ============================================================ */
    public function getRelatedProducts(Product $product)
    {
        return Cache::remember("related:{$product->id}", 600, function () use ($product) {
            return Product::select('id', 'slug', 'title', 'price', 'image')
                ->with('category')
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->limit(4)
                ->get();
        });
    }

    /* ============================================================
     |  ОЧИСТКА КЭША
     ============================================================ */
    public function clearCache(Product $product)
    {
        Cache::forget("product_page:{$product->slug}");
        Cache::forget("related:{$product->id}");
    }
}
