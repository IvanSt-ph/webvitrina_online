{{-- resources/views/seller/orders/index.blade.php --}}
<x-seller-layout title="Заказы">
    @php
        $tabs = [
            null => ['label' => 'Все', 'icon' => 'ri-inbox-line'],
            \App\Models\Order::STATUS_PENDING => ['label' => 'Ожидают', 'icon' => 'ri-time-line'],
            \App\Models\Order::STATUS_PROCESSING => ['label' => 'Приняты', 'icon' => 'ri-user-follow-line'],
            \App\Models\Order::STATUS_READY_FOR_PICKUP => ['label' => 'Готовы к самовывозу', 'icon' => 'ri-store-2-line'],
            \App\Models\Order::STATUS_PAID => ['label' => 'Оплачены', 'icon' => 'ri-bank-card-line'],
            \App\Models\Order::STATUS_SHIPPED => ['label' => 'В пути', 'icon' => 'ri-truck-line'],
            \App\Models\Order::STATUS_DELIVERED => ['label' => 'Доставлены', 'icon' => 'ri-checkbox-circle-line'],
            \App\Models\Order::STATUS_COMPLETED => ['label' => 'Завершены', 'icon' => 'ri-check-double-line'],
            \App\Models\Order::STATUS_CANCELED => ['label' => 'Отменённые', 'icon' => 'ri-close-circle-line'],
        ];

        $statusColors = [
            \App\Models\Order::STATUS_PENDING => 'border-amber-200 bg-amber-50 text-amber-700',
            \App\Models\Order::STATUS_PROCESSING => 'border-sky-200 bg-sky-50 text-sky-700',
            \App\Models\Order::STATUS_READY_FOR_PICKUP => 'border-violet-200 bg-violet-50 text-violet-700',
            \App\Models\Order::STATUS_PAID => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            \App\Models\Order::STATUS_SHIPPED => 'border-blue-200 bg-blue-50 text-blue-700',
            \App\Models\Order::STATUS_DELIVERED => 'border-green-200 bg-green-50 text-green-700',
            \App\Models\Order::STATUS_COMPLETED => 'border-slate-200 bg-slate-50 text-slate-700',
            \App\Models\Order::STATUS_CANCELED => 'border-rose-200 bg-rose-50 text-rose-700',
        ];

        $totalOrders = $statusCounts->sum();
        $activeStatus = $status ?? null;
        $activeAction = $action ?? null;
    @endphp

    <div class="min-h-screen w-full bg-white px-3 py-4 pb-28 text-neutral-900 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">
        <div class="w-full space-y-5 sm:space-y-6">
            <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                        <i class="ri-store-2-line"></i>
                        Кабинет продавца
                    </div>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">Заказы</h1>
                    <p class="mt-1 max-w-2xl text-sm text-neutral-500">
                        Покупатели, состав заказов, суммы и действия — в одном рабочем списке.
                    </p>
                </div>

                @if($orders->count())
                    <div class="w-fit rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm text-neutral-600">
                        Показано <span class="font-semibold text-neutral-900">{{ $orders->firstItem() }}–{{ $orders->lastItem() }}</span>
                        из <span class="font-semibold text-neutral-900">{{ $orders->total() }}</span>
                    </div>
                @endif
            </header>

            <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                    <div class="flex items-center justify-between text-xs text-neutral-500 sm:text-sm">
                        <span>Всего</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><i class="ri-file-list-3-line"></i></span>
                    </div>
                    <div class="mt-2 text-xl font-bold text-neutral-950 sm:text-2xl">{{ number_format($totalOrders, 0, ',', ' ') }}</div>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4">
                    <div class="flex items-center justify-between text-xs text-amber-700 sm:text-sm">
                        <span>Ждут реакции</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/80"><i class="ri-time-line"></i></span>
                    </div>
                    <div class="mt-2 text-xl font-bold text-amber-800 sm:text-2xl">{{ number_format(($statusCounts[\App\Models\Order::STATUS_PENDING] ?? 0) + ($statusCounts[\App\Models\Order::STATUS_PROCESSING] ?? 0), 0, ',', ' ') }}</div>
                </div>
                <div class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4">
                    <div class="flex items-center justify-between text-xs text-blue-700 sm:text-sm">
                        <span>Готовы к выдаче / в пути</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/80"><i class="ri-truck-line"></i></span>
                    </div>
                    <div class="mt-2 text-xl font-bold text-blue-800 sm:text-2xl">{{ number_format(($statusCounts[\App\Models\Order::STATUS_READY_FOR_PICKUP] ?? 0) + ($statusCounts[\App\Models\Order::STATUS_SHIPPED] ?? 0), 0, ',', ' ') }}</div>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4">
                    <div class="flex items-center justify-between text-xs text-emerald-700 sm:text-sm">
                        <span>Завершены</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white/80"><i class="ri-check-double-line"></i></span>
                    </div>
                    <div class="mt-2 text-xl font-bold text-emerald-800 sm:text-2xl">{{ number_format($statusCounts[\App\Models\Order::STATUS_COMPLETED] ?? 0, 0, ',', ' ') }}</div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
                <div class="border-b border-neutral-100 p-3 sm:p-4">
                    <form method="GET" action="{{ route('seller.orders.index') }}" class="grid gap-3 lg:grid-cols-[1fr_220px_220px_auto]">
                        <label class="relative block">
                            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400"></i>
                            <input
                                type="search"
                                name="q"
                                value="{{ $search }}"
                                placeholder="Номер заказа, имя или email покупателя"
                                class="wv-field h-11 pl-10"
                            >
                        </label>

                        <select
                            name="status"
                            class="wv-field h-11 pr-9"
                        >
                            <option value="">Все статусы</option>
                            @foreach($tabs as $key => $tab)
                                @continue($key === null)
                                <option value="{{ $key }}" @selected($activeStatus === $key)>{{ $tab['label'] }}</option>
                            @endforeach
                        </select>

                        <select
                            name="action"
                            class="wv-field h-11 pr-9"
                        >
                            <option value="">Все действия</option>
                            <option value="needs_action" @selected($activeAction === 'needs_action')>Требуют ответа</option>
                            <option value="cancel_request" @selected($activeAction === 'cancel_request')>Запросы отмены</option>
                        </select>

                        <button type="submit" class="wv-btn-primary inline-flex h-11 items-center justify-center gap-2 px-5">
                            <i class="ri-filter-3-line"></i>
                            Применить
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto border-b border-neutral-100 px-3 py-2.5">
                    <div class="flex min-w-max items-center gap-2">
                        @foreach($tabs as $key => $tab)
                            @php
                                $isActive = ($key === '' && $activeStatus === null && $activeAction === null) || ($activeStatus === $key);
                                $count = $key === null ? $totalOrders : ($statusCounts[$key] ?? 0);
                                $href = $key === null
                                    ? route('seller.orders.index', array_filter(['q' => $search]))
                                    : route('seller.orders.index', array_filter(['q' => $search, 'status' => $key]));
                            @endphp
                            <a href="{{ $href }}"
                               class="wv-ui-pill inline-flex min-h-11 items-center gap-2 rounded-xl px-3 py-2 text-sm"
                               @if($isActive) aria-current="true" @endif>
                                <i class="{{ $tab['icon'] }}"></i>
                                <span>{{ $tab['label'] }}</span>
                                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold">{{ $count }}</span>
                            </a>
                        @endforeach
                        @foreach([
                            'needs_action' => ['label' => 'Требуют ответа', 'icon' => 'ri-alarm-warning-line'],
                            'cancel_request' => ['label' => 'Запросы отмены', 'icon' => 'ri-close-circle-line'],
                        ] as $key => $tab)
                            @php
                                $isActive = $activeAction === $key;
                                $href = route('seller.orders.index', array_filter(['q' => $search, 'action' => $key]));
                            @endphp
                            <a href="{{ $href }}"
                               class="wv-ui-pill inline-flex min-h-11 items-center gap-2 rounded-xl px-3 py-2 text-sm"
                               @if($isActive) aria-current="true" @endif>
                                <i class="{{ $tab['icon'] }}"></i>
                                <span>{{ $tab['label'] }}</span>
                                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold">{{ $actionCounts[$key] ?? 0 }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-3 bg-neutral-50/60 p-3 sm:p-4">
                    @forelse($orders as $order)
                        @php
                            $itemsCount = $order->items->sum('quantity');
                            $firstItem = $order->items->first();
                            $colorClass = $statusColors[$order->status] ?? 'border-neutral-200 bg-neutral-50 text-neutral-700';
                        @endphp

                        <a href="{{ route('seller.orders.show', $order) }}"
                           class="grid gap-4 rounded-xl border border-neutral-200 bg-white p-4 transition hover:border-brand-200 hover:shadow-md lg:grid-cols-[1.1fr_1fr_180px_150px] lg:items-center">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-neutral-950">#{{ $order->number }}</span>
                                    <span class="rounded-full border {{ $colorClass }} px-2 py-0.5 text-xs font-medium">{{ $order->status_ru }}</span>
                                    @if($order->workflow_version === \App\Models\Order::WORKFLOW_PICKUP)
                                        <span class="text-xs text-neutral-600">{{ $order->pickup_payment_status_label }}</span>
                                        <span class="text-xs text-neutral-600">{{ $order->buyer_confirmed_at ? 'Получение подтверждено покупателем' : 'Получение не подтверждено покупателем' }}</span>
                                    @endif
                                    @if($order->cancellation_requested_at && $order->status !== \App\Models\Order::STATUS_CANCELED)
                                        <span class="rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700">Запрос отмены</span>
                                    @elseif($order->status === \App\Models\Order::STATUS_PENDING)
                                        <span class="rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">Нужен ответ</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-sm text-neutral-500">
                                    {{ $order->created_at?->format('d.m.Y H:i') }} · ID {{ $order->id }}
                                </div>
                            </div>

                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-neutral-800">
                                    {{ $order->buyer_name }}
                                </div>
                                <div class="mt-1 truncate text-xs text-neutral-500">
                                    {{ $firstItem?->historical_title ?? 'Название не сохранено' }}
                                    @if($itemsCount > 1)
                                        · ещё {{ $itemsCount - 1 }}
                                    @endif
                                </div>
                            </div>

                            <div class="text-sm text-neutral-600 lg:text-right">
                                <span class="font-semibold text-neutral-900">{{ $itemsCount }}</span>
                                {{ trans_choice('товар|товара|товаров', $itemsCount) }}
                            </div>

                            <div class="flex items-center justify-between gap-3 lg:justify-end">
                                <span class="whitespace-nowrap text-base font-bold text-neutral-950">
                                    {{ $order->formatted_total_price ?? (number_format($order->total_price, 2, ',', ' ') . ' ' . ($order->currency ?? '')) }}
                                </span>
                                <i class="ri-arrow-right-s-line text-xl text-neutral-400"></i>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-xl border border-dashed border-neutral-200 bg-white px-6 py-12 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-2xl text-brand-500">
                                <i class="ri-file-list-3-line"></i>
                            </div>
                            <h2 class="mt-4 text-lg font-semibold text-neutral-900">Заказов не найдено</h2>
                            <p class="mt-1 text-sm text-neutral-500">Попробуйте снять фильтр или изменить строку поиска.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            @if($orders->hasPages())
                <div>
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>

    @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
