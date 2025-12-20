{{-- resources/views/seller/orders/show.blade.php --}}
<x-seller-layout :title="'Заказ ' . ($order->number ?? ('#' . $order->id))">

    @php
        /** @var \App\Models\Order $order */

        $statusColors = [
            'pending'    => 'bg-amber-50 text-amber-700 border border-amber-200',
            'processing' => 'bg-sky-50 text-sky-700 border border-sky-200',
            'paid'       => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'shipped'    => 'bg-blue-50 text-blue-700 border border-blue-200',
            'delivered'  => 'bg-green-50 text-green-700 border border-green-200',
            'completed'  => 'bg-slate-50 text-slate-700 border border-slate-200',
            'canceled'   => 'bg-red-50 text-red-700 border border-red-200',
        ];

        $currentStatusClass = $statusColors[$order->status] ?? 'bg-gray-50 text-gray-700 border border-gray-200';

        $steps = [
            \App\Models\Order::STATUS_PENDING    => 'Новый заказ',
            \App\Models\Order::STATUS_PROCESSING => 'Принят продавцом',
            \App\Models\Order::STATUS_PAID       => 'Оплачен',
            \App\Models\Order::STATUS_SHIPPED    => 'Передан в доставку',
            \App\Models\Order::STATUS_DELIVERED  => 'Доставлен',
            \App\Models\Order::STATUS_COMPLETED  => 'Завершён',
        ];

        // Позиция текущего статуса в прогрессе
        $statusKeys   = array_keys($steps);
        $currentIndex = array_search($order->status, $statusKeys, true);
        if ($currentIndex === false) {
            $currentIndex = 0;
        }

        $itemsCount = $order->items->sum('quantity');
    @endphp

    <div class="space-y-6">

        {{-- Верхняя панель --}}
        <div class="flex items-center justify-between gap-3">
            <div class="space-y-1">
                <a href="{{ route('seller.orders.index') }}"
                   class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                    <x-icon name="arrow-left" class="w-4 h-4 mr-1"/>
                    Назад к списку заказов
                </a>

                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                    Заказ {{ $order->number ?? ('#' . $order->id) }}
                </h1>

                <div class="text-sm text-gray-500">
                    от {{ $order->created_at?->format('d.m.Y H:i') }}
                    • Покупатель: {{ $order->user->name ?? 'Неизвестен' }}
                    (ID: {{ $order->user_id }})
                </div>
            </div>

            <div class="text-right space-y-2">
                <div class="text-lg font-semibold text-gray-900">
                    {{ $order->formatted_total_price ?? (number_format($order->total_price, 2, ',', ' ') . ' ' . ($order->currency ?? '')) }}
                </div>

                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $currentStatusClass }}">
                    {{ $order->status_ru }}
                </span>
            </div>
        </div>

        {{-- Прогресс статусов --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm px-5 py-4">
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between text-xs font-medium text-gray-500">
                    @foreach($steps as $key => $label)
                        @php
                            $index = array_search($key, $statusKeys, true);
                            $isDone = $index !== false && $index <= $currentIndex;
                        @endphp
                        <div class="flex-1 flex flex-col items-center">
                            <div class="flex items-center w-full">
                                @if(!$loop->first)
                                    <div class="flex-1 h-[2px] {{ $isDone ? 'bg-indigo-500' : 'bg-gray-200' }}"></div>
                                @endif

                                <div class="flex items-center justify-center w-7 h-7 rounded-full border text-[11px] font-semibold
                                            {{ $isDone ? 'bg-indigo-500 border-indigo-500 text-white' : 'bg-white border-gray-300 text-gray-400' }}">
                                    {{ $loop->iteration }}
                                </div>

                                @if(!$loop->last)
                                    <div class="flex-1 h-[2px] {{ $index < $currentIndex ? 'bg-indigo-500' : 'bg-gray-200' }}"></div>
                                @endif
                            </div>

                            <div class="mt-2 text-[11px] text-center leading-snug
                                        {{ $isDone ? 'text-gray-800' : 'text-gray-400' }}">
                                {{ $label }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
{{-- Покупатель --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 flex items-center gap-4">

    {{-- Аватар --}}
    <img src="{{ $order->user->avatar_url ?? asset('images/default-avatar.png') }}"
         class="w-14 h-14 rounded-xl object-cover border shadow-sm" alt="avatar">

    <div class="flex-1 space-y-1">
        <h2 class="text-sm font-semibold text-gray-800">Покупатель</h2>

        <div class="text-sm font-medium text-gray-900">
            {{ $order->user->name ?? 'Неизвестен' }}
        </div>

        <div class="text-xs text-gray-500">
            ID: {{ $order->user_id }}
        </div>

        @if(!empty($order->user->phone))
            <div class="text-xs text-gray-700 flex items-center gap-1 pt-1">
                <i class="ri-phone-line text-gray-500 text-sm"></i>
                <span>{{ $order->user->phone }}</span>
            </div>
        @endif

        @if(isset($order->user->email))
            <div class="text-xs text-gray-500 flex items-center gap-1">
                <i class="ri-mail-line text-gray-500 text-sm"></i>
                <span>{{ $order->user->email }}</span>
            </div>
        @endif
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">



            {{-- Действия продавца --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
    <h2 class="text-sm font-semibold text-gray-800 mb-3">Действия продавца</h2>

    <div class="flex flex-wrap gap-3">

        {{-- PENDING → PROCESSING --}}
        @if($order->status === 'pending')
            <form method="POST" action="{{ route('seller.orders.updateStatus', $order) }}">
                @csrf
                <input type="hidden" name="status" value="processing">
                <button class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700">
                    Принять заказ
                </button>
            </form>
        @endif

        {{-- PROCESSING → PAID --}}
        @if($order->status === 'processing')
            <form method="POST" action="{{ route('seller.orders.updateStatus', $order) }}">
                @csrf
                <input type="hidden" name="status" value="paid">
                <button class="px-5 py-2 bg-emerald-600 text-white rounded-xl text-sm font-medium hover:bg-emerald-700">
                    Отметить как оплаченный
                </button>
            </form>
        @endif

        {{-- PAID → SHIPPED --}}
        @if($order->status === 'paid')
            <form method="POST" action="{{ route('seller.orders.updateStatus', $order) }}">
                @csrf
                <input type="hidden" name="status" value="shipped">
                <button class="px-5 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700">
                    Передать в доставку
                </button>
            </form>
        @endif

        {{-- SHIPPED → DELIVERED --}}
        @if($order->status === 'shipped')
            <form method="POST" action="{{ route('seller.orders.updateStatus', $order) }}">
                @csrf
                <input type="hidden" name="status" value="delivered">
                <button class="px-5 py-2 bg-green-600 text-white rounded-xl text-sm font-medium hover:bg-green-700">
                    Отметить доставленным
                </button>
            </form>
        @endif

        {{-- DELIVERED → COMPLETED --}}
        @if($order->status === 'delivered')
            <form method="POST" action="{{ route('seller.orders.updateStatus', $order) }}">
                @csrf
                <input type="hidden" name="status" value="completed">
                <button class="px-5 py-2 bg-gray-700 text-white rounded-xl text-sm font-medium hover:bg-gray-800">
                    Завершить заказ
                </button>
            </form>
        @endif

        {{-- Отмена заказа — доступна в любой статусной ветке, кроме completed --}}
        @if($order->status !== 'completed' && $order->status !== 'canceled')
            <form method="POST" action="{{ route('seller.orders.updateStatus', $order) }}"
                onsubmit="return confirm('Вы точно хотите отменить заказ?');">
                @csrf
                <input type="hidden" name="status" value="canceled">
                <button class="px-5 py-2 bg-red-600 text-white rounded-xl text-sm font-medium hover:bg-red-700">
                    Отменить заказ
                </button>
            </form>
        @endif

    </div>
</div>


{{-- Доставка --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 space-y-2">
    <h2 class="text-sm font-semibold text-gray-800 mb-1">
        Доставка
    </h2>

    @php
        $deliveryLabels = [
            'courier' => '🚚 Курьерская доставка(1-2дня)',
            'pickup' => '🏪 Самовывоз из пункта выдачи',
            'post' => '📮 Почта ПМР',
            'express' => '⚡ Экспресс-доставка',
        ];
    @endphp
    
    <div class="text-sm text-gray-900">
        {{ $deliveryLabels[$order->delivery_method] ?? $order->delivery_method ?? 'Способ не указан' }}
    </div>

    <div class="text-xs text-gray-500 mt-2">
        @if($order->delivery_address)
            📦 {{ $order->delivery_address }}
        @elseif($order->address)
            🏠 {{ $order->address->full }}
        @else
            Адрес не указан
        @endif
    </div>
</div>

            {{-- Оплата --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 space-y-2">
                <h2 class="text-sm font-semibold text-gray-800 mb-1">
                    Оплата
                </h2>

                <div class="text-sm text-gray-900">
                    {{ $order->payment_method ?? 'Не указано' }}
                </div>

                <div class="text-xs text-gray-500 mt-2 space-y-1">
                    <div>
                        Создан: {{ $order->created_at?->format('d.m.Y H:i') }}
                    </div>
                    @if($order->paid_at)
                        <div>
                            Оплачен: {{ $order->paid_at->format('d.m.Y H:i') }}
                        </div>
                    @endif
                    @if($order->shipped_at)
                        <div>
                            Отправлен: {{ $order->shipped_at->format('d.m.Y H:i') }}
                        </div>
                    @endif
                    @if($order->delivered_at)
                        <div>
                            Доставлен: {{ $order->delivered_at->format('d.m.Y H:i') }}
                        </div>
                    @endif
                    @if($order->canceled_at)
                        <div class="text-red-500">
                            Отменён: {{ $order->canceled_at->format('d.m.Y H:i') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Товары в заказе --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-800">
                    Товары в заказе
                </h2>
                <span class="text-xs text-gray-500">
                    {{ $itemsCount }} {{ \Illuminate\Support\Str::plural('товар', $itemsCount) }}
                </span>
            </div>

            <div class="divide-y divide-gray-100">
                @foreach($order->items as $item)
                    <div class="px-5 py-4 flex items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900">
                                {{ $item->product->title ?? 'Товар удалён' }}
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                ID товара: {{ $item->product_id }}
                            </div>
                        </div>

                        <div class="flex items-center gap-6 text-sm">
                            <div class="text-gray-500">
                                Кол-во:
                                <span class="font-semibold text-gray-900">
                                    {{ $item->quantity }}
                                </span>
                            </div>

                            <div class="text-gray-500">
                                Цена:
                                <span class="font-semibold text-gray-900">
                                    {{ number_format($item->price, 2, ',', ' ') }} {{ $order->currency ?? '' }}
                                </span>
                            </div>

                            <div class="text-right font-semibold text-gray-900">
                                {{ number_format($item->total, 2, ',', ' ') }} {{ $order->currency ?? '' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-end">
                <div class="text-sm text-gray-500 mr-3">
                    Итого:
                </div>
                <div class="text-lg font-semibold text-gray-900">
                    {{ $order->formatted_total_price ?? (number_format($order->total_price, 2, ',', ' ') . ' ' . ($order->currency ?? '')) }}
                </div>
            </div>
        </div>

    </div>
</x-seller-layout>
