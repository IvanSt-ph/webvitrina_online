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
        // Проверяем, есть ли запись в избранном
        $fav = Favorite::where([
            'user_id' => auth()->id(),
            'product_id' => $product->id
        ])->first();

        if ($fav) {
            // Если уже есть — удаляем
            $fav->delete();
            $state = false;
        } else {
            // Если нет — добавляем
            Favorite::create([
                'user_id' => auth()->id(),
                'product_id' => $product->id
            ]);
            $state = true;
        }

        // ✅ Если запрос AJAX (fetch), возвращаем JSON без перезагрузки
        if (request()->expectsJson()) {
            return response()->json([
                'status'  => $state ? 'added' : 'removed',
                'message' => $state ? 'Добавлено в избранное' : 'Удалено из избранного'
            ]);
        }

        // 🔁 Если это обычный POST-запрос (через <form>), делаем редирект как раньше
        return back()->with(
            'success',
            $state ? 'Добавлено в избранное' : 'Удалено из избранного'
        );
    }
}
