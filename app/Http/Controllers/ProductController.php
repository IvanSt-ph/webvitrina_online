<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with(['city.country']);

        if ($request->filled('q')) {
            $query->where('title','like','%'.$request->q.'%');
        }

        if ($request->filled('country_id')) {
            $countryId = (int) $request->country_id;
            $query->whereHas('city', fn($q)=>$q->where('country_id',$countryId));

            if ($request->filled('city_id')) {
                $query->where('city_id',(int)$request->city_id);
            }
        }

        if ($request->filled('category_id')) {
            $query->where('category_id',(int)$request->category_id);
        }

        $sort = $request->get('sort','new');
        match($sort) {
            'price_asc'  => $query->orderBy('price','asc'),
            'price_desc' => $query->orderBy('price','desc'),
            'rating'     => $query->withAvg('reviews','rating')->orderBy('reviews_avg_rating','desc'),
            'benefit'    => $query->orderByRaw('(price / greatest(stock,1)) asc'),
            default      => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();

        return view('shop.index', compact('products'));
    }

public function show($key)
{
    // 🔹 Если число — ищем по ID
    if (is_numeric($key)) {
        $product = \App\Models\Product::find($key);

        if ($product) {
            // 301 redirect на slug
            return redirect()->route('product.show', $product->slug, 301);
        }
    }

    // 🔹 Ключ кэша для slug
    $cacheKey = "product_by_slug:{$key}";

    // 🔹 Пытаемся достать из кэша (10 минут)
    $product = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($key) {
        return \App\Models\Product::with(['city.country', 'category', 'user'])
            ->where('slug', $key)
            ->first();
    });

    // 🔹 Если не нашли — ищем по старым slug (тоже с кэшем)
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

    return view('shop.product-show', compact('product'));
}






}
