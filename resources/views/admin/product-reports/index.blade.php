@extends('admin.layout')

@section('title', 'Жалобы на товары')

@section('content')
@php
    use App\Models\Product;
    use App\Models\ProductReport;

    $statusMap = [
        'all' => ['label' => 'Все', 'class' => 'border-slate-200 bg-white text-slate-600'],
        ProductReport::STATUS_OPEN => ['label' => 'Новые', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'],
        ProductReport::STATUS_RESOLVED => ['label' => 'Решены', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'],
        ProductReport::STATUS_DISMISSED => ['label' => 'Отклонены', 'class' => 'border-slate-200 bg-slate-50 text-slate-600'],
    ];

    $actionMap = [
        'all' => 'Все действия',
        'none' => 'Без действия',
        ProductReport::ACTION_REVIEWED => 'Принято к работе',
        ProductReport::ACTION_PRODUCT_HIDDEN => 'Товар заблокирован',
        ProductReport::ACTION_PRODUCT_RESTORED => 'Товар возвращён',
        ProductReport::ACTION_DISMISSED => 'Жалоба отклонена',
    ];

    $productStatusMap = [
        'all' => ['label' => 'Все товары', 'class' => 'border-slate-200 bg-white text-slate-600'],
        Product::STATUS_ACTIVE => ['label' => 'На витрине', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'],
        Product::STATUS_DRAFT => ['label' => 'Черновики', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'],
        Product::STATUS_BLOCKED => ['label' => 'Заблокированы', 'class' => 'border-rose-200 bg-rose-50 text-rose-700'],
    ];

    $reasonPresets = [
        'Товар временно заблокирован: карточка требует исправления описания, фото или характеристик.',
        'Жалоба принята к работе: нарушение проверяется, продавец будет уведомлён при необходимости.',
        'Нарушение не подтверждено: карточка соответствует правилам площадки.',
        'Товар проверен после исправлений и может быть возвращён в продажу.',
    ];

    $focusMap = [
        'all' => 'Все жалобы',
        'active_open' => 'Срочно проверить',
        'blocked' => 'Заблокированные товары',
        'repeated' => 'Повторные жалобы',
        'restored' => 'Возвращённые товары',
    ];
@endphp

<div class="space-y-5">
    <header class="rounded-3xl border border-rose-100 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-rose-600">
                    <i class="ri-shield-flash-line"></i>
                    Центр модерации
                </div>
                <h1 class="mt-3 text-2xl font-bold text-slate-950">Жалобы на товары</h1>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                    Очередь показывает, где товар ещё виден покупателям, где жалобы повторяются, и какое решение уже принято. Блокировка переводит товар в статус <span class="font-semibold text-slate-700">«заблокирован»</span>, продавец сам не сможет вернуть его на витрину.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.products.index', ['status' => Product::STATUS_BLOCKED]) }}"
                   class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-rose-100 bg-rose-50 px-4 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                    <i class="ri-eye-off-line"></i>
                    Заблокированные
                </a>
                <a href="{{ route('admin.products.index') }}"
                   class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    <i class="ri-box-3-line"></i>
                    Все товары
                </a>
            </div>
        </div>
    </header>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.product-reports.index', ['focus' => 'active_open', 'status' => 'all', 'action' => 'all', 'product_status' => 'all', 'q' => $q]) }}"
           class="group rounded-2xl border p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $focus === 'active_open' ? 'border-amber-300 bg-amber-100 ring-2 ring-amber-100' : 'border-amber-100 bg-amber-50' }}">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-2xl font-bold text-amber-700">{{ $riskCounters['active_open'] ?? 0 }}</div>
                    <div class="mt-1 text-sm font-semibold text-amber-900">Срочно проверить</div>
                    <p class="mt-1 text-xs text-amber-700/80">Новые жалобы на товары, которые ещё на витрине.</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-amber-700 opacity-80 group-hover:opacity-100">
                        Показать <i class="ri-arrow-right-line"></i>
                    </span>
                </div>
                <i class="ri-alarm-warning-line text-3xl text-amber-500"></i>
            </div>
        </a>
        <a href="{{ route('admin.product-reports.index', ['focus' => 'blocked', 'status' => 'all', 'action' => 'all', 'product_status' => 'all', 'q' => $q]) }}"
           class="group rounded-2xl border p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $focus === 'blocked' ? 'border-rose-300 bg-rose-100 ring-2 ring-rose-100' : 'border-rose-100 bg-rose-50' }}">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-2xl font-bold text-rose-700">{{ $riskCounters['blocked_products'] ?? 0 }}</div>
                    <div class="mt-1 text-sm font-semibold text-rose-900">Заблокировано</div>
                    <p class="mt-1 text-xs text-rose-700/80">Уникальные товары, которые сейчас заблокированы.</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-rose-700 opacity-80 group-hover:opacity-100">
                        Показать <i class="ri-arrow-right-line"></i>
                    </span>
                </div>
                <i class="ri-lock-2-line text-3xl text-rose-500"></i>
            </div>
        </a>
        <a href="{{ route('admin.product-reports.index', ['focus' => 'repeated', 'status' => 'all', 'action' => 'all', 'product_status' => 'all', 'q' => $q]) }}"
           class="group rounded-2xl border p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $focus === 'repeated' ? 'border-indigo-300 bg-indigo-100 ring-2 ring-indigo-100' : 'border-indigo-100 bg-indigo-50' }}">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-2xl font-bold text-indigo-700">{{ $riskCounters['repeated_products'] ?? 0 }}</div>
                    <div class="mt-1 text-sm font-semibold text-indigo-950">Повторяются</div>
                    <p class="mt-1 text-xs text-indigo-700/80">Уникальные товары, на которые пришло больше одной жалобы.</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-indigo-700 opacity-80 group-hover:opacity-100">
                        Какие именно <i class="ri-arrow-right-line"></i>
                    </span>
                </div>
                <i class="ri-stack-line text-3xl text-indigo-500"></i>
            </div>
        </a>
        <a href="{{ route('admin.product-reports.index', ['focus' => 'restored', 'status' => 'all', 'action' => 'all', 'product_status' => 'all', 'q' => $q]) }}"
           class="group rounded-2xl border p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $focus === 'restored' ? 'border-emerald-300 bg-emerald-100 ring-2 ring-emerald-100' : 'border-emerald-100 bg-emerald-50' }}">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-2xl font-bold text-emerald-700">{{ $riskCounters['restored_products'] ?? 0 }}</div>
                    <div class="mt-1 text-sm font-semibold text-emerald-950">Возвращено</div>
                    <p class="mt-1 text-xs text-emerald-700/80">Уникальные товары, восстановленные после проверки.</p>
                    <span class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 opacity-80 group-hover:opacity-100">
                        Показать <i class="ri-arrow-right-line"></i>
                    </span>
                </div>
                <i class="ri-refresh-line text-3xl text-emerald-500"></i>
            </div>
        </a>
    </section>

    <section class="grid gap-3 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <i class="ri-route-line text-xl"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-950">Логика решения</h2>
                    <div class="mt-2 grid gap-2 text-sm text-slate-600 sm:grid-cols-3">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="font-semibold text-slate-900">1. Риск</div>
                            <p class="mt-1 text-xs leading-5">Активный товар с новой жалобой проверяем первым.</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="font-semibold text-slate-900">2. Действие</div>
                            <p class="mt-1 text-xs leading-5">Принять, отклонить, заблокировать или вернуть после исправлений.</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="font-semibold text-slate-900">3. След</div>
                            <p class="mt-1 text-xs leading-5">Комментарий сохраняется и важные решения уходят продавцу.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Быстрые комментарии</div>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach($reasonPresets as $preset)
                    <button type="button"
                            data-report-preset="{{ $preset }}"
                            class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                        {{ Str::limit($preset, 38) }}
                    </button>
                @endforeach
            </div>
            <p class="mt-3 text-xs leading-5 text-slate-500">Клик по фразе вставит её в активное поле комментария в карточке жалобы.</p>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        @if($focus !== 'all')
            <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-indigo-100 bg-indigo-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-indigo-500">Активный фокус</div>
                    <div class="mt-1 text-sm font-bold text-indigo-950">{{ $focusMap[$focus] ?? 'Фокус' }}</div>
                </div>
                <a href="{{ route('admin.product-reports.index', ['status' => $status, 'action' => $action, 'product_status' => $productStatus, 'q' => $q]) }}"
                   class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-white px-3 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-100">
                    <i class="ri-close-line"></i>
                    Сбросить фокус
                </a>
            </div>
        @endif

        <div class="mb-4 flex flex-wrap gap-2">
            @foreach($statusMap as $key => $meta)
                <a href="{{ route('admin.product-reports.index', ['status' => $key, 'action' => $action, 'product_status' => $productStatus, 'focus' => $focus, 'q' => $q]) }}"
                   class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-semibold transition hover:-translate-y-0.5 {{ $status === $key ? $meta['class'] : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-white' }}">
                    {{ $meta['label'] }}
                    <span class="rounded-full bg-white/80 px-2 py-0.5 text-xs">{{ $counters[$key] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('admin.product-reports.index') }}" class="grid gap-3 xl:grid-cols-[1fr_220px_220px_auto]">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="focus" value="{{ $focus }}">
            <label class="relative">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input name="q" value="{{ $q }}" type="search"
                       placeholder="Поиск по товару, SKU, продавцу, покупателю или причине"
                       class="h-11 w-full rounded-xl border border-slate-200 pl-10 pr-3 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
            </label>
            <select name="action" class="h-11 rounded-xl border border-slate-200 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                @foreach($actionMap as $key => $label)
                    <option value="{{ $key }}" @selected($action === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="product_status" class="h-11 rounded-xl border border-slate-200 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                @foreach($productStatusMap as $key => $meta)
                    <option value="{{ $key }}" @selected($productStatus === $key)>
                        {{ $meta['label'] }} @if($key !== 'all') · {{ $productStatusCounters[$key] ?? 0 }} @endif
                    </option>
                @endforeach
            </select>
            <button class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700">
                <i class="ri-filter-3-line"></i>
                Применить
            </button>
        </form>
    </section>

    <section class="space-y-3">
        @forelse($reports as $report)
            @php
                $product = $report->product;
                $badge = $statusMap[$report->status] ?? $statusMap[ProductReport::STATUS_OPEN];
                $productBadge = $productStatusMap[$product->status] ?? $productStatusMap[Product::STATUS_DRAFT];
                $totalReportsForProduct = (int) ($productReportCounts[$product->id] ?? 1);
                $openReportsForProduct = (int) ($openProductReportCounts[$product->id] ?? 0);
                $isActiveOpen = $report->status === ProductReport::STATUS_OPEN && $product->status === Product::STATUS_ACTIVE;
                $isRepeated = $totalReportsForProduct > 1;
                $priority = $product->status === Product::STATUS_BLOCKED
                    ? ['label' => 'Товар уже заблокирован', 'class' => 'border-rose-100 bg-rose-50 text-rose-700', 'icon' => 'ri-lock-2-line']
                    : ($isActiveOpen
                        ? ['label' => 'Срочно: товар на витрине', 'class' => 'border-amber-100 bg-amber-50 text-amber-700', 'icon' => 'ri-alarm-warning-line']
                        : ['label' => 'Обычная проверка', 'class' => 'border-slate-200 bg-slate-50 text-slate-600', 'icon' => 'ri-shield-check-line']);
            @endphp

            <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div class="min-w-0">
                        <div class="flex flex-col gap-4 sm:flex-row">
                            <a href="{{ route('admin.products.edit', $product) }}" class="h-28 w-28 shrink-0 overflow-hidden rounded-2xl border border-slate-100 bg-slate-50">
                                <img src="{{ $product->image_thumb_url }}" alt="{{ $product->title }}" class="h-full w-full object-cover">
                            </a>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $badge['class'] }}">
                                        {{ $badge['label'] }}
                                    </span>
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $productBadge['class'] }}">
                                        {{ $productBadge['label'] }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-bold {{ $priority['class'] }}">
                                        <i class="{{ $priority['icon'] }}"></i>
                                        {{ $priority['label'] }}
                                    </span>
                                    @if($isRepeated)
                                        <span class="inline-flex rounded-full border border-indigo-100 bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">
                                            {{ $totalReportsForProduct }} жалобы на товар
                                        </span>
                                    @endif
                                </div>

                                <a href="{{ route('admin.products.edit', $product) }}" class="mt-3 block truncate text-lg font-bold text-slate-950 hover:text-indigo-600">
                                    {{ $product->title }}
                                </a>

                                <div class="mt-2 grid gap-2 text-xs text-slate-500 sm:grid-cols-2">
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <span class="text-slate-400">SKU:</span>
                                        <span class="font-semibold text-slate-700">{{ $product->sku ?: '—' }}</span>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <span class="text-slate-400">Продавец:</span>
                                        <span class="font-semibold text-slate-700">{{ $product->seller?->name ?? '—' }}</span>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <span class="text-slate-400">Жалоба от:</span>
                                        <span class="font-semibold text-slate-700">{{ $report->user?->name ?? 'Гость' }}</span>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2">
                                        <span class="text-slate-400">Открытых по товару:</span>
                                        <span class="font-semibold text-slate-700">{{ $openReportsForProduct }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-3 lg:grid-cols-[1fr_0.9fr]">
                            <div class="rounded-2xl bg-slate-50 p-3">
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Причина жалобы</div>
                                <div class="mt-1 text-sm font-semibold text-slate-800">{{ $report->reason }}</div>
                                @if($report->details)
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $report->details }}</p>
                                @else
                                    <p class="mt-2 text-sm text-slate-400">Пользователь не добавил подробности.</p>
                                @endif
                            </div>

                            <div class="rounded-2xl border border-slate-100 bg-white p-3 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-xs font-bold uppercase tracking-wide text-slate-400">История решения</div>
                                        <div class="mt-1 font-semibold text-slate-800">{{ ProductReport::actionLabel($report->action_taken) }}</div>
                                    </div>
                                    <span class="text-xs text-slate-400">#{{ $report->id }}</span>
                                </div>
                                <div class="mt-3 text-xs leading-5 text-slate-500">
                                    Создано: {{ $report->created_at->format('d.m.Y H:i') }}
                                    @if($report->reviewed_at)
                                        <br>Проверил: {{ $report->reviewer?->name ?? 'Администратор' }}, {{ $report->reviewed_at->format('d.m.Y H:i') }}
                                    @endif
                                </div>
                                @if($report->resolution)
                                    <p class="mt-3 rounded-xl bg-slate-50 p-3 text-sm leading-6 text-slate-600">{{ $report->resolution }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <aside class="rounded-2xl border border-slate-100 bg-slate-50 p-3">
                        <div class="rounded-xl bg-white p-3 text-sm">
                            <div class="font-bold text-slate-900">Доступные действия</div>
                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                @if($product->status === Product::STATUS_BLOCKED)
                                    Товар заблокирован. После исправлений его можно вернуть в продажу только решением администратора.
                                @elseif($report->status === ProductReport::STATUS_OPEN)
                                    Проверь карточку и выбери действие. При блокировке продавец получит уведомление с причиной.
                                @else
                                    Жалоба обработана. При необходимости можно открыть товар и изменить статус вручную.
                                @endif
                            </p>
                        </div>

                        <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                            @if($product->status === Product::STATUS_ACTIVE)
                                <a href="{{ route('product.show', $product->slug) }}" target="_blank"
                                   class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                    <i class="ri-external-link-line"></i>
                                    Открыть витрину
                                </a>
                            @else
                                <span class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-500">
                                    <i class="ri-eye-off-line"></i>
                                    Не на витрине
                                </span>
                            @endif
                            <a href="{{ route('admin.products.edit', $product) }}"
                               class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                <i class="ri-edit-line"></i>
                                Карточка товара
                            </a>
                        </div>

                        @if($product->status === Product::STATUS_BLOCKED)
                            <form method="POST" action="{{ route('admin.product-reports.restore-product', $report) }}" class="mt-3 space-y-2 rounded-xl border border-emerald-100 bg-emerald-50 p-3">
                                @csrf
                                <textarea name="resolution" rows="3" maxlength="1000" required placeholder="Что исправлено и почему товар можно вернуть?"
                                          data-report-resolution
                                          class="w-full rounded-xl border border-emerald-200 text-sm focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100"></textarea>
                                <select name="status" class="h-10 w-full rounded-xl border border-emerald-200 text-sm focus:border-emerald-300 focus:ring-4 focus:ring-emerald-100">
                                    <option value="{{ Product::STATUS_ACTIVE }}">Вернуть на витрину</option>
                                    <option value="{{ Product::STATUS_DRAFT }}">Оставить черновиком</option>
                                </select>
                                <button class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                        onclick="return confirm('Вернуть товар из блокировки и уведомить продавца?')">
                                    <i class="ri-refresh-line"></i>
                                    Вернуть товар
                                </button>
                            </form>
                        @endif

                        @if($report->status === ProductReport::STATUS_OPEN)
                            <div class="mt-3 space-y-2">
                                <form method="POST" action="{{ route('admin.product-reports.resolve', $report) }}" class="space-y-2">
                                    @csrf
                                    <textarea name="resolution" rows="2" maxlength="1000" placeholder="Комментарий для истории, необязательно"
                                              data-report-resolution
                                              class="w-full rounded-xl border border-slate-200 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"></textarea>
                                    <button class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                        <i class="ri-check-line"></i>
                                        Принять без блокировки
                                    </button>
                                </form>

                                @if($product->status !== Product::STATUS_BLOCKED)
                                    <form method="POST" action="{{ route('admin.product-reports.hide-product', $report) }}" class="space-y-2 rounded-xl border border-rose-100 bg-rose-50 p-3">
                                        @csrf
                                        <textarea name="resolution" rows="3" maxlength="1000" required placeholder="Почему товар блокируется? Этот текст уйдёт продавцу."
                                                  data-report-resolution
                                                  class="w-full rounded-xl border border-rose-200 text-sm focus:border-rose-300 focus:ring-4 focus:ring-rose-100"></textarea>
                                        <button class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-rose-600 px-3 text-sm font-semibold text-white transition hover:bg-rose-700"
                                                onclick="return confirm('Заблокировать товар, снять с витрины и уведомить продавца?')">
                                            <i class="ri-lock-2-line"></i>
                                            Заблокировать товар
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.product-reports.dismiss', $report) }}" class="space-y-2">
                                    @csrf
                                    <textarea name="resolution" rows="2" maxlength="1000" placeholder="Почему жалоба отклонена? Необязательно"
                                              data-report-resolution
                                              class="w-full rounded-xl border border-slate-200 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"></textarea>
                                    <button class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                        <i class="ri-close-line"></i>
                                        Отклонить жалобу
                                    </button>
                                </form>
                            </div>
                        @elseif($product->status !== Product::STATUS_BLOCKED)
                            <div class="mt-3 rounded-xl bg-white p-3 text-sm text-slate-500">
                                Жалоба уже обработана. Для нового решения открой карточку товара или дождись новой жалобы.
                            </div>
                        @endif
                    </aside>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white p-10 text-center shadow-sm">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <i class="ri-shield-check-line text-2xl"></i>
                </div>
                <h2 class="mt-4 text-lg font-bold text-slate-900">Жалоб по этому фильтру нет</h2>
                <p class="mt-1 text-sm text-slate-500">Измени фильтр или поиск. Новые жалобы покупателей появятся в этой очереди.</p>
                <a href="{{ route('admin.product-reports.index') }}"
                   class="mt-4 inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                    <i class="ri-refresh-line"></i>
                    Показать новые
                </a>
            </div>
        @endforelse
    </section>

    {{ $reports->links() }}
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let activeResolution = null;

    document.querySelectorAll('[data-report-resolution]').forEach((field) => {
        field.addEventListener('focus', () => {
            activeResolution = field;
        });
    });

    document.querySelectorAll('[data-report-preset]').forEach((button) => {
        button.addEventListener('click', () => {
            const field = activeResolution || document.querySelector('[data-report-resolution]');
            if (!field) {
                return;
            }

            field.value = button.dataset.reportPreset || '';
            field.focus();
        });
    });
});
</script>
@endpush
@endsection
