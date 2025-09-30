<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
public function index()
{
    $items = CartItem::with('product')
        ->where('user_id', auth()->id())
        ->get();

    // считаем общую сумму
    $total = 0;
    foreach ($items as $item) {
        if ($item->product) {
            // если цена хранится в копейках (int)
            $total += $item->product->price * $item->qty;
        }
    }

    return view('shop.cart', compact('items', 'total'));
}

    public function add(Product $product, Request $request)
    {
        $request->validate([
            'qty' => ['nullable','integer','min:1','max:999'],
        ]);

        $qty = (int)($request->input('qty', 1));

        $item = CartItem::firstOrNew([
            'user_id'    => auth()->id(),
            'product_id' => $product->id,
        ]);

        $item->qty = max(1, (int)$item->qty + $qty);
        $item->save();

        return back()->with('success', 'Товар добавлен в корзину');
    }

    public function update(CartItem $item, Request $request)
    {
        // только владелец может менять свою строку корзины
        $this->authorize('update', $item);

        $data = $request->validate([
            'qty' => ['required','integer','min:1','max:999'],
        ]);

        $item->update(['qty' => $data['qty']]);

        return back()->with('success', 'Количество обновлено');
    }

    public function remove(CartItem $item)
    {
        // только владелец может удалять
        $this->authorize('delete', $item);

        $item->delete();

        return back()->with('success', 'Товар удалён из корзины');
    }
}
