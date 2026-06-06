@php
  $seoUrl = route('category.show', $category->slug);
  $seoDescription = 'Раздел “' . $category->name . '” на WebVitrina: подкатегории, товары продавцов, цены, наличие и условия покупки.';
  $seoImage = $category->image_url;
@endphp

@push('meta')
  <meta name="description" content="{{ $seoDescription }}">
  <link rel="canonical" href="{{ $seoUrl }}">
  <meta property="og:type" content="website">
  <meta property="og:title" content="{{ $category->name }} — {{ config('app.name') }}">
  <meta property="og:description" content="{{ $seoDescription }}">
  <meta property="og:url" content="{{ $seoUrl }}">
  <meta property="og:image" content="{{ $seoImage }}">
  <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $category->name,
        'description' => $seoDescription,
        'url' => $seoUrl,
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => url('/'),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
  </script>
@endpush

<x-app-layout :title="$category->name">

<div class="border-b border-slate-100 bg-white/95 py-2 backdrop-blur">
  <div class="mx-auto max-w-7xl px-4 lg:px-6">
    <x-breadcrumbs :items="$breadcrumbs" />
  </div>
</div>

<div class="mx-auto max-w-7xl px-4 py-5 sm:py-6 lg:px-6">
  <header class="mb-5 flex flex-col gap-2 sm:mb-6 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="text-2xl font-bold text-slate-950 sm:text-3xl">{{ $category->name }}</h1>
      <p class="mt-1 text-sm text-slate-500">Выберите раздел, чтобы посмотреть товары и фильтры внутри него.</p>
    </div>
    <span class="inline-flex w-fit rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-bold text-slate-600">
      {{ $category->children->count() }} {{ trans_choice('раздел|раздела|разделов', $category->children->count()) }}
    </span>
  </header>

  @if($category->children->count())
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5">

      @foreach($category->children as $child)
        <a href="{{ route('category.show', $child->slug) }}"
           class="group block overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-indigo-200 hover:shadow-md">

          {{-- 🔹 Плитка изображения --}}
          <div class="flex aspect-[4/3] items-center justify-center overflow-hidden bg-slate-50 sm:aspect-square">

              @if(!empty($child->image))
                  <picture>
                      <source srcset="{{ $child->image_thumb_url }}" type="image/webp">
                      <img
                          src="{{ $child->image_thumb_url }}"
                          alt="{{ $child->name }}"
                          class="h-full w-full object-cover opacity-0 transition-all duration-700 ease-out group-hover:scale-105"
                          loading="lazy"
                          decoding="async"
                          onload="this.style.opacity=1"
                          onerror="this.src='/images/no-image.webp'">
                  </picture>

              @elseif(!empty($child->icon))
                  <img
                      src="{{ $child->icon_url }}"
                      alt="{{ $child->name }}"
                      class="h-16 w-16 object-contain opacity-60 transition-transform duration-500 group-hover:scale-110 sm:h-20 sm:w-20"
                      loading="lazy"
                      decoding="async"
                      onerror="this.src='/images/no-image.webp'">

              @else
                  <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5"
                       viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h18v18H3z"/>
                  </svg>
              @endif

          </div>

          {{-- 🔹 Название категории --}}
          <div class="p-3 text-center sm:p-4">
            <h2 class="line-clamp-2 text-sm font-semibold text-slate-800 transition group-hover:text-indigo-600">
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
