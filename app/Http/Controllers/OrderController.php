<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /** 📋 Список заказов */
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())
            ->latest()
            ->with('items.product')
            ->get();

        return view('shop.orders', compact('orders'));
    }

    /** 💳 Оформление заказа */
    public function checkout()
    {
        $userId = auth()->id();

        $items = CartItem::with('product')
            ->where('user_id', $userId)
            ->get();

        abort_if($items->isEmpty(), 400, 'Корзина пуста');

        return DB::transaction(function () use ($items, $userId) {
            // 💰 Подсчёт общей суммы
            $total = $items->sum(fn($i) => $i->qty * $i->product->price);

            // 🧾 Генерация номера заказа
            $nextId = (Order::max('id') ?? 0) + 1;
            $orderNumber = 'ORD-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            // 🧾 Создаём заказ
            $order = Order::create([
                'user_id'      => $userId,
                'total_price'  => $total,
                'currency'     => 'RUB',
                'status'       => 'pending',
                'number'       => $orderNumber,
            ]);

            // 📦 Добавляем товары в заказ
            foreach ($items as $i) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $i->product_id,
                    'price'      => $i->product->price,
                    'quantity'   => $i->qty,
                    'total'      => $i->qty * $i->product->price,
                ]);
            }

            // 🧹 Очищаем корзину
            CartItem::where('user_id', $userId)->delete();

            return redirect()
                ->route('orders.index')
                ->with('success', '✅ Заказ успешно создан!');
        });
    }

    /** 📄 Просмотр конкретного заказа */
    public function show(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $order->load('items.product');

        return view('shop.order-show', compact('order'));
    }
}
