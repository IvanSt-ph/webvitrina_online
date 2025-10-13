<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

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

public function show($slug)
{
    // Ищем товар по основному slug
    $product = \App\Models\Product::where('slug', $slug)->first();

    // Если не нашли — ищем по старым slug
    if (!$product) {
        $old = \App\Models\ProductSlug::where('slug', $slug)->first();

        if ($old && $old->product) {
            // 🔁 301 редирект на новый slug
            return redirect()->route('product.show', $old->product->slug, 301);
        }

        abort(404);
    }

    return view('shop.product-show', compact('product'));

}






}
