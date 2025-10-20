{{-- resources/views/seller/products/form.blade.php --}}
<x-app-layout :title="$product->exists ? 'Редактирование' : 'Добавление'">
  <h1 class="text-2xl font-bold mb-4">
    {{ $product->exists ? 'Редактирование' : 'Добавление' }} товара
  </h1>

  {{-- Форма добавления / редактирования товара --}}
  <form method="post" enctype="multipart/form-data"
        action="{{ $product->exists ? route('seller.products.update',$product) : route('seller.products.store') }}"
        class="max-w-2xl bg-white border rounded p-4 space-y-3">
    @csrf
    @if($product->exists) @method('PUT') @endif

    {{-- === Название === --}}
    <div>
      <label class="block text-sm">Название</label>
      <input name="title" value="{{ old('title',$product->title) }}" class="w-full border rounded p-2"/>
      @error('title') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- === Цена и остаток === --}}
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="block text-sm">Цена (в рублях)</label>
        <input name="price" type="number" step="0.01" min="0"
               value="{{ old('price',$product->price) }}" class="w-full border rounded p-2"/>
        @error('price') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
      </div>
      <div>
        <label class="block text-sm">Остаток</label>
        <input name="stock" type="number" min="0"
               value="{{ old('stock',$product->stock) }}" class="w-full border rounded p-2"/>
        @error('stock') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
      </div>
    </div>

    {{-- === Описание === --}}
    <div>
      <label class="block text-sm">Описание</label>
      <textarea name="description" class="w-full border rounded p-2">{{ old('description',$product->description) }}</textarea>
      @error('description') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- === Категории (каскад, без подгрузок) === --}}
<div id="categories-wrapper">
  <label class="block text-sm mb-1">Категория</label>

  @php
      $currentParent = null;
  @endphp

  {{-- 🟢 1. Корневая категория --}}
  <select name="category_level_1" id="category-root" class="w-full border rounded p-2 mb-2">
      <option value="">-- выберите категорию --</option>
      @foreach($rootCategories as $root)
          <option value="{{ $root->id }}"
              @selected(($categoryChain->first()?->id ?? $product->category_id) == $root->id)>
              {{ $root->name }}
          </option>
      @endforeach
  </select>

  {{-- 🟢 2. Промежуточные уровни --}}
@foreach($categoryChain->slice(1)->unique('id') as $index => $cat)

      @php
          $parentId = $cat->parent_id;
         $siblings = \App\Models\Category::where('parent_id', $parentId)
    ->where('id', '!=', $parentId)
    ->orderBy('name')
    ->get();

      @endphp
      <select name="category_level_{{ $index + 2 }}" class="w-full border rounded p-2 mb-2">
          <option value="">-- выберите подкатегорию --</option>
          @foreach($siblings as $sibling)
              <option value="{{ $sibling->id }}" @selected($sibling->id == $cat->id)>
                  {{ $sibling->name }}
              </option>
          @endforeach
      </select>
  @endforeach

  {{-- 🟢 Скрытое итоговое поле --}}
  <input type="hidden" name="category_id" id="category_id"
         value="{{ old('category_id', $product->category_id) }}">
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const wrapper = document.getElementById('categories-wrapper');
  const hiddenInput = document.getElementById('category_id');

  // Очистить все селекты после выбранного
  function removeNextSelects(currentSelect) {
    let next = currentSelect.nextElementSibling;
    while (next && next.tagName === 'SELECT') {
      next.remove();
      next = currentSelect.nextElementSibling;
    }
  }

  // Загрузить подкатегории
  async function loadChildren(parentId, afterSelect) {
    removeNextSelects(afterSelect);
    hiddenInput.value = parentId; // временно — текущий id

    if (!parentId) return;

    const res = await fetch(`/categories/${parentId}/children`);
    if (!res.ok) return;
    const categories = await res.json();

    if (!categories.length) return; // если подкатегорий нет

    const select = document.createElement('select');
    select.className = "w-full border rounded p-2 mb-2";
    select.innerHTML = `<option value="">-- выберите подкатегорию --</option>`;

    categories.forEach(cat => {
      const opt = document.createElement('option');
      opt.value = cat.id;
      opt.textContent = cat.name;
      select.appendChild(opt);
    });

    // обработчик выбора подкатегории
    select.addEventListener('change', e => {
      hiddenInput.value = e.target.value || parentId;
      loadChildren(e.target.value, select);
    });

    afterSelect.insertAdjacentElement('afterend', select);
  }

  // слушаем выбор верхнего уровня
  wrapper.querySelector('#category-root').addEventListener('change', e => {
    const id = e.target.value;
    hiddenInput.value = id;
    loadChildren(id, e.target);
  });
});
</script>


