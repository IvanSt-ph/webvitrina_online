<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDispute;
use App\Services\UserNotificationService;
use Illuminate\Http\Request;

class OrderDisputeController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $q = trim((string) $request->get('q', ''));

        if (! in_array($status, ['all', OrderDispute::STATUS_OPEN, OrderDispute::STATUS_RESOLVED, OrderDispute::STATUS_CLOSED], true)) {
            $status = 'all';
        }

        $baseQuery = OrderDispute::query()
            ->where('user_id', $request->user()->id);

        $rawCounters = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counters = [
            'all' => $rawCounters->sum(),
            OrderDispute::STATUS_OPEN => (int) ($rawCounters[OrderDispute::STATUS_OPEN] ?? 0),
            OrderDispute::STATUS_RESOLVED => (int) ($rawCounters[OrderDispute::STATUS_RESOLVED] ?? 0),
            OrderDispute::STATUS_CLOSED => (int) ($rawCounters[OrderDispute::STATUS_CLOSED] ?? 0),
        ];

        $disputes = $baseQuery
            ->with(['order.items.product', 'seller', 'resolver'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('reason', 'like', "%{$q}%")
                        ->orWhere('details', 'like', "%{$q}%")
                        ->orWhereHas('order', fn ($order) => $order->where('number', 'like', "%{$q}%"))
                        ->orWhereHas('seller', fn ($seller) => $seller
                            ->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%"));
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('buyer.disputes.index', compact('disputes', 'status', 'q', 'counters'));
    }

    public function store(Request $request, Order $order, UserNotificationService $notifications)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:120'],
            'details' => ['nullable', 'string', 'max:1200'],
        ]);

        $dispute = OrderDispute::firstOrCreate(
            [
                'order_id' => $order->id,
                'status' => OrderDispute::STATUS_OPEN,
            ],
            [
                'user_id' => $request->user()->id,
                'seller_id' => $order->seller_id,
                'reason' => trim($data['reason']),
                'details' => trim((string) ($data['details'] ?? '')),
            ]
        );

        if ($dispute->wasRecentlyCreated) {
            $notifications->create(
                $order->seller,
                'order_dispute_opened',
                'Открыт спор по заказу',
                "Покупатель открыл спор по заказу {$order->number}.",
                route('seller.orders.show', $order, false),
                ['order_id' => $order->id, 'dispute_id' => $dispute->id]
            );

            $admin = \App\Models\User::where('role', 'admin')->orderBy('id')->first();
            if ($admin) {
                $notifications->create(
                    $admin,
                    'order_dispute_opened',
                    'Новый спор по заказу',
                    "Нужно проверить заказ {$order->number}.",
                    route('admin.orders.show', $order, false),
                    ['order_id' => $order->id, 'dispute_id' => $dispute->id]
                );
            }
        }

        return back()->with('success', 'Спор открыт. Поддержка и продавец увидят обращение.');
    }
}
