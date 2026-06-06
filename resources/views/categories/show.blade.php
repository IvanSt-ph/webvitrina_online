@php
  $seoUrl = route('category.show', $category->slug);
  $seoDescription = 'Товары в категории “' . $category->name . '” на WebVitrina: предложения продавцов, цены, наличие, доставка и связь с продавцом.';
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


<div class="mx-auto max-w-7xl px-3 py-5 sm:px-4 sm:py-6 lg:px-6">
  <header class="mb-5 flex flex-col gap-2 sm:mb-6 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <h1 class="text-2xl font-bold text-slate-950 sm:text-3xl">{{ $category->name }}</h1>
      <p class="mt-1 text-sm text-slate-500">Товары продавцов, фильтры и актуальные предложения в выбранной категории.</p>
    </div>
    @if($products->total() > 0)
      <span class="inline-flex w-fit rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-bold text-slate-600">
        {{ number_format($products->total(), 0, ',', ' ') }} товаров
      </span>
    @endif
  </header>

  {{-- 🌸 Панель фильтров --}}
  @include('partials.category-filters')

@if(($categoryAdCampaigns ?? collect())->isNotEmpty())
  <section class="mb-6 rounded-2xl border border-indigo-100 bg-white p-3 shadow-sm sm:p-4">
    <div class="mb-3 flex items-center justify-between gap-3">
      <div>
        <h2 class="text-base font-bold text-slate-950 sm:text-lg">Популярное в категории</h2>
        <p class="text-xs text-slate-500 sm:text-sm">Продвигаемые предложения с прозрачной меткой</p>
      </div>
      <span class="shrink-0 rounded-full border border-indigo-100 bg-indigo-50 px-2.5 py-1 text-[11px] font-bold text-indigo-700 sm:text-xs">Продвигается</span>
    </div>

    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
      @foreach($categoryAdCampaigns as $campaign)
        @if($campaign->product)
          @php
            $product = $campaign->product;
            $priceData = $product->price_for_current_currency;
            $price = $priceData['amount'] ?? $product->price;
            $currencySymbol = $priceData['symbol'] ?? '₽';
          @endphp
          <a href="{{ $campaign->resolved_url }}" class="group grid min-w-0 grid-cols-[72px_minmax(0,1fr)] gap-3 rounded-xl border border-slate-200 bg-slate-50 p-2.5 transition hover:border-indigo-200 hover:bg-indigo-50">
            <img src="{{ $product->image_thumb_url }}" alt="{{ $product->title }}" class="h-[72px] w-[72px] rounded-lg object-cover">
            <span class="min-w-0">
              <span class="inline-flex rounded-full bg-white px-2 py-0.5 text-[11px] font-bold text-indigo-700">{{ $campaign->label }}</span>
              <span class="mt-1 block truncate text-sm font-bold text-slate-950 group-hover:text-indigo-700">{{ $product->title }}</span>
              <span class="mt-1 block text-sm font-bold text-indigo-700">{{ number_format($price, 0, ',', ' ') }} {{ $currencySymbol }}</span>
              @if($product->seller?->shop?->name)
                <span class="mt-1 block truncate text-xs text-slate-500">{{ $product->seller->shop->name }}</span>
              @endif
            </span>
          </a>
        @elseif($campaign->shop)
          <a href="{{ $campaign->resolved_url }}" class="group grid min-w-0 grid-cols-[72px_minmax(0,1fr)] gap-3 rounded-xl border border-slate-200 bg-slate-50 p-2.5 transition hover:border-indigo-200 hover:bg-indigo-50">
            <img src="{{ $campaign->shop->banner_url }}" alt="{{ $campaign->shop->name }}" class="h-[72px] w-[72px] rounded-lg object-cover">
            <span class="min-w-0">
              <span class="inline-flex rounded-full bg-white px-2 py-0.5 text-[11px] font-bold text-indigo-700">{{ $campaign->label }}</span>
              <span class="mt-1 block truncate text-sm font-bold text-slate-950 group-hover:text-indigo-700">{{ $campaign->shop->name }}</span>
              <span class="mt-1 block line-clamp-2 text-xs text-slate-500">{{ strip_tags($campaign->shop->description ?: $campaign->description ?: 'Магазин продавца WebVitrina') }}</span>
            </span>
          </a>
        @else
          <a href="{{ $campaign->resolved_url }}" class="group rounded-xl border border-indigo-100 bg-indigo-50 p-3 transition hover:bg-indigo-100">
            <span class="inline-flex rounded-full bg-white px-2 py-0.5 text-[11px] font-bold text-indigo-700">{{ $campaign->label }}</span>
            <span class="mt-2 block text-sm font-bold text-slate-950">{{ $campaign->title }}</span>
            <span class="mt-1 block line-clamp-2 text-xs text-slate-600">{{ $campaign->description ?: 'Партнёрский блок WebVitrina' }}</span>
          </a>
        @endif
      @endforeach
    </div>
  </section>
@endif

@if($products->count())
    <div id="products-container">
        @include('partials.products-grid', ['products' => $products])
    </div>
@else
    <p class="rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-sm text-slate-500">В этой категории пока нет товаров.</p>
@endif


</div>



<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('filtersAjax', () => ({

        loading: false,

        // =============================
        //      APPLY (ПРИМЕНИТЬ)
        // =============================
        apply() {
            this.loading = true;

            const form = document.querySelector('#filters-form');
            const formData = new FormData(form);
            const params = new URLSearchParams(formData).toString();
            const url = form.getAttribute('action') + '?' + params;

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.text())
                .then(html => {

                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // товары
                    const newProducts = doc.querySelector('#products-container');
                    const productsContainer = document.querySelector('#products-container');
                    if (newProducts && productsContainer) {
                        productsContainer.innerHTML = newProducts.innerHTML;
                    }

                    // 🔥 чипы выбранных фильтров
                    const newChips = doc.querySelector('#active-filters');
                    const chipsContainer = document.querySelector('#active-filters');
                    if (newChips && chipsContainer) {
                        chipsContainer.innerHTML = newChips.innerHTML;
                    }

                    // обновляем URL
                    window.history.replaceState({}, '', url);

                    this.loading = false;
                })
                .catch(() => this.loading = false);
        },


        // =============================
        //          PAGINATION
        // =============================
        paginate({ target }) {
            this.loading = true;

            fetch(target.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.text())
                .then(html => {

                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // товары
                    const newProducts = doc.querySelector('#products-container');
                    const productsContainer = document.querySelector('#products-container');
                    if (newProducts && productsContainer) {
                        productsContainer.innerHTML = newProducts.innerHTML;
                    }

                    // 🔥 чипы выбранных фильтров — тоже обновляем
                    const newChips = doc.querySelector('#active-filters');
                    const chipsContainer = document.querySelector('#active-filters');
                    if (newChips && chipsContainer) {
                        chipsContainer.innerHTML = newChips.innerHTML;
                    }

                    window.history.replaceState({}, '', target.href);

                    this.loading = false;
                })
                .catch(() => this.loading = false);
        }

    }));
});
</script>

</x-app-layout>
