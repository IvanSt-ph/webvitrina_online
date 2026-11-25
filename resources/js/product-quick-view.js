// Shared requests retain the initiating ID even if the quick view changes product.
const favoriteRequests = new Set()

function requireAuthentication() {
  if (document.body.dataset.authenticated === 'true') return true
  window.showSiteToast('Войдите в аккаунт, чтобы добавлять товары в избранное или корзину.', 'info')
  return false
}

async function post(url) {
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
  })
  if (response.status === 401) {
    window.showSiteToast('Войдите в аккаунт, чтобы продолжить.', 'info')
  }
  if (!response.ok) throw new Error('Product action failed')
  return response.json()
}

function actions() {
  return {
    anim: '',
    animationTimer: null,
    async addToCart() {
      if (!requireAuthentication()) return
      const productId = this.productId
      try {
        const data = await post(this.cartUrl)
        if (data.success) {
          window.dispatchEvent(new CustomEvent('product-cart-quantity-updated', {
            detail: { productId, quantity: data.quantity },
          }))
        }
      } catch { /* Keep the last confirmed state on an unsuccessful request. */ }
    },
    async toggleFavorite() {
      if (!requireAuthentication()) return
      const productId = this.productId
      if (favoriteRequests.has(productId)) return
      favoriteRequests.add(productId)
      try {
        const data = await post(this.favoriteUrl)
        if (data.status !== 'error' && typeof data.favorite === 'boolean') {
          window.dispatchEvent(new CustomEvent('product-favorite-updated', {
            detail: { productId, active: data.favorite },
          }))
        }
      } catch { /* A guest/expired session must not produce a false success. */ }
      finally { favoriteRequests.delete(productId) }
    },
    syncFavorite(detail) {
      if (Number(detail.productId) !== this.productId) return
      clearTimeout(this.animationTimer)
      this.anim = detail.active ? 'explode' : 'implode'
      this.active = detail.active
      this.animationTimer = setTimeout(() => { this.anim = '' }, 600)
    },
    destroy() { clearTimeout(this.animationTimer) },
  }
}

export function productCard(state) {
  return {
    ...actions(),
    ...state,
    openQuickView() {
      // Parse metadata only on demand; no image nodes or extra requests per card.
      let product
      try {
        const json = this.$root.dataset.quickView
        if (!json) throw new Error('Missing data-quick-view on the product card')
        product = JSON.parse(json)
        if (!product || typeof product !== 'object' || Array.isArray(product)
          || !Array.isArray(product.gallery) || product.gallery.length === 0
          || !product.gallery.every(src => typeof src === 'string' && src.length > 0)
          || !['title', 'url', 'checkoutUrl'].every(key => typeof product[key] === 'string' && product[key].length > 0)) {
          throw new Error('Invalid quick-view product data')
        }
      } catch (error) {
        console.error('Unable to open product quick view', { productId: this.productId, error })
        window.showSiteToast('Не удалось открыть быстрый просмотр. Обновите страницу и попробуйте снова.', 'error')
        return
      }
      window.dispatchEvent(new CustomEvent('product-quick-view', {
        detail: {
          product,
          productId: this.productId,
          cartCount: this.cartCount,
          active: this.active,
          cartUrl: this.cartUrl,
          favoriteUrl: this.favoriteUrl,
        },
      }))
    },
  }
}

export function productQuickView() {
  return {
    ...actions(),
    open: false,
    product: null,
    productId: null,
    cartCount: 0,
    active: false,
    cartUrl: null,
    favoriteUrl: null,
    gallery: [],
    current: 0,
    touchStartX: null,
    show(detail) {
      clearTimeout(this.animationTimer)
      this.anim = ''
      Object.assign(this, detail)
      this.gallery = detail.product.gallery
      this.current = 0
      this.touchStartX = null
      this.open = true
      this.$nextTick(() => {
        if (this.$refs.thumbnails) this.$refs.thumbnails.scrollLeft = 0
        this.$refs.info?.scrollTo(0, 0)
      })
    },
    prev() {
      if (this.gallery.length) this.current = (this.current - 1 + this.gallery.length) % this.gallery.length
    },
    next() {
      if (this.gallery.length) this.current = (this.current + 1) % this.gallery.length
    },
    onTouchStart(event) { this.touchStartX = event.touches[0].clientX },
    onTouchEnd(event) {
      if (this.touchStartX === null) return
      const dx = event.changedTouches[0].clientX - this.touchStartX
      this.touchStartX = null
      if (Math.abs(dx) > 40) dx < 0 ? this.next() : this.prev()
    },
  }
}
