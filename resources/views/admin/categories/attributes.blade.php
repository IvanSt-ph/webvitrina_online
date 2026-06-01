@extends('admin.layout')
@section('title', 'Характеристики — '.$category->name)

@section('content')
@php
  $typeLabels = [
    'select' => 'Выбор из списка',
    'number' => 'Число',
    'color' => 'Цвет',
    'text' => 'Текст',
  ];

  $typeHints = [
    'select' => 'Лучше для бренда, размера, материала, типа товара.',
    'number' => 'Лучше для веса, диагонали, объема, мощности.',
    'color' => 'Использует справочник цветов.',
    'text' => 'Используйте редко: такие данные хуже фильтровать.',
  ];

  $parentsChain = collect();
  $currentParent = $category->parent;

  while ($currentParent) {
      $parentsChain->prepend($currentParent);
      $currentParent = $currentParent->parent;
  }

  $categoryPath = $parentsChain->pluck('name')->push($category->name)->join(' / ');
  $isLeaf = (int) $category->children_count === 0;
@endphp

<div class="space-y-5"
     x-data="{
        showEdit:false,
        editId:null,
        editName:'',
        editType:'select',
        editUnit:'',
        editOptions:'',
        editColors: [],
        editFilterable:true,
        query:'',
        openEditor(attribute) {
          this.editId = attribute.id;
          this.editName = attribute.name;
          this.editType = attribute.type;
          this.editUnit = attribute.unit || '';
          this.editOptions = attribute.options || '';
          this.editColors = attribute.colors || [];
          this.editFilterable = attribute.is_filterable;
          this.showEdit = true;
        },
        matchesRow(text) {
          return !this.query.trim() || text.toLowerCase().includes(this.query.trim().toLowerCase());
        },
     }">

  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <a href="{{ route('admin.categories.index') }}"
       class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-indigo-600">
      <i class="ri-arrow-left-line"></i>
      Назад к категориям
    </a>

    <a href="{{ route('admin.colors.index') }}" target="_blank"
       class="inline-flex items-center justify-center gap-2 rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
      <i class="ri-palette-line"></i>
      Справочник цветов
    </a>
  </div>

  <section class="rounded-3xl border border-amber-100 bg-gradient-to-br from-white via-white to-amber-50/70 p-5 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
          <i class="ri-equalizer-line"></i>
          Характеристики категории
        </div>
        <h1 class="mt-3 text-2xl font-bold text-gray-900">
          {{ $category->name }}
        </h1>
        <p class="mt-1 max-w-3xl text-sm text-gray-500">
          {{ $categoryPath }}
        </p>
      </div>

      <div class="grid min-w-full gap-2 sm:grid-cols-3 lg:min-w-[420px]">
        <div class="rounded-2xl border border-white bg-white/80 p-4 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Тип</div>
          <div class="mt-1 text-sm font-bold {{ $isLeaf ? 'text-emerald-700' : 'text-indigo-700' }}">
            {{ $isLeaf ? 'Конечная' : 'С подкатегориями' }}
          </div>
        </div>
        <div class="rounded-2xl border border-white bg-white/80 p-4 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Товары</div>
          <div class="mt-1 text-xl font-bold text-slate-900">{{ $category->products_count }}</div>
        </div>
        <div class="rounded-2xl border border-white bg-white/80 p-4 shadow-sm">
          <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Полей</div>
          <div class="mt-1 text-xl font-bold text-slate-900">{{ $attributes->count() }}</div>
        </div>
      </div>
    </div>

    @if(!$isLeaf)
      <div class="mt-4 rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
        <i class="ri-information-line mr-1"></i>
        Эта категория ещё имеет подкатегории. Обычно характеристики добавляют на самый нижний уровень, куда продавец выбирает товар.
      </div>
    @elseif($attributes->isEmpty())
      <div class="mt-4 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <i class="ri-alert-line mr-1"></i>
        Конечная категория без характеристик: продавцу будет сложнее заполнить карточку, а покупателю — фильтровать товары.
      </div>
    @endif
  </section>

  @if(session('success'))
    <div class="rounded-2xl border border-green-200 bg-green-50 p-3 text-sm text-green-700">
      {{ session('success') }}
    </div>
  @endif

  @if(session('error'))
    <div class="rounded-2xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
      {{ session('error') }}
    </div>
  @endif

  <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
    <div class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm">
      <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">Добавить характеристику</h2>
          <p class="text-sm text-gray-500">Создавайте поля так, чтобы продавец выбирал готовые значения, а не писал как попало.</p>
        </div>
      </div>

      <form action="{{ route('admin.categories.attributes.store', $category->id) }}" method="POST"
            class="grid grid-cols-1 gap-4 md:grid-cols-2"
            x-data="{ type: 'select', filterable: true, name: '', options: '', unit: '' }">
        @csrf

        <div>
          <label class="mb-1.5 block text-sm font-semibold text-gray-700">Название</label>
          <input type="text" name="name" x-model="name"
                 class="w-full rounded-xl border border-gray-300 p-2.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500"
                 placeholder="Например: Бренд, Размер, Материал" required>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-semibold text-gray-700">Тип поля</label>
          <select name="type" x-model="type"
                  class="w-full rounded-xl border border-gray-300 p-2.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500">
            @foreach($typeLabels as $value => $label)
              <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
          </select>
          <p class="mt-1 text-xs text-gray-400" x-text="{
            select: 'Лучше для бренда, размера, материала, типа товара.',
            number: 'Лучше для веса, диагонали, объема, мощности.',
            color: 'Использует справочник цветов.',
            text: 'Используйте редко: такие данные хуже фильтровать.'
          }[type]"></p>
        </div>

        <div x-show="type === 'number'">
          <label class="mb-1.5 block text-sm font-semibold text-gray-700">Единица измерения</label>
          <input type="text" name="unit" x-model="unit"
                 class="w-full rounded-xl border border-gray-300 p-2.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500"
                 placeholder="кг, см, л, Вт">
        </div>

        <div x-show="type !== 'number'">
          <label class="mb-1.5 block text-sm font-semibold text-gray-700">Единица измерения</label>
          <input type="text" name="unit" x-model="unit"
                 class="w-full rounded-xl border border-gray-300 p-2.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500"
                 placeholder="Обычно пусто">
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
          <input type="hidden" name="is_filterable" value="0">
          <label class="flex cursor-pointer items-start gap-3">
            <input type="checkbox" name="is_filterable" value="1" x-model="filterable"
                   class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <span>
              <span class="block text-sm font-semibold text-gray-800">Показывать в фильтрах</span>
              <span class="block text-xs text-gray-500">Отключайте только служебные поля, которые не нужны покупателю.</span>
            </span>
          </label>
        </div>

        <div class="md:col-span-2" x-show="type === 'select'">
          <label class="mb-1.5 block text-sm font-semibold text-gray-700">Готовые значения</label>
          <textarea name="options" x-model="options"
                    class="h-24 w-full resize-none rounded-xl border border-gray-300 p-2.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500"
                    placeholder="Apple, Samsung, Xiaomi&#10;или каждое значение с новой строки"></textarea>
        </div>

        <div class="md:col-span-2" x-show="type === 'color'">
          <div class="mb-2 flex items-center justify-between gap-2">
            <label class="block text-sm font-semibold text-gray-700">Цвета из справочника</label>
            <a href="{{ route('admin.colors.index') }}" target="_blank" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Управлять цветами</a>
          </div>
          <div class="flex flex-wrap gap-3">
            @foreach($colors as $c)
              <label class="group flex cursor-pointer items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2">
                <input type="checkbox" name="colors[]" value="{{ $c->id }}" class="hidden peer">
                <span class="h-6 w-6 rounded-full border shadow-sm peer-checked:ring-2 peer-checked:ring-indigo-600"
                      style="background: {{ $c->hex }}"></span>
                <span class="text-sm text-gray-700 group-hover:text-indigo-600">{{ $c->name }}</span>
              </label>
            @endforeach
          </div>
        </div>

        <div class="md:col-span-2 rounded-2xl border border-dashed border-indigo-200 bg-indigo-50/60 p-4">
          <div class="text-xs font-semibold uppercase tracking-wide text-indigo-500">Предпросмотр для продавца</div>
          <label class="mt-2 block text-sm font-semibold text-gray-800" x-text="name || 'Название характеристики'"></label>
          <template x-if="type === 'select'">
            <select class="mt-1 w-full rounded-xl border border-indigo-100 bg-white p-2.5 text-sm text-gray-500">
              <option x-text="options.split(/[\n,]+/).map(v => v.trim()).filter(Boolean)[0] || '— не выбрано —'"></option>
            </select>
          </template>
          <template x-if="type === 'number'">
            <div class="mt-1 flex rounded-xl border border-indigo-100 bg-white">
              <input type="number" class="min-w-0 flex-1 rounded-l-xl border-0 p-2.5 text-sm" placeholder="0">
              <span class="border-l border-indigo-100 px-3 py-2.5 text-sm text-gray-500" x-text="unit || 'ед.'"></span>
            </div>
          </template>
          <template x-if="type === 'text'">
            <input class="mt-1 w-full rounded-xl border border-indigo-100 bg-white p-2.5 text-sm" placeholder="Текстовое значение">
          </template>
          <template x-if="type === 'color'">
            <div class="mt-2 flex items-center gap-2 text-sm text-gray-500">
              <span class="h-7 w-7 rounded-full border bg-slate-200"></span>
              Выбор цвета из справочника
            </div>
          </template>
        </div>

        <div class="md:col-span-2">
          <button class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
            <i class="ri-add-line"></i>
            Добавить характеристику
          </button>
        </div>
      </form>
    </div>

    <aside class="space-y-3">
      <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="font-semibold text-slate-900">Быстрые правила</h3>
        <div class="mt-4 space-y-3 text-sm text-slate-600">
          <p><span class="font-semibold text-slate-900">Select</span> — лучший выбор для фильтров: бренд, размер, тип, материал.</p>
          <p><span class="font-semibold text-slate-900">Number</span> — только для измеримых значений: вес, объем, мощность.</p>
          <p><span class="font-semibold text-slate-900">Text</span> — осторожно, покупатель не сможет нормально фильтровать разные варианты написания.</p>
        </div>
      </div>

      <div class="rounded-3xl border border-amber-100 bg-amber-50 p-5 text-sm text-amber-900">
        <div class="flex items-center gap-2 font-semibold">
          <i class="ri-lightbulb-line"></i>
          Совет
        </div>
        <p class="mt-2">Для маркетплейса лучше 3-7 сильных характеристик на категорию, чем 20 полей, которые продавец заполнит случайно.</p>
      </div>
    </aside>
  </section>

  <section class="rounded-3xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h2 class="text-lg font-semibold text-gray-900">Характеристики категории</h2>
        <p class="text-sm text-gray-500">Здесь видно, что попадёт в форму товара и фильтры покупателя.</p>
      </div>
      <div class="relative w-full lg:w-80">
        <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <input type="search" x-model="query"
               class="w-full rounded-xl border border-gray-300 py-2.5 pl-10 pr-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500"
               placeholder="Найти характеристику...">
      </div>
    </div>

    @if($attributes->count())
      <div class="overflow-x-auto rounded-2xl border border-slate-100">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
            <tr>
              <th class="p-3 text-left">Поле</th>
              <th class="p-3 text-left">Тип</th>
              <th class="p-3 text-left">Значения</th>
              <th class="p-3 text-left">Фильтр</th>
              <th class="p-3 text-right">Действия</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @foreach($attributes as $attr)
              @php
                $options = collect($attr->options ?? []);
                $searchText = mb_strtolower($attr->name.' '.$attr->type.' '.$options->join(' '));
              @endphp
              <tr class="hover:bg-gray-50" data-attribute-row x-show="matchesRow(@js($searchText))">
                <td class="p-3">
                  <div class="font-semibold text-gray-900">{{ $attr->name }}</div>
                  @if($attr->unit)
                    <div class="mt-1 text-xs text-gray-400">Единица: {{ $attr->unit }}</div>
                  @endif
                </td>
                <td class="p-3">
                  <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                    {{ $typeLabels[$attr->type] ?? $attr->type }}
                  </span>
                  <div class="mt-1 max-w-[220px] text-xs text-gray-400">{{ $typeHints[$attr->type] ?? '' }}</div>
                </td>
                <td class="p-3">
                  @if($attr->type === 'color')
                    <div class="flex flex-wrap gap-2">
                      @forelse($attr->colors as $c)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2 py-1 text-xs text-gray-600 ring-1 ring-slate-200">
                          <span class="h-4 w-4 rounded-full border" style="background: {{ $c->hex }}"></span>
                          {{ $c->name }}
                        </span>
                      @empty
                        <span class="text-gray-400 italic">цвета не выбраны</span>
                      @endforelse
                    </div>
                  @elseif($options->isNotEmpty())
                    <div class="flex max-w-xl flex-wrap gap-1.5">
                      @foreach($options->take(8) as $option)
                        <span class="rounded-full bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700">{{ $option }}</span>
                      @endforeach
                      @if($options->count() > 8)
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-500">+{{ $options->count() - 8 }}</span>
                      @endif
                    </div>
                  @else
                    <span class="text-gray-400 italic">—</span>
                  @endif
                </td>
                <td class="p-3">
                  @if($attr->is_filterable)
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                      <i class="ri-filter-3-line"></i>
                      В фильтрах
                    </span>
                  @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                      <i class="ri-eye-off-line"></i>
                      Только в карточке
                    </span>
                  @endif
                </td>
                <td class="p-3">
                  <div class="flex justify-end gap-2">
                    <button type="button"
                            @click='openEditor(@js([
                              "id" => $attr->id,
                              "name" => $attr->name,
                              "type" => $attr->type,
                              "unit" => $attr->unit,
                              "is_filterable" => (bool) $attr->is_filterable,
                              "options" => implode("\n", $attr->options ?? []),
                              "colors" => $attr->colors->pluck("id")->values(),
                            ]))'
                            class="inline-flex items-center justify-center rounded-lg bg-indigo-50 px-3 py-2 text-indigo-700 transition hover:bg-indigo-100"
                            title="Редактировать">
                      <i class="ri-edit-line"></i>
                    </button>

                    <form action="{{ route('admin.categories.attributes.destroy', [$category->id, $attr->id]) }}"
                          method="POST"
                          onsubmit="return confirm('Удалить выбранный атрибут?')">
                      @csrf @method('DELETE')
                      <button class="inline-flex items-center justify-center rounded-lg bg-red-50 px-3 py-2 text-red-700 transition hover:bg-red-100"
                              title="Удалить">
                        <i class="ri-delete-bin-6-line"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <div class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl text-slate-400">
          <i class="ri-equalizer-line"></i>
        </div>
        <h3 class="mt-4 font-semibold text-slate-900">Характеристик пока нет</h3>
        <p class="mt-1 text-sm text-slate-500">Добавьте хотя бы бренд, тип, размер/объем или материал — зависит от категории.</p>
      </div>
    @endif
  </section>

  <div x-show="showEdit" x-cloak
       class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl"
         @click.away="showEdit=false"
         x-transition>
      <div class="mb-5 flex items-start justify-between gap-3">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">Редактировать характеристику</h2>
          <p class="text-sm text-gray-500">Изменение повлияет на форму товара и фильтры категории.</p>
        </div>
        <button type="button" @click="showEdit=false" class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700">
          <i class="ri-close-line text-xl"></i>
        </button>
      </div>

      <form method="POST" :action="'/admin/categories/{{ $category->id }}/attributes/' + editId" class="grid gap-4 md:grid-cols-2">
        @csrf @method('PUT')

        <div>
          <label class="mb-1.5 block text-sm font-semibold text-gray-700">Название</label>
          <input type="text" name="name" x-model="editName"
                 class="w-full rounded-xl border border-gray-300 p-2.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500">
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-semibold text-gray-700">Тип</label>
          <select name="type" x-model="editType"
                  class="w-full rounded-xl border border-gray-300 p-2.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500">
            @foreach($typeLabels as $value => $label)
              <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="mb-1.5 block text-sm font-semibold text-gray-700">Единица измерения</label>
          <input type="text" name="unit" x-model="editUnit"
                 class="w-full rounded-xl border border-gray-300 p-2.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500"
                 placeholder="кг, см, л, Вт">
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
          <input type="hidden" name="is_filterable" value="0">
          <label class="flex cursor-pointer items-start gap-3">
            <input type="checkbox" name="is_filterable" value="1" x-model="editFilterable"
                   class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            <span>
              <span class="block text-sm font-semibold text-gray-800">Показывать в фильтрах</span>
              <span class="block text-xs text-gray-500">Иначе поле останется только в карточке товара.</span>
            </span>
          </label>
        </div>

        <div x-show="editType==='select'" class="md:col-span-2">
          <label class="mb-1.5 block text-sm font-semibold text-gray-700">Значения</label>
          <textarea name="options" x-model="editOptions"
                    class="h-28 w-full resize-none rounded-xl border border-gray-300 p-2.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500"
                    placeholder="Одно значение на строку или через запятую"></textarea>
        </div>

        <div x-show="editType==='color'" class="md:col-span-2">
          <label class="mb-2 block text-sm font-semibold text-gray-700">Цвета</label>
          <div class="flex flex-wrap gap-3">
            @foreach($colors as $c)
              <label class="group flex cursor-pointer items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2">
                <input type="checkbox"
                       name="colors[]"
                       x-model="editColors"
                       :value="{{ $c->id }}"
                       class="hidden peer">
                <span class="h-6 w-6 rounded-full border shadow-sm peer-checked:ring-2 peer-checked:ring-indigo-600"
                      style="background: {{ $c->hex }}"></span>
                <span class="text-sm text-gray-700">{{ $c->name }}</span>
              </label>
            @endforeach
          </div>
        </div>

        <div class="md:col-span-2 flex justify-end gap-3 border-t border-slate-100 pt-4">
          <button type="button"
                  @click="showEdit=false"
                  class="rounded-xl bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">
            Отмена
          </button>

          <button class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            Сохранить
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
