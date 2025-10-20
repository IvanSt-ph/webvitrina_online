{{-- resources/views/seller/products/form.blade.php --}}
<x-app-layout :title="$product->exists ? 'Редактирование' : 'Добавление'">

  {{-- =================================================================== --}}
  {{--  ВНЕШНИЙ КОНТЕЙНЕР / ЗАГОЛОВОК                                     --}}
  {{-- =================================================================== --}}
  <div class="max-w-3xl mx-auto my-8 space-y-6">

    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-semibold text-gray-800">
        {{ $product->exists ? '✏️ Редактирование' : '➕ Добавление' }} товара
      </h1>
      <a href="{{ route('seller.products.index') }}"
         class="text-sm text-indigo-600 hover:text-indigo-800">← Назад к товарам</a>
    </div>

    {{-- =================================================================== --}}
    {{--  Ф О Р М А                                                          --}}
    {{-- =================================================================== --}}
    <form method="post" enctype="multipart/form-data"
          action="{{ $product->exists ? route('seller.products.update',$product) : route('seller.products.store') }}"
          class="space-y-6">
      @csrf
      @if($product->exists) @method('PUT') @endif

      {{-- ================================================================ --}}
      {{-- 🧩 ОСНОВНАЯ ИНФОРМАЦИЯ                                          --}}
      {{-- ================================================================ --}}
      <section class="bg-white border rounded-2xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">📦 Основная информация</h2>

        {{-- Название --}}
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Название</label>
          <input name="title" value="{{ old('title',$product->title) }}"
                 class="w-full border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
          @error('title') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Цена и остаток --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Цена (в рублях)</label>
            <input name="price" type="number" step="0.01" min="0"
                   value="{{ old('price',$product->price) }}"
                   class="w-full border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
            @error('price') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Остаток</label>
            <input name="stock" type="number" min="0"
                   value="{{ old('stock',$product->stock) }}"
                   class="w-full border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200" />
            @error('stock') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
          </div>
        </div>

        {{-- Описание --}}
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
          <textarea name="description" rows="5"
                    class="w-full border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200">{{ old('description',$product->description) }}</textarea>
          @error('description') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
      </section>

      {{-- ================================================================ --}}
      {{-- 🗂 КАТЕГОРИЯ (КАСКАД)                                            --}}
      {{-- ================================================================ --}}
      <section class="bg-white border rounded-2xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">🏷 Категория</h2>

        {{-- === Категории (каскад, без подгрузок) === --}}
        <div id="categories-wrapper" class="space-y-2">
          <label class="block text-sm font-medium text-gray-600">Категория</label>

          @php $currentParent = null; @endphp

          {{-- 1. Корневая категория --}}
          <select name="category_level_1" id="category-root"
                  class="w-full border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <option value="">-- выберите категорию --</option>
            @foreach($rootCategories as $root)
              <option value="{{ $root->id }}"
                @selected(($categoryChain->first()?->id ?? $product->category_id) == $root->id)>
                {{ $root->name }}
              </option>
            @endforeach
          </select>

          {{-- 2. Промежуточные уровни (предзаполненная цепочка) --}}
          @foreach($categoryChain->slice(1)->unique('id') as $index => $cat)
            @php
              $parentId = $cat->parent_id;
              $siblings = \App\Models\Category::where('parent_id', $parentId)
                          ->where('id', '!=', $parentId)
                          ->orderBy('name')
                          ->get();
            @endphp
            <select name="category_level_{{ $index + 2 }}"
                    class="w-full border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200">
              <option value="">-- выберите подкатегорию --</option>
              @foreach($siblings as $sibling)
                <option value="{{ $sibling->id }}" @selected($sibling->id == $cat->id)>
                  {{ $sibling->name }}
                </option>
              @endforeach
            </select>
          @endforeach

          {{-- Скрытое итоговое поле --}}
          <input type="hidden" name="category_id" id="category_id"
                 value="{{ old('category_id', $product->category_id) }}">
        </div>
      </section>

      {{-- ================================================================ --}}
      {{-- 🌍 МЕСТОПОЛОЖЕНИЕ (СТРАНА, ГОРОД, АДРЕС, КАРТА)                   --}}
      {{-- ================================================================ --}}
      <section class="bg-white border rounded-2xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">🌍 Местоположение</h2>

        {{-- Страна --}}
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Страна</label>
          <select id="country" name="country_id"
                  class="w-full border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <option value="">-- выберите страну --</option>
            @foreach($countries as $country)
              <option value="{{ $country->id }}"
                @selected(old('country_id', optional($product->city)->country_id) == $country->id)>
                {{ $country->name }}
              </option>
            @endforeach
          </select>
          @error('country_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Город --}}
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Город</label>
          <select id="city" name="city_id"
                  class="w-full border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <option value="">-- выберите город --</option>
          </select>
          @error('city_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Адрес + Поиск --}}
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Адрес (улица, дом)</label>
          <div class="flex gap-3">
            <input id="address" name="address" type="text"
                   class="flex-1 border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                   placeholder="Например: ул. Ленина, 2"
                   value="{{ old('address', $product->address ?? '') }}">
            <button type="button" id="searchAddress"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
              Найти
            </button>
          </div>
          <p id="addressError" class="text-red-600 text-sm mt-2 hidden"></p>
        </div>

        {{-- Карта --}}
        <div class="mt-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Местоположение на карте</label>
          <div id="map" class="w-full h-64 rounded-lg border"></div>

          <input type="hidden" id="latitude" name="latitude"
                 value="{{ old('latitude', $product->latitude) }}">
          <input type="hidden" id="longitude" name="longitude"
                 value="{{ old('longitude', $product->longitude) }}">
        </div>
      </section>

      {{-- ================================================================ --}}
      {{-- 🖼 ИЗОБРАЖЕНИЯ (ГЛАВНОЕ + ГАЛЕРЕЯ)                               --}}
      {{-- ================================================================ --}}
      <section class="bg-white border rounded-2xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">🖼 Изображения</h2>

        {{-- Главное изображение --}}
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Изображение (главное фото)</label>
          <input type="file" name="image" class="w-full border-gray-200 rounded-lg p-2" />
          @if($product->image)
            <img src="{{ asset('storage/'.$product->image) }}" class="mt-3 w-40 rounded-lg border" />
          @endif
          @error('image') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        {{-- Галерея --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Галерея</label>
          <input type="file" name="gallery[]" multiple class="w-full border-gray-200 rounded-lg p-2" />

          @php
            $gallery = is_array($product->gallery)
                ? $product->gallery
                : (json_decode($product->gallery, true) ?? []);
          @endphp

          @if(!empty($gallery))
            <div id="gallery-container" class="flex gap-3 mt-3 flex-wrap">
              @foreach($gallery as $img)
                @if($img)
                  <div class="relative group rounded overflow-hidden border">
                    <img src="{{ asset('storage/'.$img) }}"
                         alt="Фото"
                         class="w-20 h-20 object-cover">
                    <button type="button"
                            data-path="{{ $img }}"
                            class="absolute top-1 right-1 bg-red-600 text-white text-xs px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition">
                      ✕
                    </button>
                  </div>
                @endif
              @endforeach
            </div>
          @else
            <p class="text-gray-400 text-sm mt-2">Нет загруженных фото</p>
          @endif

          @error('gallery') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
      </section>

      {{-- ================================================================ --}}
      {{-- 💾 ДЕЙСТВИЯ                                                     --}}
      {{-- ================================================================ --}}
      <div class="flex justify-end">
        <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
          💾 Сохранить
        </button>
      </div>
    </form>
  </div>

  {{-- =================================================================== --}}
  {{--  ПОДКЛЮЧЕНИЯ ДЛЯ КАРТ (Leaflet)                                    --}}
  {{--  (оставил здесь, чтобы не дёргать <head> вашего layout)            --}}
  {{-- =================================================================== --}}
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

  <style>
    /* аккуратный стиль подписи © OpenStreetMap */
    #map .leaflet-control-attribution {
      font-size: 11px !important;
      color: #666 !important;
      background: rgba(255,255,255,0.8) !important;
      border-radius: 6px !important;
      padding: 2px 6px !important;
    }
  </style>

  {{-- =================================================================== --}}
  {{--  Е Д И Н Ы Й   С К Р И П Т   (ВСЯ ЛОГИКА ВМЕСТЕ, ЧИТАЕМО)          --}}
  {{-- =================================================================== --}}
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    // ===============================================================
    // === КАТЕГОРИИ: каскадная подгрузка и hidden category_id =======
    // ===============================================================
    const catWrapper   = document.getElementById('categories-wrapper');
    const catHidden    = document.getElementById('category_id');
    const rootSelect   = catWrapper ? catWrapper.querySelector('#category-root') : null;

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
        const res = await fetch(`/categories/${parentId}/children`);
        if (!res.ok) return;
        const categories = await res.json();
        if (!Array.isArray(categories) || !categories.length) return;

        const select = document.createElement('select');
        select.className = "w-full border-gray-200 rounded-lg p-3 mb-2 focus:outline-none focus:ring-2 focus:ring-indigo-200";
        select.innerHTML = `<option value="">-- выберите подкатегорию --</option>`;

        categories.forEach(cat => {
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
      } catch(_) {}
    }

    if (rootSelect) {
      rootSelect.addEventListener('change', e => {
        const id = e.target.value;
        catHidden.value = id || '';
        loadChildren(id, e.target);
      });

      // Обновление hidden при любых изменениях в блоке
      catWrapper.addEventListener('change', () => {
        const selects = catWrapper.querySelectorAll('select');
        let lastValue = '';
        selects.forEach(s => { if (s.value) lastValue = s.value; });
        catHidden.value = lastValue || catHidden.value;
      });
    }

    // ===============================================================
    // === ЛОКАЦИЯ: Загрузка городов по стране =======================
    // ===============================================================
    const countrySelect = document.getElementById('country');
    const citySelect    = document.getElementById('city');
    const preCountryId  = "{{ old('country_id', optional($product->city)->country_id) }}";
    const preCityId     = "{{ old('city_id', $product->city_id) }}";

    async function loadCities(countryId, selectedCityId = null) {
      if (!citySelect) return;
      citySelect.innerHTML = '<option value="">-- выберите город --</option>';
      if (!countryId) return;
      try {
        const res = await fetch(`/countries/${countryId}/cities`);
        if (!res.ok) return;
        const cities = await res.json();
        cities.forEach(c => {
          const opt = document.createElement('option');
          opt.value = c.id;
          opt.textContent = c.name;
          if (selectedCityId && String(selectedCityId) === String(c.id)) {
            opt.selected = true;
          }
          citySelect.appendChild(opt);
        });
      } catch(_) {}
    }

    if (countrySelect) {
      countrySelect.addEventListener('change', () => loadCities(countrySelect.value, null));

      if (preCountryId) {
        countrySelect.value = preCountryId;
        loadCities(preCountryId, preCityId);
      }
    }

    // ===============================================================
    // === КАРТА + ПОИСК АДРЕСА (Leaflet + Nominatim) ================
    // ===============================================================
    const mapEl       = document.getElementById('map');
    const addressEl   = document.getElementById('address');
    const searchBtn   = document.getElementById('searchAddress');
    const errorBox    = document.getElementById('addressError');
    const latInput    = document.getElementById('latitude');
    const lngInput    = document.getElementById('longitude');

    if (mapEl && typeof L !== 'undefined') {
      const initialLat = {{ $product->latitude ?? 47.0105 }};
      const initialLng = {{ $product->longitude ?? 28.8638 }};
      const initialZoom = {{ $product->latitude ? 14 : 7 }};

      const map = L.map('map').setView([initialLat, initialLng], initialZoom);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      map.attributionControl.setPrefix(false);
      map.attributionControl.setPosition('bottomleft');

      const marker = L.marker([initialLat, initialLng], {draggable:true}).addTo(map);

      function updateCoords(latlng) {
        if (latInput) latInput.value = latlng.lat.toFixed(6);
        if (lngInput) lngInput.value = latlng.lng.toFixed(6);
      }
      updateCoords(marker.getLatLng());

      function shortAddress(addr) {
        if (!addr) return '';
        const parts = [];
        if (addr.road) parts.push(addr.road);
        if (addr.house_number) parts.push(addr.house_number);
        if (addr.city) parts.push(addr.city);
        else if (addr.town) parts.push(addr.town);
        else if (addr.village) parts.push(addr.village);
        if (addr.country) parts.push(addr.country);
        return parts.join(', ');
      }

      marker.on('dragend', async (e) => {
        const latlng = e.target.getLatLng();
        updateCoords(latlng);
        try {
          const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&zoom=18&addressdetails=1`;
          const res = await fetch(url, { headers: { 'Accept-Language': 'ru' } });
          const data = await res.json();
          if (data && data.address && addressEl) {
            addressEl.value = shortAddress(data.address);
          }
        } catch(_) {}
      });

      map.on('click', async (e) => {
        marker.setLatLng(e.latlng);
        updateCoords(e.latlng);
        try {
          const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}&zoom=18&addressdetails=1`;
          const res = await fetch(url, { headers: { 'Accept-Language': 'ru' } });
          const data = await res.json();
          if (data && data.address && addressEl) {
            addressEl.value = shortAddress(data.address);
          }
        } catch(_) {}
      });

      if (searchBtn) {
        searchBtn.addEventListener('click', async () => {
          let query = addressEl ? addressEl.value.trim() : '';
          if (errorBox) { errorBox.classList.add('hidden'); errorBox.textContent = ""; }

          const countryText = (document.getElementById('country')?.selectedOptions?.[0]?.text) || '';
          const cityText    = (document.getElementById('city')?.selectedOptions?.[0]?.text) || '';

          if (!query && cityText) query = `${cityText}, ${countryText}`;
          if (!query) {
            if (errorBox) { errorBox.textContent = "Введите адрес или выберите город"; errorBox.classList.remove('hidden'); }
            return;
          }

          try {
            const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=1&q=${encodeURIComponent(query)}`;
            const res = await fetch(url, { headers: { 'Accept-Language': 'ru' } });
            const data = await res.json();

            if (!Array.isArray(data) || !data.length) {
              if (errorBox) { errorBox.textContent = "❌ Такой адрес не найден."; errorBox.classList.remove('hidden'); }
              return;
            }

            const found = data[0];
            const latlng = [parseFloat(found.lat), parseFloat(found.lon)];
            map.setView(latlng, 14);
            marker.setLatLng(latlng);
            updateCoords({lat: latlng[0], lng: latlng[1]});

            if (found.address && addressEl) {
              addressEl.value = shortAddress(found.address);
            } else if (found.display_name && addressEl) {
              addressEl.value = found.display_name;
            }
          } catch(_) {}
        });
      }

      const citySelectEl = document.getElementById('city');
      if (citySelectEl) {
        citySelectEl.addEventListener('change', async function() {
          const countryText = (document.getElementById('country')?.selectedOptions?.[0]?.text) || '';
          const cityText    = this?.selectedOptions?.[0]?.text || '';
          if (!cityText) return;
          try {
            const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=1&q=${encodeURIComponent(`${cityText}, ${countryText}`)}`;
            const res = await fetch(url, { headers: { 'Accept-Language': 'ru' } });
            const data = await res.json();
            if (Array.isArray(data) && data.length) {
              const f = data[0];
              const latlng = [parseFloat(f.lat), parseFloat(f.lon)];
              map.setView(latlng, 13);
              marker.setLatLng(latlng);
              updateCoords({lat: latlng[0], lng: latlng[1]});
              if (addressEl) addressEl.value = `${cityText}${countryText ? ', ' + countryText : ''}`;
            }
          } catch(_) {}
        });
      }
    }

    // ===============================================================
    // === ГАЛЕРЕЯ: удаление фото ====================================
    // ===============================================================
    const gallery = document.getElementById('gallery-container');
    if (gallery) {
      gallery.addEventListener('click', async (e) => {
        const target = e.target;
        if (!target || !target.dataset || !target.dataset.path) return;

        const url = "{{ $product->exists ? route('seller.products.gallery.delete', $product) : '' }}";
        if (!url) { alert('💡 Сначала сохраните товар, чтобы можно было удалять фото.'); return; }
        if (!confirm('Удалить это фото из галереи?')) return;

        const path = target.dataset.path;
        try {
          const res = await fetch(url, {
            method: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ path })
          });

          if (!res.ok) {
            const text = await res.text();
            alert('Ошибка: ' + res.status + ' — ' + text.substring(0, 200));
            return;
          }
          const data = await res.json();
          if (data.success) {
            target.closest('.relative')?.remove();
          } else {
            alert('Ошибка при удалении изображения');
          }
        } catch(_) {
          alert('Ошибка при удалении изображения');
        }
      });
    }
  });
  </script>
</x-app-layout>
