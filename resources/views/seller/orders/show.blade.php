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
        $primaryProduct = $order->items
            ->map(fn ($item) => $item->product)
            ->first(fn ($product) => $product !== null);
        $nextStatus = [
            \App\Models\Order::STATUS_PENDING => \App\Models\Order::STATUS_PROCESSING,
            \App\Models\Order::STATUS_PROCESSING => \App\Models\Order::STATUS_PAID,
            \App\Models\Order::STATUS_PAID => \App\Models\Order::STATUS_SHIPPED,
            \App\Models\Order::STATUS_SHIPPED => \App\Models\Order::STATUS_DELIVERED,
            \App\Models\Order::STATUS_DELIVERED => \App\Models\Order::STATUS_COMPLETED,
        ][$order->status] ?? null;
        $nextActionLabel = [
            \App\Models\Order::STATUS_PENDING => 'Принять заказ',
            \App\Models\Order::STATUS_PROCESSING => 'Отметить как оплаченный',
            \App\Models\Order::STATUS_PAID => 'Передать в доставку',
            \App\Models\Order::STATUS_SHIPPED => 'Отметить доставленным',
            \App\Models\Order::STATUS_DELIVERED => 'Завершить заказ',
        ][$order->status] ?? 'Действий по статусу нет';
        $nextActionHint = [
            \App\Models\Order::STATUS_PENDING => 'Покупатель ждёт, что продавец подтвердит заказ.',
            \App\Models\Order::STATUS_PROCESSING => 'Подходит для оплаты при получении или ручной проверки оплаты.',
            \App\Models\Order::STATUS_PAID => 'После передачи в доставку покупатель увидит, что заказ уже в пути.',
            \App\Models\Order::STATUS_SHIPPED => 'Отметьте доставку, когда заказ прибыл покупателю.',
            \App\Models\Order::STATUS_DELIVERED => 'Завершайте после финального подтверждения.',
        ][$order->status] ?? 'Заказ уже не требует смены статуса.';
        $canCancel = in_array($order->status, [
            \App\Models\Order::STATUS_PENDING,
            \App\Models\Order::STATUS_PROCESSING,
            \App\Models\Order::STATUS_PAID,
        ], true);
    @endphp

    <div class="seller-order-show-safe min-h-screen space-y-6 overflow-x-hidden px-3 py-4 pb-[5.5rem] sm:px-5 sm:py-6 lg:px-6">

        {{-- Верхняя панель --}}
        <div class="grid min-w-0 gap-3 sm:flex sm:items-center sm:justify-between">
            <div class="min-w-0 space-y-1">
                <x-breadcrumbs :items="[
                    ['label' => 'Панель', 'href' => route('seller.cabinet')],
                    ['label' => 'Заказы', 'href' => route('seller.orders.index')],
                    ['label' => 'Заказ ' . ($order->number ?? ('#' . $order->id))],
                ]" />

                <h1 class="truncate text-2xl sm:text-3xl font-bold text-gray-900">
                    Заказ {{ $order->number ?? ('#' . $order->id) }}
                </h1>

                <div class="break-words text-sm text-gray-500">
                    от {{ $order->created_at?->format('d.m.Y H:i') }}
                    • Покупатель: {{ $order->user->name ?? 'Неизвестен' }}
                    (ID: {{ $order->user_id }})
                </div>
            </div>

            <div class="min-w-0 space-y-2 sm:shrink-0 sm:text-right">
                <div class="truncate text-lg font-semibold text-gray-900">
                    {{ $order->formatted_total_price ?? (number_format($order->total_price, 2, ',', ' ') . ' ' . ($order->currency ?? '')) }}
                </div>

                <span class="inline-flex max-w-full items-center truncate px-3 py-1 rounded-full text-xs font-medium {{ $currentStatusClass }}">
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

            {{-- Рабочая панель продавца --}}
