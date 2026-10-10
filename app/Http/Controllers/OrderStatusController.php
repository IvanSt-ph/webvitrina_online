<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderPickupWorkflow;
use Illuminate\Http\Request;

class OrderStatusController extends Controller
{
    public function __construct(private readonly OrderPickupWorkflow $workflow)
    {
    }

    public function confirmDelivery(Order $order)
    {
        $this->workflow->buyerConfirmReceipt($order, auth()->user());

        return back()->with('success', 'Спасибо! Вы подтвердили получение заказа.');
    }

    public function requestCancellation(Request $request, Order $order)
    {
        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:700'],
        ]);
        $this->workflow->buyerRequestCancellation($order, $request->user(), trim($data['cancellation_reason']));

        return back()->with('success', 'Запрос на отмену отправлен продавцу.');
    }

    public function sellerUpdate(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'max:32'],
            'cancellation_reason' => ['nullable', 'string', 'max:700'],
        ]);
        $this->workflow->sellerUpdate($order, $request->user(), $data['status'], $data['cancellation_reason'] ?? null);

        return back()->with('success', 'Статус обновлён.');
    }

    public function sellerConfirmPayment(Request $request, Order $order)
    {
        $this->workflow->sellerConfirmPayment($order, $request->user());

        return back()->with('success', 'Оплата подтверждена продавцом.');
    }

    public function sellerRequestReceiptConfirmation(Request $request, Order $order)
    {
        $sent = $this->workflow->sellerRequestReceiptConfirmation($order, $request->user());

        return back()->with('success', $sent
            ? 'Напоминание создано для покупателя.'
            : 'Повторное напоминание пока недоступно; новое уведомление не создавалось.');
    }

    public function sellerRejectCancellation(Request $request, Order $order)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:700'],
        ]);
        $this->workflow->sellerRejectCancellation($order, $request->user(), trim($data['reason']));

        return back()->with('success', 'Запрос на отмену отклонён.');
    }

    public function adminUpdate(Request $request, Order $order)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', Order::allStatuses())],
            'change_reason' => ['nullable', 'string', 'max:700', 'required_if:status,' . Order::STATUS_CANCELED],
        ]);
        $this->workflow->adminUpdate($order, $request->user(), $data['status'], $data['change_reason'] ?? null);

        return back()->with('success', 'Статус заказа обновлён администратором.');
    }
}
