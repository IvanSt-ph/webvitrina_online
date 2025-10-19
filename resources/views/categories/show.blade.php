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

  {{-- 🔹 Плитки подкатегорий --}}
  @if($category->children->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6 mb-10">
      @foreach($category->children as $child)
        <a href="{{ route('category.show', $child->slug) }}"
           class="group block bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-300">
          <div class="aspect-square bg-gray-50 flex items-center justify-center">

{{-- 🔸 Изображение для плитки --}}
@if(!empty($child->image))
    <img src="{{ url('storage/'.$child->image) }}"
         alt="{{ $child->name }}"
         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
         loading="lazy"
         onerror="this.src='/images/no-image.webp'">
@elseif(!empty($child->icon))
    {{-- Иконка только если нет изображения плитки --}}
    <img src="{{ url('storage/'.$child->icon) }}"
         alt="{{ $child->name }}"
         class="w-20 h-20 object-contain transition-transform duration-300 group-hover:scale-110 mx-auto opacity-60"
         loading="lazy"
         onerror="this.src='/images/no-image.webp'">
@else
    <svg class="w-10 h-10 text-gray-300 mx-auto" fill="none" stroke="currentColor" stroke-width="1.5"
         viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h18v18H3z"/>
    </svg>
@endif






          </div>
          <div class="p-4 text-center">
            <h2 class="text-sm font-medium text-gray-800 group-hover:text-indigo-600 transition">
              {{ $child->name }}
            </h2>
          </div>
        </a>
      @endforeach
    </div>
  @endif

  {{-- 🔹 Товары --}}
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
