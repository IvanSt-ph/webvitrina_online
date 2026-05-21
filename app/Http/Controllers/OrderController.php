<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /** 📋 Список заказов */
    public function index(Request $request)
    {
        $tab = in_array($request->get('tab'), ['active', 'completed', 'canceled'], true)
            ? $request->get('tab')
            : 'active';

        $query = Order::where('user_id', auth()->id())
            ->latest()
            ->with(['items.product.category', 'items.product.city.country']);

        match ($tab) {
            'completed' => $query->where('status', Order::STATUS_COMPLETED),
            'canceled' => $query->where('status', Order::STATUS_CANCELED),
            default => $query->whereNotIn('status', [
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELED,
            ]),
        };

        $orders = $query->paginate(12)->withQueryString();

        $statusCounts = Order::where('user_id', auth()->id())
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('shop.orders', compact('orders', 'tab', 'statusCounts'));
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
