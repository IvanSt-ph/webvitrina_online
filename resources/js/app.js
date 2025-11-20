import Alpine from 'alpinejs'

window.Alpine = Alpine
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
