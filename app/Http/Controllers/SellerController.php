<?php

namespace App\Http\Controllers;

use App\Models\User;

class SellerController extends Controller
{
    public function show(User $user)
    {
        // Товары этого продавца
        $products = $user->products()->latest()->paginate(12);

        return view('seller.show', compact('user', 'products'));
    }
}
