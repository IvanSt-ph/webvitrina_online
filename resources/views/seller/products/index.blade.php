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

    $discount = $discount ?? false;
@endphp

<x-seller-layout title="Мои товары" :hideHeader="true">
    <style>[x-cloak]{display:none!important}</style>

    <div
        x-data="{ viewMode: localStorage.getItem('seller_view') || 'grid', showConfirm: false, productId: null, productTitle: '' }"
        class="min-h-screen w-full bg-white px-3 py-4 pb-28 text-neutral-900 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8"
    >
        <template x-teleport="body">
            <div
                x-show="showConfirm"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-950/55 p-3 backdrop-blur-sm"
                @keydown.escape.window="showConfirm = false"
            >
                <div class="w-full max-w-md rounded-2xl border border-neutral-200 bg-white p-5 shadow-2xl">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                            <i class="ri-delete-bin-6-line text-xl"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-neutral-950">Удалить товар?</h2>
                            <p class="mt-1 text-sm text-neutral-500">
                                <span class="font-medium text-neutral-700" x-text="productTitle"></span>
                                исчезнет из витрины и кабинета продавца.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="showConfirm = false"
                            class="rounded-xl border border-neutral-200 px-4 py-2 text-sm font-medium text-neutral-700 transition hover:bg-neutral-50"
                        >
                            Отмена
                        </button>
                        <form :action="`/seller/products/${productId}`" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">
                                <i class="ri-delete-bin-line"></i>
                                Удалить
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </template>

        <div class="w-full space-y-5 sm:space-y-6">
            <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                        <i class="ri-box-3-line"></i>
                        Ассортимент
                    </div>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">Мои товары</h1>
                    <p class="mt-1 max-w-2xl text-sm text-neutral-500">
                        Управляйте публикацией, остатками, ценами и быстрым поиском по своему каталогу.
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:items-end">
                    @if($sellerPlanProfile['can_create'])
                        <x-action-button as="a" :href="route('seller.products.create')">
                            <i class="ri-add-line text-lg"></i>
                            Добавить товар
                        </x-action-button>
                    @else
                        <button type="button"
                                disabled
                                title="Лимит товаров исчерпан"
                                class="inline-flex h-11 cursor-not-allowed items-center justify-center gap-2 rounded-xl bg-neutral-200 px-5 text-sm font-semibold text-neutral-500">
                            <i class="ri-lock-line text-lg"></i>
                            Лимит исчерпан
                        </button>
                    @endif
                    <span class="text-xs font-semibold text-neutral-400">
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
                        <div class="font-bold">Вы близко к лимиту уровня магазина {{ $sellerPlanProfile['label'] }}</div>
                        <p class="mt-1 text-amber-800">Осталось {{ $sellerPlanProfile['remaining'] }} мест для товаров. Лучше заранее оставить заявку на повышение.</p>
                    </div>
                    <a href="{{ route('seller.plans.index') }}" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 text-sm font-bold text-white hover:bg-brand-600">
                        <i class="ri-vip-crown-line"></i>
                        Уровень магазина
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
                    <a href="{{ route('seller.products.index', ['status' => 'blocked']) }}" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl bg-rose-600 px-4 text-sm font-bold text-white hover:bg-rose-700">
                        <i class="ri-lock-2-line"></i>
                        Показать
                    </a>
                </div>
            @endif

            <section class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                    <div class="flex items-center justify-between text-xs text-neutral-500 sm:text-sm">
                        <span>Всего товаров</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><i class="ri-stack-line"></i></span>
                    </div>
                    <div class="mt-2 text-xl font-bold text-neutral-950 sm:text-2xl">{{ number_format($productTotals->total ?? 0, 0, ',', ' ') }}</div>
                    <div class="mt-1 text-xs {{ $newProductsCount > 0 ? 'text-emerald-600' : 'text-neutral-400' }}">
                        {{ $newProductsCount > 0 ? '+' . $newProductsCount . ' за период' : 'Без новых за период' }}
                    </div>
                </div>

                <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                    <div class="flex items-center justify-between text-xs text-neutral-500 sm:text-sm">
                        <span>Просмотры за период</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><i class="ri-eye-line"></i></span>
                    </div>
                    <div class="mt-2 text-xl font-bold text-neutral-950 sm:text-2xl">{{ number_format($summary->views ?? 0, 0, ',', ' ') }}</div>
                    <div class="mt-1 text-xs {{ str_starts_with($viewsDelta, '+') ? 'text-emerald-700' : 'text-rose-700' }}">{{ $viewsDelta }} к прошлому периоду</div>
                </div>

                <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                    <div class="flex items-center justify-between text-xs text-neutral-500 sm:text-sm">
                        <span>Статус продавца</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600"><i class="ri-vip-crown-line"></i></span>
                    </div>
                    <div class="mt-2 truncate text-xl font-bold text-neutral-950 sm:text-2xl">{{ $sellerPlanProfile['label'] }}</div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-neutral-100">
                        <div class="h-full rounded-full bg-brand-500" style="width: {{ $sellerPlanProfile['percent'] }}%"></div>
                    </div>
                    <div class="mt-1 text-xs opacity-80">{{ $sellerPlanProfile['used'] }} из {{ $sellerPlanProfile['limit_label'] }} товаров</div>
                </div>

                <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                    <div class="flex items-center justify-between text-xs text-neutral-500 sm:text-sm">
                        <span>Нет в наличии</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ ($productTotals->out_of_stock ?? 0) > 0 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' }}"><i class="ri-alert-line"></i></span>
                    </div>
                    <div class="mt-2 text-xl font-bold text-neutral-950 sm:text-2xl">
                        {{ number_format($productTotals->out_of_stock ?? 0, 0, ',', ' ') }}
                    </div>
                    <div class="mt-1 text-xs {{ ($productTotals->out_of_stock ?? 0) > 0 ? 'text-rose-600' : 'text-emerald-700' }}">
                        {{ ($productTotals->out_of_stock ?? 0) > 0 ? 'Стоит пополнить остатки' : 'Остатки выглядят хорошо' }}
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
                <div class="border-b border-neutral-100 p-3 sm:p-4">
                    <form method="GET" action="{{ route('seller.products.index') }}" class="grid gap-3 xl:grid-cols-[1fr_180px_180px_170px_220px_auto]">
                        <label class="relative block">
                            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400"></i>
                            <input
                                type="search"
                                name="q"
                                value="{{ $search }}"
                                placeholder="Поиск по названию или категории"
                                class="wv-field h-11 pl-10"
                            >
                        </label>

                        <select name="status" class="wv-field h-11 pr-9">
                            <option value="">Все статусы</option>
                            @foreach($statusLabels as $key => $label)
                                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <select name="stock" class="wv-field h-11 pr-9">
                            <option value="">Все остатки</option>
                            @foreach($stockLabels as $key => $label)
                                <option value="{{ $key }}" @selected(($stock ?? null) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <label class="flex h-11 items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 text-sm font-semibold text-neutral-600 transition hover:bg-neutral-50">
                            <input type="checkbox" name="discount" value="1" @checked($discount) class="h-4 w-4 rounded border-neutral-300 text-brand-600 focus:ring-brand-500">
                            <span>Со скидкой</span>
                        </label>

                        <select name="sort" class="wv-field h-11 pr-9">
                            @foreach($sortLabels as $key => $label)
                                <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="wv-btn-primary inline-flex h-11 items-center justify-center gap-2 px-5">
                            <i class="ri-filter-3-line"></i>
                            Применить
                        </button>
                    </form>
                </div>

                <div class="flex flex-col gap-3 border-b border-neutral-100 px-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 gap-2 overflow-x-auto">
                        @php
                            $filterBase = array_filter(['q' => $search, 'sort' => $sort, 'stock' => $stock ?? null, 'discount' => $discount ? 1 : null]);
                            $allCount = $productTotals->total ?? 0;
                        @endphp
                        <a href="{{ route('seller.products.index', $filterBase) }}"
                           class="inline-flex shrink-0 items-center gap-2 rounded-xl border px-3 py-2 text-sm transition {{ $status === null ? 'border-brand-200 bg-brand-50 text-brand-700' : 'border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">
                            Все
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold">{{ $allCount }}</span>
                        </a>
                        @foreach($statusLabels as $key => $label)
                            <a href="{{ route('seller.products.index', array_merge($filterBase, ['status' => $key])) }}"
                               class="inline-flex shrink-0 items-center gap-2 rounded-xl border px-3 py-2 text-sm transition {{ $status === $key ? 'border-brand-200 bg-brand-50 text-brand-700' : 'border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">
                                {{ $label }}
                                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold">{{ $statusCounts[$key] ?? 0 }}</span>
                            </a>
                        @endforeach
                        @foreach($stockLabels as $key => $label)
                            <a href="{{ route('seller.products.index', array_merge(array_filter(['q' => $search, 'sort' => $sort, 'status' => $status, 'discount' => $discount ? 1 : null]), ['stock' => $key])) }}"
                               class="inline-flex shrink-0 items-center gap-2 rounded-xl border px-3 py-2 text-sm transition {{ ($stock ?? null) === $key ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                        <a href="{{ route('seller.products.index', array_merge(array_filter(['q' => $search, 'sort' => $sort, 'status' => $status, 'stock' => $stock ?? null]), ['discount' => 1])) }}"
                           class="inline-flex shrink-0 items-center gap-2 rounded-xl border px-3 py-2 text-sm transition {{ $discount ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">
                            Со скидкой
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold">{{ $discountProductsCount ?? 0 }}</span>
                        </a>
                    </div>

                    <div class="inline-flex w-full overflow-hidden rounded-xl border border-neutral-200 bg-white sm:w-auto" aria-label="Вид списка товаров">
                        <button
                            type="button"
                            title="Показать товары адаптивной сеткой"
                            @click="viewMode = 'grid'; localStorage.setItem('seller_view', 'grid')"
                            :class="viewMode === 'grid' ? 'bg-brand-50 text-brand-700' : 'text-neutral-500 hover:bg-neutral-50'"
                            class="flex h-10 flex-1 items-center justify-center gap-2 px-3 text-sm font-semibold transition sm:flex-none"
                        >
                            <i class="ri-layout-grid-fill text-lg"></i>
                            <span>Сетка</span>
                        </button>
                        <button
                            type="button"
                            title="Список"
                            @click="viewMode = 'list'; localStorage.setItem('seller_view', 'list')"
                            :class="viewMode === 'list' ? 'bg-brand-50 text-brand-700' : 'text-neutral-500 hover:bg-neutral-50'"
                            class="flex h-10 flex-1 items-center justify-center gap-2 border-l border-neutral-200 px-3 text-sm font-semibold transition sm:flex-none"
                        >
                            <i class="ri-list-unordered text-lg"></i>
                            <span>Список</span>
                        </button>
                    </div>
                </div>

                @if($products->count())
                    <div x-show="viewMode === 'grid'" x-cloak class="grid grid-cols-2 gap-2 bg-neutral-50/60 p-2 sm:gap-3 sm:p-4 lg:grid-cols-[repeat(auto-fill,minmax(280px,1fr))]">
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
                            <article class="group min-w-0 overflow-hidden rounded-2xl border border-neutral-200 bg-white transition hover:border-brand-200 hover:shadow-md">
                                <div class="relative aspect-square bg-neutral-50 sm:aspect-[4/3]">
                                    <img data-image-candidates="{{ json_encode($p->image_thumb_candidates) }}" data-image-fallback="{{ asset(\App\Models\Product::IMAGE_FALLBACK_ASSET) }}" src="{{ $p->image_thumb_url }}" alt="{{ $p->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]">
                                    <div class="absolute left-1.5 right-1.5 top-1.5 flex flex-wrap gap-1 sm:left-2 sm:right-2 sm:top-2 sm:gap-2">
                                        <span class="rounded-full border {{ $statusClass }} px-2 py-0.5 text-[10px] font-medium sm:py-1 sm:text-xs">{{ $statusLabel }}</span>
                                        @if($p->stock <= 0)
                                            <span class="rounded-full border border-rose-200 bg-white px-2 py-0.5 text-[10px] font-medium text-rose-700 sm:py-1 sm:text-xs">Нет в наличии</span>
                                        @endif
                                        @if($p->discount_percent)
                                            <span class="rounded-full border border-red-200 bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white sm:py-1 sm:text-xs">-{{ $p->discount_percent }}%</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="space-y-2 p-2.5 sm:space-y-3 sm:p-3">
                                    <div>
                                        <h3 class="line-clamp-2 min-h-[2.25rem] text-xs font-semibold leading-[1.125rem] text-neutral-950 sm:min-h-[2.5rem] sm:text-sm sm:leading-5">{{ $p->title }}</h3>
                                        <p class="mt-1 truncate text-[10px] text-neutral-500 sm:text-xs">{{ $p->category->name ?? 'Без категории' }} · {{ $p->city->name ?? 'Город не указан' }}</p>
                                        @if($qualityHints->isNotEmpty())
                                            <div class="mt-2 hidden flex-wrap gap-1 sm:flex">
                                                @foreach($qualityHints as $hint)
                                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">{{ $hint }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if($p->status === 'blocked')
                                            <div class="mt-2 hidden rounded-xl border border-rose-100 bg-rose-50 p-2 text-xs leading-5 text-rose-800 sm:block">
                                                <span class="font-bold">Заблокирован админом.</span>
                                                Исправьте карточку и напишите в поддержку/админу: самостоятельно опубликовать нельзя.
                                            </div>
                                        @endif
                                    </div>

                                    <div class="flex items-end justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-bold text-neutral-950 sm:text-base">{{ number_format($p->price, 0, ',', ' ') }} ₽</div>
                                            @if($p->old_price && $p->old_price > $p->price)
                                                <div class="truncate text-[10px] text-neutral-400 line-through sm:text-xs">{{ number_format($p->old_price, 0, ',', ' ') }} ₽</div>
                                            @endif
                                            <div class="text-[10px] text-neutral-500 sm:text-xs">Остаток: {{ $p->stock }}</div>
                                        </div>
                                        <div class="shrink-0 text-right text-[10px] text-neutral-500 sm:text-xs">
                                            <div class="font-semibold text-neutral-700">{{ number_format($p->views_sum ?? 0, 0, ',', ' ') }}</div>
                                            <div>просм.</div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-1.5 pt-1 sm:gap-2">
                                        <a href="{{ route('seller.products.edit', $p) }}" class="inline-flex h-9 min-w-0 items-center justify-center gap-1 rounded-xl bg-brand-500 px-2 text-xs font-semibold text-white transition hover:bg-brand-600 sm:h-10 sm:gap-2 sm:px-3 sm:text-sm">
                                            <i class="ri-edit-line"></i>
                                            <span class="truncate sm:hidden">Изменить</span>
                                            <span class="hidden sm:inline">Редактировать</span>
                                        </a>
                                        <button
                                            type="button"
                                            title="Удалить"
                                            @click="productId = {{ $p->id }}; productTitle = @js($p->title); showConfirm = true"
                                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 text-rose-600 transition hover:bg-rose-50 sm:h-10 sm:w-10"
                                        >
                                            <i class="ri-delete-bin-6-line"></i>
                                        </button>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div x-show="viewMode === 'list'" x-cloak class="grid gap-2 bg-neutral-50/60 p-2 sm:p-3">
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
                            <article class="flex min-w-0 items-center gap-2 rounded-xl border border-neutral-200 bg-white p-2 transition hover:border-brand-200 sm:gap-3">
                                <img data-image-candidates="{{ json_encode($p->image_thumb_candidates) }}" data-image-fallback="{{ asset(\App\Models\Product::IMAGE_FALLBACK_ASSET) }}" src="{{ $p->image_thumb_url }}" alt="{{ $p->title }}" class="h-11 w-11 shrink-0 rounded-lg border border-neutral-200 object-cover sm:h-12 sm:w-12">

                                <div class="min-w-0 flex-1">
                                    <div class="flex min-w-0 items-center gap-1.5">
                                        <h3 class="min-w-0 flex-1 truncate text-sm font-semibold text-neutral-950">{{ $p->title }}</h3>
                                        @if($p->discount_percent)
                                            <span class="shrink-0 rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-bold text-rose-600">-{{ $p->discount_percent }}%</span>
                                        @endif
                                    </div>

                                    <div class="mt-1 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-[10px] leading-4 text-neutral-500 sm:text-xs">
                                        <span class="rounded-full border {{ $statusClass }} px-1.5 py-0.5 font-medium">{{ $statusLabel }}</span>
                                        <span class="whitespace-nowrap font-bold text-neutral-900">{{ number_format($p->price, 0, ',', ' ') }} ₽</span>
                                        @if($p->old_price && $p->old_price > $p->price)
                                            <span class="whitespace-nowrap text-neutral-400 line-through">{{ number_format($p->old_price, 0, ',', ' ') }} ₽</span>
                                        @endif
                                        <span class="whitespace-nowrap {{ $p->stock > 0 ? 'text-neutral-500' : 'font-semibold text-rose-600' }}">Остаток: {{ $p->stock }}</span>
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-1.5">
                                    <a href="{{ route('seller.products.edit', $p) }}" class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-700 transition hover:bg-brand-100" title="Редактировать">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                    <button
                                        type="button"
                                        title="Удалить"
                                        @click="productId = {{ $p->id }}; productTitle = @js($p->title); showConfirm = true"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                    >
                                        <i class="ri-delete-bin-6-line"></i>
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if($products->hasPages())
                        <div class="border-t border-neutral-100 p-4">
                            {{ $products->links() }}
                        </div>
                    @endif
                @else
                    <div class="px-6 py-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-2xl text-brand-500">
                            <i class="ri-store-2-line"></i>
                        </div>
                        <h2 class="mt-4 text-lg font-semibold text-neutral-900">Товары не найдены</h2>
                        <p class="mt-1 text-sm text-neutral-500">По текущим условиям ничего не подходит. Сбросьте фильтры или создайте карточку, если товара ещё нет в каталоге.</p>
                        <a href="{{ route('seller.products.index') }}" class="mt-5 mr-2 inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-50">
                            <i class="ri-close-circle-line"></i>
                            Сбросить фильтры
                        </a>
                        <a href="{{ route('seller.products.create') }}" class="mt-5 inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white transition hover:bg-brand-600">
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
