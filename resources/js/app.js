import Alpine from 'alpinejs'

window.Alpine = Alpine

Alpine.store('specs', { open: false })

Alpine.data('appShell', () => ({
  open: false,
  openSearch: false,
  openFilters: false,
  openSettings: false,

  clearFilters() {
    const url = new URL(window.location.href)
    url.searchParams.delete('country_id')
    url.searchParams.delete('city_id')
    window.location.href = url.toString()
  },
}))

Alpine.data('mobileHeader', () => ({
  search: document.body.dataset.searchQuery ?? '',
  filtersOpen: false,
  settingsOpen: false,
  currency: document.body.dataset.currency ?? 'PRB',

  init() {
    // Reserved for future mobile header initialization.
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

// 🔔 Единый toast для быстрых уведомлений
window.showAppToast = (text, type = 'success') => {
  const existing = document.querySelector('.toast');
  if (existing) existing.remove();

  const styleId = 'app-toast-style';
  if (!document.getElementById(styleId)) {
    const style = document.createElement('style');
    style.id = styleId;
    style.textContent = `
      .toast {
        position: fixed;
        right: 16px;
        top: 80px;
        padding: 10px 18px;
        color: white;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 12px 30px -8px rgba(15,23,42,.28);
        animation: appToastIn .25s ease;
        z-index: 99999;
        backdrop-filter: blur(10px);
        background: rgba(30, 41, 59, .96);
      }
      .toast-success { border-left: 3px solid #10b981; }
      .toast-error { background: rgba(239, 68, 68, .96); border-left: 3px solid #fecaca; }
      @keyframes appToastIn {
        from { opacity: 0; transform: translateX(24px); }
        to { opacity: 1; transform: translateX(0); }
      }
    `;
    document.head.appendChild(style);
  }

  const el = document.createElement('div');
  el.className = 'toast ' + (type === 'error' ? 'toast-error' : 'toast-success');
  el.innerHTML = `
    <div class="flex items-center gap-2">
      <i class="${type === 'error' ? 'ri-error-warning-line' : 'ri-checkbox-circle-line'} text-base"></i>
      <span>${text}</span>
    </div>
  `;
  document.body.appendChild(el);

  setTimeout(() => {
    el.style.opacity = '0';
    el.style.transform = 'translateX(20px)';
    setTimeout(() => el.remove(), 300);
  }, 2500);
};

// 🧮 Актуализация счетчиков товаров на карточках после возврата назад
// Браузер может восстановить главную из bfcache, поэтому Blade-значения становятся устаревшими.
window.refreshProductCartQuantities = async () => {
  const productCards = document.querySelectorAll('.pc-card')
  if (!productCards.length) return

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

    if (!response.ok) return

    const data = await response.json()
    window.dispatchEvent(new CustomEvent('cart-quantities-refreshed', {
      detail: { quantities: data.quantities || {} },
    }))
  } catch (error) {
    void error
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



// 🧩 Подключаем твои доп. скрипты (пример: форма продавца)
// ------------------------------------------------------
import './seller-product-form.js';


// ☎️ intl-tel-input — плагин для телефонов
// ------------------------------------------------------
// Подключаем CSS и JS плагина (через Vite)
import 'intl-tel-input/build/css/intlTelInput.css';
import intlTelInput from 'intl-tel-input';
window.intlTelInput = intlTelInput;


// 🚀 Инициализация всех <input type="tel">
// ------------------------------------------------------
// Автоматически применяет intl-tel-input ко всем телефонным полям
document.addEventListener('DOMContentLoaded', () => {

  // Находим все поля типа "телефон"
  const inputs = document.querySelectorAll('input[type="tel"]');

  inputs.forEach((input) => {
    // Некоторые страницы инициализируют телефонное поле сами,
    // чтобы использовать настройки, отличные от глобальных.
    if (input.dataset.intlManual === 'true') return;

    // 🔒 Предотвращаем повторную инициализацию
    if (input.dataset.intlInitialized) return;
    input.dataset.intlInitialized = 'true';

    // ✅ Инициализация intl-tel-input для текущего поля
    const iti = window.intlTelInput(input, {
      // 🌍 Настройки отображения
      initialCountry: 'md',                 // Страна по умолчанию — 🇲🇩 Молдова
      preferredCountries: ['md', 'ua', 'ru'], // Три популярных страны вверху списка
      separateDialCode: true,               // Отображает код страны отдельно от номера
      nationalMode: false,                  // Ввод всегда в международном формате (+373 ...)
      autoPlaceholder: 'aggressive',        // Подсказка вида: +373 777 00 000
      showSelectedDialCode: true,           // Показывает код страны рядом с флагом

      // ⚙️ Подключаем утилиты для форматирования и валидации
      utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@25.12.5/build/js/utils.js',
    });


    // 🟢 Автоопределение страны при вводе кода
    // ------------------------------------------------------
    // Когда пользователь начинает вводить +373 / +380 / +7
    // — автоматически меняем флаг страны
    input.addEventListener('input', () => {
      const val = input.value.trim();

      if (val.startsWith('+373')) iti.setCountry('md'); // 🇲🇩 Молдова
      else if (val.startsWith('+380')) iti.setCountry('ua'); // 🇺🇦 Украина
      else if (val.startsWith('+7')) iti.setCountry('ru'); // 🇷🇺 Россия
    });


    // ➕ При фокусе — если пользователь кликает в пустое поле
    // ------------------------------------------------------
    // и там нет "+", мы автоматически добавляем его.
    input.addEventListener('focus', () => {
      if (!input.value.startsWith('+')) input.value = '+';
    });


    // ✨ (Необязательно) — автоформатирование при потере фокуса
    // ------------------------------------------------------
    // Преобразует номер в красивый международный формат при blur
    input.addEventListener('blur', () => {
      if (window.intlTelInputUtils && iti.isValidNumber()) {
        const formatted = iti.getNumber(intlTelInputUtils.numberFormat.INTERNATIONAL);
        if (formatted) input.value = formatted;
      }
    });
  });
});


// 🩵 Принудительный запуск Alpine после сборки Vite
// document.addEventListener('DOMContentLoaded', () => {
//   if (window.Alpine && !window.Alpine.initialized) {
//     window.Alpine.start();
//   }
// });
