@extends('admin.layout')

@section('content')
@php $isEdit = $banner->exists; @endphp

<div class="flex items-center justify-between mb-8">
  <h1 class="text-2xl font-semibold text-gray-800 tracking-tight">
    {{ $isEdit ? '✏️ Редактирование баннера' : '➕ Добавление нового баннера' }}
  </h1>
  <a href="{{ route('admin.banners.index') }}" 
     class="text-sm text-gray-500 hover:text-gray-700 transition">
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
  action="{{ $isEdit ? route('admin.banners.update', $banner) : route('admin.banners.store') }}"
  method="POST" 
  enctype="multipart/form-data"
  x-data="{
    preview: '{{ $isEdit ? asset('storage/'.$banner->image) : '' }}',
    title: '{{ old('title', $banner->title) }}',
    link: '{{ old('link', $banner->link) }}',
    active: {{ old('active', $banner->active ? 'true' : 'false') }},
  }"
class="bg-white p-8 rounded-2xl border border-gray-200 shadow-sm space-y-8 w-full"

>
  @csrf
  @if($isEdit) @method('PUT') @endif

  {{-- === Основная информация === --}}
  <section>
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Основная информация</h2>
    <div class="grid md:grid-cols-2 gap-8">
      
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Заголовок баннера</label>
        <input type="text" name="title" x-model="title"
               placeholder="Например: Осенние скидки до -30%"
               class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-gray-200 transition">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ссылка (куда ведёт баннер)</label>
        <input type="text" name="link" x-model="link"
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
          Меньшее число — выше в списке. Если одинаково, то по дате добавления.
        </p>
      </div>

      <div class="flex items-center gap-3 pt-5">
        <input type="checkbox" id="active" name="active" value="1" x-model="active"
               class="rounded border-gray-300 text-gray-900 focus:ring-gray-400">
        <label for="active" class="text-sm text-gray-700 select-none">
          Отображать баннер на сайте
        </label>
      </div>
    </div>
  </section>



    {{-- === Изображение баннера === --}}
    {{-- === Реалистичный предпросмотр баннера === --}}
<section 
  x-data="{
    preview: '{{ $isEdit ? asset('storage/'.$banner->image) : '' }}',
    mode: 'desktop',
    get size() {
      return { mobile: 'h-40 w-[390px]', tablet: 'h-60 w-[820px]', desktop: 'h-60 w-[1280px]' }[this.mode];
    },
    get label() {
      return { mobile: '📱 Мобильный', tablet: '💻 Планшет', desktop: '🖥 Десктоп' }[this.mode];
    }
  }"
  class="space-y-4"
>
  <h2 class="text-lg font-semibold text-gray-800 mb-2">Предпросмотр баннера</h2>
  <p class="text-xs text-gray-500">
    Здесь показано, как баннер будет обрезан на сайте. 
    (используется <code>object-cover</code> с фиксированной высотой).
  </p>

  {{-- 🔘 Переключатели устройства --}}
  <div class="flex gap-2">
    <template x-for="opt in ['mobile','tablet','desktop']" :key="opt">
      <button 
        type="button"
        @click="mode = opt"
        class="px-3 py-1.5 rounded-lg text-sm border transition-all duration-150"
        :class="mode === opt ? 'bg-gray-900 text-white border-gray-900' : 'bg-white border-gray-300 hover:bg-gray-100'">
        <span x-text="{mobile:'📱 Мобильный', tablet:'💻 Планшет', desktop:'🖥 Десктоп'}[opt]"></span>
      </button>
    </template>
  </div>

  {{-- 🖼 Контейнер предпросмотра --}}
  <div class="flex flex-col items-center gap-3 bg-gray-50 border border-gray-200 rounded-xl p-4">
    <p class="text-sm text-gray-600" x-text="label"></p>

    <div class="relative overflow-hidden rounded-2xl border border-gray-300 shadow-md"
         :class="size">
      <template x-if="preview">
        <div class="relative w-full h-full">
          <img :src="preview" alt="Баннер" class="absolute inset-0 w-full h-full object-cover">
          <div class="absolute inset-y-0 left-0 w-16 bg-gradient-to-r from-black/25 via-transparent to-transparent pointer-events-none"></div>
          <div class="absolute inset-y-0 right-0 w-16 bg-gradient-to-l from-black/25 via-transparent to-transparent pointer-events-none"></div>
        </div>
      </template>

      <template x-if="!preview">
        <div class="flex items-center justify-center w-full h-full bg-gray-200 text-gray-500 text-sm">
          Нет изображения
        </div>
      </template>
    </div>
  </div>

  {{-- 📤 Загрузка нового файла --}}
  <div>
    <input 
      type="file" name="image" accept="image/*" 
      @change="preview = URL.createObjectURL($event.target.files[0])"
      class="block w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-4 
             file:rounded-lg file:border-0 file:bg-gray-900 file:text-white 
             hover:file:bg-gray-800 transition mt-3"
      {{ $isEdit ? '' : 'required' }}>
  </div>
</section>


  {{-- === Действия === --}}
  <div class="flex gap-4 pt-2">
    <button 
      class="px-6 py-2.5 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-all duration-150 shadow-sm">
      {{ $isEdit ? '💾 Сохранить изменения' : 'Создать баннер' }}
    </button>

    <a href="{{ route('admin.banners.index') }}" 
       class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-all duration-150">
      Отмена
    </a>
  </div>
</form>
@endsection
