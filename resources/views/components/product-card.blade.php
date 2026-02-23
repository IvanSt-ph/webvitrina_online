@props(['p', 'showDescription' => false])

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
            // синхронизируем с основной карточкой
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


<style>
@import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap');

/* ══════════════════════════════════
   CARD
══════════════════════════════════ */
.pc-card {
    position: relative; display: flex; flex-direction: column;
    background: #fff; border-radius: 20px; overflow: hidden;
    font-family: 'Manrope', sans-serif;
    transition: transform 0.3s cubic-bezier(.22,.68,0,1.1), box-shadow 0.3s ease;
    box-shadow: 0 1px 4px rgba(0,0,0,.05), 0 0 0 1px rgba(0,0,0,.05);
    cursor: pointer;
}
.pc-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 36px rgba(0,0,0,.1), 0 0 0 1px rgba(0,0,0,.05);
}
.pc-card-link { position: absolute; inset: 0; z-index: 1; }
.pc-fav-btn, .pc-qv-btn, .pc-body, .pc-badge { position: relative; z-index: 2; }

/* Fav */
.pc-fav-btn {
    position: absolute; top: 10px; right: 10px; z-index: 3;
    width: 30px; height: 30px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,0.26); backdrop-filter: blur(8px);
    border-radius: 50%; border: none; cursor: pointer;
    transition: background 0.2s, transform 0.2s;
}
.pc-fav-btn:hover { background: rgba(0,0,0,0.46); transform: scale(1.1); }
.pc-fav-icon { width: 15px; height: 15px; transition: transform 0.3s cubic-bezier(.22,.68,0,1.5); }
.pc-fav--active { transform: scale(1.2); }

/* Badge */
.pc-badge {
    position: absolute; top: 10px; left: 10px; z-index: 3;
    padding: 2px 8px; background: #ff6b6b; color: #fff;
    font-family: 'Manrope', sans-serif; font-size: 10px; font-weight: 700;
    letter-spacing: .03em; border-radius: 100px;
}

