{{-- resources/views/seller/products/form.blade.php --}}
<x-seller-layout :title="$product->exists ? 'Редактирование товара' : 'Добавление товара'">

<div class="seller-product-page min-h-screen w-full bg-white px-3 py-4 pb-28 text-neutral-900 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8"
     data-country="{{ old('country_id', optional($product->city)->country_id) }}"
     data-city="{{ old('city_id', $product->city_id) }}">

    @php
      $qualityTitle = trim((string) old('title', $product->title));
      $qualityDescription = trim(strip_tags((string) old('description', $product->description)));
      $qualityGalleryCount = count($product->gallery_images ?? []);
      $qualityHasImage = filled($product->image) || $qualityGalleryCount > 0;
      $qualityAttributeCount = $product->exists ? $product->attributes()->count() : 0;
      $qualityChecks = collect([
        [
          'label' => 'Название понятно покупателю',
          'hint' => 'Минимум 8 символов, без лишнего шума.',
          'done' => mb_strlen($qualityTitle) >= 8,
        ],
        [
          'label' => 'Выбрана точная категория',
          'hint' => 'От неё зависят характеристики и фильтры.',
          'done' => filled(old('category_id', $product->category_id)),
        ],
        [
          'label' => 'Описание помогает купить',
          'hint' => 'Лучше от 120 символов: состояние, комплект, нюансы.',
          'done' => mb_strlen($qualityDescription) >= 120,
        ],
        [
          'label' => 'Заполнены характеристики',
          'hint' => 'Покупатель сможет сравнить товар и найти его через фильтры.',
          'done' => $qualityAttributeCount > 0,
        ],
        [
          'label' => 'Есть фото товара',
          'hint' => 'Главное фото должно показывать товар крупно и понятно.',
          'done' => $qualityHasImage,
        ],
        [
          'label' => 'Цена и остаток указаны',
          'hint' => 'Цена больше нуля, остаток не пустой.',
          'done' => (float) old('price', $product->price) > 0 && old('stock', $product->stock) !== null,
        ],
        [
          'label' => 'Город заполнен',
          'hint' => 'Покупатель сразу понимает, где находится товар.',
          'done' => filled(old('city_id', $product->city_id)),
        ],
      ]);
      $qualityDone = $qualityChecks->where('done', true)->count();
      $qualityTotal = $qualityChecks->count();
      $qualityPercent = $qualityTotal > 0 ? round($qualityDone / $qualityTotal * 100) : 0;
    @endphp


    {{-- ===== Заголовок ===== --}}
    <div class="mb-5 flex flex-col gap-4 sm:mb-6 lg:flex-row lg:items-end lg:justify-between">
      <div class="min-w-0">
        <a href="{{ route('seller.products.index') }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-neutral-500 transition hover:text-brand-600">
          <i class="ri-arrow-left-line" aria-hidden="true"></i>
          <span>Назад к товарам</span>
        </a>
        <div class="mt-4 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
          <i class="ri-box-3-line"></i>
          Карточка товара
        </div>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">
          {{ $product->exists ? 'Редактирование товара' : 'Новый товар' }}
        </h1>
        <p class="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">
          Собери карточку товара без спешки: сначала основные данные, затем цена, остаток и фото.
          Черновик можно сохранить сейчас, а опубликовать позже.
        </p>
      </div>

      <div class="hidden min-w-80 grid-cols-3 overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50 text-center text-xs font-semibold text-neutral-600 lg:grid">
        <div class="border-r border-neutral-200 px-3 py-3">
          <span class="block text-brand-600">1</span>
          Данные
        </div>
        <div class="border-r border-neutral-200 px-3 py-3">
          <span class="block text-brand-600">2</span>
          Цена
        </div>
        <div class="px-3 py-3">
          <span class="block text-brand-600">3</span>
          Фото
        </div>
      </div>
    </div>

    <div class="mb-5 rounded-2xl border p-4 sm:p-5 {{ $sellerPlanProfile['class'] }}">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div class="text-xs font-bold uppercase tracking-wide opacity-70">Статус продавца</div>
          <div class="mt-1 text-lg font-extrabold">{{ $sellerPlanProfile['label'] }}</div>
          <p class="mt-1 text-sm opacity-80">{{ $sellerPlanProfile['description'] }}</p>
        </div>
        <div class="min-w-44">
          <div class="text-right text-sm font-bold">
            {{ $sellerPlanProfile['used'] }} / {{ $sellerPlanProfile['limit_label'] }} товаров
          </div>
          <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/70">
            <div class="h-full rounded-full bg-brand-500" style="width: {{ $sellerPlanProfile['percent'] }}%"></div>
          </div>
        </div>
      </div>
    </div>

    @if($errors->has('product_limit'))
      <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">
        <i class="ri-error-warning-line mr-1"></i>
        {{ $errors->first('product_limit') }}
      </div>
    @endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ $product->exists ? route('seller.products.update',$product) : route('seller.products.store') }}"
          class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
      @csrf
      @if($product->exists) @method('PUT') @endif

      <div class="space-y-5">

      {{-- ================= Название ================= --}}
      <section class="seller-form-card">
        <div class="seller-section-head">
          <div>
            <p class="seller-section-kicker">01</p>
            <h2 class="seller-section-title">Название товара</h2>
          </div>
          <p class="seller-section-hint">Коротко и понятно, как покупатель будет искать товар.</p>
        </div>

        <div>
          <input name="title" value="{{ old('title',$product->title) }}"
                 placeholder="Например: Мужская рубашка Slim Fit"
                 class="seller-input text-base font-medium">
          @error('title') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
      </section>


