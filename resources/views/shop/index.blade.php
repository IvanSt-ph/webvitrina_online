{{-- resources/views/shop/index.blade.php --}}
{{-- Главная / Каталог с баннерами и сортировкой --}}

@php
$firstBanner = $bannerItems->first();
$catalogSeo = \App\Support\CatalogSeo::forListing(request(), route(request()->routeIs('search') ? 'search' : 'home'), $products->currentPage());
@endphp

{{-- ✅ Предзагрузка первого баннера для ускорения --}}
@push('meta')
@include('partials.catalog-seo')
<meta name="description" content="WebVitrina — товары и магазины вашего города. Находите предложения местных продавцов, сравнивайте цены и выбирайте товары рядом с вами.">
@if($firstBanner)
<link rel="preload" as="image" href="{{ $firstBanner->imageUrl('mobile') }}" media="(width <= 768px)">
<link rel="preload" as="image" href="{{ $firstBanner->imageUrl('tablet') }}" media="(768px < width <= 1280px)">
<link rel="preload" as="image" href="{{ $firstBanner->imageUrl('desktop') }}" media="(width > 1280px)">
@endif
@endpush

<x-app-layout title="Каталог">

  {{-- 🚀 Адаптивный баннер с плавной сменой изображений --}}
  {{-- ⚠️ ВНИМАНИЕ: .banner-container не должен иметь padding, иначе absolute блок "поплывёт" --}}
  <div class="relative -mt-[15px] w-full flex justify-center bg-transparent">
    <div 
      class="w-[92%] max-w-[1440px] overflow-hidden rounded-b-2xl relative banner-container"
    >
      @if($bannerItems->isNotEmpty())
      <div 
        x-data="{
          active: 0,
          timer: null,
          paused: false,
          screen: null,
          resizeHandler: null,
          slides: @js($bannerItems->map(fn($b) => [
              'desktop' => $b->imageUrl('desktop'),
              'candidates' => ['desktop' => $b->imageCandidates('desktop'), 'tablet' => $b->imageCandidates('tablet'), 'mobile' => $b->imageCandidates('mobile')],
              'tablet'  => $b->imageUrl('tablet'),
              'mobile'  => $b->imageUrl('mobile'),
              'link'    => $b->link ?: '#',
          ])),
          next() { 
            this.active = (this.active + 1) % this.slides.length 
          },
          prev() { 
            this.active = (this.active - 1 + this.slides.length) % this.slides.length 
          },
          start() { 
            if (this.timer) clearInterval(this.timer);
            this.timer = setInterval(() => { 
              if (!this.paused) this.next() 
            }, 6000) 
          },
          srcFor(slide) {
            if (!this.screen) return slide.desktop;
            if (this.screen <= 768) return slide.mobile ?? slide.tablet ?? slide.desktop;
            if (this.screen <= 1280) return slide.tablet ?? slide.desktop;
            return slide.desktop;
          },
          init() {
            this.screen = window.innerWidth;
            this.resizeHandler = () => { this.screen = window.innerWidth };
            window.addEventListener('resize', this.resizeHandler);
            this.start();
          },
          destroy() {
            if (this.resizeHandler) {
              window.removeEventListener('resize', this.resizeHandler);
            }
            if (this.timer) {
              clearInterval(this.timer);
            }
          }
        }"
        @mouseenter="paused = true"
        @mouseleave="paused = false"
        class="absolute inset-0 z-[1] overflow-hidden rounded-b-2xl bg-gray-100"
      >
        <a 
          href="{{ $firstBanner->link ?: '#' }}"
          :href="slides[active].link" 
          class="banner-bg block w-full h-full"
        >
          <picture class="block w-full h-full">
            <source media="(width <= 768px)"
              srcset="{{ $firstBanner->imageUrl('mobile') }}"
              :srcset="slides[active].mobile">
            <source media="(768px < width <= 1280px)"
              srcset="{{ $firstBanner->imageUrl('tablet') }}"
              :srcset="slides[active].tablet">
            <img data-image-fallback="{{ asset('images/image-placeholder.svg') }}"
              src="{{ $firstBanner->imageUrl('desktop') }}"
              alt="banner-0"
              loading="eager"
              class="w-full h-full object-cover select-none pointer-events-auto
                     transition-transform duration-700 ease-out will-change-transform"
              :data-image-candidates="JSON.stringify(slides[active].candidates[screen <= 768 ? 'mobile' : screen <= 1280 ? 'tablet' : 'desktop'])" :src="srcFor(slides[active])"
              :alt="`banner-${active}`"
              :loading="active === 0 ? 'eager' : 'lazy'"
            >
          </picture>
        </a>

        <button 
          @click="prev()" 
          class="hidden sm:flex absolute left-2 top-1/2 -translate-y-1/2 items-center justify-center 
                 w-9 h-9 rounded-full bg-black/40 text-white text-xl banner-control-arrow z-10
                 transition hover:bg-black/70 focus:outline-none backdrop-blur-sm"
          aria-label="Предыдущий баннер"
        >
          <span aria-hidden="true" class="-mt-0.5">&lsaquo;</span>
        </button>

        <button 
          @click="next()" 
          class="hidden sm:flex absolute right-2 top-1/2 -translate-y-1/2 items-center justify-center 
                 w-9 h-9 rounded-full bg-black/40 text-white text-xl banner-control-arrow z-10
                 transition hover:bg-black/70 focus:outline-none backdrop-blur-sm"
          aria-label="Следующий баннер"
        >
          <span aria-hidden="true" class="-mt-0.5">&rsaquo;</span>
        </button>

        <div class="absolute bottom-[11px] inset-x-0 mx-auto z-10 flex w-max flex-wrap justify-center max-w-[calc(100%-16px)] gap-0 px-3 rounded-xl bg-black/30 backdrop-blur-sm">
          <template x-for="(slide, index) in slides" :key="index">
            <button type="button"
              @click="active = index"
              class="banner-control-dot"
              :aria-label="`Перейти к слайду ${index + 1}`"
            >
              <span aria-hidden="true" class="block w-2.5 h-2.5 rounded-full transition-all duration-200"
                    :class="active === index ? 'bg-white scale-[1.15]' : 'bg-white/60'"></span>
            </button>
          </template>
        </div>
      </div>
      @else
        <div class="absolute inset-0 rounded-b-2xl bg-gradient-to-r from-indigo-500 via-indigo-600 to-slate-900"></div>
      @endif
    </div>
  </div>

  {{-- 📦 Основной контент --}}
  <div class="max-w-[90rem] mx-auto px-2 sm:px-4 lg:px-6 mt-6 mb-12">
    @php
      $featuredProductAds = ($homeAdCampaigns ?? collect())->get(\App\Models\AdSlot::HOME_FEATURED_PRODUCTS, collect());
      $weeklyShopAds = ($homeAdCampaigns ?? collect())->get(\App\Models\AdSlot::HOME_WEEKLY_SHOPS, collect());
      $weeklyShopCampaigns = $weeklyShopAds
          ->filter(fn ($campaign) => $campaign->shop || $campaign->target_type === \App\Models\AdCampaign::TYPE_CUSTOM)
          ->take(8)
          ->values();
      $recommendedFallbackProducts = $recommendedFallbackProducts ?? collect();
      $recommendedCatalogProducts = $recommendedCatalogProducts ?? collect();
      $recommendedProductLimit = $recommendedProductLimit ?? 16;
      $recommendedFeaturedProductAds = $featuredProductAds->take($recommendedProductLimit);
      $recommendedProductsCount = $recommendedFeaturedProductAds->count() + $recommendedFallbackProducts->count() + $recommendedCatalogProducts->count();
      $recommendedVisibleLimit = 6;
      $hasRecommendedProducts = $recommendedProductsCount > 0;
    @endphp

    @if($hasRecommendedProducts || $weeklyShopAds->isNotEmpty())
      <section class="mb-8 space-y-6">
        @if($hasRecommendedProducts)
          <div class="space-y-3" x-data="recommendedCarousel" role="region" aria-label="Рекомендуемые товары">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-bold text-slate-950 sm:text-xl">Рекомендуемые товары</h2>
                <p class="text-sm text-slate-500">Подборка WebVitrina и товары с высоким рейтингом</p>
              </div>
              <div class="recommended-carousel-controls flex w-full flex-wrap items-center gap-2 sm:w-auto">
                @if($recommendedProductsCount > $recommendedVisibleLimit)
                  <a href="{{ route('recommendations.index') }}"
                     class="order-last ml-auto inline-flex min-h-9 items-center gap-1 rounded-lg text-xs font-bold text-indigo-700 transition-colors hover:text-indigo-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 sm:order-none sm:ml-0 sm:gap-2 sm:rounded-xl sm:border sm:border-indigo-100 sm:bg-white sm:px-3 sm:shadow-sm sm:hover:bg-indigo-50">
                    Все рекомендации
                    <i class="ri-arrow-right-line"></i>
                  </a>
                @endif
                <span class="rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">Подборка</span>
                <button type="button" class="text-xs font-bold" x-cloak @click="togglePause()" :aria-pressed="paused" x-show="!reduced" x-text="paused ? 'Возобновить' : 'Пауза'" aria-label="Пауза автоматической прокрутки">Пауза</button>
              </div>
            </div>

            <div class="recommended-carousel-viewport">
              <button type="button" class="recommended-carousel-arrow recommended-carousel-arrow--previous" @click="scroll(-1)" aria-label="Предыдущие товары" aria-controls="recommended-track" x-cloak>
                <i class="ri-arrow-left-s-line" aria-hidden="true"></i>
              </button>
              <button type="button" class="recommended-carousel-arrow recommended-carousel-arrow--next" @click="scroll(1)" aria-label="Следующие товары" aria-controls="recommended-track" x-cloak>
                <i class="ri-arrow-right-s-line" aria-hidden="true"></i>
              </button>
            <div id="recommended-track" x-ref="track" class="recommended-carousel-track" tabindex="0" aria-label="Лента рекомендуемых товаров">
              @foreach($recommendedFeaturedProductAds as $campaign)
                @if($campaign->product)
                  <div>
                    <x-product-card :p="$campaign->product" :promoted="true" :promotion-label="$campaign->label" />
                  </div>
                @elseif($campaign->target_type === \App\Models\AdCampaign::TYPE_CUSTOM)
                  <div>
                    <a href="{{ $campaign->resolved_url }}" class="group flex min-h-[220px] flex-col justify-between rounded-2xl border border-indigo-100 bg-indigo-50 p-4 transition hover:border-indigo-200 hover:bg-indigo-100">
                      <span class="w-fit rounded-full bg-white px-2.5 py-1 text-xs font-bold text-indigo-700">{{ $campaign->label }}</span>
                      <span class="mt-6 text-base font-bold text-slate-950">{{ $campaign->title }}</span>
                      @if($campaign->description)
                        <span class="mt-2 text-sm text-slate-600">{{ \Illuminate\Support\Str::limit($campaign->description, 90) }}</span>
                      @endif
                      <span class="mt-5 inline-flex items-center gap-1 text-sm font-bold text-indigo-700">Открыть <i class="ri-arrow-right-line"></i></span>
                    </a>
                  </div>
                @endif
              @endforeach
              @foreach($recommendedFallbackProducts as $product)
                <div>
                  <x-product-card :p="$product" :promoted="true" promotion-label="Высокий рейтинг" />
                </div>
              @endforeach
              @foreach($recommendedCatalogProducts as $product)
                <div>
                  <x-product-card :p="$product" />
                </div>
              @endforeach
            </div>
            </div>

          </div>
        @endif

        @if($weeklyShopCampaigns->isNotEmpty())
          <div class="space-y-3">
            <div class="flex items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-bold text-slate-950 sm:text-xl">Магазины недели</h2>

              </div>
              <span class="shrink-0 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-bold text-slate-600">Партнёрский блок</span>
            </div>

            @if($weeklyShopCampaigns->count() <= 2)
              <div class="grid gap-3 {{ $weeklyShopCampaigns->count() === 2 ? 'lg:grid-cols-2' : '' }}">
                @foreach($weeklyShopCampaigns as $campaign)
                  @php
                    $shop = $campaign->shop;
                    $cover = $shop?->banner_url ?? null;
                    $title = $shop?->name ?? $campaign->title;
                    $description = $shop
                        ? strip_tags($shop->description ?: $campaign->description ?: 'Магазин продавца WebVitrina')
                        : ($campaign->description ?: 'Партнёрский блок WebVitrina');
                  @endphp
                  <a href="{{ $campaign->resolved_url }}"
                     class="group relative min-h-[170px] overflow-hidden rounded-2xl border border-slate-200 bg-slate-900 p-5 text-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    @if($cover)
                      <img data-image-fallback="{{ asset('images/image-placeholder.svg') }}" src="{{ $cover }}" alt="{{ $title }}" class="absolute inset-0 h-full w-full object-cover opacity-80 transition duration-500 group-hover:scale-[1.03]">
                    @endif
                    <span class="absolute inset-0 bg-gradient-to-r from-slate-950/80 via-slate-950/35 to-slate-950/5"></span>
                    <span class="relative z-10 flex min-h-[130px] flex-col justify-end">
                      <span class="w-fit rounded-full bg-white/90 px-2.5 py-1 text-xs font-bold text-indigo-700">{{ $campaign->label }}</span>
                      <span class="mt-3 text-xl font-bold drop-shadow-sm">{{ $title }}</span>
                      <span class="mt-2 max-w-xl text-sm leading-5 text-white/85">{{ \Illuminate\Support\Str::limit($description, 110) }}</span>
                      <span class="mt-3 inline-flex items-center gap-1 text-sm font-bold text-white">В магазин <i class="ri-arrow-right-line"></i></span>
                    </span>
                  </a>
                @endforeach
              </div>
            @else
              <div class="weekly-shop-track relative -mx-2 overflow-hidden px-2 py-1">
                <div class="weekly-shop-marquee flex w-max gap-3">
                  @foreach([false, true] as $duplicate)
                    @foreach($weeklyShopCampaigns as $campaign)
                      @php
                        $shop = $campaign->shop;
                      @endphp
                      @if($shop)
                        <a href="{{ $campaign->resolved_url }}"
                           @if($duplicate) aria-hidden="true" tabindex="-1" @endif
                           class="group grid w-[280px] shrink-0 grid-cols-[76px_minmax(0,1fr)] gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-indigo-200 hover:shadow-md sm:w-[330px]">
                          <img data-image-candidates="{{ json_encode($shop->card_image_candidates) }}" data-image-fallback="{{ asset('images/image-placeholder.svg') }}" src="{{ $shop->card_image_url }}" alt="{{ $shop->name }}" class="h-[76px] w-[76px] rounded-xl object-cover">
                          <span class="min-w-0">
                            <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">{{ $campaign->label }}</span>
                            <span class="mt-2 block truncate text-base font-bold text-slate-950 group-hover:text-indigo-700">{{ $shop->name }}</span>
                            <span class="mt-1 block line-clamp-2 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit(strip_tags($shop->description ?: $campaign->description ?: 'Магазин продавца WebVitrina'), 82) }}</span>
                          </span>
                        </a>
                      @elseif($campaign->target_type === \App\Models\AdCampaign::TYPE_CUSTOM)
                        <a href="{{ $campaign->resolved_url }}"
                           @if($duplicate) aria-hidden="true" tabindex="-1" @endif
                           class="w-[280px] shrink-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-200 hover:shadow-md sm:w-[330px]">
                          <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">{{ $campaign->label }}</span>
                          <span class="mt-3 block truncate text-base font-bold text-slate-950">{{ $campaign->title }}</span>
                          <span class="mt-2 block line-clamp-2 text-sm text-slate-500">{{ \Illuminate\Support\Str::limit($campaign->description ?: 'Партнёрский блок WebVitrina', 90) }}</span>
                        </a>
                      @endif
                    @endforeach
                  @endforeach
                </div>
                <div class="pointer-events-none absolute inset-y-0 left-0 hidden w-12 bg-gradient-to-r from-slate-50 to-transparent sm:block"></div>
                <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-12 bg-gradient-to-l from-slate-50 to-transparent sm:block"></div>
              </div>
            @endif
          </div>
        @endif
      </section>
    @endif
    
    {{-- 🧭 Панель сортировки / info --}}
    <div class="mb-6">
      @php
        $currentSort = request('sort', 'popular');
        $showSellerCategoryButton = auth()->check() && auth()->user()->isSeller();
        $labels = [
            'popular'     => 'По популярности',
            'rating'      => 'По рейтингу',
            'price_asc'   => 'По возрастанию цены',
            'price_desc'  => 'По убыванию цены',
            'new'         => 'По новинкам',
            'benefit'     => 'Сначала выгодные',
        ];
      @endphp

      <div class="flex min-h-9 {{ $showSellerCategoryButton ? 'items-end sm:items-center' : 'items-center' }} justify-between gap-2 sm:gap-3">
        <div class="min-w-0">
          <h1 class="m-0 leading-none text-xl sm:text-2xl font-semibold text-gray-900">
            Каталог товаров
          </h1>

          @if($showSellerCategoryButton)
            <button type="button"
                    @click="open = true"
                    class="mt-2 inline-flex h-9 items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 active:bg-indigo-100 sm:hidden">
              <i class="ri-apps-2-line text-base" aria-hidden="true"></i>
              <span>Категории</span>
            </button>
          @endif
        </div>

        <div x-data="{ openSort: false }" class="relative shrink-0 sm:hidden">
          <button type="button"
                  @click="openSort = !openSort"
                  class="flex h-9 items-center gap-1.5 rounded-xl border border-indigo-100 bg-indigo-50 px-3 text-sm font-medium text-indigo-700">
            <span class="max-w-[128px] truncate">{{ $labels[$currentSort] ?? 'По популярности' }}</span>
            <svg class="h-4 w-4 shrink-0 transition-transform"
                 :class="{ 'rotate-180': openSort }"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <div x-show="openSort"
               x-cloak
               x-transition
               @click.away="openSort = false"
               class="absolute right-0 top-full z-30 mt-2 w-56 overflow-hidden rounded-2xl border border-gray-200 bg-white p-1 shadow-xl">
            @foreach($labels as $value => $label)
              <a href="?{{ http_build_query(request()->except('sort', 'page') + ['sort' => $value]) }}"
                 class="block rounded-xl px-3 py-2 text-sm transition-colors {{ $currentSort === $value ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                {{ $label }}
              </a>
            @endforeach
          </div>
        </div>

        <form method="GET" class="hidden h-9 shrink-0 items-center gap-2 text-sm sm:flex">
          @foreach(request()->except('sort', 'page') as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
          @endforeach

          <span class="hidden sm:inline text-gray-500">Сортировать:</span>
          <select
            name="sort"
            class="h-9 max-w-[155px] sm:max-w-none border-gray-200 rounded-lg text-sm leading-none pl-3 pr-8 focus:ring-indigo-500 focus:border-indigo-500"
            onchange="this.form.submit()"
          >
            @foreach($labels as $value => $label)
              <option value="{{ $value }}" @selected($currentSort === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </form>
      </div>
    </div>

    <div data-load-more-root="home-products">
      {{-- 🎯 Сетка карточек — 6 колонок максимум (чтобы карточки не были слишком узкими) --}}
      <div id="products-grid"
           data-load-more-grid
           class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-5 gap-2 sm:gap-3 lg:gap-4">
        @forelse($products as $index => $p)
          <div
            data-load-more-item
            class="fade-card"
            style="--delay-index: {{ $index }}"
          >
            <x-product-card :p="$p" />
          </div>
        @empty
          <div class="col-span-2 sm:col-span-3 md:col-span-4 lg:col-span-5 xl:col-span-6 text-center text-gray-500 py-16">
            Товаров пока нет. Попробуйте изменить фильтры.
          </div>
        @endforelse
      </div>

      @include('partials.load-more', ['paginator' => $products])
    </div>
  </div>
</x-app-layout>

{{-- ⚙️ Анимации карточек и баннера --}}
<style>
  [x-cloak] { display: none !important; }

  /* Scale only the visual dot; adjacent real button boxes never overlap. */
  .banner-control-dot {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 24px;
    width: 24px;
    height: 24px;
    padding: 0;
    border: 0;
    background: transparent;
  }
  .banner-control-dot:hover > span { background: rgb(255 255 255 / 90%); }
  .banner-control-dot:focus-visible { outline: 2px solid white; outline-offset: -2px; }
  .banner-control-arrow::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
  }
  .banner-control-arrow::after { width: 44px; height: 44px; }

  /* ✅ Адаптивный aspect-ratio: чуть ниже, чтобы главная быстрее показывала каталог */
  .banner-container {
    aspect-ratio: 18/9;
  }
  
  @media (min-width: 640px) {
    .banner-container {
      aspect-ratio: 24/9;
    }
  }
  
  @media (min-width: 1280px) {
    .banner-container {
      aspect-ratio: 30/9;
    }
  }

  .fade-card {
    opacity: 0;
    transform: translateY(12px);
    transition:
      opacity 0.45s ease-out,
      transform 0.45s ease-out;
    transition-delay: calc(var(--delay-index, 0) * 40ms);
    will-change: opacity, transform;
  }

  .fade-card.visible {
    opacity: 1;
    transform: translateY(0);
  }

  .fade-in {
    opacity: 0;
    transform: translateY(8px);
    animation: fadeInBlock .5s ease-out forwards;
  }

  .weekly-shop-track {
    scrollbar-width: none;
  }

  .weekly-shop-track::-webkit-scrollbar {
    display: none;
  }

  .weekly-shop-marquee {
    animation: weeklyShopMarquee 28s linear infinite;
    will-change: transform;
  }

  .weekly-shop-track:hover .weekly-shop-marquee {
    animation-play-state: paused;
  }

  @keyframes weeklyShopMarquee {
    from { transform: translateX(0); }
    to { transform: translateX(calc(-50% - 0.375rem)); }
  }

  @media (prefers-reduced-motion: reduce) {
    .weekly-shop-marquee {
      animation: none;
    }

    .weekly-shop-track {
      overflow-x: auto;
    }
  }

  @keyframes fadeInBlock {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.fade-card');

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
          }
        });
      }, {
        threshold: 0.15,
        rootMargin: '50px'
      });

      cards.forEach(card => observer.observe(card));
    } else {
      cards.forEach(card => card.classList.add('visible'));
    }
  });
</script>
