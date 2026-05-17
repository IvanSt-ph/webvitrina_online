{{-- resources/views/shop/index.blade.php --}}
{{-- Главная / Каталог с баннерами и сортировкой --}}

@php
use App\Models\Banner;

/**
 * ✅ Кэш баннеров на 1 час (3600 сек)
 */
$bannerItems = cache()->remember('slides_home', 3600, function () {
    return Banner::where('active', true)
        ->orderBy('sort_order')
        ->get(['image_desktop', 'image_tablet', 'image_mobile', 'link']);
});

/**
 * ✅ Локальный helper для получения URL баннера.
 */
$bannerImageUrl = function ($banner, $default = 'storage/banners/sale1.jpg') {
    if (!$banner) return asset($default);
    $image = $banner->image_desktop ?? $banner->image_tablet ?? $banner->image_mobile;
    return $image ? asset('storage/'.$image) : asset($default);
};

$firstBanner = $bannerItems->first();
$firstImage = $bannerImageUrl($firstBanner);
@endphp

{{-- ✅ Предзагрузка первого баннера для ускорения --}}
<link rel="preload" as="image" href="{{ $firstImage }}">

<x-app-layout title="Каталог">

  {{-- 🚀 Адаптивный баннер с плавной сменой изображений --}}
  {{-- ⚠️ ВНИМАНИЕ: .banner-container не должен иметь padding, иначе absolute блок "поплывёт" --}}
  <div class="relative w-full flex justify-center bg-transparent">
    <div 
      class="w-[92%] max-w-[1440px] overflow-hidden rounded-b-2xl relative banner-container
             opacity-0 translate-y-3 animate-[fadeBannerIn_0.9s_ease-out_forwards]"
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
              'desktop' => $b->image_desktop ? asset('storage/'.$b->image_desktop) : asset('storage/banners/sale1.jpg'),
              'tablet'  => $b->image_tablet  ? asset('storage/'.$b->image_tablet)  : asset('storage/banners/sale1.jpg'),
              'mobile'  => $b->image_mobile  ? asset('storage/'.$b->image_mobile)  : asset('storage/banners/sale1.jpg'),
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
        x-init="init()"
        x-cloak
        @mouseenter="paused = true"
        @mouseleave="paused = false"
        class="absolute inset-0 z-[1] overflow-hidden rounded-b-2xl bg-gray-100"
      >
        <a 
          :href="slides[active].link" 
          class="banner-bg block w-full h-full"
        >
          <img
            class="w-full h-full object-cover select-none pointer-events-auto
                   transition-transform duration-700 ease-out will-change-transform"
            :src="srcFor(slides[active])"
            :alt="`banner-${active}`"
            :loading="active === 0 ? 'eager' : 'lazy'"
          >
        </a>

        <button 
          @click="prev()" 
          class="hidden sm:flex absolute left-2 top-1/2 -translate-y-1/2 items-center justify-center 
                 w-9 h-9 rounded-full bg-black/40 text-white text-xl
                 transition hover:bg-black/70 focus:outline-none backdrop-blur-sm"
          aria-label="Предыдущий баннер"
        >
          <span aria-hidden="true" class="-mt-0.5">&lsaquo;</span>
        </button>

        <button 
          @click="next()" 
          class="hidden sm:flex absolute right-2 top-1/2 -translate-y-1/2 items-center justify-center 
                 w-9 h-9 rounded-full bg-black/40 text-white text-xl
                 transition hover:bg-black/70 focus:outline-none backdrop-blur-sm"
          aria-label="Следующий баннер"
        >
          <span aria-hidden="true" class="-mt-0.5">&rsaquo;</span>
        </button>

        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 px-3 py-1.5 rounded-full bg-black/30 backdrop-blur-sm">
          <template x-for="(slide, index) in slides" :key="index">
            <button 
              @click="active = index"
              class="w-2.5 h-2.5 rounded-full transition-all duration-200"
              :class="active === index ? 'bg-white scale-[1.15]' : 'bg-white/60 hover:bg-white/90'"
              :aria-label="`Перейти к слайду ${index + 1}`"
            ></button>
          </template>
        </div>
      </div>
      @else
        <div class="absolute inset-0 rounded-b-2xl bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
      @endif
    </div>
  </div>

  {{-- 🧭 Панель сортировки / info --}}
  <div class="max-w-[90rem] mx-auto px-2 sm:px-4 lg:px-6 mt-6">
    @php
      $currentSort = request('sort', 'popular');
      $labels = [
          'popular'     => 'По популярности',
          'rating'      => 'По рейтингу',
          'price_asc'   => 'По возрастанию цены',
          'price_desc'  => 'По убыванию цены',
          'new'         => 'По новинкам',
          'benefit'     => 'Сначала выгодные',
      ];
    @endphp

    <div class="flex min-h-9 items-center justify-between gap-2 sm:gap-3">
      <div class="min-w-0">
        <h1 class="m-0 leading-none text-xl sm:text-2xl font-semibold text-gray-900">
          Каталог товаров
        </h1>
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

  {{-- 📦 Основной контент --}}
  <div class="max-w-[90rem] mx-auto px-2 sm:px-4 lg:px-6 mt-6 mb-12">
    
    {{-- 🎯 Сетка карточек — 6 колонок максимум (чтобы карточки не были слишком узкими) --}}
    <div id="products-grid" 
         class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-5 gap-2 sm:gap-3 lg:gap-4">
      @forelse($products as $index => $p)
        <div 
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

    {{-- Пагинация --}}
    @if($products->hasPages())
      <div class="mt-10 flex justify-center fade-in">
        {{ $products->withQueryString()->links('vendor.pagination.webvitrina') }}
      </div>
    @endif
  </div>
</x-app-layout>

{{-- ⚙️ Анимации карточек и баннера --}}
<style>
  [x-cloak] { display: none !important; }

  @keyframes fadeBannerIn {
    0% { opacity: 0; transform: translateY(10px); }
    100% { opacity: 1; transform: translateY(0); }
  }

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
