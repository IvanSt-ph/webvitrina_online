<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;

class FavoriteController extends Controller
{
    /**
     * Отображение списка избранных товаров
     */
    public function index()
    {
        $items = Favorite::with('product')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('shop.favorites', compact('items'));
    }

    /**
     * Добавление или удаление товара из избранного
     */
public function toggle(Product $product)
{
    $fav = Favorite::where([
        'user_id'    => auth()->id(),
        'product_id' => $product->id
    ])->first();

    if ($fav) {
        // если уже есть — удаляем
        $fav->delete();
        $product->decrement('favorites_count'); // 👈 уменьшаем счётчик
        $state = false;
    } else {
        // если нет — добавляем
        Favorite::create([
            'user_id'    => auth()->id(),
            'product_id' => $product->id
        ]);
        $product->increment('favorites_count'); // 👈 увеличиваем счётчик
        $state = true;
    }

    if (request()->expectsJson()) {
        return response()->json([
            'status'  => $state ? 'added' : 'removed',
            'message' => $state ? 'Добавлено в избранное' : 'Удалено из избранного'
        ]);
    }

    return back()->with(
        'success',
        $state ? 'Добавлено в избранное' : 'Удалено из избранного'
    );
}

}
