<x-buyer-layout title="Подтверждение заказа">

<div class="checkout-confirm-safe w-full max-w-none overflow-x-hidden px-3 py-4 pb-[5.5rem] sm:px-6 sm:py-8 sm:pb-8 space-y-5 sm:space-y-8">

    <!-- 🔙 Назад в корзину -->
    <a href="{{ route('cart.index') }}"
       class="inline-flex max-w-full items-center gap-1 text-sm text-gray-600 hover:text-indigo-600">
        <i class="ri-arrow-left-line"></i> Вернуться в корзину
    </a>

    <!-- 🧾 Заголовок -->
    <div>
        <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">Подтверждение заказа</h1>
        <p class="text-gray-500 text-sm mt-1">
            Проверьте товары перед оформлением. Для каждого магазина будет создан отдельный заказ.
        </p>
    </div>

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            {{ $errors->first() }}
        </div>
    @endif

    @if($pricesUpdated)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Цена одного или нескольких товаров изменилась. Ниже показана актуальная сумма; подтвердите заказ с новой ценой.
        </div>
    @endif

    <!-- 📦 Заказы по магазинам -->
    <div class="space-y-4">
        @foreach($orderGroups as $group)
            <section class="w-full max-w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm sm:rounded-2xl">
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 bg-slate-50 px-4 py-3 sm:px-5">
                    <div class="min-w-0 text-sm font-semibold text-gray-900">
                        <i class="ri-store-2-line mr-1 text-indigo-500"></i>
                        <span class="break-words">{{ $group['seller_name'] }}</span>
                    </div>
                    <span class="shrink-0 text-xs text-gray-500">Отдельный заказ</span>
                </div>
                <div class="divide-y">
                    @foreach($group['items'] as $item)
                        @php
                            $itemTitle = $item['title'] ?? 'Товар';
                            $shortItemTitle = Str::limit($itemTitle, 18);
                        @endphp
                        <div class="grid min-w-0 grid-cols-[64px_minmax(0,1fr)] gap-3 p-3 sm:flex sm:items-center sm:gap-4 sm:p-5">
                            <img src="{{ asset('storage/'.$item['image']) }}"
                                 class="h-16 w-16 rounded-xl border object-cover sm:h-20 sm:w-20"
                                 alt="{{ $itemTitle }}">

                            <div class="min-w-0 sm:flex-1">
                                <p class="text-gray-900 font-medium text-sm sm:text-base line-clamp-2" style="overflow-wrap: anywhere; word-break: break-word;">
                                    <span class="sm:hidden">{{ $shortItemTitle }}</span>
                                    <span class="hidden sm:inline">{{ $itemTitle }}</span>
                                </p>
                                <p class="text-gray-500 text-xs sm:text-sm mt-1">
                                    Кол-во: <span class="text-gray-800 font-semibold">{{ $item['qty'] }}</span>
                                </p>
                            </div>

                            <div class="col-span-2 min-w-0 rounded-xl bg-slate-50 px-3 py-2 text-left sm:col-span-1 sm:min-w-[110px] sm:bg-transparent sm:px-0 sm:py-0 sm:text-right">
                                <div class="text-gray-900 font-semibold text-base sm:text-lg">
                                    {{ number_format($item['price'] * $item['qty'], 2, ',', ' ') }} ₽
                                </div>
                                <div class="text-xs text-gray-400">
                                    {{ number_format($item['price'], 2, ',', ' ') }} ₽ / шт
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-between gap-3 border-t border-gray-100 px-4 py-3 text-sm sm:px-5">
                    <span class="text-gray-500">Товары магазина:</span>
                    <span class="font-semibold text-gray-900">{{ number_format($group['subtotal'], 2, ',', ' ') }} ₽</span>
                </div>
            </section>
        @endforeach
    </div>

    @if($orderCount > 1)
        <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
            Будет создано заказов: <strong>{{ $orderCount }}</strong>. Стоимость выбранной доставки начисляется для каждого магазина отдельно.
        </div>
    @endif

    <!-- 📝 Форма оформления заказа -->
    <form action="{{ route('checkout.create') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">

