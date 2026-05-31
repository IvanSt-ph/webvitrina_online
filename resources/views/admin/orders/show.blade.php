@extends('admin.layout')

@section('title', 'Заказ ' . $order->number)

@section('content')
@php
    $statuses = [
        \App\Models\Order::STATUS_PENDING => ['label' => 'Ожидает обработки', 'icon' => 'ri-time-line', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'],
        \App\Models\Order::STATUS_PROCESSING => ['label' => 'Принят продавцом', 'icon' => 'ri-user-follow-line', 'class' => 'border-sky-200 bg-sky-50 text-sky-700'],
        \App\Models\Order::STATUS_PAID => ['label' => 'Оплачен', 'icon' => 'ri-bank-card-line', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'],
        \App\Models\Order::STATUS_SHIPPED => ['label' => 'В пути', 'icon' => 'ri-truck-line', 'class' => 'border-blue-200 bg-blue-50 text-blue-700'],
        \App\Models\Order::STATUS_DELIVERED => ['label' => 'Доставлен', 'icon' => 'ri-checkbox-circle-line', 'class' => 'border-green-200 bg-green-50 text-green-700'],
        \App\Models\Order::STATUS_COMPLETED => ['label' => 'Завершён', 'icon' => 'ri-check-double-line', 'class' => 'border-slate-200 bg-slate-50 text-slate-700'],
        \App\Models\Order::STATUS_CANCELED => ['label' => 'Отменён', 'icon' => 'ri-close-circle-line', 'class' => 'border-rose-200 bg-rose-50 text-rose-700'],
    ];
    $current = $statuses[$order->status] ?? ['label' => $order->status, 'icon' => 'ri-information-line', 'class' => 'border-slate-200 bg-slate-50 text-slate-700'];
@endphp

<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-breadcrumbs :items="[
            ['label' => 'Админ', 'href' => route('admin.dashboard')],
            ['label' => 'Заказы', 'href' => route('admin.orders.index')],
            ['label' => '#' . $order->number],
        ]" />
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.users.show', $order->user) }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                <i class="ri-user-line"></i> Покупатель
            </a>
            <a href="{{ route('admin.users.show', $order->seller) }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                <i class="ri-store-2-line"></i> Продавец
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
    @endif

    @if($order->cancellation_requested_at && $order->status !== \App\Models\Order::STATUS_CANCELED)
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <div class="font-semibold">Покупатель запросил отмену {{ $order->cancellation_requested_at->format('d.m.Y H:i') }}</div>
            <div class="mt-1">{{ $order->cancellation_reason }}</div>
        </div>
    @endif

    <header class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="break-all text-2xl font-bold tracking-tight text-slate-950">Заказ #{{ $order->number }}</h1>
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold {{ $current['class'] }}">
                        <i class="{{ $current['icon'] }}"></i>{{ $current['label'] }}
                    </span>
                </div>
                <p class="mt-2 text-sm text-slate-500">
                    ID {{ $order->id }} · создан {{ $order->created_at?->format('d.m.Y H:i') }} · {{ $order->items->sum('quantity') }} товар(ов)
                </p>
            </div>
            <div class="rounded-xl border border-indigo-100 bg-indigo-50 px-5 py-3 text-right">
                <div class="text-xs font-semibold uppercase text-indigo-500">Сумма заказа</div>
                <div class="mt-1 text-2xl font-bold text-indigo-800">{{ $order->formatted_total_price }}</div>
            </div>
        </div>
    </header>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_365px]">
        <div class="min-w-0 space-y-5">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3 sm:px-5">
                    <h2 class="font-bold text-slate-900">Состав заказа</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach($order->items as $item)
                        <div class="flex items-center gap-3 p-4 sm:px-5">
                            @if($item->product?->image)
                                <img src="{{ asset('storage/' . $item->product->image) }}" class="h-14 w-14 shrink-0 rounded-lg object-cover" alt="">
                            @else
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                    <i class="ri-image-line"></i>
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                @if($item->product)
                                    <a href="{{ route('product.show', $item->product->slug ?? $item->product->id) }}" class="block truncate font-semibold text-slate-900 transition hover:text-indigo-700">{{ $item->product->title }}</a>
                                @else
                                    <div class="font-semibold text-slate-500">Удалённый товар</div>
                                @endif
                                <div class="mt-1 text-xs text-slate-500">{{ $item->quantity }} шт. · {{ number_format($item->price, 2, ',', ' ') }} {{ $order->currency }}</div>
                            </div>
                            <div class="shrink-0 text-sm font-bold text-slate-900">{{ number_format($item->total ?: $item->total_price, 2, ',', ' ') }} {{ $order->currency }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="font-bold text-slate-900">Доставка и оплата</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Доставка</dt><dd class="text-right font-semibold text-slate-800">{{ $order->delivery_method ?: 'Не указана' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Оплата</dt><dd class="text-right font-semibold text-slate-800">{{ $order->payment_method ?: 'Не указана' }}</dd></div>
                        <div class="border-t border-slate-100 pt-3">
                            <dt class="text-slate-500">Адрес</dt>
                            <dd class="mt-1 break-words font-semibold text-slate-800">{{ $order->delivery_address ?: ($order->address?->full ?: 'Адрес не заполнен') }}</dd>
                            @if(filled($order->address?->comment))
                                <p class="mt-2 text-xs text-slate-500">{{ $order->address->comment }}</p>
                            @endif
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="font-bold text-slate-900">Участники</h2>
                    <div class="mt-4 space-y-4 text-sm">
                        @foreach([['label' => 'Покупатель', 'user' => $order->user, 'icon' => 'ri-user-line'], ['label' => 'Продавец', 'user' => $order->seller, 'icon' => 'ri-store-2-line']] as $participant)
                            <div class="flex items-start gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><i class="{{ $participant['icon'] }}"></i></div>
                                <div class="min-w-0">
                                    <div class="text-xs text-slate-400">{{ $participant['label'] }}</div>
                                    <a href="{{ route('admin.users.show', $participant['user']) }}" class="font-semibold text-slate-900 hover:text-indigo-700">{{ $participant['user']?->name ?? 'Не найден' }}</a>
                                    <div class="truncate text-xs text-slate-500">{{ $participant['user']?->email }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="font-bold text-slate-900">Диалоги по ситуации</h2>
                <p class="mt-1 text-sm text-slate-500">Marketplace-диалоги по участникам и товарам заказа, а также обращения этих пользователей в поддержку.</p>
                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                    <div class="space-y-2">
                        <h3 class="text-xs font-bold uppercase text-slate-400">Marketplace</h3>
                        @forelse($marketplaceConversations as $conversation)
                            <a href="{{ route('admin.chats.show', $conversation) }}" class="block rounded-xl border border-slate-100 bg-slate-50 p-3 transition hover:border-indigo-200 hover:bg-indigo-50">
                                <div class="text-sm font-semibold text-slate-900">{{ $conversation->product?->title ?? 'Общий диалог' }}</div>
                                <div class="mt-1 truncate text-xs text-slate-500">{{ $conversation->lastMessage?->body ?? 'Нет сообщений' }}</div>
                            </a>
                        @empty
                            <div class="rounded-xl bg-slate-50 p-3 text-sm text-slate-500">Связанных диалогов нет.</div>
                        @endforelse
                    </div>
                    <div class="space-y-2">
                        <h3 class="text-xs font-bold uppercase text-slate-400">Поддержка</h3>
                        @forelse($supportConversations as $conversation)
                            <a href="{{ route('admin.chats.show', $conversation) }}" class="block rounded-xl border border-slate-100 bg-slate-50 p-3 transition hover:border-indigo-200 hover:bg-indigo-50">
                                <div class="text-sm font-semibold text-slate-900">{{ $conversation->buyer?->name ?? 'Пользователь' }}</div>
                                <div class="mt-1 truncate text-xs text-slate-500">{{ $conversation->lastMessage?->body ?? 'Нет сообщений' }}</div>
                            </a>
                        @empty
                            <div class="rounded-xl bg-slate-50 p-3 text-sm text-slate-500">Обращений нет.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-4">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="font-bold text-slate-900">Решение по заказу</h2>
                <p class="mt-1 text-sm text-slate-500">Смена статуса фиксируется в журнале администратора.</p>
                <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" class="mt-4 space-y-3">
                    @csrf
                    <select name="status" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                        @foreach($statuses as $value => $meta)
                            <option value="{{ $value }}" @selected($order->status === $value)>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                    <textarea name="change_reason" rows="3" class="w-full resize-none rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100" placeholder="Комментарий к решению; обязателен при отмене">{{ old('change_reason') }}</textarea>
                    <button class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-700">
                        <i class="ri-save-3-line"></i> Сохранить статус
                    </button>
                </form>
            </section>

            <x-order-timeline :order="$order" />

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="font-bold text-slate-900">Действия админов</h2>
                <div class="mt-4 space-y-3">
                    @forelse($activity as $entry)
                        <div class="border-l-2 border-indigo-100 pl-3 text-sm">
                            <div class="font-semibold text-slate-800">{{ $entry->description }}</div>
                            @if(data_get($entry->meta, 'reason'))
                                <div class="mt-1 text-slate-600">{{ data_get($entry->meta, 'reason') }}</div>
                            @endif
                            <div class="mt-1 text-xs text-slate-400">{{ $entry->admin?->name ?? 'Система' }} · {{ $entry->created_at->format('d.m H:i') }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Решений администратора пока нет.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
