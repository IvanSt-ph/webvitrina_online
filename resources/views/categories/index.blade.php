@php
  $seoUrl = route('category.index');
  $seoDescription = 'Каталог категорий WebVitrina: выберите раздел, найдите товары продавцов и уточните условия покупки, доставки и оплаты.';
@endphp

@push('meta')
  <meta name="description" content="{{ $seoDescription }}">
  <link rel="canonical" href="{{ $seoUrl }}">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Категории — {{ config('app.name') }}">
  <meta property="og:description" content="{{ $seoDescription }}">
  <meta property="og:url" content="{{ $seoUrl }}">
  <meta property="og:image" content="{{ asset('images/icon.png') }}">
  <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Категории',
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

<x-app-layout title="Категории">
  <div class="mx-auto max-w-[90rem] px-3 py-6 sm:px-4 lg:px-6">
    <nav class="mb-5 text-sm text-slate-500" aria-label="Breadcrumbs">
      <a href="{{ route('home') }}" class="font-semibold text-slate-600 hover:text-indigo-700">Главная</a>
      <span class="mx-2 text-slate-300">/</span>
      <span class="text-slate-900">Категории</span>
    </nav>

    <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <p class="text-sm font-bold text-indigo-700">Разделы каталога</p>
        <h1 class="mt-1 text-2xl font-bold text-slate-950 sm:text-3xl">Все категории</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
          Выберите раздел, чтобы перейти к подкатегориям или товарам продавцов.
        </p>
      </div>
      <a href="{{ route('home') }}" class="inline-flex h-10 w-fit items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
        <i class="ri-shopping-bag-3-line"></i>
        Каталог товаров
      </a>
    </header>

    @if($categories->count())
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
        @foreach($categories as $cat)
          @php
            $initial = mb_substr($cat->name, 0, 1);
            $hasImage = filled($cat->image);
            $hasIcon = filled($cat->icon);
          @endphp
          <a href="{{ route('category.show', $cat->slug) }}"
             class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg">
            <div data-category-media class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-indigo-50 via-white to-slate-100">
              @if($hasImage)
                <img
                  src="{{ $cat->image_thumb_url }}"
                  alt="{{ $cat->name }}"
                  class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]"
                  loading="lazy"
                  decoding="async"
                  onerror="this.closest('[data-category-media]').classList.add('category-media-failed'); this.remove();"
                >
              @elseif($hasIcon)
                <img
                  src="{{ $cat->icon_url }}"
                  alt="{{ $cat->name }}"
                  class="absolute left-1/2 top-1/2 h-16 w-16 -translate-x-1/2 -translate-y-1/2 object-contain opacity-80 transition duration-500 group-hover:scale-110"
                  loading="lazy"
                  decoding="async"
                  onerror="this.closest('[data-category-media]').classList.add('category-media-failed'); this.remove();"
                >
              @endif

              <div class="{{ ($hasImage || $hasIcon) ? 'hidden category-fallback' : 'category-fallback' }} absolute inset-0 flex items-center justify-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-2xl font-black text-indigo-600 shadow-sm ring-1 ring-indigo-100">
                  {{ $initial }}
                </span>
              </div>
            </div>

            <div class="flex min-h-[64px] items-center justify-between gap-3 px-3 py-3">
              <h2 class="line-clamp-2 text-sm font-bold leading-5 text-slate-800 transition group-hover:text-indigo-700">
                {{ $cat->name }}
              </h2>
              <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition group-hover:bg-indigo-50 group-hover:text-indigo-600">
                <i class="ri-arrow-right-line"></i>
              </span>
            </div>
          </a>
        @endforeach
      </div>
    @else
      <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
        <h2 class="text-lg font-bold text-slate-950">Категории пока не добавлены</h2>
        <p class="mt-2 text-sm text-slate-500">Когда администратор добавит разделы каталога, они появятся здесь.</p>
      </div>
    @endif
  </div>

  <style>
    .category-media-failed .category-fallback {
      display: flex !important;
    }
  </style>
</x-app-layout>
