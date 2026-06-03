@extends('admin.layout')

@section('title', 'Заказы')

@section('content')
@php
    $currentStatus = request('status');
    $search = request('q');
    $dateFrom = request('date_from');
    $dateTo = request('date_to');
    $currentFocus = request('focus');
    $currentSort = $sort ?? request('sort', 'latest');

    $tabs = [
        null => ['label' => 'Все', 'icon' => 'ri-inbox-line'],
        \App\Models\Order::STATUS_PENDING => ['label' => 'Ожидают', 'icon' => 'ri-time-line'],
        \App\Models\Order::STATUS_PROCESSING => ['label' => 'Приняты', 'icon' => 'ri-user-follow-line'],
        \App\Models\Order::STATUS_PAID => ['label' => 'Оплачены', 'icon' => 'ri-bank-card-line'],
        \App\Models\Order::STATUS_SHIPPED => ['label' => 'В пути', 'icon' => 'ri-truck-line'],
        \App\Models\Order::STATUS_DELIVERED => ['label' => 'Доставлены', 'icon' => 'ri-checkbox-circle-line'],
        \App\Models\Order::STATUS_COMPLETED => ['label' => 'Завершены', 'icon' => 'ri-check-double-line'],
        \App\Models\Order::STATUS_CANCELED => ['label' => 'Отменённые', 'icon' => 'ri-close-circle-line'],
    ];

    $statusColors = [
        \App\Models\Order::STATUS_PENDING => 'border-amber-200 bg-amber-50 text-amber-700',
        \App\Models\Order::STATUS_PROCESSING => 'border-sky-200 bg-sky-50 text-sky-700',
        \App\Models\Order::STATUS_PAID => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        \App\Models\Order::STATUS_SHIPPED => 'border-blue-200 bg-blue-50 text-blue-700',
        \App\Models\Order::STATUS_DELIVERED => 'border-green-200 bg-green-50 text-green-700',
        \App\Models\Order::STATUS_COMPLETED => 'border-slate-200 bg-slate-50 text-slate-700',
        \App\Models\Order::STATUS_CANCELED => 'border-rose-200 bg-rose-50 text-rose-700',
    ];

    $statusLabels = collect($tabs)->mapWithKeys(fn ($tab, $key) => [$key => $tab['label']])->all();
    $totalOrders = $statusCounts->sum();
    $filteredTotal = $summary['total'] ?? $orders->total();
    $activeCount = $summary['active'] ?? 0;
    $cancelRequestsCount = $summary['cancel_requests'] ?? 0;
    $stuckCount = $summary['stuck'] ?? 0;
    $attentionCount = $summary['attention'] ?? 0;

    $focusLabels = [
        'attention' => 'Требуют внимания',
        'active' => 'Активные процессы',
        'cancel_requests' => 'Запросы отмены',
        'stuck' => 'Зависшие заказы',
    ];

    $baseFilters = array_filter([
        'q' => $search,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'focus' => $currentFocus,
        'sort' => $currentSort !== 'latest' ? $currentSort : null,
    ], fn ($value) => filled($value));

    $focusBaseFilters = array_filter([
        'q' => $search,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'status' => $currentStatus,
        'sort' => $currentSort !== 'latest' ? $currentSort : null,
    ], fn ($value) => filled($value));
@endphp

