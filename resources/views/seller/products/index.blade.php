@php
    $viewsDelta = (function ($now, $previous) {
        $now = (float) $now;
        $previous = (float) $previous;

        if ($previous <= 0) {
            return $now > 0 ? '+100%' : '0%';
        }

        $delta = (($now - $previous) / $previous) * 100;

        return ($delta >= 0 ? '+' : '') . round($delta, 1) . '%';
    })($summary->views ?? 0, $prev->views ?? 0);

    $statusLabels = [
        'active' => 'Опубликован',
        'draft' => 'Черновик',
        'blocked' => 'Заблокирован',
    ];

    $statusClasses = [
        'active' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'draft' => 'border-amber-200 bg-amber-50 text-amber-700',
        'blocked' => 'border-rose-200 bg-rose-50 text-rose-700',
    ];

    $sortLabels = [
        'new' => 'Сначала новые',
        'cheap' => 'Сначала дешевле',
        'expensive' => 'Сначала дороже',
        'popular' => 'По просмотрам',
    ];

    $stockLabels = [
        'out' => 'Нет в наличии',
        'low' => 'Мало остатков',
    ];
@endphp

<x-seller-layout title="Мои товары" :hideHeader="true">
    <style>[x-cloak]{display:none!important}</style>

    <div
        x-data="{ viewMode: localStorage.getItem('seller_view') || 'grid', showConfirm: false, productId: null, productTitle: '' }"
        class="min-h-screen bg-white px-3 py-4 pb-[5.5rem] text-slate-900 sm:px-5 sm:py-6 lg:px-6"
    >
        <template x-teleport="body">
            <div
                x-show="showConfirm"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/55 p-3"
                @keydown.escape.window="showConfirm = false"
            >
                <div class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-5 shadow-2xl">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                            <i class="ri-delete-bin-6-line text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-slate-950">Удалить товар?</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                <span class="font-medium text-slate-700" x-text="productTitle"></span>
                                исчезнет из витрины и кабинета продавца.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="showConfirm = false"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Отмена
                        </button>
                        <form :action="`/seller/products/${productId}`" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">
                                <i class="ri-delete-bin-line"></i>
                                Удалить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        <div class="w-full max-w-none space-y-5">
            <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                        <i class="ri-box-3-line"></i>
                        Ассортимент
                    </div>
                    <h1 class="mt-3 text-2xl font-bold text-slate-950 sm:text-3xl">Мои товары</h1>
                    <p class="mt-1 max-w-2xl text-sm text-slate-500">
                        Управляйте публикацией, остатками, ценами и быстрым поиском по своему каталогу.
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:items-end">
                    @if($sellerPlanProfile['can_create'])
                        <a href="{{ route('seller.products.create') }}"
                           class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <i class="ri-add-line text-lg"></i>
                            Добавить товар
                        </a>
                    @else
                        <button type="button"
                                disabled
                                title="Лимит товаров исчерпан"
                                class="inline-flex h-11 cursor-not-allowed items-center justify-center gap-2 rounded-lg bg-slate-200 px-5 text-sm font-semibold text-slate-500">
                            <i class="ri-lock-line text-lg"></i>
                            Лимит исчерпан
                        </button>
                    @endif
                    <span class="text-xs font-semibold text-slate-400">
                        {{ $sellerPlanProfile['label'] }}: {{ $sellerPlanProfile['used'] }} / {{ $sellerPlanProfile['limit_label'] }} товаров
                    </span>
                </div>
            </header>

            @if($errors->has('product_limit'))
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
                    <i class="ri-error-warning-line mr-1"></i>
                    {{ $errors->first('product_limit') }}
                </div>
            @endif

            @if($sellerPlanProfile['near_limit'] && $sellerPlanProfile['can_create'])
                <div class="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="font-bold">Вы близко к лимиту тарифа {{ $sellerPlanProfile['label'] }}</div>
                        <p class="mt-1 text-amber-800">Осталось {{ $sellerPlanProfile['remaining'] }} мест для товаров. Лучше заранее оставить заявку на повышение.</p>
                    </div>
                    <a href="{{ route('seller.plans.index') }}" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-bold text-white hover:bg-indigo-700">
                        <i class="ri-vip-crown-line"></i>
                        Тарифы
                    </a>
                </div>
            @endif

            @if(($statusCounts['blocked'] ?? 0) > 0)
                <div class="flex flex-col gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="font-bold">Есть товары, заблокированные администратором: {{ $statusCounts['blocked'] }}</div>
                        <p class="mt-1 text-rose-800">
                            Они сняты с витрины после модерации или жалобы. Можно исправить карточку, но вернуть товар в продажу сможет только администратор после проверки.
                        </p>
                    </div>
                    <a href="{{ route('seller.products.index', ['status' => 'blocked']) }}" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 text-sm font-bold text-white hover:bg-rose-700">
                        <i class="ri-lock-2-line"></i>
                        Показать
                    </a>
                </div>
            @endif

            <section class="hidden gap-3 sm:grid sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500">
                        <span>Всего товаров</span>
                        <i class="ri-stack-line text-indigo-500"></i>
                    </div>
                    <div class="mt-2 text-2xl font-bold">{{ number_format($productTotals->total ?? 0, 0, ',', ' ') }}</div>
                    <div class="mt-1 text-xs {{ $newProductsCount > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                        {{ $newProductsCount > 0 ? '+' . $newProductsCount . ' за период' : 'Без новых за период' }}
                    </div>
                </div>

                <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                    <div class="flex items-center justify-between text-sm text-indigo-700">
                        <span>Просмотры за период</span>
                        <i class="ri-eye-line"></i>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-indigo-900">{{ number_format($summary->views ?? 0, 0, ',', ' ') }}</div>
                    <div class="mt-1 text-xs {{ str_starts_with($viewsDelta, '+') ? 'text-emerald-700' : 'text-rose-700' }}">{{ $viewsDelta }} к прошлому периоду</div>
                </div>

                <div class="rounded-xl border {{ $sellerPlanProfile['class'] }} p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500">
                        <span>Статус продавца</span>
                        <i class="ri-vip-crown-line"></i>
                    </div>
                    <div class="mt-2 text-2xl font-bold">{{ $sellerPlanProfile['label'] }}</div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/70">
                        <div class="h-full rounded-full bg-indigo-500" style="width: {{ $sellerPlanProfile['percent'] }}%"></div>
                    </div>
                    <div class="mt-1 text-xs opacity-80">{{ $sellerPlanProfile['used'] }} из {{ $sellerPlanProfile['limit_label'] }} товаров</div>
                </div>

                <div class="rounded-xl border {{ ($productTotals->out_of_stock ?? 0) > 0 ? 'border-rose-200 bg-rose-50' : 'border-emerald-200 bg-emerald-50' }} p-4">
                    <div class="flex items-center justify-between text-sm {{ ($productTotals->out_of_stock ?? 0) > 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                        <span>Нет в наличии</span>
                        <i class="ri-alert-line"></i>
                    </div>
                    <div class="mt-2 text-2xl font-bold {{ ($productTotals->out_of_stock ?? 0) > 0 ? 'text-rose-800' : 'text-emerald-800' }}">
                        {{ number_format($productTotals->out_of_stock ?? 0, 0, ',', ' ') }}
                    </div>
                    <div class="mt-1 text-xs {{ ($productTotals->out_of_stock ?? 0) > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                        {{ ($productTotals->out_of_stock ?? 0) > 0 ? 'Стоит пополнить остатки' : 'Остатки выглядят хорошо' }}
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 p-3 sm:p-4">
                    <form method="GET" action="{{ route('seller.products.index') }}" class="grid gap-3 xl:grid-cols-[1fr_180px_180px_220px_auto]">
                        <label class="relative block">
                            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="search"
                                name="q"
                                value="{{ $search }}"
                                placeholder="Поиск по названию или категории"
                                class="h-11 w-full rounded-lg border border-slate-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                            >
                        </label>

                        <select name="status" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 pr-9 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                            <option value="">Все статусы</option>
                            @foreach($statusLabels as $key => $label)
                                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <select name="stock" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 pr-9 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                            <option value="">Все остатки</option>
                            @foreach($stockLabels as $key => $label)
                                <option value="{{ $key }}" @selected(($stock ?? null) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <select name="sort" class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 pr-9 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                            @foreach($sortLabels as $key => $label)
                                <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <i class="ri-filter-3-line"></i>
                            Применить
                        </button>
                    </form>
                </div>

                <div class="flex flex-col gap-3 border-b border-slate-100 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 gap-2 overflow-x-auto">
                        @php
                            $filterBase = array_filter(['q' => $search, 'sort' => $sort, 'stock' => $stock ?? null]);
                            $allCount = $productTotals->total ?? 0;
                        @endphp
                        <a href="{{ route('seller.products.index', $filterBase) }}"
                           class="inline-flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-sm transition {{ $status === null ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                            Все
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold">{{ $allCount }}</span>
                        </a>
                        @foreach($statusLabels as $key => $label)
                            <a href="{{ route('seller.products.index', array_merge($filterBase, ['status' => $key])) }}"
                               class="inline-flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-sm transition {{ $status === $key ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                                {{ $label }}
                                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold">{{ $statusCounts[$key] ?? 0 }}</span>
                            </a>
                        @endforeach
                        @foreach($stockLabels as $key => $label)
                            <a href="{{ route('seller.products.index', array_merge(array_filter(['q' => $search, 'sort' => $sort, 'status' => $status]), ['stock' => $key])) }}"
                               class="inline-flex shrink-0 items-center gap-2 rounded-lg border px-3 py-2 text-sm transition {{ ($stock ?? null) === $key ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>

                    <div class="inline-flex w-fit overflow-hidden rounded-lg border border-slate-200 bg-white">
                        <button
                            type="button"
                            title="Плитка"
                            @click="viewMode = 'grid'; localStorage.setItem('seller_view', 'grid')"
                            :class="viewMode === 'grid' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-500 hover:bg-slate-50'"
                            class="flex h-10 w-11 items-center justify-center transition"
                        >
                            <i class="ri-layout-grid-fill text-lg"></i>
                        </button>
                        <button
                            type="button"
                            title="Список"
                            @click="viewMode = 'list'; localStorage.setItem('seller_view', 'list')"
                            :class="viewMode === 'list' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-500 hover:bg-slate-50'"
                            class="flex h-10 w-11 items-center justify-center border-l border-slate-200 transition"
                        >
                            <i class="ri-list-unordered text-lg"></i>
                        </button>
                    </div>
                </div>

                @if($products->count())
                    <div x-show="viewMode === 'grid'" x-cloak class="grid gap-4 p-3 sm:grid-cols-2 sm:p-4 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                        @foreach($products as $p)
                            @php
                                $statusLabel = $statusLabels[$p->status] ?? 'Неизвестный статус';
                                $statusClass = $statusClasses[$p->status] ?? 'border-rose-200 bg-rose-50 text-rose-700';
                                $qualityHints = collect();
                                if (!$p->image || in_array($p->image, ['default/no-image.png', 'no-image.png'], true)) $qualityHints->push('Нет фото');
                                if (mb_strlen(strip_tags((string) $p->description)) < 60) $qualityHints->push('Короткое описание');
                                if (!$p->category_id) $qualityHints->push('Нет категории');
                                if (($p->attribute_values_count ?? 0) === 0) $qualityHints->push('Нет характеристик');
                                if ($p->stock <= 0) $qualityHints->push('Нет остатков');
                            @endphp
                            <article class="group overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:border-indigo-200 hover:shadow-sm">
                                <div class="relative aspect-[4/3] bg-slate-50">
                                    <img src="{{ $p->image_thumb_url }}" alt="{{ $p->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]">
                                    <div class="absolute left-2 top-2 flex flex-wrap gap-2">
                                        <span class="rounded-full border {{ $statusClass }} px-2 py-1 text-xs font-medium">{{ $statusLabel }}</span>
                                        @if($p->stock <= 0)
                                            <span class="rounded-full border border-rose-200 bg-white px-2 py-1 text-xs font-medium text-rose-700">Нет в наличии</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="space-y-3 p-3">
                                    <div>
                                        <h3 class="line-clamp-2 min-h-[2.5rem] text-sm font-semibold leading-5 text-slate-950">{{ $p->title }}</h3>
                                        <p class="mt-1 truncate text-xs text-slate-500">{{ $p->category->name ?? 'Без категории' }} · {{ $p->city->name ?? 'Город не указан' }}</p>
                                        @if($qualityHints->isNotEmpty())
                                            <div class="mt-2 flex flex-wrap gap-1">
                                                @foreach($qualityHints as $hint)
                                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">{{ $hint }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if($p->status === 'blocked')
                                            <div class="mt-2 rounded-xl border border-rose-100 bg-rose-50 p-2 text-xs leading-5 text-rose-800">
                                                <span class="font-bold">Заблокирован админом.</span>
                                                Исправьте карточку и напишите в поддержку/админу: самостоятельно опубликовать нельзя.
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex items-end justify-between gap-3">
                                        <div>
                                            <div class="text-base font-bold text-slate-950">{{ number_format($p->price, 0, ',', ' ') }} ₽</div>
                                            <div class="text-xs text-slate-500">Остаток: {{ $p->stock }}</div>
                                        </div>
                                        <div class="text-right text-xs text-slate-500">
                                            <div class="font-semibold text-slate-700">{{ number_format($p->views_sum ?? 0, 0, ',', ' ') }}</div>
                                            <div>просм.</div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-[1fr_auto] gap-2 pt-1">
                                        <a href="{{ route('seller.products.edit', $p) }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                            <i class="ri-edit-line"></i>
                                            Редактировать
                                        </a>
                                        <button
                                            type="button"
                                            title="Удалить"
                                            @click="productId = {{ $p->id }}; productTitle = @js($p->title); showConfirm = true"
                                            class="flex h-9 w-10 items-center justify-center rounded-lg border border-rose-200 text-rose-600 transition hover:bg-rose-50"
                                        >
                                            <i class="ri-delete-bin-6-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div x-show="viewMode === 'list'" x-cloak class="divide-y divide-slate-100">
                        @foreach($products as $p)
                            @php
                                $statusLabel = $statusLabels[$p->status] ?? 'Неизвестный статус';
                                $statusClass = $statusClasses[$p->status] ?? 'border-rose-200 bg-rose-50 text-rose-700';
                                $qualityHints = collect();
                                if (!$p->image || in_array($p->image, ['default/no-image.png', 'no-image.png'], true)) $qualityHints->push('Нет фото');
                                if (mb_strlen(strip_tags((string) $p->description)) < 60) $qualityHints->push('Короткое описание');
                                if (!$p->category_id) $qualityHints->push('Нет категории');
                                if (($p->attribute_values_count ?? 0) === 0) $qualityHints->push('Нет характеристик');
                                if ($p->stock <= 0) $qualityHints->push('Нет остатков');
                            @endphp
                            <div class="grid gap-3 px-4 py-3 transition hover:bg-slate-50 lg:grid-cols-[1fr_170px_120px_130px] lg:items-center">
                                <div class="flex min-w-0 items-center gap-3">
                                    <img src="{{ $p->image_thumb_url }}" alt="{{ $p->title }}" class="h-12 w-12 shrink-0 rounded-lg border border-slate-200 object-cover">
                                    <div class="min-w-0">
                                        <div class="flex min-w-0 flex-wrap items-center gap-2">
                                            <h3 class="truncate text-sm font-semibold text-slate-950">{{ $p->title }}</h3>
                                            <span class="rounded-full border {{ $statusClass }} px-2 py-0.5 text-xs font-medium">{{ $statusLabel }}</span>
                                        </div>
                                        <div class="mt-1 truncate text-xs text-slate-500">
                                            {{ $p->category->name ?? 'Без категории' }} · {{ $p->city->name ?? 'Город не указан' }} · {{ $p->created_at->format('d.m.Y') }}
                                        </div>
                                        @if($qualityHints->isNotEmpty())
                                            <div class="mt-2 flex flex-wrap gap-1">
                                                @foreach($qualityHints as $hint)
                                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">{{ $hint }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if($p->status === 'blocked')
                                            <div class="mt-2 rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-xs leading-5 text-rose-800">
                                                <span class="font-bold">Заблокирован администратором.</span>
                                                Можно исправить данные, но публикацию вернёт только админ.
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="text-sm text-slate-600">
                                    <span class="font-semibold text-slate-950">{{ number_format($p->price, 0, ',', ' ') }} ₽</span>
                                </div>

                                <div class="text-sm {{ $p->stock > 0 ? 'text-slate-600' : 'text-rose-600' }}">
                                    Остаток: <span class="font-semibold">{{ $p->stock }}</span>
                                </div>

                                <div class="flex items-center gap-2 lg:justify-end">
                                    <a href="{{ route('seller.products.edit', $p) }}" class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700 transition hover:bg-indigo-100" title="Редактировать">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                    <button
                                        type="button"
                                        title="Удалить"
                                        @click="productId = {{ $p->id }}; productTitle = @js($p->title); showConfirm = true"
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                    >
                                        <i class="ri-delete-bin-6-line"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($products->hasPages())
                        <div class="border-t border-slate-100 p-4">
                            {{ $products->links() }}
                        </div>
                    @endif
                @else
                    <div class="px-6 py-14 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                            <i class="ri-store-2-line"></i>
                        </div>
                        <h2 class="mt-4 text-lg font-semibold text-slate-900">Товары не найдены</h2>
                        <p class="mt-1 text-sm text-slate-500">Попробуйте изменить фильтр или добавить новый товар.</p>
                        <a href="{{ route('seller.products.create') }}" class="mt-5 inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <i class="ri-add-line"></i>
                            Добавить товар
                        </a>
                    </div>
                @endif
            </section>
        </div>
    </div>

    @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
