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
  <div class="relative w-full flex justify-center bg-transparent min-h-[300px] sm:min-h-[360px] md:min-h-[420px] lg:min-h-[480px]">
    <div 
      class="w-[90%] max-w-[1920px] overflow-hidden rounded-b-2xl relative
             aspect-[3.84/1] sm:aspect-[2.8/1] md:aspect-[2.5/1] lg:aspect-[3.84/1]
             opacity-0 scale-[0.97] animate-[fadeZoomIn_1.6s_ease-out_forwards] [animation-delay:0.1s]"
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
          start() { this.timer = setInterval(() => { if(!this.paused) this.next() }, 5000) },
          srcFor(slide) {
            if (this.screen <= 768) return slide.mobile ?? slide.tablet ?? slide.desktop;
            if (this.screen <= 1280) return slide.tablet ?? slide.desktop;
            return slide.desktop;
          }
        }"
        x-init="
          start();
          window.addEventListener('resize', () => { screen = window.innerWidth });
          $watch('active', () => {
            const el = $el.querySelector('.banner-bg');
            el.classList.add('changing');
            setTimeout(() => el.classList.remove('changing'), 400);
          });
        "
        x-cloak
        @mouseenter="paused = true"
        @mouseleave="paused = false"
        class="absolute inset-0 z-[1] overflow-hidden rounded-b-2xl"
      >
        <!-- ✅ Один слайд -->
        <a 
          :href="slides[active].link" 
          class="banner-bg absolute inset-0 w-full h-full object-cover"
          :style="`background-image: url('${srcFor(slides[active])}'); background-size: cover; background-position: center;`"
        ></a>

        <!-- ◀▶ Стрелки -->
        <button 
          @click="prev()" 
          class="absolute left-0 top-0 bottom-0 flex items-center px-4 text-white text-6xl font-light 
                transition-all duration-300 hover:opacity-100 opacity-70 hover:-translate-x-1">‹</button>

        <button 
          @click="next()" 
          class="absolute right-0 top-0 bottom-0 flex items-center px-4 text-white text-6xl font-light 
                transition-all duration-300 hover:opacity-100 opacity-70 hover:translate-x-1">›</button>

        <!-- ⚪ Индикаторы -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
          <template x-for="(slide, index) in slides" :key="index">
            <button 
              @click="active = index"
              class="w-3 h-3 rounded-full transition"
              :class="active === index ? 'bg-white' : 'bg-white/50 hover:bg-white/70'"></button>
          </template>
        </div>
      </div>
      @endif

    </div>
  </div>

  {{-- ✨ Анимации и плавность --}}
  <style>
  [x-cloak] { display: none !important; }

  @keyframes fadeZoomIn {
    0% { opacity: 0; transform: scale(0.96); }
    100% { opacity: 1; transform: scale(1); }
  }

  .banner-bg {
    filter: blur(0px);
    opacity: 1;
    transform: scale(1);
    transition: transform 0.6s ease-in-out, opacity 0.8s ease-in-out, filter 0.6s ease-in-out;
    will-change: transform, opacity;
  }

  .banner-bg.changing {
    filter: blur(2px);
    opacity: 0.7;
  }

  .banner-bg:hover {
    transform: scale(1.01);
  }
  </style>

  {{-- 📦 Основной контент --}}
  <div class="max-w-7xl mx-auto px-4 lg:px-6 mt-12">
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-8">
      @foreach($products as $p)
        <div class="fade-card">
          <x-product-card :p="$p" />
        </div>
      @endforeach
    </div>

    <div class="mt-12 fade-in">
      {{ $products->withQueryString()->links() }}
    </div>
  </div>
</x-app-layout>

{{-- ⚙️ Анимации карточек --}}
<style>
.fade-card {
  opacity: 0;
  transform: translateY(15px);
  transition: all 0.8s ease-out;
}
.fade-card.visible {
  opacity: 1;
  transform: translateY(0);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('.fade-card');
  const showVisibleCards = () => {
    cards.forEach(card => {
      const rect = card.getBoundingClientRect();
      if (rect.top < window.innerHeight - 100) card.classList.add('visible');
    });
  };
  showVisibleCards();
  window.addEventListener('scroll', showVisibleCards, { passive: true });
});
</script>
