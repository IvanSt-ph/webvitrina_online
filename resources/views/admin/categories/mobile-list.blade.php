@forelse($categories as $category)
  <article class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
    @if($category->image)
      <img src="{{ asset('storage/'.$category->image) }}" class="h-14 w-14 rounded-lg object-cover" alt="">
    @elseif($category->icon)
      <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-slate-50">
        <img src="{{ asset('storage/'.$category->icon) }}" class="h-10 w-10 object-contain" alt="">
      </div>
    @else
      <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-slate-50 text-xl text-slate-400">
        <i class="ri-folder-line"></i>
      </div>
    @endif

    <div class="min-w-0 flex-1">
      <h3 class="truncate font-semibold text-slate-900">{{ $category->name }}</h3>
      <p class="mt-1 truncate text-xs text-slate-500">
        ID {{ $category->id }} · {{ $category->parent?->name ?? 'Корневая категория' }}
      </p>
    </div>

    <div class="flex shrink-0 gap-1">
      <a href="{{ route('admin.categories.attributes', $category->id) }}"
         class="flex h-9 w-9 items-center justify-center rounded-lg text-amber-600 transition hover:bg-amber-50"
         title="Атрибуты категории">
        <i class="ri-settings-3-line text-lg"></i>
      </a>
      <a href="{{ route('admin.categories.edit', $category) }}"
         class="flex h-9 w-9 items-center justify-center rounded-lg text-indigo-600 transition hover:bg-indigo-50"
         title="Редактировать">
        <i class="ri-edit-2-line"></i>
      </a>
      <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
            onsubmit="return confirm('Удалить выбранную категорию и ее вложенные категории?')">
        @csrf @method('DELETE')
        <button class="flex h-9 w-9 items-center justify-center rounded-lg text-rose-600 transition hover:bg-rose-50"
                title="Удалить">
          <i class="ri-delete-bin-line"></i>
        </button>
      </form>
    </div>
  </article>
@empty
  <div class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-10 text-center text-sm text-slate-500">
    Категории не найдены.
  </div>
@endforelse
