@extends('admin.layout')
@section('title', 'Цвета')

@section('content')
<div class="space-y-6">

  {{-- Заголовок --}}
  <div>
    <h1 class="text-2xl font-bold text-gray-800">Цвета</h1>
    <p class="text-sm text-gray-500">
      Справочник цветов, используемых в атрибутах типа <b>color</b>
    </p>
  </div>

  {{-- Сообщения --}}
  @if(session('success'))
    <div class="p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm">
      {{ session('success') }}
    </div>
  @endif

  @if(session('error'))
    <div class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
      {{ session('error') }}
    </div>
  @endif

  {{-- Добавление цвета --}}
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <h2 class="text-sm font-semibold text-gray-700 mb-4">
      Добавить цвет
    </h2>

    <form method="POST"
          action="{{ route('admin.colors.store') }}"
          class="flex flex-wrap items-end gap-4">
      @csrf

      <div>
        <label class="block text-xs text-gray-500 mb-1">Название</label>
        <input type="text"
               name="name"
               placeholder="Например: Красный"
               class="border rounded-lg p-2 w-48"
               required>
      </div>

      <div>
        <label class="block text-xs text-gray-500 mb-1">Цвет</label>
        <input type="color"
               name="hex"
               value="#000000"
               class="h-10 w-14 border rounded cursor-pointer">
      </div>

      <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
        Добавить
      </button>
    </form>
  </div>

 {{-- Список цветов --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
  <h2 class="text-sm font-semibold text-gray-700 mb-4">
    Список цветов
  </h2>

  @if($colors->count())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
      @foreach($colors as $color)
        <div class="border rounded-lg p-3 space-y-3">

          {{-- Основная строка --}}
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full border shadow-sm"
                 style="background: {{ $color->hex }}"></div>

            <div class="flex-1">
              <div class="text-sm font-medium text-gray-800">
                {{ $color->name }}
              </div>
              <div class="text-xs text-gray-400">
                {{ $color->hex }}
              </div>
            </div>

            @if($color->attributes_count > 0)
              <span class="px-2 py-0.5 text-xs rounded-md bg-gray-100 text-gray-500">
                используется ({{ $color->attributes_count }})
              </span>
            @else
              <form method="POST"
                    action="{{ route('admin.colors.destroy', $color) }}"
                    onsubmit="return confirm('Удалить цвет?')">
                @csrf @method('DELETE')
                <button class="text-gray-400 hover:text-red-600">
                  <i class="ri-delete-bin-6-line text-lg"></i>
                </button>
              </form>
            @endif
          </div>

          {{-- Редактирование --}}
          <details class="group">
            <summary class="text-xs text-indigo-600 cursor-pointer select-none">
              Редактировать
            </summary>

            <form method="POST"
                  action="{{ route('admin.colors.update', $color) }}"
                  class="flex items-end gap-3 pt-3">
              @csrf
              @method('PUT')

              <div>
                <label class="block text-xs text-gray-500 mb-1">Название</label>
                <input type="text"
                       name="name"
                       value="{{ $color->name }}"
                       class="border rounded-lg p-2 w-full"
                       required>
              </div>

              <div>
                <label class="block text-xs text-gray-500 mb-1">Цвет</label>
                <input type="color"
                       name="hex"
                       value="{{ $color->hex }}"
                       class="h-10 w-14 border rounded">
              </div>

              <button class="px-3 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 text-sm">
                Сохранить
              </button>
            </form>
          </details>

        </div>
      @endforeach
    </div>
  @else
    <div class="text-sm text-gray-500 italic">
      Цвета ещё не добавлены.
    </div>
  @endif
</div>

</div>
@endsection
