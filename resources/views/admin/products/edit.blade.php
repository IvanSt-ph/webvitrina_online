@extends('admin.layout')

@section('title', 'Редактировать товар')

@section('content')
@php
    $gallery = is_array($product->gallery) ? $product->gallery : (json_decode($product->gallery, true) ?? []);
    $currentSellerProfile = $sellerPlanProfiles[$product->user_id] ?? null;
    $statusMeta = [
        'active' => ['label' => 'Опубликован', 'class' => 'bg-emerald-50 text-emerald-700'],
        'draft' => ['label' => 'Черновик', 'class' => 'bg-amber-50 text-amber-700'],
        'blocked' => ['label' => 'Заблокирован', 'class' => 'bg-rose-50 text-rose-700'],
    ][$product->status] ?? ['label' => $product->status, 'class' => 'bg-slate-100 text-slate-600'];
@endphp

<div class="space-y-5">
    <header class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-indigo-700">
                <i class="ri-arrow-left-line"></i>
                Назад к товарам
            </a>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <h1 class="truncate text-2xl font-bold text-slate-950">{{ $product->title }}</h1>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">ID {{ $product->id }}</span>
                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $statusMeta['class'] }}">
                    {{ $statusMeta['label'] }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-500">Проверьте карточку, медиа, продавца, категорию и координаты товара.</p>
        </div>

        <div class="grid grid-cols-3 gap-2 text-center text-xs sm:min-w-[360px]">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="font-bold text-slate-950">{{ number_format((float) $product->price, 0, ',', ' ') }}</div>
                <div class="mt-1 text-slate-500">цена</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="font-bold text-slate-950">{{ $product->stock }}</div>
                <div class="mt-1 text-slate-500">остаток</div>
            </div>
            <div class="rounded-lg border {{ $currentSellerProfile['class'] ?? 'border-slate-200 bg-slate-50 text-slate-700' }} p-3">
                <div class="font-bold">{{ $currentSellerProfile['label'] ?? 'Уровень магазина' }}</div>
                <div class="mt-1 opacity-75">{{ $currentSellerProfile ? $currentSellerProfile['used'].'/'.$currentSellerProfile['limit_label'] : 'продавец' }}</div>
            </div>
        </div>
    </header>

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-700">
            <div class="mb-2 flex items-center gap-2 font-bold">
                <i class="ri-error-warning-line"></i>
                Ошибки при сохранении
            </div>
            <ul class="list-disc space-y-1 pl-5 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
        @csrf
        @method('PUT')

        <div class="space-y-5">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <i class="ri-file-text-line text-indigo-600"></i>
                    <h2 class="font-bold text-slate-950">Основная информация</h2>
                </div>

                <div class="grid gap-4 md:grid-cols-2" x-data="slugHelper()">
                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-800">Название</span>
                        <input type="text" name="title" x-model="title" value="{{ old('title', $product->title) }}" required
                               class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-800">Slug</span>
                        <div class="flex gap-2">
                            <input type="text" name="slug" x-model="slug" value="{{ old('slug', $product->slug) }}"
                                   class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                            <button type="button" @click="makeSlug()" class="inline-flex h-11 shrink-0 items-center justify-center rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                <i class="ri-magic-line"></i>
                            </button>
                        </div>
                    </label>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-4" x-data="{ sku: @js(old('sku', $product->sku)), generate() { this.sku = 'PRD-' + (Math.floor(Math.random() * 90000) + 10000); } }">
                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-800">Артикул</span>
                        <div class="flex gap-2">
                            <input type="text" name="sku" x-model="sku" value="{{ old('sku', $product->sku) }}" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                            <button type="button" @click="generate()" class="h-11 rounded-lg border border-slate-200 px-3 text-slate-700 hover:bg-slate-50" title="Сгенерировать"><i class="ri-refresh-line"></i></button>
                        </div>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-800">Цена</span>
                        <input type="number" step="0.01" name="price" value="{{ old('price', $product->price) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-800">Старая цена</span>
                        <input type="number" step="0.01" name="old_price" value="{{ old('old_price', $product->old_price) }}" placeholder="Если есть скидка" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                        <span class="mt-1 block text-xs text-slate-400">Должна быть выше текущей цены.</span>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-800">Количество</span>
                        <input type="number" name="stock" value="{{ old('stock', $product->stock) }}" required class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    </label>
                </div>

                <label class="mt-4 block">
                    <span class="mb-2 block text-sm font-bold text-slate-800">Описание</span>
                    <textarea name="description" rows="5" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">{{ old('description', $product->description) }}</textarea>
                </label>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <i class="ri-folder-3-line text-indigo-600"></i>
                    <h2 class="font-bold text-slate-950">Категория и локация</h2>
                </div>

                <div x-data="categorySelect()" x-init="init()" class="space-y-2">
                    <label class="block text-sm font-bold text-slate-800">Категория</label>
                    <div id="category-selects" class="space-y-2">
                        <select @change="loadChildren($event, 0)" name="categories[0]" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                            <option value="">Выберите категорию</option>
                            @foreach($categories as $parent)
                                <option value="{{ $parent->id }}" {{ old('category_id', $product->category_id) == $parent->id ? 'selected' : '' }}>{{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="category_id" x-model="finalCategory" value="{{ old('category_id', $product->category_id) }}">
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2" x-data="cityPicker()" x-init="init(@js(old('country_id', optional($product->city)->country_id)), @js(old('city_id', $product->city_id)))">
                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-800">Страна</span>
                        <select name="country_id" x-model="country" @change="loadCities()" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                            <option value="">Выберите страну</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-2 block text-sm font-bold text-slate-800">Город</span>
                        <select name="city_id" x-model="city" :disabled="!country" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100 disabled:bg-slate-50">
                            <option value="">Выберите город</option>
                            <template x-for="c in cities" :key="c.id">
                                <option :value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                    </label>
                </div>

                <div class="mt-4">
                    <label class="mb-2 block text-sm font-bold text-slate-800">Адрес</label>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input id="address" name="address" type="text" placeholder="Например: ул. Ленина, 2" value="{{ old('address', $product->address) }}" class="h-11 flex-1 rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                        <button type="button" id="searchAddress" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-700">
                            <i class="ri-map-pin-line"></i>
                            Найти
                        </button>
                    </div>
                    <p id="addressError" class="mt-2 hidden text-sm text-rose-600"></p>
                </div>

                <div class="mt-4">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label class="block text-sm font-bold text-slate-800">Местоположение на карте</label>
                        <span class="text-xs text-slate-400">Метка перетаскивается</span>
                    </div>
                    <div id="map" class="h-72 w-full rounded-xl border border-slate-200"></div>
                    <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', $product->latitude) }}">
                    <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude', $product->longitude) }}">
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <i class="ri-settings-3-line text-indigo-600"></i>
                    <h2 class="font-bold text-slate-950">Публикация</h2>
                </div>

                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-800">Статус</span>
                    <select name="status" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                        <option value="active" {{ old('status', $product->status) === 'active' ? 'selected' : '' }}>Опубликован</option>
                        <option value="draft" {{ old('status', $product->status) === 'draft' ? 'selected' : '' }}>Черновик</option>
                        <option value="blocked" {{ old('status', $product->status) === 'blocked' ? 'selected' : '' }}>Заблокирован администратором</option>
                    </select>
                    @if($product->status === 'blocked')
                        <p class="mt-2 rounded-lg bg-rose-50 p-3 text-xs leading-5 text-rose-700">
                            Сейчас продавец не может вернуть товар на витрину. При смене статуса на “Опубликован” или “Черновик” продавцу уйдёт уведомление о возобновлении.
                        </p>
                    @endif
                </label>

                <label class="mt-4 block">
                    <span class="mb-2 block text-sm font-bold text-slate-800">Продавец</span>
                    <select name="user_id" class="h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100" required>
                        @foreach($sellers as $seller)
                            @php $profile = $sellerPlanProfiles[$seller->id] ?? null; @endphp
                            <option value="{{ $seller->id }}" {{ old('user_id', $product->user_id) == $seller->id ? 'selected' : '' }}>
                                {{ $seller->name }} · {{ $profile['label'] ?? 'Starter' }} · {{ $profile ? $profile['used'].'/'.$profile['limit_label'] : '0/10' }}
                            </option>
                        @endforeach
                    </select>
                </label>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <i class="ri-image-line text-indigo-600"></i>
                    <h2 class="font-bold text-slate-950">Изображения</h2>
                </div>

                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-slate-800">Главное изображение</span>
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" class="aspect-[4/3] w-full object-cover" alt="{{ $product->title }}">
                        @else
                            <div class="flex aspect-[4/3] items-center justify-center text-sm text-slate-400">Нет изображения</div>
                        @endif
                    </div>
                    <input type="file" name="image" accept="image/*" class="mt-3 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </label>

                <div class="mt-5">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <span class="block text-sm font-bold text-slate-800">Галерея</span>
                        <span class="text-xs text-slate-400">{{ count($gallery) }} фото</span>
                    </div>
                    @if(!empty($gallery))
                        <div id="admin-gallery-container" class="grid grid-cols-3 gap-2">
                            @foreach($gallery as $img)
                                <div class="group relative overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                    <img src="{{ asset('storage/' . $img) }}" alt="Фото" class="aspect-square w-full object-cover">
                                    <button type="button" data-path="{{ $img }}" class="absolute right-1 top-1 flex h-7 w-7 items-center justify-center rounded-lg bg-rose-600 text-white opacity-0 transition group-hover:opacity-100" title="Удалить">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-sm text-slate-400">Галерея пустая</div>
                    @endif
                    <input type="file" name="gallery[]" multiple accept="image/*" class="mt-3 block w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>
            </section>

            <div class="sticky bottom-4 rounded-xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur">
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('admin.products.index') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Отмена</a>
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 text-sm font-bold text-white transition hover:bg-indigo-700">
                        <i class="ri-save-3-line"></i>
                        Сохранить
                    </button>
                </div>
            </div>
        </aside>
    </form>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const gallery = document.getElementById('admin-gallery-container');
    if (gallery && gallery.dataset.deleteBound !== '1') {
        gallery.dataset.deleteBound = '1';
        gallery.addEventListener('click', async (e) => {
            const button = e.target.closest('button[data-path]');
            if (!button || !gallery.contains(button)) return;
            e.preventDefault();
            if (button.dataset.deleting === '1') return;
            if (!confirm('Удалить это фото из галереи?')) return;
            button.dataset.deleting = '1';
            button.disabled = true;

            try {
                const res = await fetch(@json(route('admin.products.gallery.delete', $product)), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': @json(csrf_token()),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ path: button.dataset.path })
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.success) throw new Error(data.error || 'Не удалось удалить');
                button.closest('.group')?.remove();
            } catch (error) {
                button.dataset.deleting = '0';
                button.disabled = false;
                alert('Ошибка при удалении изображения: ' + error.message);
            }
        });
    }

    const productLat = {{ $product->latitude ?? 'null' }};
    const productLng = {{ $product->longitude ?? 'null' }};
    let lat = {{ old('latitude', 'null') }} ?? productLat ?? 47.0105;
    let lng = {{ old('longitude', 'null') }} ?? productLng ?? 28.8638;
    if (!lat || isNaN(lat)) lat = productLat ?? 47.0105;
    if (!lng || isNaN(lng)) lng = productLng ?? 28.8638;

    const mapEl = document.getElementById('map');
    if (!mapEl || typeof L === 'undefined') return;
    if (L.DomUtil.get('map') !== null) L.DomUtil.get('map')._leaflet_id = null;

    const map = L.map('map').setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    const updateCoords = (latlng) => {
        document.getElementById('latitude').value = latlng.lat.toFixed(6);
        document.getElementById('longitude').value = latlng.lng.toFixed(6);
    };
    updateCoords(marker.getLatLng());

    const shortAddress = (addr) => {
        const parts = [];
        if (addr.road) parts.push(addr.road);
        if (addr.house_number) parts.push(addr.house_number);
        if (addr.city) parts.push(addr.city);
        else if (addr.town) parts.push(addr.town);
        else if (addr.village) parts.push(addr.village);
        if (addr.country) parts.push(addr.country);
        return parts.join(', ');
    };

    const reverse = async (latlng) => {
        updateCoords(latlng);
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}&zoom=18&addressdetails=1`);
            const data = await res.json();
            if (data?.address) document.getElementById('address').value = shortAddress(data.address);
        } catch (_) {}
    };

    marker.on('dragend', (e) => reverse(e.target.getLatLng()));
    map.on('click', (e) => {
        marker.setLatLng(e.latlng);
        reverse(e.latlng);
    });

    document.getElementById('searchAddress')?.addEventListener('click', async () => {
        let query = document.getElementById('address').value.trim();
        const errorBox = document.getElementById('addressError');
        errorBox.classList.add('hidden');
        errorBox.textContent = '';

        const country = document.querySelector('[name="country_id"]');
        const city = document.querySelector('[name="city_id"]');
        const countryText = country?.options[country.selectedIndex]?.text || '';
        const cityText = city?.options[city.selectedIndex]?.text || '';
        if (!query && cityText) query = `${cityText}, ${countryText}`;

        if (!query) {
            errorBox.textContent = 'Введите адрес или выберите город';
            errorBox.classList.remove('hidden');
            return;
        }

        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`);
            const data = await res.json();
            if (!data.length) {
                errorBox.textContent = 'Такой адрес не найден.';
                errorBox.classList.remove('hidden');
                return;
            }
            const found = data[0];
            const latlng = [parseFloat(found.lat), parseFloat(found.lon)];
            map.setView(latlng, 14);
            marker.setLatLng(latlng);
            updateCoords({ lat: latlng[0], lng: latlng[1] });
        } catch (_) {
            errorBox.textContent = 'Не удалось проверить адрес.';
            errorBox.classList.remove('hidden');
        }
    });
});

