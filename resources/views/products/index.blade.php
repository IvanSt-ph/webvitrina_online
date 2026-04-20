<x-app-layout title="{{ $category->name ?? 'Каталог' }}">
  <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">

{{-- 🧭 Хлебные крошки и фильтры закреплены --}}
<div class="sticky top-[65px] z-40 bg-white/95 backdrop-blur 
     supports-[backdrop-filter]:backdrop-blur-sm 
     border-b border-gray-100 py-0.5 mb-3 sm:mb-4">

  <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
    <x-breadcrumbs :items="$breadcrumbs" />
  </div>
</div>

{{-- 🌸 Панель фильтров --}}
@include('partials.category-filters')

    {{-- Заголовок категории --}}
    @if(isset($category))
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-800 mb-4 sm:mb-6 fade-in px-1">
        {{ $category->name }}
      </h1>
    @else
      <h1 class="text-xl sm:text-2xl font-semibold text-gray-800 mb-4 sm:mb-6 fade-in px-1">
        Каталог товаров
      </h1>
    @endif

    {{-- 📦 Контейнер для товаров (обновляется через AJAX) --}}
    <div id="products-container" class="products-container">
      @include('partials.products-grid', ['products' => $products])
    </div>

  </div>

  {{-- 🧃 Контейнер уведомлений --}}
  <div id="toast-container"
       class="fixed bottom-12 right-3 sm:right-5 flex flex-col gap-3 z-[9999] pointer-events-none">
  </div>
</x-app-layout>

{{-- ============================================================ --}}
{{-- 🎨 СТИЛИ --}}
{{-- ============================================================ --}}
<style>
/* 🌿 Эффект плавного появления карточек */
.fade-card {
  opacity: 0;
  transform: translateY(15px);
  transition: all 0.5s ease-out;
  will-change: opacity, transform;
}

.fade-card.visible {
  opacity: 1;
  transform: translateY(0);
}

.fade-in {
  opacity: 0;
  animation: fadeIn 0.5s ease-out forwards;
}

@keyframes fadeIn {
  to { opacity: 1; }
}
</style>

{{-- ============================================================ --}}
{{-- 📜 СКРИПТЫ --}}
{{-- ============================================================ --}}
<script src="{{ asset('js/catalog.js') }}"></script>