<section class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-white to-indigo-50/60 p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Рабочая панель продавца</p>
            <h2 class="mt-1 text-xl font-bold text-slate-950">{{ $nextActionLabel }}</h2>
            <p class="mt-2 max-w-2xl text-sm text-slate-600">{{ $nextActionHint }}</p>
        </div>

        <div class="flex shrink-0 flex-col gap-2 sm:flex-row lg:flex-col xl:flex-row">
            <form method="POST" action="{{ route('seller.orders.chat.buyer', $order) }}">
                @csrf
                <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                        @disabled(! $primaryProduct)>
                    <i class="ri-chat-3-line"></i>
                    Написать покупателю
                </button>
            </form>

            <form method="POST" action="{{ route('support.start') }}">
                @csrf
                <input type="hidden" name="topic" value="Вопрос по заказу {{ $order->number }}">
                <input type="hidden" name="details" value="Заказ {{ $order->number }}, покупатель {{ $order->user->name ?? 'не указан' }}, статус: {{ $order->status_ru }}.">
                <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 sm:w-auto">
                    <i class="ri-customer-service-2-line"></i>
                    Поддержка
                </button>
            </form>
        </div>
    </div>

    <div class="mt-5 grid gap-3 md:grid-cols-3">
        <div class="rounded-xl border border-white/80 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                <i class="ri-flag-line text-indigo-500"></i>
                Следующий шаг
            </div>
            <div class="mt-3">
                @if($nextStatus)
                    <form method="POST" action="{{ route('seller.orders.updateStatus', $order) }}">
                        @csrf
                        <input type="hidden" name="status" value="{{ $nextStatus }}">
                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            {{ $nextActionLabel }}
                            <i class="ri-arrow-right-line"></i>
                        </button>
                    </form>
                @else
                    <p class="rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600">По этому заказу нет доступного следующего шага.</p>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-white/80 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                <i class="ri-user-smile-line text-emerald-500"></i>
                Покупатель ждёт
            </div>
            <p class="mt-3 text-sm text-slate-600">
                @if($order->cancellation_requested_at && $order->status !== \App\Models\Order::STATUS_CANCELED)
                    Решения по отмене заказа.
                @elseif($order->status === \App\Models\Order::STATUS_PENDING)
                    Подтверждения заказа продавцом.
                @elseif($order->status === \App\Models\Order::STATUS_PAID)
                    Передачи товара в доставку.
                @elseif($order->status === \App\Models\Order::STATUS_SHIPPED)
                    Обновления по доставке.
                @else
                    Актуального статуса и ответа при вопросах.
                @endif
            </p>
        </div>

        <div class="rounded-xl border border-white/80 bg-white p-4 shadow-sm">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                <i class="ri-error-warning-line text-rose-500"></i>
                Безопасное действие
            </div>
            <div class="mt-3">
                @if($canCancel)
                    <form method="POST" action="{{ route('seller.orders.updateStatus', $order) }}"
                          onsubmit="return confirm('Вы точно хотите отменить заказ?');">
                        @csrf
                        <input type="hidden" name="status" value="canceled">
                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                            Отменить заказ
                        </button>
                    </form>
                @else
                    <p class="rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600">Отмена недоступна для текущего статуса.</p>
                @endif
            </div>
        </div>
    </div>
