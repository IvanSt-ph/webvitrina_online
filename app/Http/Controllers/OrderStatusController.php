<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\AdminActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderStatusController extends Controller
{
    public function __construct(private readonly AdminActivityLogger $activity)
    {
    }

    /* -------------------------------------------------
     | 🔥 Покупатель подтверждает доставку
     |--------------------------------------------------*/
    public function confirmDelivery(Order $order)
    {
        // Проверяем, что это его заказ
        if ($order->user_id !== auth()->id()) {
            abort(403, 'Вы не можете изменить этот заказ.');
        }

        // Проверяем, что заказ уже доставлен раньше продавцом
        if (!in_array($order->status, [
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED
        ])) {
            return back()->with('error', 'Этот заказ ещё не был доставлен.');
        }

        // Устанавливаем delivered
        $order->setStatus(Order::STATUS_DELIVERED);

        return back()->with('success', 'Спасибо! Вы подтвердили получение заказа.');
    }

    public function requestCancellation(Request $request, Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        if (! in_array($order->status, [
            Order::STATUS_PENDING,
            Order::STATUS_PROCESSING,
            Order::STATUS_PAID,
        ], true)) {
            throw ValidationException::withMessages([
                'cancellation_reason' => 'Запрос отмены доступен только до отправки заказа.',
            ]);
        }

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:700'],
        ]);

        $order->update([
            'cancellation_requested_at' => now(),
            'cancellation_reason' => trim($data['cancellation_reason']),
        ]);

        return back()->with('success', 'Запрос на отмену отправлен продавцу.');
    }


    /* -------------------------------------------------
     | 🟣 Продавец обновляет статус
     |--------------------------------------------------*/
public function sellerUpdate(Request $request, Order $order)
{
    if ($order->seller_id !== auth()->id()) {
        abort(403);
    }

    $allowed = [
        'pending'    => 'processing',
        'processing' => 'paid',
        'paid'       => 'shipped',
        'shipped'    => 'delivered',
        'delivered'  => 'completed',
    ];

    $new = $request->status;
    $cancelableStatuses = [
        Order::STATUS_PENDING,
        Order::STATUS_PROCESSING,
        Order::STATUS_PAID,
    ];

    // проверяем валидность перехода
    if (
        !in_array($new, ['canceled', ...array_values($allowed)])
        || ($new === 'canceled' && ! in_array($order->status, $cancelableStatuses, true))
        || ($new !== 'canceled' && ($allowed[$order->status] ?? null) !== $new)
    ) {
        return back()->with('error', 'Недопустимый переход статуса.');
    }
    $order->setStatus($new);

    return back()->with('success', 'Статус обновлён.');
}



    /* -------------------------------------------------
     | 🔥 Админ обновляет любой статус
     |--------------------------------------------------*/
    public function adminUpdate(Request $request, Order $order)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', Order::allStatuses())],
            'change_reason' => ['nullable', 'string', 'max:700', 'required_if:status,' . Order::STATUS_CANCELED],
        ]);

        if ($order->status === $data['status']) {
            return back()->with('success', 'Статус заказа уже актуален.');
        }

        $previousStatus = $order->status;
        $order->setStatus($data['status']);

        $this->activity->log('order.status_updated', $order, 'Администратор изменил статус заказа.', [
            'from' => $previousStatus,
            'to' => $data['status'],
            'reason' => trim((string) ($data['change_reason'] ?? '')) ?: null,
        ]);

        return back()->with('success', 'Статус заказа обновлён администратором.');
    }
}



