<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductRepository
{
    /** 🔍 Фильтрация и сортировка товаров (универсальная) */
    public function getFilteredProducts($request)
    {
        $query = Product::query()->with(['city.country']);

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->user_id);
        }

        if ($request->filled('country_id')) {
            $countryId = (int) $request->country_id;
            $query->whereHas('city', fn($q) => $q->where('country_id', $countryId));

            if ($request->filled('city_id')) {
                $query->where('city_id', (int) $request->city_id);
            }
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->category_id);
        }

        $sort = $request->get('sort', 'new');
        match ($sort) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating'     => $query->withAvg('reviews', 'rating')->orderBy('reviews_avg_rating', 'desc'),
            'benefit'    => $query->orderByRaw('(price / greatest(stock,1)) asc'),
            default      => $query->latest(),
        };

        return $query->paginate((int) ($request->get('per_page', 12)) ?: 12)
                     ->withQueryString();
    }

    /** 🧾 Получение товара по slug или ID (с кэшированием) */
    public function getProductBySlugOrId($key)
    {
        if (is_numeric($key)) {
            $product = Product::find($key);
            if ($product) {
                return redirect()->route('product.show', $product->slug, 301);
            }
        }

        $cacheKey = "product_by_slug:{$key}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($key) {
            $product = Product::with([
                'city.country',
                'category.parent',
                'seller',
                'reviews' => function ($q) {
                    $q->where('status', 'approved')
                      ->with(['user', 'images'])
                      ->latest();
                },
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
                $oldSlug = \App\Models\ProductSlug::where('slug', $key)->first();
                if ($oldSlug && $oldSlug->product) {
                    return redirect()->route('product.show', $oldSlug->product->slug, 301);
                }
                abort(404);
            }

            return $product;
        });
    }

    /** 🔄 Похожие товары (4 штуки, кэшируются) */
    public function getRelatedProducts($product)
    {
        return Cache::remember("related_products:{$product->id}", now()->addMinutes(10), function () use ($product) {
            return Product::select('id', 'slug', 'title', 'price', 'image')
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->take(4)
                ->get();
        });
    }

    /** 🧹 Очистка кэша товара (при добавлении или изменении отзыва) */
    public static function clearProductCache($product)
    {
        if (!$product) return;

        Cache::forget("product_by_slug:{$product->slug}");
        Cache::forget("related_products:{$product->id}");
    }
}
