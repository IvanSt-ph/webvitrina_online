<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Product;
use App\Models\ProductStat;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

        $state = DB::transaction(function () use ($product, $userId): bool {
            User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $currentProduct = Product::whereKey($product->id)->firstOrFail();
            abort_if($currentProduct->status !== 'active', 404);
            abort_if($currentProduct->user_id === $userId, 403);

            $favorite = Favorite::where([
                'user_id' => $userId,
                'product_id' => $currentProduct->id,
            ])->first();

            if ($favorite) {
                $favorite->delete();
                Product::whereKey($currentProduct->id)
                    ->where('favorites_count', '>', 0)
                    ->decrement('favorites_count');
                ProductStat::where([
                    'product_id' => $currentProduct->id,
                    'date' => Carbon::today()->toDateString(),
                ])->where('favorites', '>', 0)->decrement('favorites');

                return false;
            }

            Favorite::create([
                'user_id' => $userId,
                'product_id' => $currentProduct->id,
            ]);
            Product::whereKey($currentProduct->id)->increment('favorites_count');
            ProductStat::addFavorite($currentProduct->id);

            return true;
        });

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
        $userId = auth()->id();
        abort_unless($favorite->user_id === $userId, 403);

        DB::transaction(function () use ($favorite, $userId): void {
            User::whereKey($userId)->lockForUpdate()->firstOrFail();

            $current = Favorite::whereKey($favorite->id)
                ->where('user_id', $userId)
                ->first();

            if (! $current) {
                return;
            }

            $productId = $current->product_id;
            $current->delete();
            Product::whereKey($productId)
                ->where('favorites_count', '>', 0)
                ->decrement('favorites_count');
        });

        return back()->with('success', 'Товар удалён из избранного');
    }
}