function slugHelper() {
    return {
        title: @json(old('title', $product->title)),
        slug: @json(old('slug', $product->slug)),
        makeSlug() {
            const map = {а:'a',б:'b',в:'v',г:'g',д:'d',е:'e',ё:'e',ж:'zh',з:'z',и:'i',й:'y',к:'k',л:'l',м:'m',н:'n',о:'o',п:'p',р:'r',с:'s',т:'t',у:'u',ф:'f',х:'h',ц:'c',ч:'ch',ш:'sh',щ:'sch',ъ:'',ы:'y',ь:'',э:'e',ю:'yu',я:'ya'};
            let s = (this.slug || this.title || '').toString().trim().toLowerCase();
            s = s.replace(/[\u0400-\u04FF]/g, ch => map[ch] ?? ch);
            this.slug = s.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').substring(0, 80);
        }
    };
}

function categorySelect() {
    return {
        finalCategory: {{ (int) old('category_id', $product->category_id) ?: 'null' }},
        async init() {
            if (this.finalCategory) await this.loadChain(this.finalCategory);
        },
        async loadChildren(event, level) {
            const parentId = event.target.value;
            this.finalCategory = parentId;
            document.querySelectorAll('#category-selects select').forEach((el, i) => { if (i > level) el.remove(); });
            if (!parentId) return;

            const res = await fetch(`/admin/categories/${parentId}/children`);
            const data = await res.json();
            if (data.length > 0) {
                const select = document.createElement('select');
                select.name = `categories[${level + 1}]`;
                select.className = 'h-11 w-full rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100';
                select.add(new Option('Выберите подкатегорию', ''));
                data.forEach(cat => select.add(new Option(cat.name, cat.id)));
                select.addEventListener('change', (e) => this.loadChildren(e, level + 1));
                document.getElementById('category-selects').appendChild(select);
            }
        },
        async loadChain(categoryId) {
            const chain = [];
            let currentId = categoryId;
            while (currentId) {
                const res = await fetch(`/admin/categories/${currentId}/parent`);
                const data = await res.json();
                if (data?.parent_id) {
                    chain.unshift(data.parent_id);
                    currentId = data.parent_id;
                } else {
                    break;
                }
            }
            let level = 0;
            for (const id of chain) {
                const select = document.querySelector(`#category-selects select[name="categories[${level}]"]`);
                if (select) {
                    select.value = id;
                    await this.loadChildren({ target: select }, level);
                }
                level++;
            }
            const lastSelect = document.querySelector(`#category-selects select[name="categories[${level}]"]`);
            if (lastSelect) {
                lastSelect.value = categoryId;
                this.finalCategory = categoryId;
            }
        }
    };
}

function cityPicker() {
    return {
        country: '',
        city: '',
        cities: [],
        async init(initCountry = '', initCity = '') {
            this.country = String(initCountry || '');
            this.city = String(initCity || '');
            if (this.country) {
                await this.loadCities(true);
                this.$nextTick(() => {
                    const citySelect = document.querySelector('[name="city_id"]');
                    if (citySelect && this.city) citySelect.value = this.city;
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
                if (!preserveSelected) this.city = '';
                if (preserveSelected && !this.cities.some(c => String(c.id) === String(this.city))) this.city = '';
            } catch (_) {
                this.cities = [];
                this.city = '';
            }
        }
    };
}
</script>

<style>
    #map { box-shadow: 0 0 0 1px #e2e8f0; }
    .leaflet-control-attribution { font-size: 10px; }
</style>
@endsection
