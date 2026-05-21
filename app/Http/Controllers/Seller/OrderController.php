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
        $status = in_array($request->get('status'), Order::allStatuses(), true)
            ? $request->get('status')
            : null;
        $search = trim((string) $request->get('q', ''));

        $query = Order::query()
            ->where('seller_id', auth()->id())
            ->with(['user', 'items.product'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        $statusCounts = Order::query()
            ->where('seller_id', auth()->id())
            ->select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('seller.orders.index', compact('orders', 'status', 'search', 'statusCounts'));
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
