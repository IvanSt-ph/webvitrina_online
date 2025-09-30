@props(['category', 'activeCategoryId' => null])

@php
  $hasChildren = $category->children->isNotEmpty();
  $isActive = $activeCategoryId === $category->id;

  $keep = http_build_query(request()->except('page'));
  $link = route('category.show', $category->slug) . ($keep ? '?' . $keep : '');

  // Если иконка загружена → берём из storage, иначе показываем дефолт
  $iconUrl = $category->icon 
      ? asset('storage/' . $category->icon) 
      : asset('images/categories/default.png');
@endphp

<li>
  <div x-data="{ open: false }" class="my-1">
    <div class="flex items-center justify-between">
      @if($hasChildren)
        <button type="button"
                @click="open = !open"
                class="flex items-center flex-1 text-left py-2 px-3 rounded hover:bg-gray-100 {{ $isActive ? 'bg-indigo-100 font-semibold text-indigo-700' : '' }}">
          <img src="{{ $iconUrl }}" alt="{{ $category->name }}" class="w-5 h-5 mr-2 object-contain" />
          {{ $category->name }}
        </button>

        <a href="{{ $link }}"
           class="p-2 rounded hover:bg-gray-200 {{ $isActive ? 'text-indigo-600' : '' }}"
           title="Перейти в категорию {{ $category->name }}">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
          </svg>
        </a>
      @else
        <a href="{{ $link }}"
           class="flex items-center py-2 px-3 rounded hover:bg-gray-100 block {{ $isActive ? 'bg-indigo-100 font-semibold text-indigo-700' : '' }}">
          <img src="{{ $iconUrl }}" alt="{{ $category->name }}" class="w-5 h-5 mr-2 object-contain" />
          {{ $category->name }}
        </a>
      @endif
    </div>

    @if($hasChildren)
      <ul x-show="open" x-transition x-cloak class="ml-4 border-l pl-2">
        @foreach($category->children as $child)
          <x-category-item :category="$child" :activeCategoryId="$activeCategoryId" />
        @endforeach
      </ul>
    @endif
  </div>
</li>
