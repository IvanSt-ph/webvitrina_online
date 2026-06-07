@if($products->count())
    <div data-load-more-root="category-products">
        <div data-load-more-grid class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 sm:gap-5 lg:gap-8">
            @foreach($products as $p)
                <div data-load-more-item class="fade-card">
                    <x-product-card :p="$p" />
                </div>
            @endforeach
        </div>

        @include('partials.load-more', ['paginator' => $products])
    </div>
@else
    <p class="text-gray-500 text-center py-10 sm:py-20">
        Нет товаров по выбранным фильтрам.
    </p>
@endif
