@props(['category', 'activeCategoryId' => null])

@php
  $hasChildren = $category->children->isNotEmpty();
  $isActive = (int) $activeCategoryId === (int) $category->id;
  $keep = http_build_query(request()->except('page'));
  $link = route('category.show', $category->slug) . ($keep ? '?' . $keep : '');
  $initial = mb_substr($category->name, 0, 1);
  $svgUrl = $category->category_svg_url;
@endphp

<li x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
  <div class="group rounded-xl transition-colors {{ $isActive ? 'bg-indigo-50' : 'bg-white hover:bg-slate-50' }}">
    <div class="flex min-h-11 items-center gap-2 px-2">
      @if($hasChildren)
        <button type="button"
           @click="open = !open"
           class="flex min-w-0 flex-1 items-center gap-2.5 rounded-xl px-1 py-1.5 text-left focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600">
      @else
        <a href="{{ $link }}"
           @click="$dispatch('category-menu-close')"
           class="flex min-w-0 flex-1 items-center gap-2.5 rounded-xl px-1 py-1.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600">
      @endif
        <span data-category-media class="relative flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-50 text-sm font-semibold text-slate-600 ring-1 ring-slate-100">
          @if($svgUrl)
            <img data-image-candidates="{{ json_encode(array_merge([$svgUrl], $category->image_candidates)) }}"
              data-image-fallback="{{ asset('images/image-placeholder.svg') }}"
              src="{{ $svgUrl }}"
              alt="{{ $category->name }}"
              class="h-6 w-6 object-contain"
              loading="lazy"
              decoding="async"
            >
          @elseif(filled($category->icon))
            <img data-image-fallback="{{ asset('images/image-placeholder.svg') }}"
              src="{{ $category->icon_url }}"
              alt="{{ $category->name }}"
              class="h-6 w-6 object-contain"
              loading="lazy"
              decoding="async"
            >
          @elseif(filled($category->image))
            <img data-image-candidates="{{ json_encode($category->image_candidates) }}" data-image-fallback="{{ asset('images/image-placeholder.svg') }}"
              src="{{ $category->image_thumb_url }}"
              alt="{{ $category->name }}"
              class="h-full w-full object-cover"
              loading="lazy"
              decoding="async"
            >
          @endif

          <span class="{{ ($svgUrl || filled($category->icon) || filled($category->image)) ? 'hidden category-menu-fallback' : 'category-menu-fallback' }}">
            {{ $initial }}
          </span>
        </span>

        <span class="min-w-0 flex-1">
          <span class="block truncate text-[14px] font-medium leading-5 {{ $isActive ? 'text-slate-900' : 'text-slate-800' }}">
            {{ $category->name }}
          </span>
        </span>
      @if($hasChildren)
        </button>
      @else
        </a>
      @endif

      @if($hasChildren)
        <a href="{{ $link }}"
           @click="$dispatch('category-menu-close')"
           class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-white hover:text-slate-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600"
           title="Перейти в категорию {{ $category->name }}"
           aria-label="Перейти в категорию {{ $category->name }}">
          <i class="ri-arrow-right-line text-lg"></i>
        </a>
      @else
        <span aria-hidden="true" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition group-hover:text-slate-600">
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
