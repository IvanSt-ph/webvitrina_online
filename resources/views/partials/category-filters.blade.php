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

<!-- 🌸 Верхняя панель фильтров -->
<div x-data="{ openFilters: false }" class="max-w-7xl mx-auto px-4 lg:px-6 mt-8 mb-6">
  <div class="flex flex-wrap items-center gap-2 md:gap-3 text-sm">

    <!-- 🟣 Распродажа -->
    <label x-data="{ active: false }" @click="active = !active"
           class="flex items-center gap-3 px-4 py-2 bg-white border border-gray-200 rounded-[15px] shadow-sm cursor-pointer select-none hover:border-purple-400 hover:bg-purple-50 transition-all">
      <span class="text-purple-600 font-semibold">РАСПРОДАЖА</span>
      <div class="relative w-10 h-5 bg-gray-300 rounded-full overflow-hidden transition-all duration-300"
           :class="active ? 'bg-purple-500' : 'bg-gray-300'">
        <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-all duration-300"
             :style="active ? 'transform: translateX(20px)' : 'transform: translateX(0)'" ></div>
      </div>
    </label>

    <!-- 🔽 Сортировка -->
    <div x-data="{ open: false }" class="relative">
      <button @click="open = !open"
              class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-[15px] shadow-sm hover:border-indigo-400 hover:bg-indigo-50 transition text-sm text-gray-700">
        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M3 12h18M3 20h18"/></svg>
        <span>{{ $labels[$currentSort] ?? 'Сортировка' }}</span>
        <svg class="w-4 h-4 text-gray-500 transition-transform duration-200"
             :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none"
             viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 9l-7 7-7-7" />
        </svg>
      </button>

      <!-- Выпадающий список -->
      <div x-show="open" @click.away="open = false" x-transition x-cloak
           class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-[15px] shadow-lg z-50">
        <form method="GET" action="{{ url()->current() }}" class="p-2 space-y-1">
          @foreach($keep as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
          @endforeach
          @foreach($labels as $value => $label)
            <label class="flex items-center justify-between gap-2 px-3 py-2 rounded-[12px] hover:bg-gray-100 cursor-pointer">
              <span class="text-gray-700 text-sm">{{ $label }}</span>
              <input type="radio" name="sort" value="{{ $value }}"
                     onchange="this.form.submit()"
                     @checked($currentSort === $value)
                     class="text-indigo-600 focus:ring-indigo-500 border-gray-300">
            </label>
          @endforeach
        </form>
      </div>
    </div>

    <!-- Все фильтры -->
    <button @click="openFilters = true"
            class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-[15px] shadow-sm hover:border-indigo-400 hover:bg-indigo-50 transition">
      <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2"
           viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
           d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2H3V4zm0 8h20v2H3v-2zm0 8h20v2H3v-2z"/></svg>
      Все фильтры
    </button>
  </div>

  <!-- 🧭 Панель фильтров с выездом справа -->
  <div x-show="openFilters" x-cloak class="fixed inset-0 z-50 flex justify-end bg-black/10 backdrop-blur-[2px]">
    <div class="absolute inset-0 bg-black/10 backdrop-blur-[2px] transition-opacity duration-300"
         x-show="openFilters" x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="openFilters = false"></div>

    <div x-show="openFilters"
         x-transition:enter="transition-transform ease-out duration-400"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform ease-in duration-400"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="relative w-full max-w-sm bg-white h-full shadow-2xl shadow-gray-400/40 border-l border-gray-100 p-6 overflow-y-auto transform will-change-transform">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-gray-800">Фильтры</h2>
        <button @click="openFilters = false" class="text-gray-500 hover:text-black text-xl leading-none">✕</button>
      </div>

      <div class="space-y-6">
        <div>
          <h3 class="text-sm font-medium text-gray-700 mb-2">Категории</h3>
          <div class="space-y-1">
            <label class="flex items-center gap-2"><input type="checkbox" class="rounded text-indigo-600"> Кроссовки</label>
            <label class="flex items-center gap-2"><input type="checkbox" class="rounded text-indigo-600"> Сандалии</label>
            <label class="flex items-center gap-2"><input type="checkbox" class="rounded text-indigo-600"> Ботинки</label>
          </div>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-700 mb-2">Бренд</h3>
          <div class="grid grid-cols-2 gap-1">
            <label><input type="checkbox" class="mr-2 text-indigo-600 rounded"> Fila</label>
            <label><input type="checkbox" class="mr-2 text-indigo-600 rounded"> Adidas</label>
            <label><input type="checkbox" class="mr-2 text-indigo-600 rounded"> Nike</label>
            <label><input type="checkbox" class="mr-2 text-indigo-600 rounded"> Puma</label>
          </div>
        </div>
        <div>
          <h3 class="text-sm font-medium text-gray-700 mb-2">Рейтинг</h3>
          <input type="range" min="0" max="5" value="4" class="w-full accent-indigo-600">
          <p class="text-xs text-gray-500">от 4★ и выше</p>
        </div>
        <div class="pt-4 border-t">
          <button class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-[15px] font-medium transition">
            Показать товары
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
