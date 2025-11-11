
import { getCurrencyRates } from './currency-rates.js';

// resources/js/seller-product-form.js

document.addEventListener('DOMContentLoaded', () => {
    
  // ===============================================================
  // === 🏷 КАТЕГОРИИ: каскадная подгрузка и hidden category_id =====
  // ===============================================================
  const catWrapper = document.getElementById('categories-wrapper');
  const catHidden = document.getElementById('category_id');
  const rootSelect = catWrapper ? catWrapper.querySelector('#category-root') : null;

  function removeNextSelects(currentSelect) {
    let next = currentSelect.nextElementSibling;
    while (next && next.tagName === 'SELECT') {
      next.remove();
      next = currentSelect.nextElementSibling;
    }
  }

function loadChildren(parentId, afterSelect) {
  removeNextSelects(afterSelect);
  catHidden.value = parentId || '';

  if (!parentId) return;

  // ⚡ Используем кэшированный JSON всех категорий
  const children = window.allCategories.filter(c => c.parent_id === Number(parentId));
  if (!children.length) return;

  const select = document.createElement('select');
  select.className =
    'w-full border-gray-200 rounded-lg p-3 mb-2 focus:outline-none focus:ring-2 focus:ring-indigo-200 opacity-0 transition duration-300';
  select.innerHTML = `<option value="">-- выберите подкатегорию --</option>`;

  children.forEach(cat => {
    const opt = document.createElement('option');
    opt.value = cat.id;
    opt.textContent = cat.name;
    select.appendChild(opt);
  });

  select.addEventListener('change', e => {
    catHidden.value = e.target.value || parentId;
    loadChildren(e.target.value, select);
  });

  afterSelect.insertAdjacentElement('afterend', select);
  requestAnimationFrame(() => select.classList.remove('opacity-0'));
}


  if (rootSelect) {
    rootSelect.addEventListener('change', e => {
      const id = e.target.value;
      catHidden.value = id || '';
      loadChildren(id, e.target);
    });
  }
// ===============================================================
// === 🌍 ЛОКАЦИЯ: Загрузка городов по стране (с кэшированием) ====
// ===============================================================
const countrySelect = document.getElementById('country');
const citySelect = document.getElementById('city');
const wrapperEl = document.querySelector('[data-country]');
const preCountryId = wrapperEl?.dataset.country || null;
const preCityId = wrapperEl?.dataset.city || null;

// 🧠 Кэш стран -> массив городов
const cacheCities = new Map();
let citiesLoaded = false; // защита от повторной подгрузки

async function loadCities(countryId, selectedCityId = null) {
  if (!citySelect || !countryId) return;

  // очищаем список перед загрузкой
  citySelect.innerHTML = '<option value="">-- выберите город --</option>';

  // 1️⃣ Проверяем кэш
  if (cacheCities.has(countryId)) {
    renderCities(cacheCities.get(countryId), selectedCityId);
    return;
  }

  // 2️⃣ Если нет в кэше — грузим с сервера
  try {
    const res = await fetch(`/countries/${countryId}/cities`);
    if (!res.ok) return;
    const cities = await res.json();

    // фильтруем дубли
    const uniqueCities = Array.from(new Map(cities.map(c => [c.id, c])).values());

    // сохраняем в кэш
    cacheCities.set(countryId, uniqueCities);

    // отрисовываем
    renderCities(uniqueCities, selectedCityId);
  } catch (error) {
    console.error('Ошибка загрузки городов:', error);
  }
}

function renderCities(cities, selectedCityId = null) {
  citySelect.innerHTML = '<option value="">-- выберите город --</option>';
  cities.forEach(c => {
    const opt = document.createElement('option');
    opt.value = c.id;
    opt.textContent = c.name;
    if (selectedCityId && String(selectedCityId) === String(c.id)) {
      opt.selected = true;
    }
    citySelect.appendChild(opt);
  });
}

if (countrySelect) {
  countrySelect.addEventListener('change', () => {
    citiesLoaded = false;
    loadCities(countrySelect.value, null);
  });

  if (preCountryId && !citiesLoaded) {
    citiesLoaded = true;
    countrySelect.value = preCountryId;
    loadCities(preCountryId, preCityId);
  }
}


  // ===============================================================
// === 🗺️ КАРТА + ПОИСК АДРЕСА (Leaflet + Nominatim) =============
// ===============================================================
const mapEl = document.getElementById('map');

function initMap() {
  if (!mapEl || typeof L === 'undefined') return;

  const initialLat = parseFloat(mapEl.dataset.lat || 47.0105);
  const initialLng = parseFloat(mapEl.dataset.lng || 28.8638);
  const initialZoom = parseInt(mapEl.dataset.zoom || 7);

  const latInput = document.getElementById('latitude');
  const lngInput = document.getElementById('longitude');
  const addressEl = document.getElementById('address');
  const searchBtn = document.getElementById('searchAddress');
  const errorBox = document.getElementById('addressError');
  const countrySelect = document.getElementById('country');
  const citySelect = document.getElementById('city');

  const map = L.map('map').setView([initialLat, initialLng], initialZoom);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map);

  const marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

  const updateCoords = latlng => {
    if (latInput) latInput.value = latlng.lat.toFixed(6);
    if (lngInput) lngInput.value = latlng.lng.toFixed(6);
  };
  updateCoords(marker.getLatLng());

  async function reverseGeocode(lat, lng) {
    try {
      const res = await fetch(
        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
        { headers: { 'Accept-Language': 'ru' } }
      );
      const data = await res.json();
      if (data?.address && addressEl) {
        const addr = [
          data.address.road,
          data.address.house_number,
          data.address.city || data.address.town || data.address.village,
          data.address.country,
        ]
          .filter(Boolean)
          .join(', ');
        addressEl.value = addr;
      }
    } catch (e) {
      console.warn('Ошибка обратного геокодирования', e);
    }
  }

  marker.on('dragend', e => {
    const latlng = e.target.getLatLng();
    updateCoords(latlng);
    reverseGeocode(latlng.lat, latlng.lng);
  });

  map.on('click', e => {
    marker.setLatLng(e.latlng);
    updateCoords(e.latlng);
    reverseGeocode(e.latlng.lat, e.latlng.lng);
  });

  // 🔍 Поиск адреса вручную
  if (searchBtn) {
    searchBtn.addEventListener('click', async () => {
      const query = addressEl?.value?.trim();
      if (!query) {
        errorBox.textContent = 'Введите адрес для поиска';
        errorBox.classList.remove('hidden');
        return;
      }
      try {
        const res = await fetch(
          `https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=1&q=${encodeURIComponent(
            query
          )}`,
          { headers: { 'Accept-Language': 'ru' } }
        );
        const data = await res.json();
        if (!data.length) {
          errorBox.textContent = 'Адрес не найден';
          errorBox.classList.remove('hidden');
          return;
        }
        const { lat, lon } = data[0];
        const coords = { lat: parseFloat(lat), lng: parseFloat(lon) };
        map.setView(coords, 14);
        marker.setLatLng(coords);
        updateCoords(coords);
        errorBox.classList.add('hidden');
      } catch (e) {
        errorBox.textContent = 'Ошибка поиска адреса';
        errorBox.classList.remove('hidden');
      }
    });
  }

  // 🌆 Когда выбираем город — карта переходит туда автоматически
  if (citySelect) {
    citySelect.addEventListener('change', async () => {
      const countryName = countrySelect?.selectedOptions?.[0]?.text || '';
      const cityName = citySelect?.selectedOptions?.[0]?.text || '';
      if (!cityName) return;

      try {
        const res = await fetch(
          `https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=1&q=${encodeURIComponent(
            `${cityName}, ${countryName}`
          )}`,
          { headers: { 'Accept-Language': 'ru' } }
        );
        const data = await res.json();
        if (Array.isArray(data) && data.length > 0) {
          const lat = parseFloat(data[0].lat);
          const lon = parseFloat(data[0].lon);
          const coords = { lat, lng: lon };
          map.setView(coords, 13);
          marker.setLatLng(coords);
          updateCoords(coords);
          addressEl.value = `${cityName}, ${countryName}`;
        }
      } catch (e) {
        console.warn('Ошибка позиционирования карты по городу', e);
      }
    });
  }
}

if (typeof L === 'undefined') {
  window.addEventListener('load', initMap);
} else {
  initMap();
}


// ===============================================================
// === 💱 АВТОМАТИЧЕСКАЯ ВАЛЮТА ПРИ ВЫБОРЕ СТРАНЫ ================
// ===============================================================
const currencySelect = document.querySelector('select[name="currency_base"]');

if (countrySelect && currencySelect) {
  countrySelect.addEventListener('change', () => {
    const selected = countrySelect.options[countrySelect.selectedIndex]?.text?.toLowerCase() || '';

    // Привязываем валюту к стране
    if (selected.includes('приднестров')) {
      currencySelect.value = 'PRB'; // ₽ ПМР
    } else if (selected.includes('молд')) {
      currencySelect.value = 'MDL'; // леu
    } else if (selected.includes('укра')) {
      currencySelect.value = 'UAH'; // гривна
    }
  });
}


  // ===============================================================
  // === 🖼️ ГАЛЕРЕЯ: удаление фото =================================
  // ===============================================================
  const gallery = document.getElementById('gallery-container');
  if (gallery) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const deleteUrl = gallery.dataset.deleteUrl;

    gallery.addEventListener('click', async e => {
      const target = e.target;
      if (!target?.dataset?.path) return;
      if (!confirm('Удалить это фото из галереи?')) return;

      try {
        const res = await fetch(deleteUrl, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Content-Type': 'application/json',
            Accept: 'application/json',
          },
          body: JSON.stringify({ path: target.dataset.path }),
        });
        const data = await res.json();
        if (data.success) {
          target.closest('.relative')?.remove();
        } else {
          alert('Ошибка при удалении изображения');
        }
      } catch (e) {
        alert('Ошибка при удалении изображения');
      }
    });
  }
});

