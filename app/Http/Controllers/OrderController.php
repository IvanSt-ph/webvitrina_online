<?php

namespace App\Http\Controllers;

use App\Models\Order;

class OrderController extends Controller
{
    /** 📋 Список заказов */
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())
            ->latest()
            ->with(['items.product.category', 'items.product.city.country'])
            ->get();

        return view('shop.orders', compact('orders'));
    }

    /** 📄 Просмотр заказа */
    public function show(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $order->load([
            'items.product.category',
            'items.product.city.country',
            'address'
        ]);

        return view('shop.order-show', compact('order'));
    }
}
