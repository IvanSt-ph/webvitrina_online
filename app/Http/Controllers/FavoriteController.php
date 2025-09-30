<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;

class FavoriteController extends Controller
{
    public function index(){
        $items = Favorite::with('product')->where('user_id',auth()->id())->latest()->get();
        return view('shop.favorites', compact('items'));
    }

    public function toggle(Product $product){
        $fav = Favorite::where(['user_id'=>auth()->id(),'product_id'=>$product->id])->first();
        if($fav){ $fav->delete(); $state=false; } else { Favorite::create(['user_id'=>auth()->id(),'product_id'=>$product->id]); $state=true; }
        return back()->with('success',$state?'Добавлено в избранное':'Удалено из избранного');
    }
}
