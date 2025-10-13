@extends('admin.layout')

@section('title', 'Редактировать товар')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">✏️ Редактировать товар</h1>
        <a href="{{ route('admin.products.index') }}"
           class="px-4 py-2 text-sm bg-gray-200 rounded hover:bg-gray-300">
            ⬅ Назад к товарам
        </a>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
        {{-- Ошибки --}}
        @if ($errors->any())
            <div class="mb-4 p-4 rounded bg-red-100 text-red-700">
                <strong>Ошибки при сохранении:</strong>
                <ul class="list-disc ml-5 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Название + slug --}}
            <div class="grid md:grid-cols-2 gap-4" x-data="slugHelper()">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Название</label>
                    <input type="text" name="title" x-model="title"
                           value="{{ old('title', $product->title) }}"
                           class="mt-1 block w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                           required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Slug</label>
                    <div class="flex gap-2">
                        <input type="text" name="slug" x-model="slug"
                               value="{{ old('slug', $product->slug) }}"
                               class="mt-1 block w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <button type="button" @click="makeSlug()"
                                class="mt-1 px-3 py-2 text-sm bg-gray-100 rounded hover:bg-gray-200">
                            Сгенерировать
                        </button>
                    </div>
                </div>
            </div>

            {{-- Цена + Количество + Продавец --}}
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Цена</label>
                    <input type="number" step="0.01" name="price"
                           value="{{ old('price', $product->price) }}"
                           class="mt-1 block w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                           required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Количество</label>
                    <input type="number" name="stock"
                           value="{{ old('stock', $product->stock) }}"
                           class="mt-1 block w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                           required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Продавец</label>
                    <select name="user_id"
                            class="mt-1 block w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                        @foreach($sellers as $seller)
                            <option value="{{ $seller->id }}"
                                {{ old('user_id', $product->user_id) == $seller->id ? 'selected' : '' }}>
                                {{ $seller->name }} (ID: {{ $seller->id }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Каскадные категории --}}
            <div x-data="categorySelect()" x-init="init()" class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Категория</label>
                <div id="category-selects" class="space-y-2">
                    <select @change="loadChildren($event, 0)"
                            name="categories[0]"
                            class="w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">— Выберите категорию —</option>
                        @foreach($categories as $parent)
                            <option value="{{ $parent->id }}"
                                {{ old('category_id', $product->category_id) == $parent->id ? 'selected' : '' }}>
                                {{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="category_id" x-model="finalCategory"
                       value="{{ old('category_id', $product->category_id) }}">
            </div>

            {{-- Страна и город --}}
            <div 
                x-data="cityPicker()" 
                x-init="init('{{ old('country_id', $product->country_id) }}','{{ old('city_id', $product->city_id) }}')"
            >
                <label class="block text-sm font-medium text-gray-700">Страна</label>
                <select name="country_id" x-model="country" @change="loadCities()"
                        class="mt-1 block w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">— Выберите страну —</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>

                <label class="block text-sm font-medium text-gray-700 mt-4">Город</label>
                <select name="city_id" x-model="city" :disabled="!country"
                        class="mt-1 block w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">
                        <span x-text="country ? '— Выберите город —' : '— Сначала выберите страну —'"></span>
                    </option>
                    <template x-for="c in cities" :key="c.id">
                        <option :value="c.id" x-text="c.name"></option>
                    </template>
                </select>
            </div>


            {{-- === Адрес + карта === --}}
<div>
  <label class="block text-sm font-medium text-gray-700">Адрес (улица, дом)</label>
<input id="address" name="address" type="text"
       class="w-full border rounded-lg px-3 py-2"
       placeholder="Например: ул. Ленина, 2"
       value="{{ old('address', $product->address) }}">


  <button type="button" id="searchAddress"
          class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
    Найти на карте
  </button>

  <p id="addressError" class="text-red-600 text-sm mt-2 hidden"></p>
</div>

<div class="mt-4">
  <label class="block text-sm font-medium text-gray-700 mb-1">Местоположение на карте</label>
  <div id="map" class="w-full h-64 rounded border"></div>

<input type="hidden" id="latitude" name="latitude"
       value="{{ old('latitude', $product->latitude) }}">
<input type="hidden" id="longitude" name="longitude"
       value="{{ old('longitude', $product->longitude) }}">


  <p class="text-xs text-gray-500 mt-2">
    📍 Перетащите метку или кликните по карте, чтобы указать координаты.
  </p>
</div>

{{-- Подключение leaflet --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Координаты из БД (PHP вставит реальные числа)
    const productLat = {{ $product->latitude ?? 'null' }};
    const productLng = {{ $product->longitude ?? 'null' }};

    // Приоритет: old() → координаты из БД → дефолт
    let lat = {{ old('latitude', 'null') }} ?? productLat ?? 47.0105;
    let lng = {{ old('longitude', 'null') }} ?? productLng ?? 28.8638;

    // Если данные NaN или null — fallback
    if (!lat || isNaN(lat)) lat = productLat ?? 47.0105;
    if (!lng || isNaN(lng)) lng = productLng ?? 28.8638;

    let zoom = (lat && lng) ? 13 : 7;


    let map = L.map('map').setView([lat, lng], zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    let marker = L.marker([lat, lng], {draggable:true}).addTo(map);

    function updateCoords(latlng) {
        document.getElementById('latitude').value = latlng.lat.toFixed(6);
        document.getElementById('longitude').value = latlng.lng.toFixed(6);
    }
    updateCoords(marker.getLatLng());

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

    document.getElementById('searchAddress').addEventListener('click', async function() {
        let query = document.getElementById('address').value.trim();
        let errorBox = document.getElementById('addressError');
        errorBox.classList.add('hidden');
        errorBox.textContent = "";

        let country = document.querySelector('[name="country_id"]');
        let city    = document.querySelector('[name="city_id"]');
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
        if (found.address) {
            document.getElementById('address').value = shortAddress(found.address);
        }
    });

    document.querySelector('[name="city_id"]').addEventListener('change', async function() {
        let country = document.querySelector('[name="country_id"]');
        let city    = document.querySelector('[name="city_id"]');
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







            {{-- Статус --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Статус</label>
                <select name="status"
                        class="mt-1 block w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="1" {{ old('status', $product->status) == 1 ? 'selected' : '' }}>Опубликован</option>
                    <option value="0" {{ old('status', $product->status) == 0 ? 'selected' : '' }}>Черновик</option>
                </select>
            </div>

            {{-- Изображения --}}
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Главное изображение</label>
                    @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" class="h-24 rounded border mb-2">
                    @endif
                    <input type="file" name="image" accept="image/*" class="mt-1 block w-full">
                </div>
              


                {{-- Галерея (несколько) --}}
<div>
  <label class="block text-sm font-medium text-gray-700">Галерея изображений</label>

  {{-- Отображаем существующие изображения --}}
  @php
      $gallery = is_array($product->gallery)
          ? $product->gallery
          : (json_decode($product->gallery, true) ?? []);
  @endphp

  @if(!empty($gallery))
    <div id="gallery-container" class="flex flex-wrap gap-3 mt-2">
      @foreach($gallery as $img)
        <div class="relative group">
          <img src="{{ asset('storage/' . $img) }}"
               alt="Фото"
               class="w-20 h-20 object-cover rounded border">
          <button type="button"
                  data-path="{{ $img }}"
                  class="absolute top-0 right-0 bg-red-600 text-white text-xs px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition">
            ✕
          </button>
        </div>
      @endforeach
    </div>
  @else
    <p class="text-gray-400 text-sm mt-1">Нет загруженных фото</p>
  @endif

  {{-- Загрузка новых фото --}}
  <input type="file" name="gallery[]" multiple accept="image/*" class="mt-2 block w-full border rounded px-3 py-2">
</div>

{{-- JS для AJAX удаления --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  const gallery = document.getElementById('gallery-container');
  if (!gallery) return;

  gallery.addEventListener('click', async (e) => {
    if (!e.target.dataset.path) return;
    if (!confirm('Удалить это фото из галереи?')) return;

    const path = e.target.dataset.path;
    const url = "{{ route('admin.products.gallery.delete', $product) }}";

    const res = await fetch(url, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ path })
    });

    try {
      const data = await res.json();
      if (data.success) {
        e.target.closest('.relative').remove();
      } else {
        alert('Ошибка: ' + (data.error || 'Не удалось удалить'));
      }
    } catch (err) {
      alert('Ошибка при удалении изображения');
    }
  });
});
</script>


            {{-- Описание --}}
            <div>
                <label class="block text-sm font-medium text-gray-700">Описание</label>
                <textarea name="description" rows="4"
                          class="mt-1 block w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500">{{ old('description', $product->description) }}</textarea>
            </div>

            {{-- Кнопки --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.products.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                    Отмена
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    💾 Обновить
                </button>
            </div>
        </form>
    </div>

    {{-- Скрипты --}}
    <script>
        function slugHelper() {
            return {
                title: @json(old('title', $product->title)),
                slug: @json(old('slug', $product->slug)),
                makeSlug() {
                    const map = {а:'a',б:'b',в:'v',г:'g',д:'d',е:'e',ё:'e',ж:'zh',з:'z',и:'i',й:'y',
                        к:'k',л:'l',м:'m',н:'n',о:'o',п:'p',р:'r',с:'s',т:'t',у:'u',ф:'f',
                        х:'h',ц:'c',ч:'ch',ш:'sh',щ:'sch',ъ:'',ы:'y',ь:'',э:'e',ю:'yu',я:'ya'};
                    let s = (this.slug || this.title || '').toString().trim().toLowerCase();
                    s = s.replace(/[\u0400-\u04FF]/g, ch => map[ch] ?? ch);
                    s = s.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').substring(0, 80);
                    this.slug = s;
                }
            }
        }

        function categorySelect() {
            return {
                finalCategory: '{{ old('category_id', $product->category_id) }}',
                async init() {
                    if (this.finalCategory) {
                        await this.loadChain(this.finalCategory);
                    }
                },
                async loadChildren(event, level) {
                    const parentId = event.target.value;
                    this.finalCategory = parentId;
                    document.querySelectorAll('#category-selects select').forEach((el, i) => {
                        if (i > level) el.remove();
                    });
                    if (!parentId) return;
                    const res = await fetch(`/categories/${parentId}/children`);
                    const data = await res.json();
                    if (data.length > 0) {
                        const select = document.createElement('select');
                        select.name = `categories[${level + 1}]`;
                        select.className = 'w-full border rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500';
                        select.innerHTML = `<option value="">— Выберите подкатегорию —</option>`;
                        data.forEach(cat => {
                            select.innerHTML += `<option value="${cat.id}" ${cat.id == this.finalCategory ? 'selected' : ''}>${cat.name}</option>`;
                        });
                        select.addEventListener('change', (e) => this.loadChildren(e, level + 1));
                        document.getElementById('category-selects').appendChild(select);

                        if (data.some(cat => cat.id == this.finalCategory)) {
                            await this.loadChildren({target: select}, level + 1);
                        }
                    }
                },
                async loadChain(categoryId) {
                    let parentId = categoryId;
                    let chain = [];
                    while (parentId) {
                        const res = await fetch(`/categories/${parentId}/parent`);
                        const data = await res.json();
                        if (data && data.parent_id) {
                            chain.unshift(data.parent_id);
                            parentId = data.parent_id;
                        } else break;
                    }
                    let level = 0;
                    for (const id of chain) {
                        const select = document.querySelector(`#category-selects select[name="categories[${level}]"]`);
                        if (select) {
                            select.value = id;
                            await this.loadChildren({target: select}, level);
                            level++;
                        }
                    }
                }
            }
        }


function cityPicker() {
    return {
        country: '',
        city: '',
        cities: [],
        async init(initCountry = '', initCity = '') {
            this.country = String(initCountry || '');
            this.city = String(initCity || '');

            // если страна указана, загружаем города
            if (this.country) {
                await this.loadCities(true);

                // после загрузки городов проставляем выбранный
                this.$nextTick(() => {
                    if (this.city && this.cities.length > 0) {
                        const citySelect = document.querySelector('[name="city_id"]');
                        if (citySelect) citySelect.value = this.city;
                    }
                });
            }


        },

        async loadCities(preserveSelected = false) {
            if (!this.country) {
                this.cities = [];
                this.city = '';
                return;
            }
            try {
                const res = await fetch(`/countries/${this.country}/cities`);
                this.cities = await res.json();

                if (preserveSelected) {
                    const exists = this.cities.some(c => String(c.id) === String(this.city));
                    if (!exists) this.city = '';
                } else {
                    this.city = '';
                }
            } catch (e) {
                console.error('Ошибка загрузки городов', e);
                this.cities = [];
                this.city = '';
            }
        }
    }
}

    </script>
@endsection
