@props(['p', 'showDescription' => false])

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/product-card.css') }}">
@endpush

@php
    $url = route('product.show', $p->slug ?? $p->id);

    // Собираем галерею: main_image + gallery[]
    $rawGallery = [];
    if ($p->main_image) $rawGallery[] = $p->main_image;
    if ($p->image && $p->image !== $p->main_image) $rawGallery[] = $p->image;
    foreach (($p->gallery ?? []) as $g) {
        if ($g && !in_array($g, $rawGallery)) $rawGallery[] = $g;
    }
    if (empty($rawGallery)) $rawGallery = [];

    $gallery = array_map(function($img) {
        return (!str_starts_with($img, 'http')) ? asset('storage/' . $img) : $img;
    }, $rawGallery);

    $image = $gallery[0] ?? null;
    $hasGallery = count($gallery) > 1;

    $price   = $p->price ?? 0;
    $oldPrice = $p->old_price ?? null;
    $hasDiscount = $oldPrice && $oldPrice > $price;
    $discountPercent = $hasDiscount ? round(100 - ($price * 100 / $oldPrice)) : null;

    $avg = round($p->reviews->avg('rating'), 1);
    $rating = $avg > 0 ? $avg : null;

    $city     = $p->city->name ?? null;
    $country  = $p->city->country->name ?? $p->country->name ?? null;
    $category = $p->category->name ?? null;

    // ✅ Добавляем проверку избранного
    $isFav = auth()->check() && $p->isFavoritedBy(auth()->user());
    
    $currencySymbol = session('currency_symbol', '₽');
    $galleryJson = json_encode($gallery);
    
    // Начальное количество в корзине (будет передаваться из контроллера)
    $cartQuantity = $p->cart_quantity ?? 0;
@endphp

