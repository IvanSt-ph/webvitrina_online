@extends('admin.layout')

@section('title', 'Релиз-чеклист')

@section('content')
@php
    $health = \App\Support\ProductionHealth::make();
    $checks = $health['checks'];
    $done = $health['done'];
    $total = $health['total'];
    $percent = $total > 0 ? round(($done / $total) * 100) : 0;
    $attentionItems = collect($checks)
        ->flatMap(fn ($group) => collect($group['items'])
            ->reject(fn ($item) => $item['ok'])
            ->map(fn ($item) => $item + ['group' => $group['group']]))
        ->values();
    $groupAnchors = [
        'Окружение' => 'release-environment',
        'Инфраструктура' => 'release-infrastructure',
        'Бизнес-логика' => 'release-business',
    ];
    $cardLinks = [
        'База данных' => ['href' => '#release-infrastructure'],
        'Диск' => ['href' => '#release-infrastructure'],
        'laravel.log' => ['href' => '#release-infrastructure'],
        'Очередь' => ['href' => '#release-environment'],
        'Storage' => ['href' => '#release-infrastructure'],
        'Backup' => ['href' => '#release-infrastructure'],
        'SMTP' => ['href' => '#release-infrastructure'],
        'Sitemap' => ['href' => route('sitemap'), 'target' => '_blank'],
        'Robots' => ['href' => route('robots'), 'target' => '_blank'],
        'Ошибки 24ч' => ['href' => '#release-infrastructure'],
    ];
@endphp

<div class="space-y-6">
    <header class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="grid gap-0 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="p-5 sm:p-6">
                <div class="wv-eyebrow">
                    <i class="ri-rocket-line"></i>
                    Перед выпуском
                </div>
                <h1 class="mt-3 text-2xl font-black text-slate-950">Production checklist</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Быстрая панель готовности: окружение, безопасность, очереди, почта, бэкапы и честные бизнес-ограничения текущего MVP.
                </p>
                <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full {{ $percent >= 85 ? 'bg-emerald-500' : ($percent >= 60 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $percent }}%"></div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">
                        <i class="ri-checkbox-circle-line text-emerald-600"></i>
                        {{ $done }} готово
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">
                        <i class="ri-error-warning-line text-amber-600"></i>
                        {{ $attentionItems->count() }} требует внимания
                    </span>
                </div>
            </div>

            <div class="border-t border-slate-100 bg-slate-50/80 p-5 sm:p-6 xl:border-l xl:border-t-0">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Готовность</div>
                        <div class="mt-1 text-4xl font-black text-indigo-700">{{ $percent }}%</div>
                    </div>
                    <div class="rounded-2xl border {{ $attentionItems->isEmpty() ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-amber-100 bg-amber-50 text-amber-700' }} px-3 py-2 text-sm font-bold">
                        <i class="{{ $attentionItems->isEmpty() ? 'ri-check-line' : 'ri-error-warning-line' }}"></i>
                        {{ $attentionItems->isEmpty() ? 'Можно выпускать' : 'Есть задачи' }}
                    </div>
                </div>
                <div class="mt-4 space-y-2">
                    @forelse($attentionItems->take(3) as $item)
                        <div class="rounded-xl border border-white bg-white px-3 py-2 text-xs shadow-sm">
                            <div class="font-bold text-slate-800">{{ $item['label'] }}</div>
                            <div class="mt-0.5 truncate text-slate-500">{{ $item['current'] }}</div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-white bg-white px-3 py-3 text-sm font-semibold text-emerald-700 shadow-sm">
                            Критичных предупреждений нет.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </header>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach($health['cards'] as $card)
            @php
                $cardLink = $cardLinks[$card['title']] ?? ['href' => '#release-checks'];
            @endphp
            <a href="{{ $cardLink['href'] }}"
               @if(($cardLink['target'] ?? null) === '_blank') target="_blank" rel="noopener noreferrer" @endif
               class="group block rounded-2xl border bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-200 {{ $card['ok'] ? 'border-emerald-100' : 'border-amber-200' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-400">
                            <i class="{{ $card['icon'] }}"></i>
                            {{ $card['title'] }}
                        </div>
                        <div class="mt-2 truncate text-base font-black text-slate-950">{{ $card['value'] }}</div>
                    </div>
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $card['ok'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                        <i class="{{ ($cardLink['target'] ?? null) === '_blank' ? 'ri-external-link-line' : ($card['ok'] ? 'ri-arrow-down-line' : 'ri-error-warning-line') }} transition group-hover:translate-x-0.5"></i>
                    </span>
                </div>
                <p class="mt-3 line-clamp-2 text-xs leading-5 text-slate-500">{{ $card['detail'] }}</p>
            </a>
        @endforeach
    </section>

    <section id="release-checks" class="scroll-mt-24 grid gap-4 xl:grid-cols-3">
        @foreach($checks as $group)
            @php
                $groupItems = collect($group['items']);
                $groupDone = $groupItems->where('ok', true)->count();
                $groupAnchor = $groupAnchors[$group['group']] ?? 'release-checks';
            @endphp
            <article id="{{ $groupAnchor }}" class="scroll-mt-24 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold text-slate-950">{{ $group['group'] }}</h2>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-500">{{ $groupDone }}/{{ $groupItems->count() }}</span>
                </div>
                <div class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-100">
                    @foreach($group['items'] as $item)
                        <div class="{{ $item['ok'] ? 'bg-white' : 'bg-amber-50/70' }} p-3">
                            <div class="flex items-start gap-2.5">
                                <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $item['ok'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    <i class="{{ $item['ok'] ? 'ri-check-line' : 'ri-error-warning-line' }}"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-slate-900">{{ $item['label'] }}</div>
                                    <div class="mt-1 truncate text-xs text-slate-500">Сейчас: {{ $item['current'] }}</div>
                                    <p class="mt-2 text-xs leading-5 text-slate-600">{{ $item['hint'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        @endforeach
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-slate-950">Что сделать вручную перед публичным запуском</h2>
                <p class="mt-1 text-sm text-slate-500">Эти пункты нельзя честно проверить только кодом, зато они сильно снижают риск релиза.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-1 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700">
                <i class="ri-shield-check-line"></i>
                Финальный контроль
            </span>
        </div>
        <div class="mt-4 grid gap-3 text-sm text-slate-700 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-3">Проверить `.env` на сервере: debug, cookies, queue, mail, URL.</div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-3">Настроить cron для Laravel scheduler и регулярных бэкапов.</div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-3">Проверить restore backup на отдельной базе: архив без восстановления ещё не защита.</div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-3">Прогнать smoke-test: регистрация, товар, корзина, заказ, чат, спор, жалоба.</div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-3">Показать юридические страницы юристу и зафиксировать дату редакции.</div>
        </div>
    </section>
</div>
@endsection
