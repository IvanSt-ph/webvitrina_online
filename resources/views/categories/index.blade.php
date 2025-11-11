<x-app-layout title="Категории">

{{-- 🧭 Хлебные крошки и фильтры закреплены --}}
<div class="sticky top-[65px] z-40 bg-white/95 backdrop-blur supports-[backdrop-filter]:backdrop-blur-sm border-b border-gray-100 py-2 mb-4">
  <div class="max-w-7xl mx-auto px-4 lg:px-6 space-y-2">
    <x-breadcrumbs :items="$breadcrumbs" />
  </div>
</div>


<div class="max-w-7xl mx-auto px-4 lg:px-6 mt-10">
  <h1 class="text-2xl font-bold text-gray-800 mb-8">Все категории</h1>
  
{{-- 🌸 Панель фильтров --}}
@include('partials.category-filters')

  @if($categories->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
      @foreach($categories as $cat)
        <a href="{{ route('category.show', $cat->slug) }}"
           class="group block bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md hover:border-indigo-300 transition-all duration-300">

          {{-- 🔹 Плитка изображения --}}
          <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden">
              {{-- 1️⃣ Если есть изображение для плитки --}}
              @if(!empty($cat->image))
<picture>
    <source srcset="{{ asset('storage/'.$cat->image) }}" type="image/webp">
    <img
        src="{{ asset('storage/'.$cat->image) }}"
        alt="{{ $cat->name }}"
        class="w-full h-full object-cover opacity-0 transition-all duration-700 ease-out group-hover:scale-105"
        loading="lazy"
        decoding="async"
        onload="this.style.opacity=1"
        onerror="this.src='/images/no-image.webp'"
    >
</picture>

              {{-- 2️⃣ Если нет изображения, но есть иконка --}}
              @elseif(!empty($cat->icon))
                  <img src="{{ asset('storage/'.$cat->icon) }}"
                       alt="{{ $cat->name }}"
                       class="w-20 h-20 object-contain transition-transform duration-300 group-hover:scale-110 opacity-60"
                       loading="lazy"
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
              {{ $cat->name }}
            </h2>
          </div>

        </a>
      @endforeach
    </div>
  @else
    <p class="text-gray-500">Категории пока не добавлены.</p>
  @endif
</div>

</x-app-layout>
