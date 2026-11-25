@props(['p', 'showDescription' => false, 'promoted' => false, 'promotionLabel' => 'Продвигается'])

@pushOnce('styles', 'product-card-css')
    <link rel="stylesheet" href="{{ asset('css/product-card.css') }}">
@endPushOnce

@php
    $url = route('product.show', $p->slug ?? $p->id);
    $galleryItems = $p->gallery ?? [];

    if (is_string($galleryItems)) {
        $decodedGallery = json_decode($galleryItems, true);
        $galleryItems = is_array($decodedGallery) ? $decodedGallery : [];
    }

    // Собираем галерею: main_image + gallery[]
    $rawGallery = [];
    if ($p->main_image) $rawGallery[] = $p->main_image;
    if ($p->image && $p->image !== $p->main_image) $rawGallery[] = $p->image;
    foreach ($galleryItems as $g) {
        if ($g && !in_array($g, $rawGallery)) $rawGallery[] = $g;
    }
  // ✅ Добавляем заглушку, если галерея пуста
if (empty($rawGallery)) {
    $rawGallery[] = 'no-image.png'; // Добавляем имя файла заглушки
}

$gallery = array_map(fn($img) => \App\Models\Product::storageImageUrl($img), $rawGallery);

    $image = $p->image_thumb_url ?? ($gallery[0] ?? null);
    $hasGallery = count($gallery) > 1;

    $currentPrice = $p->price_for_current_currency;
    $price   = $currentPrice['amount'] ?? ($p->price ?? 0);
    $oldPriceData = $p->old_price_for_current_currency;
    $oldPrice = $oldPriceData['amount'] ?? null;
    $discountPercent = $p->discount_percent;
    $hasDiscount = $discountPercent && $oldPrice && $oldPrice > $price;

    $reviewsCount = (int) ($p->reviews_count ?? 0);
    $avg = round((float) ($p->reviews_avg_rating ?? 0), 1);
    $rating = $avg > 0 ? $avg : null;

    $city     = $p->city->name ?? null;
    $country  = $p->city->country->name ?? $p->country->name ?? null;
    $category = $p->category->name ?? null;

    // ✅ Используем withExists('favorites') из запросов витрины, а при точечном рендере аккуратно fallback.
    $isFav = auth()->check()
        && (array_key_exists('is_favorited', $p->getAttributes())
            ? (bool) $p->is_favorited
            : $p->isFavoritedBy(auth()->user()));
    
    $currencySymbol = $currentPrice['symbol'] ?? '₽';
    
    // Начальное количество в корзине (будет передаваться из контроллера)
    $cartQuantity = $p->cart_quantity ?? 0;
    $quickView = [
        'gallery' => $gallery,
        'title' => $p->title,
        'url' => $url,
        'checkoutUrl' => route('checkout.quick', $p->id),
        'shopName' => $p->seller?->shop?->name,
        'shopUrl' => $p->seller?->shop?->name ? route('seller.show', $p->seller->shop->slug) : null,
        'avg' => $avg,
        'rating' => $rating ? $rating . '/5' : '—',
        'reviews' => '(' . $reviewsCount . ' отзывов)',
        'price' => number_format($price, 2, ',', ' ') . "\u{00A0}" . $currencySymbol,
        'oldPrice' => $hasDiscount ? number_format($oldPrice, 2, ',', ' ') . "\u{00A0}" . $currencySymbol : null,
        'hasDiscount' => (bool) $hasDiscount,
        'discount' => '−' . $discountPercent . '%',
        'location' => ($city ?? '') . ($country ? ($city ? ', ' : '') . $country : ''),
        'category' => $category,
        'description' => $p->description ? Str::limit($p->description, 320) : null,
    ];
    $cardState = [
        'productId' => (int) $p->id,
        'cartCount' => (int) $cartQuantity,
        'active' => (bool) $isFav,
        'cartUrl' => route('cart.add', $p),
        'favoriteUrl' => route('favorites.toggle', $p),
    ];
@endphp

