<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use App\Models\ProductStat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FavoriteController extends Controller
{
    public function index()
    {
        $items = Favorite::with([
                'product.category',
                'product.city.country',
                'product.seller',
            ])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('shop.favorites', compact('items'));
    }

    public function toggle(Product $product)
    {
        $userId = auth()->id();

        // ❗ Защита: нельзя добавлять свой товар в избранное
        if ($product->user_id === $userId) {
            if (request()->expectsJson()) {
                return response()->json([
                    'status'   => 'error',
                    'favorite' => false,
                    'message'  => 'Вы не можете добавить свой товар в избранное.'
                ]);
            }

            return back()->with('error', 'Вы не можете добавить свой товар в избранное.');
        }

        $fav = Favorite::where([
            'user_id'    => $userId,
            'product_id' => $product->id
        ])->first();

        $today = Carbon::today()->toDateString();

        if ($fav) {
            // ❌ Удаляем из избранного
            $fav->delete();
            $product->decrement('favorites_count');

            // 📊 уменьшаем счётчик за день (но не ниже 0)
            ProductStat::updateOrCreate(
                ['product_id' => $product->id, 'date' => $today],
                ['favorites' => DB::raw('GREATEST(favorites - 1, 0)')]
            );

            $state = false;
        } else {
            // ✅ Добавляем в избранное
            Favorite::create([
                'user_id'    => $userId,
                'product_id' => $product->id
            ]);
            $product->increment('favorites_count');

            // 📊 увеличиваем счётчик за день
            ProductStat::updateOrCreate(
                ['product_id' => $product->id, 'date' => $today],
                ['favorites' => DB::raw('favorites + 1')]
            );

            $state = true;
        }

        if (request()->expectsJson()) {
            return response()->json([
                'status'   => $state ? 'added' : 'removed',
                'favorite' => $state,
                'message'  => $state ? 'Добавлено в избранное' : 'Удалено из избранного'
            ]);
        }

        return back()->with(
            'success',
            $state ? 'Добавлено в избранное' : 'Удалено из избранного'
        );
    }
}