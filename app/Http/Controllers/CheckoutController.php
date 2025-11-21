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

        // Если корзина в сессии пуста — возвращаем в обычную корзину
        if (!$cart || !count($cart)) {
            return redirect()->route('cart.index')
                ->with('error', 'Корзина пуста.');
        }

        $total = collect($cart)->sum(fn ($i) => $i['price'] * $i['qty']);

        // Подгружаем адреса пользователя одним запросом
        $user = auth()->user()->load('addresses');

        $addresses = $user->addresses;

        // Ищем основной адрес (is_default = 1)
        $defaultAddressId = $addresses->firstWhere('is_default', 1)?->id;

        return view('shop.order-confirm', [
            'cart'             => $cart,
            'total'            => $total,
            'addresses'        => $addresses,
            'defaultAddressId' => $defaultAddressId,
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

        // 💡 Определяем address_id:
        //  1) Если передали из формы — проверяем, что этот адрес принадлежит пользователю
        //  2) Если нет — берём основной (is_default = 1)
        //  3) Если основного нет — берём первый попавшийся адрес
        $addressId = null;

        if ($user->addresses->count()) {
            $requestedId = $request->input('address_id');

            if ($requestedId && $user->addresses->contains('id', $requestedId)) {
                $addressId = $requestedId;
            } else {
                $addressId =
                    $user->addresses->firstWhere('is_default', 1)?->id
                    ?? $user->addresses->first()?->id;
            }
        }

        // Грузим товары одним запросом, чтобы знать seller_id
        $productIds = collect($cart)->pluck('product_id')->all();

        $products = Product::whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        // Добавляем seller_id к каждой позиции
        $cartWithSellers = collect($cart)->map(function ($row) use ($products) {
            $product = $products[$row['product_id']] ?? null;

            if (!$product) {
                throw new \RuntimeException("Товар ID {$row['product_id']} не найден.");
            }

            $row['seller_id'] = $product->user_id;

            return $row;
        });

        // Группируем корзину по продавцу
        $groups = $cartWithSellers->groupBy('seller_id');

        $createdOrders = [];

        DB::transaction(function () use ($groups, $addressId, &$createdOrders) {
            foreach ($groups as $sellerId => $items) {
                $total = $items->sum(fn ($i) => $i['price'] * $i['qty']);

                // Создаём сам заказ
                $order = Order::create([
                    'user_id'     => auth()->id(),
                    'seller_id'   => $sellerId,
                    'address_id'  => $addressId, // ← вот здесь привязываем адрес!
                    'number'      => 'ORD-' . now()->timestamp . '-' . $sellerId,
                    'status'      => Order::STATUS_PENDING,
                    'total_price' => $total,
                    'currency'    => 'RUB', // TODO: подвязать под выбранную валюту
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