{{-- 🔹 JS для обновления скрытого поля --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const wrapper = document.getElementById('categories-wrapper');
    wrapper.addEventListener('change', () => {
        const selects = wrapper.querySelectorAll('select');
        let lastValue = '';
        selects.forEach(s => { if (s.value) lastValue = s.value; });
        document.getElementById('category_id').value = lastValue;
    });
});
</script>


    {{-- === Страна === --}}
    <div>
      <label class="block text-sm">Страна</label>
      <select id="country" name="country_id" class="w-full border rounded p-2">
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

    {{-- === Город === --}}
    <div>
      <label class="block text-sm">Город</label>
      <select id="city" name="city_id" class="w-full border rounded p-2">
        <option value="">-- выберите город --</option>
      </select>
      @error('city_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- === Адрес + карта === --}}
    <div>
      <label class="block text-sm">Адрес (улица, дом)</label>
      <input id="address" name="address" type="text" 
             class="w-full border rounded p-2" 
             placeholder="Например: ул. Ленина, 2" 
             value="{{ old('address', $product->address ?? '') }}">

      <button type="button" id="searchAddress" 
              class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
        Найти на карте
      </button>

      {{-- Сообщение об ошибке поиска --}}
      <p id="addressError" class="text-red-600 text-sm mt-2 hidden"></p>
    </div>

    <div class="mt-4">
      <label class="block text-sm mb-1">Местоположение на карте</label>
      <div id="map" class="w-full h-64 rounded border"></div>

      <input type="hidden" id="latitude" name="latitude" 
             value="{{ old('latitude', $product->latitude) }}">
      <input type="hidden" id="longitude" name="longitude" 
             value="{{ old('longitude', $product->longitude) }}">
    </div>

   {{-- Подключение leaflet --}}
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    // === Начальные координаты ===
    const lat = {{ $product->latitude ?? 47.0105 }};
    const lng = {{ $product->longitude ?? 28.8638 }};
    const zoom = {{ $product->latitude ? 14 : 7 }};

    const map = L.map('map').setView([lat, lng], zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // 🔹 только внутри после создания карты
    map.attributionControl.setPrefix(false);
    map.attributionControl.setPosition('bottomleft');

    const marker = L.marker([lat, lng], {draggable:true}).addTo(map);

    // === Обновление скрытых полей (lat/lng) ===
    function updateCoords(latlng) {
        document.getElementById('latitude').value = latlng.lat.toFixed(6);
        document.getElementById('longitude').value = latlng.lng.toFixed(6);
    }
    updateCoords(marker.getLatLng());

    // === Формирование короткого адреса ===
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

    // === Обратное геокодирование при перетаскивании ===
    marker.on('dragend', async (e) => {
        const latlng = e.target.getLatLng();
        updateCoords(latlng);
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&zoom=18&addressdetails=1`;
        const res = await fetch(url, { headers: { 'Accept-Language': 'ru' } });
        const data = await res.json();
        if (data && data.address) {
            document.getElementById('address').value = shortAddress(data.address);
        }
    });

    // === Клик по карте ===
    map.on('click', async (e) => {
        marker.setLatLng(e.latlng);
        updateCoords(e.latlng);
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}&zoom=18&addressdetails=1`;
        const res = await fetch(url, { headers: { 'Accept-Language': 'ru' } });
        const data = await res.json();
        if (data && data.address) {
            document.getElementById('address').value = shortAddress(data.address);
        }
    });

    // === Поиск адреса вручную ===
    document.getElementById('searchAddress').addEventListener('click', async () => {
        let query = document.getElementById('address').value.trim();
        const errorBox = document.getElementById('addressError');
        errorBox.classList.add('hidden');
        errorBox.textContent = "";

        const country = document.getElementById('country');
        const city    = document.getElementById('city');
        const countryText = country?.options[country.selectedIndex]?.text || '';
        const cityText    = city?.options[city.selectedIndex]?.text || '';

        if (!query && cityText) query = `${cityText}, ${countryText}`;
        if (!query) {
            errorBox.textContent = "Введите адрес или выберите город";
            errorBox.classList.remove('hidden');
            return;
        }

        const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=1&q=${encodeURIComponent(query)}`;
        const res = await fetch(url, { headers: { 'Accept-Language': 'ru' } });
        const data = await res.json();

        if (!data.length) {
            errorBox.textContent = "❌ Такой адрес не найден.";
            errorBox.classList.remove('hidden');
            return;
        }

        const found = data[0];
        const latlng = [parseFloat(found.lat), parseFloat(found.lon)];
        map.setView(latlng, 14);
        marker.setLatLng(latlng);
        updateCoords({lat: latlng[0], lng: latlng[1]});

        if (found.address) {
            document.getElementById('address').value = shortAddress(found.address);
        } else if (found.display_name) {
            document.getElementById('address').value = found.display_name;
        }
    });

    // === Авто-прыжок на выбранный город ===
    document.getElementById('city').addEventListener('change', async function() {
        const country = document.getElementById('country');
        const countryText = country?.options[country.selectedIndex]?.text || '';
        const cityText    = this?.options[this.selectedIndex]?.text || '';
        if (!cityText) return;

        const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=1&q=${encodeURIComponent(`${cityText}, ${countryText}`)}`;
        const res = await fetch(url, { headers: { 'Accept-Language': 'ru' } });
        const data = await res.json();

        if (data.length) {
            const f = data[0];
            const latlng = [parseFloat(f.lat), parseFloat(f.lon)];
            map.setView(latlng, 13);
            marker.setLatLng(latlng);
            updateCoords({lat: latlng[0], lng: latlng[1]});
            document.getElementById('address').value = `${cityText}${countryText ? ', ' + countryText : ''}`;
        }
    });
});
</script>


    {{-- === Главное изображение === --}}
    <div>
      <label class="block text-sm">Изображение (главное фото)</label>
      <input type="file" name="image" class="w-full border rounded p-2"/>
      @if($product->image)
        <img src="{{ asset('storage/'.$product->image) }}" class="mt-2 w-40 rounded"/>
      @endif
      @error('image') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

   {{-- === Галерея (несколько фото) === --}}
