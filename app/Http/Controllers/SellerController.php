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
        $products = $user->products()
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
