@extends('admin.layout')
@section('title', 'Атрибуты — '.$category->name)

@section('content')
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8">

  {{-- Назад к категориям --}}
  <a href="{{ route('admin.categories.index') }}"
     class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-indigo-600 mb-4 transition">
    <i class="ri-arrow-left-line"></i>
    Назад к категориям
  </a>

  {{-- Заголовок --}}
  <h1 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
    <i class="ri-settings-3-line text-amber-600"></i>
    Атрибуты категории: <span class="text-indigo-600">{{ $category->name }}</span>
  </h1>

  {{-- Сообщения --}}
  @if(session('success'))
    <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm flex items-center gap-2">
      <i class="ri-checkbox-circle-line text-green-600"></i>
      {{ session('success') }}
    </div>
  @endif

  @if(session('error'))
    <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-2">
      <i class="ri-error-warning-line text-red-600"></i>
      {{ session('error') }}
    </div>
  @endif

  {{-- Добавить новый атрибут --}}
  <h2 class="text-lg font-semibold text-gray-700 mb-3 flex items-center gap-2">
    <i class="ri-add-circle-line text-indigo-500"></i>
    Добавить новый атрибут
  </h2>

  <form action="{{ route('admin.categories.attributes.store', $category->id) }}" method="POST"
        class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-10"
        x-data="{ type: 'select', colors: [] }">
    @csrf

    {{-- Название --}}
    <div>
      <label class="block text-sm text-gray-700 mb-1">Название</label>
      <input type="text" name="name" autocomplete="off"
             class="w-full border rounded-lg p-2 focus:ring-indigo-500 focus:border-indigo-500" required>
    </div>

    {{-- Тип --}}
    <div>
      <label class="block text-sm text-gray-700 mb-1">Тип</label>
      <select name="type" x-model="type"
              class="w-full border rounded-lg p-2 focus:ring-indigo-500 focus:border-indigo-500">
        <option value="select">select (список)</option>
        <option value="number">number (число)</option>
        <option value="color">color (цвет)</option>
        <option value="text">text (текст)</option>
      </select>
    </div>

    {{-- Значения --}}
    <div x-show="type === 'select' || type === 'color'" x-transition>
      <label class="block text-sm text-gray-700 mb-1">Значения (через запятую)</label>
      <input type="text" name="options" x-model="inputValue"
             class="w-full border rounded-lg p-2 focus:ring-indigo-500 focus:border-indigo-500"
             @input="if(type==='color'){colors = inputValue.split(',').map(c=>c.trim()).filter(Boolean)}"
             :placeholder="type === 'select'
               ? '22,24,26,28'
               : (type === 'color' ? '#ff0000,#00ff00,#0000ff' : 'Введите значения...')">

      {{-- Превью цветов --}}
      <template x-if="type === 'color' && colors.length">
        <div class="flex flex-wrap gap-2 mt-2">
          <template x-for="(c, i) in colors" :key="i">
            <div class="w-6 h-6 rounded-full border shadow-sm" :style="`background-color: ${c}`" :title="c"></div>
          </template>
        </div>
      </template>
    </div>

    {{-- Кнопка --}}
    <div class="md:col-span-3 mt-3">
      <button type="submit"
              class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow transition">
        <i class="ri-save-line text-lg"></i>
        <span>Сохранить</span>
      </button>
    </div>
  </form>

  <div class="border-t border-gray-100 my-8"></div>

  {{-- Список атрибутов --}}
  <h2 class="text-lg font-semibold text-gray-700 mb-3 flex items-center gap-2">
    <i class="ri-list-check-2 text-amber-500"></i>
    Список атрибутов
  </h2>

  @if($attributes->count())
    <table class="w-full text-sm mb-8 border border-gray-100 rounded-lg overflow-hidden">
      <thead class="bg-gray-50 text-gray-600">
        <tr>
          <th class="p-3 text-left">Название</th>
          <th class="p-3 text-left">Тип</th>
          <th class="p-3 text-left">Значения</th>
          <th class="p-3 text-right">Действия</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @foreach($attributes as $attr)
        <tr class="hover:bg-gray-50 transition">
          <td class="p-3 font-medium text-gray-800">{{ $attr->name }}</td>
          <td class="p-3 text-gray-600">{{ $attr->type }}</td>
          <td class="p-3 text-gray-500">
            @if($attr->options)
              {{ implode(', ', $attr->options) }}
            @else
              <span class="text-gray-400 italic">—</span>
            @endif
          </td>
          <td class="p-3 text-right">
            <form action="{{ route('admin.categories.attributes.destroy', [$category->id, $attr->id]) }}"
                  method="POST" onsubmit="return confirm('Удалить атрибут {{ $attr->name }}?')" class="inline">
              @csrf
              @method('DELETE')
              <button class="text-red-600 hover:text-red-800 transition" title="Удалить">
                <i class="ri-delete-bin-6-line text-lg"></i>
              </button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <div class="text-center py-6 border border-dashed border-gray-200 rounded-lg text-gray-500">
      Нет атрибутов в этой категории.<br>
      <span class="text-indigo-600 font-medium">Добавьте первый атрибут выше.</span>
    </div>
  @endif
</div>
@endsection
