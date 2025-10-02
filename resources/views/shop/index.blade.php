<x-app-layout title="Каталог">
  <div class="max-w-7xl mx-auto px-4 lg:px-6">

    <!-- Заголовок -->
    <h1 class="text-2xl font-bold mb-6">Каталог товаров</h1>

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
    <div class="flex justify-end mb-6">
      <div x-data="{ open: false }" class="relative inline-block text-left">
          <button type="button" @click="open = !open"
                  class="inline-flex items-center gap-2 px-4 py-2 border rounded-lg bg-white shadow-sm text-gray-700 hover:bg-gray-50 transition">
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

    <!-- Список товаров -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
      @foreach($products as $p)
        <div class="bg-white rounded-xl border p-3 flex flex-col shadow-sm hover:shadow-lg transition duration-200">
          
          <!-- Фото -->
          <a href="{{ route('product.show',$p) }}" 
             class="block w-full h-40 sm:h-48 bg-gray-50 rounded-lg mb-3 overflow-hidden flex items-center justify-center">
            @if($p->image)
              <img src="{{ asset('storage/'.$p->image) }}" 
                   alt="{{ $p->title }}"
                   class="max-h-full max-w-full object-contain transition-transform duration-300 hover:scale-105"/>
            @else
              <div class="text-gray-400 text-sm">Нет фото</div>
            @endif
          </a>

          <!-- Название -->
          <div class="font-medium text-sm sm:text-base text-gray-800 line-clamp-2 mb-2">
              {{ $p->title }}
          </div>

          <!-- Цена + кнопка -->
          <div class="mt-auto flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div class="text-base sm:text-lg font-bold text-gray-900">
                {{ number_format($p->price, 0, ',', ' ') }} ₽
            </div>
            <form method="post" action="{{ route('cart.add',$p) }}" class="w-full sm:w-auto">@csrf
              <button class="w-full sm:w-auto px-3 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                🛒 В корзину
              </button>
            </form>
          </div>
        </div>
      @endforeach
    </div>

    <!-- Пагинация -->
    <div class="mt-8">{{ $products->withQueryString()->links() }}</div>
  </div>
</x-app-layout>
