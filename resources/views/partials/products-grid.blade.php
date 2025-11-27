@if($products->count())
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-8">
        @foreach($products as $p)
            <x-product-card :p="$p" />
        @endforeach
    </div>
<div class="mt-12" @click.prevent="paginate">
    {{ $products->withQueryString()->links() }}
</div>


@else
    <p class="text-gray-500">Нет товаров по выбранным фильтрам.</p>
@endif
