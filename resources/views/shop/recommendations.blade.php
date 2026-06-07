@php
  $seoUrl = route('recommendations.index');
  $seoDescription = 'Рекомендованные товары WebVitrina: ручная подборка площадки, продвигаемые товары и позиции с высоким рейтингом.';
@endphp

@push('meta')
  <meta name="description" content="{{ $seoDescription }}">
  <link rel="canonical" href="{{ $seoUrl }}">
  <meta property="og:type" content="website">
  <meta property="og:title" content="Рекомендованные товары — {{ config('app.name') }}">
  <meta property="og:description" content="{{ $seoDescription }}">
  <meta property="og:url" content="{{ $seoUrl }}">
  <meta property="og:image" content="{{ asset('images/icon.png') }}">
@endpush

<x-app-layout title="Рекомендованные товары">
  <div class="mx-auto max-w-[90rem] px-3 py-6 sm:px-4 lg:px-6">
    <nav class="mb-5 text-sm text-slate-500" aria-label="Breadcrumbs">
      <a href="{{ route('home') }}" class="font-semibold text-slate-600 hover:text-indigo-700">Главная</a>
      <span class="mx-2 text-slate-300">/</span>
      <span class="text-slate-900">Рекомендации</span>
    </nav>

    <header class="mb-6 rounded-2xl border border-indigo-100 bg-indigo-50/60 px-4 py-5 sm:px-6">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-sm font-bold text-indigo-700">Подборка WebVitrina</p>
          <h1 class="mt-1 text-2xl font-bold text-slate-950 sm:text-3xl">Рекомендованные товары</h1>

        </div>
        <a href="{{ route('home') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-white px-4 text-sm font-bold text-indigo-700 shadow-sm ring-1 ring-indigo-100 transition hover:bg-indigo-50">
          <i class="ri-arrow-left-line"></i>
          На главную
        </a>
      </div>
    </header>

    <div data-load-more-root="recommendations-products">
      <div data-load-more-grid class="grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
        @forelse($products as $index => $product)
          <div data-load-more-item class="fade-card visible" style="--delay-index: {{ $index }}">
            <x-product-card :p="$product" />
          </div>
        @empty
          <div class="col-span-full rounded-2xl border border-slate-200 bg-white p-8 text-center">
            <h2 class="text-lg font-bold text-slate-950">Рекомендаций пока нет</h2>
            <p class="mt-2 text-sm text-slate-500">Когда появятся продвигаемые товары или товары с отзывами, они будут здесь.</p>
            <a href="{{ route('home') }}" class="mt-4 inline-flex h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-700">
              Смотреть каталог
            </a>
          </div>
        @endforelse
      </div>

      @include('partials.load-more', ['paginator' => $products])
    </div>
  </div>
</x-app-layout>