<!-- 📍 Доставка и оплата (теперь внутри формы!) -->
<div class="grid min-w-0 gap-4 sm:grid-cols-2 sm:gap-6">
    <!-- 🚚 Выбор способа доставки -->
    <div class="min-w-0 bg-white border rounded-xl sm:rounded-2xl shadow-sm p-4 sm:p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Способ доставки</h3>
        
        @if(isset($deliveryMethods) && count($deliveryMethods))
            <div class="space-y-2">
                @foreach($deliveryMethods as $key => $label)
                    <label class="flex min-w-0 items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                        <input type="radio" 
                               name="delivery_method" 
                               value="{{ $key }}"
                               class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600"
                               {{ $loop->first ? 'checked' : '' }}
                               required>
                        <span class="min-w-0 text-sm text-gray-800 leading-snug" style="overflow-wrap: anywhere;">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        @else
            <div class="space-y-2">
                <label class="flex min-w-0 items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="delivery_method" value="courier" class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600" checked required>
                    <span class="min-w-0 text-gray-800 text-sm">🚚 Курьерская доставка</span>
                </label>
                <label class="flex min-w-0 items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="delivery_method" value="pickup" class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600">
                    <span class="min-w-0 text-gray-800 text-sm">🏪 Самовывоз</span>
                </label>
                <label class="flex min-w-0 items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="delivery_method" value="post" class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600">
                    <span class="min-w-0 text-gray-800 text-sm">📮 Почта России</span>
                </label>
            </div>
        @endif
        
        <p class="text-xs text-gray-500 mt-3">
            Доставка начисляется отдельно для каждого заказа продавцу.
        </p>
    </div>

    <!-- 💳 Выбор способа оплаты -->
    <div class="min-w-0 bg-white border rounded-xl sm:rounded-2xl shadow-sm p-4 sm:p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Способ оплаты</h3>
        
        @if(isset($paymentMethods) && count($paymentMethods))
            <div class="space-y-2">
                @foreach($paymentMethods as $key => $label)
                    <label class="flex min-w-0 items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                        <input type="radio" 
                               name="payment_method" 
                               value="{{ $key }}"
                               class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600"
                               {{ $loop->first ? 'checked' : '' }}
                               required>
                        <span class="min-w-0 text-sm text-gray-800 leading-snug" style="overflow-wrap: anywhere;">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        @else
            <div class="space-y-2">
                <label class="flex min-w-0 items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="payment_method" value="cash" class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600" checked required>
                    <span class="min-w-0 text-gray-800 text-sm">💵 Наличными при получении</span>
                </label>
                <label class="flex min-w-0 items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="payment_method" value="card" class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600">
                    <span class="min-w-0 text-gray-800 text-sm">💳 Картой при получении (онлайн-оплата пока недоступна)</span>
                </label>
                <label class="flex min-w-0 items-start gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="payment_method" value="bank_transfer" class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600">
                    <span class="min-w-0 text-gray-800 text-sm">🏦 Перевод по согласованию с продавцом</span>
                </label>
            </div>
        @endif
        
        <p class="text-xs text-gray-500 mt-3">
            Онлайн-платёж на сайте пока не выполняется. Условия оплаты фиксируются для связи с продавцом.
        </p>
    </div>
</div>

<!-- 💰 Итоги -->
<div class="min-w-0 bg-white rounded-xl sm:rounded-2xl border border-gray-200 shadow-sm p-4 sm:p-6 space-y-4">
    <div class="flex min-w-0 justify-between gap-3 text-gray-700 text-sm">
<span>
    Товаров:
    {{ collect($cart)->sum('qty') }}
