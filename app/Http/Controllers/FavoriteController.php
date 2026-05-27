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
        $favorites = Favorite::with([
                'product.category',
                'product.city.country',
                'product.seller',
            ])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        $items = $favorites
            ->filter(fn (Favorite $favorite) => $favorite->product && $favorite->product->status === 'active')
            ->values();
        $unavailableItems = $favorites
            ->reject(fn (Favorite $favorite) => $favorite->product && $favorite->product->status === 'active')
            ->values();

        return view('shop.favorites', compact('items', 'unavailableItems'));
    }

    public function toggle(Product $product)
    {
        abort_if($product->status !== 'active', 404);

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

            // ✅ ИСПРАВЛЕНО: безопасное уменьшение счётчика
            $stat = ProductStat::where([
                'product_id' => $product->id,
                'date' => $today
            ])->first();

            if ($stat && $stat->favorites > 0) {
                $stat->decrement('favorites');
            }
            // Если статистики нет или favorites = 0, ничего не делаем

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

    public function remove(Favorite $favorite)
    {
        abort_unless($favorite->user_id === auth()->id(), 403);

        $product = $favorite->product;
        $favorite->delete();

        if ($product && $product->favorites_count > 0) {
            $product->decrement('favorites_count');
        }

        return back()->with('success', 'Товар удалён из избранного');
    }
}
