<x-app-layout :title="$category->name">

{{-- 🧭 Хлебные крошки и фильтры закреплены --}}
<div class="sticky z-40 bg-white/95 backdrop-blur supports-[backdrop-filter]:backdrop-blur-sm 
     border-b border-gray-100 py-0.5 mb-4 sticky-breadcrumbs">


  <div class="max-w-7xl mx-auto px-4 lg:px-6 mt-4">
    <x-breadcrumbs :items="$breadcrumbs" />
  </div>
</div>


<div class="max-w-7xl mx-auto px-1 sm:px-4 lg:px-6">

  {{-- 🌸 Панель фильтров --}}
  @include('partials.category-filters')

  {{-- 🔹 Заголовок --}}
  <h1 class="text-2xl font-semibold text-gray-800 mb-6">
    {{ $category->name }}
  </h1>


  {{-- 🔹 Плитки подкатегорий --}}
  @if($category->children->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6 mb-10">
      @foreach($category->children as $child)

        <a href="{{ route('category.show', $child->slug) }}"
           class="group block bg-white border border-gray-200 rounded-2xl
                  overflow-hidden shadow-sm hover:shadow-md hover:border-indigo-300
                  transition-all duration-300">

          <div class="aspect-square bg-gray-50 flex items-center justify-center overflow-hidden">

              @if(!empty($child->image))
                  <img src="{{ asset('storage/'.$child->image) }}"
                       alt="{{ $child->name }}"
                       class="w-full h-full object-cover opacity-0 transition-all duration-700 ease-out group-hover:scale-105"
                       loading="lazy"
                       onload="this.style.opacity=1"
                       onerror="this.src='/images/no-image.webp'">

              @elseif(!empty($child->icon))
                  <img src="{{ asset('storage/'.$child->icon) }}"
                       alt="{{ $child->name }}"
                       class="w-20 h-20 object-contain opacity-60 transition-transform duration-500 group-hover:scale-110"
                       loading="lazy"
                       onerror="this.src='/images/no-image.webp'">

              @else
                <svg class="w-10 h-10 text-gray-300 mx-auto" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h18v18H3z"/>
                </svg>
              @endif

          </div>

          <div class="p-4 text-center">
            <h2 class="text-sm font-medium text-gray-800 group-hover:text-indigo-600 transition">
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
    <p class="text-gray-500">В этой категории пока нет товаров.</p>
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
