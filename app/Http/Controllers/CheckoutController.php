<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    /** 🧾 Обычное оформление — вся корзина */
    public function index(Request $request)
    {
        $cart = session('cart', []);
        $quick = session('quick_checkout');

        // Если оформляем только один товар ("Купить сейчас")
        if ($quick) {
            $cart = [$quick];
            session()->forget('quick_checkout');
        }

        return view('shop.checkout', compact('cart'));
    }

    /** ⚡ Купить сейчас — только один товар */
    public function quick(Product $product)
    {
        if (!$product->price) {
            return back()->with('error', 'Этот товар недоступен для покупки.');
        }

        session(['quick_checkout' => [
            'id' => $product->id,
            'title' => $product->title,
            'price' => $product->price,
            'image' => $product->image,
            'quantity' => 1,
        ]]);

        return redirect()->route('checkout.index');
    }
}
