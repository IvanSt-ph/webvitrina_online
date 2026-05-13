<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Review;

class SellerController extends Controller
{
    public function show(User $user)
    {
        // Магазин продавца
        $shop = $user->shop;

        // Товары продавца
        $productsQuery = $user->products()
            ->active()
            ->with(['category', 'seller.shop', 'city.country'])
            ->withCount([
                'reviews as reviews_count' => fn($q) => $q->where('status', 'approved'),
            ])
            ->withAvg([
                'reviews as reviews_avg_rating' => fn($q) => $q->where('status', 'approved'),
            ], 'rating');

        if (auth()->check()) {
            $productsQuery
                ->withSum([
                    'cartItems as cart_quantity' => fn($q) => $q->where('user_id', auth()->id()),
                ], 'qty')
                ->withExists([
                    'favorites as is_favorited' => fn($q) => $q->where('user_id', auth()->id()),
                ]);
        }

        $products = $productsQuery
            ->latest()
            ->paginate(12);

        // Отзывы по товарам продавца
        $reviews = Review::whereHas('product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('status', 'approved')
            ->latest()
            ->limit(10)
            ->get();

        return view('seller.show', compact(
            'user',
            'shop',
            'products',
            'reviews'
        ));
    }
}
