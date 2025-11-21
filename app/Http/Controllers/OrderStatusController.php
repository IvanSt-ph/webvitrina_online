<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderStatusController extends Controller
{
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

    // проверяем валидность перехода
    if (
        !in_array($new, ['canceled', ...array_values($allowed)])
        || ($new !== 'canceled' && ($allowed[$order->status] ?? null) !== $new)
    ) {
        return back()->with('error', 'Недопустимый переход статуса.');
    }

    $order->update([
        'status' => $new,
    ]);

    return back()->with('success', 'Статус обновлён.');
}



    /* -------------------------------------------------
     | 🔥 Админ обновляет любой статус
     |--------------------------------------------------*/
    public function adminUpdate(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|string'
        ]);

        // Любой статус разрешён
        $order->setStatus($request->status);

        return back()->with('success', 'Статус заказа обновлён администратором.');
    }
}
