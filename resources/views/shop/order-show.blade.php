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

    $shop = $order->seller?->shop;
    $canConfirmDelivery = $order->status === \App\Models\Order::STATUS_SHIPPED;
    $canRequestCancellation = in_array($order->status, [
        \App\Models\Order::STATUS_PENDING,
        \App\Models\Order::STATUS_PROCESSING,
        \App\Models\Order::STATUS_PAID,
    ], true) && ! $order->cancellation_requested_at;
    $canReview = in_array($order->status, [
        \App\Models\Order::STATUS_DELIVERED,
        \App\Models\Order::STATUS_COMPLETED,
    ], true);
    $chatProducts = $order->items
        ->pluck('product')
        ->filter(fn ($product) => $product && ! $product->trashed())
        ->unique('id')
        ->values();
    $primaryChatProduct = $chatProducts->first();
    $nextActionTitle = [
        \App\Models\Order::STATUS_PENDING => 'Ожидайте подтверждения продавца',
        \App\Models\Order::STATUS_PROCESSING => 'Договоритесь с продавцом о передаче',
        \App\Models\Order::STATUS_PAID => 'Ожидайте отправки заказа',
        \App\Models\Order::STATUS_SHIPPED => 'Проверьте получение товара',
        \App\Models\Order::STATUS_DELIVERED => 'Оставьте отзыв о покупке',
        \App\Models\Order::STATUS_COMPLETED => 'Заказ завершён',
        \App\Models\Order::STATUS_CANCELED => 'Заказ отменён',
    ][$order->status] ?? 'Следите за статусом заказа';
    $nextActionHint = [
        \App\Models\Order::STATUS_PENDING => 'Продавец должен принять заказ. Если нужно уточнить детали, напишите ему в чат.',
        \App\Models\Order::STATUS_PROCESSING => 'Согласуйте срок, место передачи, доставку и оплату. Все договорённости лучше держать в чате.',
        \App\Models\Order::STATUS_PAID => 'Продавец готовит заказ к передаче или доставке. При вопросах напишите ему.',
        \App\Models\Order::STATUS_SHIPPED => 'Когда товар будет у вас, подтвердите получение. Если что-то не так, сначала напишите продавцу или откройте спор.',
        \App\Models\Order::STATUS_DELIVERED => 'Покупка доставлена. Можно оценить товар и продавца.',
        \App\Models\Order::STATUS_COMPLETED => 'Все основные действия по заказу выполнены.',
        \App\Models\Order::STATUS_CANCELED => 'Если отмена ошибочная или нужен возврат, обратитесь в поддержку.',
    ][$order->status] ?? 'Проверяйте обновления и сообщения по заказу.';
@endphp