// ===============================================================
// === 💱 АВТОВАЛЮТА С ПЛАВНЫМ "СЧЁТЧИКОМ" + МЯГКОЕ СИЯНИЕ ========
// ===============================================================
const baseInput = document.getElementById('base-price');
const currencySelect = document.getElementById('currency_base');
const prbInput = document.getElementById('price_prb');
const mdlInput = document.getElementById('price_mdl');
const uahInput = document.getElementById('price_uah');
const countrySelect = document.getElementById('country');

// 💱 Курсы валют (заглушка, потом можно API)
let rates = {
  PRB: { PRB: 1, MDL: 1.06, UAH: 2.6 },
  MDL: { PRB: 0.94, MDL: 1, UAH: 2.45 },
  UAH: { PRB: 0.385, MDL: 0.41, UAH: 1 },
};

getCurrencyRates().then(latest => {
  rates = latest;
  recalcPrices(false); // обновим цены с новыми курсами
});



// ✨ Добавляем мягкое “дыхание” свечения
const style = document.createElement('style');
style.textContent = `
  @keyframes glowPulse {
    0% { box-shadow: 0 0 0px rgba(99,102,241,0); }
    50% { box-shadow: 0 0 10px 4px rgba(99,102,241,0.35); }
    100% { box-shadow: 0 0 0px rgba(99,102,241,0); }
  }
  .glow {
    animation: glowPulse 1.2s ease-in-out;
    border-color: rgba(99,102,241,0.5);
  }
`;
document.head.appendChild(style);