<div class="space-y-5">
    @if(session('success'))
        <div class="flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
            <i class="ri-check-line text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="flex items-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
            <i class="ri-error-warning-line text-lg"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <section class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                    <i class="ri-shield-user-line"></i>
                    Мониторинг заказов
                </div>
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Заказы</h1>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                    Общая картина по заказам: статусы, деньги, участники и сигналы риска. Админ не ведёт заказ вместо продавца, а видит, где нужна проверка или поддержка.
                </p>
            </div>

            @if($orders->count())
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    Показано <span class="font-semibold text-slate-900">{{ $orders->firstItem() }}-{{ $orders->lastItem() }}</span>
                    из <span class="font-semibold text-slate-900">{{ $orders->total() }}</span>
                </div>
            @endif
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-slate-500">
                <span>Всего заказов</span>
                <i class="ri-file-list-3-line text-indigo-500"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($totalOrders, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-slate-400">Во всей системе</p>
        </div>
        <a href="{{ route('admin.orders.index', array_merge($focusBaseFilters, ['focus' => 'attention'])) }}"
           class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-center justify-between text-sm text-rose-700">
                <span>Требуют внимания</span>
                <i class="ri-error-warning-line"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-rose-800">{{ number_format($attentionCount, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-rose-700/70">Отмена или долго без движения</p>
        </a>
        <a href="{{ route('admin.orders.index', array_merge($focusBaseFilters, ['focus' => 'active'])) }}"
           class="rounded-2xl border border-sky-200 bg-sky-50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-center justify-between text-sm text-sky-700">
                <span>В процессе</span>
                <i class="ri-route-line"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-sky-800">{{ number_format($activeCount, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-sky-700/70">Не завершены и не отменены</p>
        </a>
        <a href="{{ route('admin.orders.index', array_merge($focusBaseFilters, ['focus' => 'cancel_requests'])) }}"
           class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-center justify-between text-sm text-amber-700">
                <span>Запросы отмены</span>
                <i class="ri-question-answer-line"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-amber-800">{{ number_format($cancelRequestsCount, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-amber-700/70">Где нужен контроль причины</p>
        </a>
        <a href="{{ route('admin.orders.index', array_merge($focusBaseFilters, ['focus' => 'stuck'])) }}"
           class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-center justify-between text-sm text-rose-700">
                <span>Зависшие</span>
                <i class="ri-alarm-warning-line"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-rose-800">{{ number_format($stuckCount, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-rose-700/70">Долго без движения</p>
        </a>
        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-indigo-700">
                <span>Сумма по фильтру</span>
                <i class="ri-money-dollar-circle-line"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-indigo-800">{{ number_format($summary['revenue'] ?? 0, 2, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-indigo-700/70">Без отменённых заказов</p>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Админский фокус</h2>
                <p class="mt-1 text-sm text-slate-500">Цифра в левом меню ведёт сюда: это не все новые заказы, а только отмены и зависшие процессы.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach([
                    null => ['label' => 'Обычный список', 'icon' => 'ri-list-check'],
                    'attention' => ['label' => 'Требуют внимания', 'icon' => 'ri-error-warning-line'],
                    'active' => ['label' => 'В процессе', 'icon' => 'ri-route-line'],
                    'cancel_requests' => ['label' => 'Запросы отмены', 'icon' => 'ri-question-answer-line'],
                    'stuck' => ['label' => 'Зависшие', 'icon' => 'ri-alarm-warning-line'],
                ] as $focusKey => $focus)
                    @php
                        $isFocusActive = ($currentFocus === null && $focusKey === null) || ((string) $currentFocus === (string) $focusKey);
                        $href = $focusKey === null
                            ? route('admin.orders.index', $focusBaseFilters)
                            : route('admin.orders.index', array_merge($focusBaseFilters, ['focus' => $focusKey]));
                    @endphp
                    <a href="{{ $href }}"
                       class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold transition {{ $isFocusActive ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}">
                        <i class="{{ $focus['icon'] }}"></i>
                        {{ $focus['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-3 sm:p-4">
            <form method="GET" action="{{ route('admin.orders.index') }}" class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_180px_170px_170px_190px_auto]">
                @if($currentFocus)
                    <input type="hidden" name="focus" value="{{ $currentFocus }}">
                @endif

                <label class="relative block">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="search"
                           name="q"
                           value="{{ $search }}"
                           placeholder="Номер, ID, покупатель, продавец, товар или SKU"
                           class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                </label>

                <select name="status" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Все статусы</option>
                    @foreach($tabs as $key => $tab)
                        @continue($key === null)
                        <option value="{{ $key }}" @selected($currentStatus === $key)>{{ $tab['label'] }}</option>
                    @endforeach
                </select>

                <input type="date"
                       name="date_from"
                       value="{{ $dateFrom }}"
                       class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">

                <input type="date"
                       name="date_to"
                       value="{{ $dateTo }}"
                       class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">

                <select name="sort" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <option value="latest" @selected($currentSort === 'latest')>Сначала новые</option>
                    <option value="oldest" @selected($currentSort === 'oldest')>Сначала старые</option>
                    <option value="amount_desc" @selected($currentSort === 'amount_desc')>Сумма по убыванию</option>
                    <option value="amount_asc" @selected($currentSort === 'amount_asc')>Сумма по возрастанию</option>
                </select>

                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                    <i class="ri-filter-3-line"></i>
                    Применить
                </button>
            </form>

            @if($search || $currentStatus || $dateFrom || $dateTo || $currentFocus || $currentSort !== 'latest')
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                    <span class="font-semibold uppercase tracking-wide text-slate-400">Фильтр:</span>
                    @if($search)
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">Поиск: {{ $search }}</span>
                    @endif
                    @if($currentStatus)
                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700">Статус: {{ $statusLabels[$currentStatus] ?? $currentStatus }}</span>
                    @endif
                    @if($dateFrom || $dateTo)
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">Период: {{ $dateFrom ?: '...' }} - {{ $dateTo ?: '...' }}</span>
                    @endif
                    @if($currentFocus)
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700">Фокус: {{ $focusLabels[$currentFocus] ?? $currentFocus }}</span>
                    @endif
                    <a href="{{ route('admin.orders.index') }}" class="rounded-full border border-slate-200 px-2.5 py-1 font-semibold text-slate-500 transition hover:border-indigo-200 hover:text-indigo-700">
                        Сбросить
                    </a>
                </div>
            @endif
        </div>

        <div class="overflow-x-auto border-b border-slate-100 px-3 py-2">
            <div class="flex min-w-max items-center gap-2">
                @foreach($tabs as $key => $tab)
                    @php
                        $isActive = ($currentStatus === null && $key === null) || ($currentStatus !== null && (string) $currentStatus === (string) $key);
                        $count = $key === null ? $totalOrders : ($statusCounts[$key] ?? 0);
                        $href = $key === null
                            ? route('admin.orders.index', $baseFilters)
                            : route('admin.orders.index', array_merge($baseFilters, ['status' => $key]));
                    @endphp
                    <a href="{{ $href }}"
                       class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm transition {{ $isActive ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}">
                        <i class="{{ $tab['icon'] }}"></i>
                        <span>{{ $tab['label'] }}</span>
                        <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold">{{ number_format($count, 0, ',', ' ') }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="hidden xl:block">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Заказ</th>
                        <th class="px-4 py-3 text-left font-semibold">Участники</th>
                        <th class="px-4 py-3 text-left font-semibold">Состав</th>
                        <th class="px-4 py-3 text-left font-semibold">Оплата</th>
                        <th class="px-4 py-3 text-left font-semibold">Статус</th>
                        <th class="px-4 py-3 text-right font-semibold">Управление</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        @php
                            $itemsCount = $order->items->sum('quantity');
                            $firstItem = $order->items->first();
                            $statusClass = $statusColors[$order->status] ?? 'border-slate-200 bg-slate-50 text-slate-700';
                            $sellerShopUrl = $order->seller?->shop?->slug ? route('seller.show', $order->seller->shop->slug) : null;
                            $hasCancelRequest = $order->cancellation_requested_at && !in_array($order->status, [\App\Models\Order::STATUS_CANCELED, \App\Models\Order::STATUS_COMPLETED], true);
                            $isStuck = ($order->status === \App\Models\Order::STATUS_PENDING && $order->created_at?->lte(now()->subDay()))
                                || ($order->status === \App\Models\Order::STATUS_PROCESSING && (($order->accepted_at && $order->accepted_at->lte(now()->subDays(2))) || (!$order->accepted_at && $order->created_at?->lte(now()->subDays(2)))));
                        @endphp
                        <tr class="align-top transition hover:bg-indigo-50/25">
                            <td class="px-4 py-4">
                                <div class="font-bold text-slate-950">#{{ $order->number }}</div>
                                <div class="mt-1 text-xs text-slate-400">ID {{ $order->id }} · {{ $order->created_at?->format('d.m.Y H:i') }}</div>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">{{ $order->delivery_method ?? 'Доставка не указана' }}</span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">{{ $order->payment_method ?? 'Оплата не указана' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="space-y-3">
                                    <div>
                                        <div class="text-xs text-slate-400">Покупатель</div>
                                        <a href="{{ route('admin.users.show', $order->user) }}" class="font-semibold text-slate-800 transition hover:text-indigo-700">{{ $order->user?->name ?? '—' }}</a>
                                        <div class="truncate text-xs text-slate-400">{{ $order->user?->email }}</div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-slate-400">Продавец</div>
                                        @if($sellerShopUrl)
                                            <a href="{{ $sellerShopUrl }}" class="font-semibold text-slate-800 transition hover:text-indigo-700">{{ $order->seller?->name ?? '—' }}</a>
                                        @else
                                            <a href="{{ route('admin.users.show', $order->seller) }}" class="font-semibold text-slate-800 transition hover:text-indigo-700">{{ $order->seller?->name ?? '—' }}</a>
                                        @endif
                                        <div class="truncate text-xs text-slate-400">{{ $order->seller?->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-900">{{ $itemsCount }} товар(ов)</div>
                                @if($firstItem?->product)
                                    <a href="{{ route('product.show', $firstItem->product->slug ?? $firstItem->product->id) }}"
                                       class="mt-1 block max-w-[260px] truncate text-sm text-slate-600 transition hover:text-indigo-700">
                                        {{ $firstItem->product->title }}
                                    </a>
                                @else
                                    <div class="mt-1 text-sm text-slate-400">Товар не найден</div>
                                @endif
                                @if($order->items->count() > 1)
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-xs font-semibold text-indigo-600">Показать остальные</summary>
                                        <div class="mt-2 space-y-1 text-xs text-slate-500">
                                            @foreach($order->items->skip(1)->take(4) as $item)
                                                <div class="truncate">{{ $item->product?->title ?? 'Товар удалён' }} · {{ $item->quantity }} шт.</div>
                                            @endforeach
                                        </div>
                                    </details>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <div class="whitespace-nowrap text-base font-bold text-slate-950">{{ $order->formatted_total_price }}</div>
                                <div class="mt-1 text-xs text-slate-400">Валюта: {{ $order->currency }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold {{ $statusClass }}">
                                    <i class="{{ $tabs[$order->status]['icon'] ?? 'ri-checkbox-blank-circle-line' }}"></i>
                                    {{ $order->status_ru }}
                                </span>
                                <div class="mt-2 space-y-1 text-xs text-slate-400">
                                    @if($order->accepted_at)<div>Принят: {{ $order->accepted_at->format('d.m H:i') }}</div>@endif
                                    @if($order->shipped_at)<div>Отправлен: {{ $order->shipped_at->format('d.m H:i') }}</div>@endif
                                    @if($order->delivered_at)<div>Доставлен: {{ $order->delivered_at->format('d.m H:i') }}</div>@endif
                                    @if($order->canceled_at)<div>Отменён: {{ $order->canceled_at->format('d.m H:i') }}</div>@endif
                                </div>
                                @if($hasCancelRequest || $isStuck)
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @if($hasCancelRequest)
                                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">запрос отмены</span>
                                        @endif
                                        @if($isStuck)
                                            <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700">завис</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                <a href="{{ route('admin.orders.show', $order) }}"
                                   class="inline-flex h-10 items-center justify-center gap-1 rounded-xl border border-indigo-200 bg-indigo-50 px-3 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100">
                                    <i class="ri-arrow-right-up-line"></i>
                                    Открыть
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                                    <i class="ri-file-list-3-line"></i>
                                </div>
                                <h2 class="mt-4 text-lg font-semibold text-slate-900">Заказы не найдены</h2>
                                <p class="mt-1 text-sm text-slate-500">Попробуйте снять фильтр или изменить строку поиска.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 xl:hidden">
            @forelse($orders as $order)
                @php
                    $itemsCount = $order->items->sum('quantity');
                    $firstItem = $order->items->first();
                    $statusClass = $statusColors[$order->status] ?? 'border-slate-200 bg-slate-50 text-slate-700';
                    $hasCancelRequest = $order->cancellation_requested_at && !in_array($order->status, [\App\Models\Order::STATUS_CANCELED, \App\Models\Order::STATUS_COMPLETED], true);
                    $isStuck = ($order->status === \App\Models\Order::STATUS_PENDING && $order->created_at?->lte(now()->subDay()))
                        || ($order->status === \App\Models\Order::STATUS_PROCESSING && (($order->accepted_at && $order->accepted_at->lte(now()->subDays(2))) || (!$order->accepted_at && $order->created_at?->lte(now()->subDays(2)))));
                @endphp
                <article class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate font-bold text-slate-950">#{{ $order->number }}</div>
                            <div class="mt-1 text-xs text-slate-400">ID {{ $order->id }} · {{ $order->created_at?->format('d.m.Y H:i') }}</div>
                        </div>
                        <span class="inline-flex shrink-0 items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-bold {{ $statusClass }}">
                            <i class="{{ $tabs[$order->status]['icon'] ?? 'ri-checkbox-blank-circle-line' }}"></i>
                            {{ $statusLabels[$order->status] ?? $order->status }}
                        </span>
                    </div>

                    @if($hasCancelRequest || $isStuck)
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if($hasCancelRequest)
                                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Запрос отмены</span>
                            @endif
                            @if($isStuck)
                                <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">Долго без движения</span>
                            @endif
                        </div>
                    @endif

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="text-xs text-slate-400">Покупатель</div>
                            <a href="{{ route('admin.users.show', $order->user) }}" class="mt-1 block truncate font-semibold text-slate-800">{{ $order->user?->name ?? '—' }}</a>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="text-xs text-slate-400">Продавец</div>
                            <a href="{{ route('admin.users.show', $order->seller) }}" class="mt-1 block truncate font-semibold text-slate-800">{{ $order->seller?->name ?? '—' }}</a>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="text-xs text-slate-400">Состав</div>
                            <div class="mt-1 truncate font-semibold text-slate-800">{{ $firstItem?->product?->title ?? 'Товар не найден' }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $itemsCount }} товар(ов)</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="text-xs text-slate-400">Сумма</div>
                            <div class="mt-1 font-bold text-slate-950">{{ $order->formatted_total_price }}</div>
                        </div>
                    </div>

                    <a href="{{ route('admin.orders.show', $order) }}"
                       class="mt-4 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-bold text-indigo-700 transition hover:bg-indigo-100">
                        <i class="ri-arrow-right-up-line"></i>
                        Открыть заказ
                    </a>
                </article>
            @empty
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                        <i class="ri-file-list-3-line"></i>
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-slate-900">Заказы не найдены</h2>
                    <p class="mt-1 text-sm text-slate-500">Попробуйте снять фильтр или изменить строку поиска.</p>
                </div>
            @endforelse
        </div>
    </section>

    @if($orders->hasPages())
        <div>{{ $orders->links() }}</div>
    @endif
</div>
@endsection