</section>

        @if($order->cancellation_requested_at && $order->status !== \App\Models\Order::STATUS_CANCELED)
            <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                <h2 class="font-semibold text-rose-900">Покупатель запросил отмену заказа</h2>
                <p class="mt-1 text-sm text-rose-700">{{ $order->cancellation_requested_at->format('d.m.Y H:i') }}</p>
                <p class="mt-3 rounded-xl bg-white px-3 py-2 text-sm text-slate-700">{{ $order->cancellation_reason }}</p>
                <p class="mt-3 text-sm text-rose-800">Если заказ ещё не отправлен, отмените его в блоке действий ниже или свяжитесь с покупателем.</p>
            </section>
        @endif

        <div class="grid gap-4 xl:grid-cols-[380px_minmax(0,1fr)]">
            {{-- Покупатель --}}
            <div class="min-w-0 overflow-hidden bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                <div class="flex items-start gap-4">
                    <img src="{{ $order->user->avatar_url ?? asset('images/default-avatar.png') }}"
                         class="w-14 h-14 rounded-xl object-cover border shadow-sm" alt="avatar">

                    <div class="min-w-0 flex-1 space-y-1">
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
                            <div class="min-w-0 text-xs text-gray-500 flex items-center gap-1">
                                <i class="ri-mail-line text-gray-500 text-sm"></i>
                                <span class="min-w-0 break-all">{{ $order->user->email }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('seller.orders.chat.buyer', $order) }}" class="mt-4">
                    @csrf
                    <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
                            @disabled(! $primaryProduct)>
                        <i class="ri-chat-3-line"></i>
                        Написать покупателю
                    </button>
                </form>
            </div>

            <x-order-timeline :order="$order" :compact="true" />
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

    <div class="break-words text-xs text-gray-500 mt-2">
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
        <div class="min-w-0 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-bold text-gray-900">
                        Товары в заказе
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">Фото, артикул, остаток и сумма по каждой позиции.</p>
                </div>
                <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                    {{ $itemsCount }} шт.
                </span>
            </div>

            <div class="divide-y divide-gray-100">
                @foreach($order->items as $item)
                    @php
                        $product = $item->product;
                        $itemTitle = $product->title ?? 'Товар удалён';
                        $shortItemTitle = \Illuminate\Support\Str::limit($itemTitle, 18);
                        $productEditUrl = $product ? route('seller.products.edit', $product) : null;
                    @endphp
                    <div class="grid min-w-0 gap-4 px-4 py-5 sm:grid-cols-[112px_minmax(0,1fr)] lg:grid-cols-[128px_minmax(0,1fr)_auto] lg:items-center sm:gap-5 sm:px-5">
                        <div class="h-28 w-28 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm lg:h-32 lg:w-32">
                            @if($product)
                                <a href="{{ $productEditUrl }}" class="block h-full w-full" title="Открыть товар продавца">
                                    <img src="{{ $product->image_thumb_url }}"
                                         alt="{{ $itemTitle }}"
                                         class="h-full w-full object-cover">
                                </a>
                            @else
                                <div class="flex h-full w-full items-center justify-center text-slate-300">
                                    <i class="ri-image-line text-2xl"></i>
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 space-y-2">
                            <div class="truncate text-sm font-semibold text-gray-900 sm:hidden" title="{{ $itemTitle }}">
                                {{ $shortItemTitle }}
                            </div>
                            <div class="hidden sm:block">
                                <div
                                    class="overflow-hidden text-sm font-semibold text-gray-900"
                                    style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow-wrap:anywhere;word-break:break-word;"
                                    title="{{ $itemTitle }}"
                                >
                                    {{ $itemTitle }}
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                <span class="rounded-full bg-slate-100 px-2 py-1">ID товара: {{ $item->product_id }}</span>
                                @if($product?->sku)
                                    <span class="rounded-full bg-slate-100 px-2 py-1">SKU: {{ $product->sku }}</span>
                                @endif
                                @if($product)
                                    <span class="rounded-full {{ $product->stock <= 0 ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }} px-2 py-1">Остаток: {{ $product->stock }}</span>
                                @endif
                            </div>

                            @if($productEditUrl)
                                <a href="{{ $productEditUrl }}" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                                    <i class="ri-external-link-line"></i>
                                    Открыть товар
                                </a>
                            @endif
                        </div>

                        <div class="grid min-w-0 gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-4 text-sm sm:col-span-2 sm:grid-cols-3 lg:col-span-1 lg:min-w-[320px] lg:items-center">
                            <div>
                                <div class="text-xs text-gray-400">Кол-во</div>
                                <div class="font-semibold text-gray-900">{{ $item->quantity }}</div>
                            </div>

                            <div>
                                <div class="text-xs text-gray-400">Цена</div>
                                <div class="font-semibold text-gray-900">
                                    {{ number_format($item->price, 2, ',', ' ') }} {{ $order->currency ?? '' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-gray-400">Сумма</div>
                                <div class="font-semibold text-gray-900 sm:text-right">
                                    {{ number_format($item->total, 2, ',', ' ') }} {{ $order->currency ?? '' }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="px-5 py-4 border-t border-gray-100 grid grid-cols-[auto_minmax(0,1fr)] items-center gap-3 sm:flex sm:justify-end">
                <div class="text-sm text-gray-500">
                    Итого:
                </div>
                <div class="truncate text-right text-lg font-semibold text-gray-900">
                    {{ $order->formatted_total_price ?? (number_format($order->total_price, 2, ',', ' ') . ' ' . ($order->currency ?? '')) }}
                </div>
            </div>
        </div>

    </div>

    @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
