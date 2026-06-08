@props(['category', 'activeCategoryId' => null])

@php
  $hasChildren = $category->children->isNotEmpty();
  $isActive = (int) $activeCategoryId === (int) $category->id;
  $keep = http_build_query(request()->except('page'));
  $link = route('category.show', $category->slug) . ($keep ? '?' . $keep : '');
  $initial = mb_substr($category->name, 0, 1);
@endphp

<li x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
  <div class="group rounded-2xl border transition {{ $isActive ? 'border-indigo-200 bg-indigo-50' : 'border-transparent bg-white hover:border-slate-200 hover:bg-slate-50' }}">
    <div class="flex items-center gap-2 p-2">
      @if($hasChildren)
        <button type="button"
           @click="open = !open"
           class="flex min-w-0 flex-1 items-center gap-3 rounded-xl px-1 py-1.5 text-left">
      @else
        <a href="{{ $link }}"
           @click="$dispatch('category-menu-close')"
           class="flex min-w-0 flex-1 items-center gap-3 rounded-xl px-1 py-1.5">
      @endif
        <span data-category-media class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-50 via-white to-slate-100 text-sm font-black text-indigo-600 shadow-sm ring-1 ring-slate-100">
          @if(filled($category->icon))
            <img
              src="{{ $category->icon_url }}"
              alt="{{ $category->name }}"
              class="h-6 w-6 object-contain"
              loading="lazy"
              decoding="async"
              onerror="this.closest('[data-category-media]').classList.add('category-menu-media-failed'); this.remove();"
            >
          @elseif(filled($category->image))
            <img
              src="{{ $category->image_thumb_url }}"
              alt="{{ $category->name }}"
              class="h-full w-full object-cover"
              loading="lazy"
              decoding="async"
              onerror="this.closest('[data-category-media]').classList.add('category-menu-media-failed'); this.remove();"
            >
          @endif

          <span class="{{ (filled($category->icon) || filled($category->image)) ? 'hidden category-menu-fallback' : 'category-menu-fallback' }}">
            {{ $initial }}
          </span>
        </span>

        <span class="min-w-0">
          <span class="block truncate text-sm font-bold {{ $isActive ? 'text-indigo-800' : 'text-slate-800 group-hover:text-indigo-700' }}">
            {{ $category->name }}
          </span>
          @if($hasChildren)
            <span class="mt-0.5 block text-xs text-slate-400">{{ $category->children->count() }} подразделов</span>
          @else
            <span class="mt-0.5 block text-xs text-slate-400">Перейти к товарам</span>
          @endif
        </span>
      @if($hasChildren)
        </button>
      @else
        </a>
      @endif

      @if($hasChildren)
        <a href="{{ $link }}"
           @click="$dispatch('category-menu-close')"
           class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-white hover:text-indigo-600"
           title="Перейти в категорию {{ $category->name }}"
           aria-label="Перейти в категорию {{ $category->name }}">
          <i class="ri-arrow-right-line text-lg"></i>
        </a>
      @else
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-300 transition group-hover:text-indigo-500">
          <i class="ri-arrow-right-line"></i>
        </span>
      @endif
    </div>

    @if($hasChildren)
      <div x-show="open" x-transition x-cloak class="border-t border-slate-100 px-2 pb-2 pt-1">
        <ul class="space-y-1 pl-2">
          @foreach($category->children as $child)
            <x-category-item :category="$child" :activeCategoryId="$activeCategoryId" />
          @endforeach
        </ul>
      </div>
    @endif
  </div>
</li>

@once
  @push('styles')
    <style>
      .category-menu-media-failed .category-menu-fallback {
        display: inline !important;
      }
    </style>
  @endpush
@endonce
