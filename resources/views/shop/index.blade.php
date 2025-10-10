<x-app-layout title="Каталог">
  <div class="max-w-7xl mx-auto px-4 lg:px-6">

    {{-- 🔥 Рекламный баннер / слайдер --}}
    <div 
      x-data="{
        active: 0,
        timer: null,
        paused: false,
        direction: 1,
        slides: [
          { img: @js(asset('storage/banners/sale1.jpg')), link: '/products?sort=benefit' },
          { img: @js(asset('storage/banners/new.jpg')),  link: '/products?sort=new' },
          { img: @js(asset('storage/banners/hits.jpg')), link: '/products?sort=popular' }
        ],
        next() { this.direction = 1; this.active = (this.active + 1) % this.slides.length },
        prev() { this.direction = -1; this.active = (this.active - 1 + this.slides.length) % this.slides.length },
        start() { this.timer = setInterval(() => { if(!this.paused) this.next() }, 5000) }
      }"
      x-init="start()"
      @mouseenter="paused = true"
      @mouseleave="paused = false"
      class="relative w-full h-40 sm:h-72 lg:h-60 overflow-hidden rounded-2xl mt-6 mb-10 shadow-lg shadow-gray-200/50 slider-fade"
    >
      <!-- Слайды -->
      <template x-for="(slide, index) in slides" :key="index" x-cloak>
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
          <img :src="slide.img" alt="" 
               class="w-full h-full object-cover transition-transform duration-700 ease-in-out scale-105 hover:scale-110">
          
          <!-- боковые затемнения -->
          <div class="absolute inset-y-0 left-0 w-24 bg-gradient-to-r from-black/25 via-transparent to-transparent pointer-events-none"></div>
          <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-black/25 via-transparent to-transparent pointer-events-none"></div>
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
        class="absolute left-0 top-0 bottom-0 flex items-center px-4 text-white text-7xl font-light transition-all duration-300 hover:opacity-100 opacity-80 hover:-translate-x-1"
      >
        ‹
      </button>

      <button 
        @click="next()" 
        class="absolute right-0 top-0 bottom-0 flex items-center px-4 text-white text-7xl font-light transition-all duration-300 hover:opacity-100 opacity-80 hover:translate-x-1"
      >
        ›
      </button>
    </div>
    {{-- /слайдер --}}

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

  <!-- 🧃 Контейнер для уведомлений -->
  <div id="toast-container"
       class="fixed bottom-12 right-5 flex flex-col gap-3 z-[9999] pointer-events-none">
  </div>
</x-app-layout>





<style>
/* 🌈 Плавное появление слайдера */
.slider-fade {
  opacity: 0;
  transform: translateY(10px);
  animation: fadeInUp 0.8s ease-out forwards;
}

/* 🌿 Анимация для карточек */
.fade-card {
  opacity: 0;
  transform: translateY(15px);
  transition: all 0.8s ease-out;
  will-change: opacity, transform;
}

.fade-card.visible {
  opacity: 1;
  transform: translateY(0);
}

/* 🔹 Общие эффекты */
@keyframes fadeInUp {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('.fade-card');

  const showVisibleCards = () => {
    cards.forEach(card => {
      const rect = card.getBoundingClientRect();
      // если карточка видна хотя бы на 100px — показать её
      if (rect.top < window.innerHeight - 100) {
        card.classList.add('visible');
      }
    });
  };

  // Показать видимые карточки сразу при загрузке
  showVisibleCards();

  // Затем при прокрутке — остальные
  window.addEventListener('scroll', showVisibleCards, { passive: true });
});
</script>
