<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    /**
     * ⚡ BUY NOW — купить конкретный товар
     * Сохраняем в сессию "виртуальную корзину" только с этим товаром.
     */
    public function quick(Product $product, Request $request)
    {
        // ❗ КРИТИЧЕСКАЯ ЗАЩИТА: запрет покупки своего товара
        if ($product->user_id === auth()->id()) {
return redirect()
    ->route('product.show', $product->slug ?? $product->id)  // ← правильно: product.show
    ->with('error', 'Вы не можете купить собственный товар.');
        }

        // Ищем, нет ли уже этого товара в обычной корзине
        $cartItem = CartItem::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();

        // Берём количество: либо из запроса, либо из корзины, либо 1
        $qty = $request->qty ?? ($cartItem?->qty ?? 1);

        // Кладём "корзину для оформления" в сессию
        session()->put('checkout_cart', [
            [
                'cart_id'    => $cartItem?->id,
                'product_id' => $product->id,
                'title'      => $product->title,
                'price'      => $product->price,
                'qty'        => $qty,
                'image'      => $product->image,
            ]
        ]);

        return redirect()->route('checkout.confirm');
    }

    /**
     * 📥 PREPARE — выбранные товары или вся корзина
     * Сюда попадаем из корзины (кнопка "Оформить заказ").
     */
    public function prepare(Request $request)
    {
        $query = CartItem::where('user_id', auth()->id())
            ->with('product');

        // Если были отмечены конкретные позиции — оформляем только их
        if ($request->filled('selected_items')) {
            $query->whereIn('id', $request->selected_items);
        }

        $items = $query->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'Нет выбранных товаров.');
        }

        // ❗ ЗАЩИТА: проверяем, нет ли своих товаров в корзине
        $userId = auth()->id();
        foreach ($items as $item) {
            if ($item->product->user_id === $userId) {
                return redirect()
                    ->route('cart.index')
                    ->with('error', 'В корзине есть ваш собственный товар. Пожалуйста, удалите его перед оформлением заказа.');
            }
        }

        // Приводим к простому массиву для хранения в сессии
        $cart = $items->map(fn ($i) => [
            'cart_id'    => $i->id,
            'product_id' => $i->product_id,
            'title'      => $i->product->title,
            'price'      => $i->product->price,
            'qty'        => $i->qty,
            'image'      => $i->product->image,
        ])->toArray();

        session()->put('checkout_cart', $cart);

        return redirect()->route('checkout.confirm');
    }

    /**
     * 📄 Страница подтверждения заказа
     * Показываем товары + список адресов пользователя.
     */
    public function confirm()
    {
        $cart = session('checkout_cart');

        if (!$cart || !count($cart)) {
            return redirect()->route('cart.index')
                ->with('error', 'Корзина пуста.');
        }

        // ❗ ДОПОЛНИТЕЛЬНАЯ ЗАЩИТА: проверяем товары в сессии
        $userId = auth()->id();
        $productIds = collect($cart)->pluck('product_id')->toArray();
        $products = Product::whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($cart as $item) {
            $product = $products[$item['product_id']] ?? null;
            if ($product && $product->user_id === $userId) {
                // Если нашли свой товар — очищаем сессию и отправляем назад
                session()->forget('checkout_cart');
                return redirect()
                    ->route('cart.index')
                    ->with('error', 'Обнаружена попытка купить свой товар. Действие отменено.');
            }
        }

        $total = collect($cart)->sum(fn ($i) => $i['price'] * $i['qty']);

        $user = auth()->user()->load('addresses');
        $addresses = $user->addresses;
        $defaultAddressId = $addresses->firstWhere('is_default', 1)?->id;

        // ✅ СПОСОБЫ ОПЛАТЫ
        $paymentMethods = [
            'cash' => '💵 Наличными при получении',
            'card' => '💳 Картой онлайн',
            'bank_transfer' => '🏦 Банковский перевод',
        ];

        // ✅ СПОСОБЫ ДОСТАВКИ
        $deliveryMethods = [
            'courier' => '🚚 Курьерская доставка (Доставка курьером 1-2 дня (до 10 кг) )',
            'pickup' => '🏪 Самовывоз из пункта выдачи (Магазин продавца)',
            'post' => '📮 Почта ПМР (Время доставки зависит от загруженности почтового отделения)',
            'express' => '⚡ Экспресс-доставка (Доставка по таксометру +50 руб до 15 кг)',
        ];

        // ✅ ЦЕНЫ ДОСТАВКИ (для расчета)
        $deliveryPrices = [
            'courier' => 155,
            'pickup' => 0,
            'post' => 80,
            'express' => 175,
        ];

        // ✅ Рассчитываем итог с доставкой по умолчанию
        $defaultDelivery = 'courier';
        $deliveryCost = $deliveryPrices[$defaultDelivery] ?? 0;
        $totalWithDelivery = $total + $deliveryCost;

        return view('shop.order-confirm', [
            'cart'                => $cart,
            'total'               => $total,
            'addresses'           => $addresses,
            'defaultAddressId'    => $defaultAddressId,
            'paymentMethods'      => $paymentMethods,
            'deliveryMethods'     => $deliveryMethods,
            'deliveryPrices'      => $deliveryPrices,
            'totalWithDelivery'   => $totalWithDelivery,
        ]);
    }

    /**
     * 🧾 Создание заказов
     *
     * ОДНА "виртуальная корзина" (из сессии) → несколько заказов:
     *  - по одному заказу на каждого продавца
     *  - каждому заказу привязываем address_id покупателя
     */
    public function create(Request $request)
    {
        $cart = session('checkout_cart');

        if (!$cart || !count($cart)) {
            return redirect()->route('cart.index')
                ->with('error', 'Корзина пуста.');
        }

        $user = auth()->user()->load('addresses');
        $userId = $user->id;
        
        // ❗ ФИНАЛЬНАЯ ЗАЩИТА: проверяем все товары перед созданием заказа
        $productIds = collect($cart)->pluck('product_id')->all();
        $products = Product::whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($cart as $item) {
            $product = $products[$item['product_id']] ?? null;
            if (!$product) {
                session()->forget('checkout_cart');
                return redirect()
                    ->route('cart.index')
                    ->with('error', 'Один из товаров не найден.');
            }
            
            if ($product->user_id === $userId) {
                session()->forget('checkout_cart');
                return redirect()
                    ->route('cart.index')
                    ->with('error', 'Нельзя оформить заказ на свой товар.');
            }
        }

        // Определяем address_id
        $addressId = null;
        if ($user->addresses->count()) {
            $requestedId = $request->input('address_id');
            if ($requestedId && $user->addresses->contains('id', $requestedId)) {
                $addressId = $requestedId;
            } else {
                $addressId = $user->addresses->firstWhere('is_default', 1)?->id
                    ?? $user->addresses->first()?->id;
            }
        }

        // ✅ Получаем способы оплаты и доставки из формы
        $paymentMethod = $request->input('payment_method', 'cash');
        $deliveryMethod = $request->input('delivery_method', 'courier');

        // ✅ ЦЕНЫ ДОСТАВКИ
        $deliveryPrices = [
            'courier' => 155,
            'pickup' => 0,
            'post' => 80,
            'express' => 175,
        ];
        
        $deliveryCost = $deliveryPrices[$deliveryMethod] ?? 0;

        // Добавляем seller_id к каждой позиции (уже проверили товары выше)
        $cartWithSellers = collect($cart)->map(function ($row) use ($products) {
            $product = $products[$row['product_id']];
            $row['seller_id'] = $product->user_id;
            return $row;
        });

        // Группируем корзину по продавцу
        $groups = $cartWithSellers->groupBy('seller_id');
        $createdOrders = [];

        DB::transaction(function () use ($groups, $addressId, $paymentMethod, $deliveryMethod, $deliveryCost, &$createdOrders, $userId) {
            foreach ($groups as $sellerId => $items) {
                $total = $items->sum(fn ($i) => $i['price'] * $i['qty']);
                
                // ✅ ИТОГ С УЧЕТОМ ДОСТАВКИ
                $totalWithDelivery = $total + $deliveryCost;

                // Создаём сам заказ
                $order = Order::create([
                    'user_id'         => $userId,
                    'seller_id'       => $sellerId,
                    'address_id'      => $addressId,
                    'payment_method'  => $paymentMethod,
                    'delivery_method' => $deliveryMethod,
                    'number'          => 'ORD-' . now()->timestamp . '-' . $sellerId,
                    'status'          => Order::STATUS_PENDING,
                    'total_price'     => $totalWithDelivery,
                    'currency'        => 'RUB',
                ]);

                // Позиции заказа
                foreach ($items as $i) {
                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $i['product_id'],
                        'quantity'   => $i['qty'],
                        'price'      => $i['price'],
                        'total'      => $i['price'] * $i['qty'],
                    ]);

                    // Удаляем исходную запись из корзины
                    if (!empty($i['cart_id'])) {
                        CartItem::destroy($i['cart_id']);
                    }
                }

                $createdOrders[] = $order;
            }
        });

        // Чистим "корзину для оформления"
        session()->forget('checkout_cart');

        // Если создан один заказ — ведём на его страницу
        if (count($createdOrders) === 1) {
            return redirect()->route('orders.show', $createdOrders[0])
                ->with('success', 'Заказ создан!');
        }

        // Если несколько — на список заказов
        return redirect()->route('orders.index')
            ->with('success', 'Создано заказов: ' . count($createdOrders));
    }
}