<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\ProductStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = CartItem::with([
            'product.category',
            'product.city.country',
            'product.seller',
        ])
            ->where('user_id', auth()->id())
            ->get();

        $items = $cartItems
            ->filter(fn (CartItem $item) => $item->product && $item->product->status === 'active' && $item->product->stock > 0)
            ->values();
        $unavailableItems = $cartItems
            ->reject(fn (CartItem $item) => $item->product && $item->product->status === 'active' && $item->product->stock > 0)
            ->values();

        // считаем общую сумму
        $total = 0;
        foreach ($items as $item) {
            if ($item->product) {
                $total += $item->product->price * $item->qty;
            }
        }

        $currentProductIds = $items->pluck('product.id')->filter()->values();
        $categoryIds = $items->pluck('product.category_id')->unique()->filter()->values();

        $crossSellProducts = Product::query()
            ->active()
            ->whereNotIn('id', $currentProductIds)
            ->whereIn('category_id', $categoryIds)
            ->latest('id')
            ->limit(4)
            ->get();

        $recommendedProducts = Product::query()
            ->active()
            ->whereNotIn('id', $currentProductIds)
            ->latest('id')
            ->limit(4)
            ->get();

        return view('shop.cart', compact(
            'items',
            'unavailableItems',
            'total',
            'crossSellProducts',
            'recommendedProducts'
        ));
    }

    public function add(Product $product, Request $request)
    {
        abort_if($product->status !== 'active', 404);

        $request->validate([
            'qty' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        // ❌ ЗАЩИТА: запрет покупки своего товара
        if ($product->user_id === auth()->id()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Вы не можете добавить в корзину собственный товар.'
                ], 403);
            }

            return back()->with('error', 'Вы не можете добавить в корзину собственный товар.');
        }

        $qty = (int)($request->input('qty', 1));

        if ($product->stock < 1) {
            throw ValidationException::withMessages([
                'qty' => 'Товара нет в наличии.',
            ]);
        }

        $item = CartItem::firstOrNew([
            'user_id'    => auth()->id(),
            'product_id' => $product->id,
        ]);

        $newQty = max(1, (int) $item->qty + $qty);

        if ($newQty > $product->stock) {
            throw ValidationException::withMessages([
                'qty' => "Доступно только {$product->stock} шт. Возможно, часть товара уже купили другие пользователи.",
            ]);
        }

        $today = Carbon::today()->toDateString();

        // если пользователь впервые добавляет этот товар в корзину
        if (! $item->exists) {
            $product->increment('cart_adds_count');

            ProductStat::updateOrCreate(
                ['product_id' => $product->id, 'date' => $today],
                ['carts' => DB::raw('carts + 1')]
            );
        }

        $item->qty = $newQty;
        $item->save();

        $removedFromFavorites = false;

        if ($request->boolean('remove_from_favorites')) {
            $removedFromFavorites = Favorite::where('user_id', auth()->id())
                ->where('product_id', $product->id)
                ->delete() > 0;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'quantity' => $item->qty,
                'removed_from_favorites' => $removedFromFavorites,
                'message' => $removedFromFavorites ? 'Товар перенесён в корзину' : 'Товар добавлен в корзину'
            ]);
        }

        return back()
            ->with('success', $removedFromFavorites ? 'Товар перенесён в корзину!' : 'Товар добавлен в корзину!')
            ->with('cart_added_id', $product->id);
    }

    public function addFavorites(Request $request)
    {
        $data = $request->validate([
            'favorite_ids' => ['nullable', 'array'],
            'favorite_ids.*' => ['integer'],
            'remove_from_favorites' => ['nullable', 'boolean'],
        ]);

        $favoritesQuery = Favorite::with('product')
            ->where('user_id', auth()->id());

        if (! empty($data['favorite_ids'])) {
            $favoritesQuery->whereIn('id', $data['favorite_ids']);
        }

        $favorites = $favoritesQuery->get();

        $added = 0;
        $addedFavoriteIds = [];
        $today = Carbon::today()->toDateString();

        foreach ($favorites as $favorite) {
            $product = $favorite->product;

            if (! $product || $product->status !== 'active' || $product->user_id === auth()->id()) {
                continue;
            }

            $item = CartItem::firstOrNew([
                'user_id' => auth()->id(),
                'product_id' => $product->id,
            ]);

            if (((int) $item->qty + 1) > $product->stock) {
                continue;
            }

            if (! $item->exists) {
                $product->increment('cart_adds_count');

                ProductStat::updateOrCreate(
                    ['product_id' => $product->id, 'date' => $today],
                    ['carts' => DB::raw('carts + 1')]
                );
            }

            $item->qty = max(1, (int) $item->qty + 1);
            $item->save();
            $added++;
            $addedFavoriteIds[] = $favorite->id;
        }

        if ($request->boolean('remove_from_favorites') && ! empty($addedFavoriteIds)) {
            Favorite::where('user_id', auth()->id())
                ->whereIn('id', $addedFavoriteIds)
                ->delete();
        }

        return back()->with(
            $added > 0 ? 'success' : 'error',
            $added > 0
                ? ($request->boolean('remove_from_favorites')
                    ? "Перенесено в корзину: {$added} товар(ов)"
                    : "Добавлено в корзину: {$added} товар(ов)")
                : 'В избранном нет товаров, которые можно добавить в корзину.'
        );
    }

    public function update(CartItem $item, Request $request)
    {
        $this->authorize('update', $item);

        $data = $request->validate([
            'qty' => ['required','integer','min:1','max:999'],
        ]);

        $item->loadMissing('product');
        if ($item->product && $data['qty'] > $item->product->stock) {
            throw ValidationException::withMessages([
                'qty' => "Доступно только {$item->product->stock} шт. Возможно, часть товара уже купили другие пользователи.",
            ]);
        }

        $item->update(['qty' => $data['qty']]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'qty' => $item->qty,
            ]);
        }

        return back()->with('success', 'Количество обновлено');
    }

    public function remove(CartItem $item)
    {
        $this->authorize('delete', $item);

        $item->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Товар удалён из корзины',
            ]);
        }

        return back()->with('success', 'Товар удалён из корзины');
    }
}
