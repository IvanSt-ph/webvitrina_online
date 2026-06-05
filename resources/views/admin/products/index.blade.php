@extends('admin.layout')

@section('title', 'Товары')

@section('content')
@php
    $search = request('q');
    $currentStatus = request('status');
    $currentStock = request('stock');
    $currentCategory = request('category_id');
    $currentSeller = request('seller_id');
    $currentDiscount = request()->boolean('discount');
    $currentSort = request('sort', 'latest');

    $tabs = [
        '' => ['label' => 'Все', 'icon' => 'ri-box-3-line'],
        'active' => ['label' => 'Опубликованы', 'icon' => 'ri-checkbox-circle-line'],
        'draft' => ['label' => 'Черновики', 'icon' => 'ri-draft-line'],
        'blocked' => ['label' => 'Заблокированы', 'icon' => 'ri-lock-2-line'],
    ];

    $statusLabels = [
        'active' => 'Опубликован',
        'draft' => 'Черновик',
        'blocked' => 'Заблокирован',
    ];

    $statusClasses = [
        'active' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'draft' => 'border-slate-200 bg-slate-50 text-slate-600',
        'blocked' => 'border-rose-200 bg-rose-50 text-rose-700',
    ];

    $stockLabels = [
        'out' => 'Нет в наличии',
        'low' => 'Заканчивается',
        'available' => 'В наличии',
    ];

    $baseFilters = array_filter([
        'q' => $search,
        'stock' => $currentStock,
        'category_id' => $currentCategory,
        'seller_id' => $currentSeller,
        'discount' => $currentDiscount ? 1 : null,
        'sort' => $currentSort !== 'latest' ? $currentSort : null,
    ], fn ($value) => filled($value));

    $hasAdvancedFilters = filled($search)
        || filled($currentStock)
        || filled($currentCategory)
        || filled($currentSeller)
        || $currentDiscount
        || $currentSort !== 'latest';
@endphp