{{-- ================= Категория ================= --}}
<section class="seller-form-card">
  <div class="seller-section-head">
    <div>
      <p class="seller-section-kicker">02</p>
      <h2 class="seller-section-title">Категория</h2>
    </div>
    <p class="seller-section-hint">От категории зависят характеристики и фильтры.</p>
  </div>

  @if($categoryMissing)
    <div class="mb-4 p-4 rounded-lg border border-amber-300 bg-amber-50 text-amber-800 flex gap-3 items-start">
        <svg class="w-6 h-6 flex-shrink-0 text-amber-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" 
                  d="M12 9v3m0 4h.01M10.29 3.86L1.82 18a1 1 0 0 0 .86 1.5h18.64a1 1 0 0 0 .86-1.5L13.71 3.86a1 1 0 0 0-1.72 0z"/>
        </svg>

        <div>
            <strong class="block text-sm font-semibold">Категория не выбрана</strong>
            <span class="text-sm">
                Для редактирования характеристик товара, пожалуйста, выбери категорию.
            </span>
        </div>
    </div>
@endif

  <div id="categories-wrapper" class="space-y-2">
    <label class="block text-sm font-medium text-neutral-600">Выберите категорию</label>

    <select name="category_level_1" id="category-root"
            class="seller-input category-select">
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





{{-- 🔹 Характеристики --}}
<section id="attributes-wrapper">
  @include('seller.products.partials.attributes', ['product' => $product])
