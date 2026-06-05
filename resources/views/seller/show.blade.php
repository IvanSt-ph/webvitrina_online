{{-- Полная страница магазина продавца --}}

@php
    /** @var \App\Models\User $user */
    /** @var \Illuminate\Pagination\LengthAwarePaginator $products */

    $shop = $shop ?? $user->shop;
    $filter = $filter ?? 'all';
    $filterCounts = $filterCounts ?? ['all' => 0, 'new' => 0, 'sale' => 0, 'hit' => 0];
    $filters = [
        'all' => ['label' => 'Все', 'icon' => 'ri-store-2-line'],
        'new' => ['label' => 'Новинки', 'icon' => 'ri-sparkling-2-line'],
        'sale' => ['label' => 'Скидки', 'icon' => 'ri-price-tag-3-line'],
        'hit' => ['label' => 'Хиты', 'icon' => 'ri-fire-line'],
    ];
    $filterTitles = [
        'all' => 'Товары магазина',
        'new' => 'Новинки магазина',
        'sale' => 'Товары со скидкой',
        'hit' => 'Хиты магазина',
    ];
    $emptyFilterText = [
        'all' => 'У этого продавца пока нет товаров.',
        'new' => 'За последние 30 дней у продавца не появлялись новые товары.',
        'sale' => 'У продавца пока нет товаров со старой ценой выше текущей.',
        'hit' => 'Пока нет товаров, которые набрали просмотры или отзывы.',
    ];
    $reputationLevels = [
        'top' => [
            'label' => 'Платиновый уровень',
            'description' => 'Высший уровень доверия и качества',
            'icon' => 'ri-vip-crown-line',
            'badge' => 'border-cyan-200 bg-cyan-50 text-cyan-800',
            'mark' => 'bg-cyan-500',
            'tooltip' => 'border-cyan-100',
        ],
        'trusted' => [
            'label' => 'Золотой уровень',
            'description' => 'Много продаж, высокий рейтинг',
            'icon' => 'ri-award-line',
            'badge' => 'border-amber-200 bg-amber-50 text-amber-800',
            'mark' => 'bg-amber-500',
            'tooltip' => 'border-amber-100',
        ],
        'verified' => [
            'label' => 'Серебряный уровень',
            'description' => 'Проверенный временем, хорошие отзывы',
            'icon' => 'ri-shield-check-line',
            'badge' => 'border-slate-200 bg-slate-50 text-slate-700',
            'mark' => 'bg-slate-500',
            'tooltip' => 'border-slate-100',
        ],
        'new' => [
            'label' => 'Бронзовый уровень',
            'description' => 'Надёжный базовый уровень',
            'icon' => 'ri-medal-line',
            'badge' => 'border-orange-200 bg-orange-50 text-orange-800',
            'mark' => 'bg-orange-500',
            'tooltip' => 'border-orange-100',
        ],
        'low_rating' => [
            'label' => 'Требует внимания',
            'description' => 'Рейтинг ниже обычного, проверьте отзывы перед заказом',
            'icon' => 'ri-error-warning-line',
            'badge' => 'border-rose-200 bg-rose-50 text-rose-800',
            'mark' => 'bg-rose-500',
            'tooltip' => 'border-rose-100',
        ],
    ];
    $reputation = $reputationLevels[$shop->seller_reputation] ?? $reputationLevels['new'];
    $ratingValue = (float) ($user->reviews_avg_rating ?? $shop->rating ?? 0);
    $reviewsValue = (int) ($user->reviews_count ?? 0);
    $salesValue = (int) ($shop->sales_count ?? 0);
    $reputationSignals = [
        [
            'label' => 'Email',
            'value' => $user->hasVerifiedEmail() ? 'подтверждён' : 'не подтверждён',
            'done' => $user->hasVerifiedEmail(),
        ],
        [
            'label' => 'Телефон',
            'value' => $shop->is_phone_verified ? 'подтверждён' : 'не подтверждён',
            'done' => (bool) $shop->is_phone_verified,
        ],
        [
            'label' => 'Продажи',
            'value' => $salesValue > 0 ? $salesValue . ' завершено' : 'история набирается',
            'done' => $salesValue > 0,
        ],
        [
            'label' => 'Отзывы',
            'value' => $reviewsValue > 0 ? $reviewsValue . ' · ' . number_format($ratingValue, 1, ',', ' ') . '/5' : 'пока нет',
            'done' => $reviewsValue > 0 && $ratingValue >= 4,
        ],
    ];
