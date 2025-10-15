<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductRepository
{
    public function getFilteredProducts($request)
    {
        $query = Product::query()->with(['city.country']);

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        if ($request->filled('country_id')) {
            $countryId = (int) $request->country_id;
            $query->whereHas('city', fn($q) => $q->where('country_id', $countryId));

            if ($request->filled('city_id')) {
                $query->where('city_id', (int)$request->city_id);
            }
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', (int)$request->category_id);
        }

        $sort = $request->get('sort', 'new');
        match ($sort) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'rating'     => $query->withAvg('reviews', 'rating')->orderBy('reviews_avg_rating', 'desc'),
            'benefit'    => $query->orderByRaw('(price / greatest(stock,1)) asc'),
            default      => $query->latest(),
        };

        return $query->paginate(12)->withQueryString();
    }

    public function getProductBySlugOrId($key)
    {
        // Если это ID → редиректим на slug
        if (is_numeric($key)) {
            $product = Product::find($key);
            if ($product) {
                return redirect()->route('product.show', $product->slug, 301);
            }
        }

        $cacheKey = "product_by_slug:{$key}";

        $product = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($key) {
            return Product::with([
                'city.country',
                'category.parent',
                'user',
                'reviews.user',
                'reviews.images',
            ])
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->where('slug', $key)
                ->first();
        });

        if (!$product) {
            $oldCacheKey = "product_old_slug:{$key}";
            $oldSlug = Cache::remember($oldCacheKey, now()->addMinutes(30), function () use ($key) {
                return \App\Models\ProductSlug::where('slug', $key)->first();
            });

            if ($oldSlug && $oldSlug->product) {
                return redirect()->route('product.show', $oldSlug->product->slug, 301);
            }

            abort(404);
        }

        return $product;
    }

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
    
}


