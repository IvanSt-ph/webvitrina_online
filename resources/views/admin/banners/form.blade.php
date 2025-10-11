{{-- Форма создания и редактирования баннера в админке --}}
{{-- Страница: resources/views/admin/banners/form.blade.php --}}

@extends('admin.layout')

@section('content')
@php
  $isEdit = $banner->exists;
@endphp

<div class="flex items-center justify-between mb-8">
  <h1 class="text-2xl font-semibold text-gray-800 tracking-tight">
    {{ $isEdit ? '✏️ Редактирование баннера' : '➕ Добавление нового баннера' }}
  </h1>
  <a href="{{ route('admin.banners.index') }}" class="text-sm text-gray-500 hover:text-gray-700 transition">
    ← Назад к списку
  </a>
</div>

@if ($errors->any())
  <div class="mb-6 p-4 border border-red-200 bg-red-50 text-red-700 rounded-lg shadow-sm">
    <ul class="list-disc pl-5 space-y-1">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form 
  method="POST"
  enctype="multipart/form-data"
  action="{{ $isEdit ? route('admin.banners.update', $banner) : route('admin.banners.store') }}"
  class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm space-y-8 w-full"
  autocomplete="off"
>
  @csrf
  @if($isEdit) @method('PUT') @endif

  {{-- === Основная информация === --}}
  <section>
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Основная информация</h2>
    <div class="grid md:grid-cols-2 gap-8">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Заголовок баннера</label>
        <input type="text" name="title" value="{{ old('title', $banner->title) }}"
               placeholder="Например: Осенние скидки до -30%"
               maxlength="80"
               class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-gray-200 transition">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ссылка (куда ведёт баннер)</label>
        <input type="text" name="link" value="{{ old('link', $banner->link) }}"
               placeholder="/products?sort=new или https://example.com"
               class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-gray-200 transition">
        <p class="text-xs text-gray-500 mt-1 leading-snug">
          Можно указать <b>внутренний путь</b> (например: <code>/products?sort=benefit</code>) 
          или <b>внешнюю ссылку</b> (начинается с https://).
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Порядок отображения</label>
        <input type="number" name="sort_order"
               value="{{ old('sort_order', $banner->sort_order ?? 0) }}"
               class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-gray-200 transition">
        <p class="text-xs text-gray-500 mt-1 leading-snug">
          Меньшее число — выше в списке.
        </p>
      </div>

      <div class="flex items-center gap-3 pt-5">
        <input type="checkbox" id="active" name="active" value="1" {{ old('active', $banner->active) ? 'checked' : '' }}
               class="rounded border-gray-300 text-gray-900 focus:ring-gray-400">
        <label for="active" class="text-sm text-gray-700 select-none">
          Отображать баннер на сайте
        </label>
      </div>
    </div>
  </section>

  {{-- === Изображения для разных устройств === --}}
  <section class="space-y-8">
    <h2 class="text-lg font-semibold text-gray-800">Изображения баннера</h2>
    <p class="text-xs text-gray-500">
      Загрузите версии для разных устройств:<br>
      🖥 <b>Десктоп</b> — 1920×500 px<br>
      💻 <b>Планшет</b> — 1024×400 px<br>
      📱 <b>Мобильный</b> — 768×480 px
    </p>

    @foreach ([
        ['field' => 'image_desktop', 'label' => '🖥 Десктоп', 'size' => '1920×500 px', 'aspect' => 'aspect-[3.84/1] sm:aspect-[2.8/1] md:aspect-[2.5/1] lg:aspect-[3.84/1]'],
        ['field' => 'image_tablet',  'label' => '💻 Планшет', 'size' => '1024×400 px', 'aspect' => 'aspect-[2.8/1] md:aspect-[2.5/1]'],
        ['field' => 'image_mobile',  'label' => '📱 Мобильный', 'size' => '768×480 px',  'aspect' => 'aspect-[3.84/1]'],
    ] as $b)
      @php
        $field = $b['field'];
        $image = $banner->$field ? asset('storage/'.$banner->$field) : '';
      @endphp

      <div x-data="{ preview: '{{ $image }}' }" class="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="font-medium text-gray-700">{{ $b['label'] }}</h3>
          <button type="button" @click="preview = ''"
                  class="text-xs text-gray-500 hover:text-red-600 transition">
            Очистить превью ✕
          </button>
        </div>

        {{-- 🖼 Реалистичный предпросмотр (как на сайте) --}}
        <div class="relative bg-gray-200 border border-gray-300 rounded-xl overflow-hidden shadow-sm mx-auto w-full {{ $b['aspect'] }}">
          <div class="absolute inset-0 w-full h-full bg-center bg-cover transition-all duration-500"
               :style="preview ? `background-image: url('${preview}')` : ''"></div>

          <template x-if="!preview">
            <div class="flex items-center justify-center w-full h-full text-gray-500 text-sm">
              Нет изображения
            </div>
          </template>

          <div class="absolute bottom-0 left-0 right-0 bg-black/30 text-white text-xs py-1 px-3">
            {{ $b['label'] }} — {{ $b['size'] }}
          </div>
        </div>

        {{-- 📤 Загрузка файла --}}
        <input 
          type="file"
          name="{{ $b['field'] }}"
          accept="image/png,image/jpeg,image/webp"
          @change="
            const file = $event.target.files[0];
            if (!file) return;
            if (file.size > 2 * 1024 * 1024) {
              alert('⚠️ Файл слишком большой! Максимум 2 МБ.');
              $event.target.value = '';
              return;
            }
            preview = URL.createObjectURL(file);
          "
          class="block w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-4 
                 file:rounded-lg file:border-0 file:bg-gray-900 file:text-white 
                 hover:file:bg-gray-800 focus:ring-2 focus:ring-offset-2 focus:ring-gray-300 transition"
        >
        <p class="text-xs text-gray-500">Рекомендуемый размер: <b>{{ $b['size'] }}</b></p>
      </div>
    @endforeach
  </section>

  {{-- === Кнопки действий === --}}
  <div class="flex gap-4 pt-2">
    <button class="px-6 py-2.5 bg-gray-900 text-white rounded-lg hover:bg-gray-800 
                   focus:ring-2 focus:ring-offset-2 focus:ring-gray-300
                   transition-all duration-150 shadow-sm">
      {{ $isEdit ? '💾 Сохранить изменения' : 'Создать баннер' }}
    </button>

    <a href="{{ route('admin.banners.index') }}" 
       class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 
              transition-all duration-150">
      Отмена
    </a>
  </div>
</form>
@endsection