@endphp

@if(!$shop)

    <x-app-layout :title="$user->name">
        <div class="max-w-3xl mx-auto py-20 text-center text-gray-500">
            У продавца ещё не создан магазин
        </div>
        @include('layouts.mobile-bottom-nav')
    </x-app-layout>

@else

<x-app-layout :title="$shop->name">

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-8">

<!-- 🔹 Слайдер баннеров магазина --> 
<div class="relative w-full h-56 md:h-64 mt-0 md:mt-8 rounded-xl overflow-hidden shadow-lg">
    <div class="absolute inset-0 overflow-hidden rounded-xl">
        <img src="{{ $shop->banner_url }}" class="w-full h-full object-cover transition-transform duration-500 hover:scale-105" alt="Баннер магазина">
        <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/60"></div>
    </div>
<div class="absolute bottom-4 left-4 right-4 text-white" x-data="{ expanded: false }">
    <h1 class="text-2xl md:text-3xl font-bold drop-shadow-lg mb-2">{{ $shop->name }}</h1>
    
    <!-- Описание (короткое или полное) -->
    <div class="text-sm md:text-base drop-shadow-md bg-black/30 backdrop-blur-sm p-3 rounded-lg whitespace-normal break-words">
        <span x-show="!expanded" class="line-clamp-1">{{ $shop->description ?? 'Описание магазина отсутствует.' }}</span>
        <span x-show="expanded" x-cloak>{{ $shop->description ?? 'Описание магазина отсутствует.' }}</span>
        
        <!-- Кнопка "ещё" - показываем только если описание длинное -->
        @if(strlen($shop->description ?? '') > 50)
            <button @click="expanded = !expanded" class="mt-1 text-indigo-200 hover:text-white font-medium text-sm flex items-center gap-1">
                <span x-show="!expanded">Читать далее <i class="ri-arrow-down-s-line"></i></span>
                <span x-show="expanded" x-cloak>Свернуть <i class="ri-arrow-up-s-line"></i></span>
            </button>
        @endif
    </div>
</div>
</div>

