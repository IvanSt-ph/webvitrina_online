@extends('admin.layout')

@section('title', 'Реклама')

@section('content')
@php
    $search = request('q');
    $status = request('status');
    $slotFilter = request('slot');
    $type = request('type');
    $statusLabels = [
        'active' => 'Активные',
        'hidden' => 'Скрытые',
        'scheduled' => 'Запланированные',
        'expired' => 'Завершённые',
    ];
    $tabs = [
        'overview' => ['label' => 'Обзор', 'icon' => 'ri-dashboard-line'],
        'slots' => ['label' => 'Слоты', 'icon' => 'ri-layout-grid-line'],
        'campaigns' => ['label' => 'Кампании', 'icon' => 'ri-megaphone-line'],
        'stats' => ['label' => 'Статистика', 'icon' => 'ri-bar-chart-2-line'],
        'settings' => ['label' => 'Настройки', 'icon' => 'ri-settings-3-line'],
    ];
@endphp

<div class="space-y-5">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                    <i class="ri-megaphone-line"></i>
                    Retail Media
                </div>
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Реклама / Продвижение</h1>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                    Ручное управление продвигаемыми товарами, магазинами и партнёрскими блоками. Оплата и автоматические аукционы не подключены.
                </p>
            </div>

            <a href="{{ route('admin.ads.create') }}"
               class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                <i class="ri-add-line text-lg"></i>
                Добавить кампанию
            </a>
        </div>
    </section>

    <nav class="overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
        <div class="flex min-w-max gap-2">
            @foreach($tabs as $key => $tab)
                <a href="{{ route('admin.ads.index', array_merge(request()->except('section', 'page'), ['section' => $key])) }}"
                   class="inline-flex h-10 items-center gap-2 rounded-xl px-3 text-sm font-bold transition {{ $section === $key ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">
                    <i class="{{ $tab['icon'] }}"></i>
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
    </nav>

    @if(in_array($section, ['overview', 'stats'], true))
    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Всего</div>
            <div class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($summary['total'] ?? 0, 0, ',', ' ') }}</div>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
            <div class="text-sm text-emerald-700">Сейчас активны</div>
            <div class="mt-2 text-2xl font-bold text-emerald-800">{{ number_format($summary['active'] ?? 0, 0, ',', ' ') }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Скрытые</div>
            <div class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($summary['hidden'] ?? 0, 0, ',', ' ') }}</div>
        </div>
        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 shadow-sm">
            <div class="text-sm text-indigo-700">Запланированы</div>
            <div class="mt-2 text-2xl font-bold text-indigo-800">{{ number_format($summary['scheduled'] ?? 0, 0, ',', ' ') }}</div>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <div class="text-sm text-amber-700">Завершены</div>
            <div class="mt-2 text-2xl font-bold text-amber-800">{{ number_format($summary['expired'] ?? 0, 0, ',', ' ') }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Показы</div>
            <div class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($summary['impressions'] ?? 0, 0, ',', ' ') }}</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Клики</div>
            <div class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($summary['clicks'] ?? 0, 0, ',', ' ') }}</div>
        </div>
        <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4 shadow-sm">
            <div class="text-sm text-violet-700">CTR</div>
            <div class="mt-2 text-2xl font-bold text-violet-800">{{ number_format($summary['ctr'] ?? 0, 2, ',', ' ') }}%</div>
        </div>
    </section>
    @endif

    @if(in_array($section, ['overview', 'slots'], true))
    <details class="rounded-2xl border border-slate-200 bg-white shadow-sm" @if($section === 'slots') open @endif>
        <summary class="flex cursor-pointer list-none flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
            <span class="min-w-0">
                <span class="flex items-center gap-2 text-lg font-bold text-slate-950">
                    <i class="ri-layout-grid-line text-indigo-600"></i>
                    Где показывается реклама
                </span>
                <span class="mt-1 block text-sm text-slate-500">Краткий справочник по слотам. На обзоре он свёрнут, во вкладке “Слоты” открыт полностью.</span>
            </span>
            <span class="flex items-center gap-2">
                <span class="hidden rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 sm:inline-flex">
                    {{ collect($slotGuide)->where('enabled', true)->count() }} выводится
                </span>
                <span class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm font-bold text-slate-700">
                    <i class="ri-arrow-down-s-line"></i>
                    Показать
                </span>
            </span>
        </summary>

        <div class="border-t border-slate-100 p-4 sm:p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <p class="max-w-3xl text-sm leading-6 text-slate-500">
                    Слот отвечает за место на сайте, тип цели отвечает за переход: товар, магазин или своя ссылка. Если слот “подготовлен”, он есть в базе, но публичный вывод ещё не подключён.
                </p>
                <a href="{{ route('admin.ads.create') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-indigo-100 bg-indigo-50 px-4 text-sm font-bold text-indigo-700 transition hover:bg-indigo-100">
                    <i class="ri-add-line"></i>
                    Создать
                </a>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                @foreach($slots as $slot)
                    @php
                        $guide = $slotGuide[$slot->key] ?? null;
                    @endphp
                    <div class="rounded-2xl border {{ ($guide['enabled'] ?? false) ? 'border-emerald-100 bg-emerald-50/40' : 'border-slate-200 bg-slate-50' }} p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-bold text-slate-950">{{ $slot->name }}</h3>
                                    @if($guide['enabled'] ?? false)
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">Выводится</span>
                                    @else
                                        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-bold text-slate-600">Подготовлен</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-slate-600">{{ $guide['where'] ?? $slot->description }}</p>
                                <p class="mt-2 text-xs font-semibold text-slate-500">Что выбирать: {{ $guide['target'] ?? 'Товар, магазин или свою ссылку.' }}</p>
                            </div>
                            @if($guide['url'] ?? null)
                                <a href="{{ $guide['url'] }}" target="_blank" rel="noopener" class="shrink-0 rounded-xl bg-white px-3 py-2 text-xs font-bold text-indigo-700 shadow-sm ring-1 ring-indigo-100 hover:bg-indigo-50">
                                    Открыть
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </details>
    @endif

    @if(in_array($section, ['overview', 'campaigns'], true))
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-3 sm:p-4">
            <form method="GET" action="{{ route('admin.ads.index') }}" class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_170px_210px_170px_210px_auto]">
                <input type="hidden" name="section" value="{{ $section }}">
                <label class="relative block">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="search"
                           name="q"
                           value="{{ $search }}"
                           placeholder="ID, кампания, товар, магазин или ссылка"
                           class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                </label>

                <select name="status" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm">
                    <option value="">Любой статус</option>
                    @foreach($statusLabels as $key => $label)
                        <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="slot" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm">
                    <option value="">Любой слот</option>
                    @foreach($slots as $slot)
                        <option value="{{ $slot->id }}" @selected((string) $slotFilter === (string) $slot->id)>{{ $slot->name }}</option>
                    @endforeach
                </select>

                <select name="type" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm">
                    <option value="">Любой тип</option>
                    @foreach(\App\Models\AdCampaign::targetTypes() as $key => $label)
                        <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="category_id" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm">
                    <option value="">Любая категория</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>

                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white transition hover:bg-indigo-700">
                    <i class="ri-filter-3-line"></i>
                    Применить
                </button>
            </form>

            @if($search || $status || $slotFilter || $type || request('category_id'))
                <a href="{{ route('admin.ads.index', ['section' => $section]) }}" class="mt-3 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                    Сбросить фильтры
                </a>
            @endif
        </div>

        @if($campaigns->isEmpty())
            <div class="px-6 py-14 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                    <i class="ri-megaphone-line"></i>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-slate-900">Кампаний пока нет</h2>
                <p class="mt-1 text-sm text-slate-500">Добавьте первый продвигаемый товар, магазин или партнёрский блок.</p>
                <a href="{{ route('admin.ads.create') }}" class="mt-5 inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-700">
                    <i class="ri-add-line"></i>
                    Добавить кампанию
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Кампания</th>
                            <th class="px-4 py-3">Слот</th>
                            <th class="px-4 py-3">Цель</th>
                            <th class="px-4 py-3">Период</th>
                            <th class="px-4 py-3">Статистика</th>
                            <th class="px-4 py-3 text-right">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($campaigns as $campaign)
                            @php
                                $isLive = $campaign->is_active
                                    && (!$campaign->starts_at || $campaign->starts_at->lte(now()))
                                    && (!$campaign->ends_at || $campaign->ends_at->gte(now()))
                                    && $campaign->slot?->is_active !== false;
                                $slotMeta = $campaign->slot ? ($slotGuide[$campaign->slot->key] ?? null) : null;
                            @endphp
                            <tr class="align-top transition hover:bg-slate-50/70">
                                <td class="px-4 py-4">
                                    <div class="flex items-start gap-3">
                                        <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $isLive ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                            <i class="{{ $campaign->target_type === 'shop' ? 'ri-store-3-line' : ($campaign->target_type === 'custom' ? 'ri-links-line' : 'ri-price-tag-3-line') }}"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <a href="{{ route('admin.ads.edit', $campaign) }}" class="font-bold text-slate-950 hover:text-indigo-700">{{ $campaign->title }}</a>
                                                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-bold text-indigo-700">{{ $campaign->label }}</span>
                                                @if($isLive)
                                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-700">{{ $campaign->computed_status_label }}</span>
                                                @elseif(! $campaign->is_active)
                                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-500">{{ $campaign->computed_status_label }}</span>
                                                @else
                                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-700">{{ $campaign->computed_status_label }}</span>
                                                @endif
                                            </div>
                                            @if($campaign->description)
                                                <p class="mt-1 max-w-xl text-xs leading-5 text-slate-500">{{ \Illuminate\Support\Str::limit($campaign->description, 140) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-slate-800">{{ $campaign->slot?->name ?? 'Слот удалён' }}</div>
                                    <div class="mt-1 text-xs text-slate-400">{{ $slotMeta['where'] ?? $campaign->slot?->placement }}</div>
                                    @if($campaign->category)
                                        <div class="mt-1 text-xs font-semibold text-indigo-600">Категория: {{ $campaign->category->name }}</div>
                                    @endif
                                    @if($slotMeta)
                                        <div class="mt-2 flex flex-wrap items-center gap-2">
                                            <span class="rounded-full {{ $slotMeta['enabled'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }} px-2 py-0.5 text-[11px] font-bold">
                                                {{ $slotMeta['enabled'] ? 'Публично выводится' : 'Пока только подготовлен' }}
                                            </span>
                                            @if($slotMeta['url'])
                                                <a href="{{ $slotMeta['url'] }}" target="_blank" rel="noopener" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800">Посмотреть место</a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-slate-800">{{ $campaign->target_name }}</div>
                                    <a href="{{ $campaign->resolved_url }}" target="_blank" rel="noopener" class="mt-1 block max-w-[220px] truncate text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                        {{ $campaign->resolved_url }}
                                    </a>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-500">
                                    <div>Старт: <span class="font-semibold text-slate-700">{{ $campaign->starts_at?->format('d.m.Y H:i') ?? 'сразу' }}</span></div>
                                    <div class="mt-1">Финиш: <span class="font-semibold text-slate-700">{{ $campaign->ends_at?->format('d.m.Y H:i') ?? 'без даты' }}</span></div>
                                    <div class="mt-1">Приоритет: <span class="font-semibold text-slate-700">{{ $campaign->sort_order }}</span></div>
                                    <div class="mt-1">Лимит: <span class="font-semibold text-slate-700">{{ $campaign->max_impressions ? number_format($campaign->max_impressions, 0, ',', ' ') : 'без лимита' }}</span></div>
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-500">
                                    <div>Показы: <span class="font-bold text-slate-900">{{ number_format($campaign->impressions_count, 0, ',', ' ') }}</span></div>
                                    <div class="mt-1">Клики: <span class="font-bold text-slate-900">{{ number_format($campaign->clicks_count, 0, ',', ' ') }}</span></div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-1">
                                        <a href="{{ route('admin.ads.edit', $campaign) }}" class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700 transition hover:bg-indigo-100" title="Редактировать">
                                            <i class="ri-edit-2-line"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.ads.destroy', $campaign) }}" onsubmit="return confirm('Удалить кампанию продвижения?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-700 transition hover:bg-rose-100" title="Удалить">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($campaigns->hasPages())
                <div class="border-t border-slate-100 p-4">
                    {{ $campaigns->links() }}
                </div>
            @endif
        @endif
    </section>
    @endif

    @if($section === 'stats')
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 class="text-lg font-bold text-slate-950">Статистика кампаний</h2>
            <p class="mt-1 text-sm text-slate-500">Показы и клики уже заложены в структуру. Сейчас они будут расти после подключения клиентского трекинга.</p>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Кампания</th>
                            <th class="px-4 py-3">Слот</th>
                            <th class="px-4 py-3">Показы</th>
                            <th class="px-4 py-3">Клики</th>
                            <th class="px-4 py-3">CTR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($campaigns as $campaign)
                            @php
                                $ctr = $campaign->impressions_count > 0 ? round($campaign->clicks_count * 100 / $campaign->impressions_count, 2) : 0;
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $campaign->title }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $campaign->slot?->name ?? 'Слот удалён' }}</td>
                                <td class="px-4 py-3">{{ number_format($campaign->impressions_count, 0, ',', ' ') }}</td>
                                <td class="px-4 py-3">{{ number_format($campaign->clicks_count, 0, ',', ' ') }}</td>
                                <td class="px-4 py-3 font-bold text-indigo-700">{{ number_format($ctr, 2, ',', ' ') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if($section === 'settings')
        <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
            <div class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-950">Настройки рекламной системы</h2>
                            <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                                Сейчас это ручное продвижение администратора: без оплат, тарифов, личного кабинета рекламодателя и автоматического аукциона.
                            </p>
                        </div>
                        <a href="{{ route('admin.ads.create') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-700">
                            <i class="ri-add-line"></i>
                            Новая кампания
                        </a>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                            <div class="flex items-center gap-2 font-bold text-emerald-800">
                                <i class="ri-price-tag-3-line"></i>
                                Маркировка публичных блоков
                            </div>
                            <p class="mt-2 text-sm leading-6 text-emerald-700">
                                Для ручных кампаний показывается метка из поля кампании: “Продвигается”, “Реклама”, “Партнёрский блок” или ваш текст.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4">
                            <div class="flex items-center gap-2 font-bold text-indigo-800">
                                <i class="ri-bank-card-line"></i>
                                Денежная логика отключена
                            </div>
                            <p class="mt-2 text-sm leading-6 text-indigo-700">
                                Здесь нет списаний, оплат, тарифов и баланса продавца. Кампанию создаёт и включает только администратор.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center gap-2 font-bold text-slate-800">
                                <i class="ri-sort-desc"></i>
                                Порядок показа
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Меньший приоритет показывается выше. Например, приоритет 10 будет выше, чем 100. Если дата финиша прошла, кампания считается завершённой.
                            </p>
                        </div>

                        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4">
                            <div class="flex items-center gap-2 font-bold text-amber-800">
                                <i class="ri-eye-line"></i>
                                Показы и клики
                            </div>
                            <p class="mt-2 text-sm leading-6 text-amber-700">
                                Таблицы под статистику уже есть. Реальный рост показов и кликов появится после отдельного подключения клиентского трекинга.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="text-lg font-bold text-slate-950">Как безопасно создать кампанию</h2>
                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm font-bold text-slate-950">1. Выберите слот</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Главная, категория или подготовленный блок. Слот определяет место на сайте.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm font-bold text-slate-950">2. Выберите цель</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Товар, магазин или своя ссылка. Для товара и магазина ссылка строится автоматически.</p>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm font-bold text-slate-950">3. Проверьте метку</div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Публичная метка должна честно объяснять блок: “Продвигается”, “Реклама” или “Партнёрский блок”.</p>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="space-y-5">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-base font-bold text-slate-950">Что уже выводится</h2>
                    <div class="mt-3 space-y-3">
                        @foreach($slots as $slot)
                            @php
                                $guide = $slotGuide[$slot->key] ?? null;
                            @endphp
                            <div class="rounded-xl border {{ ($guide['enabled'] ?? false) ? 'border-emerald-100 bg-emerald-50/50' : 'border-slate-200 bg-slate-50' }} p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0 truncate text-sm font-bold text-slate-900">{{ $slot->name }}</div>
                                    <span class="shrink-0 rounded-full {{ ($guide['enabled'] ?? false) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }} px-2 py-0.5 text-[11px] font-bold">
                                        {{ ($guide['enabled'] ?? false) ? 'видно' : 'заготовка' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $guide['where'] ?? $slot->description }}</p>
                                @if($guide['url'] ?? null)
                                    <a href="{{ $guide['url'] }}" target="_blank" rel="noopener" class="mt-2 inline-flex text-xs font-bold text-indigo-600 hover:text-indigo-800">Открыть место</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-rose-100 bg-rose-50 p-4 text-sm text-rose-800 shadow-sm">
                    <div class="flex items-start gap-2">
                        <i class="ri-alert-line mt-0.5 text-lg"></i>
                        <p>
                            Не называйте это тарифом или оплатой продавца, пока финансовая часть не оформлена юридически и технически. Сейчас это ручная админская подборка.
                        </p>
                    </div>
                </div>
            </aside>
        </section>
    @endif
</div>
@endsection
