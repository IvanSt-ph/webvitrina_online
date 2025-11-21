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


<div class="max-w-5xl mx-auto px-4 py-10 space-y-10">

    <!-- 🔙 Навигация -->
    <div class="flex items-center justify-between">
        <a href="{{ route('orders.index') }}"
           class="flex items-center gap-1 text-sm text-gray-600 hover:text-indigo-600">
            <i class="ri-arrow-left-line"></i> Назад к заказам
        </a>

        <span class="text-xs text-gray-400">
            Создан: {{ $order->created_at->format('d.m.Y H:i') }}
        </span>
    </div>


    <!-- 🧾 Заголовок заказа -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-2xl p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

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

        <div class="text-right">
            <div class="text-sm text-gray-500">Итоговая сумма:</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">
                {{ number_format($order->total_price, 2, ',', ' ') }} {{ $order->currency }}
            </div>
        </div>
    </div>


    <!-- 🔵 Прогресс бар (6 шагов) -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-2xl p-8">

        <div class="grid grid-cols-6 gap-4 text-center text-xs font-medium text-gray-600">

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
        <div class="flex justify-between -mt-5 px-4">
            @foreach(range(1,5) as $line)
                <div class="w-1/5 h-1 {{ $line < $active ? 'bg-indigo-600' : 'bg-gray-200' }}"></div>
            @endforeach
        </div>

    </div>


    <!-- ℹ Информация покупателя / доставка / оплата -->
    <div class="grid sm:grid-cols-3 gap-6">

<!-- Покупатель -->
<div class="bg-white border rounded-2xl p-6 shadow-sm">
    <h3 class="font-semibold text-gray-900 mb-3">Покупатель</h3>

    @if($order->buyer)
        <p class="text-sm text-gray-800">{{ $order->buyer->name ?? '—' }}</p>
        <p class="text-sm text-gray-500 mt-1">ID: {{ $order->buyer->id }}</p>
        <p class="text-sm text-indigo-600 mt-1">{{ $order->buyer->email }}</p>
    @else
        <p class="text-sm text-gray-400 italic">Покупатель не найден</p>
    @endif
</div>


        <!-- Доставка -->
        <div class="bg-white border rounded-2xl p-6 shadow-sm">
            <h3 class="font-semibold text-gray-900 mb-3">Доставка</h3>

            @if($order->address)
                <p class="text-sm text-gray-700">
                    {{ $order->address->country }},
                    {{ $order->address->city }},
                    {{ $order->address->street }} {{ $order->address->house }},
                    кв. {{ $order->address->apartment }}
                </p>

                @if($order->address->comment)
                    <p class="text-xs text-gray-500 mt-2">💬 {{ $order->address->comment }}</p>
                @endif
            @else
                <p class="text-sm text-gray-400">Адрес не указан</p>
            @endif
        </div>

        <!-- Оплата -->
        <div class="bg-white border rounded-2xl p-6 shadow-sm">
            <h3 class="font-semibold text-gray-900 mb-3">Оплата</h3>
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
    <div class="bg-white border rounded-2xl shadow-sm">

        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Товары в заказе</h3>
        </div>

        <div class="divide-y">
            @foreach($order->items as $item)
                <div class="p-6 flex items-center gap-4">

                    <img src="{{ asset('storage/' . $item->product->image) }}"
                         class="w-24 h-24 rounded-xl object-cover border">

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

                    <div class="text-right font-semibold text-gray-900">
                        {{ number_format($item->total, 2, ',', ' ') }} ₽
                    </div>

                </div>
            @endforeach
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t flex justify-between">
            <div class="text-sm text-gray-500">Итого:</div>
            <div class="text-xl font-bold text-gray-900">
                {{ number_format($order->total_price, 2, ',', ' ') }} ₽
            </div>
        </div>

    </div>


    <!-- 🎯 Кнопки -->
    <div class="flex flex-wrap gap-3">

        <a href="{{ route('orders.index') }}"
           class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-100">
            <i class="ri-arrow-left-line"></i> Назад
        </a>

        <a href="#"
           class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
            <i class="ri-shopping-bag-3-line"></i> Купить снова
        </a>

        <a href="#"
           class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
            <i class="ri-file-download-line"></i> Скачать чек (PDF)
        </a>

        <a href="#"
           class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
            <i class="ri-star-line"></i> Оставить отзыв
        </a>

    </div>

</div>

<link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">

</x-buyer-layout>
