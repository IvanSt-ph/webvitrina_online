<div class="lg:col-span-4 w-full max-w-xl mx-auto">

    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-snug">
        {{ $product->title }}
    </h1>

    {{-- Рейтинг / отзывы --}}
    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-gray-600">
        <div class="flex items-center gap-1">
            <span class="text-yellow-400">★</span>
            <span class="font-semibold">
                {{ number_format($product->reviews_avg_rating ?? 0, 1) }}
            </span>
            <span class="text-gray-400">
                ({{ $product->reviews_count }} отзывов)
            </span>
        </div>

        @if ($product->orders_count)
            <span class="text-gray-400">
                · {{ $product->orders_count }} заказов
            </span>
        @endif
    </div>

    {{-- Мини параметры --}}
    <div class="mt-5 space-y-3 text-sm text-gray-700">
        @if ($product->sku)
            <div class="flex flex-col sm:flex-row sm:justify-between">
                <span class="text-gray-500">Артикул</span>
                <span class="font-medium">{{ $product->sku }}</span>
            </div>
        @endif

        @if ($product->category)
            <div class="flex flex-col sm:flex-row sm:justify-between">
                <span class="text-gray-500">Категория</span>
                <span class="font-medium">{{ $product->category->name }}</span>
            </div>
        @endif

        @if ($product->city || $product->country)
            <div class="flex flex-col sm:flex-row sm:justify-between">
                <span class="text-gray-500">Локация</span>
                <span class="font-medium">
                    {{ $product->country->name ?? $product->city->country->name ?? '' }}
                    @if ($product->city)
                        , {{ $product->city->name }}
                    @endif
                </span>
            </div>
        @endif
    </div>

    <button 
    @click="$store.specs.open = true"
    class="mt-4 w-full text-left px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl
           hover:bg-gray-100 transition font-medium text-indigo-600 flex items-center justify-between"
>
    <span>Характеристики товара</span>
    <i class="ri-arrow-right-s-line text-xl"></i>
</button>

    {{-- Продавец --}}
    @if ($product->seller)
        <div class="mt-6 bg-gray-50 border border-gray-100 rounded-xl p-4">
            <div class="text-xs uppercase tracking-wide text-gray-400 mb-1">Магазин</div>
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="font-semibold text-gray-900">
                        {{ $product->seller->name }}
                    </div>
                    <div class="text-xs text-gray-600 mt-1">
                        ⭐ {{ number_format($product->seller->reviews_avg_rating ?? 0, 2) }}
                        · {{ $product->seller->reviews_count }} отзывов
                    </div>
                </div>
                <a href="{{ route('seller.show', $product->seller) }}"
                   class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                    Перейти →
                </a>
            </div>
        </div>
    @endif

</div>
