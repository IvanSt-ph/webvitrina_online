<x-app-layout :title="$product->title">

    @php
        /** Флаг: товар уже в избранном у текущего пользователя или нет */
        $isFav = auth()->check() && $product->isFavoritedBy(auth()->user());
    @endphp

    {{-- SEO / META --}}
    @push('meta')
        <link rel="canonical" href="{{ url()->current() }}" />
    @endpush

    {{-- ==================== ОСНОВНОЙ КОНТЕЙНЕР ==================== --}}
    <div
        class="w-full max-w-[1440px] mx-auto
               pt-20 pb-10
               px-3 sm:px-4 md:px-6 lg:px-8
               overflow-x-hidden"
    >

        {{-- ================= Хлебные крошки ================= --}}
        <nav class="mb-4 text-sm text-gray-500 flex flex-wrap items-center gap-1">
            <a href="{{ route('home') }}" class="hover:text-indigo-600">Главная</a>

            @if ($product->category)
                @php
                    $breadcrumbs = [];
                    $cat = $product->category;
                    while ($cat) {
                        $breadcrumbs[] = $cat;
                        $cat = $cat->parent;
                    }
                    $breadcrumbs = array_reverse($breadcrumbs);
                @endphp

                @foreach ($breadcrumbs as $cat)
                    <span>›</span>
                    <a href="{{ route('category.show', $cat->slug) }}" class="hover:text-indigo-600">
                        {{ $cat->name }}
                    </a>
                @endforeach
            @endif
        </nav>

        {{-- =========================================================
             ВЕРХНИЙ БЛОК: Галерея + Инфо + Цена
        ========================================================== --}}
        <div
            class="grid gap-6 lg:grid-cols-12
                   bg-white border border-gray-200 rounded-3xl shadow-sm
                   p-4 sm:p-6 md:p-8
                   justify-center"
        >
            {{-- ================= ЛЕВО: ГАЛЕРЕЯ ================= --}}
            <div class="lg:col-span-5 w-full max-w-xl mx-auto">
                <div
                    x-data="{
                        activeImage: '{{ $product->image ? asset('storage/'.$product->image) : '' }}',
                        images: [
                            @if ($product->image)
                                '{{ asset('storage/'.$product->image) }}',
                            @endif
                            @foreach ($product->gallery ?? [] as $img)
                                '{{ asset('storage/'.$img) }}',
                            @endforeach
                        ],
                        startIndex: 0,
                        visibleCount: 6,
                        get canScrollUp()   { return this.startIndex > 0 },
                        get canScrollDown() { return this.startIndex + this.visibleCount < this.images.length },
                        scrollUp()          { if (this.canScrollUp)   this.startIndex-- },
                        scrollDown()        { if (this.canScrollDown) this.startIndex++ },
                        startX: 0,
                        handleTouchStart(e) { this.startX = e.touches[0].clientX },
                        handleTouchEnd(e) {
                            const diff = e.changedTouches[0].clientX - this.startX;
                            if (Math.abs(diff) > 50) {
                                const idx = this.images.indexOf(this.activeImage);
                                if (diff < 0 && idx < this.images.length - 1) {
                                    this.activeImage = this.images[idx + 1];
                                } else if (diff > 0 && idx > 0) {
                                    this.activeImage = this.images[idx - 1];
                                }
                            }
                        }
                    }"
                    class="flex flex-col md:flex-row gap-4 items-start"
                >
                    {{-- 📱 Мобильное большое фото со свайпом --}}
                    <div
                        x-on:touchstart="handleTouchStart($event)"
                        x-on:touchend="handleTouchEnd($event)"
                        class="md:hidden w-full bg-gray-50 border rounded-2xl
                               flex items-center justify-center
                               aspect-square
                               max-h-[380px] sm:max-h-[430px] md:max-h-[480px]
                               overflow-hidden relative select-none"
                    >
                        <template x-if="activeImage">
                            <img
                                :src="activeImage"
                                class="object-contain w-full h-full transition-transform duration-300 ease-in-out"
                            />
                        </template>

                        {{-- Индикаторы внизу --}}
                        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2">
                            <template x-for="(img, i) in images" :key="i">
                                <div
                                    class="w-2.5 h-2.5 rounded-full"
                                    :class="activeImage === img ? 'bg-indigo-600' : 'bg-gray-300'"
                                ></div>
                            </template>
                        </div>
                    </div>

                    {{-- 💻 Десктоп: вертикальные превью + большое фото --}}
                    <div class="hidden md:flex gap-4 items-start w-full">
                        {{-- Превью --}}
                        <div class="relative flex md:flex-col items-center h-[520px] lg:h-[580px] xl:h-[620px]">
                            {{-- ↑ кнопка скролла превью --}}
                            <template x-if="canScrollUp && images.length > visibleCount">
                                <button
                                    @click="scrollUp()"
                                    class="absolute -top-4 left-1/2 -translate-x-1/2
                                           bg-white border border-gray-200
                                           rounded-full w-8 h-8 flex items-center justify-center shadow-sm
                                           hover:bg-indigo-50 hover:scale-105
                                           transition-all duration-200 z-10"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-700" fill="none"
                                         viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
                                    </svg>
                                </button>
                            </template>

                            <div class="flex md:flex-col gap-2 overflow-hidden w-full md:w-auto h-full relative">
                                <div
                                    class="flex md:flex-col gap-2 transition-transform duration-500 ease-in-out"
                                    :style="{ transform: `translateY(-${startIndex * 108}px)` }"
                                >
                                    <template x-for="(img, i) in images" :key="i">
                                        <img
                                            :src="img"
                                            @mouseover="activeImage = img"
                                            @click="activeImage = img"
                                            class="w-20 h-[6.3rem] object-cover rounded-xl border cursor-pointer
                                                   transition-all duration-300 ease-out
                                                   hover:ring-2 hover:ring-indigo-600 hover:scale-[1.05] hover:opacity-90"
                                            :class="{ 'ring-2 ring-indigo-600': activeImage === img }"
                                        >
                                    </template>
                                </div>
                            </div>

                            {{-- ↓ кнопка скролла превью --}}
                            <template x-if="canScrollDown && images.length > visibleCount">
                                <button
                                    @click="scrollDown()"
                                    class="absolute -bottom-4 left-1/2 -translate-x-1/2
                                           bg-white border border-gray-200
                                           rounded-full w-8 h-8 flex items-center justify-center shadow-sm
                                           hover:bg-indigo-50 hover:scale-105
                                           transition-all duration-200 z-10"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-700" fill="none"
                                         viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </template>
                        </div>

                        {{-- Большое фото --}}
                        <div
                            class="flex-1 bg-gray-50 border rounded-2xl
                                   flex items-center justify-center aspect-square
                                   h-[520px] lg:h-[580px] xl:h-[620px]
                                   overflow-hidden w-full relative"
                        >
                            <template x-if="activeImage">
                                <img
                                    :src="activeImage"
                                    class="object-contain w-full h-full transition-transform duration-300 hover:scale-105"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========== ЦЕНТР: НАЗВАНИЕ + КРАТКАЯ ИНФО ========== --}}
            <div class="lg:col-span-4 w-full max-w-xl mx-auto">
                <div>
                    {{-- Название --}}
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-snug">
                        {{ $product->title }}
                    </h1>

                    {{-- Рейтинг / отзывы / заказы --}}
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-gray-600">
                        <div class="flex items-center gap-1">
                            <span class="text-yellow-400">★</span>
                            <span class="font-semibold">
                                {{ number_format($product->reviews_avg_rating ?? 0, 1) }}
                            </span>
                            <span class="text-gray-400">
                                ({{ $product->reviews_count }} отзывов)
                            </span>
                        </div>

                        @if ($product->orders_count ?? 0)
                            <span class="text-gray-400">
                                · {{ $product->orders_count }} заказов
                            </span>
                        @endif
                    </div>

                    {{-- Мини-таблица характеристик --}}
                    <div class="mt-5 space-y-3 text-sm text-gray-700">
                        @if ($product->sku)
                            <div class="flex flex-col sm:flex-row sm:justify-between">
                                <span class="text-gray-500">Артикул</span>
                                <span class="font-medium text-gray-800 sm:text-right">
                                    {{ $product->sku }}
                                </span>
                            </div>
                        @endif

                        @if ($product->category)
                            <div class="flex flex-col sm:flex-row sm:justify-between">
                                <span class="text-gray-500">Категория</span>
                                <span class="font-medium text-gray-800 sm:text-right">
                                    {{ $product->category->name }}
                                </span>
                            </div>
                        @endif

                        @if ($product->city || $product->country)
                            <div class="flex flex-col sm:flex-row sm:justify-between">
                                <span class="text-gray-500">Местоположение</span>
                                <span class="font-medium text-gray-800 sm:text-right">
                                    @if ($product->country)
                                        {{ $product->country->name }}
                                    @elseif($product->city && $product->city->country)
                                        {{ $product->city->country->name }}
                                    @endif
                                    @if ($product->city)
                                        , {{ $product->city->name }}
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Продавец (мини-блок) --}}
                    @if ($product->seller)
                        <div class="mt-6 bg-gray-50 border border-gray-100 rounded-xl p-4">
                            <div class="text-xs uppercase tracking-wide text-gray-400 mb-1">Магазин</div>
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-gray-900">
                                        {{ $product->seller->name }}
                                    </div>
                                    <div class="text-xs text-gray-600 mt-1">
                                        ⭐ {{ number_format($product->seller->reviews_avg_rating ?? 0, 2) }}
                                        · {{ $product->seller->reviews_count }} отзывов
                                    </div>
                                </div>
                                <a href="{{ route('seller.show', $product->seller) }}"
                                   class="inline-flex items-center text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                    Перейти в магазин →
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ========== ПРАВО: ЦЕНА + КНОПКИ ========== --}}
            <div class="lg:col-span-3 w-full max-w-xl mx-auto">
                <div
                    class="bg-indigo-50/40 border border-indigo-100/70 rounded-3xl
                           p-5 sm:p-6
                           shadow-[0_18px_45px_rgba(15,23,42,0.12)]
                           lg:sticky lg:top-24 space-y-4"
                >
                    {{-- Цена + скидка --}}
                    <div class="space-y-1">
                        <div class="flex items-baseline gap-2">
                            <div class="text-3xl font-semibold text-gray-900 leading-none">
                                {{ number_format($product->price, 0, ',', ' ') }} ₽
                            </div>

                            @if ($product->old_price)
                                <div class="text-sm text-gray-400 line-through">
                                    {{ number_format($product->old_price, 0, ',', ' ') }} ₽
                                </div>
                            @endif
                        </div>

                        @if ($product->old_price && $product->old_price > 0 && $product->old_price > $product->price)
                            @php
                                $discount = round(100 - ($product->price / $product->old_price * 100));
                            @endphp
                            <div
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                       bg-pink-100 text-pink-700 border border-pink-200 mt-1"
                            >
                                -{{ $discount }}% выгода
                            </div>
                        @endif
                    </div>

                    {{-- Доставка / возврат (заглушка) --}}
                    <div class="mt-3 space-y-1 text-xs text-gray-600">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🚚</span>
                            <span>Доставка: уточняется при оформлении заказа</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-base">↩️</span>
                            <span>14 дней на возврат, если товар не подошёл</span>
                        </div>
                    </div>

                    {{-- Кнопки действий --}}
                    <div class="mt-5 flex flex-col gap-3">
                        @auth
                            {{-- ⚡ Купить сейчас --}}
                            <form method="POST" action="{{ route('checkout.quick', $product->id) }}">
                                @csrf
                                <button
                                    class="w-full py-3.5 rounded-xl text-base font-semibold text-white text-center
                                           bg-gradient-to-r from-indigo-600 to-fuchsia-500
                                           hover:from-indigo-600 hover:to-fuchsia-600
                                           active:scale-[0.98]
                                           shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/40
                                           transition-all duration-200"
                                >
                                    ⚡ Купить сейчас
                                </button>
                            </form>

                            {{-- 🛒 В корзину --}}
                            <form method="post" action="{{ route('cart.add', $product) }}">
                                @csrf
                                <button
                                    class="w-full py-3 rounded-xl text-base font-medium text-gray-800 text-center
                                           bg-white hover:bg-gray-50 active:scale-[0.98]
                                           border border-gray-200 shadow-sm
                                           transition-all duration-200 flex items-center justify-center gap-2"
                                >
                                    🛒 <span>в корзину</span>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}"
                               class="w-full py-3.5 rounded-xl text-base font-semibold text-white text-center
                                      bg-gradient-to-r from-indigo-600 to-fuchsia-500
                                      hover:from-indigo-600 hover:to-fuchsia-600
                                      active:scale-[0.98]
                                      shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/40
                                      transition-all duration-200 block">
                                Войти, чтобы купить
                            </a>
                        @endauth
                    </div>

                    {{-- Артикул + избранное --}}
                    <div
                        x-data="{ copied: false }"
                        class="mt-4 pt-3 border-t border-indigo-100 flex items-center justify-between gap-3
                               text-sm text-gray-600"
                    >
                        @if ($product->sku)
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-1 text-gray-500">
                                    <span class="text-gray-400">Арт.</span>
                                    <span class="font-medium text-gray-800" id="sku-value">
                                        {{ $product->sku }}
                                    </span>
                                </div>

                                <button
                                    @click="navigator.clipboard.writeText('{{ $product->sku }}'); copied = true; setTimeout(() => copied = false, 1500)"
                                    class="text-gray-400 hover:text-indigo-600 transition"
                                    title="Скопировать артикул"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none"
                                         viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-2 8h4a2 2 0 002-2v-4a2 2 0 00-2-2h-4a2 2 0 00-2 2v4a2 2 0 002 2z" />
                                    </svg>
                                </button>

                                <span
                                    x-show="copied"
                                    x-transition
                                    class="text-green-600 text-xs font-medium"
                                >
                                    Скопировано
                                </span>
                            </div>
                        @endif

                        {{-- Форма избранного + анимация сердца --}}
                        <form method="POST" action="{{ route('favorites.toggle', $product) }}"
                              x-data="{
                                active: {{ $isFav ? 'true' : 'false' }},
                                anim: '',
                                toggleFavorite() {
                                    fetch('{{ route('favorites.toggle', $product) }}', {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        }
                                    })
                                    .then(r => r.json())
                                    .then(() => {
                                        this.anim  = this.active ? 'implode' : 'explode';
                                        this.active = !this.active;
                                        setTimeout(() => this.anim = '', 600);
                                    });
                                }
                              }"
                              @submit.prevent="toggleFavorite()"
                              class="relative flex flex-col items-center gap-1 mt-2"
                        >
                            {{-- ❤️ Сердце --}}
                            <button
                                type="button"
                                @click="toggleFavorite()"
                                class="p-1.5 transition relative"
                            >
                                <svg
                                    class="w-6 h-6 transition-transform duration-300"
                                    :class="active ? 'scale-110' : 'scale-100'"
                                    :fill="active ? '#74bdfd' : 'transparent'"
                                    :stroke="active ? 'none' : '#74bdfd'"
                                    stroke-width="1.7"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M12 21.35l-1.45-1.32C5.4 15.36
                                           2 12.28 2 8.5 2 5.42 4.42 3
                                           7.5 3c1.74 0 3.41 0.81
                                           4.5 2.09C13.09 3.81
                                           14.76 3 16.5 3c3.08 0 5.5 2.42 5.5 5.5
                                           0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
                                    />
                                </svg>

                                {{-- ✨ Explode --}}
                                <template x-if="anim === 'explode'">
                                    <div>
                                        <template x-for="i in 8">
                                            <div class="dot" :style="'--i:' + i"></div>
                                        </template>
                                    </div>
                                </template>

                                {{-- ✨ Implode --}}
                                <template x-if="anim === 'implode'">
                                    <div>
                                        <template x-for="i in 8">
                                            <div class="dot dot-in" :style="'--i:' + i"></div>
                                        </template>
                                    </div>
                                </template>
                            </button>

                            <span class="text-xs text-gray-500">В избранное</span>
                        </form>
                    </div>
                </div>
            </div>
        </div> {{-- /верхний блок --}}

        {{-- ===================== МЕСТОПОЛОЖЕНИЕ ===================== --}}
        @if ($product->city || $product->country || $product->address)
            <div class="mt-8 bg-white border rounded-2xl p-5 shadow-sm">
                <div class="text-sm text-gray-500 mb-1">Местоположение</div>

                <div class="font-medium text-gray-800">
                    @if ($product->country)
                        {{ $product->country->name }}
                    @elseif($product->city && $product->city->country)
                        {{ $product->city->country->name }}
                    @endif

                    @if ($product->city)
                        , {{ $product->city->name }}
                    @endif
                </div>

                @if ($product->address)
                    <div class="mt-1 text-gray-700">
                        {{ $product->address }}
                    </div>
                @endif

                @if ($product->latitude && $product->longitude)
                    <div class="mt-3">
                        <div id="map" class="w-full h-56 rounded-lg border"></div>
                        <a href="https://www.google.com/maps/search/?api=1&query={{ $product->latitude }},{{ $product->longitude }}"
                           target="_blank"
                           class="mt-2 inline-block text-indigo-600 hover:underline text-sm">
                            📍 Открыть в Google Maps
                        </a>
                    </div>
                @endif
            </div>
        @endif

        {{-- =========================================================
             ВКЛАДКИ: Описание / Размеры / Характеристики / Отзывы
        ========================================================== --}}
        <div
            class="mt-12 bg-white border rounded-2xl shadow-sm p-6"
            x-data="{ tab: 'desc' }"
        >
            {{-- Навигация вкладок --}}
            <div class="flex flex-wrap gap-6 border-b pb-2 text-sm">
                <button
                    @click="tab='desc'"
                    :class="tab==='desc'
                        ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium'
                        : 'text-gray-600'"
                    class="pb-2 transition"
                >
                    Описание
                </button>

                <button
                    @click="tab='sizes'"
                    :class="tab==='sizes'
                        ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium'
                        : 'text-gray-600'"
                    class="pb-2 transition"
                >
                    Размеры
                </button>

                <button
                    @click="tab='props'"
                    :class="tab==='props'
                        ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium'
                        : 'text-gray-600'"
                    class="pb-2 transition"
                >
                    Характеристики
                </button>

                <button
                    @click="tab='reviews'"
                    :class="tab==='reviews'
                        ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium'
                        : 'text-gray-600'"
                    class="pb-2 transition"
                >
                    Отзывы ({{ $product->reviews_count }})
                </button>
            </div>

            {{-- Контент вкладок --}}
            <div class="mt-6">
                {{-- Описание --}}
                <div x-show="tab==='desc'" x-transition.opacity.duration.400ms>
                    <p class="text-gray-700 leading-relaxed">
                        {{ $product->description }}
                    </p>
                </div>

                {{-- Размеры (заглушка) --}}
                <div x-show="tab==='sizes'" x-transition.opacity.duration.400ms>
                    <p class="text-gray-700">
                        Таблица размеров (сюда можно вывести данные из БД).
                    </p>
                </div>

                {{-- Характеристики (пример) --}}
                <div x-show="tab==='props'" x-transition.opacity.duration.400ms>
                    <ul class="text-gray-700 list-disc pl-5 space-y-1">
                        <li>Материал: {{ $product->material ?? '—' }}</li>
                        <li>Сезон: {{ $product->season ?? 'Всесезон' }}</li>
                        <li>Бренд: {{ $product->brand->name ?? '—' }}</li>
                    </ul>
                </div>

                {{-- ==================== ОТЗЫВЫ ==================== --}}
                <div
                    x-show="tab==='reviews'"
                    x-cloak
                    class="space-y-6"
                    x-transition.opacity.duration.400ms
                    x-data
                    x-init="
                        const observer = new IntersectionObserver(entries => {
                            entries.forEach(el => {
                                if (el.isIntersecting) {
                                    el.target.classList.add('animate-fade-in-up');
                                }
                            });
                        }, { threshold: 0.1 });
                        document.querySelectorAll('.review-card')
                            .forEach(c => observer.observe(c));
                    "
                >
                    @auth
                        @php
                            $myReview = $product->reviews->firstWhere('user_id', auth()->id());
                        @endphp

                        {{-- Форма отзыва (создание / редактирование) --}}
                        <div
                            x-data="{
                                editing: {{ $myReview ? 'false' : 'true' }},
                                rating:  {{ $myReview->rating ?? 0 }},
                                hoverRating: 0
                            }"
                            class="bg-gray-50 border rounded-2xl p-5 shadow-sm space-y-3"
                        >
                            {{-- Уже есть отзыв --}}
                            <template x-if="!editing">
                                <div class="flex justify-between items-center gap-3">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800">Ваш отзыв</h3>
                                        <p class="text-gray-700 mt-1">
                                            {{ $myReview->body ?? 'Без текста' }}
                                        </p>

                                        @if ($myReview && $myReview->images->count())
                                            <div class="mt-3 flex gap-3 flex-wrap">
                                                @foreach ($myReview->images as $img)
                                                    <a href="{{ asset('storage/'.$img->path) }}" target="_blank">
                                                        <img
                                                            src="{{ asset('storage/'.$img->path) }}"
                                                            class="w-24 h-24 object-cover rounded-lg border
                                                                   hover:scale-105 transition-transform duration-300"
                                                        >
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <button
                                        @click="editing = true"
                                        class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700
                                               text-white rounded-lg transition"
                                    >
                                        ✏️ Изменить
                                    </button>
                                </div>
                            </template>

                            {{-- Форма создания / редактирования --}}
                            <template x-if="editing">
                                <form
                                    method="post"
                                    action="{{ route('review.store', $product) }}"
                                    enctype="multipart/form-data"
                                    class="space-y-3"
                                >
                                    @csrf

                                    <h3 class="text-lg font-semibold text-gray-800">
                                        {{ $myReview ? 'Изменить отзыв' : 'Оставить отзыв' }}
                                    </h3>

                                    {{-- Звёзды --}}
                                    <div
                                        class="flex items-center gap-2"
                                        @mouseleave="hoverRating = 0"
                                    >
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg
                                                @mouseover="hoverRating={{ $i }}"
                                                @click="rating={{ $i }}"
                                                :class="{
                                                    'text-yellow-400 scale-110':
                                                        {{ $i }} <= (hoverRating || rating),
                                                    'text-gray-300':
                                                        {{ $i }} > (hoverRating || rating)
                                                }"
                                                class="w-8 h-8 cursor-pointer transition-all duration-200 transform"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.945a1 1 0 00.95.69h4.148c.969 0 1.371 1.24.588 1.81l-3.357 2.44a1 1 0 00-.364 1.118l1.286 3.945c.3.921-.755 1.688-1.54 1.118l-3.357-2.44a1 1 0 00-1.175 0l-3.357 2.44c-.784.57-1.839-.197-1.54-1.118l1.286-3.945a1 1 0 00-.364-1.118L2.075 9.372c-.783-.57-.38-1.81.588-1.81h4.148a1 1 0 00.95-.69l1.286-3.945z"
                                                />
                                            </svg>
                                        @endfor

                                        <input type="hidden" name="rating" :value="rating">
                                    </div>

                                    {{-- Текст --}}
                                    <textarea
                                        name="body"
                                        rows="3"
                                        placeholder="Поделись впечатлениями о товаре..."
                                        class="w-full border rounded-lg p-3
                                               focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                    >{{ $myReview->body ?? '' }}</textarea>

                                    {{-- Фото --}}
                                    <input
                                        type="file"
                                        name="images[]"
                                        multiple
                                        accept="image/*"
                                        class="block w-full text-sm text-gray-600 border rounded-lg p-2
                                               cursor-pointer hover:border-indigo-500 transition"
                                    >
                                    <p class="text-xs text-gray-400 mt-1">
                                        Можно добавить до 3 фото
                                    </p>

                                    <div class="flex justify-between items-center">
                                        <button
                                            type="submit"
                                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700
                                                   text-white rounded-lg shadow transition"
                                        >
                                            💾 {{ $myReview ? 'Сохранить изменения' : 'Отправить' }}
                                        </button>

                                        @if ($myReview)
                                            <button
                                                type="button"
                                                @click="editing = false"
                                                class="text-sm text-gray-500 hover:text-gray-700"
                                            >
                                                Отмена
                                            </button>
                                        @endif
                                    </div>
                                </form>
                            </template>
                        </div>
                    @endauth

                    {{-- Список отзывов --}}
                    <div class="space-y-4">
                        @forelse ($product->reviews as $r)
                            <div
                                class="review-card opacity-0 translate-y-6
                                       bg-white border rounded-2xl p-4 shadow-sm
                                       hover:shadow-md transition"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-full bg-indigo-100
                                                   flex items-center justify-center
                                                   text-indigo-700 font-bold"
                                        >
                                            {{ mb_substr($r->user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-800">
                                                {{ $r->user->name }}
                                            </div>
                                            <div class="flex text-yellow-400 text-sm">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <span class="{{ $i <= $r->rating ? 'text-yellow-400' : 'text-gray-300' }}">
                                                        ★
                                                    </span>
                                                @endfor
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        {{ $r->created_at->diffForHumans() }}
                                    </div>
                                </div>

                                <div class="text-gray-700 leading-relaxed border-t pt-2">
                                    {{ $r->body }}
                                </div>

                                @if ($r->images->count())
                                    <div class="mt-3 flex gap-3 flex-wrap">
                                        @foreach ($r->images as $img)
                                            <a href="{{ asset('storage/'.$img->path) }}" target="_blank">
                                                <img
                                                    src="{{ asset('storage/'.$img->path) }}"
                                                    class="w-24 h-24 object-cover rounded-lg border
                                                           hover:scale-105 transition-transform duration-300"
                                                >
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center text-gray-500 py-10">
                                <p class="text-lg">Пока нет отзывов 😌</p>
                                <p class="text-sm mt-1">
                                    Стань первым, кто поделится мнением о товаре!
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ Анимации для отзывов и фикс карты ============ --}}
        <style>
            @keyframes fade-in-up {
                0% {
                    opacity: 0;
                    transform: translateY(12px);
                }
                100% {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .animate-fade-in-up {
                animation: fade-in-up 0.6s ease forwards;
            }

            svg {
                transition: transform 0.2s ease, color 0.2s ease;
            }

            svg:hover {
                transform: scale(1.15);
            }

            #map,
            #map * {
                z-index: 0 !important;
            }

            #map .leaflet-control-container {
                z-index: 1 !important;
            }
        </style>

        {{-- ==================== Похожие товары ==================== --}}
        <div class="mt-12">
            <h2 class="text-xl font-semibold mb-4">Похожие товары</h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach ($related ?? [] as $item)
                    <a
                        href="{{ route('product.show', $item->slug) }}"
                        class="bg-white border rounded-xl p-3
                               hover:shadow-lg transition group"
                    >
                        @if ($item->image)
                            <img
                                src="{{ asset('storage/'.$item->image) }}"
                                class="w-full h-48 object-cover rounded-lg mb-2
                                       group-hover:scale-105 transition-transform duration-300"
                            />
                        @endif

                        <div class="text-sm font-medium line-clamp-2">
                            {{ $item->title }}
                        </div>

                        <div class="text-indigo-600 font-semibold mt-1">
                            {{ number_format($item->price, 0, ',', ' ') }} ₽
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

    </div> {{-- /container --}}

    {{-- ====================== Leaflet карта ====================== --}}
    @if ($product->latitude && $product->longitude)
        <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

        <style>
            #map .leaflet-control-attribution {
                font-size: 11px !important;
                color: #666 !important;
                background: rgba(255, 255, 255, .8) !important;
                border-radius: 6px !important;
                padding: 2px 6px !important;
            }
        </style>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const lat = {{ $product->latitude }};
                const lng = {{ $product->longitude }};

                const map = L.map('map').setView([lat, lng], 15);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                map.attributionControl.setPrefix(false);
                map.attributionControl.setPosition('bottomleft');

                L.marker([lat, lng])
                    .addTo(map)
                    .bindPopup("{{ addslashes($product->title) }}");
            });
        </script>
    @endif

    {{-- ======================== флеш-успех ======================== --}}
    @if (session('success'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-transition.duration.500ms
            x-init="setTimeout(() => show = false, 3000)"
            class="fixed bottom-6 right-6 z-50 bg-green-600 text-white
                   px-5 py-3 rounded-xl shadow-lg flex items-center gap-2"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ==================== Анимация сердца (dot) ==================== --}}
    <style>
        .dot {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 6px;
            height: 6px;
            background: #74bdfd;
            border-radius: 50%;
            opacity: 0;
            animation: burst 0.6s ease-out forwards;
        }

        @keyframes burst {
            0% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 1;
            }
            100% {
                transform: rotate(calc(var(--i) * 45deg))
                           translate(-50%, -40px)
                           scale(.3);
                opacity: 0;
            }
        }

        .dot-in {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 6px;
            height: 6px;
            background: #74bdfd;
            border-radius: 50%;
            opacity: 0;
            animation: collapse 0.6s ease-in forwards;
        }

        @keyframes collapse {
            0% {
                transform: rotate(calc(var(--i) * 45deg))
                           translate(-50%, -40px)
                           scale(.3);
                opacity: 1;
            }
            100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 0;
            }
        }
    </style>

</x-app-layout>
