<x-buyer-layout title="Заказ {{ $order->number }}">

@php
    $labels = [
        'pending'     => 'Ожидает обработки',
        'processing'  => 'Принят продавцом',
        'paid'        => 'Оплачен',
        'shipped'     => 'Передан в доставку',
        'delivered'   => 'Доставлен',
        'completed'   => 'Завершён',
        'canceled'    => 'Отменён',
    ];

    $colors = [
        'pending'     => 'bg-yellow-100 text-yellow-800',
        'processing'  => 'bg-indigo-100 text-indigo-700',
        'paid'        => 'bg-green-100 text-green-800',
        'shipped'     => 'bg-blue-100 text-blue-800',
        'delivered'   => 'bg-emerald-100 text-emerald-800',
        'completed'   => 'bg-gray-100 text-gray-700',
        'canceled'    => 'bg-red-100 text-red-800',
    ];

    $steps = [
        'pending'     => 1,
        'processing'  => 2,
        'paid'        => 3,
        'shipped'     => 4,
        'delivered'   => 5,
        'completed'   => 6,
    ];

    $active = $steps[$order->status] ?? 1;
@endphp


<div class="max-w-8xl mx-auto px-2 sm:px-4 py-4 sm:py-10 space-y-6 sm:space-y-10">

    <!-- 🔙 Навигация -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <a href="{{ route('orders.index') }}"
           class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-indigo-600">
            <i class="ri-arrow-left-line"></i> Назад к заказам
        </a>

        <span class="text-xs text-gray-400">
            Создан: {{ $order->created_at->format('d.m.Y H:i') }}
        </span>
    </div>


    <!-- 🧾 Заголовок заказа -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-xl sm:rounded-2xl p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

        <div class="flex items-start gap-3">
            <div class="w-11 h-11 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm shrink-0">
                <i class="ri-shopping-bag-3-line text-xl"></i>
            </div>
            <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Заказ {{ $order->number }}
            </h1>

            <div class="mt-1 text-sm text-gray-500">
                Статус:
                <span class="px-3 py-1 text-xs font-medium rounded-full {{ $colors[$order->status] }}">
                    {{ $labels[$order->status] }}
                </span>
            </div>
            </div>
        </div>

        <div class="sm:text-right">
            <div class="text-sm text-gray-500">Итоговая сумма:</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">
                {{ number_format($order->total_price, 2, ',', ' ') }} {{ $order->currency }}
            </div>
        </div>
    </div>


    <!-- 🔵 Прогресс бар (6 шагов) -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-xl sm:rounded-2xl p-4 sm:p-8 overflow-x-auto">

        <div class="grid grid-cols-6 gap-4 text-center text-xs font-medium text-gray-600 min-w-[620px]">

            @foreach([
                1 => 'Новый заказ',
                2 => 'Принят продавцом',
                3 => 'Оплачен',
                4 => 'В доставке',
                5 => 'Доставлен',
                6 => 'Завершён'
            ] as $step => $text)

                <div>
                    <div class="w-10 h-10 mx-auto flex items-center justify-center rounded-full
                        {{ $step <= $active ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                        {{ $step }}
                    </div>

                    <div class="mt-2">{{ $text }}</div>
                </div>

            @endforeach

        </div>

        <!-- Полоски между кружками -->
        <div class="flex justify-between -mt-5 px-4 min-w-[620px]">
            @foreach(range(1,5) as $line)
                <div class="w-1/5 h-1 {{ $line < $active ? 'bg-indigo-600' : 'bg-gray-200' }}"></div>
            @endforeach
        </div>

    </div>


    <!-- ℹ Информация покупателя / доставка / оплата -->
    <div class="grid sm:grid-cols-3 gap-4 sm:gap-6">

<!-- Покупатель -->
<div class="bg-white border border-gray-200 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
    <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
        <i class="ri-user-line text-indigo-500"></i>
        Покупатель
    </h3>

    @if($order->buyer)
        <p class="text-sm text-gray-800">{{ $order->buyer->name ?? '—' }}</p>
        <p class="text-sm text-gray-500 mt-1">ID: {{ $order->buyer->id }}</p>
        <p class="text-sm text-indigo-600 mt-1">{{ $order->buyer->email }}</p>
    @else
        <p class="text-sm text-gray-400 italic">Покупатель не найден</p>
    @endif
</div>


        <!-- Доставка -->
        <div class="bg-white border border-gray-200 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
            <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <i class="ri-truck-line text-indigo-500"></i>
                Доставка
            </h3>

            @if($order->address)
                <p class="text-sm text-gray-700">
                    {{ $order->address->country }},
                    {{ $order->address->city }},
                    {{ $order->address->street }} {{ $order->address->house }},
                    кв. {{ $order->address->apartment }}
                </p>

                @if($order->address->comment)
                    <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                        <i class="ri-chat-1-line"></i>
                        {{ $order->address->comment }}
                    </p>
                @endif
            @else
                <p class="text-sm text-gray-400">Адрес не указан</p>
            @endif
        </div>

        <!-- Оплата -->
        <div class="bg-white border border-gray-200 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
            <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <i class="ri-bank-card-line text-indigo-500"></i>
                Оплата
            </h3>
            <p class="text-sm text-gray-700">
                {{ $order->payment_method ?? 'Не указан' }}
            </p>

            @if($order->paid_at)
                <p class="text-sm text-green-600 mt-1">Оплачено в {{ $order->paid_at }}</p>
            @else
                <p class="text-sm text-gray-400">Пока не оплачено</p>
            @endif
        </div>

    </div>


    <!-- 🛒 Состав заказа -->
    <div class="bg-white border border-gray-200 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">

        <div class="px-4 sm:px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Товары в заказе</h3>
        </div>

        <div class="divide-y">
            @foreach($order->items as $item)
                <div class="p-4 sm:p-6 flex items-center gap-3 sm:gap-4">

                    <img src="{{ asset('storage/' . $item->product->image) }}"
                         class="w-16 h-16 sm:w-24 sm:h-24 rounded-xl object-cover border shrink-0">

                    <div class="flex-1">
                        <p class="text-gray-900 font-medium">
                            {{ $item->product->title ?? 'Товар удалён' }}
                        </p>
                        <p class="text-gray-500 text-sm mt-1">
                            Кол-во: {{ $item->quantity }}  
                            <span class="mx-1">•</span>  
                            Цена: {{ number_format($item->price, 2, ',', ' ') }} ₽
                        </p>
                    </div>

                    <div class="text-right font-semibold text-gray-900 text-sm sm:text-base">
                        {{ number_format($item->total, 2, ',', ' ') }} ₽
                    </div>

                </div>
            @endforeach
        </div>

        <div class="px-4 sm:px-6 py-4 bg-gray-50 border-t flex justify-between">
            <div class="text-sm text-gray-500">Итого:</div>
            <div class="text-xl font-bold text-gray-900">
                {{ number_format($order->total_price, 2, ',', ' ') }} ₽
            </div>
        </div>

    </div>


    <!-- 🎯 Кнопки -->
    <div class="flex flex-col sm:flex-row flex-wrap gap-3">

        <a href="{{ route('orders.index') }}"
           class="h-11 px-5 border border-gray-200 bg-white rounded-xl text-sm font-semibold hover:bg-gray-50 flex items-center justify-center gap-2">
            <i class="ri-arrow-left-line"></i> Назад
        </a>

        <a href="#"
           class="relative overflow-hidden group h-11 px-5 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2 backdrop-blur-sm border border-indigo-400/30">
            <span class="relative z-10 flex items-center gap-2">
                <i class="ri-shopping-bag-3-line"></i> Купить снова
            </span>
            <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
        </a>

        <a href="#"
           class="h-11 px-5 border border-gray-200 bg-white rounded-xl text-sm font-semibold hover:bg-gray-50 flex items-center justify-center gap-2">
            <i class="ri-file-download-line"></i> Скачать чек (PDF)
        </a>

        <a href="#"
           class="h-11 px-5 border border-gray-200 bg-white rounded-xl text-sm font-semibold hover:bg-gray-50 flex items-center justify-center gap-2">
            <i class="ri-star-line"></i> Оставить отзыв
        </a>

    </div>

</div>

<link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">

</x-buyer-layout>
