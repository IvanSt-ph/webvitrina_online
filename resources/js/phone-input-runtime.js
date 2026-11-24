import 'intl-tel-input/build/css/intlTelInput.css';
import intlTelInput from 'intl-tel-input';
import itiFlags from 'intl-tel-input/build/img/flags.webp?url';
import itiFlags2x from 'intl-tel-input/build/img/flags@2x.webp?url';
import itiGlobe from 'intl-tel-input/build/img/globe.webp?url';
import itiGlobe2x from 'intl-tel-input/build/img/globe@2x.webp?url';
window.intlTelInput = intlTelInput;
window.loadIntlTelInputUtils = () => import('intl-tel-input/utils');

document.documentElement.style.setProperty('--iti-path-flags-1x', `url("${itiFlags}")`);
document.documentElement.style.setProperty('--iti-path-flags-2x', `url("${itiFlags2x}")`);
document.documentElement.style.setProperty('--iti-path-globe-1x', `url("${itiGlobe}")`);
document.documentElement.style.setProperty('--iti-path-globe-2x', `url("${itiGlobe2x}")`);


// 🚀 Инициализация всех <input type="tel">
// ------------------------------------------------------
// Автоматически применяет intl-tel-input ко всем телефонным полям
export function initializeAutomaticPhoneInputs(root = document) {

  // Находим все поля типа "телефон"
  const inputs = [...(root.matches?.('input[type="tel"]') ? [root] : []), ...root.querySelectorAll('input[type="tel"]')];

  return Promise.all(inputs.map((input) => {
    // Некоторые страницы инициализируют телефонное поле сами,
    // чтобы использовать настройки, отличные от глобальных.
    if (input.dataset.intlManual === 'true') return;

    // 🔒 Предотвращаем повторную инициализацию
    if (input.dataset.intlInitialized) return;
    return window.initializePhoneInput(input, () => {

      // ✅ Инициализация intl-tel-input для текущего поля
      const iti = window.intlTelInput(input, {
        // 🌍 Настройки отображения
        initialCountry: 'md',                 // Страна по умолчанию — 🇲🇩 Молдова
        countryOrder: ['md', 'ua', 'ro', 'ru'], // Популярные страны вверху списка
        separateDialCode: true,               // Отображает код страны отдельно от номера
        autoPlaceholder: 'aggressive',        // Подсказка вида: +373 777 00 000
        formatAsYouType: true,
        strictMode: true,
        showFlags: true,

        // Карточка регистрации имеет overflow-hidden. Рендерим desktop-dropdown
        // в body, иначе список стран визуально обрезается границами карточки.
        dropdownContainer: document.body,

        // ⚙️ Подключаем утилиты для форматирования и валидации
        loadUtils: window.loadIntlTelInputUtils,
      });


      // ✨ (Необязательно) — автоформатирование при потере фокуса
      // ------------------------------------------------------
      // Преобразует номер в красивый международный формат при blur
      input.addEventListener('blur', () => {
        if (window.intlTelInput.utils && iti.isValidNumber()) {
          const formatted = iti.getNumber(window.intlTelInput.utils.numberFormat.INTERNATIONAL);
          if (formatted) input.value = formatted;
        }
      });

      const form = input.closest('form');
      if (form) {
        form.addEventListener('submit', () => {
          // getNumber() без format возвращает полный международный номер и не
          // теряет dial code при separateDialCode, даже если utils ещё грузятся.
          const fullNumber = iti.getNumber();
          if (fullNumber) input.value = fullNumber;
        });
      }
      return iti;
    });
  }));
}