<div>
  <label class="block text-sm">Галерея</label>
  <input type="file" name="gallery[]" multiple class="w-full border rounded p-2"/>

  {{-- ✅ Отображение уже загруженных фото --}}
  @php
      $gallery = is_array($product->gallery)
          ? $product->gallery
          : (json_decode($product->gallery, true) ?? []);
  @endphp

  @if(!empty($gallery))
    <div id="gallery-container" class="flex gap-2 mt-2 flex-wrap">
      @foreach($gallery as $img)
        @if($img)
          <div class="relative group">
            <img src="{{ asset('storage/'.$img) }}"
                 alt="Фото"
                 class="w-20 h-20 object-cover rounded border">
            <button type="button"
                    data-path="{{ $img }}"
                    class="absolute top-0 right-0 bg-red-600 text-white text-xs px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition">
              ✕
            </button>
          </div>
        @endif
      @endforeach
    </div>
  @else
    <p class="text-gray-400 text-sm mt-1">Нет загруженных фото</p>
  @endif

  @error('gallery') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
</div>

{{-- === JS для удаления фото === --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  const gallery = document.getElementById('gallery-container');
  if (!gallery) return;

  gallery.addEventListener('click', async (e) => {
    if (!e.target.dataset.path) return;

    const url = "{{ $product->exists ? route('seller.products.gallery.delete', $product) : '' }}";

    if (!url) {
      alert('💡 Сначала сохраните товар, чтобы можно было удалять фото.');
      return;
    }

    if (!confirm('Удалить это фото из галереи?')) return;

    const path = e.target.dataset.path;

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



    try {
      const data = await res.json();
      if (data.success) {
        e.target.closest('.relative').remove();
      } else {
        alert('Ошибка при удалении изображения');
      }
    } catch {
      alert('Ошибка при удалении изображения');
    }
  });
});
</script>


    {{-- === Кнопка сохранить === --}}
    <div class="pt-2">
      <button class="px-4 py-2 bg-emerald-600 text-white rounded">Сохранить</button>
    </div>
  </form>

{{-- === JS для каскадных категорий и загрузки городов === --}}
<script>
document.addEventListener('DOMContentLoaded', () => {


    // === AJAX загрузка городов по стране ===
    const countrySelect = document.getElementById('country');
    const citySelect = document.getElementById('city');
    const preselectedCountryId = "{{ old('country_id', optional($product->city)->country_id) }}";
    const preselectedCityId = "{{ old('city_id', $product->city_id) }}";

    async function loadCities(countryId, selectedCityId = null) {
        citySelect.innerHTML = '<option value="">-- выберите город --</option>';
        if (!countryId) return;
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
    }

    countrySelect.addEventListener('change', () => loadCities(countrySelect.value, null));

    if (preselectedCountryId) {
        countrySelect.value = preselectedCountryId;
        loadCities(preselectedCountryId, preselectedCityId);
    }





});
</script>
</x-app-layout>
