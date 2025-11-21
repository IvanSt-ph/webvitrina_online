<x-buyer-layout title="Подтверждение заказа">

<div class="max-w-4xl mx-auto px-4 py-10 space-y-10">

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

    <!-- 📍 Доставка и оплата (инфо-блоки) -->
    <div class="grid sm:grid-cols-2 gap-6">

        <div class="bg-white border rounded-2xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Доставка</h3>
            <p class="text-gray-700 text-sm">Бесплатная доставка</p>
            <p class="text-xs text-gray-500 mt-1">Способ будет выбран автоматически</p>
        </div>

        <div class="bg-white border rounded-2xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Оплата</h3>
            <p class="text-gray-700 text-sm">Оплата при получении</p>
        </div>
    </div>

    <!-- 💰 Итоги -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-4">
        <div class="flex justify-between text-gray-700 text-sm">
            <span>Товаров: {{ count($cart) }}</span>
            <span>{{ number_format($total, 2, ',', ' ') }} ₽</span>
        </div>

        <div class="flex justify-between text-gray-700 text-sm">
            <span>Доставка:</span>
            <span class="text-green-600 font-medium">Бесплатно</span>
        </div>

        <hr class="border-gray-200">

        <div class="flex justify-between items-center">
            <span class="text-lg font-semibold text-gray-900">Итого к оплате</span>
            <span class="text-2xl font-bold text-gray-900">
                {{ number_format($total, 2, ',', ' ') }} ₽
            </span>
        </div>
    </div>

    <!-- 📝 Форма оформления заказа -->
    <form action="{{ route('checkout.create') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Прячем содержимое корзины (на всякий случай, если хочешь использовать) -->
        @foreach($cart as $item)
            <input type="hidden" name="cart[]" value="{{ json_encode($item) }}">
        @endforeach

        <!-- 📍 Выбор адреса (УЖЕ внутри формы!) -->
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

        <!-- 🟣 Кнопка подтверждения -->
        <button
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-base py-4 rounded-xl shadow-sm transition">
            Оформить заказ
        </button>
    </form>

</div>

<link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">

</x-buyer-layout>
