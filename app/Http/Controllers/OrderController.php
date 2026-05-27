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
        $tab = in_array($request->get('tab'), ['active', 'action', 'completed', 'canceled'], true)
            ? $request->get('tab')
            : 'active';
        $search = trim((string) $request->get('q', ''));
        $buyerId = auth()->id();

        $query = Order::where('user_id', $buyerId)
            ->latest()
            ->with(['items.product.category', 'items.product.city.country', 'seller.shop'])
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhereHas('seller', fn ($seller) => $seller
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('shop', fn ($shop) => $shop->where('name', 'like', "%{$search}%")))
                    ->orWhereHas('items.product', fn ($product) => $product->where('title', 'like', "%{$search}%"));
            }));

        match ($tab) {
            'completed' => $query->where('status', Order::STATUS_COMPLETED),
            'canceled' => $query->where('status', Order::STATUS_CANCELED),
            'action' => $this->requireBuyerAction($query, $buyerId),
            default => $query->whereNotIn('status', [
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELED,
            ]),
        };

        $orders = $query->paginate(12)->withQueryString();

        $statusCounts = Order::where('user_id', $buyerId)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $actionCountQuery = Order::where('user_id', $buyerId);
        $this->requireBuyerAction($actionCountQuery, $buyerId);
        $actionCount = $actionCountQuery->count();

        return view('shop.orders', compact('orders', 'tab', 'statusCounts', 'actionCount', 'search'));
    }

    private function requireBuyerAction($query, int $buyerId): void
    {
        $query->where(function ($inner) use ($buyerId) {
            $inner->where('status', Order::STATUS_SHIPPED)
                ->orWhere(function ($reviewable) use ($buyerId) {
                    $reviewable
                        ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
                        ->whereHas('items.product', fn ($product) => $product->whereDoesntHave(
                            'reviews',
                            fn ($reviews) => $reviews->where('user_id', $buyerId)
                        ));
                });
        });
    }

    /** 📄 Просмотр заказа */
    public function show(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $order->load([
            'items.product.category',
            'items.product.city.country',
            'address',
            'seller.shop',
        ]);

        return view('shop.order-show', compact('order'));
    }
}