</section>








      {{-- ================= Описание ================= --}}
      <section class="seller-form-card"
               x-data="{
                 description: @js(old('description', $product->description ?? '')),
                 max: 3000,
                 count() { return Array.from(this.description || '').length },
                 clamp() {
                   const chars = Array.from(this.description || '');
                   if (chars.length > this.max) this.description = chars.slice(0, this.max).join('');
                 }
               }">
        <div class="seller-section-head">
          <div>
            <p class="seller-section-kicker">04</p>
            <h2 class="seller-section-title">Описание</h2>
          </div>
          <p class="seller-section-hint">Состояние, комплектация, размеры, нюансы доставки.</p>
        </div>
        <textarea name="description"
                  rows="7"
                  maxlength="3000"
                  x-model="description"
                  @input="clamp()"
                  placeholder="Опишите товар, комплектацию, состояние, размеры, сценарии применения, гарантию и важные нюансы..."
                  class="seller-input min-h-44 resize-y">{{ old('description',$product->description) }}</textarea>
        <div class="mt-2 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-sm text-neutral-500">
            Хорошее описание отвечает на вопросы покупателя до чата: что входит в комплект, есть ли дефекты, кому подходит товар.
          </p>
          <div class="shrink-0 text-sm font-semibold"
               :class="count() > max * 0.9 ? 'text-amber-600' : 'text-neutral-400'">
            <span x-text="count()"></span>/<span x-text="max"></span>
          </div>
        </div>
        @error('description') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
      </section>


      {{-- ================= Местоположение ================= --}}
      <section class="seller-form-card">
        <div class="seller-section-head">
          <div>
            <p class="seller-section-kicker">05</p>
            <h2 class="seller-section-title">Местоположение</h2>
          </div>
          <p class="seller-section-hint">Покупателю важно сразу понимать город и район.</p>
        </div>

        {{-- Страна --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-neutral-700">Страна</label>
          <select id="country" name="country_id"
                  class="seller-input">
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
        <div>
          <label class="mb-1 block text-sm font-medium text-neutral-700">Город</label>
          <select id="city" name="city_id"
                  class="seller-input">
            <option value="">-- выберите город --</option>
          </select>
          @error('city_id') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        </div>

        {{-- Адрес --}}
        <div class="mt-4">
          <label class="mb-1 block text-sm font-medium text-neutral-700">Адрес</label>
          <div class="flex flex-col gap-3 sm:flex-row">
            <input id="address" name="address" type="text"
                   placeholder="Например: ул. Ленина, 2"
                   value="{{ old('address', $product->address ?? '') }}"
                   class="seller-input flex-1">
            <button type="button" id="searchAddress"
                    class="seller-primary-button inline-flex items-center justify-center gap-2 px-5 py-3">
              <i class="ri-map-pin-search-line text-base"></i>
              <span>Найти</span>
            </button>
          </div>
          <p id="addressError" class="text-red-600 text-sm mt-2 hidden"></p>
        </div>

        {{-- Карта --}}
        <div class="mt-2 ">
          <label class="mb-2 block text-sm font-medium text-neutral-700">Карта</label>
          <div id="map"
               data-lat="{{ $product->latitude ?? 47.0105 }}"
               data-lng="{{ $product->longitude ?? 28.8638 }}"
               data-zoom="{{ $product->latitude ? 14 : 7 }}"
               class="h-72 w-full overflow-hidden rounded-xl border border-neutral-200 bg-neutral-100"></div>

          <input type="hidden" id="latitude" name="latitude"
                 value="{{ old('latitude', $product->latitude) }}">
          <input type="hidden" id="longitude" name="longitude"
                 value="{{ old('longitude', $product->longitude) }}">
        </div>
      </section>


      {{-- ================= Цена и валюта ================= --}}
      <section class="seller-form-card">
        <div class="seller-section-head">
          <div>
            <p class="seller-section-kicker">06</p>
            <h2 class="seller-section-title">Цена и валюта</h2>
          </div>
          <p class="seller-section-hint">Цена пересчитается в остальные валюты автоматически.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-neutral-700">Цена</label>
<input id="base-price" name="price" type="number" step="0.01" min="0" max="1000000"
       value="{{ old('price',$product->price) }}"
       oninput="if(this.value.length > 10) this.value=this.value.slice(0,10)"
       class="seller-input text-lg font-semibold">

            <p class="mt-1 text-sm text-neutral-500">Введите цену в валюте своей страны.</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-neutral-700">Старая цена</label>
            <input id="old-price" name="old_price" type="number" step="0.01" min="0" max="1000000"
                   value="{{ old('old_price', $product->old_price) }}"
                   oninput="if(this.value.length > 10) this.value=this.value.slice(0,10)"
                   class="seller-input text-lg font-semibold">
            <p class="mt-1 text-sm text-neutral-500">Только если была цена выше текущей.</p>
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-neutral-700">Базовая валюта</label>
            <select id="currency_base" name="currency_base"
                    class="seller-input">
              <option value="PRB" @selected(old('currency_base', $product->currency_base) === 'PRB')>₽ Рубль ПМР</option>
              <option value="MDL" @selected(old('currency_base', $product->currency_base) === 'MDL')>L Молдавский Лей</option>
              <option value="UAH" @selected(old('currency_base', $product->currency_base) === 'UAH')>₴ Украинская Гривна</option>
            </select>
            <p class="mt-1 text-sm text-neutral-500">Определяется автоматически по стране.</p>
          </div>
        </div>

        <hr class="my-4 border-neutral-100">

        {{-- Автоматически пересчитанные цены --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="mb-1 block text-sm font-medium text-neutral-700">₽ ПМР</label>
            <input id="price_prb" name="price_prb" type="number" step="0.01"
                   value="{{ old('price_prb',$product->price_prb) }}"
                   class="seller-input bg-neutral-50 text-neutral-600" readonly>
            <input name="old_price_prb" type="hidden" value="{{ old('old_price_prb', $product->old_price_prb) }}">
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-neutral-700">L Молдова</label>
            <input id="price_mdl" name="price_mdl" type="number" step="0.01"
                   value="{{ old('price_mdl',$product->price_mdl) }}"
                   class="seller-input bg-neutral-50 text-neutral-600" readonly>
            <input name="old_price_mdl" type="hidden" value="{{ old('old_price_mdl', $product->old_price_mdl) }}">
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-neutral-700">₴ Украина</label>
            <input id="price_uah" name="price_uah" type="number" step="0.01"
                   value="{{ old('price_uah',$product->price_uah) }}"
                   class="seller-input bg-neutral-50 text-neutral-600" readonly>
            <input name="old_price_uah" type="hidden" value="{{ old('old_price_uah', $product->old_price_uah) }}">
          </div>
        </div>
      </section>


      {{-- ================= Остаток ================= --}}
      <section class="seller-form-card">
        <div class="seller-section-head">
          <div>
            <p class="seller-section-kicker">07</p>
            <h2 class="seller-section-title">Остаток товара</h2>
          </div>
          <p class="seller-section-hint">Если товар один, поставь 1. При заказе остаток спишется.</p>
        </div>
        <input name="stock" type="number" min="0" value="{{ old('stock',$product->stock) }}"
               class="seller-input max-w-xs text-lg font-semibold">
        <p class="mt-1 text-sm text-neutral-500">Количество товара в наличии.</p>
      </section>

      </div>

      <aside class="space-y-5 xl:sticky xl:top-24 xl:self-start">

      {{-- ================= Статус ================= --}}
      <section class="seller-form-card">
        <div class="seller-section-head">
          <div>
            <p class="seller-section-kicker">Публикация</p>
            <h2 class="seller-section-title">Статус</h2>
          </div>
        </div>
        @php
          $statusValue = old('status', $product->status ?: 'draft');
        @endphp
        @if($product->isBlocked())
          <input type="hidden" name="status" value="draft">
          <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <div class="flex items-start gap-3">
              <i class="ri-lock-2-line mt-0.5 text-lg"></i>
              <div>
                <div class="font-bold">Товар заблокирован администратором</div>
                <p class="mt-1 leading-5">
                  Можно исправить описание, фото, цену и характеристики, но вернуть товар на витрину сможет только администратор после проверки.
                </p>
              </div>
            </div>
          </div>
        @else
        <div class="grid grid-cols-1 gap-3">
          <label class="seller-status-option">
            <input type="radio" name="status" value="draft" class="sr-only peer" @checked($statusValue === 'draft')>
            <span class="seller-status-dot bg-amber-400"></span>
            <span>
              <span class="block font-semibold text-neutral-900">Черновик</span>
              <span class="text-xs text-neutral-500">Виден только тебе, можно дополнять позже.</span>
            </span>
          </label>
          <label class="seller-status-option">
            <input type="radio" name="status" value="active" class="sr-only peer" @checked($statusValue === 'active')>
            <span class="seller-status-dot bg-emerald-500"></span>
            <span>
              <span class="block font-semibold text-neutral-900">Опубликовать</span>
              <span class="text-xs text-neutral-500">Товар появится на витрине после сохранения.</span>
            </span>
          </label>
        </div>
        @endif
        @error('status') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        @unless($product->isBlocked())
          <div class="mt-3 rounded-xl bg-brand-50 p-3 text-xs leading-5 text-brand-800">
            Черновик не виден покупателям. Опубликованный товар сразу появится на витрине.
          </div>
        @endunless
      </section>


      {{-- ================= Изображения ================= --}}
      <section class="seller-form-card">
        <div class="seller-section-head">
          <div>
            <p class="seller-section-kicker">Фото</p>
            <h2 class="seller-section-title">Изображения</h2>
          </div>
        </div>

        <div class="mb-4">
          <label class="mb-1 block text-sm font-medium text-neutral-700">Главное изображение</label>
          <label class="seller-upload-zone">
            <input type="file" name="image" class="sr-only" data-main-crop="true" data-preview-target="main-image-preview" accept="image/jpeg,image/png,image/webp">
            <span class="seller-upload-icon"><i class="ri-image-add-line"></i></span>
            <span class="font-semibold text-neutral-900">Выбрать главное фото</span>
            <span class="text-xs text-neutral-500">После выбора можно настроить кадр для карточки товара.</span>
          </label>
          <div id="main-image-preview" class="seller-preview-grid mt-3 hidden"></div>
          <button type="button" id="main-image-open-crop" class="mt-3 hidden rounded-xl border border-brand-200 bg-brand-50 px-3 py-2 text-xs font-semibold text-brand-700 transition hover:bg-brand-100">
            <i class="ri-crop-line mr-1"></i>
            Настроить кадр карточки
          </button>
          @if($product->image)
            <div class="mt-3 max-w-72">
              <img data-image-candidates="{{ json_encode($product->image_thumb_candidates) }}" data-image-fallback="{{ asset(\App\Models\Product::IMAGE_FALLBACK_ASSET) }}" src="{{ $product->image_thumb_url }}" class="w-full rounded-xl border border-neutral-200 object-cover" style="aspect-ratio: 4 / 3.2" alt="Текущее главное фото">
              <p class="mt-2 text-xs text-neutral-500">Текущее главное фото. Новый кадр можно выбрать после загрузки нового файла.</p>
            </div>
          @endif
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-neutral-700">Галерея</label>
          <label class="seller-upload-zone">
            <input type="file" name="gallery[]" multiple class="sr-only" data-preview-target="gallery-preview" accept="image/jpeg,image/png,image/webp">
            <span class="seller-upload-icon"><i class="ri-gallery-upload-line"></i></span>
            <span class="font-semibold text-neutral-900">Добавить фото в галерею</span>
            <span class="text-xs text-neutral-500">Можно выбрать сразу несколько файлов.</span>
          </label>
          <div id="gallery-preview" class="seller-preview-grid mt-3 hidden"></div>
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
                  <div class="group relative overflow-hidden rounded-xl border border-neutral-200">
                    <img data-image-candidates="{{ json_encode(\App\Models\Product::storageThumbCandidates($img)) }}" data-image-fallback="{{ asset(\App\Models\Product::IMAGE_FALLBACK_ASSET) }}" src="{{ \App\Models\Product::storageThumbUrl($img) }}" alt="Фото" class="w-20 h-20 object-cover">
                    <button type="button" data-path="{{ $img }}"
                            class="absolute right-1 top-1 rounded bg-neutral-800 px-1.5 py-0.5 text-xs text-white opacity-0 transition group-hover:opacity-100">
                      ✕
                    </button>
                  </div>
                @endif
              @endforeach
            </div>
          @else
            <p class="mt-2 text-sm text-neutral-400">Нет загруженных изображений</p>
          @endif
        </div>
      </section>

      <section class="seller-form-card">
        <div class="seller-section-head mb-3">
          <div>
            <p class="seller-section-kicker">Проверка</p>
            <h2 class="seller-section-title">Качество карточки</h2>
          </div>
          <div class="shrink-0 text-right">
            <div class="text-lg font-semibold text-neutral-950">{{ $qualityDone }}/{{ $qualityTotal }}</div>
            <div class="text-xs text-neutral-500">готово</div>
          </div>
        </div>
        <div class="mb-4">
          <div class="h-2 overflow-hidden rounded-full bg-neutral-100">
            <div class="h-full rounded-full {{ $qualityPercent >= 80 ? 'bg-emerald-500' : ($qualityPercent >= 50 ? 'bg-amber-500' : 'bg-brand-500') }}" style="width: {{ $qualityPercent }}%"></div>
          </div>
          <p class="mt-2 text-xs text-neutral-500">
            {{ $qualityPercent >= 80 ? 'Карточка выглядит уверенно.' : 'Заполните недостающие пункты, чтобы товар выглядел убедительнее.' }}
          </p>
        </div>
        <ul class="space-y-2 text-sm text-neutral-600">
          @foreach($qualityChecks as $check)
            <li class="rounded-xl border {{ $check['done'] ? 'border-emerald-100 bg-emerald-50/70' : 'border-neutral-200 bg-white' }} px-3 py-2">
              <div class="flex gap-2">
                <i class="{{ $check['done'] ? 'ri-checkbox-circle-fill text-emerald-600' : 'ri-circle-line text-neutral-300' }} mt-0.5"></i>
                <div>
                  <div class="font-medium {{ $check['done'] ? 'text-emerald-800' : 'text-neutral-800' }}">{{ $check['label'] }}</div>
                  <div class="mt-0.5 text-xs {{ $check['done'] ? 'text-emerald-700' : 'text-neutral-500' }}">{{ $check['hint'] }}</div>
                </div>
              </div>
            </li>
          @endforeach
        </ul>
      </section>


      {{-- ================= Кнопка ================= --}}
      <div class="seller-form-card">
        <button type="submit"
                class="seller-primary-button w-full px-6 py-3">
          <i class="ri-save-3-line mr-1"></i>
          {{ $product->exists ? 'Сохранить изменения' : 'Создать товар' }}
        </button>
        <p class="mt-3 text-center text-xs text-neutral-500">Перед публикацией проверь цену, город и главное фото.</p>
      </div>

      </aside>

    </form>
  </div>

  <div id="main-image-cropper" class="seller-cropper fixed inset-0 z-[80] hidden items-center justify-center bg-neutral-950/55 p-4 backdrop-blur-sm">
    <div class="w-full max-w-2xl rounded-2xl border border-neutral-200 bg-white p-5 shadow-2xl">
      <div class="mb-4 flex items-start justify-between gap-4">
        <div>
          <h2 class="text-lg font-semibold text-neutral-900">Кадр карточки товара</h2>
          <p class="mt-1 text-sm text-neutral-500">Перетащи фото и выбери, как оно будет выглядеть в карточке и списках.</p>
        </div>
        <button type="button" data-crop-cancel class="h-9 w-9 rounded-xl text-neutral-500 transition hover:bg-neutral-100 hover:text-neutral-800">
          <i class="ri-close-line text-xl"></i>
        </button>
      </div>

      <div class="mx-auto w-full max-w-[560px]">
        <canvas id="main-image-crop-canvas" width="500" height="400" class="w-full cursor-move rounded-xl border border-neutral-200 bg-neutral-100" style="aspect-ratio: 4 / 3.2"></canvas>
        <p class="mt-2 text-center text-xs text-neutral-500">Оптимально загружать фото от 1200 x 960 px: товар крупно, без важного текста у краёв.</p>
      </div>

      <label class="mt-4 block text-sm font-medium text-neutral-700">
        Масштаб
        <input id="main-image-crop-zoom" type="range" min="1" max="3" step="0.01" value="1" class="mt-2 w-full accent-brand-600">
      </label>

      <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <button type="button" data-crop-cancel class="rounded-xl px-4 py-2 text-sm font-medium text-neutral-600 transition hover:bg-neutral-100">
          Отмена
        </button>
        <button type="button" id="main-image-crop-fit" class="rounded-xl border border-neutral-200 px-4 py-2 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-100">
          Вписать целиком
        </button>
        <button type="button" id="main-image-crop-apply" class="seller-primary-button px-5 py-2.5">
          Применить кадр
        </button>
      </div>
    </div>
  </div>

  {{-- Leaflet карта --}}

  <style>
    .seller-product-page {
      background: #fff;
    }
    .seller-form-card {
      border: 1px solid #e5e5e5;
      border-radius: 16px;
      background: #fff;
      padding: 20px;
      transition: border-color .18s ease, background-color .18s ease;
    }
    .seller-form-card:hover {
      border-color: #d4d4d4;
    }
    .seller-section-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 18px;
    }
    .seller-section-kicker {
      margin: 0 0 3px;
      color: #4f46e5;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .08em;
    }
    .seller-section-title {
      color: #171717;
      font-size: 18px;
      font-weight: 650;
    }
    .seller-section-hint {
      max-width: 300px;
      color: #737373;
      font-size: 13px;
      line-height: 1.45;
      text-align: right;
    }
    .seller-input {
      width: 100%;
      border-radius: 12px;
      border: 1px solid #e5e5e5;
      background-color: #fff;
      padding: 12px 14px;
      color: #171717;
      transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
    }
    .seller-input:focus {
      border-color: #a5b4fc;
      box-shadow: 0 0 0 4px rgba(199, 210, 254, 0.55);
      outline: none;
    }
    .seller-upload-zone {
      display: flex;
      min-height: 118px;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 5px;
      cursor: pointer;
      border: 1.5px dashed #d4d4d4;
      border-radius: 14px;
      background: #fafafa;
      padding: 18px;
      text-align: center;
      transition: border-color .18s ease, background-color .18s ease;
    }
    .seller-upload-zone:hover {
      border-color: #a5b4fc;
      background: #eef2ff;
    }
    .seller-upload-icon {
      display: inline-flex;
      height: 36px;
      width: 36px;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      background: #4f46e5;
      color: white;
      font-size: 22px;
      line-height: 1;
    }
    .seller-status-option {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      cursor: pointer;
      border: 1px solid #e5e5e5;
      border-radius: 14px;
      background: #fff;
      padding: 13px;
      transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
    }
    .seller-status-option:has(input:focus-visible) {
      outline: 2px solid #6366f1;
      outline-offset: 2px;
    }
    .seller-status-option:has(input:checked) {
      border-color: #a5b4fc;
      background: #eef2ff;
      box-shadow: 0 0 0 4px rgba(199, 210, 254, 0.45);
    }
    .seller-status-option::after {
      content: "";
      margin-left: auto;
      display: flex;
      height: 20px;
      width: 20px;
      flex: 0 0 auto;
      align-items: center;
      justify-content: center;
      border: 2px solid #d4d4d4;
      border-radius: 999px;
      background: #fff;
      transition: border-color .18s ease, background-color .18s ease, box-shadow .18s ease;
    }
    .seller-status-option:has(input:checked)::after {
      border-color: #4f46e5;
      background: radial-gradient(circle, #fff 0 32%, #4f46e5 35% 100%);
      box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
    }
    .seller-status-dot {
      margin-top: 4px;
      height: 10px;
      width: 10px;
      flex: 0 0 auto;
      border-radius: 999px;
    }
    .seller-empty-state {
      border: 1px dashed #d4d4d4;
      border-radius: 14px;
      background: #fafafa;
      padding: 26px;
      text-align: center;
    }
    .seller-empty-icon {
      margin: 0 auto 8px;
      display: flex;
      height: 34px;
      width: 34px;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      background: #eef2ff;
      color: #4f46e5;
      font-weight: 800;
    }
    .seller-preview-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(76px, 1fr));
      gap: 10px;
    }
    .seller-preview-grid img {
      aspect-ratio: 1 / 1;
      width: 100%;
      border-radius: 12px;
      border: 1px solid #e5e5e5;
      object-fit: cover;
    }
    #main-image-preview {
      grid-template-columns: minmax(180px, 280px);
    }
    #main-image-preview img {
      aspect-ratio: 4 / 3.2;
    }
    #map .leaflet-control-attribution {
      font-size: 11px;
      color: #666;
      background: rgba(255, 255, 255, 0.8);
      border-radius: 6px;
      padding: 2px 6px;
    }
    .seller-primary-button {
      position: relative;
      overflow: hidden;
      border-radius: 12px;
      border: 1px solid #6366f1;
      background: #6366f1;
      color: #fff;
      font-size: 14px;
      font-weight: 600;
      transition: background-color .18s ease, border-color .18s ease;
    }
    .seller-primary-button:hover {
      background: #4f46e5;
      border-color: #4f46e5;
    }
    .seller-upload-zone input[type="file"] {
      position: absolute;
      width: 1px;
      height: 1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
    }
    @media (max-width: 640px) {
      .seller-form-card { padding: 16px; }
      .seller-section-head { display: block; }
      .seller-section-hint { margin-top: 4px; text-align: left; }
    }
  </style>



<script>
  window.allCategories = @json($categoriesTree);
</script>

  @include('layouts.mobile-bottom-seller-nav')

</x-seller-layout>
