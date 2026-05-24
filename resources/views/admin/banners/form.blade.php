{{-- Форма создания и редактирования баннера в админке --}}

@extends('admin.layout')

@section('title', $banner->exists ? 'Редактирование баннера' : 'Новый баннер')

@section('content')
@php
  $isEdit = $banner->exists;
  $initialPreview = $banner->image_desktop
      ? asset('storage/'.$banner->image_desktop)
      : ($banner->image_tablet
          ? asset('storage/'.$banner->image_tablet)
          : ($banner->image_mobile
              ? asset('storage/'.$banner->image_mobile)
              : ($banner->image ? asset('storage/'.$banner->image) : '')));
  $initialMobilePreview = $banner->image_mobile ? asset('storage/'.$banner->image_mobile) : $initialPreview;

  $previewSlots = [
      ['key' => 'desktop', 'label' => 'Десктоп', 'icon' => 'ri-computer-line', 'size' => '2400 x 720', 'aspect' => 'aspect-[30/9]'],
      ['key' => 'tablet', 'label' => 'Планшет', 'icon' => 'ri-tablet-line', 'size' => '1600 x 600', 'aspect' => 'aspect-[24/9]'],
      ['key' => 'mobile', 'label' => 'Мобильный', 'icon' => 'ri-smartphone-line', 'size' => '960 x 480', 'aspect' => 'aspect-[18/9]'],
  ];
@endphp

