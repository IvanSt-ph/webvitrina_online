<x-buyer-layout title="Заказ {{ $order->number }}">

@php
    $steps = [
        'pending'     => 1,
        'processing'  => 2,
        'paid'        => 3,
        'shipped'     => 4,
        'delivered'   => 5,
        'completed'   => 6,
    ];

    $active = $steps[$order->status] ?? 1;

    $stepLabels = [
        1 => 'Новый заказ',
        2 => 'Принят продавцом',
        3 => 'Оплачен',
        4 => 'В доставке',
        5 => 'Доставлен',
        6 => 'Завершён',
    ];

    $addressParts = collect([
        $order->address?->country,
        $order->address?->city,
        $order->address?->street,
        trim(($order->address?->house ?? '') . ($order->address?->apartment ? ', кв. ' . $order->address->apartment : '')),
    ])->filter(fn ($part) => filled($part));
@endphp


<div class="order-show-mobile-safe min-h-screen w-full max-w-full overflow-x-hidden bg-white px-3 py-4 pb-[5.5rem] text-slate-900 sm:px-5 sm:py-6 lg:px-6" style="max-width:100vw;">
<div class="mx-auto w-full max-w-[1500px] space-y-5 overflow-hidden sm:space-y-6">

    <!-- 🔙 Навигация -->
    <div class="grid w-full min-w-0 gap-2 sm:flex sm:items-center sm:justify-between">
        <a href="{{ route('orders.index') }}"
           class="inline-flex min-w-0 items-center gap-1 text-sm text-gray-600 hover:text-indigo-600">
            <i class="ri-arrow-left-line shrink-0"></i>
            <span class="min-w-0 truncate">Назад к заказам</span>
        </a>

        <span class="min-w-0 truncate text-xs text-gray-400">
            Создан: {{ $order->created_at->format('d.m.Y H:i') }}
        </span>
    </div>


    <!-- 🧾 Заголовок заказа -->
    <div class="grid w-full max-w-full min-w-0 gap-4 overflow-hidden rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex sm:flex-row sm:items-center sm:justify-between sm:rounded-2xl sm:p-6">

        <div class="flex min-w-0 items-start gap-3">
            <div class="w-11 h-11 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm shrink-0">
                <i class="ri-shopping-bag-3-line text-xl"></i>
            </div>
            <div class="min-w-0">
            <h1 class="truncate text-2xl font-bold text-gray-900">
                Заказ {{ $order->number }}
            </h1>

            <div class="mt-1 flex min-w-0 flex-wrap items-center gap-2 text-sm text-gray-500">
                Статус:
                <x-status-badge :status="$order->status" class="max-w-full truncate px-2 sm:px-3" />
            </div>
            </div>
        </div>

        <div class="min-w-0 sm:shrink-0 sm:text-right">
            <div class="text-sm text-gray-500">Итоговая сумма:</div>
            <div class="mt-1 truncate text-2xl font-bold text-gray-900">
                {{ number_format($order->total_price, 2, ',', ' ') }} {{ $order->currency }}
            </div>
        </div>
    </div>


    <!-- 🔵 Прогресс бар (6 шагов) -->
    <div class="w-full max-w-full bg-white shadow-sm border border-gray-200 rounded-xl sm:rounded-2xl p-4 sm:p-8 overflow-hidden">

        <div class="sm:hidden space-y-3">
            @foreach($stepLabels as $step => $text)
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold shrink-0
                        {{ $step <= $active ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-400' }}">
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

        <div class="hidden sm:grid grid-cols-6 gap-4 text-center text-xs font-medium text-gray-600">

            @foreach($stepLabels as $step => $text)

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
        <div class="hidden sm:flex justify-between -mt-5 px-4">
            @foreach(range(1,5) as $line)
                <div class="w-1/5 h-1 {{ $line < $active ? 'bg-indigo-600' : 'bg-gray-200' }}"></div>
            @endforeach
        </div>

    </div>


    <!-- ℹ Информация покупателя / доставка / оплата -->
    <div class="grid w-full min-w-0 gap-4 overflow-hidden sm:grid-cols-3 sm:gap-6">

<!-- Покупатель -->
<div class="min-w-0 bg-white border border-gray-200 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
    <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
        <i class="ri-user-line text-indigo-500"></i>
        Покупатель
    </h3>

    @if($order->buyer)
        <p class="break-words text-sm text-gray-800">{{ $order->buyer->name ?? '—' }}</p>
        <p class="text-sm text-gray-500 mt-1">ID: {{ $order->buyer->id }}</p>
        <p class="break-all text-sm text-indigo-600 mt-1">{{ $order->buyer->email }}</p>
    @else
        <p class="text-sm text-gray-400 italic">Покупатель не найден</p>
    @endif
</div>


        <!-- Доставка -->
        <div class="min-w-0 max-w-full overflow-hidden bg-white border border-gray-200 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
            <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <i class="ri-truck-line text-indigo-500"></i>
                Доставка
            </h3>

            @if($addressParts->isNotEmpty())
                <p class="break-words text-sm text-gray-700">
                    {{ $addressParts->join(', ') }}
                </p>

                @if(filled($order->address?->comment))
                    <p class="min-w-0 break-words text-xs text-gray-500 mt-2 flex items-start gap-1">
                        <i class="ri-chat-1-line shrink-0"></i>
                        {{ $order->address->comment }}
                    </p>
                @endif
            @else
                <p class="text-sm text-gray-400">Адрес не указан</p>
            @endif
        </div>

        <!-- Оплата -->
        <div class="min-w-0 max-w-full overflow-hidden bg-white border border-gray-200 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
            <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                <i class="ri-bank-card-line text-indigo-500"></i>
                Оплата
            </h3>
            <p class="break-words text-sm text-gray-700">
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
    <div class="w-full max-w-full bg-white border border-gray-200 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">

        <div class="px-4 sm:px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-900">Товары в заказе</h3>
        </div>

        <div class="divide-y">
            @foreach($order->items as $item)
                @php
                    $itemTitle = $item->product->title ?? 'Товар удалён';
                    $shortItemTitle = \Illuminate\Support\Str::limit($itemTitle, 14);
                @endphp
                <div class="grid w-full min-w-0 grid-cols-[4rem_minmax(0,1fr)] gap-3 overflow-hidden p-4 sm:flex sm:items-center sm:gap-4 sm:p-6">

                    @if($item->product)
                        <img src="{{ $item->product->image_thumb_url }}"
                             alt="{{ $item->product->title }}"
                             class="w-16 h-16 sm:w-24 sm:h-24 rounded-xl object-cover border shrink-0">
                    @else
                        <div class="w-16 h-16 sm:w-24 sm:h-24 rounded-xl border bg-gray-100 flex items-center justify-center shrink-0 text-gray-400">
                            <i class="ri-image-off-line text-2xl"></i>
                        </div>
                    @endif

                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-gray-900 font-medium sm:hidden"
                            title="{{ $itemTitle }}"
                        >
                            {{ $shortItemTitle }}
                        </p>
                        <div class="hidden sm:block">
                            <p
                                class="overflow-hidden text-gray-900 font-medium"
                                style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow-wrap:anywhere;word-break:break-word;"
                                title="{{ $itemTitle }}"
                            >
                                {{ $itemTitle }}
                            </p>
                        </div>
                        <p class="text-gray-500 text-sm mt-1">
                            Кол-во: {{ $item->quantity }}  
                            <span class="mx-1">•</span>  
                            Цена: {{ number_format($item->price, 2, ',', ' ') }} ₽
                        </p>
                        <p class="mt-1 text-sm font-semibold text-gray-900 sm:hidden">
                            Сумма: {{ number_format($item->total, 2, ',', ' ') }} ₽
                        </p>
                    </div>

                    <div class="hidden min-w-0 break-words text-right font-semibold text-gray-900 text-sm sm:ml-auto sm:block sm:shrink-0 sm:text-base">
                        {{ number_format($item->total, 2, ',', ' ') }} ₽
                    </div>

                </div>
            @endforeach
        </div>

        <div class="px-4 sm:px-6 py-4 bg-gray-50 border-t grid min-w-0 grid-cols-[auto_minmax(0,1fr)] items-center gap-3">
            <div class="text-sm text-gray-500">Итого:</div>
            <div class="min-w-0 truncate text-right text-xl font-bold text-gray-900">
                {{ number_format($order->total_price, 2, ',', ' ') }} ₽
            </div>
        </div>

    </div>


    <!-- 🎯 Кнопки -->
    <div class="grid w-full min-w-0 max-w-full grid-cols-1 gap-3 overflow-hidden sm:flex sm:flex-row sm:flex-wrap">

        <a href="{{ route('orders.index') }}"
           class="flex h-11 w-full max-w-full min-w-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 sm:w-auto sm:px-5">
            <i class="ri-arrow-left-line shrink-0"></i>
            <span class="min-w-0 truncate">Назад</span>
        </a>

        <a href="#"
           class="group relative flex h-11 w-full max-w-full min-w-0 items-center justify-center gap-2 overflow-hidden rounded-xl border border-indigo-400/30 bg-indigo-500/90 px-4 text-sm font-semibold text-white shadow-lg backdrop-blur-sm transition-all duration-300 hover:bg-indigo-600 hover:shadow-xl sm:w-auto sm:px-5">
            <span class="relative z-10 flex min-w-0 items-center gap-2">
                <i class="ri-shopping-bag-3-line shrink-0"></i>
                <span class="min-w-0 truncate">Купить снова</span>
            </span>
            <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
        </a>

        <a href="#"
           class="flex h-11 w-full max-w-full min-w-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 sm:w-auto sm:px-5">
            <i class="ri-file-download-line shrink-0"></i>
            <span class="min-w-0 truncate">Скачать чек (PDF)</span>
        </a>

        <a href="#"
           class="flex h-11 w-full max-w-full min-w-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 sm:w-auto sm:px-5">
            <i class="ri-star-line shrink-0"></i>
            <span class="min-w-0 truncate">Оставить отзыв</span>
        </a>

    </div>

</div>
</div>

<style>
    @media (max-width: 767px) {
        .order-show-mobile-safe,
        .order-show-mobile-safe * {
            box-sizing: border-box;
        }

        .order-show-mobile-safe {
            inline-size: 100%;
            max-inline-size: 100vw;
        }
    }
</style>

<link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">

</x-buyer-layout>
