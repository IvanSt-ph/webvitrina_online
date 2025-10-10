<x-app-layout title="{{ $category->name ?? 'Каталог' }}">
  <div class="max-w-7xl mx-auto px-4 lg:px-6">

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

    <!-- Заголовок категории -->
    @if(isset($category))
      <h1 class="text-2xl font-semibold text-gray-800 mb-6 fade-in">
        {{ $category->name }}
      </h1>
    @else
      <h1 class="text-2xl font-semibold text-gray-800 mb-6 fade-in">
        Каталог товаров
      </h1>
    @endif

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
      @forelse($products as $p)
        <div class="fade-card">
          <x-product-card :p="$p" />
        </div>
      @empty
        <p class="col-span-full text-gray-500 text-center py-20">Нет товаров в этой категории.</p>
      @endforelse
    </div>

    <!-- Пагинация -->
    <div class="mt-12 fade-in">
      {{ $products->withQueryString()->links() }}
    </div>

  </div>

  <!-- 🧃 Контейнер уведомлений -->
  <div id="toast-container"
       class="fixed bottom-12 right-5 flex flex-col gap-3 z-[9999] pointer-events-none">
  </div>
</x-app-layout>

<style>
/* 🌿 Эффект плавного появления карточек */
.fade-card {
  opacity: 0;
  transform: translateY(15px);
  transition: all 0.7s ease-out;
  will-change: opacity, transform;
}

.fade-card.visible {
  opacity: 1;
  transform: translateY(0);
}

.fade-in {
  opacity: 0;
  animation: fadeIn 0.6s ease-out forwards;
}

@keyframes fadeIn {
  to { opacity: 1; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('.fade-card');

  const showVisibleCards = () => {
    cards.forEach(card => {
      const rect = card.getBoundingClientRect();
      if (rect.top < window.innerHeight - 100) {
        card.classList.add('visible');
      }
    });
  };

  // Показываем при загрузке и при прокрутке
  showVisibleCards();
  window.addEventListener('scroll', showVisibleCards, { passive: true });
});
</script>
