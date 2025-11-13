<x-buyer-layout title="Подтверждение заказа">

<div class="max-w-4xl mx-auto px-4 py-10 space-y-8">

    <!-- Заголовок -->
    <div>
        <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">Подтверждение заказа</h1>
        <p class="text-gray-500 mt-1 text-sm sm:text-base">
            Проверьте товары перед оформлением.
        </p>
    </div>

    <!-- Список товаров -->
    <div class="bg-white border border-gray-100 shadow-sm rounded-2xl divide-y">

        @foreach($cart as $item)
        <div class="flex items-center gap-4 p-5">

            <!-- Фото -->
            <img src="{{ asset('storage/'.$item['image']) }}"
                 class="w-20 h-20 rounded-xl border object-cover" />

            <div class="flex-1">
                <p class="text-gray-900 font-medium text-sm sm:text-base">
                    {{ $item['title'] }}
                </p>
                <p class="text-gray-500 text-xs sm:text-sm mt-1">
                    Кол-во: <span class="text-gray-700 font-medium">{{ $item['qty'] }}</span>
                </p>
            </div>

            <!-- Цена -->
            <div class="text-right">
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


    <!-- Блок итогов -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">

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


    <!-- Кнопка подтверждения -->
    <form method="POST" action="{{ route('checkout.create') }}" class="pt-2">
        @csrf

        @foreach($cart as $item)
        <input type="hidden" name="cart[]" value="{{ json_encode($item) }}">
        @endforeach

        <button
            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-base py-4 rounded-xl transition shadow-sm">
            Оформить заказ
        </button>
    </form>

</div>

</x-buyer-layout>
