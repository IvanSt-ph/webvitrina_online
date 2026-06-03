<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\Conversation;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:' . implode(',', Order::allStatuses())],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'sort' => ['nullable', 'in:latest,oldest,amount_desc,amount_asc'],
            'focus' => ['nullable', 'in:active,cancel_requests,stuck,attention'],
        ]);

        $statusCounts = Order::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $query = Order::with(['user', 'seller.shop', 'items.product']);

        if ($request->filled('status') && in_array($request->string('status')->toString(), Order::allStatuses(), true)) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));

            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhereHas('items.product', function ($productQuery) use ($search) {
                        $productQuery->where('title', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('seller', function ($sellerQuery) use ($search) {
                        $sellerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });

                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        if ($request->filled('focus')) {
            match ($request->string('focus')->toString()) {
                'active' => $query->whereIn('status', [
                    Order::STATUS_PENDING,
                    Order::STATUS_PROCESSING,
                    Order::STATUS_PAID,
                    Order::STATUS_SHIPPED,
                    Order::STATUS_DELIVERED,
                ]),
                'cancel_requests' => $query->whereNotNull('cancellation_requested_at')
                    ->whereNotIn('status', [Order::STATUS_CANCELED, Order::STATUS_COMPLETED]),
                'stuck' => $this->applyStuckOrderFilter($query),
                'attention' => $this->applyAttentionOrderFilter($query),
                default => null,
            };
        }

        $filtered = clone $query;

        $summary = [
            'total' => (clone $filtered)->count(),
            'revenue' => (clone $filtered)
                ->where('status', '!=', Order::STATUS_CANCELED)
                ->sum('total_price'),
            'active' => (clone $filtered)
                ->whereIn('status', [
                    Order::STATUS_PENDING,
                    Order::STATUS_PROCESSING,
                    Order::STATUS_PAID,
                    Order::STATUS_SHIPPED,
                    Order::STATUS_DELIVERED,
                ])
                ->count(),
            'cancel_requests' => (clone $filtered)
                ->whereNotNull('cancellation_requested_at')
                ->whereNotIn('status', [Order::STATUS_CANCELED, Order::STATUS_COMPLETED])
                ->count(),
            'stuck' => tap(clone $filtered, fn ($query) => $this->applyStuckOrderFilter($query))->count(),
            'attention' => tap(clone $filtered, fn ($query) => $this->applyAttentionOrderFilter($query))->count(),
            'today' => Order::query()
                ->whereDate('created_at', today())
                ->count(),
        ];

        $sort = $request->input('sort', 'latest');
        match ($sort) {
            'oldest' => $query->oldest(),
            'amount_desc' => $query->orderByDesc('total_price'),
            'amount_asc' => $query->orderBy('total_price'),
            default => $query->latest(),
        };

        $orders = $query->paginate(12)->withQueryString();

        return view('admin.orders.index', compact('orders', 'statusCounts', 'summary', 'sort'));
    }

    private function applyStuckOrderFilter($query): void
    {
        $query->where(function ($stuck) {
            $stuck->where(function ($pending) {
                $pending->where('status', Order::STATUS_PENDING)
                    ->where('created_at', '<=', now()->subDay());
            })->orWhere(function ($processing) {
                $processing->where('status', Order::STATUS_PROCESSING)
                    ->where(function ($dates) {
                        $dates->where('accepted_at', '<=', now()->subDays(2))
                            ->orWhere(function ($fallback) {
                                $fallback->whereNull('accepted_at')
                                    ->where('created_at', '<=', now()->subDays(2));
                            });
                    });
            });
        });
    }

    private function applyAttentionOrderFilter($query): void
    {
        $query->where(function ($attention) {
            $attention->where(function ($cancel) {
                $cancel->whereNotNull('cancellation_requested_at')
                    ->whereNotIn('status', [Order::STATUS_CANCELED, Order::STATUS_COMPLETED]);
            })->orWhere(function ($stuck) {
                $this->applyStuckOrderFilter($stuck);
            });
        });
    }

    public function show(Order $order)
    {
        $order->load(['user', 'seller.shop', 'address', 'items.product']);

        $productIds = $order->items->pluck('product_id')->filter()->values();

        $marketplaceConversations = Conversation::query()
            ->with(['product', 'lastMessage'])
            ->where('conversation_type', Conversation::TYPE_MARKETPLACE)
            ->where('buyer_id', $order->user_id)
            ->where('seller_id', $order->seller_id)
            ->when($productIds->isNotEmpty(), fn ($query) => $query->where(function ($sub) use ($productIds) {
                $sub->whereIn('product_id', $productIds)->orWhereNull('product_id');
            }))
            ->latest('last_message_at')
            ->limit(6)
            ->get();

        $supportConversations = Conversation::query()
            ->with(['buyer', 'lastMessage'])
            ->where('conversation_type', Conversation::TYPE_SUPPORT)
            ->whereIn('buyer_id', [$order->user_id, $order->seller_id])
            ->latest('last_message_at')
            ->limit(6)
            ->get();

        $activity = AdminActivityLog::query()
            ->with('admin')
            ->where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->latest()
            ->limit(15)
            ->get();

        return view('admin.orders.show', compact(
            'order',
            'marketplaceConversations',
            'supportConversations',
            'activity'
        ));
    }
}
