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

{{-- 🧭 Хлебные крошки и фильтры закреплены --}}
<div class="sticky z-40 mb-4 border-b border-slate-100 bg-white/95 py-0.5 backdrop-blur supports-[backdrop-filter]:backdrop-blur-sm sticky-breadcrumbs">


  <div class="max-w-7xl mx-auto px-4 lg:px-6 mt-4">
    <x-breadcrumbs :items="$breadcrumbs" />
  </div>
</div>


<div class="mx-auto max-w-7xl px-1 sm:px-4 lg:px-6">

  {{-- 🌸 Панель фильтров --}}
  @include('partials.category-filters')

  {{-- 🔹 Заголовок --}}
  <h1 class="mb-6 text-2xl font-semibold text-slate-950">
    {{ $category->name }}
  </h1>


  {{-- 🔹 Плитки подкатегорий --}}
  @if($category->children->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6 mb-10">
      @foreach($category->children as $child)

        <a href="{{ route('category.show', $child->slug) }}"
           class="group block overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.03)]
                  hover:border-indigo-200 hover:shadow-[0_10px_24px_rgba(15,23,42,0.06)]
                  transition-all duration-300">

          <div class="flex aspect-square items-center justify-center overflow-hidden bg-slate-50">

              @if(!empty($child->image))
                  <img src="{{ $child->image_thumb_url }}"
                       alt="{{ $child->name }}"
                       class="w-full h-full object-cover opacity-0 transition-all duration-700 ease-out group-hover:scale-105"
                       loading="lazy"
                       onload="this.style.opacity=1"
                       onerror="this.src='/images/no-image.webp'">

              @elseif(!empty($child->icon))
                  <img src="{{ $child->icon_url }}"
                       alt="{{ $child->name }}"
                       class="w-20 h-20 object-contain opacity-60 transition-transform duration-500 group-hover:scale-110"
                       loading="lazy"
                       onerror="this.src='/images/no-image.webp'">

              @else
                <svg class="mx-auto h-10 w-10 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h18v18H3z"/>
                </svg>
              @endif

          </div>

          <div class="p-4 text-center">
            <h2 class="text-sm font-medium text-slate-800 transition group-hover:text-indigo-600">
              {{ $child->name }}
            </h2>
          </div>

        </a>
      @endforeach
    </div>
  @endif


{{-- 🔹 Товары --}}
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

<style>
  /* Мобильное значение (по умолчанию) */
.sticky-breadcrumbs {
    top: 45px;
}

/* Планшеты и ПК (640px+) */
@media (min-width: 640px) {
    .sticky-breadcrumbs {
        top: 65px;
    }
}

</style>
</x-app-layout>
