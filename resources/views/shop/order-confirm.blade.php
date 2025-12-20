<x-buyer-layout title="Подтверждение заказа">

<div class="max-w-8xl mx-auto px-4 py-10 space-y-10">

    <!-- 🔙 Назад в корзину -->
    <a href="{{ route('cart.index') }}"
       class="text-sm text-gray-600 hover:text-indigo-600 flex items-center gap-1">
        <i class="ri-arrow-left-line"></i> Вернуться в корзину
    </a>

    <!-- 🧾 Заголовок -->
    <div>
        <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">Подтверждение заказа</h1>
        <p class="text-gray-500 text-sm mt-1">
            Проверьте товары перед оформлением. После подтверждения заказ уйдёт продавцу.
        </p>
    </div>

    <!-- 📦 Состав заказа -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-2xl divide-y">
        @foreach($cart as $item)
            <div class="flex items-center gap-4 p-5">
                <img src="{{ asset('storage/'.$item['image']) }}"
                     class="w-20 h-20 rounded-xl border object-cover">

                <div class="flex-1">
                    <p class="text-gray-900 font-medium text-sm sm:text-base">
                        {{ $item['title'] }}
                    </p>
                    <p class="text-gray-500 text-xs sm:text-sm mt-1">
                        Кол-во:
                        <span class="text-gray-800 font-semibold">{{ $item['qty'] }}</span>
                    </p>
                </div>

                <div class="text-right min-w-[110px]">
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

    <!-- 📝 Форма оформления заказа -->
    <form action="{{ route('checkout.create') }}" method="POST" class="space-y-6">
        @csrf

<!-- 📍 Доставка и оплата (теперь внутри формы!) -->
<div class="grid sm:grid-cols-2 gap-6">
    <!-- 🚚 Выбор способа доставки -->
    <div class="bg-white border rounded-2xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Способ доставки</h3>
        
        @if(isset($deliveryMethods) && count($deliveryMethods))
            <div class="space-y-2">
                @foreach($deliveryMethods as $key => $label)
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                        <input type="radio" 
                               name="delivery_method" 
                               value="{{ $key }}"
                               class="w-4 h-4 text-indigo-600 mr-3"
                               {{ $loop->first ? 'checked' : '' }}
                               required>
                        <span class="text-gray-800 text-sm">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        @else
            <div class="space-y-2">
                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="delivery_method" value="courier" class="w-4 h-4 text-indigo-600 mr-3" checked required>
                    <span class="text-gray-800 text-sm">🚚 Курьерская доставка</span>
                </label>
                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="delivery_method" value="pickup" class="w-4 h-4 text-indigo-600 mr-3">
                    <span class="text-gray-800 text-sm">🏪 Самовывоз</span>
                </label>
                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="delivery_method" value="post" class="w-4 h-4 text-indigo-600 mr-3">
                    <span class="text-gray-800 text-sm">📮 Почта России</span>
                </label>
            </div>
        @endif
        
        <p class="text-xs text-gray-500 mt-3">
            Стоимость доставки рассчитывается после выбора способа <br>*(Важно: стоимость доставки указана примерно и может варьироваться.)
        </p>
    </div>

    <!-- 💳 Выбор способа оплаты -->
    <div class="bg-white border rounded-2xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Способ оплаты</h3>
        
        @if(isset($paymentMethods) && count($paymentMethods))
            <div class="space-y-2">
                @foreach($paymentMethods as $key => $label)
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                        <input type="radio" 
                               name="payment_method" 
                               value="{{ $key }}"
                               class="w-4 h-4 text-indigo-600 mr-3"
                               {{ $loop->first ? 'checked' : '' }}
                               required>
                        <span class="text-gray-800 text-sm">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        @else
            <div class="space-y-2">
                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="payment_method" value="cash" class="w-4 h-4 text-indigo-600 mr-3" checked required>
                    <span class="text-gray-800 text-sm">💵 Наличными при получении</span>
                </label>
                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="payment_method" value="card" class="w-4 h-4 text-indigo-600 mr-3">
                    <span class="text-gray-800 text-sm">💳 Картой онлайн</span>
                </label>
                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="radio" name="payment_method" value="bank_transfer" class="w-4 h-4 text-indigo-600 mr-3">
                    <span class="text-gray-800 text-sm">🏦 Банковский перевод</span>
                </label>
            </div>
        @endif
        
        <p class="text-xs text-gray-500 mt-3">
            Выбранный способ оплаты будет сохранён в заказе
        </p>
    </div>
</div>

<!-- 💰 Итоги -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
    <div class="flex justify-between text-gray-700 text-sm">
<span>
    Товаров:
    {{ collect($cart)->sum('qty') }}
</span>

        <span id="subtotal">{{ number_format($total, 2, ',', ' ') }} ₽</span>
    </div>

    <div class="flex justify-between text-gray-700 text-sm">
        <span>Доставка:</span>
        <span id="delivery-cost" class="font-medium">
            @if(isset($deliveryPrices['courier']) && $deliveryPrices['courier'] > 0)
                {{ number_format($deliveryPrices['courier'], 2, ',', ' ') }} ₽
            @else
                Бесплатно
            @endif
        </span>
    </div>

    <hr class="border-gray-200">

    <div class="flex justify-between items-center">
        <span class="text-lg font-semibold text-gray-900">Итого к оплате</span>
        <span id="total-with-delivery" class="text-2xl font-bold text-gray-900">
            {{ number_format($totalWithDelivery, 2, ',', ' ') }} ₽
        </span>
    </div>
</div>

<!-- 📍 Выбор адреса -->
        <div class="bg-white p-6 rounded-2xl border shadow-sm space-y-3">
            <h2 class="font-semibold text-gray-900">Адрес доставки</h2>

            @if($addresses->count())
                <select name="address_id"
                        class="w-full border rounded-lg px-3 py-2 text-sm text-gray-700">
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
    class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold text-base py-4 rounded-xl shadow-sm transition">
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

    const deliveryEl = document.getElementById('delivery-cost');
    const totalEl = document.getElementById('total-with-delivery');

    const format = v =>
        v.toLocaleString('ru-RU', { minimumFractionDigits: 2 }) + ' ₽';

    function updateTotal(radio) {
        if (!radio) return;

        const price = Number(prices[radio.value] ?? 0);
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

    // Инициализация при загрузке страницы
    const checkedRadio = document.querySelector('input[name="delivery_method"]:checked');
    updateTotal(checkedRadio);
    updateButtonState();
});
</script>






</div>

<link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">

</x-buyer-layout>