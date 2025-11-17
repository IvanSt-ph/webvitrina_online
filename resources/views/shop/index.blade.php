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
 * ✅ Функция fallback для изображений
 */
function bannerImage($banner) {
    if ($banner->image_desktop) return asset('storage/'.$banner->image_desktop);
    if ($banner->image_tablet)  return asset('storage/'.$banner->image_tablet);
    if ($banner->image_mobile)  return asset('storage/'.$banner->image_mobile);
    return asset('storage/banners/sale1.jpg');
}

$firstBanner = $bannerItems->first();
$firstImage = $firstBanner ? bannerImage($firstBanner) : asset('storage/banners/sale1.jpg');
@endphp

{{-- ✅ Предзагрузка первого баннера для ускорения --}}
<link rel="preload" as="image" href="{{ $firstImage }}">

<x-app-layout title="Каталог">

  {{-- 🚀 Адаптивный баннер с плавной сменой изображений --}}
  <div class="relative w-full flex justify-center bg-transparent min-h-[260px] sm:min-h-[340px] md:min-h-[380px] lg:min-h-[420px]">
    <div 
      class="w-[94%] max-w-[1600px] overflow-hidden rounded-b-2xl relative
             aspect-[3.4/1] sm:aspect-[2.8/1] md:aspect-[2.5/1] lg:aspect-[3.2/1]
             opacity-0 translate-y-3 animate-[fadeBannerIn_0.9s_ease-out_forwards]"
    >
      @if($bannerItems->isNotEmpty())
      <div 
        x-data="{
          active: 0,
          timer: null,
          paused: false,
          screen: window.innerWidth,
          slides: @js($bannerItems->map(fn($b) => [
              'desktop' => $b->image_desktop ? asset('storage/'.$b->image_desktop) : asset('storage/banners/sale1.jpg'),
              'tablet'  => $b->image_tablet  ? asset('storage/'.$b->image_tablet)  : asset('storage/banners/sale1.jpg'),
              'mobile'  => $b->image_mobile  ? asset('storage/'.$b->image_mobile)  : asset('storage/banners/sale1.jpg'),
              'link'    => $b->link ?: '#',
          ])),
          next() { this.active = (this.active + 1) % this.slides.length },
          prev() { this.active = (this.active - 1 + this.slides.length) % this.slides.length },
          start() { 
            this.timer = setInterval(() => { 
              if (!this.paused) this.next() 
            }, 6000) 
          },
          srcFor(slide) {
            if (this.screen <= 768) return slide.mobile ?? slide.tablet ?? slide.desktop;
            if (this.screen <= 1280) return slide.tablet ?? slide.desktop;
            return slide.desktop;
          }
        }"
        x-init="
          start();
          window.addEventListener('resize', () => { screen = window.innerWidth });
        "
        x-cloak
        @mouseenter="paused = true"
        @mouseleave="paused = false"
        class="absolute inset-0 z-[1] overflow-hidden rounded-b-2xl bg-gray-100"
      >
        <!-- ✅ Один слайд как фон -->
        <a 
          :href="slides[active].link" 
          class="banner-bg block w-full h-full"
        >
          <img
            class="w-full h-full object-cover select-none pointer-events-auto
                   transition-transform duration-700 ease-out will-change-transform"
            :src="srcFor(slides[active])"
            :alt="`banner-${active}`"
            loading="lazy"
          >
        </a>

        <!-- ◀▶ Стрелки -->
        <button 
          @click="prev()" 
          class="hidden sm:flex absolute left-2 top-1/2 -translate-y-1/2 items-center justify-center 
                 w-9 h-9 rounded-full bg-black/40 text-white text-xl
                 transition hover:bg-black/70 focus:outline-none backdrop-blur-sm"
          aria-label="Предыдущий баннер"
        >
          <span class="-mt-0.5">&lsaquo;</span>
        </button>

        <button 
          @click="next()" 
          class="hidden sm:flex absolute right-2 top-1/2 -translate-y-1/2 items-center justify-center 
                 w-9 h-9 rounded-full bg-black/40 text-white text-xl
                 transition hover:bg-black/70 focus:outline-none backdrop-blur-sm"
          aria-label="Следующий баннер"
        >
          <span class="-mt-0.5">&rsaquo;</span>
        </button>

        <!-- ⚪ Индикаторы -->
        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5 px-3 py-1.5 rounded-full bg-black/30 backdrop-blur-sm">
          <template x-for="(slide, index) in slides" :key="index">
            <button 
              @click="active = index"
              class="w-2.5 h-2.5 rounded-full transition-all duration-200"
              :class="active === index ? 'bg-white scale-[1.15]' : 'bg-white/60 hover:bg-white/90'"></button>
          </template>
        </div>
      </div>
      @else
        {{-- Фолбек если баннеров нет --}}
        <div class="absolute inset-0 rounded-b-2xl bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
      @endif
    </div>
  </div>

  {{-- 🧭 Панель сортировки / info (по желанию) --}}
  <div class="max-w-7xl mx-auto px-4 lg:px-6 mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">
        Каталог товаров
      </h1>

    </div>

    {{-- Пример блока сортировки (если у тебя уже есть – можешь заменить своим) --}}
    <form method="GET" class="flex items-center gap-2 text-sm">
      @foreach(request()->except('sort', 'page') as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
      @endforeach

      <span class="hidden sm:inline text-gray-500">Сортировать:</span>
      <select 
        name="sort" 
        class="border-gray-200 rounded-lg text-sm py-1.5 pl-3 pr-8 focus:ring-indigo-500 focus:border-indigo-500"
        onchange="this.form.submit()"
      >
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
        @foreach($labels as $value => $label)
          <option value="{{ $value }}" @selected($currentSort === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </form>
  </div>

  {{-- 📦 Основной контент --}}
  <div class="max-w-[90rem] mx-auto px-4 lg:px-6 mt-6 mb-12">

    {{-- Сетка карточек --}}
<div   id="products-grid"  class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-[repeat(5,minmax(0,1fr))] gap-4 sm:gap-5 lg:gap-6">
      @forelse($products as $index => $p)
        <div 
          class="fade-card"
          style="--delay-index: {{ $index }}"
        >
          <x-product-card :p="$p" />
        </div>
      @empty
        <div class="col-span-2 sm:col-span-3 lg:col-span-4 text-center text-gray-500 py-16">
          Товаров пока нет. Попробуйте изменить фильтры.
        </div>
      @endforelse
    </div>

    {{-- Пагинация --}}
    @if($products->hasPages())
      <div class="mt-10 flex justify-center fade-in">
        {{ $products->withQueryString()->links() }}
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
        threshold: 0.15
      });

      cards.forEach(card => observer.observe(card));
    } else {
      // Фолбек для старых браузеров
      cards.forEach(card => card.classList.add('visible'));
    }
  });
</script>
