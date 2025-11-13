<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /**
     * ⚡ BUY NOW — купить конкретный товар
     */
    public function quick(Product $product, Request $request)
    {
        // если товар есть в корзине — берём cart_id
        $cartItem = CartItem::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();

        $qty = $request->qty ?? ($cartItem?->qty ?? 1);

        session()->put('checkout_cart', [
            [
                'cart_id'    => $cartItem?->id,
                'product_id' => $product->id,
                'title'      => $product->title,
                'price'      => $product->price,
                'qty'        => $qty,
                'image'      => $product->image,
            ]
        ]);

        return redirect()->route('checkout.confirm');
    }



    /**
     * 📥 PREPARE — выбранные товары или вся корзина
     */
    public function prepare(Request $request)
    {
        $query = CartItem::where('user_id', auth()->id())->with('product');

        if ($request->filled('selected_items')) {
            $query->whereIn('id', $request->selected_items);
        }

        $items = $query->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'Нет выбранных товаров.');
        }

        $cart = $items->map(fn($i) => [
            'cart_id'    => $i->id,
            'product_id' => $i->product_id,
            'title'      => $i->product->title,
            'price'      => $i->product->price,
            'qty'        => $i->qty,
            'image'      => $i->product->image,
        ])->toArray();

        // сохраняем в сессии корзину для оформления
        session()->put('checkout_cart', $cart);

        return redirect()->route('checkout.confirm');
    }



    /**
     * 📄 Страница подтверждения заказа
     */
    public function confirm()
    {
        $cart = session('checkout_cart');

        if (!$cart) {
            return redirect()->route('cart.index')
                ->with('error', 'Корзина пуста.');
        }

        $total = collect($cart)->sum(fn($i) => $i['price'] * $i['qty']);

        return view('shop.order-confirm', compact('cart', 'total'));
    }



    /**
     * 🧾 Создание заказа
     */
    public function create(Request $request)
    {
        $cart = session('checkout_cart');

        if (!$cart) {
            return redirect()->route('cart.index')
                ->with('error', 'Корзина пуста.');
        }

        $total = collect($cart)->sum(fn($i) => $i['price'] * $i['qty']);

        $order = Order::create([
            'user_id'     => auth()->id(),
            'number'      => 'ORD-' . time(),
            'status'      => 'pending',
            'total_price' => $total,
        ]);

        foreach ($cart as $i) {
            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $i['product_id'],
                'quantity'   => $i['qty'],
                'price'      => $i['price'],
                'total'      => $i['price'] * $i['qty'],
            ]);

            // удаляем из корзины только если там был cart_id
            if (!empty($i['cart_id'])) {
                CartItem::destroy($i['cart_id']);
            }
        }

        session()->forget('checkout_cart');

        return redirect()->route('orders.show', $order)
            ->with('success', 'Заказ создан!');
    }
}
