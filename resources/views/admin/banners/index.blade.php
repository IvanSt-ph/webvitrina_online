@extends('admin.layout')

@section('title', 'Баннеры')

@section('content')
@php
    $search = request('q');
    $currentStatus = request('status');
    $sort = request('sort', 'order_asc');

    $statusTabs = [
        null => ['label' => 'Все', 'icon' => 'ri-layout-grid-line', 'count' => $summary['total'] ?? 0],
        'active' => ['label' => 'Активные', 'icon' => 'ri-eye-line', 'count' => $summary['active'] ?? 0],
        'hidden' => ['label' => 'Скрытые', 'icon' => 'ri-eye-off-line', 'count' => $summary['hidden'] ?? 0],
        'missing_mobile' => ['label' => 'Без mobile', 'icon' => 'ri-smartphone-line', 'count' => max(0, ($summary['total'] ?? 0) - ($summary['mobile_ready'] ?? 0))],
    ];

    $baseFilters = array_filter([
        'q' => $search,
        'sort' => $sort !== 'order_asc' ? $sort : null,
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
                    <i class="ri-image-line"></i>
                    Панель администратора
                </div>
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Баннеры</h1>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                    Управление hero-баннерами главной: порядок показа, адаптивные изображения, ссылки и активность.
                </p>
            </div>

            <a href="{{ route('admin.banners.create') }}"
               class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                <i class="ri-add-line text-lg"></i>
                Добавить баннер
            </a>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-slate-500">
                <span>Всего</span>
                <i class="ri-gallery-line text-indigo-500"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($summary['total'] ?? 0, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-slate-400">Все баннеры</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-emerald-700">
                <span>Активные</span>
                <i class="ri-eye-line"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-emerald-800">{{ number_format($summary['active'] ?? 0, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-emerald-700/70">Показываются на сайте</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-slate-500">
                <span>Скрытые</span>
                <i class="ri-eye-off-line text-indigo-500"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($summary['hidden'] ?? 0, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-slate-400">Не участвуют в показе</p>
        </div>
        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-indigo-700">
                <span>Mobile-ready</span>
                <i class="ri-smartphone-line"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-indigo-800">{{ number_format($summary['mobile_ready'] ?? 0, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-indigo-700/70">Есть отдельная mobile-версия</p>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-3 sm:p-4">
            <form method="GET" action="{{ route('admin.banners.index') }}" class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_220px_220px_auto]">
                <label class="relative block">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="search"
                           name="q"
                           value="{{ $search }}"
                           placeholder="ID, название или ссылка"
                           class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                </label>

                <select name="status" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Любой статус</option>
                    <option value="active" @selected($currentStatus === 'active')>Активные</option>
                    <option value="hidden" @selected($currentStatus === 'hidden')>Скрытые</option>
                    <option value="missing_mobile" @selected($currentStatus === 'missing_mobile')>Без mobile-версии</option>
                </select>

                <select name="sort" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <option value="order_asc" @selected($sort === 'order_asc')>Порядок 1 → 9</option>
                    <option value="order_desc" @selected($sort === 'order_desc')>Порядок 9 → 1</option>
                    <option value="latest" @selected($sort === 'latest')>Сначала новые</option>
                    <option value="oldest" @selected($sort === 'oldest')>Сначала старые</option>
                    <option value="title" @selected($sort === 'title')>По названию</option>
                </select>

                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                    <i class="ri-filter-3-line"></i>
                    Применить
                </button>
            </form>

            @if($search || $currentStatus || $sort !== 'order_asc')
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                    <span class="font-semibold uppercase tracking-wide text-slate-400">Фильтр:</span>
                    @if($search)
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">Поиск: {{ $search }}</span>
                    @endif
                    @if($currentStatus)
                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700">Статус: {{ $statusTabs[$currentStatus]['label'] ?? $currentStatus }}</span>
                    @endif
                    <a href="{{ route('admin.banners.index') }}" class="rounded-full border border-slate-200 px-2.5 py-1 font-semibold text-slate-500 transition hover:border-indigo-200 hover:text-indigo-700">
                        Сбросить
                    </a>
                </div>
            @endif
        </div>

        <div class="overflow-x-auto border-b border-slate-100 px-3 py-2">
            <div class="flex min-w-max items-center gap-2">
                @foreach($statusTabs as $key => $tab)
                    @php
                        $isActive = ($currentStatus === null && $key === null) || ($currentStatus !== null && (string) $currentStatus === (string) $key);
                        $href = $key === null
                            ? route('admin.banners.index', $baseFilters)
                            : route('admin.banners.index', array_merge($baseFilters, ['status' => $key]));
                    @endphp
                    <a href="{{ $href }}"
                       class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm transition {{ $isActive ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}">
                        <i class="{{ $tab['icon'] }}"></i>
                        <span>{{ $tab['label'] }}</span>
                        <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold">{{ number_format($tab['count'], 0, ',', ' ') }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        @if($banners->count() === 0)
            <div class="px-6 py-14 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                    <i class="ri-image-line"></i>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-slate-900">Баннеры не найдены</h2>
                <p class="mt-1 text-sm text-slate-500">Попробуйте снять фильтр или добавьте первый баннер.</p>
                <a href="{{ route('admin.banners.create') }}" class="mt-5 inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-700">
                    <i class="ri-add-line"></i>
                    Добавить баннер
                </a>
            </div>
        @else
            <div class="grid gap-4 p-3 sm:p-4 xl:grid-cols-2 2xl:grid-cols-3">
                @foreach($banners as $banner)
                    @php
                        $desktopImage = $banner->image_desktop ?: $banner->image_tablet ?: $banner->image_mobile ?: $banner->image;
                        $mobileImage = $banner->image_mobile ?: $desktopImage;
                        $desktopUrl = $desktopImage ? asset('storage/'.$desktopImage) : null;
                        $mobileUrl = $mobileImage ? asset('storage/'.$mobileImage) : null;
                    @endphp
                    <article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                        <div class="relative bg-slate-100">
                            <div class="aspect-[30/9] overflow-hidden">
                                @if($desktopUrl)
                                    <img src="{{ $desktopUrl }}" alt="{{ $banner->title ?: 'Баннер' }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.03]">
                                @else
                                    <div class="flex h-full items-center justify-center text-sm text-slate-400">Нет desktop изображения</div>
                                @endif
                            </div>

                            <div class="absolute left-3 top-3 flex flex-wrap gap-2">
                                <span class="rounded-full bg-white/90 px-2.5 py-1 text-xs font-bold text-slate-700 shadow-sm backdrop-blur">ID {{ $banner->id }}</span>
                                <span class="rounded-full bg-white/90 px-2.5 py-1 text-xs font-bold text-slate-700 shadow-sm backdrop-blur">Порядок {{ $banner->sort_order }}</span>
                            </div>

                            <div class="absolute right-3 top-3">
                                @if($banner->active)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500 px-2.5 py-1 text-xs font-bold text-white shadow-sm">
                                        <i class="ri-eye-line"></i>
                                        Активен
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-700 px-2.5 py-1 text-xs font-bold text-white shadow-sm">
                                        <i class="ri-eye-off-line"></i>
                                        Скрыт
                                    </span>
                                @endif
                            </div>

                            <div class="absolute bottom-3 right-3 hidden w-20 overflow-hidden rounded-xl border-2 border-white bg-slate-200 shadow-lg sm:block">
                                <div class="aspect-[9/16]">
                                    @if($mobileUrl)
                                        <img src="{{ $mobileUrl }}" alt="Mobile preview" class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full items-center justify-center text-[10px] text-slate-400">mobile</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h2 class="truncate text-base font-bold text-slate-950">{{ $banner->title ?: 'Без названия' }}</h2>
                                    <p class="mt-1 text-xs text-slate-400">Создан {{ $banner->created_at?->format('d.m.Y H:i') }}</p>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <a href="{{ route('admin.banners.edit', $banner) }}" class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700 transition hover:bg-indigo-100" title="Редактировать">
                                        <i class="ri-edit-2-line"></i>
                                    </a>
                                    <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" onsubmit="return confirm('Удалить баннер?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-700 transition hover:bg-rose-100" title="Удалить">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-2 text-xs sm:grid-cols-3">
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <div class="font-semibold text-slate-400">Desktop</div>
                                    <div class="mt-1 font-bold {{ $banner->image_desktop ? 'text-emerald-700' : 'text-amber-700' }}">{{ $banner->image_desktop ? 'Есть' : 'Нет' }}</div>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <div class="font-semibold text-slate-400">Tablet</div>
                                    <div class="mt-1 font-bold {{ $banner->image_tablet ? 'text-emerald-700' : 'text-amber-700' }}">{{ $banner->image_tablet ? 'Есть' : 'Нет' }}</div>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-3">
                                    <div class="font-semibold text-slate-400">Mobile</div>
                                    <div class="mt-1 font-bold {{ $banner->image_mobile ? 'text-emerald-700' : 'text-amber-700' }}">{{ $banner->image_mobile ? 'Есть' : 'Нет' }}</div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                @if($banner->link)
                                    <a href="{{ $banner->link }}" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between gap-2 font-semibold text-indigo-600 hover:text-indigo-800">
                                        <span class="truncate">{{ $banner->link }}</span>
                                        <i class="ri-arrow-right-up-line shrink-0"></i>
                                    </a>
                                @else
                                    <div class="flex items-center gap-2 text-slate-400">
                                        <i class="ri-link-unlink"></i>
                                        Ссылка не задана
                                    </div>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @if($banners->hasPages())
        <div>{{ $banners->links() }}</div>
    @endif
</div>
@endsection