<div
    x-data="{
        query: '',
        results: [],
        loading: false,
        summaryOpen: false,
        filterOpen: {{ $hasAdvancedFilters ? 'true' : 'false' }},
        viewMode: 'list',

        init() {
            try {
                const storedView = localStorage.getItem('adminProductsView');
                if (storedView === 'list' || storedView === 'grid') {
                    this.viewMode = storedView;
                }
            } catch (error) {
                this.viewMode = 'list';
            }
        },

        setViewMode(mode) {
            this.viewMode = mode;
            try {
                localStorage.setItem('adminProductsView', mode);
            } catch (error) {
                return;
            }
        },

        escapeHtml(text) {
            const ampersand = String.fromCharCode(38);
            return String(text ?? '').replace(/[&<>']/g, character => ({
                '&': ampersand + 'amp;',
                '<': ampersand + 'lt;',
                '>': ampersand + 'gt;',
                '\'': ampersand + '#039;'
            }[character])).replaceAll(String.fromCharCode(34), ampersand + '#034;');
        },

        highlight(text) {
            const escapedText = this.escapeHtml(text);
            if (!this.query) return escapedText;
            const safeQuery = this.query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp('(' + safeQuery + ')', 'gi');
            return escapedText.replace(regex, '<mark class=\'rounded bg-yellow-200 px-0.5 text-slate-900\'>$1</mark>');
        },

        async search() {
            if (this.query.trim().length < 2) {
                this.results = [];
                return;
            }

            this.loading = true;
            try {
                const response = await fetch(`{{ route('admin.products.search') }}?q=${encodeURIComponent(this.query.trim())}`);
                this.results = response.ok ? await response.json() : [];
            } catch (error) {
                this.results = [];
            } finally {
                this.loading = false;
            }
        },

        clearSearch() {
            this.query = '';
            this.results = [];
        }
    }"
    class="space-y-5">
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

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between sm:p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <i class="ri-box-3-line text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-950">Товары</h1>
                    <div class="flex flex-wrap gap-x-3 text-xs text-slate-500">
                        <span>Всего: <b class="text-slate-700">{{ number_format($summary['total'], 0, ',', ' ') }}</b></span>
                        <span>В продаже: <b class="text-emerald-700">{{ number_format($summary['active'], 0, ',', ' ') }}</b></span>
                        @if($summary['out_of_stock'])
                            <span class="font-semibold text-amber-700">Без остатка: {{ number_format($summary['out_of_stock'], 0, ',', ' ') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button"
                        @click="filterOpen = !filterOpen"
                        :aria-expanded="filterOpen"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    <i class="ri-filter-3-line"></i>
                    Фильтры
                    @if($hasAdvancedFilters)
                        <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-100 px-1.5 text-[11px] font-bold text-indigo-700">!</span>
                    @endif
                    <i class="ri-arrow-down-s-line transition" :class="{ 'rotate-180': filterOpen }"></i>
                </button>
                <button type="button"
                        @click="summaryOpen = !summaryOpen"
                        :aria-expanded="summaryOpen"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    <i class="ri-bar-chart-box-line"></i>
                    Сводка
                    <i class="ri-arrow-down-s-line transition" :class="{ 'rotate-180': summaryOpen }"></i>
                </button>
                <details class="relative">
                    <summary class="inline-flex h-10 cursor-pointer list-none items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50">
                        <i class="ri-more-2-fill"></i>
                        Обслуживание
                    </summary>
                    <form action="{{ route('admin.products.purge-old') }}" method="POST"
                          class="absolute right-0 z-20 mt-2 w-72 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                        @csrf
                        <p class="mb-3 text-xs leading-5 text-slate-500">Безвозвратно очистить товары, удалённые более 90 дней назад.</p>
                        <button type="submit"
                                onclick="return confirm('Удалить товары, удалённые более 90 дней назад?')"
                                class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-lg border border-rose-200 bg-rose-50 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                            <i class="ri-delete-bin-6-line"></i>
                            Очистить архив
                        </button>
                    </form>
                </details>
                <a href="{{ route('admin.products.create') }}"
                   class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                    <i class="ri-add-line text-lg"></i>
                    Добавить товар
                </a>
            </div>
        </div>

        <div x-cloak x-show="summaryOpen" x-transition class="grid gap-3 border-t border-slate-100 p-3 sm:grid-cols-2 sm:p-4 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
            <div class="flex items-center justify-between text-sm text-slate-500">
                <span>Всего товаров</span>
                <i class="ri-box-3-line text-indigo-500"></i>
            </div>
            <div class="mt-1 text-xl font-bold text-slate-950">{{ number_format($summary['total'], 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-slate-400">Включая черновики</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3">
            <div class="flex items-center justify-between text-sm text-emerald-700">
                <span>Опубликованы</span>
                <i class="ri-checkbox-circle-line"></i>
            </div>
            <div class="mt-1 text-xl font-bold text-emerald-800">{{ number_format($summary['active'], 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-emerald-700/70">Видны покупателям</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
            <div class="flex items-center justify-between text-sm text-slate-500">
                <span>Черновики</span>
                <i class="ri-draft-line text-indigo-500"></i>
            </div>
            <div class="mt-1 text-xl font-bold text-slate-950">{{ number_format($summary['draft'], 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-slate-400">Не опубликованы</p>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-3">
            <div class="flex items-center justify-between text-sm text-rose-700">
                <span>Заблокированы</span>
                <i class="ri-lock-2-line"></i>
            </div>
            <div class="mt-1 text-xl font-bold text-rose-800">{{ number_format($summary['blocked'] ?? 0, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-rose-700/70">Сняты администратором</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3">
            <div class="flex items-center justify-between text-sm text-amber-700">
                <span>Требуют внимания</span>
                <i class="ri-alarm-warning-line"></i>
            </div>
            <div class="mt-1 text-xl font-bold text-amber-800">{{ number_format($summary['out_of_stock'], 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-amber-700/70">Опубликованы без остатка</p>
        </div>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div x-cloak x-show="filterOpen" x-transition class="border-b border-slate-100 bg-slate-50/40 p-3 sm:p-4">
            <form method="GET" action="{{ route('admin.products.index') }}" class="grid gap-3 xl:grid-cols-[minmax(220px,1fr)_150px_160px_170px_170px_150px_180px_auto]">
                <label class="relative block">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="search"
                           name="q"
                           value="{{ $search }}"
                           placeholder="Название, SKU, продавец или категория"
                           class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                </label>

                <select name="status" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Все статусы</option>
                    <option value="active" @selected($currentStatus === 'active')>Опубликованы</option>
                    <option value="draft" @selected($currentStatus === 'draft')>Черновики</option>
                    <option value="blocked" @selected($currentStatus === 'blocked')>Заблокированы</option>
                </select>

                <select name="stock" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Любой остаток</option>
                    @foreach($stockLabels as $key => $label)
                        <option value="{{ $key }}" @selected($currentStock === $key)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="category_id" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Все категории</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $currentCategory === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>

                <select name="seller_id" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Все продавцы</option>
                    @foreach($sellers as $seller)
                        <option value="{{ $seller->id }}" @selected((string) $currentSeller === (string) $seller->id)>{{ $seller->name }}</option>
                    @endforeach
                </select>

                <label class="flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">
                    <input type="checkbox" name="discount" value="1" @checked($currentDiscount) class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Со скидкой</span>
                </label>

                <select name="sort" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <option value="latest" @selected($currentSort === 'latest')>Сначала новые</option>
                    <option value="oldest" @selected($currentSort === 'oldest')>Сначала старые</option>
                    <option value="price_desc" @selected($currentSort === 'price_desc')>Цена по убыванию</option>
                    <option value="price_asc" @selected($currentSort === 'price_asc')>Цена по возрастанию</option>
                    <option value="stock_asc" @selected($currentSort === 'stock_asc')>Мало остатка</option>
                    <option value="views_desc" @selected($currentSort === 'views_desc')>Больше просмотров</option>
                </select>

                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                    <i class="ri-filter-3-line"></i>
                    Применить
                </button>
            </form>

            @if($search || $currentStatus || $currentStock || $currentCategory || $currentSeller || $currentDiscount || $currentSort !== 'latest')
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                    <span class="font-semibold uppercase tracking-wide text-slate-400">Фильтр:</span>
                    @if($search)
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">Поиск: {{ $search }}</span>
                    @endif
                    @if($currentStatus)
                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700">Статус: {{ $statusLabels[$currentStatus] ?? $currentStatus }}</span>
                    @endif
                    @if($currentStock)
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700">Остаток: {{ $stockLabels[$currentStock] ?? $currentStock }}</span>
                    @endif
                    @if($currentCategory)
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">Категория выбрана</span>
                    @endif
                    @if($currentSeller)
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">Продавец выбран</span>
                    @endif
                    @if($currentDiscount)
                        <span class="rounded-full bg-red-50 px-2.5 py-1 text-red-700">Со скидкой</span>
                    @endif
                    <a href="{{ route('admin.products.index') }}" class="rounded-full border border-slate-200 px-2.5 py-1 font-semibold text-slate-500 transition hover:border-indigo-200 hover:text-indigo-700">
                        Сбросить
                    </a>
                </div>
            @endif
        </div>

        <div class="grid gap-3 border-b border-slate-100 p-3 lg:grid-cols-[minmax(260px,420px)_1fr] lg:items-center sm:p-4">
            <div class="relative">
                <label class="relative block">
                    <i class="ri-flashlight-line absolute left-3 top-1/2 -translate-y-1/2 text-indigo-500"></i>
                    <input type="search"
                           x-model="query"
                           @input.debounce.350ms="search"
                           placeholder="Быстро открыть товар по названию или SKU"
                           class="h-11 w-full rounded-xl border border-indigo-100 bg-indigo-50/40 pl-10 pr-10 text-sm outline-none transition focus:border-indigo-300 focus:bg-white focus:ring-4 focus:ring-indigo-100">
                    <i x-show="loading" class="ri-loader-4-line absolute right-3 top-1/2 -translate-y-1/2 animate-spin text-indigo-500"></i>
                </label>

                <div x-show="results.length" x-transition @click.outside="results = []"
                     class="absolute z-30 mt-2 max-h-96 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-xl">
                    <template x-for="product in results" :key="product.id">
                        <a :href="`/admin/products/${product.id}/edit`" class="flex items-center gap-3 border-b border-slate-100 px-3 py-3 transition last:border-none hover:bg-indigo-50">
                            <img :src="product.image ? '/storage/' + product.image : '/images/no-image.png'"
                                 class="h-11 w-11 rounded-lg border border-slate-200 object-cover" alt="">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold text-slate-900" x-html="highlight(product.title)"></div>
                                <div class="mt-0.5 flex gap-2 text-xs text-slate-500">
                                    <span x-html="'SKU: ' + highlight(product.sku ?? '')"></span>
                                    <span x-text="Number(product.price).toLocaleString('ru-RU')"></span>
                                </div>
                            </div>
                            <i class="ri-arrow-right-s-line text-slate-400"></i>
                        </a>
                    </template>
                    <button type="button" @click="clearSearch()" class="h-10 w-full text-xs font-semibold text-slate-500 transition hover:bg-slate-50">Очистить поиск</button>
                </div>
            </div>

            <p class="text-xs leading-5 text-slate-400 lg:text-right">
                Быстрый переход открывает редактирование. Для отбора всего каталога используйте фильтры выше.
            </p>
        </div>

        <div class="flex flex-col gap-3 border-b border-slate-100 px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-max items-center gap-2 overflow-x-auto">
                @foreach($tabs as $key => $tab)
                    @php
                        $isActive = (! filled($currentStatus) && $key === '') || ((string) $currentStatus === (string) $key);
                        $count = $key === '' ? $summary['total'] : ($summary[$key] ?? 0);
                        $href = $key === ''
                            ? route('admin.products.index', $baseFilters)
                            : route('admin.products.index', array_merge($baseFilters, ['status' => $key]));
                    @endphp
                    <a href="{{ $href }}"
                       class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm transition {{ $isActive ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}">
                        <i class="{{ $tab['icon'] }}"></i>
                        <span>{{ $tab['label'] }}</span>
                        <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold">{{ number_format($count, 0, ',', ' ') }}</span>
                    </a>
                @endforeach
                <a href="{{ route('admin.products.index', array_merge($baseFilters, ['discount' => 1])) }}"
                   class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm transition {{ $currentDiscount ? 'border-red-200 bg-red-50 text-red-700' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}">
                    <i class="ri-price-tag-3-line"></i>
                    <span>Со скидкой</span>
                    <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold">{{ number_format($summary['discount'] ?? 0, 0, ',', ' ') }}</span>
                </a>
            </div>
            <div class="flex shrink-0 rounded-lg border border-slate-200 bg-slate-50 p-1" aria-label="Вид каталога">
                <button type="button"
                        @click="setViewMode('list')"
                        :class="viewMode === 'list' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500'"
                        class="flex h-8 items-center gap-1.5 rounded-md px-3 text-xs font-semibold transition"
                        title="Показать списком">
                    <i class="ri-list-check-2"></i>
                    Список
                </button>
                <button type="button"
                        @click="setViewMode('grid')"
                        :class="viewMode === 'grid' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-500'"
                        class="flex h-8 items-center gap-1.5 rounded-md px-3 text-xs font-semibold transition"
                        title="Показать плиткой">
                    <i class="ri-layout-grid-line"></i>
                    Плитка
                </button>
            </div>
        </div>

        <div x-cloak x-show="viewMode === 'list'" class="hidden xl:block">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Товар</th>
                        <th class="px-4 py-3 text-left font-semibold">Продавец</th>
                        <th class="px-4 py-3 text-left font-semibold">Категория</th>
                        <th class="px-4 py-3 text-left font-semibold">Цена и остаток</th>
                        <th class="px-4 py-3 text-left font-semibold">Состояние</th>
                        <th class="px-4 py-3 text-right font-semibold">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        @php
                            $isActive = $product->status === 'active';
                            $statusLabel = $statusLabels[$product->status] ?? $product->status;
                            $statusClass = $statusClasses[$product->status] ?? 'border-slate-200 bg-slate-50 text-slate-600';
                            $stockClass = $product->stock === 0
                                ? 'border-rose-200 bg-rose-50 text-rose-700'
                                : ($product->stock <= 5
                                    ? 'border-amber-200 bg-amber-50 text-amber-700'
                                    : 'border-emerald-200 bg-emerald-50 text-emerald-700');
                        @endphp
                        <tr class="align-top transition hover:bg-indigo-50/25">
                            <td class="px-4 py-4">
                                <div class="flex gap-3">
                                    <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('images/no-image.png') }}"
                                         class="h-14 w-14 rounded-lg border border-slate-200 object-cover"
                                         alt="{{ $product->title }}">
                                    <div class="min-w-0 max-w-xs">
                                        <div class="line-clamp-2 font-bold text-slate-950">{{ $product->title }}</div>
                                        <div class="mt-1 text-xs text-slate-400">ID {{ $product->id }} · SKU {{ $product->sku ?: 'не задан' }}</div>
                                        <div class="mt-2 text-xs text-slate-400">{{ $product->created_at?->format('d.m.Y H:i') }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                @if($product->user_id)
                                    <a href="{{ route('admin.users.show', $product->user_id) }}" class="font-semibold text-slate-800 transition hover:text-indigo-700">
                                        {{ $product->seller?->name ?: 'Продавец удалён' }}
                                    </a>
                                @else
                                    <span class="text-slate-400">Не назначен</span>
                                @endif
                                <div class="mt-1 text-xs text-slate-400">{{ $product->city?->name ?: 'Город не указан' }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ $product->category?->name ?: 'Без категории' }}</td>
                            <td class="px-4 py-4">
                                <div class="font-bold text-slate-950">{{ number_format((float) $product->price, 2, ',', ' ') }} ₽</div>
                                @if($product->discount_percent)
                                    <div class="mt-1 flex flex-wrap items-center gap-1">
                                        <span class="text-xs text-slate-400 line-through">{{ number_format((float) $product->old_price, 2, ',', ' ') }} ₽</span>
                                        <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-bold text-red-700">-{{ $product->discount_percent }}%</span>
                                    </div>
                                @endif
                                <span class="mt-2 inline-flex rounded-full border px-2 py-1 text-xs font-semibold {{ $stockClass }}">
                                    {{ $product->stock }} шт.
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                                <div class="mt-2 text-xs text-slate-400">{{ number_format((int) ($product->views_count ?? 0), 0, ',', ' ') }} просмотров</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    @if($isActive)
                                        <a href="{{ route('product.show', $product->slug ?: $product->id) }}"
                                           class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-indigo-200 hover:text-indigo-700"
                                           title="Открыть на сайте">
                                            <i class="ri-external-link-line"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.products.edit', $product) }}"
                                       class="flex h-9 w-9 items-center justify-center rounded-lg border border-indigo-100 bg-indigo-50 text-indigo-700 transition hover:bg-indigo-100"
                                       title="Редактировать">
                                        <i class="ri-edit-2-line"></i>
                                    </a>
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                          onsubmit="return confirm('Удалить этот товар?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="flex h-9 w-9 items-center justify-center rounded-lg border border-rose-100 bg-rose-50 text-rose-700 transition hover:bg-rose-100" title="Удалить">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <i class="ri-box-3-line text-4xl text-slate-300"></i>
                                <p class="mt-3 font-semibold text-slate-700">Товары не найдены</p>
                                <p class="mt-1 text-sm text-slate-400">По текущим условиям ничего не подходит. Сбросьте фильтры или создайте товар вручную.</p>
                                <div class="mt-4 flex justify-center gap-2">
                                    <a href="{{ route('admin.products.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Сбросить</a>
                                    <a href="{{ route('admin.products.create') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">Добавить товар</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div x-cloak x-show="viewMode === 'grid'" class="grid gap-2 bg-slate-50/50 p-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-7 2xl:grid-cols-8">
            @forelse($products as $product)
                @php
                    $isActive = $product->status === 'active';
                    $statusLabel = $statusLabels[$product->status] ?? $product->status;
                    $statusClass = $statusClasses[$product->status] ?? 'border-slate-200 bg-slate-50 text-slate-600';
                    $stockClass = $product->stock === 0
                        ? 'border-rose-200 bg-rose-50 text-rose-700'
                        : ($product->stock <= 5
                            ? 'border-amber-200 bg-amber-50 text-amber-700'
                            : 'border-emerald-200 bg-emerald-50 text-emerald-700');
                @endphp
                <article class="group min-w-0 overflow-hidden rounded-lg border border-slate-200 bg-white transition hover:-translate-y-px hover:border-indigo-200 hover:shadow-md hover:shadow-indigo-950/5">
                    <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
                        <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('images/no-image.png') }}"
                             class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                             alt="{{ $product->title }}">
                        @if(! $isActive)
                            <span class="absolute left-1.5 top-1.5 rounded-md border bg-white/95 px-1.5 py-0.5 text-[9px] font-semibold shadow-sm {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        @endif
                        @if($product->stock <= 5)
                            <span class="absolute bottom-1.5 right-1.5 rounded-md border bg-white/95 px-1.5 py-0.5 text-[9px] font-bold shadow-sm {{ $stockClass }}">
                                {{ $product->stock === 0 ? 'Нет в наличии' : 'Осталось ' . $product->stock }}
                            </span>
                        @endif
                        @if($product->discount_percent)
                            <span class="absolute right-1.5 top-1.5 rounded-md bg-red-500 px-1.5 py-0.5 text-[9px] font-bold text-white shadow-sm">
                                -{{ $product->discount_percent }}%
                            </span>
                        @endif
                    </div>
                    <div class="p-2">
                        <h2 class="truncate text-[11px] font-bold leading-4 text-slate-950" title="{{ $product->title }}">{{ $product->title }}</h2>
                        <div class="mt-1 flex items-center justify-between gap-1">
                            <span class="truncate text-xs font-bold text-indigo-700">
                                {{ number_format((float) $product->price, 0, ',', ' ') }} ₽
                                @if($product->discount_percent)
                                    <span class="ml-1 text-[9px] font-medium text-slate-400 line-through">{{ number_format((float) $product->old_price, 0, ',', ' ') }} ₽</span>
                                @endif
                            </span>
                            <span class="inline-flex shrink-0 items-center gap-1 text-[9px] text-slate-400">
                                <span class="h-1.5 w-1.5 rounded-full {{ $isActive ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                {{ $isActive ? 'В продаже' : $statusLabel }}
                            </span>
                        </div>
                        <div class="mt-2 flex gap-1 border-t border-slate-100 pt-1.5">
                            @if($isActive)
                                <a href="{{ route('product.show', $product->slug ?: $product->id) }}"
                                   class="flex h-7 w-7 items-center justify-center rounded-md border border-slate-200 text-xs text-slate-400 transition hover:border-indigo-200 hover:text-indigo-700"
                                   title="Открыть на сайте">
                                    <i class="ri-external-link-line"></i>
                                </a>
                            @endif
                            <a href="{{ route('admin.products.edit', $product) }}"
                               class="inline-flex h-7 flex-1 items-center justify-center rounded-md bg-indigo-50 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100"
                               title="Редактировать">
                                <i class="ri-edit-2-line"></i>
                            </a>
                            <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                  onsubmit="return confirm('Удалить этот товар?')">
                                @csrf
                                @method('DELETE')
                                <button class="flex h-7 w-7 items-center justify-center rounded-md text-xs text-slate-400 transition hover:bg-rose-50 hover:text-rose-700" title="Удалить">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full px-6 py-14 text-center">
                    <i class="ri-box-3-line text-4xl text-slate-300"></i>
                    <p class="mt-3 font-semibold text-slate-700">Товары не найдены</p>
                    <p class="mt-1 text-sm text-slate-400">По текущим условиям ничего не подходит. Сбросьте фильтры или создайте товар вручную.</p>
                    <div class="mt-4 flex justify-center gap-2">
                        <a href="{{ route('admin.products.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Сбросить</a>
                        <a href="{{ route('admin.products.create') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">Добавить товар</a>
                    </div>
                </div>
            @endforelse
        </div>

        <div x-cloak x-show="viewMode === 'list'" class="divide-y divide-slate-100 xl:hidden">
            @forelse($products as $product)
                @php
                    $isActive = $product->status === 'active';
                    $statusLabel = $statusLabels[$product->status] ?? $product->status;
                    $statusClass = $statusClasses[$product->status] ?? 'border-slate-200 bg-slate-50 text-slate-600';
                    $stockClass = $product->stock === 0
                        ? 'border-rose-200 bg-rose-50 text-rose-700'
                        : ($product->stock <= 5
                            ? 'border-amber-200 bg-amber-50 text-amber-700'
                            : 'border-emerald-200 bg-emerald-50 text-emerald-700');
                @endphp
                <article class="p-4">
                    <div class="flex gap-3">
                        <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('images/no-image.png') }}"
                             class="h-16 w-16 shrink-0 rounded-xl border border-slate-200 object-cover"
                             alt="{{ $product->title }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap gap-1.5">
                                <span class="rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                                <span class="rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $stockClass }}">{{ $product->stock }} шт.</span>
                                @if($product->discount_percent)
                                    <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-bold text-red-700">-{{ $product->discount_percent }}%</span>
                                @endif
                            </div>
                            <h2 class="mt-2 line-clamp-2 text-sm font-bold text-slate-950">{{ $product->title }}</h2>
                            <p class="mt-1 text-xs text-slate-400">ID {{ $product->id }} · SKU {{ $product->sku ?: 'не задан' }}</p>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <div class="text-xs text-slate-400">Цена</div>
                            <div class="mt-1 font-bold text-slate-950">{{ number_format((float) $product->price, 2, ',', ' ') }} ₽</div>
                            @if($product->discount_percent)
                                <div class="mt-0.5 text-xs text-slate-400 line-through">{{ number_format((float) $product->old_price, 2, ',', ' ') }} ₽</div>
                            @endif
                        </div>
                        <div>
                            <div class="text-xs text-slate-400">Продавец</div>
                            @if($product->user_id)
                                <a href="{{ route('admin.users.show', $product->user_id) }}" class="mt-1 block truncate font-semibold text-indigo-700">{{ $product->seller?->name ?: 'Продавец удалён' }}</a>
                            @else
                                <div class="mt-1 text-slate-500">Не назначен</div>
                            @endif
                        </div>
                        <div>
                            <div class="text-xs text-slate-400">Категория</div>
                            <div class="mt-1 truncate text-slate-600">{{ $product->category?->name ?: 'Без категории' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400">Просмотры</div>
                            <div class="mt-1 text-slate-600">{{ number_format((int) ($product->views_count ?? 0), 0, ',', ' ') }}</div>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2 border-t border-slate-100 pt-3">
                        @if($isActive)
                            <a href="{{ route('product.show', $product->slug ?: $product->id) }}"
                               class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-600">
                                <i class="ri-external-link-line"></i>
                                На сайте
                            </a>
                        @endif
                        <a href="{{ route('admin.products.edit', $product) }}"
                           class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-lg bg-indigo-50 text-sm font-semibold text-indigo-700">
                            <i class="ri-edit-2-line"></i>
                            Изменить
                        </a>
                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                              onsubmit="return confirm('Удалить этот товар?')">
                            @csrf
                            @method('DELETE')
                            <button class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-50 text-rose-700" title="Удалить">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="px-6 py-14 text-center">
                    <i class="ri-box-3-line text-4xl text-slate-300"></i>
                    <p class="mt-3 font-semibold text-slate-700">Товары не найдены</p>
                    <p class="mt-1 text-sm text-slate-400">По текущим условиям ничего не подходит. Сбросьте фильтры или создайте товар вручную.</p>
                    <div class="mt-4 flex justify-center gap-2">
                        <a href="{{ route('admin.products.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-600 hover:bg-slate-50">Сбросить</a>
                        <a href="{{ route('admin.products.create') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">Добавить товар</a>
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    @if($products->hasPages() || $products->count())
        <div class="flex flex-col items-center justify-between gap-3 text-sm text-slate-500 sm:flex-row">
            <span>
                Показано <strong class="text-slate-800">{{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }}</strong>
                из <strong class="text-slate-800">{{ $products->total() }}</strong>
            </span>
            {{ $products->onEachSide(1)->links() }}
        </div>
    @endif
</div>
@endsection