<div class="mx-auto max-w-7xl space-y-6">
  <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="space-y-2">
      <a href="{{ route('admin.banners.index') }}"
         class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition hover:text-indigo-600">
        <i class="ri-arrow-left-line text-base"></i>
        <span>Баннеры</span>
      </a>
      <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
          <i class="ri-image-add-line text-2xl"></i>
        </div>
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">
            {{ $isEdit ? 'Редактирование баннера' : 'Новый баннер' }}
          </h1>
          <p class="text-sm text-gray-500">
            Загрузите один баннер, обрежьте как фото товара и проверьте все экраны.
          </p>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600 shadow-sm">
      <i class="ri-shield-check-line text-emerald-500"></i>
      <span>JPG, PNG и WebP сохраняются в легком WebP</span>
    </div>
  </div>

  @if ($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 shadow-sm">
      <div class="mb-2 flex items-center gap-2 font-semibold">
        <i class="ri-error-warning-line text-lg"></i>
        <span>Проверьте поля формы</span>
      </div>
      <ul class="list-disc space-y-1 pl-5">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form
    id="admin-banner-form"
    method="POST"
    enctype="multipart/form-data"
    action="{{ $isEdit ? route('admin.banners.update', $banner) : route('admin.banners.store') }}"
    autocomplete="off"
    class="space-y-6"
  >
    @csrf
    @if($isEdit) @method('PUT') @endif

    <input type="hidden" name="crop_x" id="banner-crop-x" value="0">
    <input type="hidden" name="crop_y" id="banner-crop-y" value="0">
    <input type="hidden" name="crop_w" id="banner-crop-w" value="100">
    <input type="hidden" name="crop_h" id="banner-crop-h" value="100">
    <input type="hidden" name="mobile_crop_x" id="banner-mobile-crop-x" value="0">
    <input type="hidden" name="mobile_crop_y" id="banner-mobile-crop-y" value="0">
    <input type="hidden" name="mobile_crop_w" id="banner-mobile-crop-w" value="100">
    <input type="hidden" name="mobile_crop_h" id="banner-mobile-crop-h" value="100">
    <input type="hidden" name="recrop_existing" id="banner-recrop-existing" value="0">
    <input type="hidden" name="mobile_recrop_existing" id="banner-mobile-recrop-existing" value="0">

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
      <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="mb-5 flex items-center justify-between gap-4 border-b border-gray-100 pb-4">
          <div>
            <h2 class="text-base font-semibold text-gray-900">Основная информация</h2>
            <p class="mt-1 text-sm text-gray-500">Название, ссылка и порядок показа.</p>
          </div>
          <span class="rounded-lg bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
            {{ $isEdit ? 'ID '.$banner->id : 'Черновик' }}
          </span>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
          <div class="space-y-2">
            <label for="title" class="text-sm font-medium text-gray-700">Заголовок</label>
            <div class="relative">
              <i class="ri-text absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
              <input id="title" type="text" name="title" value="{{ old('title', $banner->title) }}"
                     placeholder="Осенние скидки" maxlength="80"
                     class="w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500">
            </div>
          </div>

          <div class="space-y-2">
            <label for="link" class="text-sm font-medium text-gray-700">Ссылка</label>
            <div class="relative">
              <i class="ri-link absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
              <input id="link" type="text" name="link" value="{{ old('link', $banner->link) }}"
                     placeholder="/products?sort=new"
                     class="w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500">
            </div>
          </div>

          <div class="space-y-2">
            <label for="sort_order" class="text-sm font-medium text-gray-700">Порядок</label>
            <div class="relative">
              <i class="ri-sort-asc absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
              <input id="sort_order" type="number" name="sort_order" min="0"
                     value="{{ old('sort_order', $banner->sort_order ?? 0) }}"
                     class="w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500">
            </div>
          </div>

          <div class="space-y-2">
            <span class="text-sm font-medium text-gray-700">Статус</span>
            <label class="flex min-h-[42px] cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 transition hover:border-indigo-200 hover:bg-indigo-50/40">
              <span class="flex items-center gap-2 text-sm text-gray-700">
                <i class="ri-eye-line text-indigo-500"></i>
                Показывать на сайте
              </span>
              <input type="checkbox" id="active" name="active" value="1"
                     {{ old('active', $banner->exists ? $banner->active : true) ? 'checked' : '' }}
                     class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            </label>
          </div>
        </div>
      </section>

      <aside class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:row-span-2">
        <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
            <i class="ri-dashboard-line text-xl"></i>
          </div>
          <div>
            <h2 class="text-base font-semibold text-gray-900">Публикация</h2>
            <p class="text-sm text-gray-500">Итоговые действия</p>
          </div>
        </div>

        <div class="mt-5 space-y-3 text-sm">
          <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
            <span class="text-gray-500">Активность</span>
            <span class="font-medium text-gray-800">{{ old('active', $banner->exists ? $banner->active : true) ? 'Включена' : 'Выключена' }}</span>
          </div>
          <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
            <span class="text-gray-500">Формат</span>
            <span class="font-medium text-gray-800">WebP</span>
          </div>
          <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
            <span class="text-gray-500">Версии</span>
            <span class="font-medium text-gray-800">3 экрана</span>
          </div>
        </div>

        <div class="mt-6 flex flex-col gap-3">
          <button type="submit"
                  class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <i class="{{ $isEdit ? 'ri-save-3-line' : 'ri-add-line' }} text-base"></i>
            {{ $isEdit ? 'Сохранить изменения' : 'Создать баннер' }}
          </button>

          <a href="{{ route('admin.banners.index') }}"
             class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
            <i class="ri-close-line text-base"></i>
            Отмена
          </a>
        </div>
      </aside>

      <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="mb-5 flex flex-col gap-2 border-b border-gray-100 pb-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <h2 class="text-base font-semibold text-gray-900">Изображения баннера</h2>
            <p class="mt-1 text-sm text-gray-500">Сначала настройте широкий баннер. Мобильный добавляйте только если на телефоне кадр режется неудачно.</p>
          </div>
          <div class="flex items-center gap-2 text-xs text-gray-500">
            <i class="ri-information-line text-sm"></i>
            <span>SVG не принимается</span>
          </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
          <div class="space-y-4">
            <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4">
              <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                  <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">1</span>
                    Широкий баннер
                  </h3>
                  <p class="mt-1 text-xs text-gray-500">Для десктопа и планшета. Если мобильный файл не задан, телефон тоже возьмёт эту версию.</p>
                </div>
                <i class="ri-computer-line text-lg text-indigo-500"></i>
              </div>

              <div class="mb-3 rounded-xl border border-indigo-100 bg-white/80 px-3 py-2 text-xs leading-5 text-slate-600">
                <div class="font-bold text-slate-800">Лучше загружать: 2400 x 720 px, JPG/PNG/WebP, до 8 МБ.</div>
                <div>Главное держите по центру кадра: на desktop видна широкая область 30:9, на планшете кадр становится чуть уже.</div>
              </div>

              <label class="flex min-h-[126px] cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-indigo-200 bg-white px-5 py-6 text-center transition hover:border-indigo-400 hover:bg-white">
                <input id="banner-image-source" type="file" name="image_source" class="sr-only" accept="image/jpeg,image/png,image/webp">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm">
                  <i class="ri-image-add-line text-2xl"></i>
                </span>
                <span id="banner-file-name" class="font-semibold text-gray-900">Выбрать широкий баннер</span>
                <span class="text-xs text-gray-500">Рекомендуемо: широкий кадр без мелкого текста по краям.</span>
              </label>

              <div id="banner-main-preview" class="{{ $initialPreview ? '' : 'hidden' }} mt-3 overflow-hidden rounded-xl border border-gray-200 bg-gray-100 aspect-[30/9]">
                <img src="{{ $initialPreview }}" alt="Предпросмотр баннера" class="h-full w-full object-cover" data-banner-preview>
              </div>

              <button type="button" id="banner-open-crop"
                      class="{{ $initialPreview ? '' : 'hidden' }} mt-3 rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50">
                <i class="ri-crop-line mr-1"></i>
                Настроить кадр
              </button>
            </div>

            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
              <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                  <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-900 text-xs font-bold text-white">2</span>
                    Мобильный баннер
                  </h3>
                  <p class="mt-1 text-xs text-gray-500">Необязательно. Нужен, если на телефоне хочется другой кадр или крупнее объект.</p>
                </div>
                <i class="ri-smartphone-line text-lg text-indigo-500"></i>
              </div>

              <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-dashed border-gray-300 bg-white px-4 py-3 transition hover:border-indigo-300 hover:bg-indigo-50/30">
                <span class="min-w-0">
                  <span id="banner-mobile-file-name" class="block truncate text-sm font-medium text-gray-700">Выбрать мобильный баннер</span>
                  <span id="banner-mobile-state" class="block text-xs text-gray-500">{{ $banner->image_mobile ? 'Используется отдельная мобильная версия' : 'Пока используется широкий баннер' }}</span>
                </span>
                <span class="inline-flex shrink-0 items-center gap-2 rounded-md bg-gray-900 px-3 py-2 text-xs font-semibold text-white">
                  <i class="ri-upload-cloud-2-line"></i>
                  Загрузить
                </span>
                <input id="banner-mobile-source" type="file" name="image_mobile" accept="image/jpeg,image/png,image/webp" class="sr-only">
              </label>

              <div class="mt-3 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs leading-5 text-slate-500">
                Оптимально для телефона: 960 x 480 px. Загружайте отдельный мобильный баннер, если на узком экране текст или товар обрезаются.
              </div>

              <button type="button" id="banner-mobile-open-crop"
                      class="{{ $initialMobilePreview ? '' : 'hidden' }} mt-3 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">
                <i class="ri-crop-line mr-1"></i>
                Настроить мобильный кадр
              </button>
            </div>
          </div>

          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-semibold text-gray-900">Предпросмотр</h3>
              <i class="ri-crop-2-line text-lg text-indigo-500"></i>
            </div>

            @foreach ($previewSlots as $slot)
              <div>
                <div class="mb-1 flex items-center justify-between text-xs">
                  <span class="flex items-center gap-1 font-medium text-gray-600">
                    <i class="{{ $slot['icon'] }}"></i>
                    {{ $slot['label'] }}
                  </span>
                  <span class="text-gray-400">{{ $slot['size'] }}</span>
                </div>
                <div class="relative overflow-hidden rounded-lg border border-gray-200 bg-gray-100 {{ $slot['aspect'] }}">
                  <img src="{{ $slot['key'] === 'mobile' ? $initialMobilePreview : $initialPreview }}" alt="{{ $slot['label'] }}"
                       class="{{ $initialPreview ? '' : 'hidden' }} h-full w-full object-cover"
                       data-banner-device-preview="{{ $slot['key'] }}">
                  <div class="{{ $initialPreview ? 'hidden' : '' }} absolute inset-0 flex items-center justify-center text-xs text-gray-400" data-banner-empty>
                    Нет изображения
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </section>
    </div>
  </form>
</div>

<div id="banner-image-cropper" class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm">
  <div class="w-full max-w-4xl rounded-2xl border border-gray-200/70 bg-white p-5 shadow-2xl">
    <div class="mb-4 flex items-start justify-between gap-4">
      <div>
        <h2 id="banner-crop-title" class="text-lg font-semibold text-gray-900">Обрезать баннер</h2>
        <p id="banner-crop-help" class="mt-1 text-sm text-gray-500">Перетаскивайте изображение внутри рамки и настройте масштаб.</p>
      </div>
      <button type="button" data-banner-crop-cancel class="h-9 w-9 rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-800">
        <i class="ri-close-line text-xl"></i>
      </button>
    </div>

    <div class="mx-auto w-full max-w-[720px]">
      <canvas id="banner-crop-canvas" width="900" height="270" class="aspect-[30/9] w-full cursor-move rounded-xl border border-gray-200 bg-gray-100"></canvas>
    </div>

    <label class="mt-4 block text-sm font-medium text-gray-700">
      Масштаб
      <input id="banner-crop-zoom" type="range" min="1" max="3" step="0.01" value="1" class="mt-2 w-full accent-indigo-600">
    </label>

    <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
      <button type="button" data-banner-crop-cancel class="rounded-xl px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-100">
        Отмена
      </button>
      <button type="button" id="banner-crop-fit" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100">
        Сбросить кадр
      </button>
      <button type="button" id="banner-crop-apply" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
        Обрезать баннер
      </button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const cropper = document.getElementById('banner-image-cropper');
  const canvas = document.getElementById('banner-crop-canvas');
  const zoom = document.getElementById('banner-crop-zoom');
  const fitButton = document.getElementById('banner-crop-fit');
  const applyButton = document.getElementById('banner-crop-apply');
  const title = document.getElementById('banner-crop-title');
  const help = document.getElementById('banner-crop-help');
  const mainPreview = document.getElementById('banner-main-preview');
  const mobileState = document.getElementById('banner-mobile-state');
  const ctx = canvas?.getContext('2d');

  const states = {
    main: {
      input: document.getElementById('banner-image-source'),
      button: document.getElementById('banner-open-crop'),
      fileName: document.getElementById('banner-file-name'),
      fieldPrefix: 'banner-crop',
      title: 'Обрезать обычный баннер',
      help: 'Этот кадр совпадает с desktop-баннером главной страницы.',
      canvasWidth: 900,
      canvasHeight: 270,
      previewWidth: 1000,
      previewHeight: 300,
      uploadWidth: 2400,
      uploadHeight: 720,
      existingUrl: @js($initialPreview),
      recropInput: document.getElementById('banner-recrop-existing'),
      image: null,
      file: null,
      objectUrl: null,
      processed: false,
      offset: { x: 0, y: 0 },
      zoom: 1,
      ready: false,
    },
    mobile: {
      input: document.getElementById('banner-mobile-source'),
      button: document.getElementById('banner-mobile-open-crop'),
      fileName: document.getElementById('banner-mobile-file-name'),
      fieldPrefix: 'banner-mobile-crop',
      title: 'Обрезать мобильный баннер',
      help: 'Этот кадр используется только на телефонах.',
      canvasWidth: 720,
      canvasHeight: 360,
      previewWidth: 960,
      previewHeight: 480,
      uploadWidth: 960,
      uploadHeight: 480,
      existingUrl: @js($initialMobilePreview),
      recropInput: document.getElementById('banner-mobile-recrop-existing'),
      image: null,
      file: null,
      objectUrl: null,
      processed: false,
      offset: { x: 0, y: 0 },
      zoom: 1,
      ready: false,
    },
  };

  let activeState = states.main;
  let hasMobileOverride = {{ $banner->image_mobile ? 'true' : 'false' }};
  let dragging = false;
  let dragStart = { x: 0, y: 0 };
  let preparingSubmit = false;

  if (!cropper || !canvas || !ctx || !zoom) return;

  function baseScale(state = activeState) {
    if (!state.image) return 1;
    return Math.max(canvas.width / state.image.width, canvas.height / state.image.height);
  }

  function currentScale(state = activeState) {
    return baseScale(state) * state.zoom;
  }

  function clampOffset(state = activeState) {
    if (!state.image) return;

    const scale = currentScale(state);
    const drawnWidth = state.image.width * scale;
    const drawnHeight = state.image.height * scale;
    const minX = Math.min(0, canvas.width - drawnWidth);
    const minY = Math.min(0, canvas.height - drawnHeight);

    state.offset.x = Math.min(0, Math.max(minX, state.offset.x));
    state.offset.y = Math.min(0, Math.max(minY, state.offset.y));
  }

  function resetCropPosition(state = activeState) {
    if (!state.image) return;

    state.zoom = 1;
    zoom.value = '1';
    const scale = baseScale(state);
    state.offset = {
      x: (canvas.width - state.image.width * scale) / 2,
      y: (canvas.height - state.image.height * scale) / 2,
    };
    state.ready = true;
  }

  function drawCrop() {
    const state = activeState;
    if (!state.image) return;

    clampOffset(state);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#f3f4f6';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    const scale = currentScale(state);
    ctx.drawImage(state.image, state.offset.x, state.offset.y, state.image.width * scale, state.image.height * scale);

    ctx.strokeStyle = 'rgba(255,255,255,.95)';
    ctx.lineWidth = 2;
    ctx.strokeRect(1, 1, canvas.width - 2, canvas.height - 2);

    ctx.strokeStyle = 'rgba(79,70,229,.45)';
    ctx.lineWidth = 1;
    const thirdX = canvas.width / 3;
    const thirdY = canvas.height / 3;
    ctx.beginPath();
    ctx.moveTo(thirdX, 0);
    ctx.lineTo(thirdX, canvas.height);
    ctx.moveTo(thirdX * 2, 0);
    ctx.lineTo(thirdX * 2, canvas.height);
    ctx.moveTo(0, thirdY);
    ctx.lineTo(canvas.width, thirdY);
    ctx.moveTo(0, thirdY * 2);
    ctx.lineTo(canvas.width, thirdY * 2);
    ctx.stroke();
  }

  function setFields(state) {
    if (!state.image) return;

    clampOffset(state);
    const scale = currentScale(state);
    const sourceX = Math.max(0, -state.offset.x / scale);
    const sourceY = Math.max(0, -state.offset.y / scale);
    const sourceW = Math.min(state.image.width, canvas.width / scale);
    const sourceH = Math.min(state.image.height, canvas.height / scale);

    document.getElementById(`${state.fieldPrefix}-x`).value = (sourceX / state.image.width * 100).toFixed(4);
    document.getElementById(`${state.fieldPrefix}-y`).value = (sourceY / state.image.height * 100).toFixed(4);
    document.getElementById(`${state.fieldPrefix}-w`).value = (sourceW / state.image.width * 100).toFixed(4);
    document.getElementById(`${state.fieldPrefix}-h`).value = (sourceH / state.image.height * 100).toFixed(4);
  }

  function resetFields(state) {
    document.getElementById(`${state.fieldPrefix}-x`).value = '0';
    document.getElementById(`${state.fieldPrefix}-y`).value = '0';
    document.getElementById(`${state.fieldPrefix}-w`).value = '100';
    document.getElementById(`${state.fieldPrefix}-h`).value = '100';
    if (state.recropInput) state.recropInput.value = '0';
  }

  function fileSizeLabel(bytes) {
    if (!Number.isFinite(bytes)) return '';
    if (bytes >= 1024 * 1024) return `${(bytes / 1024 / 1024).toFixed(1)} МБ`;
    return `${Math.max(1, Math.round(bytes / 1024))} КБ`;
  }

  function updateMainPreviews(url) {
    if (states.main.objectUrl && states.main.objectUrl !== url) {
      URL.revokeObjectURL(states.main.objectUrl);
    }
    states.main.objectUrl = url.startsWith('blob:') ? url : null;
    states.main.existingUrl = url;

    document.querySelectorAll('[data-banner-preview], [data-banner-device-preview]:not([data-banner-device-preview="mobile"])').forEach(img => {
      img.src = url;
      img.classList.remove('hidden');
    });
    if (!hasMobileOverride) {
      document.querySelectorAll('[data-banner-device-preview="mobile"]').forEach(img => {
        img.src = url;
        img.classList.remove('hidden');
      });
    }
    document.querySelectorAll('[data-banner-empty]').forEach(empty => empty.classList.add('hidden'));
    mainPreview?.classList.remove('hidden');
  }

  function updateMobilePreview(url) {
    if (states.mobile.objectUrl && states.mobile.objectUrl !== url) {
      URL.revokeObjectURL(states.mobile.objectUrl);
    }
    states.mobile.objectUrl = url.startsWith('blob:') ? url : null;
    states.mobile.existingUrl = url;

    document.querySelectorAll('[data-banner-device-preview="mobile"]').forEach(img => {
      img.src = url;
      img.classList.remove('hidden');
    });
    document.querySelectorAll('[data-banner-empty]').forEach(empty => empty.classList.add('hidden'));
  }

  function updateCroppedPreview(state) {
    const preview = document.createElement('canvas');
    preview.width = state.previewWidth;
    preview.height = state.previewHeight;
    const previewCtx = preview.getContext('2d');
    const ratio = preview.width / canvas.width;
    const scale = currentScale(state) * ratio;

    previewCtx.fillStyle = '#fff';
    previewCtx.fillRect(0, 0, preview.width, preview.height);
    previewCtx.drawImage(
      state.image,
      state.offset.x * ratio,
      state.offset.y * ratio,
      state.image.width * scale,
      state.image.height * scale
    );

    const url = preview.toDataURL('image/jpeg', 0.9);
    state === states.mobile ? updateMobilePreview(url) : updateMainPreviews(url);
  }

  function closeCropper() {
    cropper.classList.add('hidden');
    cropper.classList.remove('flex');
    dragging = false;
  }

  function configureCanvas(state) {
    if (canvas.width !== state.canvasWidth) {
      canvas.width = state.canvasWidth;
    }
    if (canvas.height !== state.canvasHeight) {
      canvas.height = state.canvasHeight;
    }
    canvas.style.aspectRatio = `${state.canvasWidth} / ${state.canvasHeight}`;
  }

  function showCropper() {
    cropper.classList.remove('hidden');
    cropper.classList.add('flex');
  }

  function loadImageForCrop(state, url, revokeAfterLoad = false) {
    state.image = new Image();
    state.image.onload = () => {
      if (revokeAfterLoad) URL.revokeObjectURL(url);
      resetCropPosition(state);
      setFields(state);
      showCropper();
      drawCrop();
    };
    state.image.onerror = () => {
      if (revokeAfterLoad) URL.revokeObjectURL(url);
      alert('Не удалось открыть изображение для обрезки. Попробуйте выбрать файл заново.');
    };
    state.image.src = url;
  }

  function openCropper(state) {
    const file = state.input?.files?.[0] || state.file;
    const existingUrl = state.existingUrl;
    if ((!file || !file.type.startsWith('image/')) && !existingUrl) return;

    activeState = state;
    configureCanvas(state);
    title.textContent = state.title;
    help.textContent = state.help;
    zoom.value = String(state.zoom);

    if (state.image && state.ready) {
      showCropper();
      drawCrop();
      return;
    }

    if (file && file.type.startsWith('image/')) {
      state.file = file;
      loadImageForCrop(state, URL.createObjectURL(file), true);
      return;
    }

    state.file = null;
    loadImageForCrop(state, existingUrl);
  }

  function bindInput(state, previewCallback) {
    state.input?.addEventListener('change', () => {
      const file = state.input.files?.[0];
      if (!file || !file.type.startsWith('image/')) {
        state.button?.classList.add('hidden');
        return;
      }

      state.file = file;
      state.image = null;
      state.ready = false;
      state.zoom = 1;
      state.processed = false;
      resetFields(state);
      if (state.recropInput) state.recropInput.value = '0';
      state.fileName.textContent = file.name;
      previewCallback(URL.createObjectURL(file));
      state.button?.classList.remove('hidden');
    });

    state.button?.addEventListener('click', () => openCropper(state));
  }

  bindInput(states.main, updateMainPreviews);
  bindInput(states.mobile, url => {
    hasMobileOverride = true;
    if (mobileState) mobileState.textContent = 'Используется отдельная мобильная версия';
    updateMobilePreview(url);
  });

  zoom.addEventListener('input', () => {
    activeState.zoom = parseFloat(zoom.value) || 1;
    drawCrop();
  });

  canvas.addEventListener('pointerdown', event => {
    dragging = true;
    canvas.setPointerCapture(event.pointerId);
    dragStart = {
      x: event.clientX - activeState.offset.x,
      y: event.clientY - activeState.offset.y,
    };
  });

  canvas.addEventListener('pointermove', event => {
    if (!dragging) return;
    activeState.offset = {
      x: event.clientX - dragStart.x,
      y: event.clientY - dragStart.y,
    };
    drawCrop();
  });

  canvas.addEventListener('pointerup', event => {
    dragging = false;
    canvas.releasePointerCapture(event.pointerId);
  });

  cropper.querySelectorAll('[data-banner-crop-cancel]').forEach(button => {
    button.addEventListener('click', closeCropper);
  });

  fitButton?.addEventListener('click', () => {
    if (!activeState.image) return;
    resetCropPosition(activeState);
    setFields(activeState);
    drawCrop();
  });

  applyButton?.addEventListener('click', () => {
    if (!activeState.image) return;
    setFields(activeState);
    if (!activeState.file && activeState.recropInput) {
      activeState.recropInput.value = '1';
    }
    if (activeState.file) {
      activeState.processed = false;
    }
    updateCroppedPreview(activeState);
    closeCropper();
  });

  function canvasToBlob(targetCanvas, quality = 0.86) {
    return new Promise(resolve => {
      targetCanvas.toBlob(blob => {
        if (blob) {
          resolve({ blob, extension: 'webp' });
          return;
        }

        targetCanvas.toBlob(fallbackBlob => {
          resolve(fallbackBlob ? { blob: fallbackBlob, extension: 'jpg' } : null);
        }, 'image/jpeg', quality);
      }, 'image/webp', quality);
    });
  }

  function loadStateImageFromFile(state) {
    const file = state.input?.files?.[0];

    if (!file || !file.type.startsWith('image/')) {
      return Promise.resolve(false);
    }

    return new Promise(resolve => {
      const url = URL.createObjectURL(file);
      const image = new Image();

      image.onload = () => {
        URL.revokeObjectURL(url);
        state.file = file;
        state.image = image;
        resetCropPosition(state);
        resolve(true);
      };

      image.onerror = () => {
        URL.revokeObjectURL(url);
        resolve(false);
      };

      image.src = url;
    });
  }

  async function prepareStateUpload(state) {
    const file = state.input?.files?.[0];

    if (!file || !file.type.startsWith('image/') || state.processed) {
      return;
    }

    activeState = state;
    configureCanvas(state);

    if (!state.image || !state.ready) {
      const loaded = await loadStateImageFromFile(state);
      if (!loaded) return;
      configureCanvas(state);
    }

    clampOffset(state);

    const output = document.createElement('canvas');
    output.width = state.uploadWidth;
    output.height = state.uploadHeight;

    const outputCtx = output.getContext('2d');
    const ratio = output.width / canvas.width;
    const scale = currentScale(state) * ratio;

    outputCtx.fillStyle = '#fff';
    outputCtx.fillRect(0, 0, output.width, output.height);
    outputCtx.drawImage(
      state.image,
      state.offset.x * ratio,
      state.offset.y * ratio,
      state.image.width * scale,
      state.image.height * scale
    );

    const encoded = await canvasToBlob(output);
    if (!encoded) return;

    const baseName = (file.name || 'banner').replace(/\.[^.]+$/, '').replace(/[^\w-]+/g, '-').replace(/^-+|-+$/g, '') || 'banner';
    const compressedFile = new File([encoded.blob], `${baseName}.${encoded.extension}`, {
      type: encoded.blob.type || `image/${encoded.extension}`,
      lastModified: Date.now(),
    });

    const transfer = new DataTransfer();
    transfer.items.add(compressedFile);
    state.input.files = transfer.files;
    state.file = compressedFile;
    state.processed = true;
    resetFields(state);
    state.fileName.textContent = `${compressedFile.name} - ${fileSizeLabel(compressedFile.size)}`;
  }

  document.getElementById('admin-banner-form')?.addEventListener('submit', async event => {
    if (preparingSubmit) return;

    const selectedStates = [states.main, states.mobile].filter(state => {
      const file = state.input?.files?.[0];
      return file && file.type.startsWith('image/') && !state.processed;
    });

    if (selectedStates.length === 0) return;

    event.preventDefault();
    preparingSubmit = true;

    const submitButton = event.submitter || document.querySelector('#admin-banner-form button[type="submit"]');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.classList.add('opacity-70', 'cursor-wait');
    }

    for (const state of selectedStates) {
      await prepareStateUpload(state);
    }

    event.target.submit();
  });
});
</script>
@endsection