{{-- ===================== CARD ===================== --}}
<div class="pc-card"
x-data="{ 
    open: false,
    cartCount: {{ $cartQuantity }}, // используем $cartQuantity вместо 0
    addToCart() {
        fetch('{{ route('cart.add', $p) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.cartCount = data.quantity;
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
}"
     @keydown.escape.window="open = false"
     @click="window.location.href = '{{ $url }}'">

    <a href="{{ $url }}" class="pc-card-link" aria-label="{{ $p->title }}"></a>

    {{-- Favorite --}}
    <button
        type="button"
        x-data="{
            active: {{ $isFav ? 'true' : 'false' }},
            anim: '',
            toggleFavorite() {
                fetch('{{ route('favorites.toggle', $p) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                }).then(r => r.json()).then(() => {
                    this.anim = this.active ? 'implode' : 'explode';
                    this.active = !this.active;
                    setTimeout(() => this.anim = '', 600);
                });
            }
        }"
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

    {{-- Image --}}
    <div class="pc-image-wrap">
        <div class="pc-skeleton" aria-hidden="true"></div>

        @if($image)
            <img src="{{ $image }}" alt="{{ $p->title }}" loading="lazy"
                 class="pc-image"
                 onload="this.classList.add('pc-image--loaded'); this.closest('.pc-image-wrap').querySelector('.pc-skeleton').style.display='none'"/>
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
            <button type="button" @click.stop="open = true" class="pc-qv-btn pc-qv-desktop">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Быстрый просмотр
            </button>
        </div>

        {{-- Mobile quick-view --}}
        <button type="button" @click.stop="open = true" class="pc-qv-btn pc-qv-mobile">
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
                <a href="{{ route('seller.show', $p->seller->id) }}" class="pc-shop-name" @click.stop>
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
                <span class="pc-rating-count">({{ $p->reviews->count() }})</span>
            @else
                <span class="pc-rating-empty">Нет отзывов</span>
            @endif
        </div>

        <div class="pc-price-row">
            <span class="pc-price">{{ number_format($price, 0, ',', ' ') }}&nbsp;{{ $currencySymbol }}</span>
            @if($hasDiscount)
                <span class="pc-old-price">{{ number_format($oldPrice, 0, ',', ' ') }}&nbsp;{{ $currencySymbol }}</span>
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

    {{-- ===================== MODAL ===================== --}}
    <template x-teleport="body">
        <div x-show="open"
             x-data="{
                 gallery: {{ $galleryJson }},
                 current: 0,
                 modalCartCount: {{ $cartQuantity }},
                 addToCartModal() {
                     fetch('{{ route('cart.add', $p) }}', {
                         method: 'POST',
                         headers: {
                             'X-CSRF-TOKEN': '{{ csrf_token() }}',
                             'Accept': 'application/json',
                             'Content-Type': 'application/json'
                         }
                     })
                     .then(response => response.json())
                     .then(data => {
                         if (data.success) {
                             this.modalCartCount = data.quantity;
                             $dispatch('cart-updated', { quantity: data.quantity });
                         }
                     })
                     .catch(error => console.error('Modal error:', error));
                 },
                 prev() { this.current = (this.current - 1 + this.gallery.length) % this.gallery.length },
                 next() { this.current = (this.current + 1) % this.gallery.length },
                 touchStartX: 0,
                 onTouchStart(e) { this.touchStartX = e.touches[0].clientX },
                 onTouchEnd(e) {
                     const dx = e.changedTouches[0].clientX - this.touchStartX;
                     if (Math.abs(dx) > 40) { dx < 0 ? this.next() : this.prev() }
                 }
             }"
             x-transition:enter="pm-fade-enter"
             x-transition:enter-start="pm-fade-from"
             x-transition:enter-end="pm-fade-to"
             x-transition:leave="pm-fade-enter"
             x-transition:leave-start="pm-fade-to"
             x-transition:leave-end="pm-fade-from"
             class="pm-backdrop"
             @click.self="open = false"
             style="display:none;">

            <div class="pm-sheet"
                 x-transition:enter="pm-sheet-enter"
                 x-transition:enter-start="pm-sheet-from"
                 x-transition:enter-end="pm-sheet-to"
                 x-transition:leave="pm-sheet-enter"
                 x-transition:leave-start="pm-sheet-to"
                 x-transition:leave-end="pm-sheet-from">

                <div class="pm-topbar">
                    <a href="{{ $url }}" class="pm-fullpage-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        <span>Страница товара</span>
                    </a>
                    <button @click="open = false" class="pm-close">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="pm-inner">
                    {{-- Gallery column --}}
                    <div class="pm-image-col">
                        {{-- Main image --}}
                        <div class="pm-main-image-wrap"
                             @touchstart="onTouchStart($event)"
                             @touchend="onTouchEnd($event)">
                            @if(!empty($gallery))
                                <template x-for="(src, idx) in gallery" :key="idx">
                                    <img :src="src"
                                         alt="{{ $p->title }}"
                                         class="pm-image"
                                         :class="current === idx ? 'pm-image--active' : ''"
                                         loading="lazy"/>
                                </template>
                            @else
                                <div class="pm-no-image">
                                    <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" class="w-14 h-14 text-gray-300">
                                        <rect x="4" y="12" width="56" height="40" rx="4"/>
                                        <circle cx="22" cy="26" r="5"/>
                                        <path d="M4 42l14-12 10 10 8-8 14 10"/>
                                    </svg>
                                </div>
                            @endif

                            {{-- Favorite button in modal --}}
                            <button
                                type="button"
                                x-data="{
                                    active: {{ $isFav ? 'true' : 'false' }},
                                    anim: '',
                                    toggleFavorite() {
                                        fetch('{{ route('favorites.toggle', $p) }}', {
                                            method: 'POST',
                                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                                        }).then(r => r.json()).then(() => {
                                            this.anim = this.active ? 'implode' : 'explode';
                                            this.active = !this.active;
                                            setTimeout(() => this.anim = '', 600);
                                        });
                                    }
                                }"
                                @click.stop="toggleFavorite()"
                                class="pm-fav-btn"
                            >
                                <svg class="pm-fav-icon"
                                     :class="active ? 'pm-fav--active' : ''"
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
                                <div class="pm-badge">−{{ $discountPercent }}%</div>
                            @endif

                            {{-- Photo counter --}}
                            @if($hasGallery)
                                <div class="pm-counter" x-text="(current + 1) + ' / ' + gallery.length"></div>
                            @endif

                            {{-- Prev/Next arrows (only if multiple) --}}
                            @if($hasGallery)
                                <button @click.stop="prev()" class="pm-arrow pm-arrow--left">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>
                                <button @click.stop="next()" class="pm-arrow pm-arrow--right">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            @endif
                        </div>

                        {{-- Thumbnails --}}
                        @if($hasGallery)
                            <div class="pm-thumbs-container">
                                <div class="pm-thumbs" x-ref="thumbnails">
                                    @foreach($gallery as $idx => $src)
                                        <button @click.stop="current = {{ $idx }}; $nextTick(() => { if ($refs.thumbnails) { const thumb = $refs.thumbnails.children[{{ $idx }}]; if (thumb) thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' }); } })"
                                                class="pm-thumb"
                                                :class="current === {{ $idx }} ? 'pm-thumb--active' : ''">
                                            <img src="{{ $src }}" alt="" loading="lazy"/>
                                        </button>
                                    @endforeach
                                </div>
                                
                                {{-- Scroll indicators --}}
                                <button class="pm-thumb-scroll pm-thumb-scroll--left" @click="$refs.thumbnails.scrollBy({ left: -100, behavior: 'smooth' })">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>
                                <button class="pm-thumb-scroll pm-thumb-scroll--right" @click="$refs.thumbnails.scrollBy({ left: 100, behavior: 'smooth' })">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- Info column --}}
                    <div class="pm-info-col">
                        <div class="pm-info-scroll">
                            @if($p->seller && $p->seller->shop && $p->seller->shop->name)
                                <a href="{{ route('seller.show', $p->seller->id) }}" class="pm-shop">{{ $p->seller->shop->name }}</a>
                            @endif

                            <h2 class="pm-title">{{ $p->title }}</h2>

                            <div class="pm-rating">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="pm-star {{ $i <= $avg ? 'pm-star--on' : 'pm-star--off' }}" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.729 1.516 8.234L12 18.896l-7.452 4.373 1.516-8.234L0 9.306l8.332-1.151z"/>
                                    </svg>
                                @endfor
                                <span class="pm-rating-val">{{ $rating ? $rating . '/5' : '—' }}</span>
                                <span class="pm-review-count">({{ $p->reviews->count() }} отзывов)</span>
                            </div>

                            <div class="pm-price-block">
                                <span class="pm-price">{{ number_format($price, 0, ',', ' ') }}&nbsp;{{ $currencySymbol }}</span>
                                @if($hasDiscount)
                                    <span class="pm-old-price">{{ number_format($oldPrice, 0, ',', ' ') }}&nbsp;{{ $currencySymbol }}</span>
                                    <span class="pm-save-badge">−{{ $discountPercent }}%</span>
                                @endif
                            </div>

                            @if($city || $country || $category)
                                <div class="pm-meta-row">
                                    @if($city || $country)
                                        <div class="pm-meta-item">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21c4.8-4.9 8-8.4 8-11.5a8 8 0 1 0-16 0c0 3.1 3.2 6.6 8 11.5z"/>
                                                <circle cx="12" cy="10" r="2" fill="currentColor" stroke="none"/>
                                            </svg>
                                            {{ $city ?? '' }}{{ $country ? ($city ? ', ' : '') . $country : '' }}
                                        </div>
                                    @endif
                                    @if($category)
                                        <div class="pm-meta-item">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z"/>
                                            </svg>
                                            {{ $category }}
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @if($p->description)
                                <p class="pm-description">{{ Str::limit($p->description, 320) }}</p>
                            @endif
                        </div>

                        {{-- Sticky footer --}}
                        <div class="pm-actions">
                            {{-- Кнопка корзины в модалке --}}
                            <button 
                                type="button"
                                @click.stop="addToCartModal()"
                                class="pm-btn pm-btn--cart"
                            >
                                <div class="pm-cart-icon-wrapper">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4zM3 6h18M16 10a4 4 0 01-8 0"/>
                                    </svg>
                                    
                                    {{-- Счетчик --}}
                                    <template x-if="modalCartCount > 0">
                                        <span class="pm-cart-count" x-text="modalCartCount"></span>
                                    </template>
                                    <span x-show="modalCartCount === 0">В корзину</span>
                                </div>
                            </button>

                            <form method="POST" action="{{ route('checkout.quick', $p->id) }}">
                                @csrf
                                <button class="pm-btn pm-btn--buy">Купить сейчас</button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </template>

</div>