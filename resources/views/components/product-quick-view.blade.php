<div x-data="productQuickView"
     @product-quick-view.window="show($event.detail)"
     @keydown.escape.window="open = false"
     @cart-quantities-refreshed.window="cartCount = Number($event.detail.quantities[productId] || 0)"
     @product-cart-quantity-updated.window="if (Number($event.detail.productId) === productId) cartCount = Number($event.detail.quantity || 0)"
     @product-favorite-updated.window="syncFavorite($event.detail)">
    <template x-teleport="body">
        <div x-show="open" class="pm-backdrop" style="display:none;"
             @click.self="open = false"
             x-transition:enter="pm-fade-enter"
             x-transition:enter-start="pm-fade-from"
             x-transition:enter-end="pm-fade-to"
             x-transition:leave="pm-fade-enter"
             x-transition:leave-start="pm-fade-to"
             x-transition:leave-end="pm-fade-from">
            <template x-if="product">
            <div class="pm-sheet"
                 x-transition:enter="pm-sheet-enter"
                 x-transition:enter-start="pm-sheet-from"
                 x-transition:enter-end="pm-sheet-to"
                 x-transition:leave="pm-sheet-enter"
                 x-transition:leave-start="pm-sheet-to"
                 x-transition:leave-end="pm-sheet-from">

                <div class="pm-topbar">
                    <a :href="product.url" class="pm-fullpage-btn">
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
                             @touchend="onTouchEnd($event)"
                             @touchcancel="touchStartX = null">

                                <template x-for="(src, idx) in gallery" :key="productId + ':' + idx">
                                    <img data-image-fallback="{{ asset(\App\Models\Product::IMAGE_FALLBACK_ASSET) }}" :src="src"
                                         :alt="product.title"
                                         class="pm-image"
                                         :class="current === idx ? 'pm-image--active' : ''"
                                         loading="lazy"
                                         decoding="async"/>
                                </template>

                            {{-- Favorite button in modal --}}
                            <button
                                type="button"
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

                                <div class="pm-badge" x-show="product.hasDiscount" x-text="product.discount"></div>

                            {{-- Photo counter --}}

                                <div class="pm-counter" x-show="gallery.length > 1" x-text="(current + 1) + ' / ' + gallery.length"></div>

                            {{-- Prev/Next arrows (only if multiple) --}}

                                <button @click.stop="prev()" x-show="gallery.length > 1" class="pm-arrow pm-arrow--left">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                    </svg>
                                </button>
                                <button @click.stop="next()" x-show="gallery.length > 1" class="pm-arrow pm-arrow--right">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>

                        </div>

                        {{-- Thumbnails --}}

                            <div class="pm-thumbs-container" x-show="gallery.length > 1">
                                <div class="pm-thumbs" x-ref="thumbnails">
                                    <template x-for="(src, idx) in gallery" :key="productId + ':' + idx">
                                        <button @click.stop="current = idx; $el.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' })"
                                                class="pm-thumb"
                                                :class="current === idx ? 'pm-thumb--active' : ''">
                                            <img data-image-fallback="{{ asset(\App\Models\Product::IMAGE_FALLBACK_ASSET) }}" :src="src" alt="" loading="lazy" decoding="async"/>
                                        </button>
                                    </template>
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

                    </div>

                    {{-- Info column --}}
                    <div class="pm-info-col">
                        <div class="pm-info-scroll" x-ref="info">

                                <a :href="product.shopUrl" x-show="product.shopName" class="pm-shop" x-text="product.shopName"></a>

                            <h2 class="pm-title" x-text="product.title"></h2>

                            <div class="pm-rating">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="pm-star" :class="{{ $i }} <= product.avg ? 'pm-star--on' : 'pm-star--off'" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.729 1.516 8.234L12 18.896l-7.452 4.373 1.516-8.234L0 9.306l8.332-1.151z"/>
                                    </svg>
                                @endfor
                                <span class="pm-rating-val" x-text="product.rating"></span>
                                <span class="pm-review-count" x-text="product.reviews"></span>
                            </div>

                            <div class="pm-price-block">
                                <span class="pm-price" x-text="product.price"></span>

                                    <span class="pm-old-price" x-show="product.hasDiscount" x-text="product.oldPrice"></span>
                                    <span class="pm-save-badge" x-show="product.hasDiscount" x-text="product.discount"></span>

                            </div>

                                <div class="pm-meta-row" x-show="product.location || product.category">

                                        <div class="pm-meta-item" x-show="product.location">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21c4.8-4.9 8-8.4 8-11.5a8 8 0 1 0-16 0c0 3.1 3.2 6.6 8 11.5z"/>
                                                <circle cx="12" cy="10" r="2" fill="currentColor" stroke="none"/>
                                            </svg>
                                            <span x-text="product.location"></span>
                                        </div>

                                        <div class="pm-meta-item" x-show="product.category">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z"/>
                                            </svg>
                                            <span x-text="product.category"></span>
                                        </div>

                                </div>

                                <p class="pm-description" x-show="product.description" x-text="product.description"></p>

                        </div>

                        {{-- Sticky footer --}}
                        <div class="pm-actions">
                            {{-- Кнопка корзины в модалке --}}
                            <button
                                type="button"
                                @click.stop="addToCart()"
                                class="pm-btn pm-btn--cart"
                            >
                                <div class="pm-cart-icon-wrapper">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4zM3 6h18M16 10a4 4 0 01-8 0"/>
                                    </svg>

                                    {{-- Счетчик --}}
                                    <template x-if="cartCount > 0">
                                        <span class="pm-cart-count" x-text="cartCount"></span>
                                    </template>
                                    <span x-show="cartCount === 0">В корзину</span>
                                </div>
                            </button>

                            <form method="POST" :action="product.checkoutUrl">
                                @csrf
                                <button class="pm-btn pm-btn--buy">Купить сейчас</button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
            </template>
        </div>
    </template>
</div>
