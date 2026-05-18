<x-buyer-layout title="Мои заказы">

<div class="space-y-6 sm:space-y-8 max-w-8xl mx-auto px-3 sm:px-6 py-4 sm:py-8">

    <!-- Заголовок -->
    <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm">
            <i class="ri-shopping-bag-3-line text-xl"></i>
        </div>
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Мои заказы</h1>
            <p class="text-sm sm:text-base text-gray-600 mt-1">Отслеживайте покупки в удобном формате.</p>
        </div>
    </div>

    <!-- Вкладки -->
    @php
        $tabs = [
            'active' => 'Активные',
            'completed' => 'Завершённые',
            'canceled' => 'Отменённые',
        ];

        $current = request('tab', 'active');

        $stepLabels = [
            1 => 'Новый заказ',
            2 => 'Принят продавцом',
            3 => 'Оплачен',
            4 => 'В доставке',
            5 => 'Доставлен',
            6 => 'Завершён',
        ];
    @endphp

    <div class="border-b border-gray-200 overflow-x-auto">
        <div class="flex gap-5 min-w-max">
        @foreach($tabs as $key => $label)
            <a href="{{ route('orders.index', ['tab' => $key]) }}"
               class="pb-3 text-sm font-medium
                    {{ $current === $key ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                {{ $label }}
            </a>
        @endforeach
        </div>
    </div>

    <!-- Список заказов -->
    @forelse($orders as $order)
        <div class="bg-white border border-gray-200 rounded-xl sm:rounded-2xl shadow-sm hover:shadow-md transition p-4 sm:p-6 space-y-5">

            <!-- Верхняя часть -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <div class="font-semibold text-gray-900">
                        Заказ <span class="text-indigo-600">{{ $order->number }}</span>
                    </div>
                    <div class="text-gray-500 text-sm">
                        {{ $order->created_at->format('d.m.Y · H:i') }}
                    </div>
                </div>

                <div class="sm:text-right">
                    <div class="text-lg font-semibold text-gray-900">
                        {{ number_format($order->total_price, 2, ',', ' ') }} {{ $order->currency }}
                    </div>

                    <x-status-badge :status="$order->status" class="mt-2" />
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

            <div class="sm:hidden rounded-xl bg-gray-50 border border-gray-100 p-3">
                <div class="text-xs font-semibold text-gray-500 mb-3">Статус заказа</div>
                <div class="space-y-2">
                    @foreach($stepLabels as $step => $text)
                        <div class="flex items-center gap-3">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold shrink-0
                                {{ $step <= $active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-400 border border-gray-200' }}">
                                @if($step < $active)
                                    <i class="ri-check-line"></i>
                                @else
                                    {{ $step }}
                                @endif
                            </div>
                            <div class="text-sm {{ $step === $active ? 'font-semibold text-gray-900' : ($step < $active ? 'text-gray-700' : 'text-gray-400') }}">
                                {{ $text }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="hidden sm:flex items-center justify-between text-xs font-medium text-gray-500 overflow-x-auto pb-1">
                @foreach(range(1,6) as $step)
                    <div class="min-w-12 flex-1 flex flex-col items-center">
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
                    <div class="flex items-center gap-3 sm:gap-4">

                        {{-- ЕСЛИ ТОВАР ЕЩЁ СУЩЕСТВУЕТ --}}
                        @if($item->product)

                            <img src="{{ $item->product->image_url ?? asset('images/no-image.png') }}"
                                class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl border object-cover">

                            <div class="flex-1">
                                <div class="font-medium text-gray-900 text-sm">
                                    {{ $item->product->title }}
                                </div>
                                <div class="text-gray-500 text-xs">
                                    Кол-во: {{ $item->quantity }}
                                </div>
                            </div>

                            <div class="font-semibold text-gray-900 text-sm text-right">
                                {{ number_format($item->total, 2, ',', ' ') }} {{ $order->currency }}
                            </div>

                        {{-- ЕСЛИ ТОВАР УДАЛЁН (soft deleted) --}}
                        @else
                            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl border bg-gray-100 flex items-center justify-center">
                                <i class="ri-image-off-line text-2xl text-gray-400"></i>
                            </div>

                            <div class="flex-1">
                                <div class="font-medium text-gray-500 text-sm">
                                    Товар был удалён продавцом
                                </div>
                                <div class="text-gray-400 text-xs">
                                    Кол-во: {{ $item->quantity }}
                                </div>
                            </div>

                            <div class="font-semibold text-gray-400 text-sm">
                                {{ number_format($item->total, 2, ',', ' ') }} {{ $order->currency }}
                            </div>
                        @endif

                    </div>
                @endforeach

                @if($order->items->count() > 2)
                    <div class="text-xs text-indigo-600 font-medium">
                        + ещё {{ $order->items->count() - 2 }} товара
                    </div>
                @endif
            </div>


            <!-- Кнопки -->
            <div class="flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-3 pt-3">
                <x-secondary-action as="a" href="{{ route('orders.show', $order->id) }}" size="sm">
                    <i class="ri-eye-line"></i>
                    Подробнее
                </x-secondary-action>

                <a href="#"
                   class="relative overflow-hidden group h-10 px-4 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2 backdrop-blur-sm border border-indigo-400/30">
                    <span class="relative z-10 flex items-center gap-2">
                        <i class="ri-repeat-line"></i>
                    Купить снова
                    </span>
                    <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
                </a>
            </div>

        </div>

    @empty
        <x-empty-state
            icon="ri-shopping-bag-3-line"
            title="Пока заказов нет"
            description="Когда вы оформите покупку, заказ появится здесь."
            class="py-16 sm:py-20"
        >
            <a href="{{ route('home') }}"
               class="relative overflow-hidden group inline-flex items-center justify-center gap-2 px-6 py-3 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 backdrop-blur-sm border border-indigo-400/30">
                <span class="relative z-10 flex items-center gap-2">
                    <i class="ri-arrow-left-line"></i>
                    Перейти к покупкам
                </span>
                <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
            </a>
        </x-empty-state>
    @endforelse

</div>

</x-buyer-layout>
