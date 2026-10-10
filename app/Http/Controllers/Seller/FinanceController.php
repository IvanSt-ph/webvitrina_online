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
            ->where(function ($orders) {
                $orders->where(function ($pickup) {
                    $pickup->where('workflow_version', Order::WORKFLOW_PICKUP)
                        ->where('status', Order::STATUS_COMPLETED);
                })->orWhere(function ($legacy) {
                    $legacy->whereNull('workflow_version')->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED]);
                });
            })
            ->sum('total_price');

        $inProgressTotal = (clone $ordersQuery)
            ->where(function ($orders) {
                $orders->where(function ($pickup) {
                    $pickup->where('workflow_version', Order::WORKFLOW_PICKUP)
                        ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PROCESSING,
                            Order::STATUS_READY_FOR_PICKUP, Order::STATUS_DELIVERED]);
                })->orWhere(function ($legacy) {
                    $legacy->whereNull('workflow_version')->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PROCESSING,
                        Order::STATUS_PAID, Order::STATUS_SHIPPED]);
                });
            })
            ->sum('total_price');

        $canceledTotal = (clone $ordersQuery)
            ->where('status', Order::STATUS_CANCELED)
            ->where(fn ($query) => $query->whereNull('workflow_version')->orWhere('workflow_version', Order::WORKFLOW_PICKUP))
            ->sum('total_price');

        $recentOrders = (clone $ordersQuery)
            ->where(fn ($query) => $query->whereNull('workflow_version')->orWhere('workflow_version', Order::WORKFLOW_PICKUP))
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
