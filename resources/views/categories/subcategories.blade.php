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
    <h1 class="text-2xl font-bold text-gray-800 mb-8">{{ $category->name }}</h1>

    @if($category->children->count())
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
        @foreach($category->children as $child)
          <a href="{{ route('category.show', $child->slug) }}"
             class="group block bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-300">
            <div class="aspect-square bg-gray-50 flex items-center justify-center">
              @if($child->icon)
                <img src="{{ asset('storage/'.$child->icon) }}" alt="{{ $child->name }}"
                     class="w-24 h-24 object-contain transition-transform duration-300 group-hover:scale-110">
              @else
                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5"
                     viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M3 3h18v18H3z"/></svg>
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
    @else
      <p class="text-gray-500">Подкатегорий нет.</p>
    @endif
  </div>

</x-app-layout>
