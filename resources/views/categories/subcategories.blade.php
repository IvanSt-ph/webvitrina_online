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

          {{-- 🔹 Плитка изображения --}}
          <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden">

              {{-- 1️⃣ Если есть изображение --}}
              @if(!empty($child->image))
                  <picture>
                      <source srcset="{{ asset('storage/'.$child->image) }}" type="image/webp">
                      <img
                          src="{{ asset('storage/'.$child->image) }}"
                          alt="{{ $child->name }}"
                          class="w-full h-full object-cover opacity-0 transition-all duration-700 ease-out group-hover:scale-105"
                          loading="lazy"
                          decoding="async"
                          onload="this.style.opacity=1"
                          onerror="this.src='/images/no-image.webp'"
                      >
                  </picture>

              {{-- 2️⃣ Если нет изображения, но есть иконка --}}
              @elseif(!empty($child->icon))
                  <img
                      src="{{ asset('storage/'.$child->icon) }}"
                      alt="{{ $child->name }}"
                      class="w-20 h-20 object-contain opacity-60 transition-transform duration-500 group-hover:scale-110"
                      loading="lazy"
                      decoding="async"
                      onerror="this.src='/images/no-image.webp'">
              
              {{-- 3️⃣ Если ничего нет — заглушка --}}
              @else
                  <svg class="w-10 h-10 text-gray-300 mx-auto" fill="none" stroke="currentColor" stroke-width="1.5"
                       viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h18v18H3z"/>
                  </svg>
              @endif
          </div>

          {{-- 🔹 Название категории --}}
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
