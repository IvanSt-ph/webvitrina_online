<div class="lg:col-span-4 w-full max-w-xl mx-auto">

    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-snug break-words overflow-wrap-anywhere hyphens-auto">
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
            <div class="flex items-center text-sm">
                <span class="text-gray-500 whitespace-nowrap">Артикул</span>
                <div class="flex-1 border-b border-dotted mx-2 border-gray-300"></div>
                <span class="font-medium break-words">{{ $product->sku }}</span>
            </div>
        @endif

        @if ($product->category)
            <div class="flex items-center text-sm">
                <span class="text-gray-500 whitespace-nowrap">Категория</span>
                <div class="flex-1 border-b border-dotted mx-2 border-gray-300"></div>
                <span class="font-medium break-words">{{ $product->category->name }}</span>
            </div>
        @endif

        @if ($product->city || $product->country)
            <div class="flex items-center text-sm">
                <span class="text-gray-500 whitespace-nowrap">Локация</span>
                <div class="flex-1 border-b border-dotted mx-2 border-gray-300"></div>
                <span class="font-medium break-words">
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
        class="mt-4 w-full text-left px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl hover:bg-gray-100 hover:shadow-sm transition font-medium text-indigo-600 flex items-center justify-between">
        <span>Характеристики товара</span>
        <i class="ri-list-settings-line text-lg text-gray-500"></i>
    </button>

    {{-- Продавец --}}
    @if ($product->seller)
        <div class="mt-6 bg-gray-50 border border-gray-100 rounded-xl p-4">
            <div class="text-xs uppercase tracking-wide text-gray-400 mb-1">Магазин</div>
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="font-semibold text-gray-900 break-words">
                        {{ $product->seller->name }}
                    </div>
                    <div class="text-xs text-gray-600 mt-1">
                        ⭐ {{ number_format($product->seller->reviews_avg_rating ?? 0, 2) }}
                        · {{ $product->seller->reviews_count }} отзывов
                    </div>
                </div>
                <a href="{{ route('seller.show', $product->seller) }}"
                   class="text-xs font-medium text-indigo-600 hover:text-indigo-700 whitespace-nowrap flex-shrink-0">
                    Перейти →
                </a>
            </div>
        </div>
    @endif

</div>