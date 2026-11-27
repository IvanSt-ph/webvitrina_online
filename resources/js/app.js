import './runtime-libraries';
import './image-fallback';
import './phone-inputs';
import Alpine from 'alpinejs'
import categoryMenu from './category-menu'
import orderCancellationReason from './order-cancellation-reason'
import recommendedCarousel, { categoryCarousel } from './recommended-carousel'
import { productCard, productQuickView } from './product-quick-view'
import '../css/recommended-carousel.css'

window.Alpine = Alpine

window.showSiteToast = (message, type = 'info') => {
  window.dispatchEvent(new CustomEvent('wv-toast', {
    detail: { message, type },
  }))
}

Alpine.store('specs', { open: false })

Alpine.data('categoryMenu', categoryMenu)
Alpine.data('categoryCarousel', () => categoryCarousel(Alpine))

Alpine.data('appShell', () => ({
  open: false,
  openSearch: false,
  openFilters: false,
  openSettings: false,

  clearFilters() {
    const url = new URL(window.location.href)
    url.searchParams.delete('country_id')
    url.searchParams.delete('city_id')
    url.searchParams.set('clear_location', '1')
    window.location.href = url.toString()
  },
}))

Alpine.data('mobileHeader', () => ({
  search: document.body.dataset.searchQuery ?? '',
  settingsOpen: false,
  currency: document.body.dataset.currency ?? 'PRB',
  panelTop: 0,
  panelHeight: 0,

  init() {
    this.measurePanel = () => {
      const rect = this.$refs.topbar.getBoundingClientRect()
      const viewport = window.visualViewport
      let bottom = (viewport?.offsetTop ?? 0) + (viewport?.height ?? window.innerHeight)
      document.querySelectorAll('[data-mobile-bottom-nav], [data-mobile-bottom-seller-nav]').forEach(nav => {
        const bounds = nav.getBoundingClientRect()
        if (bounds.height && bounds.top > 0) bottom = Math.min(bottom, bounds.top)
      })
      this.panelTop = Math.max(0, rect.bottom)
      this.panelHeight = Math.max(0, bottom - this.panelTop)
      if (window.innerWidth >= 1024) this.settingsOpen = false
    }
    this.panelObserver = new ResizeObserver(this.measurePanel)
    this.panelObserver.observe(this.$refs.topbar)
    window.addEventListener('resize', this.measurePanel)
    window.addEventListener('scroll', this.measurePanel, { passive: true })
    window.visualViewport?.addEventListener('resize', this.measurePanel)
    window.visualViewport?.addEventListener('scroll', this.measurePanel)
    this.$nextTick(this.measurePanel)
  },

  openSettings() {
    this.measurePanel()
    this.settingsOpen = true
    this.$nextTick(() => this.$refs.settingsPanel.focus({ preventScroll: true }))
  },

  closeSettings() {
    this.settingsOpen = false
    this.$refs.settingsTrigger.focus({ preventScroll: true })
  },

  destroy() {
    this.panelObserver.disconnect()
    window.removeEventListener('resize', this.measurePanel)
    window.removeEventListener('scroll', this.measurePanel)
    window.visualViewport?.removeEventListener('resize', this.measurePanel)
    window.visualViewport?.removeEventListener('scroll', this.measurePanel)
  },

  submitSearch() {
    if (this.search.length === 0) {
      const url = new URL(window.location.href)
      url.searchParams.delete('q')
      window.location.href = url.toString()
    }
  },

  setCurrency(code) {
    this.currency = code

    fetch('/currency', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ currency: code }),
    }).then((response) => {
      if (!response.ok) throw new Error('Currency update failed')
      window.location.reload()
    })
  },
}))

Alpine.data('orderCancellationReason', orderCancellationReason)
Alpine.data('recommendedCarousel', () => recommendedCarousel(Alpine))
Alpine.data('productCard', productCard)
Alpine.data('productQuickView', productQuickView)

Alpine.start()

// 🧩 Основная инициализация Bootstrap (Laravel)
// Подключает axios, csrf-token и базовые зависимости проекта
import './bootstrap';

// 🛒 Анимация «добавить в корзину»
// ------------------------------------------------------
// При клике на кнопку "добавить в корзину" создаётся
// маленький кружок (dot), который летит в иконку корзины.
document.addEventListener('click', (e) => {
  // Находим кнопку "Добавить в корзину"
  const btn = e.target.closest('form[action*="/cart/add/"] button');
  if (!btn) return; // Если не нашли — выходим

  // Создаём "летающую" точку
  const dot = document.createElement('span');
  dot.className = 'fixed w-3 h-3 rounded-full bg-indigo-600 z-[60] pointer-events-none';

  // Получаем координаты кнопки
  const rect = btn.getBoundingClientRect();
  dot.style.left = rect.left + rect.width / 2 + 'px';
  dot.style.top = rect.top + 'px';
  document.body.appendChild(dot);

  // Находим иконку корзины
  const cart = document.querySelector('a[href="/cart"]');
  const end = cart?.getBoundingClientRect();

  // Если нашли корзину — анимируем полёт точки
  if (end) {
    dot.animate(
      [
        { transform: 'translate(0,0)', opacity: 1 },
        {
          transform: `translate(${end.left - rect.left}px, ${end.top - rect.top}px) scale(0.3)`,
          opacity: 0.1,
        },
      ],
      { duration: 600, easing: 'cubic-bezier(.17,.67,.83,.67)' }
    ).onfinish = () => dot.remove();
  } else {
    // Если не нашли корзину — просто удаляем точку
    setTimeout(() => dot.remove(), 600);
  }
});

// Backward-compatible entry point for screens that still call showAppToast.
// Rendering and semantic colors are owned by the shared <x-toast-stack>.
window.showAppToast = (message, type = 'success') => {
  window.showSiteToast(message, type)
}

// 🧮 Актуализация счетчиков товаров на карточках после возврата назад
// Браузер может восстановить главную из bfcache, поэтому Blade-значения становятся устаревшими.
let cartQuantitiesInFlight = false
let cartQuantitiesUnauthorized = false

window.refreshProductCartQuantities = async () => {
  if (document.body.dataset.authenticated !== 'true'
    || cartQuantitiesUnauthorized || cartQuantitiesInFlight) return

  const productCards = document.querySelectorAll('.pc-card')
  if (!productCards.length) return

  cartQuantitiesInFlight = true
  try {
    const url = new URL('/cart-quantities', window.location.origin)
    url.searchParams.set('_', Date.now().toString())

    const response = await fetch(url.toString(), {
      cache: 'no-store',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Cache-Control': 'no-cache',
      },
    })

    if (response.status === 401) {
      // A restored page may outlive its session. Only a new document resets this.
      cartQuantitiesUnauthorized = true
      return
    }
    if (!response.ok) return

    const data = await response.json()
    window.dispatchEvent(new CustomEvent('cart-quantities-refreshed', {
      detail: { quantities: data.quantities || {} },
    }))
  } catch (error) {
    void error
  } finally {
    cartQuantitiesInFlight = false
  }
}

window.addEventListener('pageshow', () => {
  window.refreshProductCartQuantities()
})

document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'visible') {
    window.refreshProductCartQuantities()
  }
})
