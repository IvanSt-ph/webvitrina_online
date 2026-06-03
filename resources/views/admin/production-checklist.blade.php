@extends('admin.layout')

@section('title', 'Релиз-чеклист')

@section('content')
@php
    $checks = [
        [
            'group' => 'Окружение',
            'items' => [
                ['label' => 'APP_ENV=production', 'ok' => app()->environment('production'), 'current' => config('app.env'), 'hint' => 'На боевом сервере окружение должно быть production.'],
                ['label' => 'APP_DEBUG=false', 'ok' => !config('app.debug'), 'current' => config('app.debug') ? 'true' : 'false', 'hint' => 'Debug-страницы могут раскрыть стек, SQL и переменные окружения.'],
                ['label' => 'HTTPS-cookie', 'ok' => (bool) config('session.secure'), 'current' => config('session.secure') ? 'true' : 'false', 'hint' => 'Для HTTPS включите SESSION_SECURE_COOKIE=true.'],
                ['label' => 'Очереди не sync', 'ok' => config('queue.default') !== 'sync', 'current' => config('queue.default'), 'hint' => 'Письма, уведомления и тяжёлые задачи лучше выносить в очередь.'],
            ],
        ],
        [
            'group' => 'Инфраструктура',
            'items' => [
                ['label' => 'Почта настроена', 'ok' => config('mail.default') !== 'log', 'current' => config('mail.default'), 'hint' => 'Для уведомлений нужен рабочий SMTP/провайдер.'],
                ['label' => 'Хранилище связано', 'ok' => is_link(public_path('storage')) || file_exists(public_path('storage')), 'current' => public_path('storage'), 'hint' => 'Проверьте php artisan storage:link на сервере.'],
                ['label' => 'Sitemap доступен', 'ok' => route('sitemap', [], false) === '/sitemap.xml', 'current' => route('sitemap', [], false), 'hint' => 'После деплоя проверьте sitemap.xml и robots.txt по домену.'],
                ['label' => 'Бэкапы БД', 'ok' => false, 'current' => 'проверяется вручную', 'hint' => 'Настройте ежедневный backup базы и файлов загрузок.'],
            ],
        ],
        [
            'group' => 'Бизнес-логика',
            'items' => [
                ['label' => 'Онлайн-оплата честно выключена', 'ok' => true, 'current' => 'режим договорённости', 'hint' => 'До эквайринга сайт не должен обещать списание с карты.'],
                ['label' => 'Доставка описана как договорённость', 'ok' => true, 'current' => 'по продавцам', 'hint' => 'До логистики показывайте отдельные условия по продавцам.'],
                ['label' => 'Правила опубликованы', 'ok' => true, 'current' => '/rules, /privacy, /delivery-returns', 'hint' => 'Финальную редакцию всё равно стоит показать юристу.'],
                ['label' => 'Модерация жалоб включена', 'ok' => true, 'current' => 'товары блокируются админом', 'hint' => 'Продавец не может сам вернуть заблокированный товар.'],
            ],
        ],
    ];

    $flat = collect($checks)->flatMap(fn ($group) => $group['items']);
    $done = $flat->where('ok', true)->count();
    $total = $flat->count();
    $percent = $total > 0 ? round(($done / $total) * 100) : 0;
@endphp

<div class="space-y-5">
    <header class="wv-panel">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="wv-eyebrow">
                    <i class="ri-rocket-line"></i>
                    Перед выпуском
                </div>
                <h1 class="mt-3 text-2xl font-bold text-slate-950">Production checklist</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Быстрая панель готовности: окружение, безопасность, очереди, почта, бэкапы и честные бизнес-ограничения текущего MVP.
                </p>
            </div>
            <div class="wv-soft-panel text-center">
                <div class="text-3xl font-black text-indigo-700">{{ $percent }}%</div>
                <div class="mt-1 text-xs font-semibold text-slate-500">{{ $done }} из {{ $total }} пунктов</div>
            </div>
        </div>
    </header>

    <section class="grid gap-4 xl:grid-cols-3">
        @foreach($checks as $group)
            <article class="wv-panel p-4">
                <h2 class="text-lg font-bold text-slate-950">{{ $group['group'] }}</h2>
                <div class="mt-4 space-y-3">
                    @foreach($group['items'] as $item)
                        <div class="rounded-2xl border {{ $item['ok'] ? 'border-emerald-100 bg-emerald-50' : 'border-amber-100 bg-amber-50' }} p-3">
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $item['ok'] ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    <i class="{{ $item['ok'] ? 'ri-check-line' : 'ri-error-warning-line' }}"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-900">{{ $item['label'] }}</div>
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

    <section class="rounded-3xl border border-rose-100 bg-rose-50 p-5 shadow-sm ring-1 ring-rose-100">
        <h2 class="font-bold text-rose-950">Что сделать вручную перед публичным запуском</h2>
        <div class="mt-3 grid gap-3 text-sm text-rose-900 md:grid-cols-2">
            <div class="rounded-2xl bg-white/70 p-3">Проверить `.env` на сервере: debug, cookies, queue, mail, URL.</div>
            <div class="rounded-2xl bg-white/70 p-3">Настроить cron для Laravel scheduler и регулярных бэкапов.</div>
            <div class="rounded-2xl bg-white/70 p-3">Прогнать smoke-test: регистрация, товар, корзина, заказ, чат, спор, жалоба.</div>
            <div class="rounded-2xl bg-white/70 p-3">Показать юридические страницы юристу и зафиксировать дату редакции.</div>
        </div>
    </section>
</div>
@endsection
