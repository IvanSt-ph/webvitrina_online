@forelse($categories as $category)
  @php
    $parentsChain = collect();
    $currentParent = $category->parent;

    while ($currentParent) {
        $parentsChain->prepend($currentParent);
        $currentParent = $currentParent->parent;
    }

    $isLeaf = (int) $category->children_count === 0;
    $hasMissingAttributes = $isLeaf && (int) $category->attributes_count === 0;
  @endphp

  <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-start gap-3">
      @if($category->image)
        <img src="{{ asset('storage/'.$category->image) }}" class="h-14 w-14 rounded-xl object-cover" alt="">
      @elseif($category->icon)
        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-slate-50">
          <img src="{{ asset('storage/'.$category->icon) }}" class="h-10 w-10 object-contain" alt="">
        </div>
      @else
        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-slate-50 text-xl text-slate-400">
          <i class="ri-folder-line"></i>
        </div>
      @endif

      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-1.5">
          <h3 class="truncate font-semibold text-slate-900">{{ $category->name }}</h3>
          <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500">ID {{ $category->id }}</span>
        </div>
        <p class="mt-1 truncate text-xs text-slate-500">
          {{ $parentsChain->pluck('name')->push($category->name)->join(' / ') }}
        </p>
        <p class="mt-1 truncate text-xs text-slate-400">{{ $category->slug }}</p>
      </div>
    </div>

    <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
      <div class="rounded-xl bg-indigo-50 px-2 py-2 text-indigo-700">
        <div class="font-bold">{{ $category->children_count }}</div>
        <div>подкат.</div>
      </div>
      <div class="rounded-xl bg-slate-100 px-2 py-2 text-slate-700">
        <div class="font-bold">{{ $category->products_count }}</div>
        <div>товаров</div>
      </div>
      <div class="rounded-xl px-2 py-2 {{ $hasMissingAttributes ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
        <div class="font-bold">{{ $category->attributes_count }}</div>
        <div>хар-ки</div>
      </div>
    </div>

    <div class="mt-3 flex gap-2">
      <a href="{{ route('admin.categories.attributes', $category->id) }}"
         class="flex flex-1 items-center justify-center gap-1.5 rounded-xl bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-100">
        <i class="ri-settings-3-line text-base"></i>
        Характеристики
      </a>
      <a href="{{ route('admin.categories.edit', $category) }}"
         class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 transition hover:bg-indigo-100"
         title="Редактировать">
        <i class="ri-edit-2-line"></i>
      </a>
      <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
            onsubmit="return confirm('Удалить выбранную категорию и ее вложенные категории?')">
        @csrf @method('DELETE')
        <button class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-700 transition hover:bg-rose-100"
                title="Удалить">
          <i class="ri-delete-bin-line"></i>
        </button>
      </form>
    </div>
  </article>
@empty
  <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">
    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-400">
      <i class="ri-search-line"></i>
    </div>
    Категории не найдены. Измените фильтр или добавьте новую категорию.
  </div>
@endforelse
