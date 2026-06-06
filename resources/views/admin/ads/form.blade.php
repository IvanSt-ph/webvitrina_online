@extends('admin.layout')

@section('title', $campaign->exists ? 'Редактировать рекламу' : 'Новая реклама')

@section('content')
@php
    $selectedType = old('target_type', $campaign->target_type ?: \App\Models\AdCampaign::TYPE_PRODUCT);
    $selectedSlotId = old('ad_slot_id', $campaign->ad_slot_id);
    $selectedProductId = old('product_id', $campaign->product_id);
    $selectedShopId = old('shop_id', $campaign->shop_id);
    $selectedProductLabel = $selectedProduct
        ? '#' . $selectedProduct->id . ' · ' . $selectedProduct->title . ($selectedProduct->seller?->shop?->name ? ' · ' . $selectedProduct->seller->shop->name : '')
        : '';
    $selectedShopLabel = $selectedShop
        ? '#' . $selectedShop->id . ' · ' . $selectedShop->name
        : '';
@endphp

<div class="space-y-5">
    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('admin.ads.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-indigo-700">
                    <i class="ri-arrow-left-line"></i>
                    Реклама / Продвижение
                </a>
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                    {{ $campaign->exists ? 'Редактировать кампанию' : 'Новая кампания' }}
                </h1>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500">
                    Кампания управляется вручную администратором. На публичной части обязательно показывается метка продвижения.
                </p>
            </div>
        </div>
    </section>

    <form method="POST" action="{{ $campaign->exists ? route('admin.ads.update', $campaign) : route('admin.ads.store') }}" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        @csrf
        @if($campaign->exists)
            @method('PUT')
        @endif

        <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Название кампании</span>
                    <input type="text"
                           name="title"
                           value="{{ old('title', $campaign->title) }}"
                           required
                           maxlength="255"
                           class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                           placeholder="Например: Летние товары продавца">
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Метка на сайте</span>
                    <input type="text"
                           name="label"
                           value="{{ old('label', $campaign->label ?: 'Продвигается') }}"
                           maxlength="80"
                           class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                </label>
            </div>

            <label class="block">
                <span class="text-sm font-semibold text-slate-700">Описание для админки</span>
                <textarea name="description"
                          rows="3"
                          maxlength="1000"
                          class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                          placeholder="Заметка: почему продвигаем, кто попросил, где показывать">{{ old('description', $campaign->description) }}</textarea>
            </label>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Рекламный слот</span>
                    <select name="ad_slot_id" required class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Выберите слот</option>
                        @foreach($slots as $slot)
                            @php
                                $guide = $slotGuide[$slot->key] ?? null;
                            @endphp
                            <option value="{{ $slot->id }}" @selected((string) old('ad_slot_id', $campaign->ad_slot_id) === (string) $slot->id)>
                                {{ $slot->name }} · {{ $guide['enabled'] ?? false ? 'выводится' : 'подготовлен' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="mt-1 block text-xs text-slate-400">Слот определяет место на сайте. Ниже есть расшифровка по каждому варианту.</span>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Тип цели</span>
                    <select name="target_type" id="ad-target-type" required class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                        @foreach($targetTypes as $key => $label)
                            <option value="{{ $key }}" @selected($selectedType === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="block" data-target-panel="product">
                    <span class="text-sm font-semibold text-slate-700">Товар</span>
                    <input type="hidden" name="product_id" id="ad-product-id" value="{{ $selectedProductId }}">
                    <div class="relative mt-1">
                        <input type="search"
                               id="ad-product-search"
                               value="{{ $selectedProductLabel }}"
                               autocomplete="off"
                               class="h-11 w-full rounded-xl border border-slate-200 px-3 pr-10 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                               placeholder="Введите название товара или ID">
                        <button type="button" id="ad-product-clear" class="absolute right-2 top-1/2 hidden h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Очистить товар">
                            <i class="ri-close-line"></i>
                        </button>
                        <div id="ad-product-results" class="absolute z-30 mt-2 hidden max-h-72 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-xl"></div>
                    </div>
                    <span class="mt-1 block text-xs text-slate-400">Начните вводить 2 символа. Поиск работает по названию и ID товара.</span>
                </div>

                <div class="block" data-target-panel="shop">
                    <span class="text-sm font-semibold text-slate-700">Магазин</span>
                    <input type="hidden" name="shop_id" id="ad-shop-id" value="{{ $selectedShopId }}">
                    <div class="relative mt-1">
                        <input type="search"
                               id="ad-shop-search"
                               value="{{ $selectedShopLabel }}"
                               autocomplete="off"
                               class="h-11 w-full rounded-xl border border-slate-200 px-3 pr-10 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                               placeholder="Введите название магазина или ID">
                        <button type="button" id="ad-shop-clear" class="absolute right-2 top-1/2 hidden h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Очистить магазин">
                            <i class="ri-close-line"></i>
                        </button>
                        <div id="ad-shop-results" class="absolute z-30 mt-2 hidden max-h-72 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-xl"></div>
                    </div>
                    <span class="mt-1 block text-xs text-slate-400">Так удобнее, когда продавцов и товаров станет много.</span>
                </div>
            </div>

            <label class="block" data-target-panel="custom">
                <span class="text-sm font-semibold text-slate-700">Своя ссылка</span>
                <input type="text"
                       name="destination_url"
                       value="{{ old('destination_url', $campaign->destination_url) }}"
                       maxlength="500"
                       class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                       placeholder="/category/odezhda или https://example.com">
                <span class="mt-1 block text-xs text-slate-400">Для товара и магазина ссылка строится автоматически.</span>
            </label>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Категория показа</span>
                    <select name="category_id" class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Все подходящие страницы</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $campaign->category_id) === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    <span class="mt-1 block text-xs text-slate-400">Работает для слота “Популярное в категории”. Если не выбрать, блок общий.</span>
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-slate-700">Лимит показов</span>
                    <input type="number"
                           name="max_impressions"
                           value="{{ old('max_impressions', $campaign->max_impressions) }}"
                           min="1"
                           max="100000000"
                           class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                           placeholder="Без лимита">
                    <span class="mt-1 block text-xs text-slate-400">Если заполнить, кампания остановится после указанного числа показов.</span>
                </label>
            </div>
        </section>

        <aside class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-base font-bold text-slate-950">Расшифровка слотов</h2>
                <div class="mt-3 space-y-3">
                    @foreach($slots as $slot)
                        @php
                            $guide = $slotGuide[$slot->key] ?? null;
                        @endphp
                        <div class="rounded-xl border {{ (string) $selectedSlotId === (string) $slot->id ? 'border-indigo-200 bg-indigo-50' : 'border-slate-200 bg-slate-50' }} p-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="font-bold text-slate-900">{{ $slot->name }}</div>
                                <span class="shrink-0 rounded-full {{ ($guide['enabled'] ?? false) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }} px-2 py-0.5 text-[11px] font-bold">
                                    {{ ($guide['enabled'] ?? false) ? 'видно' : 'заготовка' }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs leading-5 text-slate-600">{{ $guide['where'] ?? $slot->description }}</p>
                            <p class="mt-1 text-xs font-semibold text-slate-500">{{ $guide['target'] ?? 'Можно выбрать товар, магазин или свою ссылку.' }}</p>
                            @if($guide['url'] ?? null)
                                <a href="{{ $guide['url'] }}" target="_blank" rel="noopener" class="mt-2 inline-flex text-xs font-bold text-indigo-600 hover:text-indigo-800">Открыть место показа</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-base font-bold text-slate-950">Показ</h2>

                <label class="mt-4 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           @checked(old('is_active', $campaign->is_active))
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm font-semibold text-slate-700">Кампания активна</span>
                </label>

                <label class="mt-4 block">
                    <span class="text-sm font-semibold text-slate-700">Приоритет</span>
                    <input type="number"
                           name="sort_order"
                           value="{{ old('sort_order', $campaign->sort_order ?? 100) }}"
                           min="0"
                           max="999999"
                           class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <span class="mt-1 block text-xs text-slate-400">Меньшее число показывается выше. Например 10 выше, чем 100.</span>
                </label>

                <label class="mt-4 block">
                    <span class="text-sm font-semibold text-slate-700">Старт</span>
                    <input type="datetime-local"
                           name="starts_at"
                           value="{{ old('starts_at', $campaign->starts_at?->format('Y-m-d\\TH:i')) }}"
                           class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                </label>

                <label class="mt-4 block">
                    <span class="text-sm font-semibold text-slate-700">Финиш</span>
                    <input type="datetime-local"
                           name="ends_at"
                           value="{{ old('ends_at', $campaign->ends_at?->format('Y-m-d\\TH:i')) }}"
                           class="mt-1 h-11 w-full rounded-xl border border-slate-200 px-3 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                </label>
            </section>

            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 shadow-sm">
                <div class="flex items-start gap-2">
                    <i class="ri-information-line mt-0.5 text-lg"></i>
                    <p>
                        Это ручное продвижение, не платёжный тариф. Для публичного блока используется прозрачная метка: “Продвигается”, “Реклама” или ваш текст.
                    </p>
                </div>
            </section>

            <div class="grid gap-2">
                <button class="inline-flex h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white transition hover:bg-indigo-700">
                    {{ $campaign->exists ? 'Сохранить изменения' : 'Создать кампанию' }}
                </button>
                <a href="{{ route('admin.ads.index') }}" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-5 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    Отмена
                </a>
            </div>
        </aside>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('ad-target-type');
        const panels = Array.from(document.querySelectorAll('[data-target-panel]'));

        const syncPanels = () => {
            const value = select.value;
            panels.forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.targetPanel !== value);
            });
        };

        select.addEventListener('change', syncPanels);
        syncPanels();

        const setupEntitySearch = ({ inputId, hiddenId, resultsId, clearId, endpoint, emptyText }) => {
            const input = document.getElementById(inputId);
            const hidden = document.getElementById(hiddenId);
            const results = document.getElementById(resultsId);
            const clear = document.getElementById(clearId);

            if (!input || !hidden || !results || !clear) {
                return;
            }

            let timer = null;
            let selectedLabel = input.value;

            const syncClear = () => {
                clear.classList.toggle('hidden', !input.value && !hidden.value);
                clear.classList.toggle('inline-flex', !!input.value || !!hidden.value);
            };

            const hideResults = () => {
                results.classList.add('hidden');
                results.innerHTML = '';
            };

            const escapeHtml = (value) => String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const renderResults = (items) => {
                if (!items.length) {
                    results.innerHTML = `<div class="px-3 py-3 text-sm text-slate-500">${emptyText}</div>`;
                    results.classList.remove('hidden');
                    return;
                }

                results.innerHTML = items.map((item) => `
                    <button type="button"
                            class="flex w-full items-center gap-3 px-3 py-2 text-left transition hover:bg-indigo-50"
                            data-id="${escapeHtml(item.id)}"
                            data-label="${escapeHtml(item.title)}">
                        <img src="${escapeHtml(item.image)}" alt="" class="h-10 w-10 rounded-lg object-cover bg-slate-100" onerror="this.classList.add('hidden')">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-bold text-slate-900">${escapeHtml(item.title)}</span>
                            <span class="block truncate text-xs text-slate-500">${escapeHtml(item.subtitle)}</span>
                        </span>
                    </button>
                `).join('');
                results.classList.remove('hidden');
            };

            const runSearch = async () => {
                const query = input.value.trim();

                if (query.length < 2) {
                    hideResults();
                    return;
                }

                results.innerHTML = '<div class="px-3 py-3 text-sm text-slate-500">Ищем...</div>';
                results.classList.remove('hidden');

                const response = await fetch(`${endpoint}?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await response.json();
                renderResults(data.results || []);
            };

            input.addEventListener('input', () => {
                if (input.value !== selectedLabel) {
                    hidden.value = '';
                }

                syncClear();
                clearTimeout(timer);
                timer = setTimeout(runSearch, 250);
            });

            input.addEventListener('focus', () => {
                if (input.value.trim().length >= 2 && !hidden.value) {
                    runSearch();
                }
            });

            results.addEventListener('click', (event) => {
                const button = event.target.closest('button[data-id]');

                if (!button) {
                    return;
                }

                hidden.value = button.dataset.id;
                selectedLabel = button.dataset.label;
                input.value = selectedLabel;
                syncClear();
                hideResults();
            });

            clear.addEventListener('click', () => {
                hidden.value = '';
                selectedLabel = '';
                input.value = '';
                input.focus();
                hideResults();
                syncClear();
            });

            document.addEventListener('click', (event) => {
                if (!results.contains(event.target) && event.target !== input) {
                    hideResults();
                }
            });

            syncClear();
        };

        setupEntitySearch({
            inputId: 'ad-product-search',
            hiddenId: 'ad-product-id',
            resultsId: 'ad-product-results',
            clearId: 'ad-product-clear',
            endpoint: @json(route('admin.ads.search.products')),
            emptyText: 'Товары не найдены.',
        });

        setupEntitySearch({
            inputId: 'ad-shop-search',
            hiddenId: 'ad-shop-id',
            resultsId: 'ad-shop-results',
            clearId: 'ad-shop-clear',
            endpoint: @json(route('admin.ads.search.shops')),
            emptyText: 'Магазины не найдены.',
        });
    });
</script>
@endsection
