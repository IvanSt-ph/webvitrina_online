<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\ProductStat;
use App\Models\User;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct(private readonly CurrencyService $currency)
    {
    }

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

        $checkoutCurrency = $this->currency->checkoutCurrency(session('currency'));
        $currencySymbol = Product::currencySymbol($checkoutCurrency);

        // Считаем корзину в одной выбранной валюте.
        $total = 0.0;
        foreach ($items as $item) {
            if ($item->product) {
                $price = $this->currency->convert(
                    (float) $item->product->price,
                    Product::normalizeCurrencyCode($item->product->currency_base),
                    $checkoutCurrency,
                );
                $oldPrice = $item->product->old_price
                    ? $this->currency->convert(
                        (float) $item->product->old_price,
                        Product::normalizeCurrencyCode($item->product->currency_base),
                        $checkoutCurrency,
                    )
                    : null;

                $item->setAttribute('checkout_price', $price);
                $item->setAttribute('checkout_old_price', $oldPrice);
                $total += round($price * $item->qty, 2, PHP_ROUND_HALF_UP);
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

        foreach ($crossSellProducts->concat($recommendedProducts) as $product) {
            $product->setAttribute('checkout_price', $this->currency->convert(
                (float) $product->price,
                Product::normalizeCurrencyCode($product->currency_base),
                $checkoutCurrency,
            ));
        }

        $freeShippingThreshold = $this->currency->convert(5000, 'PRB', $checkoutCurrency);

        return view('shop.cart', compact(
            'items',
            'unavailableItems',
            'total',
            'crossSellProducts',
            'recommendedProducts',
            'checkoutCurrency',
            'currencySymbol',
            'freeShippingThreshold'
        ));
    }

    public function add(Product $product, Request $request)
    {
        abort_if($product->status !== 'active', 404);

        $request->validate([
            'qty' => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $userId = auth()->id();

        // ❌ ЗАЩИТА: запрет покупки своего товара
        if ($product->user_id === $userId) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Вы не можете добавить в корзину собственный товар.'
                ], 403);
            }

            return back()->with('error', 'Вы не можете добавить в корзину собственный товар.');
        }

        $qty = (int)($request->input('qty', 1));

        $result = DB::transaction(function () use ($product, $qty, $request, $userId): array {
            User::whereKey($userId)->lockForUpdate()->firstOrFail();

            $currentProduct = Product::whereKey($product->id)->firstOrFail();
            abort_if($currentProduct->status !== 'active', 404);
            abort_if($currentProduct->user_id === $userId, 403);

            if ($currentProduct->stock < 1) {
                throw ValidationException::withMessages([
                    'qty' => 'Товара нет в наличии.',
                ]);
            }

            $item = CartItem::where([
                'user_id' => $userId,
                'product_id' => $currentProduct->id,
            ])->first() ?? new CartItem([
                'user_id' => $userId,
                'product_id' => $currentProduct->id,
            ]);

            $newQty = max(1, (int) $item->qty + $qty);

            if ($newQty > 999) {
                throw ValidationException::withMessages([
                    'qty' => 'Количество товара в корзине не может превышать 999.',
                ]);
            }

            if ($newQty > $currentProduct->stock) {
                throw ValidationException::withMessages([
                    'qty' => "Доступно только {$currentProduct->stock} шт. Возможно, часть товара уже купили другие пользователи.",
                ]);
            }

            $isNew = ! $item->exists;
            $item->qty = $newQty;
            $item->save();

            if ($isNew) {
                Product::whereKey($currentProduct->id)->increment('cart_adds_count');
                ProductStat::addCart($currentProduct->id);
            }

            $removedFromFavorites = false;
            if ($request->boolean('remove_from_favorites')) {
                $removedFromFavorites = Favorite::where('user_id', $userId)
                    ->where('product_id', $currentProduct->id)
                    ->delete() > 0;

                if ($removedFromFavorites) {
                    Product::whereKey($currentProduct->id)
                        ->where('favorites_count', '>', 0)
                        ->decrement('favorites_count');
                }
            }

            return ['quantity' => $item->qty, 'removed_from_favorites' => $removedFromFavorites];
        });

        $removedFromFavorites = $result['removed_from_favorites'];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'quantity' => $result['quantity'],
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

        $userId = auth()->id();
        $added = DB::transaction(function () use ($data, $request, $userId): int {
            User::whereKey($userId)->lockForUpdate()->firstOrFail();

            $favoritesQuery = Favorite::query()->where('user_id', $userId);
            if (! empty($data['favorite_ids'])) {
                $favoritesQuery->whereIn('id', $data['favorite_ids']);
            }

            $added = 0;
            foreach ($favoritesQuery->orderBy('product_id')->get() as $favorite) {
                $product = Product::whereKey($favorite->product_id)->first();

                if (! $product || $product->status !== 'active' || $product->user_id === $userId) {
                    continue;
                }

                $item = CartItem::where([
                    'user_id' => $userId,
                    'product_id' => $product->id,
                ])->first() ?? new CartItem([
                    'user_id' => $userId,
                    'product_id' => $product->id,
                ]);
                $newQty = max(1, (int) $item->qty + 1);

                if ($newQty > 999 || $newQty > $product->stock) {
                    continue;
                }

                $isNew = ! $item->exists;
                $item->qty = $newQty;
                $item->save();

                if ($isNew) {
                    Product::whereKey($product->id)->increment('cart_adds_count');
                    ProductStat::addCart($product->id);
                }

                if ($request->boolean('remove_from_favorites') && $favorite->delete()) {
                    Product::whereKey($product->id)
                        ->where('favorites_count', '>', 0)
                        ->decrement('favorites_count');
                }

                $added++;
            }

            return $added;
        });

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

        $userId = auth()->id();
        $quantity = DB::transaction(function () use ($item, $data, $userId): int {
            User::whereKey($userId)->lockForUpdate()->firstOrFail();

            $current = CartItem::whereKey($item->id)
                ->where('user_id', $userId)
                ->firstOrFail();
            $product = Product::whereKey($current->product_id)->first();

            if ($product && $data['qty'] > $product->stock) {
                throw ValidationException::withMessages([
                    'qty' => "Доступно только {$product->stock} шт. Возможно, часть товара уже купили другие пользователи.",
                ]);
            }

            $current->update(['qty' => $data['qty']]);

            return (int) $current->qty;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'qty' => $quantity,
            ]);
        }

        return back()->with('success', 'Количество обновлено');
    }

    public function remove(CartItem $item)
    {
        $this->authorize('delete', $item);

        $userId = auth()->id();
        DB::transaction(function () use ($item, $userId): void {
            User::whereKey($userId)->lockForUpdate()->firstOrFail();

            CartItem::whereKey($item->id)
                ->where('user_id', $userId)
                ->delete();
        });

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Товар удалён из корзины',
            ]);
        }

        return back()->with('success', 'Товар удалён из корзины');
    }
}
