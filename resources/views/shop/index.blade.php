{{-- resources/views/products/index.blade.php --}}
<x-app-layout title="Каталог">
  <div class="max-w-7xl mx-auto px-4 lg:px-6">

    {{-- Рекламный баннер / слайдер --}}
<div 
  x-data="{
    active: 0,
    timer: null,
    paused: false,
    slides: [
      { img: @js(asset('storage/banners/sale1.jpg')), link: '/products?sort=benefit' },
      { img: @js(asset('storage/banners/new.jpg')),  link: '/products?sort=new' },
      { img: @js(asset('storage/banners/hits.jpg')), link: '/products?sort=popular' }
    ],
    next() { this.active = (this.active + 1) % this.slides.length },
    prev() { this.active = (this.active - 1 + this.slides.length) % this.slides.length },
    start() { this.timer = setInterval(() => { if(!this.paused) this.next() }, 5000) }
  }"
  x-init="start()"
  @mouseenter="paused = true"
  @mouseleave="paused = false"
  class="relative w-full h-40 sm:h-72 lg:h-60 overflow-hidden rounded-2xl mb-10 shadow-lg shadow-gray-200/50"
>
  <!-- Слайды -->
  <template x-for="(slide, index) in slides" :key="index" x-cloak>
    <a 
       :href="slide.link"
       class="absolute inset-0 transition-all duration-700 ease-in-out"
       x-show="active === index"
       x-transition.opacity
    >
      <img :src="slide.img" alt="" 
           class="w-full h-full object-cover transition-transform duration-700 ease-in-out scale-105 hover:scale-110">
      
      <!-- только боковые затемнения -->
      <div class="absolute inset-y-0 left-0 w-24 bg-gradient-to-r from-black/30 via-transparent to-transparent pointer-events-none"></div>
      <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-black/30 via-transparent to-transparent pointer-events-none"></div>
    </a>
  </template>

  <!-- Индикаторы -->
  <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2">
    <template x-for="(slide, index) in slides" :key="index">
      <button 
        @click="active = index"
        class="w-3 h-3 rounded-full transition"
        :class="active === index ? 'bg-white' : 'bg-white/50 hover:bg-white/70'">
      </button>
    </template>
  </div>

  <!-- Стрелки -->
  <button 
    @click="prev()" 
    class="absolute left-0 top-0 bottom-0 flex items-center px-4 text-white text-8xl font-light transition-opacity duration-300 hover:opacity-100 opacity-80"
  >
    ‹
  </button>

  <button 
    @click="next()" 
    class="absolute right-0 top-0 bottom-0 flex items-center px-4 text-white text-8xl font-light transition-opacity duration-300 hover:opacity-100 opacity-80"
  >
    ›
  </button>
</div>








    {{-- Рекламный баннер / слайдер / конец слайдера--}}
    
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
    <div class="flex justify-end mb-10">
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
        <x-product-card :p="$p" />
      @endforeach
    </div>

    <!-- Пагинация -->
    <div class="mt-12">
      {{ $products->withQueryString()->links() }}
    </div>

  </div>

  <!-- 🧃 Контейнер для уведомлений -->
  <div id="toast-container"
       class="fixed bottom-12 right-5 flex flex-col gap-3 z-[9999] pointer-events-none">
  </div>

</x-app-layout>

<style>
.slide-up {
  transform: translateY(10px);
  opacity: 0;
  transition: all 0.4s ease;
}
.group:hover .slide-up {
  transform: translateY(0);
  opacity: 1;
}

/* 🌈 Всплывающие уведомления */
.toast-item {
  position: relative;
  backdrop-filter: blur(8px);
  background-color: rgba(255,255,255,0.9);
}
.toast-item::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  width: 4px;
  background: linear-gradient(180deg, #74bdfd, #4090ee);
  border-top-left-radius: 12px;
  border-bottom-left-radius: 12px;
}
</style>
