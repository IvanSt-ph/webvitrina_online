import { renderAttributeFields, snapshotAttributeFields, updateColorSummary, captureAttributeDrafts, restoreAttributeDrafts, resetAttributeColor } from './seller-attribute-fields.js';
import { getCurrencyRates } from './currency-rates.js';

document.addEventListener('DOMContentLoaded', () => {
  if (window.__sellerProductFormInitialized) return;
  window.__sellerProductFormInitialized = true;

  // ===============================================================
  // === 🏷️ КЕШ ДЛЯ УСКОРЕНИЯ ======================================
  // ===============================================================
  const categoryChildrenCache = new Map();  // кеш подкатегорий
  const attributesCache = new Map();        // кеш характеристик
  // ===============================================================
  // === 🏷️ КАТЕГОРИИ: каскадная подгрузка + hidden category_id ====
  // ===============================================================
  const catWrapper = document.getElementById('categories-wrapper');
  const catHidden = document.getElementById('category_id');
  const rootSelect = catWrapper ? catWrapper.querySelector('#category-root') : null;
  const categoryWarning = document.getElementById('category-missing-warning');

  function syncCategoryWarning() {
    if (!categoryWarning) return;
    const selectedId = catHidden?.value;
    const categories = Array.isArray(window.allCategories) ? window.allCategories : [];
    const isLeaf = Boolean(selectedId)
      && categories.some(category => String(category.id) === String(selectedId))
      && !categories.some(category => String(category.parent_id) === String(selectedId));
    categoryWarning.style.display = isLeaf ? 'none' : '';
  }

  catWrapper?.addEventListener('change', e => {
    if (e.target.matches('.category-select')) syncCategoryWarning();
  });

  function removeNextSelects(currentSelect) {
    let next = currentSelect.nextElementSibling;
    while (next && next.tagName === 'SELECT') {
      next.remove();
      next = currentSelect.nextElementSibling;
    }
  }

  async function loadChildren(parentId, afterSelect) {
    removeNextSelects(afterSelect);
    catHidden.value = parentId || '';
    if (!parentId) return;

    try {
      let categories;

      // 🔥 кеширование
      if (categoryChildrenCache.has(parentId)) {
        categories = categoryChildrenCache.get(parentId);
      } else {
        const res = await fetch(`/seller/categories/${parentId}/children`);
        if (!res.ok) return;
        categories = await res.json();
        categoryChildrenCache.set(parentId, categories);
      }

      if (!Array.isArray(categories) || categories.length === 0) return;

      const select = document.createElement('select');
      select.className =
        'category-select seller-input mt-2';

      select.innerHTML = `<option value="">-- выберите подкатегорию --</option>`;

      categories.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.id;
        option.textContent = cat.name;
        select.appendChild(option);
      });

      select.addEventListener('change', e => {
        const chosen = e.target.value;
        catHidden.value = chosen || parentId;
        loadChildren(chosen, select);
      });

      afterSelect.insertAdjacentElement('afterend', select);

    } catch (err) {
    }
  }

  if (rootSelect) {
    rootSelect.addEventListener('change', e => {
      const id = e.target.value;
      catHidden.value = id || '';
      loadChildren(id, e.target);
    });
  }

  async function restoreCategoryChain() {
    if (!rootSelect || !catHidden?.value || !Array.isArray(window.allCategories)) return;

    const byId = new Map(window.allCategories.map(category => [String(category.id), category]));
    const chain = [];
    let current = byId.get(String(catHidden.value));

    while (current) {
      chain.unshift(current);
      current = current.parent_id ? byId.get(String(current.parent_id)) : null;
    }

    if (chain.length < 2) return;

    let currentSelect = rootSelect;
    currentSelect.value = chain[0].id;

    for (let i = 1; i < chain.length; i++) {
      await loadChildren(chain[i - 1].id, currentSelect);

      const nextSelect = currentSelect.nextElementSibling;
      if (!nextSelect || nextSelect.tagName !== 'SELECT') break;

      nextSelect.value = chain[i].id;
      currentSelect = nextSelect;
    }

    catHidden.value = chain[chain.length - 1].id;
  }

  // Use the restored ID before the cascade temporarily walks through its parents.
  syncCategoryWarning();
  restoreCategoryChain().then(syncCategoryWarning);

  // ===============================================================
  // === 🌍 ЛОКАЦИЯ: загрузка городов по стране ====================
  // ===============================================================
  const countrySelectEl = document.getElementById('country');
  const citySelectEl = document.getElementById('city');
  const wrapperEl = document.querySelector('[data-country]');
  const preCountryId = wrapperEl?.dataset.country || null;
  const preCityId = wrapperEl?.dataset.city || null;

  const cacheCities = new Map();
  let citiesLoaded = false;

  async function loadCities(countryId, selectedCityId = null) {
    if (!citySelectEl || !countryId) return;
    citySelectEl.innerHTML = '<option value="">-- выберите город --</option>';

    if (cacheCities.has(countryId)) {
      renderCities(cacheCities.get(countryId), selectedCityId);
      return;
    }

    try {
      const res = await fetch(`/countries/${countryId}/cities`);
      if (!res.ok) return;

      const cities = await res.json();
      const uniqueCities = Array.from(new Map(cities.map(c => [c.id, c])).values());

      cacheCities.set(countryId, uniqueCities);
      renderCities(uniqueCities, selectedCityId);

    } catch (error) {
    }
  }

  function renderCities(cities, selectedCityId = null) {
    citySelectEl.innerHTML = '<option value="">-- выберите город --</option>';
    cities.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.id;
      opt.textContent = c.name;
      if (selectedCityId && String(selectedCityId) === String(c.id)) opt.selected = true;
      citySelectEl.appendChild(opt);
    });
  }

  if (countrySelectEl) {
    countrySelectEl.addEventListener('change', () => {
      citiesLoaded = false;
      loadCities(countrySelectEl.value, null);
    });

    if (preCountryId && !citiesLoaded) {
      citiesLoaded = true;
      countrySelectEl.value = preCountryId;
      loadCities(preCountryId, preCityId);
    }
  }

  // ===============================================================
  // === 🗺️ КАРТА + ПОИСК (Leaflet + Nominatim) ==================
  // ===============================================================

  const mapEl = document.getElementById('map');
  const latInput = document.getElementById('latitude');
  const lngInput = document.getElementById('longitude');
  const addressEl = document.getElementById('address');
  const searchBtn = document.getElementById('searchAddress');
  const errorBox = document.getElementById('addressError');

  let mapInstance = null;
  let markerInstance = null;

  function updateCoords(latlng) {
    if (latInput) latInput.value = latlng.lat.toFixed(6);
    if (lngInput) lngInput.value = latlng.lng.toFixed(6);
  }

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
        ].filter(Boolean).join(', ');
        addressEl.value = addr;
      }
    } catch {
    }
  }

  async function geocodeAndMove(cityName, countryName, zoom = 13) {
    if (!cityName) return;
    try {
      const res = await fetch(
        `https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=1&q=${encodeURIComponent(
          `${cityName}, ${countryName || ''}`
        )}`,
        { headers: { 'Accept-Language': 'ru' } }
      );
      const data = await res.json();

      if (Array.isArray(data) && data.length > 0) {
        const lat = parseFloat(data[0].lat);
        const lon = parseFloat(data[0].lon);
        const coords = { lat, lng: lon };

        if (mapInstance && markerInstance) {
          mapInstance.setView(coords, zoom);
          markerInstance.setLatLng(coords);
          updateCoords(coords);
          if (addressEl) addressEl.value = `${cityName}${countryName ? `, ${countryName}` : ''}`;
        }
      }
    } catch {
    }
  }

  function initMap() {
    if (!mapEl || typeof L === 'undefined') return;

    const initialLat = parseFloat(mapEl.dataset.lat || 47.0105);
    const initialLng = parseFloat(mapEl.dataset.lng || 28.8638);
    const initialZoom = parseInt(mapEl.dataset.zoom || 7);

    mapInstance = L.map('map').setView([initialLat, initialLng], initialZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(mapInstance);

    markerInstance = L.marker([initialLat, initialLng], { draggable: true }).addTo(mapInstance);

    updateCoords(markerInstance.getLatLng());

    markerInstance.on('dragend', e => {
      const latlng = e.target.getLatLng();
      updateCoords(latlng);
      reverseGeocode(latlng.lat, latlng.lng);
    });

    mapInstance.on('click', e => {
      markerInstance.setLatLng(e.latlng);
      updateCoords(e.latlng);
      reverseGeocode(e.latlng.lat, e.latlng.lng);
    });

    if (searchBtn) {
      searchBtn.addEventListener('click', async () => {
        const query = addressEl?.value?.trim();
        if (!query) {
          errorBox?.classList?.remove('hidden');
          if (errorBox) errorBox.textContent = 'Введите адрес для поиска';
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
            errorBox?.classList?.remove('hidden');
            if (errorBox) errorBox.textContent = 'Адрес не найден';
            return;
          }

          const { lat, lon } = data[0];
          const coords = { lat: parseFloat(lat), lng: parseFloat(lon) };

          mapInstance.setView(coords, 14);
          markerInstance.setLatLng(coords);
          updateCoords(coords);

          errorBox?.classList?.add('hidden');

        } catch (e) {
          if (errorBox) {
            errorBox.textContent = 'Ошибка поиска адреса';
            errorBox.classList.remove('hidden');
          }
        }
      });
    }
  }

  if (typeof L === 'undefined') {
    window.addEventListener('load', initMap);
  } else {
    initMap();
  }

  if (citySelectEl) {
    citySelectEl.addEventListener('change', () => {
      const countryName = countrySelectEl?.selectedOptions?.[0]?.text || '';
      const cityName = citySelectEl?.selectedOptions?.[0]?.text || '';
      if (!cityName) return;

      const tryMove = () => {
        if (mapInstance && markerInstance) {
          geocodeAndMove(cityName, countryName, 13);
        } else {
          setTimeout(tryMove, 120);
        }
      };
      tryMove();
    });
  }

  // ===============================================================
  // === ⚙️ ДИНАМИЧЕСКИЕ АТРИБУТЫ (по категории) ==================
  // ===============================================================
  const attrWrapper = document.getElementById('attributes-wrapper');
  let activeAttributeCategory = String(catHidden?.value || '');
  let attributeRequest = 0;
  const attributeDrafts = new Map();
  attrWrapper?.addEventListener('change', e => updateColorSummary(e.target));
  attrWrapper?.addEventListener('click', e => {
    const reset = e.target.closest('[data-color-reset]');
    if (reset) resetAttributeColor(reset);
  });

  async function loadAttributes(categoryId) {
    if (!attrWrapper) return;
    const key = String(categoryId || '');
    const request = ++attributeRequest;
    if (activeAttributeCategory) {
      captureAttributeDrafts(attrWrapper, attributeDrafts);
      attributesCache.set(activeAttributeCategory, snapshotAttributeFields(attrWrapper));
    }
    activeAttributeCategory = '';
    if (!key) {
      attrWrapper.innerHTML = '';
      return;
    }
    if (attributesCache.has(key)) {
      attrWrapper.innerHTML = attributesCache.get(key);
      restoreAttributeDrafts(attrWrapper, attributeDrafts);
      activeAttributeCategory = key;
      return;
    }
    attrWrapper.innerHTML = '<div class="seller-empty-state text-sm text-neutral-500 animate-pulse" role="status">Загружаем характеристики...</div>';
    try {
      const res = await fetch('/seller/categories/' + encodeURIComponent(key) + '/attributes');
      if (!res.ok) throw new Error('Attributes request failed');
      const data = await res.json();
      if (request !== attributeRequest) return;
      if (!Array.isArray(data)) throw new Error('Invalid attributes response');
      const html = data.length ? renderAttributeFields(data) : '<div class="seller-empty-state"><p class="font-semibold text-neutral-800">Для этой категории нет дополнительных характеристик</p><p class="mt-1 text-sm text-neutral-500">Можно продолжать заполнять товар.</p></div>';
      attributesCache.set(key, html);
      attrWrapper.innerHTML = html;
      restoreAttributeDrafts(attrWrapper, attributeDrafts);
      activeAttributeCategory = key;
    } catch (e) {
      if (request !== attributeRequest) return;
      attrWrapper.innerHTML = '<div class="seller-empty-state text-danger-500 text-sm" role="alert">Ошибка при загрузке характеристик. Выберите категорию повторно.</div>';
    }
  }

  document.addEventListener('change', e => {
    if (!e.target.matches('.category-select')) return;

    const selectedId = e.target.value;
    const children = window.allCategories.filter(c => c.parent_id == selectedId);

    if (children.length > 0) {
      loadAttributes(0);
      return;
    }

    loadAttributes(selectedId);
  });

  // ===============================================================
  // === 💱 ПЛАВНЫЙ ПЕРЕСЧЁТ ЦЕН ==================================
  // ===============================================================
  const baseInput = document.getElementById('base-price');
  const currencyBaseEl = document.getElementById('currency_base');
  const prbInput = document.getElementById('price_prb');
  const mdlInput = document.getElementById('price_mdl');
  const uahInput = document.getElementById('price_uah');

  let rates = {
    PRB: { PRB: 1, MDL: 1.06, UAH: 2.6 },
    MDL: { PRB: 0.94, MDL: 1, UAH: 2.45 },
    UAH: { PRB: 0.385, MDL: 0.41, UAH: 1 },
  };

  getCurrencyRates().then(latest => {
    rates = latest;
    recalcPrices(false);
  });

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

  function animateNumber(input, newValue, duration = 800) {
    const start = parseFloat(input.value) || 0;
    const end = parseFloat(newValue);
    if (start === end || isNaN(end)) return;

    const diff = end - start;
    const startTime = performance.now();

    input.classList.add('glow');

    function tick(now) {
      const elapsed = now - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 6);

      input.value = (start + diff * eased).toFixed(2);

      if (progress < 1) requestAnimationFrame(tick);
      else setTimeout(() => input.classList.remove('glow'), 800);
    }

    requestAnimationFrame(tick);
  }

  function recalcPrices(animated = true) {
    const baseValue = parseFloat(baseInput?.value) || 0;
    const baseCurrency = currencyBaseEl?.value;
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

  if (baseInput && currencyBaseEl) {
    baseInput.addEventListener('input', () => recalcPrices(true));
    currencyBaseEl.addEventListener('change', () => recalcPrices(true));
    recalcPrices(false);
  }

  if (countrySelectEl && currencyBaseEl) {
    countrySelectEl.addEventListener('change', () => {
      const selected = countrySelectEl.options[countrySelectEl.selectedIndex]?.text?.toLowerCase() || '';

      if (selected.includes('приднестров')) currencyBaseEl.value = 'PRB';
      else if (selected.includes('молд')) currencyBaseEl.value = 'MDL';
      else if (selected.includes('укра')) currencyBaseEl.value = 'UAH';

      recalcPrices(true);
    });
  }

  // ===============================================================
  // === 🖼️ УДАЛЕНИЕ ФОТО ========================================
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
        if (data.success) target.closest('.relative')?.remove();
        else alert('Ошибка при удалении изображения');

      } catch (e) {
        alert('Ошибка при удалении изображения');
      }
    });
  }

  // ===============================================================
  // === ✂️ КРОП ГЛАВНОГО ФОТО ====================================
  // ===============================================================
  const cropInput = document.querySelector('input[type="file"][data-main-crop]');
  const cropper = document.getElementById('main-image-cropper');
  const cropCanvas = document.getElementById('main-image-crop-canvas');
  const cropZoom = document.getElementById('main-image-crop-zoom');
  const cropOpen = document.getElementById('main-image-open-crop');
  const cropFit = document.getElementById('main-image-crop-fit');
  const cropApply = document.getElementById('main-image-crop-apply');
  const cropCtx = cropCanvas?.getContext('2d');
  const cropOutput = { width: 1200, height: 960 };

  let cropImage = null;
  let cropSourceFile = null;
  let cropOffset = { x: 0, y: 0 };
  let cropDragging = false;
  let cropDragStart = { x: 0, y: 0 };
  let cropAppliedProgrammatically = false;

  function cropBaseScale() {
    if (!cropImage || !cropCanvas) return 1;
    return Math.max(cropCanvas.width / cropImage.width, cropCanvas.height / cropImage.height);
  }

  function cropCurrentScale() {
    return cropBaseScale() * (parseFloat(cropZoom?.value) || 1);
  }

  function cropPointerPosition(event) {
    const rect = cropCanvas.getBoundingClientRect();

    return {
      x: (event.clientX - rect.left) * (cropCanvas.width / rect.width),
      y: (event.clientY - rect.top) * (cropCanvas.height / rect.height),
    };
  }

  function clampCropOffset() {
    if (!cropImage || !cropCanvas) return;

    const scale = cropCurrentScale();
    const drawnWidth = cropImage.width * scale;
    const drawnHeight = cropImage.height * scale;
    const minX = Math.min(0, cropCanvas.width - drawnWidth);
    const minY = Math.min(0, cropCanvas.height - drawnHeight);

    cropOffset.x = Math.min(0, Math.max(minX, cropOffset.x));
    cropOffset.y = Math.min(0, Math.max(minY, cropOffset.y));
  }

  function drawCrop() {
    if (!cropImage || !cropCtx || !cropCanvas) return;

    clampCropOffset();
    cropCtx.clearRect(0, 0, cropCanvas.width, cropCanvas.height);
    cropCtx.fillStyle = '#f3f4f6';
    cropCtx.fillRect(0, 0, cropCanvas.width, cropCanvas.height);

    const scale = cropCurrentScale();
    cropCtx.drawImage(
      cropImage,
      cropOffset.x,
      cropOffset.y,
      cropImage.width * scale,
      cropImage.height * scale
    );

    cropCtx.strokeStyle = 'rgba(255,255,255,.95)';
    cropCtx.lineWidth = 2;
    cropCtx.strokeRect(1, 1, cropCanvas.width - 2, cropCanvas.height - 2);

    cropCtx.strokeStyle = 'rgba(79,70,229,.45)';
    cropCtx.lineWidth = 1;
    const thirdX = cropCanvas.width / 3;
    const thirdY = cropCanvas.height / 3;
    cropCtx.beginPath();
    cropCtx.moveTo(thirdX, 0);
    cropCtx.lineTo(thirdX, cropCanvas.height);
    cropCtx.moveTo(thirdX * 2, 0);
    cropCtx.lineTo(thirdX * 2, cropCanvas.height);
    cropCtx.moveTo(0, thirdY);
    cropCtx.lineTo(cropCanvas.width, thirdY);
    cropCtx.moveTo(0, thirdY * 2);
    cropCtx.lineTo(cropCanvas.width, thirdY * 2);
    cropCtx.stroke();
  }

  function showCropper(file) {
    if (!cropper || !cropCanvas || !cropZoom) return;

    cropSourceFile = file;
    cropImage = new Image();
    cropImage.onload = () => {
      URL.revokeObjectURL(cropImage.src);
      cropZoom.value = '1';
      const scale = cropBaseScale();
      cropOffset = {
        x: (cropCanvas.width - cropImage.width * scale) / 2,
        y: (cropCanvas.height - cropImage.height * scale) / 2,
      };
      cropper.classList.remove('hidden');
      cropper.classList.add('flex');
      drawCrop();
    };
    cropImage.src = URL.createObjectURL(file);
  }

  function closeCropper() {
    cropper?.classList.add('hidden');
    cropper?.classList.remove('flex');
    cropDragging = false;
  }

  function setMainCropButtonVisible(visible) {
    if (!cropOpen) return;
    cropOpen.classList.toggle('hidden', !visible);
  }

  function renderPreviewForInput(input) {
    const target = document.getElementById(input.dataset.previewTarget);
    if (!target) return;

    target.innerHTML = '';
    const files = Array.from(input.files || []).filter(file => file.type.startsWith('image/'));

    if (!files.length) {
      target.classList.add('hidden');
      return;
    }

    files.slice(0, 8).forEach(file => {
      const img = document.createElement('img');
      img.alt = file.name;
      img.src = URL.createObjectURL(file);
      img.onload = () => URL.revokeObjectURL(img.src);
      target.appendChild(img);
    });

    target.classList.remove('hidden');
  }

  if (cropInput && cropper && cropCanvas && cropCtx) {
    cropInput.addEventListener('change', () => {
      if (cropAppliedProgrammatically) {
        cropAppliedProgrammatically = false;
        renderPreviewForInput(cropInput);
        setMainCropButtonVisible(true);
        return;
      }

      const file = cropInput.files?.[0];
      if (!file || !file.type.startsWith('image/')) {
        setMainCropButtonVisible(false);
        return;
      }

      cropSourceFile = file;
      renderPreviewForInput(cropInput);
      setMainCropButtonVisible(true);
    });

    cropOpen?.addEventListener('click', () => {
      const file = cropInput.files?.[0] || cropSourceFile;
      if (!file || !file.type.startsWith('image/')) return;
      showCropper(file);
    });

    cropZoom?.addEventListener('input', drawCrop);

    cropCanvas.addEventListener('pointerdown', e => {
      const point = cropPointerPosition(e);
      cropDragging = true;
      cropCanvas.setPointerCapture(e.pointerId);
      cropDragStart = {
        x: point.x - cropOffset.x,
        y: point.y - cropOffset.y,
      };
    });

    cropCanvas.addEventListener('pointermove', e => {
      if (!cropDragging) return;
      const point = cropPointerPosition(e);
      cropOffset = {
        x: point.x - cropDragStart.x,
        y: point.y - cropDragStart.y,
      };
      drawCrop();
    });

    cropCanvas.addEventListener('pointerup', e => {
      cropDragging = false;
      cropCanvas.releasePointerCapture(e.pointerId);
    });

    cropper.querySelectorAll('[data-crop-cancel]').forEach(button => {
      button.addEventListener('click', () => {
        closeCropper();
      });
    });

    function replaceMainImageWithCanvas(output, mimeType, suffix) {
      output.toBlob(blob => {
        if (!blob || !cropSourceFile) return;

        const extension = mimeType === 'image/png' ? 'png' : 'jpg';
        const croppedFile = new File(
          [blob],
          cropSourceFile.name.replace(/\.[^.]+$/, '') + suffix + '.' + extension,
          { type: blob.type, lastModified: Date.now() }
        );

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(croppedFile);
        cropAppliedProgrammatically = true;
        cropInput.files = dataTransfer.files;
        cropInput.dispatchEvent(new Event('change', { bubbles: true }));
        closeCropper();
      }, mimeType, 0.92);
    }

    cropFit?.addEventListener('click', () => {
      if (!cropImage || !cropSourceFile) return;

      const output = document.createElement('canvas');
      output.width = cropOutput.width;
      output.height = cropOutput.height;
      const outputCtx = output.getContext('2d');
      const scale = Math.min(cropOutput.width / cropImage.width, cropOutput.height / cropImage.height);
      const width = cropImage.width * scale;
      const height = cropImage.height * scale;

      outputCtx.fillStyle = '#fff';
      outputCtx.fillRect(0, 0, cropOutput.width, cropOutput.height);
      outputCtx.drawImage(cropImage, (cropOutput.width - width) / 2, (cropOutput.height - height) / 2, width, height);

      replaceMainImageWithCanvas(output, 'image/jpeg', '-fit');
    });

    cropApply?.addEventListener('click', () => {
      if (!cropImage || !cropSourceFile) return;

      const output = document.createElement('canvas');
      output.width = cropOutput.width;
      output.height = cropOutput.height;
      const outputCtx = output.getContext('2d');
      const ratioX = cropOutput.width / cropCanvas.width;
      const ratioY = cropOutput.height / cropCanvas.height;
      const scale = cropCurrentScale() * ratioX;

      outputCtx.fillStyle = '#fff';
      outputCtx.fillRect(0, 0, cropOutput.width, cropOutput.height);
      outputCtx.drawImage(
        cropImage,
        cropOffset.x * ratioX,
        cropOffset.y * ratioY,
        cropImage.width * scale,
        cropImage.height * scale
      );

      replaceMainImageWithCanvas(output, cropSourceFile.type === 'image/png' ? 'image/png' : 'image/jpeg', '-crop');
    });
  }

  // ===============================================================
  // === 🖼️ ЛОКАЛЬНОЕ ПРЕВЬЮ ВЫБРАННЫХ ФОТО =======================
  // ===============================================================
  document.querySelectorAll('input[type="file"][data-preview-target]').forEach(input => {
    input.addEventListener('change', () => {
      renderPreviewForInput(input);
    });
  });

// Выбор цвета в фильтре
document.addEventListener('click', e => {
    const label = e.target.closest('.color-filter-option');
    if (!label) return;

    const input = label.querySelector('input[type="checkbox"]');
    if (!input) return;

    input.checked = !input.checked;

    const circle = label.querySelector('div');
    if (input.checked) {
        circle.classList.remove('border-gray-300');
        circle.classList.add('border-indigo-600', 'scale-110');
    } else {
        circle.classList.add('border-gray-300');
        circle.classList.remove('border-indigo-600', 'scale-110');
    }
});



});
