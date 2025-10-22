<table class="min-w-full w-full border-separate border-spacing-0 rounded-xl overflow-hidden">
  <thead>
    <tr class="bg-gray-100/80 text-gray-700 text-sm font-semibold uppercase tracking-wide">
      <th class="px-4 py-3 text-left rounded-tl-lg">ID</th>
      <th class="px-4 py-3 text-left">Иконка</th>
      <th class="px-4 py-3 text-left">Изображение</th>
      <th class="px-4 py-3 text-left">Название</th>
      <th class="px-4 py-3 text-left">Slug</th>
      <th class="px-4 py-3 text-left">Родитель</th>
      <th class="px-4 py-3 text-right rounded-tr-lg">Действия</th>
    </tr>
  </thead>

  <tbody class="text-sm divide-y divide-gray-100">
    @forelse($categories as $cat)
      <tr class="hover:bg-indigo-50/50 transition-all duration-200 group cursor-pointer">
        {{-- 🆔 ID --}}
        <td class="px-4 py-3 text-gray-500 font-medium w-14">{{ $cat->id }}</td>

        {{-- 🖼 Иконка --}}
        <td class="px-4 py-3">
          @if($cat->icon)
            <div class="relative w-10 h-10 overflow-hidden rounded-md border bg-white shadow-sm transition-transform duration-300 group-hover:scale-110">
              <img src="{{ asset('storage/' . $cat->icon) }}" alt="icon" class="w-full h-full object-contain">
            </div>
          @else
            <span class="text-gray-400 italic text-xs">нет</span>
          @endif
        </td>

        {{-- 🌄 Изображение --}}
        <td class="px-4 py-3">
          @if($cat->image)
            <div class="relative w-12 h-12 overflow-hidden rounded-md border bg-white shadow-sm transition-transform duration-300 group-hover:scale-110">
              <img src="{{ asset('storage/' . $cat->image) }}" alt="image" class="w-full h-full object-cover">
            </div>
          @else
            <span class="text-gray-400 italic text-xs">нет</span>
          @endif
        </td>

        {{-- 📛 Название --}}
        <td class="px-4 py-3 font-medium text-gray-800 truncate max-w-[200px] group-hover:text-indigo-700 transition">
          {{ $cat->name }}
        </td>

        {{-- 🔗 Slug --}}
        <td class="px-4 py-3">
          <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-md">
            {{ $cat->slug }}
          </span>
        </td>

        {{-- 🌿 Родитель --}}
        <td class="px-4 py-3 text-gray-600 truncate max-w-[180px]">
          {{ $cat->parent?->name ?? '—' }}
        </td>

        {{-- ⚙️ Действия --}}
        <td class="px-4 py-3 text-right">
          <div class="flex items-center justify-end gap-2">
            <a href="{{ route('admin.categories.edit', $cat) }}"
               title="Редактировать"
               class="inline-flex items-center justify-center w-8 h-8 text-indigo-600 bg-indigo-50 rounded-md hover:bg-indigo-100 hover:text-indigo-700 transition">
              <i class="ri-edit-2-line text-lg"></i>
            </a>

            <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST"
                  onsubmit="return confirm('Удалить категорию {{ $cat->name }}?')"
                  class="inline">
              @csrf
              @method('DELETE')
              <button type="submit"
                      title="Удалить"
                      class="inline-flex items-center justify-center w-8 h-8 text-red-600 bg-red-50 rounded-md hover:bg-red-100 hover:text-red-700 transition">
                <i class="ri-delete-bin-line text-lg"></i>
              </button>
            </form>
          </div>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="7" class="px-4 py-6 text-center text-gray-500 italic">
          Категории пока не добавлены.
        </td>
      </tr>
    @endforelse
  </tbody>
</table>