<div class="order-show-mobile-safe wv-page min-h-screen w-full max-w-full overflow-x-hidden pb-[5.5rem]" style="max-width:100vw;">
<div class="w-full max-w-none space-y-5 overflow-hidden sm:space-y-6">

    <!-- 🔙 Навигация -->
    <div class="grid w-full min-w-0 gap-2 sm:flex sm:items-center sm:justify-between">
        <x-breadcrumbs :items="[
            ['label' => 'Кабинет', 'href' => route('cabinet')],
            ['label' => 'Заказы', 'href' => route('orders.index')],
            ['label' => 'Заказ ' . $order->number],
        ]" />

        <span class="min-w-0 truncate text-xs text-slate-400">
            Создан: {{ $order->created_at->format('d.m.Y H:i') }}
        </span>
    </div>


    <!-- 🧾 Заголовок заказа -->
    <div class="wv-page-header grid w-full max-w-full min-w-0 overflow-hidden sm:flex">

        <div class="flex min-w-0 items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-xl text-white shadow-sm shadow-indigo-600/20">
                <i class="ri-shopping-bag-3-line text-xl"></i>
            </div>
            <div class="min-w-0">
            <h1 class="truncate text-2xl font-bold text-slate-950">
                Заказ {{ $order->number }}
            </h1>

            <div class="mt-1 flex min-w-0 flex-wrap items-center gap-2 text-sm text-slate-500">
                Статус:
                <x-status-badge :status="$order->status" class="max-w-full truncate px-2 sm:px-3" />
            </div>
            </div>
        </div>

        <div class="min-w-0 sm:shrink-0 sm:text-right">
            <div class="text-sm text-slate-500">Итоговая сумма:</div>
            <div class="mt-1 truncate text-2xl font-bold text-slate-950">
                {{ number_format($order->total_price, 2, ',', ' ') }} {{ $order->currency }}
            </div>
        </div>
    </div>


    <!-- 🔵 Прогресс бар (6 шагов) -->
    <div class="wv-card w-full max-w-full overflow-hidden p-4 sm:p-5">

        <div class="sm:hidden">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Статус заказа</div>
                    <div class="mt-1 text-base font-bold text-slate-950">{{ $stepLabels[$active] ?? $order->status_ru }}</div>
                </div>
                <div class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-bold text-indigo-700">{{ $active }}/6</div>
            </div>
            <div class="mt-4 grid grid-cols-6 gap-1">
                @foreach($stepLabels as $step => $text)
                    <div class="h-2 rounded-full {{ $step <= $active ? 'bg-indigo-600' : 'bg-slate-200' }}"></div>
                @endforeach
            </div>
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


    <x-order-timeline :order="$order" />

    <section class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-white to-indigo-50/60 p-4 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Что делать дальше</p>
                <h2 class="mt-1 text-xl font-bold text-slate-950">{{ $nextActionTitle }}</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-600">{{ $nextActionHint }}</p>
            </div>

            <div class="flex shrink-0 flex-col gap-2 sm:flex-row">
                @if($canConfirmDelivery)
                    <form method="POST" action="{{ route('orders.confirmDelivery', $order) }}" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 sm:w-auto">
                            <i class="ri-checkbox-circle-line"></i>
                            Подтвердить получение
                        </button>
                    </form>
                @elseif($canReview)
                    <a href="{{ $order->items->first()?->product ? route('product.show', $order->items->first()->product->slug) . '#reviews' : route('orders.index') }}"
                       class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                        <i class="ri-star-line"></i>
                        Оставить отзыв
                    </a>
                @elseif($primaryChatProduct)
                    <form method="POST" action="{{ route('orders.chat.product', [$order, $primaryChatProduct]) }}" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 sm:w-auto">
                            <i class="ri-chat-3-line"></i>
                            Написать продавцу
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('orders.support', $order) }}" class="w-full sm:w-auto">
                        @csrf
                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 sm:w-auto">
                            <i class="ri-customer-service-2-line"></i>
                            Поддержка
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </section>


    <!-- ℹ Информация магазина / доставка / оплата -->
    <div class="grid w-full min-w-0 gap-4 overflow-hidden sm:grid-cols-3 sm:gap-6">

<!-- Магазин -->
<div class="wv-card min-w-0 p-4 sm:p-6">
    <h3 class="mb-3 flex items-center gap-2 font-semibold text-slate-950">
        <i class="ri-store-2-line text-indigo-500"></i>
        Продавец
    </h3>

    @if($order->seller)
        <p class="break-words text-sm font-semibold text-slate-800">{{ $shop?->name ?? $order->seller->name }}</p>
        @if($shop)
            <a href="{{ route('seller.show', $shop->slug) }}" class="mt-2 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-700">
                Открыть магазин
            </a>
        @endif
    @else
        <p class="text-sm italic text-slate-400">Продавец не найден</p>
    @endif
