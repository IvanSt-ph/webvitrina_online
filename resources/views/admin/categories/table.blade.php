<table class="min-w-full w-full border-separate border-spacing-0 rounded-xl overflow-hidden">
  <thead>
    <tr class="bg-gray-100/80 text-gray-700 text-xs font-semibold uppercase tracking-wide">
      <th class="px-4 py-3 text-left rounded-tl-lg">Категория</th>
      <th class="px-4 py-3 text-left">Путь и уровень</th>
      <th class="px-4 py-3 text-center">Подкатегории</th>
      <th class="px-4 py-3 text-center">Товары</th>
      <th class="px-4 py-3 text-center">Характеристики</th>
      <th class="px-4 py-3 text-left">Slug</th>
      <th class="px-4 py-3 text-right rounded-tr-lg">Действия</th>
    </tr>
  </thead>

  <tbody class="text-sm divide-y divide-gray-100">
    @forelse($categories as $cat)
      @php
        $parentsChain = collect();
        $currentParent = $cat->parent;

        while ($currentParent) {
            $parentsChain->prepend($currentParent);
            $currentParent = $currentParent->parent;
        }

        $level = $parentsChain->count() + 1;
        $isLeaf = (int) $cat->children_count === 0;
        $hasMissingAttributes = $isLeaf && (int) $cat->attributes_count === 0;
      @endphp

      <tr class="group transition-all duration-200 hover:bg-indigo-50/50">
        <td class="px-4 py-3">
          <div class="flex items-center gap-3">
            @if($cat->image)
              <img src="{{ $cat->image_thumb_url }}" alt="{{ $cat->name }}" class="h-12 w-12 rounded-xl border border-slate-100 object-cover shadow-sm">
            @elseif($cat->icon)
              <span class="flex h-12 w-12 items-center justify-center rounded-xl border border-slate-100 bg-white shadow-sm">
                <img src="{{ $cat->icon_url }}" alt="{{ $cat->name }}" class="h-8 w-8 object-contain">
              </span>
            @else
              <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-xl text-slate-400">
                <i class="ri-folder-line"></i>
              </span>
            @endif

            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-semibold text-gray-900 group-hover:text-indigo-700">{{ $cat->name }}</span>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">ID {{ $cat->id }}</span>
                @if($isLeaf)
                  <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">конечная</span>
                @else
                  <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700">ветка</span>
                @endif
              </div>
              <p class="mt-1 max-w-[360px] truncate text-xs text-slate-500">
                Родитель: {{ $cat->parent?->name ?? 'корневая категория' }}
              </p>
            </div>
          </div>
        </td>

        <td class="px-4 py-3 text-slate-600">
          <div class="max-w-[300px] truncate text-xs">
            @if($parentsChain->isNotEmpty())
              {{ $parentsChain->pluck('name')->push($cat->name)->join(' / ') }}
            @else
              {{ $cat->name }}
            @endif
          </div>
          <span class="mt-1 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
            Уровень {{ $level }}
          </span>
        </td>

        <td class="px-4 py-3 text-center">
          <span class="inline-flex min-w-10 items-center justify-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
            {{ $cat->children_count }}
          </span>
        </td>

        <td class="px-4 py-3 text-center">
          <span class="inline-flex min-w-10 items-center justify-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
            {{ $cat->products_count }}
          </span>
        </td>

        <td class="px-4 py-3 text-center">
          <span class="inline-flex min-w-10 items-center justify-center rounded-full px-3 py-1 text-xs font-semibold {{ $hasMissingAttributes ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
            {{ $cat->attributes_count }}
          </span>
          @if($hasMissingAttributes)
            <div class="mt-1 text-[11px] font-medium text-rose-600">нужно добавить</div>
          @endif
        </td>

        <td class="px-4 py-3">
          <span class="inline-flex max-w-[180px] items-center truncate rounded-lg bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
            {{ $cat->slug }}
          </span>
        </td>

        <td class="px-4 py-3 text-right">
          <div class="flex items-center justify-end gap-2">
            <a href="{{ route('admin.categories.attributes', $cat->id) }}"
              title="Характеристики категории"
              class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">
              <i class="ri-settings-3-line text-base"></i>
              <span class="hidden xl:inline">Характеристики</span>
            </a>
            <a href="{{ route('admin.categories.edit', $cat) }}"
               title="Редактировать"
               class="inline-flex items-center justify-center rounded-lg bg-indigo-50 px-3 py-2 text-indigo-700 transition hover:bg-indigo-100">
              <i class="ri-edit-2-line text-base"></i>
            </a>
            <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST"
                  onsubmit="return confirm('Удалить выбранную категорию и ее вложенные категории?')"
                  class="inline">
              @csrf
              @method('DELETE')
              <button type="submit"
                      title="Удалить"
                      class="inline-flex items-center justify-center rounded-lg bg-red-50 px-3 py-2 text-red-700 transition hover:bg-red-100">
                <i class="ri-delete-bin-line text-base"></i>
              </button>
            </form>
          </div>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="7" class="px-4 py-12 text-center">
          <div class="mx-auto max-w-md">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-2xl text-slate-400">
              <i class="ri-search-line"></i>
            </div>
            <h3 class="mt-4 text-base font-semibold text-slate-900">Категории не найдены</h3>
            <p class="mt-1 text-sm text-slate-500">Попробуйте изменить поиск, сбросить фильтры или создать новую категорию.</p>
            <a href="{{ route('admin.categories.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
              <i class="ri-add-line"></i>
              Добавить категорию
            </a>
          </div>
        </td>
      </tr>
    @endforelse
  </tbody>
</table>