</span>

        <span id="subtotal" class="shrink-0">{{ number_format($total, 2, ',', ' ') }} ₽</span>
    </div>

    <div class="flex min-w-0 justify-between gap-3 text-gray-700 text-sm">
        <span>Доставка ({{ $orderCount }} {{ $orderCount === 1 ? 'заказ' : 'заказа' }}):</span>
        <span id="delivery-cost" class="shrink-0 font-medium">
            @if($totalDeliveryCost > 0)
                {{ number_format($totalDeliveryCost, 2, ',', ' ') }} ₽
            @else
                Бесплатно
            @endif
        </span>
    </div>

    <hr class="border-gray-200">

    <div class="flex min-w-0 items-start justify-between gap-3">
        <span class="min-w-0 text-base sm:text-lg font-semibold text-gray-900">Итого к оплате</span>
        <span id="total-with-delivery" class="shrink-0 text-xl sm:text-2xl font-bold text-gray-900">
            {{ number_format($totalWithDelivery, 2, ',', ' ') }} ₽
        </span>
    </div>
</div>

<!-- 📍 Выбор адреса -->
        <div class="min-w-0 bg-white p-4 sm:p-6 rounded-xl sm:rounded-2xl border shadow-sm space-y-3">
            <h2 class="font-semibold text-gray-900">Адрес доставки</h2>

            @if($addresses->count())
                <select name="address_id"
                        class="w-full max-w-full min-w-0 border rounded-lg px-3 py-2 text-sm text-gray-700">
                    @foreach($addresses as $address)
                        <option value="{{ $address->id }}"
                            {{ ($defaultAddressId == $address->id) ? 'selected' : '' }}>
                            {{ $address->country }}, {{ $address->city }},
                            {{ $address->street }} {{ $address->house }},
                            кв. {{ $address->apartment }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500">
                    Основной адрес отмечен по умолчанию. Вы можете выбрать другой.
                </p>
            @else
                <p class="text-sm text-gray-500">
                    У вас нет сохранённых адресов.
                    <a href="{{ route('addresses.index') }}" class="text-indigo-600">
                        Добавить адрес
                    </a>
                </p>
            @endif
        </div>

<!-- Кнопка подтверждения -->
<button
    type="submit"
    class="w-full max-w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold text-base py-4 rounded-xl shadow-sm transition">
    Оформить заказ
</button>

    </form>

    


<script>
document.addEventListener('DOMContentLoaded', () => {
    const deliveryRadios = document.querySelectorAll('input[name="delivery_method"]');
    const addressSelect = document.querySelector('select[name="address_id"]');
    const submitButton = document.querySelector('button[type="submit"]');
    const subtotal = Number(@json($total));
    const prices = @json($deliveryPrices ?? []);
    const orderCount = Number(@json($orderCount));

    const deliveryEl = document.getElementById('delivery-cost');
    const totalEl = document.getElementById('total-with-delivery');

    const format = v =>
        v.toLocaleString('ru-RU', { minimumFractionDigits: 2 }) + ' ₽';

    function updateTotal(radio) {
        if (!radio) return;

        const price = Number(prices[radio.value] ?? 0) * orderCount;
        deliveryEl.textContent = price > 0 ? format(price) : 'Бесплатно';
        deliveryEl.className = price > 0 ? 'font-medium' : 'text-green-600 font-medium';
        totalEl.textContent = format(subtotal + price);
    }

    function updateButtonState() {
        const checkedRadio = document.querySelector('input[name="delivery_method"]:checked');
        const isPickup = checkedRadio && checkedRadio.value === 'pickup';
        const hasAddress = addressSelect && addressSelect.value;

        if (isPickup || hasAddress) {
            submitButton.removeAttribute('disabled');
        } else {
            submitButton.setAttribute('disabled', 'disabled');
        }
    }

    deliveryRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            updateTotal(e.target);
            updateButtonState();
        });
    });

    if (addressSelect) {
        addressSelect.addEventListener('change', updateButtonState);
    }

    submitButton.closest('form').addEventListener('submit', () => {
        submitButton.setAttribute('disabled', 'disabled');
        submitButton.textContent = 'Оформляем заказ...';
    });

    // Инициализация при загрузке страницы
    const checkedRadio = document.querySelector('input[name="delivery_method"]:checked');
    updateTotal(checkedRadio);
    updateButtonState();
});
</script>






</div>

<link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">

<style>
    .checkout-confirm-safe,
    .checkout-confirm-safe * {
        box-sizing: border-box;
    }

    .checkout-confirm-safe {
        max-width: 100vw;
    }

    .checkout-confirm-safe .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

</x-buyer-layout>
