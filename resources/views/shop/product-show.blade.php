@php
    $seoDescription = \Illuminate\Support\Str::limit(
        trim(strip_tags($product->description ?: 'Купить ' . $product->title . ' на WebVitrina. Уточните наличие, доставку и условия покупки у продавца.')),
        160
    );
    $seoImage = $product->image_url;
    $seoUrl = route('product.show', $product->slug);
    $seoAvailability = ($product->stock ?? 0) > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
@endphp

@push('meta')
    <meta name="description" content="{{ $seoDescription }}">
    <link rel="canonical" href="{{ $seoUrl }}">
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $product->title }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoUrl }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="product:price:amount" content="{{ number_format((float) $product->price, 2, '.', '') }}">
    <meta property="product:price:currency" content="{{ $product->currency ?? 'PRB' }}">
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->title,
            'description' => $seoDescription,
            'image' => [$seoImage],
            'sku' => $product->sku,
            'category' => $product->category?->name,
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->seller?->shop?->name ?? config('app.name'),
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $seoUrl,
                'priceCurrency' => $product->currency ?? 'PRB',
                'price' => number_format((float) $product->price, 2, '.', ''),
                'availability' => $seoAvailability,
                'seller' => [
                    '@type' => 'Organization',
                    'name' => $product->seller?->shop?->name ?? $product->seller?->name ?? config('app.name'),
                ],
            ],
            'aggregateRating' => ($product->reviews_count ?? 0) > 0 ? [
                '@type' => 'AggregateRating',
                'ratingValue' => round((float) ($product->reviews_avg_rating ?? 0), 1),
                'reviewCount' => (int) $product->reviews_count,
            ] : null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
    </script>
@endpush

<x-app-layout :title="$product->title">

    <x-slot name="specs">
        @include('components.product.specs', ['product' => $product])
    </x-slot>


    @php
        $isFav = auth()->check() && $product->isFavoritedBy(auth()->user());
        $adminChatId = request()->integer('admin_chat');
        $isAdminProductPreview = auth()->check() && auth()->user()->role === 'admin' && $adminChatId;
    @endphp
    
    <div class="wv-page-shell max-w-[1440px] pb-10 pt-3 sm:pt-6">
        @if($isAdminProductPreview)
            <div class="mb-3 rounded-2xl border border-indigo-100 bg-indigo-50/90 p-3 shadow-sm shadow-indigo-950/5 sm:mb-4 sm:p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="text-xs font-bold uppercase tracking-wide text-indigo-600">Админ-просмотр товара</div>
                        <div class="mt-1 truncate text-sm font-semibold text-slate-900">
                            Карточка открыта из диалога ID {{ $adminChatId }}
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.chats.show', $adminChatId) }}"
                           class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-3 text-xs font-bold text-white shadow-sm transition hover:bg-indigo-700">
                            <i class="ri-arrow-left-line"></i>
                            Вернуться в чат
                        </a>
                        <a href="{{ route('admin.products.edit', $product) }}"
                           class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl border border-indigo-200 bg-white px-3 text-xs font-bold text-indigo-700 transition hover:bg-indigo-50">
                            <i class="ri-edit-2-line"></i>
                            Редактировать
                        </a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Хлебные крошки --}}
        <x-product.breadcrumbs :product="$product" />


        {{-- Верхний блок: Галерея + Инфо + Цена --}}
        <div class="wv-panel grid gap-6 p-4 sm:p-6 lg:grid-cols-12">
            <x-product.gallery :product="$product" />
            <x-product.info :product="$product" />
            <x-product.buy-box :product="$product" :isFav="$isFav" />
        </div>




  
        {{-- Карта местоположения (раскрывающаяся) --}}
        <div 
            x-data="{ openMap: false }" 
            class="wv-card mt-6 overflow-hidden">

            {{-- Заголовок + стрелка --}}
            <button 
                @click="openMap = !openMap" 
                class="flex w-full items-center justify-between p-4 text-left text-base font-bold text-slate-950 sm:text-lg">
                <span class="inline-flex items-center gap-2">
                    <i class="ri-map-pin-line text-indigo-600"></i>
                    Местоположение продавца
                </span>

                <svg 
                    :class="openMap ? 'rotate-90' : ''"
                    class="h-6 w-6 text-slate-400 transition-transform duration-300"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            {{-- Контейнер карты --}}
                <div class="border-t border-slate-100 p-4" x-show="openMap" x-collapse>
                    <x-product.map :product="$product" />
                </div>


        </div>

        





        {{-- Вкладки: описание, размеры, характеристики, отзывы --}}
      <x-product.tabs :product="$product" :reviews="$reviews" :myReview="$myReview" :reviewStats="$reviewStats" />

        {{-- Похожие товары --}}
        <x-product.related :items="$related" />

    </div>

    @if($chatConversation)
        @include('chats.partials.widget', [
            'conversation' => $chatConversation,
            'messages' => $chatMessages,
            'hasOlderMessages' => $chatHasOlderMessages,
            'oldestMessageId' => $chatOldestMessageId,
            'latestMessageId' => $chatLatestMessageId,
            'latestReadOutgoingMessageId' => $chatLatestReadOutgoingMessageId,
            'contextProduct' => $product,
            'returnUrl' => route('product.show', [
                'identifier' => $product->slug,
                'chat' => $chatConversation->id,
            ], false),
            'closeUrl' => route('product.show', $product->slug, false),
        ])
    @endif

</x-app-layout>
