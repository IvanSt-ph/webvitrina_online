<x-app-layout title="Каталог">
  <div class="max-w-7xl mx-auto px-4 lg:px-6">

    <h1 class="text-2xl font-semibold text-gray-800 mb-8 tracking-tight">Каталог товаров</h1>

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
        @php
          $avg = round($p->reviews->avg('rating'), 1);
          $isFav = auth()->check() && $p->isFavoritedBy(auth()->user());
          $city = $p->city->name ?? null;
          $country = $p->city->country->name ?? $p->country->name ?? null;
          $category = $p->category->name ?? null;
        @endphp

        <div class="relative bg-white rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 border border-gray-100 flex flex-col overflow-hidden group">

          <!-- Фото -->
          <div class="relative h-60 bg-gray-50 flex items-center justify-center overflow-hidden">
            @if($p->image)
              <img src="{{ asset('storage/'.$p->image) }}"
                   alt="{{ $p->title }}"
                   class="object-contain w-full h-full transition duration-500 ease-out group-hover:scale-105 group-hover:brightness-75 group-hover:blur-[1px]" />
            @else
              <span class="text-gray-400 text-sm">Нет фото</span>
            @endif

            <!-- Затемнение и надпись -->
            <div class="absolute inset-0 flex items-center justify-center">
              <a href="{{ route('product.show', $p) }}"
                 class="slide-up px-4 py-2 text-sm font-medium bg-white/90 text-gray-800 rounded-lg shadow hover:bg-white transition">
                Подробнее
              </a>
            </div>

            <!-- Плашка -->
            <div class="absolute bottom-0 left-0 right-0 bg-[#f1f8ff]/95 backdrop-blur-sm text-[13px] text-gray-600 py-2 px-3 flex flex-col items-start opacity-0 translate-y-full group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-500 ease-out">
              @if($city || $country)
                <div class="flex items-center gap-1">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 21c4.97-4.97 8-8.485 8-11.5A8 8 0 1 0 4 9.5c0 3.015 3.03 6.53 8 11.5z" />
                    <circle cx="12" cy="9.5" r="2.5" fill="currentColor"/>
                  </svg>
                  <span>{{ $city ?? '—' }}{{ $country ? ', '.$country : '' }}</span>
                </div>
              @endif
              @if($category)
                <div class="flex items-center gap-1 mt-0.5">
                  <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M4 6h16M4 12h16M4 18h16" />
                  </svg>
                  <span>{{ $category }}</span>
                </div>
              @endif
            </div>
          </div>

          <!-- Контент -->
          <div class="p-5 flex flex-col flex-1">
            <div class="text-lg font-semibold text-neutral-800 mb-1">
              {{ number_format($p->price, 0, ',', ' ') }} ₽
            </div>

            <h3 class="text-sm text-neutral-700 font-medium mb-2 line-clamp-2">
              {{ $p->title }}
            </h3>

            <p class="text-xs text-neutral-500 mb-3">
              {{ Str::limit($p->description, 60) }}
            </p>

            <!-- Рейтинг -->
            <div class="flex items-center gap-1 mb-4">
              @for($i = 1; $i <= 5; $i++)
                <svg xmlns="http://www.w3.org/2000/svg"
                     class="w-4 h-4 {{ $i <= $avg ? 'text-yellow-400' : 'text-gray-300' }}"
                     fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.729 
                           1.516 8.234L12 18.896l-7.452 4.373 
                           1.516-8.234L0 9.306l8.332-1.151z"/>
                </svg>
              @endfor
              <span class="text-xs text-gray-400 ml-1">{{ $avg > 0 ? $avg.'/5' : '—' }}</span>
            </div>

            <!-- Кнопки -->
            <div class="mt-auto flex items-center justify-between">
              <form method="post" action="{{ route('cart.add', $p) }}">
                @csrf
                <button type="submit"
                  class="px-4 py-2 text-sm font-medium bg-white border border-gray-300 rounded-lg hover:bg-[#f1f8ff] text-gray-800 active:scale-[0.98] transition">
                  В корзину
                </button>
              </form>

              <form method="post" action="{{ route('favorites.toggle', $p) }}">
                @csrf
                <button 
                  type="button"
                  x-data="{ active: {{ $isFav ? 'true' : 'false' }} }"
                  @click="active = !active; $el.closest('form').submit();"
                  class="p-2 transition duration-300 rounded-full hover:bg-[#f1f8ff]"
                  :class="active ? 'text-[#74bdfd] bg-[#f1f8ff]' : 'text-gray-400'"
>
                  <svg xmlns="http://www.w3.org/2000/svg" 
                       class="w-5 h-5 transition-transform duration-300"
                       :class="active ? 'scale-110' : 'scale-100'" 
                       fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 
                             2 12.28 2 8.5 2 5.42 4.42 3 
                             7.5 3c1.74 0 3.41 0.81 
                             4.5 2.09C13.09 3.81 
                             14.76 3 16.5 3 19.58 3 
                             22 5.42 22 8.5c0 3.78-3.4 
                             6.86-8.55 11.54L12 21.35z"/>
                  </svg>
                </button>
              </form>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <!-- Пагинация -->
    <div class="mt-12">{{ $products->withQueryString()->links() }}</div>
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
</style>