{{-- ===================== CARD ===================== --}}
<div class="pc-card"
     x-data="productCard({{ \Illuminate\Support\Js::from($cardState) }})"
     data-quick-view="{{ json_encode($quickView, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) }}"
     @product-favorite-updated.window="syncFavorite($event.detail)"
     @cart-quantities-refreshed.window="cartCount = Number($event.detail.quantities[productId] || 0)"
     @product-cart-quantity-updated.window="if (Number($event.detail.productId) === productId) cartCount = Number($event.detail.quantity || 0)"
     @click="window.location.href = '{{ $url }}'">

    <a href="{{ $url }}" class="pc-card-link" aria-label="{{ $p->title }}"></a>

    {{-- Favorite --}}
    <button
        type="button"
        @click.stop="toggleFavorite()"
        class="pc-fav-btn"
    >
        <svg class="pc-fav-icon"
             :class="active ? 'pc-fav--active' : ''"
             :fill="active ? '#ff6b6b' : 'transparent'"
             :stroke="active ? 'none' : 'rgba(255,255,255,0.9)'"
             stroke-width="1.8" viewBox="0 0 24 24">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3c3.08 0 5.5 2.42 5.5 5.5 0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
        </svg>
        <template x-if="anim === 'explode'"><div>
            <template x-for="i in 8"><div class="dot" :style="'--i:' + i"></div></template>
        </div></template>
        <template x-if="anim === 'implode'"><div>
            <template x-for="i in 8"><div class="dot dot-in" :style="'--i:' + i"></div></template>
        </div></template>
    </button>

    @if($hasDiscount)
        <div class="pc-badge">−{{ $discountPercent }}%</div>
    @endif

    @if($promoted)
        <div class="absolute left-2 z-20 rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-bold text-indigo-700 shadow-sm ring-1 ring-indigo-100 {{ $hasDiscount ? 'top-9' : 'top-2' }}">
            {{ $promotionLabel }}
        </div>
    @endif

    {{-- Image --}}
    <div class="pc-image-wrap">
        <div class="pc-skeleton" aria-hidden="true"></div>

        @if($image)
            <img data-image-candidates="{{ json_encode($p->image_thumb_candidates) }}" data-image-fallback="{{ asset(\App\Models\Product::IMAGE_FALLBACK_ASSET) }}" src="{{ $image }}" alt="{{ $p->title }}" loading="lazy" decoding="async"
                 class="pc-image"
                 onload="this.classList.toggle('pc-image--placeholder', this.currentSrc === this.dataset.imageFallback); this.classList.add('pc-image--loaded'); this.closest('.pc-image-wrap').querySelector('.pc-skeleton').style.display='none'"/>
        @else
            <div class="pc-no-image">
                <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" class="w-10 h-10 text-gray-300">
                    <rect x="4" y="12" width="56" height="40" rx="4"/>
                    <circle cx="22" cy="26" r="5"/>
                    <path d="M4 42l14-12 10 10 8-8 14 10"/>
                </svg>
            </div>
        @endif

        {{-- Dots indicator if multiple photos --}}
        @if($hasGallery)
            <div class="pc-dots">
                @foreach($gallery as $i => $g)
                    <span class="pc-dot {{ $i === 0 ? 'pc-dot--active' : '' }}"></span>
                @endforeach
            </div>
        @endif

        {{-- Overlay desktop --}}
        <div class="pc-overlay">
            <div class="pc-overlay-meta">
                @if($city || $country)
                    <div class="pc-meta-pill">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3 h-3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21c4.8-4.9 8-8.4 8-11.5a8 8 0 1 0-16 0c0 3.1 3.2 6.6 8 11.5z"/>
                            <circle cx="12" cy="10" r="2" fill="currentColor" stroke="none"/>
                        </svg>
                        {{ $city ?? '' }}{{ $country ? ($city ? ', ' : '') . $country : '' }}
                    </div>
                @endif
                @if($category)
                    <div class="pc-meta-pill">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3 h-3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z"/>
                        </svg>
                        {{ $category }}
                    </div>
                @endif
            </div>
            <button type="button" @click.stop="openQuickView()" class="pc-qv-btn pc-qv-desktop">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Быстрый просмотр
            </button>
        </div>

        {{-- Mobile quick-view --}}
        <button type="button" @click.stop="openQuickView()" class="pc-qv-btn pc-qv-mobile">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
        </button>
    </div>

    {{-- Card body --}}
    <div class="pc-body">
        <div class="pc-meta-top">
            @if($p->seller && $p->seller->shop && $p->seller->shop->name)
                <a href="{{ route('seller.show', $p->seller->shop->slug) }}" class="pc-shop-name" @click.stop>
                    {{ $p->seller->shop->name }}
                </a>
            @else
                <span class="pc-shop-name pc-shop-name--empty">Магазин</span>
            @endif
        </div>

        <h3 class="pc-title">{{ $p->title }}</h3>

        <div class="pc-rating">
            @for($i = 1; $i <= 5; $i++)
                <svg class="pc-star {{ $i <= $avg ? 'pc-star--on' : 'pc-star--off' }}" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.729 1.516 8.234L12 18.896l-7.452 4.373 1.516-8.234L0 9.306l8.332-1.151z"/>
                </svg>
            @endfor
            @if($rating)
                <span class="pc-rating-text">{{ $rating }}</span>
                <span class="pc-rating-count">({{ $reviewsCount }})</span>
            @else
                <span class="pc-rating-empty">Нет отзывов</span>
            @endif
        </div>

        <div class="pc-price-row">
            <span class="pc-price">{{ number_format($price, 2, ',', ' ') }}&nbsp;{{ $currencySymbol }}</span>
            @if($hasDiscount)
                <span class="pc-old-price">{{ number_format($oldPrice, 2, ',', ' ') }}&nbsp;{{ $currencySymbol }}</span>
            @endif
        </div>

        <div class="pc-actions" @click.stop>
            {{-- Кнопка корзины с AJAX и счетчиком --}}
            <button 
                type="button"
                @click.stop="addToCart()"
                class="pc-btn pc-btn--cart"
                title="В корзину"
            >
                <div class="pc-cart-icon-wrapper">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4zM3 6h18M16 10a4 4 0 01-8 0"/>
                    </svg>
                    
                    {{-- Счетчик --}}
                    <template x-if="cartCount > 0">
                        <span class="pc-cart-count" x-text="cartCount"></span>
                    </template>
                </div>
            </button>

            <form method="POST" action="{{ route('checkout.quick', $p->id) }}" class="pc-form-buy" @submit.stop>
                @csrf
                <button class="pc-btn pc-btn--buy">Купить сейчас</button>
            </form>
        </div>
    </div>

</div>
