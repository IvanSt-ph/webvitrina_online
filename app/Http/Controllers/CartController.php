<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CartController extends Controller
{
    public function index()
    {
       $items = CartItem::with([
        'product.category',
        'product.city.country',
        'product.seller',
    ])
    ->where('user_id', auth()->id())
    ->get();


        // считаем общую сумму
        $total = 0;
        foreach ($items as $item) {
            if ($item->product) {
                $total += $item->product->price * $item->qty;
            }
        }

        return view('shop.cart', compact('items', 'total'));
    }

   public function add(Product $product, Request $request)
{
    $request->validate([
        'qty' => ['nullable', 'integer', 'min:1', 'max:999'],
    ]);

    $qty = (int)($request->input('qty', 1));

    $item = CartItem::firstOrNew([
        'user_id'    => auth()->id(),
        'product_id' => $product->id,
    ]);

    $today = Carbon::today()->toDateString();

    // если пользователь впервые добавляет этот товар в корзину
    if (! $item->exists) {
        $product->increment('cart_adds_count');

        ProductStat::updateOrCreate(
            ['product_id' => $product->id, 'date' => $today],
            ['carts' => DB::raw('carts + 1')]
        );
    }

    $item->qty = max(1, (int)$item->qty + $qty);
    $item->save();

    // ✅ Проверяем, хочет ли клиент JSON
    if ($request->wantsJson()) {
        return response()->json([
            'success' => true,
            'quantity' => $item->qty,
            'message' => 'Товар добавлен в корзину'
        ]);
    }

    // 🔥 Добавляем ID товара для визуальных эффектов
    return back()
        ->with('success', 'Товар добавлен в корзину!')
        ->with('cart_added_id', $product->id);
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