// 🎞️ Плавное обновление чисел с “накруткой”
function animateNumber(input, newValue, duration = 800) {
  const start = parseFloat(input.value) || 0;
  const end = parseFloat(newValue);
  if (start === end || isNaN(end)) return;

  const diff = end - start;
  const startTime = performance.now();

  // запускаем эффект свечения
  input.classList.add('glow');

  function tick(now) {
    const elapsed = now - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 6); // easeOutCubic
    const current = start + diff * eased;
    input.value = current.toFixed(2);

    if (progress < 1) {
      requestAnimationFrame(tick);
    } else {
      input.value = end.toFixed(2);
      // убираем подсветку после завершения
      setTimeout(() => input.classList.remove('glow'), 800);
    }
  }

  requestAnimationFrame(tick);
}

// 🔄 Пересчёт всех валют
function recalcPrices(animated = true) {
  const baseValue = parseFloat(baseInput?.value) || 0;
  const baseCurrency = currencySelect?.value;
  if (!baseCurrency || !rates[baseCurrency]) return;

  const set = rates[baseCurrency];
  const newPRB = baseValue * set.PRB;
  const newMDL = baseValue * set.MDL;
  const newUAH = baseValue * set.UAH;

  if (animated) {
    animateNumber(prbInput, newPRB);
    animateNumber(mdlInput, newMDL);
    animateNumber(uahInput, newUAH);
  } else {
    prbInput.value = newPRB.toFixed(2);
    mdlInput.value = newMDL.toFixed(2);
    uahInput.value = newUAH.toFixed(2);
  }
}



// 🎧 Слушатели
if (baseInput && currencySelect) {
  baseInput.addEventListener('input', () => recalcPrices(true));
  currencySelect.addEventListener('change', () => recalcPrices(true));
  recalcPrices(false);
}

// 🇲🇩 Автовалюта при смене страны
if (countrySelect && currencySelect) {
  countrySelect.addEventListener('change', () => {
    const selected = countrySelect.options[countrySelect.selectedIndex]?.text?.toLowerCase() || '';
    if (selected.includes('приднестров')) currencySelect.value = 'PRB';
    else if (selected.includes('молд')) currencySelect.value = 'MDL';
    else if (selected.includes('укра')) currencySelect.value = 'UAH';
    recalcPrices(true);
  });
}

