<x-app-layout :title="$category->name">

{{-- 🧭 Хлебные крошки и фильтры закреплены --}}
<div class="sticky top-[65px] z-40 bg-white/95 backdrop-blur supports-[backdrop-filter]:backdrop-blur-sm border-b border-gray-100 py-2 mb-4">
  <div class="max-w-7xl mx-auto px-4 lg:px-6 space-y-2">
    <x-breadcrumbs :items="$breadcrumbs" />
  </div>
</div>

{{-- 🌸 Панель фильтров --}}
@include('partials.category-filters')



  <div class="max-w-7xl mx-auto px-4 lg:px-6 mt-10">
    <h1 class="text-2xl font-semibold text-gray-800 mb-6">{{ $category->name }}</h1>

    @if($products->count())
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-8">
        @foreach($products as $p)
          <x-product-card :p="$p" />
        @endforeach
      </div>

      <div class="mt-12">
        {{ $products->withQueryString()->links() }}
      </div>
    @else
      <p class="text-gray-500">В этой категории пока нет товаров.</p>
    @endif
  </div>
</x-app-layout>
