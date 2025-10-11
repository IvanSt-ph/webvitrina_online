{{-- Главная / Каталог с баннерами и сортировкой --}}

@php
use App\Models\Banner;

$bannerItems = cache()->remember('slides_home', 300, function () {
    return Banner::where('active', true)
        ->orderBy('sort_order')
        ->get(['image', 'link']);
});

$firstBanner = $bannerItems->first();
$firstImage = $firstBanner
    ? asset('storage/'.$firstBanner->image)
    : asset('storage/banners/sale1.jpg'); // запасной баннер
@endphp


<x-app-layout title="Каталог">

  {{-- 🚀 Широкий баннер почти на всю ширину (на уровне хедера) --}}
  <div class="relative w-[92vw] max-w-[1650px] mx-auto h-[220px] sm:h-[380px] lg:h-[480px] 
              overflow-hidden rounded-b-2xl shadow-lg shadow-gray-200/50 -mt-[0rem] z-[0]">

    {{-- ✅ Первый баннер как фон --}}
    <img 
      src="{{ $firstImage }}" 
      alt="Баннер"
      class="absolute inset-0 w-full h-full object-cover"
    >

    {{-- ⚡ Alpine.js слайдер --}}
    @if($bannerItems->isNotEmpty())
    <div 
      x-data="{
        active: 0,
        timer: null,
        paused: false,
        direction: 1,
        slides: @js($bannerItems->map(fn($b) => [
            'img'  => asset('storage/'.$b->image),
            'link' => $b->link ?: '#',
        ])),
        next() { this.direction = 1; this.active = (this.active + 1) % this.slides.length },
        prev() { this.direction = -1; this.active = (this.active - 1 + this.slides.length) % this.slides.length },
        start() { this.timer = setInterval(() => { if(!this.paused) this.next() }, 5000) }
      }"
      x-init="start()"
      @mouseenter="paused = true"
      @mouseleave="paused = false"
      class="absolute inset-0 z-[1]"
    >

      <!-- Слайды -->
      <template x-for="(slide, index) in slides" :key="index">
        <a 
          :href="slide.link"
          class="absolute inset-0 transition-all duration-700 ease-in-out"
          x-show="active === index"
          x-transition:enter="transform opacity ease-in-out duration-700"
          x-transition:enter-start="opacity-0 translate-x-[calc(var(--dir)*10%)]"
          x-transition:enter-end="opacity-100 translate-x-0"
          x-transition:leave="transform opacity ease-in-out duration-700"
          x-transition:leave-start="opacity-100 translate-x-0"
          x-transition:leave-end="opacity-0 translate-x-[calc(var(--dir)*-10%)]"
          :style="`--dir:${direction}`"
        >
          <img 
            :src="slide.img" 
            alt=""
            class="w-full h-full object-cover transition-transform duration-700 ease-in-out scale-105 hover:scale-110"
          >

          {{-- затемнение краёв --}}
          <div class="absolute inset-y-0 left-0 w-28 bg-gradient-to-r from-black/20 via-transparent to-transparent pointer-events-none"></div>
          <div class="absolute inset-y-0 right-0 w-28 bg-gradient-to-l from-black/20 via-transparent to-transparent pointer-events-none"></div>
        </a>
      </template>

      <!-- Индикаторы -->
      <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
        <template x-for="(slide, index) in slides" :key="index">
          <button 
            @click="active = index"
            class="w-3 h-3 rounded-full transition"
            :class="active === index ? 'bg-white' : 'bg-white/50 hover:bg-white/70'">
          </button>
        </template>
      </div>

      <!-- ◀ Стрелки ▶ -->
      <button 
        @click="prev()" 
        class="absolute left-0 top-0 bottom-0 flex items-center px-4 text-white text-6xl font-light transition-all duration-300 hover:opacity-100 opacity-70 hover:-translate-x-1"
      >
        ‹
      </button>

      <button 
        @click="next()" 
        class="absolute right-0 top-0 bottom-0 flex items-center px-4 text-white text-6xl font-light transition-all duration-300 hover:opacity-100 opacity-70 hover:translate-x-1"
      >
        ›
      </button>
    </div>
    @else
      <div class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm">
        Нет активных баннеров
      </div>
    @endif
  </div>

  {{-- 📦 Основной контент --}}
  <div class="max-w-7xl mx-auto px-4 lg:px-6 mt-12">

    @php
        $keep = request()->except(['page','sort']);
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

    <!-- Сортировка -->
    <div class="flex justify-end mb-10 fade-in">
      <div x-data="{ open: false }" class="relative inline-block text-left">
        <button @click="open = !open"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm border border-gray-300 rounded-lg bg-white text-gray-700 hover:bg-gray-50 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 4h18M3 12h18M3 20h18"/>
          </svg>
          {{ $labels[$currentSort] ?? 'Сортировка' }}
          <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div x-show="open" @click.away="open = false" x-cloak
             class="absolute right-0 mt-2 w-56 bg-white border rounded-xl shadow-lg z-50 animate-fadeIn">
          <form method="GET" action="{{ url()->current() }}" class="p-2 space-y-1">
            @foreach($keep as $key => $value)
              <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach

            @foreach($labels as $value => $label)
              <label class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 cursor-pointer">
                <input type="radio" name="sort" value="{{ $value }}"
                       onchange="this.form.submit()"
                       @checked($currentSort === $value)
                       class="text-indigo-600 focus:ring-indigo-500 border-gray-300">
                <span>{{ $label }}</span>
              </label>
            @endforeach
          </form>
        </div>
      </div>
    </div>

    <!-- Каталог карточек -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-8">
      @foreach($products as $p)
        <div class="fade-card">
          <x-product-card :p="$p" />
        </div>
      @endforeach
    </div>

    <!-- Пагинация -->
    <div class="mt-12 fade-in">
      {{ $products->withQueryString()->links() }}
    </div>

  </div>
</x-app-layout>


{{-- ⚙️ Анимации карточек --}}
<style>
[x-cloak] { display: block !important; }

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
