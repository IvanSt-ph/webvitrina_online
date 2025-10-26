{{-- resources/views/seller/products/form.blade.php --}}
<x-app-layout :title="$product->exists ? 'Редактирование товара' : 'Добавление товара'">

  <div class="max-w-3xl mx-auto my-8 space-y-6"
       data-country="{{ old('country_id', optional($product->city)->country_id) }}"
       data-city="{{ old('city_id', $product->city_id) }}">

    {{-- ===== Заголовок ===== --}}
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-semibold text-gray-800">
        {{ $product->exists ? 'Редактирование товара' : 'Добавление товара' }}
      </h1>
      <a href="{{ route('seller.products.index') }}"
         class="text-sm text-indigo-600 hover:text-indigo-800 transition">
        ← Назад к товарам
      </a>
    </div>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $product->exists ? route('seller.products.update',$product) : route('seller.products.store') }}"
          class="space-y-6">
      @csrf
      @if($product->exists) @method('PUT') @endif


      {{-- ================= Название ================= --}}
      <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Название товара</h2>

        <div>
          <input name="title" value="{{ old('title',$product->title) }}"
                 placeholder="Например: Мужская рубашка Slim Fit"
                 class="w-full border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200">
          @error('title') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
      </section>


      {{-- ================= Категория ================= --}}
      <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Категория</h2>
        <div id="categories-wrapper" class="space-y-2">
          <label class="block text-sm font-medium text-gray-600">Выберите категорию</label>
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
          <input type="hidden" name="category_id" id="category_id"
                 value="{{ old('category_id', $product->category_id) }}">
        </div>
      </section>


      {{-- ================= Описание ================= --}}
      <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Описание</h2>
        <textarea name="description" rows="5" placeholder="Опишите товар, его характеристики, преимущества..."
                  class="w-full border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200">{{ old('description',$product->description) }}</textarea>
        @error('description') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
      </section>


      {{-- ================= Местоположение ================= --}}
      <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Местоположение</h2>

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

        {{-- Адрес --}}
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Адрес</label>
          <div class="flex gap-3">
            <input id="address" name="address" type="text"
                   placeholder="Например: ул. Ленина, 2"
                   value="{{ old('address', $product->address ?? '') }}"
                   class="flex-1 border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            <button type="button" id="searchAddress"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
              Найти
            </button>
          </div>
          <p id="addressError" class="text-red-600 text-sm mt-2 hidden"></p>
        </div>

        {{-- Карта --}}
        <div class="mt-2 ">
          <label class="block text-sm font-medium text-gray-700 mb-2">Карта</label>
          <div id="map"
               data-lat="{{ $product->latitude ?? 47.0105 }}"
               data-lng="{{ $product->longitude ?? 28.8638 }}"
               data-zoom="{{ $product->latitude ? 14 : 7 }}"
               class="w-full h-64 rounded-lg border border-gray-300"></div>

          <input type="hidden" id="latitude" name="latitude"
                 value="{{ old('latitude', $product->latitude) }}">
          <input type="hidden" id="longitude" name="longitude"
                 value="{{ old('longitude', $product->longitude) }}">
        </div>
      </section>


      {{-- ================= Цена и валюта ================= --}}
      <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Цена и валюта</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Цена</label>
<input id="base-price" name="price" type="number" step="0.01" min="0" max="1000000"
       value="{{ old('price',$product->price) }}"
       oninput="if(this.value.length > 10) this.value=this.value.slice(0,10)"
       class="w-full border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200">

            <p class="text-sm text-gray-500 mt-1">Введите цену в валюте своей страны.</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Базовая валюта</label>
            <select id="currency_base" name="currency_base"
                    class="w-full border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200">
              <option value="PRB" @selected(old('currency_base', $product->currency_base) === 'PRB')>₽ ПМР</option>
              <option value="MDL" @selected(old('currency_base', $product->currency_base) === 'MDL')>L Молдова</option>
              <option value="UAH" @selected(old('currency_base', $product->currency_base) === 'UAH')>₴ Украина</option>
            </select>
            <p class="text-sm text-gray-500 mt-1">Определяется автоматически по стране.</p>
          </div>
        </div>

        <hr class="my-4 border-gray-100">

        {{-- Автоматически пересчитанные цены --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">₽ ПМР</label>
            <input id="price_prb" name="price_prb" type="number" step="0.01"
                   value="{{ old('price_prb',$product->price_prb) }}"
                   class="w-full border-gray-200 rounded-lg p-3 bg-gray-50" readonly>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">L Молдова</label>
            <input id="price_mdl" name="price_mdl" type="number" step="0.01"
                   value="{{ old('price_mdl',$product->price_mdl) }}"
                   class="w-full border-gray-200 rounded-lg p-3 bg-gray-50" readonly>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">₴ Украина</label>
            <input id="price_uah" name="price_uah" type="number" step="0.01"
                   value="{{ old('price_uah',$product->price_uah) }}"
                   class="w-full border-gray-200 rounded-lg p-3 bg-gray-50" readonly>
          </div>
        </div>
      </section>


      {{-- ================= Остаток ================= --}}
      <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Остаток товара</h2>
        <input name="stock" type="number" min="0" value="{{ old('stock',$product->stock) }}"
               class="w-full border-gray-200 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        <p class="text-sm text-gray-500 mt-1">Количество товара в наличии.</p>
      </section>


      {{-- ================= Изображения ================= --}}
      <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Изображения</h2>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Главное изображение</label>
          <input type="file" name="image" class="w-full border-gray-200 rounded-lg p-2">
          @if($product->image)
            <img src="{{ asset('storage/'.$product->image) }}" class="mt-3 w-40 rounded-lg border border-gray-200">
          @endif
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Галерея</label>
          <input type="file" name="gallery[]" multiple class="w-full border-gray-200 rounded-lg p-2">
          @php
            $gallery = is_array($product->gallery)
                ? $product->gallery
                : (json_decode($product->gallery, true) ?? []);
          @endphp

          @if(!empty($gallery))
            <div id="gallery-container"
                 data-delete-url="{{ $product->exists ? route('seller.products.gallery.delete', $product) : '' }}"
                 class="flex gap-3 mt-3 flex-wrap">
              @foreach($gallery as $img)
                @if($img)
                  <div class="relative group rounded overflow-hidden border border-gray-200">
                    <img src="{{ asset('storage/'.$img) }}" alt="Фото" class="w-20 h-20 object-cover">
                    <button type="button" data-path="{{ $img }}"
                            class="absolute top-1 right-1 bg-gray-800 text-white text-xs px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 transition">
                      ✕
                    </button>
                  </div>
                @endif
              @endforeach
            </div>
          @else
            <p class="text-gray-400 text-sm mt-2">Нет загруженных изображений</p>
          @endif
        </div>
      </section>


      {{-- ================= Кнопка ================= --}}
      <div class="flex justify-end">
        <button type="submit"
                class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
          💾 Сохранить
        </button>
      </div>

    </form>
  </div>

  {{-- Leaflet карта --}}
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

  <style>
    #map .leaflet-control-attribution {
      font-size: 11px;
      color: #666;
      background: rgba(255, 255, 255, 0.8);
      border-radius: 6px;
      padding: 2px 6px;
    }
  </style>

  @vite('resources/js/seller-product-form.js')
  @include('layouts.mobile-bottom-seller-nav')

</x-app-layout>