</div>


        <!-- Доставка -->
        <div class="wv-card min-w-0 max-w-full overflow-hidden p-4 sm:p-6">
            <h3 class="mb-3 flex items-center gap-2 font-semibold text-slate-950">
                <i class="ri-truck-line text-indigo-500"></i>
                Доставка
            </h3>

            @if($addressParts->isNotEmpty())
                <p class="mb-2 text-sm font-semibold text-slate-800">
                    {{ $order->delivery_method_label }}
                </p>
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
                <p class="text-sm font-semibold text-slate-800">{{ $order->delivery_method_label }}</p>
                <p class="mt-1 text-sm text-gray-400">Адрес не указан</p>
            @endif
            <p class="mt-3 text-xs text-slate-500">Стоимость и сроки подтверждает продавец.</p>
        </div>

        <!-- Оплата -->
        <div class="wv-card min-w-0 max-w-full overflow-hidden p-4 sm:p-6">
            <h3 class="mb-3 flex items-center gap-2 font-semibold text-slate-950">
                <i class="ri-bank-card-line text-indigo-500"></i>
                Оплата
            </h3>
            <p class="break-words text-sm text-slate-700">
                {{ $order->payment_method_label }}
            </p>

            @if($order->paid_at)
                <p class="text-sm text-green-600 mt-1">Оплачено в {{ $order->paid_at }}</p>
            @else
                <p class="text-sm text-slate-400">Онлайн-платёж на сайте не выполнялся</p>
            @endif
        </div>

    </div>


    <!-- 🛒 Состав заказа -->
    <div class="wv-card w-full max-w-full overflow-hidden">

        <div class="px-4 sm:px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-slate-950">Товары в заказе</h3>
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

        <div class="grid min-w-0 grid-cols-[auto_minmax(0,1fr)] items-center gap-3 border-t border-slate-100 bg-slate-50/80 px-4 py-4 sm:px-6">
            <div class="text-sm text-slate-500">Итого:</div>
            <div class="min-w-0 truncate text-right text-xl font-bold text-slate-950">
                {{ number_format($order->total_price, 2, ',', ' ') }} ₽
            </div>
        </div>

    </div>


    <!-- 🎯 Действия по заказу -->
    <div class="grid w-full min-w-0 max-w-full grid-cols-1 gap-3 overflow-hidden sm:flex sm:flex-row sm:flex-wrap">

        <a href="{{ route('orders.index') }}"
           class="flex h-11 w-full max-w-full min-w-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition-all duration-200 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 sm:w-auto sm:px-5">
            <i class="ri-arrow-left-line shrink-0"></i>
            <span class="min-w-0 truncate">Назад</span>
        </a>

        @if($chatProducts->count() === 1)
            <form method="POST" action="{{ route('orders.chat.product', [$order, $chatProducts->first()]) }}" class="w-full sm:w-auto">
                @csrf
                <button type="submit"
                        class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 sm:px-5">
                    <i class="ri-chat-3-line shrink-0"></i>
                    <span>Написать продавцу</span>
                </button>
            </form>
        @elseif($chatProducts->count() > 1)
            <a href="#order-product-chats"
               class="flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 sm:w-auto sm:px-5">
                <i class="ri-chat-3-line shrink-0"></i>
                <span>Написать о товаре</span>
            </a>
        @endif

        <form method="POST" action="{{ route('orders.support', $order) }}" class="w-full sm:w-auto">
            @csrf
            <button type="submit"
                    class="flex h-11 w-full max-w-full min-w-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 sm:px-5">
                <i class="ri-customer-service-2-line shrink-0"></i>
                <span>Обратиться в поддержку</span>
            </button>
        </form>

        @if($canConfirmDelivery)
            <form method="POST" action="{{ route('orders.confirmDelivery', $order) }}" class="w-full sm:w-auto">
                @csrf
                <button type="submit"
                        class="flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100 sm:px-5">
                    <i class="ri-checkbox-circle-line shrink-0"></i>
                    <span>Подтвердить получение</span>
                </button>
            </form>
        @endif

    </div>

    @if($order->cancellation_requested_at)
        <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm sm:rounded-2xl sm:p-6">
            <h3 class="font-semibold text-amber-900">Запрос на отмену отправлен</h3>
            <p class="mt-1 text-sm text-amber-800">Отправлен {{ $order->cancellation_requested_at->format('d.m.Y H:i') }}. Продавец или поддержка рассмотрят запрос.</p>
            @if(filled($order->cancellation_reason))
                <p class="mt-3 rounded-xl bg-white/80 px-3 py-2 text-sm text-slate-700">{{ $order->cancellation_reason }}</p>
            @endif
        </section>
    @elseif($canRequestCancellation)
        <section class="wv-card p-4 sm:p-6">
            <h3 class="font-semibold text-slate-950">Нужно отменить заказ?</h3>
            <p class="mt-1 text-sm text-slate-500">Пока товар не отправлен, можно направить продавцу запрос на отмену.</p>
            <form method="POST" action="{{ route('orders.requestCancellation', $order) }}" class="mt-4 max-w-xl space-y-3">
                @csrf
                <textarea name="cancellation_reason" rows="3" required maxlength="700"
                          placeholder="Напишите причину отмены"
                          class="w-full rounded-xl border border-gray-200 px-3 py-2 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">{{ old('cancellation_reason') }}</textarea>
                @error('cancellation_reason')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
                <button class="inline-flex h-11 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                    Запросить отмену
                </button>
            </form>
        </section>
    @endif

    @php $openDispute = $order->openDispute; @endphp
    @if($openDispute)
        <section class="rounded-xl border border-rose-200 bg-rose-50 p-4 shadow-sm sm:rounded-2xl sm:p-6">
            <h3 class="font-semibold text-rose-900">Спор открыт</h3>
            <p class="mt-1 text-sm text-rose-800">
                Причина: {{ $openDispute->reason }}. Поддержка проверит заказ, переписку и данные продавца.
            </p>
            @if($openDispute->details)
                <p class="mt-3 rounded-xl bg-white/80 px-3 py-2 text-sm text-slate-700">{{ $openDispute->details }}</p>
            @endif
        </section>
    @else
        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:rounded-2xl sm:p-6">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h3 class="font-semibold text-slate-900">Возникла проблема с заказом?</h3>
                    <p class="mt-1 max-w-2xl text-sm text-slate-500">
                        Если отмены или обычного сообщения продавцу недостаточно, откройте спор. Его увидят продавец и поддержка.
                    </p>
                </div>
                <details class="w-full lg:max-w-md">
                    <summary class="cursor-pointer rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-center text-sm font-semibold text-rose-700">
                        Открыть спор
                    </summary>
                    <form method="POST" action="{{ route('orders.disputes.store', $order) }}" class="mt-3 space-y-3 rounded-xl border border-slate-100 bg-slate-50 p-3">
                        @csrf
                        <select name="reason" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                            <option value="">Выберите причину</option>
                            <option value="Товар не получен">Товар не получен</option>
                            <option value="Товар не соответствует описанию">Товар не соответствует описанию</option>
                            <option value="Проблема с оплатой">Проблема с оплатой</option>
                            <option value="Продавец не отвечает">Продавец не отвечает</option>
                            <option value="Другое">Другое</option>
                        </select>
                        <textarea name="details" rows="3" maxlength="1200" placeholder="Опишите ситуацию для поддержки"
                                  class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"></textarea>
                        <button class="inline-flex h-10 items-center justify-center rounded-xl bg-rose-600 px-4 text-sm font-semibold text-white hover:bg-rose-700">
                            Отправить спор
                        </button>
                    </form>
                </details>
            </div>
        </section>
    @endif

    @if($chatProducts->count() > 1)
        <section id="order-product-chats" class="w-full max-w-full overflow-hidden rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 shadow-sm sm:rounded-2xl sm:p-6">
            <h3 class="font-semibold text-gray-900">Написать продавцу о товаре</h3>
            <p class="mt-1 text-sm text-gray-500">Выберите позицию: её карточка и ссылка сразу появятся в чате.</p>
            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                @foreach($chatProducts as $product)
                    <form method="POST" action="{{ route('orders.chat.product', [$order, $product]) }}">
                        @csrf
                        <button type="submit"
                                class="flex h-12 w-full min-w-0 items-center gap-2 rounded-xl border border-indigo-100 bg-white px-3 text-left text-sm font-medium text-indigo-700 transition hover:bg-indigo-100">
                            <i class="ri-chat-3-line shrink-0"></i>
                            <span class="truncate">{{ $product->title }}</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </section>
    @endif

    @if($canReview)
        <section class="wv-card w-full max-w-full overflow-hidden p-4 sm:p-6">
            <h3 class="font-semibold text-slate-950">Оставить отзыв о покупке</h3>
            <p class="mt-1 text-sm text-slate-500">Выберите товар, чтобы оценить его на странице товара.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach($order->items as $item)
                    @if($item->product)
                        <a href="{{ route('product.show', $item->product->slug) }}#reviews"
                           class="inline-flex h-10 max-w-full items-center gap-2 rounded-xl border border-indigo-100 bg-indigo-50 px-3 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                            <i class="ri-star-line shrink-0"></i>
                            <span class="truncate">{{ $item->product->title }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    <section class="w-full max-w-full overflow-hidden rounded-xl border border-indigo-100 bg-white p-4 shadow-sm sm:rounded-2xl sm:p-6">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h3 class="font-semibold text-gray-900">Продолжить покупки</h3>
                <p class="mt-1 text-sm text-gray-500">Похожие товары, магазин продавца и чат всегда под рукой.</p>
            </div>
            @if($shop)
                <a href="{{ route('seller.show', $shop->slug) }}" class="hidden text-sm font-semibold text-indigo-600 hover:text-indigo-700 sm:inline-flex">
                    Магазин продавца
                </a>
            @endif
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @forelse(($continueProducts ?? collect()) as $product)
                <a href="{{ route('product.show', $product->slug) }}" class="group rounded-xl border border-slate-100 bg-slate-50 p-3 transition hover:border-indigo-200 hover:bg-indigo-50">
                    <img src="{{ $product->image_thumb_url }}" alt="{{ $product->title }}" class="h-28 w-full rounded-lg object-cover">
                    <div class="mt-2 line-clamp-2 text-sm font-semibold text-slate-900 group-hover:text-indigo-700">{{ $product->title }}</div>
                    <div class="mt-1 text-sm font-bold text-indigo-700">{{ number_format($product->price, 0, ',', ' ') }} ₽</div>
                </a>
            @empty
                <div class="rounded-xl border border-dashed border-slate-200 p-4 text-sm text-slate-500 sm:col-span-2 lg:col-span-4">
                    Похожих товаров пока нет. Можно вернуться на витрину или написать продавцу по заказу.
                </div>
            @endforelse
        </div>
        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
            <a href="{{ route('home') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">
                <i class="ri-store-3-line"></i>
                На витрину
            </a>
            @if($shop)
                <a href="{{ route('seller.show', $shop->slug) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 sm:hidden">
                    <i class="ri-store-2-line"></i>
                    Магазин продавца
                </a>
            @endif
        </div>
    </section>

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
