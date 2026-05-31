<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
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
        $action = in_array($request->get('action'), ['needs_action', 'cancel_request'], true)
            ? $request->get('action')
            : null;
        $search = trim((string) $request->get('q', ''));

        $query = Order::query()
            ->where('seller_id', auth()->id())
            ->with(['user', 'items.product'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        if ($action === 'cancel_request') {
            $query->whereNotNull('cancellation_requested_at')
                ->where('status', '!=', Order::STATUS_CANCELED);
        } elseif ($action === 'needs_action') {
            $query->where(function ($actionQuery) {
                $actionQuery->where('status', Order::STATUS_PENDING)
                    ->orWhere(function ($cancelQuery) {
                        $cancelQuery->whereNotNull('cancellation_requested_at')
                            ->where('status', '!=', Order::STATUS_CANCELED);
                    });
            });
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
        $actionCounts = [
            'needs_action' => Order::query()
                ->where('seller_id', auth()->id())
                ->where(function ($actionQuery) {
                    $actionQuery->where('status', Order::STATUS_PENDING)
                        ->orWhere(function ($cancelQuery) {
                            $cancelQuery->whereNotNull('cancellation_requested_at')
                                ->where('status', '!=', Order::STATUS_CANCELED);
                        });
                })
                ->count(),
            'cancel_request' => Order::query()
                ->where('seller_id', auth()->id())
                ->whereNotNull('cancellation_requested_at')
                ->where('status', '!=', Order::STATUS_CANCELED)
                ->count(),
        ];

        return view('seller.orders.index', compact('orders', 'status', 'action', 'search', 'statusCounts', 'actionCounts'));
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

    public function startBuyerConversation(Order $order)
    {
        abort_if($order->seller_id !== auth()->id(), 403);

        $order->loadMissing(['items.product']);
        $product = $order->items
            ->map(fn ($item) => $item->product)
            ->first(fn ($product) => $product !== null);

        if (! $product) {
            return back()->with('error', 'Нельзя открыть чат: товар в заказе не найден.');
        }

        $conversation = Conversation::firstOrCreate(
            [
                'buyer_id' => $order->user_id,
                'seller_id' => $order->seller_id,
                'product_id' => $product->id,
                'order_id' => $order->id,
                'context_key' => Conversation::orderProductContextKey($order, $product),
            ],
            [
                'conversation_type' => Conversation::TYPE_MARKETPLACE,
                'last_message_at' => now(),
            ]
        );

        $conversation->forceFill([
            'buyer_deleted_at' => null,
            'seller_deleted_at' => null,
        ])->save();

        $contextBody = "Диалог по заказу {$order->number}.\nТовар: {$product->title}";

        if (! $conversation->messages()->where('type', Message::TYPE_SYSTEM)->where('body', $contextBody)->exists()) {
            $conversation->messages()->create([
                'sender_id' => auth()->id(),
                'type' => Message::TYPE_SYSTEM,
                'order_id' => $order->id,
                'body' => $contextBody,
            ]);
            $conversation->update(['last_message_at' => now()]);
        }

        return redirect()
            ->route('chats.show', $conversation)
            ->with('success', 'Чат с покупателем открыт. Контекст заказа уже добавлен.');
    }
}
