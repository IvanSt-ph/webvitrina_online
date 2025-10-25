<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /** 📋 Список заказов */
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())
            ->latest()
            ->with(['items.product', 'address'])
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

        // 1) пробуем дефолтный адрес
        $address = UserAddress::where('user_id', $userId)->where('is_default', true)->first();

        // 2) если адреса нет — лучше отправить пользователя заполнить адреса
        if (!$address) {
            return redirect()
                ->route('addresses.index')
                ->with('warning', 'Добавьте адрес доставки, прежде чем оформлять заказ.');
        }

        return DB::transaction(function () use ($items, $userId, $address) {
            // 💰 сумма (DECIMAL, т.к. у тебя price уже decimal(10,2))
            $total = $items->sum(fn($i) => $i->qty * $i->product->price);

            // 🧾 номер заказа
            $nextId = (Order::max('id') ?? 0) + 1;
            $orderNumber = 'ORD-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            // 🧾 создаём заказ (и фиксируем "снимок" адреса в текстовом поле)
            $order = Order::create([
                'user_id'          => $userId,
                'address_id'       => $address->id,
                'number'           => $orderNumber,
                'total_price'      => $total,
                'currency'         => 'RUB',
                'status'           => 'pending',
                'delivery_address' => trim(
                    "{$address->country}, {$address->city}, {$address->street} {$address->house}" .
                    ($address->apartment ? ", кв. {$address->apartment}" : '') .
                    ($address->entrance ? ", подъезд {$address->entrance}" : '') .
                    ($address->postal_code ? " ({$address->postal_code})" : '')
                ),
            ]);

            // 📦 позиции заказа (ВАЖНО: quantity/total)
            foreach ($items as $i) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $i->product_id,
                    'price'      => $i->product->price,                 // decimal(10,2)
                    'quantity'   => $i->qty,                            // <-- ИМЕННО quantity
                    'total'      => $i->qty * $i->product->price,       // decimal(10,2)
                ]);
            }

            // 🧹 чистим корзину
            CartItem::where('user_id', $userId)->delete();

            return redirect()
                ->route('orders.index')
                ->with('success', '✅ Заказ успешно создан!');
        });
    }

    /** 📄 Просмотр заказа */
    public function show(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $order->load(['items.product', 'address']);

        return view('shop.order-show', compact('order'));
    }
}
