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

    {{-- === Категории (каскад) === --}}
    <div id="categories-wrapper">
      <label class="block text-sm">Категория</label>
      <select id="category-root" name="category_id" class="w-full border rounded p-2">
        <option value="">-- выберите категорию --</option>
        @foreach(($rootCategories ?? []) as $cat)
          <option value="{{ $cat->id }}"
            @selected(old('category_level_1', $product->category_id ?? null) == $cat->id)>
            {{ $cat->name }}
          </option>
        @endforeach
      </select>
    </div>

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

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        // === Начальные координаты ===
        let lat = {{ $product->latitude ?? 47.0105 }};
        let lng = {{ $product->longitude ?? 28.8638 }};
        let zoom = {{ $product->latitude ? 14 : 7 }};

        let map = L.map('map').setView([lat, lng], zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        let marker = L.marker([lat, lng], {draggable:true}).addTo(map);

        // === Обновление скрытых полей (lat/lng) ===
        function updateCoords(latlng) {
            document.getElementById('latitude').value = latlng.lat.toFixed(6);
            document.getElementById('longitude').value = latlng.lng.toFixed(6);
        }
        updateCoords(marker.getLatLng());

        // === Формирование короткого адреса ===
        function shortAddress(addr) {
            let parts = [];
            if (addr.road) parts.push(addr.road);
            if (addr.house_number) parts.push(addr.house_number);
            if (addr.city) parts.push(addr.city);
            else if (addr.town) parts.push(addr.town);
            else if (addr.village) parts.push(addr.village);
            if (addr.country) parts.push(addr.country);
            return parts.join(', ');
        }

        // === Обратное геокодирование при перетаскивании ===
        marker.on('dragend', async function(e) {
            let latlng = e.target.getLatLng();
            updateCoords(latlng);
            let url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&zoom=18&addressdetails=1`;
            let res = await fetch(url);
            let data = await res.json();
            if (data && data.address) {
                document.getElementById('address').value = shortAddress(data.address);
            }
        });

        // === Клик по карте ===
        map.on('click', async function(e) {
            marker.setLatLng(e.latlng);
            updateCoords(e.latlng);
            let url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}&zoom=18&addressdetails=1`;
            let res = await fetch(url);
            let data = await res.json();
            if (data && data.address) {
                document.getElementById('address').value = shortAddress(data.address);
            }
        });

        // === Поиск адреса вручную ===
        document.getElementById('searchAddress').addEventListener('click', async function() {
            let query = document.getElementById('address').value.trim();
            let errorBox = document.getElementById('addressError');
            errorBox.classList.add('hidden');
            errorBox.textContent = "";

            let country = document.getElementById('country');
            let city    = document.getElementById('city');
            let countryText = country?.options[country.selectedIndex]?.text || '';
            let cityText    = city?.options[city.selectedIndex]?.text || '';

            if (!query && cityText) {
                query = `${cityText}, ${countryText}`;
            }
            if (!query) {
                errorBox.textContent = "Введите адрес или выберите город";
                errorBox.classList.remove('hidden');
                return;
            }

            let url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`;
            let res = await fetch(url);
            let data = await res.json();

            if (data.length === 0) {
                errorBox.textContent = "❌ Такой адрес не найден.";
                errorBox.classList.remove('hidden');
                return;
            }

            let found = data[0];
            let latlng = [parseFloat(found.lat), parseFloat(found.lon)];
            map.setView(latlng, 14);
            marker.setLatLng(latlng);
            updateCoords({lat: latlng[0], lng: latlng[1]});

            // адрес из city, country
          document.getElementById('address').value = shortAddress(data[0].address);


        });

        // === Авто-прыжок на выбранный город ===
        document.getElementById('city').addEventListener('change', async function() {
            let country = document.getElementById('country');
            let city    = document.getElementById('city');
            let countryText = country?.options[country.selectedIndex]?.text || '';
            let cityText    = city?.options[city.selectedIndex]?.text || '';

            if (!cityText) return;

            let query = `${cityText}, ${countryText}`;
            let url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`;
            let res = await fetch(url);
            let data = await res.json();

            if (data.length > 0) {
                let found = data[0];
                let latlng = [parseFloat(found.lat), parseFloat(found.lon)];
                map.setView(latlng, 13);
                marker.setLatLng(latlng);
                updateCoords({lat: latlng[0], lng: latlng[1]});
                document.getElementById('address').value = `${cityText}, ${countryText}`;
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
      @if(is_array($product->gallery) && count($product->gallery))
        <div class="flex gap-2 mt-2 flex-wrap">
          @foreach($product->gallery as $img)
            <img src="{{ asset('storage/'.$img) }}" class="w-20 h-20 rounded border"/>
          @endforeach
        </div>
      @endif
      @error('gallery') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- === Кнопка сохранить === --}}
    <div class="pt-2">
      <button class="px-4 py-2 bg-emerald-600 text-white rounded">Сохранить</button>
    </div>
  </form>

  {{-- === JS для каскадных категорий и загрузки городов === --}}
  <script>
  document.addEventListener('DOMContentLoaded', () => {
      // === Каскадные категории ===
      const wrapper = document.getElementById('categories-wrapper');
      async function fetchChildren(parentId) {
          if (!parentId) return [];
          const res = await fetch(`{{ url('/categories') }}/${parentId}/children`);
          return res.ok ? res.json() : [];
      }
      function createSelect(level, placeholder) {
          const div = document.createElement('div');
          div.className = "mt-2";
          const label = document.createElement('label');
          label.className = 'block text-sm';
          label.textContent = placeholder;
          const select = document.createElement('select');
          select.className = 'w-full border rounded p-2 mt-1';
          select.name = `category_level_${level}`;
          select.innerHTML = `<option value="">-- выберите --</option>`;
          div.appendChild(label);
          div.appendChild(select);
          wrapper.appendChild(div);
          return select;
      }
      wrapper.addEventListener('change', async (e) => {
          if (e.target.tagName !== 'SELECT') return;
          let currentDiv = e.target.closest('div');
          const allDivs = Array.from(wrapper.querySelectorAll('div'));
          const index = allDivs.indexOf(currentDiv);
          allDivs.slice(index + 1).forEach(div => div.remove());
          const children = await fetchChildren(e.target.value);
          if (children.length > 0) {
              const nextLevel = wrapper.querySelectorAll('select').length + 1;
              const placeholders = ['Категория','Подкатегория','Под-подкатегория','Под-уровень'];
              const select = createSelect(nextLevel, placeholders[nextLevel - 1] || `Уровень ${nextLevel}`);
              children.forEach(c => {
                  const opt = document.createElement('option');
                  opt.value = c.id;
                  opt.textContent = c.name;
                  select.appendChild(opt);
              });
          }
      });

      // === AJAX загрузка городов по стране ===
      const countrySelect = document.getElementById('country');
      const citySelect = document.getElementById('city');
      const preselectedCountryId = "{{ old('country_id', optional($product->city)->country_id) }}";
      const preselectedCityId    = "{{ old('city_id', $product->city_id) }}";

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
