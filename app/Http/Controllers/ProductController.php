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
        $product = Product::with([
            'seller' => fn($q)=>$q->withAvg('reviews','rating')->withCount('reviews'),
            'reviews.user','category.parent'
        ])
        ->withAvg('reviews','rating')
        ->withCount('reviews')
        ->where('slug',$slug)
        ->firstOrFail();

        $related = Product::where('category_id',$product->category_id)
            ->where('id','!=',$product->id)->limit(4)->get();

        return view('shop.product-show', compact('product','related'));
    }
}
