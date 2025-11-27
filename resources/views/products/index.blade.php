<x-app-layout title="{{ $category->name ?? 'Каталог' }}">
  <div class="max-w-7xl mx-auto px-4 lg:px-6">

{{-- 🧭 Хлебные крошки и фильтры закреплены --}}
<div class="sticky top-[65px] z-40 bg-white/95 backdrop-blur 
     supports-[backdrop-filter]:backdrop-blur-sm 
     border-b border-gray-100 py-0.5 mb-4">

  <div class="max-w-7xl mx-auto px-4 lg:px-6 ">
    <x-breadcrumbs :items="$breadcrumbs" />
  </div>
</div>


{{-- 🌸 Панель фильтров --}}
@include('partials.category-filters')






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
