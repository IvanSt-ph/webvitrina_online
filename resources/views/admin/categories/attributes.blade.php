@extends('admin.layout')
@section('title', 'Атрибуты — '.$category->name)

@section('content')
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8"
     x-data="{
        showEdit:false,
        editId:null,
        editName:'',
        editType:'',
        editOptions:'',
        editColors: [],
     }">

  {{-- Назад --}}
  <a href="{{ route('admin.categories.index') }}"
     class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-indigo-600 mb-4">
    <i class="ri-arrow-left-line"></i> Назад к категориям
  </a>

  {{-- Заголовок --}}
  <h1 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
    <i class="ri-settings-3-line text-amber-600"></i>
    Атрибуты категории: <span class="text-indigo-600">{{ $category->name }}</span>
  </h1>

  {{-- Сообщения --}}
  @if(session('success'))
    <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm">
      {{ session('success') }}
    </div>
  @endif

  @if(session('error'))
    <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
      {{ session('error') }}
    </div>
  @endif

  {{-- Добавить новый атрибут --}}
  <h2 class="text-lg font-semibold text-gray-700 mb-3">
    <i class="ri-add-circle-line text-indigo-500"></i>
    Добавить новый атрибут
  </h2>

  <form action="{{ route('admin.categories.attributes.store', $category->id) }}" method="POST"
        class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-10"
        x-data="{ type: 'select', selectedColors: [] }">

    @csrf

    {{-- Название --}}
    <div>
      <label class="block text-sm text-gray-700 mb-1">Название</label>
      <input type="text" name="name" class="w-full border rounded-lg p-2" required>
    </div>

    {{-- Тип --}}
    <div>
      <label class="block text-sm text-gray-700 mb-1">Тип</label>
      <select name="type" x-model="type" class="w-full border rounded-lg p-2">
        <option value="select">select</option>
        <option value="number">number</option>
        <option value="color">color</option>
        <option value="text">text</option>
      </select>
    </div>

    {{-- Значения --}}
    <div x-show="type === 'select'">
      <label class="block text-sm text-gray-700 mb-1">Значения (через запятую)</label>
      <input type="text" name="options" class="w-full border rounded-lg p-2" placeholder="S,M,L">
    </div>

    {{-- Цвета --}}
    <div class="md:col-span-3" x-show="type === 'color'">
      <label class="block text-sm text-gray-700 mb-2">Выберите цвета</label>

      <div class="flex flex-wrap gap-3">
        @foreach($colors as $c)
          <label class="flex items-center gap-2 cursor-pointer group">

            <input type="checkbox"
                   name="colors[]"
                   value="{{ $c->id }}"
                   class="hidden peer">

            <div class="w-7 h-7 rounded-full border shadow-sm peer-checked:ring-2 peer-checked:ring-indigo-600"
                 style="background: {{ $c->hex }}">
            </div>

            <span class="text-sm text-gray-700 group-hover:text-indigo-600">{{ $c->name }}</span>
          </label>
        @endforeach
      </div>
    </div>

    <div class="md:col-span-3 mt-3">
      <button class="px-5 py-2.5 bg-indigo-600 text-white rounded-lg">Сохранить</button>
    </div>
  </form>

  <div class="border-t my-8"></div>

  {{-- Таблица атрибутов --}}
  <h2 class="text-lg font-semibold text-gray-700 mb-3">
    <i class="ri-list-check-2 text-amber-500"></i> Список атрибутов
  </h2>

  @if($attributes->count())
  <table class="w-full text-sm mb-8 border rounded-lg overflow-hidden">
    <thead class="bg-gray-50">
      <tr>
        <th class="p-3 text-left">Название</th>
        <th class="p-3 text-left">Тип</th>
        <th class="p-3 text-left">Значения</th>
        <th class="p-3 text-left">Цвета</th>
        <th class="p-3 text-right">Действия</th>
      </tr>
    </thead>
    <tbody>
      @foreach($attributes as $attr)
      <tr class="hover:bg-gray-50">
        <td class="p-3 font-medium">{{ $attr->name }}</td>
        <td class="p-3">{{ $attr->type }}</td>

        {{-- Значения --}}
        <td class="p-3">
          @if($attr->options)
            {{ implode(', ', $attr->options) }}
          @else
            <span class="text-gray-400 italic">—</span>
          @endif
        </td>

        {{-- Цвета --}}
        <td class="p-3">
          @if($attr->type === 'color')
            <div class="flex gap-2">
              @foreach($attr->colors as $c)
                <div class="w-6 h-6 rounded-full border shadow-sm"
                     title="{{ $c->name }}"
                     style="background: {{ $c->hex }}"></div>
              @endforeach
            </div>
          @else
            <span class="text-gray-400">—</span>
          @endif
        </td>

        <td class="p-3 text-right flex justify-end gap-3">

          {{-- Редактировать --}}
          <button type="button"
                  @click="
                    editId={{$attr->id}};
                    editName='{{$attr->name}}';
                    editType='{{$attr->type}}';
                    editOptions='{{ implode(',', $attr->options ?? []) }}';
                    editColors={{ json_encode($attr->colors->pluck('id')) }};
                    showEdit=true;
                  "
                  class="text-indigo-600 hover:text-indigo-800">
            <i class="ri-edit-line text-lg"></i>
          </button>

          {{-- Удалить --}}
          <form action="{{ route('admin.categories.attributes.destroy', [$category->id, $attr->id]) }}"
                method="POST"
                onsubmit="return confirm('Удалить атрибут {{ $attr->name }}?')">
            @csrf @method('DELETE')
            <button class="text-red-600 hover:text-red-800">
              <i class="ri-delete-bin-6-line text-lg"></i>
            </button>
          </form>

        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @else
    <div class="py-6 text-center text-gray-500 border border-dashed rounded-lg">
      Нет атрибутов в этой категории.
    </div>
  @endif

  {{-- Модальное окно редактирования --}}
  <div x-show="showEdit" x-cloak
       class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">

    <div class="bg-white rounded-xl p-6 w-full max-w-lg"
         @click.away="showEdit=false"
         x-transition>

      <h2 class="text-lg font-semibold mb-4">Редактировать атрибут</h2>

      <form method="POST"
            :action="'/admin/categories/{{ $category->id }}/attributes/' + editId">
        @csrf @method('PUT')

        <label class="block text-sm mb-1">Название</label>
        <input type="text" name="name" x-model="editName"
               class="w-full border rounded-lg p-2 mb-3">

        <label class="block text-sm mb-1">Тип</label>
        <select name="type" x-model="editType"
                class="w-full border rounded-lg p-2 mb-3">
          <option value="select">select</option>
          <option value="number">number</option>
          <option value="text">text</option>
          <option value="color">color</option>
        </select>

        {{-- Значения --}}
        <div x-show="editType==='select'" class="mb-4">
          <label class="block text-sm mb-1">Значения</label>
          <textarea name="options" x-model="editOptions"
                    class="w-full border rounded-lg p-2 h-24"></textarea>
        </div>

        {{-- Цвета --}}
        <div x-show="editType==='color'" class="mb-4">
          <label class="block text-sm mb-1">Цвета</label>

          <div class="flex flex-wrap gap-3">
            @foreach($colors as $c)
              <label class="flex items-center gap-2 cursor-pointer group">

                <input type="checkbox"
                       name="colors[]"
                       :checked="editColors.includes({{ $c->id }})"
                       value="{{ $c->id }}"
                       class="hidden peer">

                <div class="w-7 h-7 rounded-full border shadow-sm peer-checked:ring-2 peer-checked:ring-indigo-600"
                     style="background: {{ $c->hex }}">
                </div>

                <span class="text-sm text-gray-700">{{ $c->name }}</span>
              </label>
            @endforeach
          </div>
        </div>

        <div class="flex justify-end gap-3">
          <button type="button"
                  @click="showEdit=false"
                  class="px-4 py-2 bg-gray-100 rounded-lg">
            Отмена
          </button>

          <button class="px-5 py-2 bg-indigo-600 text-white rounded-lg">
            Сохранить
          </button>
        </div>

      </form>

    </div>
  </div>

</div>
@endsection
