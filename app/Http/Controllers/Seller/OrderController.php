<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Список заказов текущего продавца
     */
    public function index(Request $request)
    {
        $query = Order::query()
            ->where('seller_id', auth()->id())
            ->with(['user', 'items.product'])
            ->latest();

        // фильтр по статусу ?status=pending
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $orders = $query->paginate(20);

        return view('seller.orders.index', compact('orders'));
    }

    /**
     * Детали заказа продавца
     */
    public function show(Order $order)
    {
        abort_if($order->seller_id !== auth()->id(), 403);

        $order->loadMissing(['user', 'items.product', 'address']);

        return view('seller.orders.show', compact('order'));
    }
}
