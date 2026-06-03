<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderDispute;
use App\Services\AdminActivityLogger;
use App\Services\UserNotificationService;
use Illuminate\Http\Request;

class OrderDisputeController extends Controller
{
    public function __construct(private readonly AdminActivityLogger $activity)
    {
    }

    public function index(Request $request)
    {
        $status = $request->get('status', OrderDispute::STATUS_OPEN);
        $q = trim((string) $request->get('q', ''));

        $rawCounters = OrderDispute::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counters = [
            'all' => $rawCounters->sum(),
            OrderDispute::STATUS_OPEN => (int) ($rawCounters[OrderDispute::STATUS_OPEN] ?? 0),
            OrderDispute::STATUS_RESOLVED => (int) ($rawCounters[OrderDispute::STATUS_RESOLVED] ?? 0),
            OrderDispute::STATUS_CLOSED => (int) ($rawCounters[OrderDispute::STATUS_CLOSED] ?? 0),
        ];

        $disputes = OrderDispute::with(['order.items.product', 'user', 'seller', 'resolver'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('reason', 'like', "%{$q}%")
                        ->orWhere('details', 'like', "%{$q}%")
                        ->orWhereHas('order', fn ($order) => $order->where('number', 'like', "%{$q}%"))
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                        ->orWhereHas('seller', fn ($seller) => $seller->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"));
                });
            })
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [OrderDispute::STATUS_OPEN])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.disputes.index', compact('disputes', 'status', 'q', 'counters'));
    }

    public function resolve(Request $request, OrderDispute $dispute, UserNotificationService $notifications)
    {
        $data = $request->validate([
            'resolution' => ['required', 'string', 'max:1200'],
        ]);

        $dispute->update([
            'status' => OrderDispute::STATUS_RESOLVED,
            'resolution' => trim($data['resolution']),
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        $this->activity->log('order_dispute.resolved', $dispute, 'Спор по заказу решён администратором.', [
            'order_id' => $dispute->order_id,
        ]);

        $this->notifyParticipants($dispute, $notifications, 'Спор по заказу рассмотрен', $data['resolution']);

        return back()->with('success', 'Спор отмечен как решённый.');
    }

    public function close(Request $request, OrderDispute $dispute, UserNotificationService $notifications)
    {
        $data = $request->validate([
            'resolution' => ['required', 'string', 'max:1200'],
        ]);

        $dispute->update([
            'status' => OrderDispute::STATUS_CLOSED,
            'resolution' => trim($data['resolution']),
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        $this->activity->log('order_dispute.closed', $dispute, 'Спор по заказу закрыт администратором.', [
            'order_id' => $dispute->order_id,
        ]);

        $this->notifyParticipants($dispute, $notifications, 'Спор по заказу закрыт', $data['resolution']);

        return back()->with('success', 'Спор закрыт.');
    }

    private function notifyParticipants(OrderDispute $dispute, UserNotificationService $notifications, string $title, string $resolution): void
    {
        foreach ([$dispute->user, $dispute->seller] as $participant) {
            if (! $participant) {
                continue;
            }

            $notifications->create(
                $participant,
                'order_dispute_resolved',
                $title,
                "Заказ {$dispute->order?->number}: {$resolution}",
                $participant->isSeller()
                    ? route('seller.orders.show', $dispute->order, false)
                    : route('orders.show', $dispute->order, false),
                ['order_id' => $dispute->order_id, 'dispute_id' => $dispute->id]
            );
        }
    }
}