/* Image */
.pc-image-wrap {
    position: relative; aspect-ratio: 4 / 3.2;
    overflow: hidden; background: #f2f2f5;
}
.pc-skeleton {
    position: absolute; inset: 0; z-index: 1;
    background: linear-gradient(90deg, #f0f0f3 25%, #e4e4ea 50%, #f0f0f3 75%);
    background-size: 200% 100%;
    animation: pc-shimmer 1.4s infinite;
}
@keyframes pc-shimmer {
    0%   { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
.pc-image {
    position: relative; z-index: 2;
    width: 100%; height: 100%; object-fit: cover;
    opacity: 0; transition: opacity 0.35s ease, transform 0.55s cubic-bezier(.22,.68,0,1.2), filter 0.55s ease;
}
.pc-image--loaded { opacity: 1; }
.pc-card:hover .pc-image--loaded { transform: scale(1.06); filter: brightness(.82); }
.pc-no-image {
    width: 100%; height: 100%; position: relative; z-index: 2;
    display: flex; align-items: center; justify-content: center; background: #f0f0f3;
}

/* Dots (gallery indicator on card) */
.pc-dots {
    position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%);
    z-index: 4; display: flex; gap: 4px; align-items: center;
}
.pc-dot {
    width: 5px; height: 5px; border-radius: 50%;
    background: rgba(255,255,255,0.5); transition: background 0.2s, transform 0.2s;
}
.pc-dot--active { background: #fff; transform: scale(1.3); }

/* Overlay */
.pc-overlay {
    position: absolute; inset: 0; z-index: 3;
    display: flex; flex-direction: column; justify-content: space-between;
    padding: 10px; pointer-events: none;
}
.pc-overlay-meta {
    display: flex; flex-wrap: wrap; gap: 4px;
    align-items: flex-end; margin-top: auto;
    transform: translateY(6px); opacity: 0;
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.pc-card:hover .pc-overlay-meta { opacity: 1; transform: translateY(0); }
.pc-meta-pill {
    display: flex; align-items: center; gap: 3px;
    padding: 2px 7px;
    background: rgba(0,0,0,0.44); backdrop-filter: blur(6px);
    color: rgba(255,255,255,.92); font-size: 10px; font-weight: 500; border-radius: 100px;
}

/* QV buttons */
.pc-qv-btn {
    display: flex; align-items: center; justify-content: center; gap: 5px;
    border: none; cursor: pointer; font-family: 'Manrope', sans-serif;
    font-weight: 600; pointer-events: auto;
}
.pc-qv-desktop {
    padding: 6px 14px; font-size: 11px;
    background: rgba(255,255,255,0.18); backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.42); color: #fff;
    border-radius: 100px;
    position: absolute; top: 50%; left: 50%; translate: -50% -50%;
    white-space: nowrap;
    opacity: 0; transform: translateY(5px) scale(0.94);
    transition: opacity 0.3s ease, transform 0.3s ease, background 0.2s;
}
.pc-qv-desktop:hover { background: rgba(255,255,255,0.3); }
.pc-card:hover .pc-qv-desktop { opacity: 1; transform: translateY(0) scale(1); translate: -50% -50%; }
.pc-qv-mobile {
    position: absolute; bottom: 9px; right: 9px; z-index: 4;
    width: 30px; height: 30px; padding: 0;
    background: rgba(0,0,0,0.36); backdrop-filter: blur(8px);
    border-radius: 50%; color: #fff; display: none;
}
.pc-qv-mobile:hover { background: rgba(0,0,0,0.6); }

@media (hover: none), (max-width: 640px) {
    .pc-qv-desktop { display: none !important; }
    .pc-qv-mobile  { display: flex !important; }
    .pc-dots { left: 50%; transform: translateX(-50%); bottom: 10px; }
}

/* Body */
.pc-body { padding: 10px 11px 11px; display: flex; flex-direction: column; flex: 1; }
.pc-meta-top { margin-bottom: 3px; }
.pc-shop-name {
    font-size: 10px; font-weight: 500; color: #b0b0bc;
    text-decoration: none; transition: color 0.2s;
    display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    position: relative; z-index: 2;
}
.pc-shop-name:hover { color: #74bdfd; }
.pc-shop-name--empty { cursor: default; color: #d0d0da; }
.pc-title {
    font-size: 13px; font-weight: 600; color: #1a1a2e;
    margin: 0 0 6px; line-height: 1.38;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    min-height: 2.76em;
}
.pc-rating { display: flex; align-items: center; gap: 2px; margin-bottom: 6px; }
.pc-star { width: 11px; height: 11px; flex-shrink: 0; }
.pc-star--on  { color: #f5c518; }
.pc-star--off { color: #e0e0e8; }
.pc-rating-text { font-size: 10px; color: #888; margin-left: 4px; font-weight: 600; }
.pc-rating-count { font-size: 10px; color: #b0b0bc; margin-left: 2px; }
.pc-rating-empty { font-size: 10px; color: #c0c0cc; margin-left: 4px; }
.pc-price-row { display: flex; align-items: baseline; gap: 7px; margin-bottom: 10px; }
.pc-price { font-size: 16px; font-weight: 800; color: #1a1a2e; letter-spacing: -.02em; }
.pc-old-price { font-size: 11px; font-weight: 500; color: #c4c4cc; text-decoration: line-through; }

/* Card actions — one row */
.pc-actions { display: flex; flex-direction: row; gap: 6px; margin-top: auto; align-items: stretch; }
.pc-form-buy { flex: 1; }
.pc-btn {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 5px;
    padding: 7px 10px; border-radius: 10px;
    font-family: 'Manrope', sans-serif; font-size: 12px; font-weight: 600;
    cursor: pointer; transition: background 0.18s, transform 0.14s, box-shadow 0.18s;
    border: none; position: relative; z-index: 2;
}
.pc-btn:active { transform: scale(0.97); }

/* Cart button with counter */
.pc-btn--cart {
    width: 36px; height: 36px; flex-shrink: 0; padding: 0;
    background: #f4f4f8; color: #888;
    border: 1.5px solid #e8e8ee; border-radius: 10px;
    position: relative;
}
.pc-btn--cart:hover { background: #eaf4ff; border-color: #74bdfd; color: #74bdfd; }
.pc-cart-icon-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}
.pc-cart-count {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    background: #ff6b6b;
    color: white;
    font-size: 10px;
    font-weight: 700;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
}

/* Buy — blue, dominant */
.pc-btn--buy {
    background: #74bdfd; color: #fff;
    box-shadow: 0 3px 12px rgba(116,189,253,0.4);
}
.pc-btn--buy:hover { background: #5aa8e5; box-shadow: 0 5px 16px rgba(116,189,253,0.55); }

/* Loading spinner */
.pc-spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.pc-btn-loading {
    display: flex; align-items: center; justify-content: center;
}

/* Particles */
.dot {
    position: absolute; top: 50%; left: 50%;
    width: 5px; height: 5px; background: #ff6b6b;
    border-radius: 50%; opacity: 0; pointer-events: none;
    animation: burst 0.6s ease-out forwards;
}
@keyframes burst {
    0%   { transform: translate(-50%,-50%) scale(1); opacity: 1; }
    100% { transform: rotate(calc(var(--i)*45deg)) translate(-50%,-34px) scale(.3); opacity: 0; }
}
.dot-in {
    position: absolute; top: 50%; left: 50%;
    width: 5px; height: 5px; background: #ff6b6b;
    border-radius: 50%; opacity: 0; pointer-events: none;
    animation: collapse 0.6s ease-in forwards;
}
@keyframes collapse {
    0%   { transform: rotate(calc(var(--i)*45deg)) translate(-50%,-34px) scale(.3); opacity: 1; }
    100% { transform: translate(-50%,-50%) scale(1); opacity: 0; }
}

/* ══════════════════════════════════
   MODAL
══════════════════════════════════ */
.pm-fade-enter { transition: opacity 0.22s ease; }
.pm-fade-from  { opacity: 0; }
.pm-fade-to    { opacity: 1; }
.pm-sheet-enter { transition: opacity 0.28s ease, transform 0.32s cubic-bezier(.22,.68,0,1.1); }
.pm-sheet-from  { opacity: 0; transform: translateY(28px) scale(0.97); }
.pm-sheet-to    { opacity: 1; transform: translateY(0) scale(1); }

.pm-backdrop {
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(6,6,18,0.6); backdrop-filter: blur(8px);
    display: flex; align-items: center; justify-content: center;
    padding: 12px;
}
.pm-sheet {
    background: #fff; border-radius: 22px;
    width: 100%; max-width: 820px;
    height: 540px; max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 32px 80px rgba(0,0,0,0.26);
    font-family: 'Manrope', sans-serif;
    display: flex; flex-direction: column;
}

/* Topbar */
.pm-topbar {
    display: flex; align-items: center; justify-content: flex-end;
    gap: 7px; padding: 12px 13px 0; flex-shrink: 0;
}
.pm-fullpage-btn {
    display: flex; align-items: center; gap: 5px;
    padding: 5px 12px; background: #f4f4f8; border-radius: 100px;
    font-size: 11px; font-weight: 600; color: #555;
    text-decoration: none; transition: background 0.18s, color 0.18s;
}
.pm-fullpage-btn:hover { background: #74bdfd; color: #fff; }
.pm-close {
    width: 30px; height: 30px;
    display: flex; align-items: center; justify-content: center;
    background: #f4f4f8; border-radius: 50%; border: none; cursor: pointer;
    color: #555; transition: background 0.18s, transform 0.24s;
}
.pm-close:hover { background: #ffe0e0; color: #d44; transform: rotate(90deg); }

/* Inner layout */
.pm-inner { display: flex; flex: 1; overflow: hidden; margin-top: 12px; min-height: 0; }

/* Image column */
.pm-image-col {
    flex: 0 0 44%; display: flex; flex-direction: column;
    background: #f6f6f8; overflow: hidden;
}
.pm-main-image-wrap {
    position: relative; flex: 1; overflow: hidden;
}
.pm-image {
    position: absolute; inset: 0;
    width: 100%; height: 100%; object-fit: cover;
    opacity: 0; transition: opacity 0.3s ease, transform 0.55s ease;
}
.pm-image--active { opacity: 1; }
.pm-main-image-wrap:hover .pm-image--active { transform: scale(1.04); }
.pm-no-image {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center; background: #f0f0f3;
}

/* Favorite button in modal */
.pm-fav-btn {
    position: absolute; top: 14px; right: 14px; z-index: 10;
    width: 34px; height: 34px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,0.28); backdrop-filter: blur(8px);
    border-radius: 50%; border: none; cursor: pointer;
    transition: background 0.2s, transform 0.2s;
}
.pm-fav-btn:hover { background: rgba(0,0,0,0.48); transform: scale(1.1); }
.pm-fav-icon { width: 16px; height: 16px; transition: transform 0.3s; }
.pm-fav--active { transform: scale(1.2); }

.pm-badge {
    position: absolute; top: 14px; left: 14px; z-index: 2;
    padding: 3px 10px; background: #ff6b6b; color: #fff;
    font-size: 11px; font-weight: 700; border-radius: 100px;
}
.pm-counter {
    position: absolute; bottom: 12px; right: 12px; z-index: 2;
    padding: 3px 9px;
    background: rgba(0,0,0,0.45); backdrop-filter: blur(6px);
    color: #fff; font-size: 11px; font-weight: 600;
    border-radius: 100px; letter-spacing: .02em;
}

/* Arrows */
.pm-arrow {
    position: absolute; top: 50%; transform: translateY(-50%); z-index: 3;
    width: 32px; height: 32px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,0.85); backdrop-filter: blur(6px);
    border: none; border-radius: 50%; cursor: pointer; color: #333;
    transition: background 0.18s, transform 0.18s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
}
.pm-arrow:hover { background: #fff; transform: translateY(-50%) scale(1.08); }
.pm-arrow--left  { left: 10px; }
.pm-arrow--right { right: 10px; }

/* Thumbnails container */
.pm-thumbs-container {
    position: relative;
    display: flex;
    align-items: center;
    background: #f0f0f3;
    padding: 8px 0;
}
.pm-thumbs {
    display: flex;
    gap: 6px;
    padding: 0 12px;
    overflow-x: auto;
    scrollbar-width: none;
    scroll-behavior: smooth;
    flex: 1;
}
.pm-thumbs::-webkit-scrollbar { display: none; }

/* Thumbnails */
.pm-thumb {
    flex-shrink: 0; width: 52px; height: 52px;
    border-radius: 8px; overflow: hidden;
    border: 2px solid transparent;
    padding: 0; cursor: pointer; background: #fff;
    transition: border-color 0.18s, transform 0.18s;
}
.pm-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.pm-thumb:hover { transform: scale(1.05); }
.pm-thumb--active { border-color: #74bdfd; }

/* Scroll buttons */
.pm-thumb-scroll {
    position: absolute;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #fff;
    border: 1px solid #e8e8ee;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.2s, transform 0.2s;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    z-index: 5;
}
.pm-thumbs-container:hover .pm-thumb-scroll { opacity: 1; }
.pm-thumb-scroll:hover { transform: scale(1.1); background: #f8f8fc; }
.pm-thumb-scroll svg { width: 16px; height: 16px; color: #666; }
.pm-thumb-scroll--left { left: 4px; }
.pm-thumb-scroll--right { right: 4px; }

/* Info column */
.pm-info-col { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-height: 0; }
.pm-info-scroll {
    flex: 1; overflow-y: auto; padding: 4px 22px 14px;
    -webkit-overflow-scrolling: touch;
}
.pm-info-scroll::-webkit-scrollbar { width: 3px; }
.pm-info-scroll::-webkit-scrollbar-thumb { background: #e0e0e8; border-radius: 2px; }

.pm-shop {
    font-size: 11px; font-weight: 500; color: #a0a0a8;
    text-decoration: none; transition: color 0.18s; margin-bottom: 5px; display: block;
}
.pm-shop:hover { color: #74bdfd; }
.pm-title { font-size: 18px; font-weight: 800; color: #1a1a2e; line-height: 1.25; margin: 0 0 10px; }
.pm-rating { display: flex; align-items: center; gap: 3px; margin-bottom: 12px; }
.pm-star { width: 13px; height: 13px; }
.pm-star--on  { color: #f5c518; }
.pm-star--off { color: #e0e0e6; }
.pm-rating-val { font-size: 12px; color: #555; font-weight: 600; margin-left: 4px; }
.pm-review-count { font-size: 11px; color: #a0a0a8; margin-left: 2px; }
.pm-price-block { display: flex; align-items: baseline; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
.pm-price { font-size: 24px; font-weight: 800; color: #1a1a2e; letter-spacing: -.03em; }
.pm-old-price { font-size: 14px; color: #c4c4cc; text-decoration: line-through; font-weight: 500; }
.pm-save-badge {
    padding: 2px 9px; background: #fff0f0; color: #ff6b6b;
    font-size: 10px; font-weight: 700; border-radius: 100px; align-self: center;
}
.pm-meta-row { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px; }
.pm-meta-item {
    display: flex; align-items: center; gap: 4px;
    padding: 3px 10px; background: #f4f4f8; border-radius: 100px;
    font-size: 11px; font-weight: 500; color: #666;
}
.pm-description { font-size: 12px; line-height: 1.72; color: #666; margin: 0; }

/* Sticky buttons */
.pm-actions {
    flex-shrink: 0; display: flex; flex-direction: row; gap: 8px;
    padding: 11px 18px 16px;
    border-top: 1px solid #f0f0f4; background: #fff;
}
.pm-actions form { flex: 1; }
.pm-btn {
    width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px;
    padding: 11px 14px; border-radius: 13px;
    font-family: 'Manrope', sans-serif; font-size: 13px; font-weight: 700;
    cursor: pointer; transition: background 0.18s, transform 0.14s, box-shadow 0.18s; border: none;
}
.pm-btn:active { transform: scale(0.98); }

/* Cart button in modal */
.pm-btn--cart {
    background: #f4f4f8; color: #666;
    border: 1.5px solid #e8e8ee;
    width: auto; padding: 11px 16px; white-space: nowrap;
    position: relative;
}
.pm-btn--cart:hover { background: #eaf4ff; border-color: #74bdfd; color: #74bdfd; }
.pm-cart-icon-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.pm-cart-count {
    position: absolute;
    top: -12px;
    right: -8px;
    min-width: 20px;
    height: 20px;
    padding: 0 5px;
    background: #ff6b6b;
    color: white;
    font-size: 11px;
    font-weight: 700;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
}

.pm-btn--buy { background: #74bdfd; color: #fff; box-shadow: 0 4px 16px rgba(116,189,253,0.4); }
.pm-btn--buy:hover { background: #5aa8e5; box-shadow: 0 6px 20px rgba(116,189,253,0.52); }

/* ── Mobile ── */
@media (max-width: 640px) {
    .pm-backdrop {
        align-items: center;
        padding: 12px;
    }

    .pm-sheet {
        border-radius: 20px;
        width: 100%; max-width: 100%;
        height: 86vh; max-height: 86vh;
        display: flex; flex-direction: column;
        overflow: hidden;
    }

    .pm-sheet-from { opacity: 0; transform: scale(0.95) translateY(10px); }
    .pm-sheet-to   { opacity: 1; transform: scale(1) translateY(0); }

    .pm-topbar {
        position: relative;
        padding-top: 12px;
        flex-shrink: 0;
    }
    .pm-topbar::before { display: none; }

    .pm-inner {
        flex: 1;
        flex-direction: column;
        overflow-y: auto;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        min-height: 0;
        padding-bottom: 0;
    }

    .pm-image-col {
        flex: none;
        width: 100%;
        display: block;
    }
    .pm-main-image-wrap {
        position: relative;
        width: 100%;
        height: 280px;
        overflow: hidden;
        background: #f6f6f8;
    }
    .pm-main-image-wrap .pm-image {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        object-fit: cover;
        opacity: 0; transition: opacity 0.3s ease;
    }
    .pm-main-image-wrap .pm-image.pm-image--active { opacity: 1; }

    .pm-thumbs {
        justify-content: center;
        padding: 6px 8px;
    }
    .pm-thumb {
        width: 48px;
        height: 48px;
    }
    .pm-thumb-scroll { display: none; }

    .pm-fav-btn {
        top: 12px;
        right: 12px;
        width: 38px;
        height: 38px;
    }

    .pm-counter {
        bottom: 16px;
        right: 16px;
        padding: 4px 10px;
        font-size: 12px;
    }

    .pm-info-col { flex: none; display: block; overflow: visible; }
    .pm-info-scroll { overflow: visible; padding: 14px 16px 6px; }

    .pm-actions {
        position: sticky;
        bottom: 0;
        z-index: 10;
        flex-direction: column;
        gap: 7px;
        padding: 10px 14px 14px;
        background: #fff;
        border-top: 1px solid #f0f0f4;
        box-shadow: 0 -4px 14px rgba(0,0,0,.07);
    }
    .pm-actions form { flex: none; width: 100%; }
    .pm-btn--cart { width: 100%; padding: 11px 14px; }

    .pm-title { font-size: 16px; }
    .pm-price { font-size: 22px; }
    .pm-fullpage-btn span { display: none; }
}

/* Десктоп: умные миниатюры */
@media (min-width: 641px) {
    .pm-thumbs {
        justify-content: center;
        padding: 8px 16px;
    }
    .pm-thumb {
        width: 52px;
        height: 52px;
    }
    .pm-thumbs-container {
        padding: 8px 16px;
    }
}
</style>