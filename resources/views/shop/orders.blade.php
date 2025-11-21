<x-buyer-layout title="Мои заказы">

<div class="space-y-8 max-w-8xl mx-auto px-4 py-8">

    <!-- Заголовок -->
    <div>
        <h1 class="text-3xl font-bold text-gray-900">🧾 Мои заказы</h1>
        <p class="text-gray-600 mt-1">Отслеживайте покупки в удобном формате.</p>
    </div>

    <!-- Вкладки -->
    @php
        $tabs = [
            'active' => 'Активные',
            'completed' => 'Завершённые',
            'canceled' => 'Отменённые',
        ];

        $current = request('tab', 'active');
    @endphp

    <div class="flex gap-4 border-b border-gray-200">
        @foreach($tabs as $key => $label)
            <a href="{{ route('orders.index', ['tab' => $key]) }}"
               class="pb-3 text-sm font-medium
                    {{ $current === $key ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Список заказов -->
    @forelse($orders as $order)
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition p-6 space-y-5">

            <!-- Верхняя часть -->
            <div class="flex justify-between">
                <div>
                    <div class="font-semibold text-gray-900">
                        Заказ <span class="text-indigo-600">{{ $order->number }}</span>
                    </div>
                    <div class="text-gray-500 text-sm">
                        {{ $order->created_at->format('d.m.Y · H:i') }}
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-lg font-semibold text-gray-900">
                        {{ number_format($order->total_price, 2, ',', ' ') }} {{ $order->currency }}
                    </div>

                    @php
                        $colors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'processing' => 'bg-blue-100 text-blue-700',
                            'paid' => 'bg-emerald-100 text-emerald-800',
                            'shipped' => 'bg-sky-100 text-sky-800',
                            'delivered' => 'bg-emerald-100 text-emerald-800',
                            'completed' => 'bg-gray-100 text-gray-700',
                            'canceled' => 'bg-red-100 text-red-800',
                        ];

                        $labels = [
                            'pending' => 'Ожидает',
                            'processing' => 'Принят продавцом',
                            'paid' => 'Оплачен',
                            'shipped' => 'Отправлен',
                            'delivered' => 'Доставлен',
                            'completed' => 'Завершён',
                            'canceled' => 'Отменён',
                        ];
                    @endphp

                    <span class="px-3 py-1 rounded-full text-xs font-medium {{ $colors[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $labels[$order->status] ?? 'Неизвестно' }}
                    </span>
                </div>
            </div>

            <!-- Прогресс-бар -->
            @php
                $steps = [
                    'pending' => 1,
                    'processing' => 2,
                    'paid' => 3,
                    'shipped' => 4,
                    'delivered' => 5,
                    'completed' => 6,
                ];

                $active = $steps[$order->status] ?? 1;
            @endphp

            <div class="flex items-center justify-between text-xs font-medium text-gray-500">
                @foreach(range(1,6) as $step)
                    <div class="flex-1 flex flex-col items-center">
                        <div class="w-8 h-8 flex items-center justify-center rounded-full
                            {{ $step <= $active ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                            {{ $step }}
                        </div>

                        @if($step !== 6)
                            <div class="h-1 w-full
                                {{ $step < $active ? 'bg-indigo-600' : 'bg-gray-200' }}"></div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Товары -->
            <div class="space-y-3 border-t pt-4">
                @foreach($order->items->take(2) as $item)
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('storage/'.$item->product->image) }}"
                             class="w-16 h-16 rounded-lg border object-cover">

                        <div class="flex-1">
                            <div class="font-medium text-gray-900 text-sm">
                                {{ $item->product->title }}
                            </div>
                            <div class="text-gray-500 text-xs">
                                Кол-во: {{ $item->quantity }}
                            </div>
                        </div>

                        <div class="font-semibold text-gray-900 text-sm">
                            {{ number_format($item->total, 2, ',', ' ') }} ₽
                        </div>
                    </div>
                @endforeach

                @if($order->items->count() > 2)
                    <div class="text-xs text-indigo-600 font-medium">
                        + ещё {{ $order->items->count() - 2 }} товара
                    </div>
                @endif
            </div>

            <!-- Кнопки -->
            <div class="flex justify-end gap-3 pt-3">
                <a href="{{ route('orders.show', $order->id) }}"
                   class="px-4 py-2 rounded-lg border text-gray-600 text-sm hover:border-indigo-400 hover:text-indigo-600 transition">
                    Подробнее
                </a>

                <a href="#"
                   class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm hover:bg-indigo-700 transition">
                    Купить снова
                </a>
            </div>

        </div>

    @empty
        <div class="text-center py-20">
            <div class="text-6xl mb-3">🛍️</div>
            <div class="text-gray-700 text-lg font-medium">Пока заказов нет</div>
            <a href="{{ route('home') }}"
               class="mt-5 inline-block bg-indigo-600 text-white px-6 py-3 rounded-xl hover:bg-indigo-700 transition">
                Перейти к покупкам
            </a>
        </div>
    @endforelse

</div>

</x-buyer-layout>
