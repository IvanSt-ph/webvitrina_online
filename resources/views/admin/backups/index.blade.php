@extends('admin.layout')

@section('title', 'Бэкапы')

@section('content')
@php
    $latestOk = $health['ok'] ?? false;
    $latestName = $health['latest_name'] ?? null;
    $manifest = $health['manifest'] ?? null;
@endphp

<div class="space-y-6">
    @if(session('success') || session('error'))
        <div class="rounded-2xl border p-4 text-sm font-semibold {{ session('success') ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800' }}">
            <i class="{{ session('success') ? 'ri-check-line' : 'ri-error-warning-line' }}"></i>
            {{ session('success') ?? session('error') }}
        </div>
    @endif

    <section class="wv-panel">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <div class="wv-eyebrow">
                    <i class="ri-database-2-line"></i>
                    Защита данных
                </div>
                <h1 class="mt-3 text-2xl font-black text-slate-950">Бэкапы базы и файлов</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                    Здесь можно вручную создать backup, проверить свежесть и быстро понять, отличается ли текущая база от последнего сохранённого состояния.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('admin.backups.index') }}"
                   class="wv-btn-secondary h-11 justify-center border-slate-200 bg-white text-slate-700 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                    <i class="ri-pulse-line text-lg"></i>
                    Проверить
                </a>
                <form method="POST"
                      action="{{ route('admin.backup.run') }}"
                      x-data="{ submitting: false }"
                      @submit="submitting = true">
                    @csrf
                    <button type="submit"
                            :disabled="submitting"
                            class="wv-btn-primary h-11 w-full justify-center disabled:cursor-wait disabled:opacity-70 sm:w-auto">
                        <i :class="submitting ? 'ri-loader-4-line animate-spin text-lg' : 'ri-add-circle-line text-lg'"></i>
                        <span x-text="submitting ? 'Создаём backup...' : 'Создать backup'"></span>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-3">
        <article class="wv-card p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Последний backup</div>
                    <div class="mt-1 text-xl font-black {{ $latestOk ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ $latestName ?: 'Не найден' }}
                    </div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $latestOk ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                    <i class="{{ $latestOk ? 'ri-shield-check-line' : 'ri-error-warning-line' }} text-2xl"></i>
                </div>
            </div>
            <div class="mt-4 space-y-2 text-sm text-slate-600">
                <div><span class="font-semibold text-slate-900">Дата:</span> {{ $health['created_at'] ?? '—' }}</div>
                <div><span class="font-semibold text-slate-900">Возраст:</span> {{ $health['age_hours'] ?? '—' }} ч</div>
                <div><span class="font-semibold text-slate-900">Папка:</span> {{ $health['path'] ?? '—' }}</div>
            </div>
        </article>

        <article class="wv-card p-5">
            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Текущая БД</div>
            <div class="mt-1 text-xl font-black text-slate-950">{{ $current['database'] }}</div>
            <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                <div class="rounded-2xl bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">Таблиц</div>
                    <div class="mt-1 text-2xl font-black text-slate-900">{{ $current['tables_total'] }}</div>
                </div>
                <div class="rounded-2xl bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">В manifest</div>
                    <div class="mt-1 text-2xl font-black text-slate-900">{{ $manifest['tables_total'] ?? '—' }}</div>
                </div>
            </div>
        </article>

        <article class="wv-card p-5">
            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Файлы backup</div>
            <div class="mt-3 space-y-2">
                @foreach($health['files'] ?? [] as $file => $info)
                    <div class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-3 py-2 text-sm">
                        <span class="font-semibold text-slate-800">{{ $file }}</span>
                        <span class="{{ $info['exists'] ? 'text-emerald-700' : 'text-rose-700' }} font-bold">
                            {{ $info['exists'] ? $info['size_human'] : 'нет' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="wv-card overflow-hidden">
        <div class="border-b border-slate-100 p-5">
            <h2 class="text-lg font-black text-slate-950">Сравнение с последним backup</h2>
            <p class="mt-1 text-sm text-slate-500">
                Это быстрая сверка по ключевым таблицам. Если после backup появились новые товары или заказы, разница будет нормальной.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Раздел</th>
                        <th class="px-5 py-3">Сейчас</th>
                        <th class="px-5 py-3">В backup</th>
                        <th class="px-5 py-3">Разница</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($comparison as $row)
                        @php
                            $diff = $row['diff'];
                            $label = $current['labels'][$row['table']] ?? $row['table'];
                        @endphp
                        <tr>
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ $label }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $row['current'] }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $row['backup'] ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @if($diff === null)
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-500">нет данных</span>
                                @elseif($diff === 0)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">без разницы</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">{{ $diff > 0 ? '+' : '' }}{{ $diff }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-2">
        <article class="wv-card p-5">
            <h2 class="text-lg font-black text-slate-950">Как восстановить безопасно</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Восстановление специально не сделано одной кнопкой: это опасное действие, которое может заменить текущую рабочую базу.
                Сначала backup надо поднять на отдельной тестовой базе и проверить данные.
            </p>
            <div class="mt-4 rounded-2xl bg-slate-950 p-4 text-xs leading-6 text-slate-100">
                <div>php artisan backup:health-check</div>
                <div>gzip -dc path/to/database.sql.gz | mysql -u root webvitrina_restore_check</div>
            </div>
        </article>

        <article class="wv-card p-5">
            <h2 class="text-lg font-black text-slate-950">Что считается нормальным</h2>
            <div class="mt-3 space-y-3 text-sm leading-6 text-slate-600">
                <div class="rounded-2xl bg-emerald-50 p-3 text-emerald-800">Backup свежий, файлы на месте, checksum совпадает.</div>
                <div class="rounded-2xl bg-amber-50 p-3 text-amber-800">Есть разница по таблицам, если после backup добавляли товары, заказы или пользователей.</div>
                <div class="rounded-2xl bg-rose-50 p-3 text-rose-800">Плохо, если нет `database.sql.gz`, `storage-public.tar.gz`, `manifest.json` или checksum не совпадает.</div>
            </div>
        </article>
    </section>
</div>
@endsection
