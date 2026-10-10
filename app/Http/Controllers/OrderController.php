<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /** 📋 Список заказов */
    public function index(Request $request)
    {
        $tab = in_array($request->get('tab'), ['active', 'action', 'completed', 'canceled', 'unsupported'], true)
            ? $request->get('tab')
            : 'active';
        $search = trim((string) $request->get('q', ''));
        $buyerId = auth()->id();

        $query = Order::where('user_id', $buyerId)
            ->latest()
            ->with(['items.product.category', 'items.product.city.country', 'seller.shop'])
            ->when($search !== '', fn ($query) => $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('seller_snapshot->name', 'like', "%{$search}%")
                    ->orWhere('seller_snapshot->shop_name', 'like', "%{$search}%")
                    ->orWhereHas('items', fn ($item) => $item->where('product_title', 'like', "%{$search}%")
                        ->orWhere('product_sku', 'like', "%{$search}%"));
            }));

        match ($tab) {
            'completed' => $query->where('status', Order::STATUS_COMPLETED)
                ->where(fn ($supported) => $supported->whereNull('workflow_version')->orWhere('workflow_version', Order::WORKFLOW_PICKUP)),
            'canceled' => $query->where('status', Order::STATUS_CANCELED)
                ->where(fn ($supported) => $supported->whereNull('workflow_version')->orWhere('workflow_version', Order::WORKFLOW_PICKUP)),
            'unsupported' => $query->whereNotNull('workflow_version')->where('workflow_version', '!=', Order::WORKFLOW_PICKUP),
            'action' => $this->requireBuyerAction($query, $buyerId, (bool) auth()->user()?->hasVerifiedEmail()),
            default => $query->where(fn ($supported) => $supported->whereNull('workflow_version')->orWhere('workflow_version', Order::WORKFLOW_PICKUP))
                ->whereNotIn('status', [
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELED,
            ]),
        };

        $orders = $query->paginate(12)->withQueryString();

        $statusCounts = Order::where('user_id', $buyerId)
            ->where(fn ($supported) => $supported->whereNull('workflow_version')->orWhere('workflow_version', Order::WORKFLOW_PICKUP))
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $actionCountQuery = Order::where('user_id', $buyerId);
        $this->requireBuyerAction($actionCountQuery, $buyerId, (bool) auth()->user()?->hasVerifiedEmail());
        $actionCount = $actionCountQuery->count();
        $unsupportedCount = Order::where('user_id', $buyerId)
            ->whereNotNull('workflow_version')->where('workflow_version', '!=', Order::WORKFLOW_PICKUP)->count();

        return view('shop.orders', compact('orders', 'tab', 'statusCounts', 'actionCount', 'unsupportedCount', 'search'));
    }

    private function requireBuyerAction($query, int $buyerId, bool $verified): void
    {
        $query->where(function ($inner) use ($buyerId, $verified) {
            $inner->where(function ($legacy) {
                $legacy->whereNull('workflow_version')->where('status', Order::STATUS_SHIPPED);
            });
            if ($verified) {
                $inner->orWhere(function ($pickup) {
                    $pickup->where('workflow_version', Order::WORKFLOW_PICKUP)
                        ->where('status', Order::STATUS_READY_FOR_PICKUP)
                        ->where('delivery_method', 'pickup')
                        ->whereIn('payment_method', ['cash', 'card'])
                        ->whereNotNull('ready_for_pickup_at')
                        ->whereNull('buyer_confirmed_at')
                        ->whereIn('payment_status', [Order::PAYMENT_UNPAID, Order::PAYMENT_SELLER_CONFIRMED]);
                });
            }
            $inner
                ->orWhere(function ($reviewable) use ($buyerId) {
                    $reviewable
                        ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
                        ->where(function ($receipt) {
                            $receipt->whereNull('workflow_version')
                                ->orWhere(function ($pickup) {
                                    $pickup->where('workflow_version', Order::WORKFLOW_PICKUP)
                                        ->whereNotNull('buyer_confirmed_at');
                                });
                        })
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
            'seller.shop',
        ]);

        $categoryIds = $order->items
            ->pluck('product.category_id')
            ->filter()
            ->unique()
            ->values();
        $productIds = $order->items
            ->pluck('product_id')
            ->filter()
            ->values();

        $continueProducts = Product::query()
            ->where('status', 'active')
            ->where('stock', '>', 0)
            ->whereNotIn('id', $productIds)
            ->when($categoryIds->isNotEmpty(), fn ($query) => $query->whereIn('category_id', $categoryIds))
            ->latest()
            ->limit(4)
            ->get();

        return view('shop.order-show', compact('order', 'continueProducts'));
    }
}
