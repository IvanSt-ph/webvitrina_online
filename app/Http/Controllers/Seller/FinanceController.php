<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class FinanceController extends Controller
{
    public function index()
    {
        $sellerId = Auth::id();
        $ordersQuery = Order::query()->where('seller_id', $sellerId);

        $completedTotal = (clone $ordersQuery)
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->sum('total_price');

        $inProgressTotal = (clone $ordersQuery)
            ->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_PROCESSING,
                Order::STATUS_PAID,
                Order::STATUS_SHIPPED,
            ])
            ->sum('total_price');

        $canceledTotal = (clone $ordersQuery)
            ->where('status', Order::STATUS_CANCELED)
            ->sum('total_price');

        $recentOrders = (clone $ordersQuery)
            ->with('user')
            ->latest()
            ->limit(8)
            ->get();

        $currency = $recentOrders->first()?->currency ?? 'RUB';

        return view('seller.finance.index', compact(
            'completedTotal',
            'inProgressTotal',
            'canceledTotal',
            'recentOrders',
            'currency'
        ));
    }
}