<!-- 🔹 Карточка продавца -->
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-visible hover:shadow-xl transition-all duration-300">
    <div class="p-6">
        <!-- Верхний блок с аватаром -->
        <div class="flex flex-col md:flex-row gap-6 items-center md:items-start">
            
            <!-- Аватар с бейджем -->
            <div class="relative flex-shrink-0">
                <div class="w-24 h-24 rounded-2xl overflow-hidden bg-indigo-50 ring-4 ring-white shadow-xl">
                    <img src="{{ $user->avatar_url }}" class="w-full h-full object-cover" alt="{{ $user->name }}">
                </div>

                <!-- Бейдж верификации -->
                @if($user->hasVerifiedEmail() && $shop->is_phone_verified)
                <div class="absolute -top-2 -left-2">
                    <div class="relative">
                        <div class="absolute w-7 h-7 bg-indigo-500 rounded-xl opacity-30 animate-ping"></div>
                        <div class="relative w-7 h-7 bg-indigo-600 rounded-xl flex items-center justify-center ring-2 ring-white shadow-lg cursor-help" title="Продавец верифицирован">
                            <i class="ri-shield-check-fill text-white text-sm"></i>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Информация -->
            <div class="flex-1 w-full md:w-auto">

                <!-- Заголовок с именем и бейджами -->
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-2">
                    
                    <!-- Левая часть: имя и магазин (на мобилке по центру) -->
                    <div class="text-center md:text-left">
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center justify-center md:justify-start gap-2 flex-wrap">
                            {{ $user->name }}
                            @if($user->hasVerifiedEmail() && $shop->is_phone_verified)
                                <span class="px-2 py-1 bg-indigo-50 text-indigo-700 text-xs font-medium rounded-full border border-indigo-200">
                                    <i class="ri-shield-check-fill text-indigo-500 mr-0.5"></i>
                                    Проверенный
                                </span>
                            @elseif($user->hasVerifiedEmail() || $shop->is_phone_verified)
                                <span class="px-2 py-1 bg-indigo-50 text-indigo-700 text-xs font-medium rounded-full border border-indigo-200">
                                    <i class="ri-shield-user-line text-indigo-500 mr-0.5"></i>
                                    Верифицирован
                                </span>
                            @endif
                        </h1>
                        
                        <!-- Название магазина (по центру на мобилке) -->
                        <div class="flex items-center justify-center md:justify-start gap-2 mt-1.5">
                            <span class="text-xs text-gray-500">Магазин:</span>
                            <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md">
                                {{ $shop->name }}
                            </span>
                        </div>
                    </div>

                    <!-- ПРАВАЯ ЧАСТЬ: Репутация + Рейтинг -->
                    <div class="flex flex-row sm:flex-col gap-2 items-center sm:items-end">
                        
                        <!-- БЛОК РЕПУТАЦИИ -->
                        <div class="flex items-center">
                            <div class="relative" x-data="{ open: false }" @mouseleave="open = false">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    @mouseenter="open = true"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-left text-xs shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50/50 hover:shadow-md"
                                    :aria-expanded="open.toString()"
                                >
                                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                                        <i class="{{ $reputation['icon'] }} text-sm"></i>
                                    </span>
                                    <span class="leading-tight">
                                        <span class="block text-[10px] font-semibold uppercase text-slate-400">Уровень продавца</span>
                                        <span class="block font-bold text-slate-800">{{ $reputation['label'] }}</span>
                                    </span>
                                    <span class="h-2 w-2 rounded-full {{ $reputation['mark'] }}" aria-hidden="true"></span>
                                </button>

                                <div
                                    x-cloak
                                    x-show="open"
                                    x-transition.opacity.duration.150ms
                                    @mouseenter="open = true"
                                    @click.outside="open = false"
                                    class="absolute right-0 top-full z-[100] mt-2 w-[min(18rem,calc(100vw-2rem))] rounded-2xl border border-slate-200 bg-white p-3 text-left text-xs text-slate-600 shadow-2xl"
                                >
                                    <div class="flex items-start gap-2">
                                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                                            <i class="{{ $reputation['icon'] }}"></i>
                                        </span>
                                        <div>
                                            <div class="font-semibold text-slate-950">{{ $reputation['label'] }}</div>
                                            <div class="mt-0.5 leading-5">{{ $reputation['description'] }}</div>
                                        </div>
                                    </div>

                                    <div class="mt-3 rounded-xl bg-slate-50 p-2.5">
                                        @foreach($reputationSignals as $signal)
                                            <div class="flex items-center justify-between gap-3 py-1 first:pt-0 last:pb-0">
                                                <span class="flex items-center gap-1.5 font-medium text-slate-700">
                                                    <i class="{{ $signal['done'] ? 'ri-checkbox-circle-fill text-emerald-500' : 'ri-time-line text-slate-300' }}"></i>
                                                    {{ $signal['label'] }}
                                                </span>
                                                <span class="text-right text-slate-500">{{ $signal['value'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="absolute -top-1.5 right-4 h-3 w-3 rotate-45 border-l border-t border-slate-200 bg-white"></div>
                                </div>
                            </div>
                        </div>

                        <!-- БЛОК РЕЙТИНГА -->
                        <div class="flex items-center">
                            <div class="flex items-center gap-2 bg-yellow-50/80 px-3 py-1.5 rounded-lg border border-yellow-100">
                                <span class="text-[10px] font-medium text-gray-500 tracking-wide">РЕЙТИНГ:</span>
                                <div class="flex items-center gap-1.5">
                                    <div class="flex items-center gap-0.5">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= round($user->reviews_avg_rating ?? 0))
                                                <i class="ri-star-fill text-yellow-400 text-xs"></i>
                                            @else
                                                <i class="ri-star-line text-yellow-200 text-xs"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <span class="text-sm font-bold text-gray-800 leading-none">{{ number_format($user->reviews_avg_rating ?? 0, 1) }}</span>
                                    <span class="text-[10px] text-gray-400">({{ $user->reviews_count ?? 0 }})</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Город и дата регистрации (по центру на мобилке) -->
                <div class="flex items-center justify-center md:justify-start gap-3 mt-2 text-sm text-gray-500">
                    @if($shop->city)
                        <span class="flex items-center gap-1">
                            <i class="ri-map-pin-line text-gray-400"></i>
                            {{ $shop->city }}
                        </span>
                    @endif
                    <span class="flex items-center gap-1">
                        <i class="ri-calendar-line text-gray-400"></i>
                        На сайте с {{ $user->created_at?->format('M Y') }}
                    </span>
                </div>

                <!-- Статусы верификации, соцсети и кнопки в одну строку -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-3 mt-3 border-t border-gray-100">
                    <div class="flex items-center gap-3">
                        <!-- Email статус -->
                        <span class="flex items-center gap-1 text-xs {{ $user->hasVerifiedEmail() ? 'text-indigo-600' : 'text-gray-400' }}" title="{{ $user->hasVerifiedEmail() ? 'Email подтверждён' : 'Email не подтверждён' }}">
                            <i class="ri-mail-{{ $user->hasVerifiedEmail() ? 'check-fill' : 'line' }} text-base"></i>
                        </span>

                        <!-- Телефон статус -->
                        @if($shop->phone)
                        <span class="flex items-center gap-1 text-xs {{ $shop->is_phone_verified ? 'text-indigo-600' : 'text-gray-400' }}" title="{{ $shop->is_phone_verified ? 'Телефон подтверждён' : 'Телефон не подтверждён' }}">
                            <i class="ri-smartphone-{{ $shop->is_phone_verified ? 'fill' : 'line' }} text-base"></i>
                        </span>
                        @endif

                        <!-- Социальные сети -->
                        @if($shop->facebook || $shop->instagram || $shop->telegram || $shop->whatsapp)
                        <span class="w-px h-4 bg-gray-200 mx-1"></span>
                        <div class="flex items-center gap-4">
                            @if($shop->facebook)
                                <a href="{{ Str::startsWith($shop->facebook, 'http') ? $shop->facebook : 'https://' . $shop->facebook }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-indigo-600 transition-colors" title="Facebook">
                                    <i class="ri-facebook-fill text-lg"></i>
                                </a>
                            @endif
                            @if($shop->instagram)
                                <a href="{{ Str::startsWith($shop->instagram, 'http') ? $shop->instagram : 'https://' . $shop->instagram }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-indigo-600 transition-colors" title="Instagram">
                                    <i class="ri-instagram-fill text-lg"></i>
                                </a>
                            @endif
                            @if($shop->telegram)
                                <a href="{{ Str::startsWith($shop->telegram, 'http') ? $shop->telegram : 'https://' . $shop->telegram }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-indigo-600 transition-colors" title="Telegram">
                                    <i class="ri-telegram-fill text-lg"></i>
                                </a>
                            @endif
                            @if($shop->whatsapp)
                                <a href="{{ Str::startsWith($shop->whatsapp, 'http') ? $shop->whatsapp : 'https://' . $shop->whatsapp }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-indigo-600 transition-colors" title="WhatsApp">
                                    <i class="ri-whatsapp-fill text-lg"></i>
                                </a>
                            @endif
                        </div>
                        @endif
                    </div>

                    <!-- Кнопки -->
                    <div class="flex items-center gap-2">
                        @auth
                            @if(!auth()->user()->is($user))
                                <form method="POST" action="{{ route('shops.follow', $shop) }}">
                                    @csrf
                                    <button class="px-4 py-2 {{ $isFollowingShop ? 'bg-indigo-600 text-white border-indigo-600 hover:bg-indigo-700' : 'bg-white text-indigo-700 border-indigo-200 hover:bg-indigo-50' }} rounded-xl transition-all duration-200 text-sm font-semibold flex items-center gap-1 border shadow-sm">
                                        <i class="{{ $isFollowingShop ? 'ri-heart-3-fill' : 'ri-heart-3-line' }}"></i>
                                        <span class="hidden sm:inline">{{ $isFollowingShop ? 'Вы подписаны' : 'Подписаться' }}</span>
                                    </button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}"
                               class="px-4 py-2 bg-white text-indigo-700 rounded-xl hover:bg-indigo-50 transition-all duration-200 text-sm font-semibold flex items-center gap-1 border border-indigo-200 shadow-sm">
                                <i class="ri-heart-3-line"></i>
                                <span class="hidden sm:inline">Подписаться</span>
                            </a>
                        @endauth
                        <span class="rounded-xl bg-slate-50 px-2.5 py-2 text-xs font-semibold text-slate-500" title="Подписчики магазина">
                            {{ $shop->followers_count ?? 0 }}
                        </span>
                        @auth
                            @if(!auth()->user()->is($user))
                                <form method="POST" action="{{ route('chats.start', $shop) }}">
                                    @csrf
                                    <button class="px-4 py-2 bg-indigo-500/90 text-white rounded-xl hover:bg-indigo-600 transition-all duration-300 text-sm font-semibold flex items-center gap-1 shadow-md hover:shadow-lg hover:-translate-y-0.5 border border-indigo-400/30">
                                        <i class="ri-chat-1-line"></i>
                                        <span class="hidden sm:inline">Написать</span>
                                    </button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}"
                               class="px-4 py-2 bg-indigo-500/90 text-white rounded-xl hover:bg-indigo-600 transition-all duration-300 text-sm font-semibold flex items-center gap-1 shadow-md hover:shadow-lg hover:-translate-y-0.5 border border-indigo-400/30">
                                <i class="ri-chat-1-line"></i>
                                <span class="hidden sm:inline">Написать</span>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

        <section
            x-data="{
                loading: false,
                async load(url, push = true) {
                    if (!url || this.loading) return;
                    this.loading = true;

                    try {
                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'text/html',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) throw new Error('Bad response');

                        const html = await response.text();
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const next = doc.querySelector('[data-shop-products-panel]');

                        if (!next || !this.$refs.panel) {
                            window.location.href = url;
                            return;
                        }

                        this.$refs.panel.innerHTML = next.innerHTML;
                        window.Alpine?.initTree(this.$refs.panel);

                        if (push) {
                            window.history.pushState({}, '', url);
                        }
                    } catch (error) {
                        window.location.href = url;
                    } finally {
                        this.loading = false;
                    }
                },
                handlePanelClick(event) {
                    const link = event.target.closest('a[data-shop-filter-link], [data-shop-pagination] a');
                    if (!link) return;

                    event.preventDefault();
                    this.load(link.href);
                }
            }"
            @popstate.window="load(window.location.href, false)"
        >
            <div
                x-ref="panel"
                data-shop-products-panel
                @click="handlePanelClick($event)"
                class="space-y-6 transition-opacity duration-150"
                :class="loading ? 'opacity-60 pointer-events-none' : 'opacity-100'"
            >
                <!-- 🔹 Фильтры товаров -->
                <section class="rounded-2xl border border-slate-200/80 bg-white px-3 py-3 shadow-[0_1px_2px_rgba(15,23,42,0.03)] sm:p-3">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="hidden sm:block">
                            <div class="text-sm font-semibold text-slate-950">Товары магазина</div>
                            <p class="mt-0.5 text-xs text-slate-500">Быстро отфильтруйте каталог продавца.</p>
                        </div>
                        <div class="seller-shop-filter-track -mx-1 flex gap-2 overflow-x-auto px-1 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0">
                            @foreach($filters as $key => $meta)
                                @php $activeFilter = $filter === $key; @endphp
                                <a href="{{ route('seller.show', ['identifier' => $shop->slug, 'filter' => $key]) }}"
                                   data-shop-filter-link
                                   class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-full border px-2.5 text-xs font-semibold transition sm:h-9 sm:gap-2 sm:px-3 sm:text-sm {{ $activeFilter ? 'border-indigo-200 bg-indigo-50 text-indigo-700 shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700' }}">
                                    <i class="{{ $meta['icon'] }} text-sm sm:text-base"></i>
                                    {{ $meta['label'] }}
                                    <span class="rounded-full bg-white px-1.5 py-0.5 text-[10px] font-bold text-slate-500 ring-1 ring-slate-100 sm:px-2 sm:text-[11px]">
                                        {{ $filterCounts[$key] ?? 0 }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>

                <!-- 🔹 Сетка товаров -->
                <div>
                    <h2 class="mb-4 text-lg font-semibold text-slate-950 sm:text-xl">{{ $filterTitles[$filter] ?? 'Товары магазина' }}</h2>

                    @if($products->count())
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 md:grid-cols-4 lg:grid-cols-5">
                            @foreach($products as $product)
                                {{-- Используем тот же компонент, что и на главной --}}
                                <x-product-card :p="$product" />
                            @endforeach
                        </div>

                        <div class="mt-6 flex justify-center" data-shop-pagination>
                            {{ $products->links('vendor.pagination.tailwind') }}
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-10 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-xl text-slate-400">
                                <i class="{{ $filters[$filter]['icon'] ?? 'ri-store-2-line' }}"></i>
                            </div>
                            <h3 class="mt-3 font-semibold text-slate-950">Ничего не найдено</h3>
                            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ $emptyFilterText[$filter] ?? $emptyFilterText['all'] }}</p>
                            @if($filter !== 'all')
                                <a href="{{ route('seller.show', $shop->slug) }}" data-shop-filter-link class="mt-4 inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    Показать все товары
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
                <!-- 🔹 Рекомендованные товары -->
                @if($filter === 'all' && ($recommendedProducts ?? collect())->count())
                    <section
                        class="rounded-2xl border border-slate-200 bg-white p-3 shadow-[0_1px_2px_rgba(15,23,42,0.03)] sm:p-4"
                        x-data="{
                            paused: false,
                            cardStep() {
                                const el = this.$refs.track;
                                const card = el?.querySelector('[data-recommended-card]');
                                const gap = window.innerWidth < 640 ? 12 : 16;

                                return card ? card.getBoundingClientRect().width + gap : 260;
                            },
                            normalize() {
                                const el = this.$refs.track;
                                if (!el) return;

                                const amount = this.cardStep();
                                let guard = 0;

                                while (el.scrollLeft >= amount - 2 && guard < 8) {
                                    const first = el.querySelector('[data-recommended-card]');
                                    if (!first) break;

                                    el.appendChild(first);
                                    el.scrollLeft = el.scrollLeft - amount;
                                    guard++;
                                }
                            },
                            step() {
                                const el = this.$refs.track;
                                if (!el || this.paused || el.scrollWidth <= el.clientWidth) return;

                                el.scrollBy({ left: this.cardStep(), behavior: 'smooth' });
                                setTimeout(() => this.normalize(), 620);
                            },
                            scroll(direction) {
                                const el = this.$refs.track;
                                if (!el) return;

                                const amount = this.cardStep();

                                if (direction < 0) {
                                    const cards = el.querySelectorAll('[data-recommended-card]');
                                    const last = cards[cards.length - 1];
                                    const first = cards[0];

                                    if (last && first) {
                                        el.insertBefore(last, first);
                                        el.scrollLeft = el.scrollLeft + amount;
                                    }
                                }

                                el.scrollBy({ left: direction * amount, behavior: 'smooth' });
                                setTimeout(() => this.normalize(), 620);
                            }
                        }"
                        x-init="setInterval(() => step(), 3200)"
                        @mouseenter="paused = true"
                        @mouseleave="paused = false"
                        @touchstart="paused = true"
                        @touchend="paused = false"
                    >
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-950 sm:text-xl">Рекомендованные</h2>
                                <p class="mt-0.5 text-xs text-slate-500">Популярные товары.</p>
                            </div>

                            <div class="hidden shrink-0 items-center gap-2 sm:flex">
                                <button
                                    type="button"
                                    @click="paused = true; scroll(-1)"
                                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"
                                    title="Назад"
                                >
                                    <i class="ri-arrow-left-s-line text-xl"></i>
                                </button>
                                <button
                                    type="button"
                                    @click="paused = true; scroll(1)"
                                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"
                                    title="Вперёд"
                                >
                                    <i class="ri-arrow-right-s-line text-xl"></i>
                                </button>
                            </div>
                        </div>

                        <div
                            x-ref="track"
                            @scroll.debounce.120ms="normalize()"
                            class="seller-recommended-track flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth sm:gap-4"
                        >
                            @foreach($recommendedProducts as $product)
                                <div data-recommended-card class="seller-recommended-card shrink-0 snap-start">
                                    <x-product-card :p="$product" />
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>
        </section>

        <!-- 🔹 Плюшки: доставка, гарантии, акции -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-4 mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-gray-700 text-sm md:text-base flex flex-col md:flex-row md:gap-6 gap-2">
                <span class="flex items-center gap-1"><i class="ri-truck-line text-indigo-600"></i> Быстрая доставка</span>
                <span class="flex items-center gap-1"><i class="ri-shield-check-line text-indigo-600"></i> Гарантия качества</span>
                <span class="flex items-center gap-1"><i class="ri-star-line text-indigo-500"></i> Топ продавцов</span>
            </div>
            <a href="{{ route('seller.show', $shop->slug) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                Смотреть товары магазина
            </a>
        </div>

    </div>

    @include('layouts.mobile-bottom-nav')
    @if($chatConversation)
        @include('chats.partials.widget', [
            'conversation' => $chatConversation,
            'messages' => $chatMessages,
            'hasOlderMessages' => $chatHasOlderMessages,
            'oldestMessageId' => $chatOldestMessageId,
            'latestMessageId' => $chatLatestMessageId,
            'latestReadOutgoingMessageId' => $chatLatestReadOutgoingMessageId,
            'returnUrl' => route('seller.show', [
                'identifier' => $shop->slug,
                'chat' => $chatConversation->id,
            ], false),
            'closeUrl' => route('seller.show', $shop->slug, false),
        ])
    @endif

    <style>
    .line-clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    [x-cloak] { display: none !important; }
    .seller-shop-filter-track {
        scrollbar-width: none;
    }
    .seller-shop-filter-track::-webkit-scrollbar {
        display: none;
    }
    .seller-recommended-track {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .seller-recommended-track::-webkit-scrollbar {
        display: none;
    }
    .seller-recommended-card {
        flex-basis: calc((100% - 0.75rem) / 2);
        min-width: 0;
    }
    @media (min-width: 640px) {
        .seller-recommended-card {
            flex-basis: 260px;
        }
    }
    @media (min-width: 768px) {
        .seller-recommended-card {
            flex-basis: 245px;
        }
    }
    @media (min-width: 1024px) {
        .seller-recommended-card {
            flex-basis: 230px;
        }
    }
    @media (min-width: 1280px) {
        .seller-recommended-card {
            flex-basis: 220px;
        }
    }
</style>
</x-app-layout>

@endif
