<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $r, Product $product){
        $data = $r->validate([
            'rating'=>'required|integer|min:1|max:5',
            'body'=>'nullable|string|max:2000'
        ]);
        Review::updateOrCreate(
            ['user_id'=>auth()->id(),'product_id'=>$product->id],
            $data + ['user_id'=>auth()->id(),'product_id'=>$product->id]
        );
        return back()->with('success','Отзыв сохранён');
    }
}
