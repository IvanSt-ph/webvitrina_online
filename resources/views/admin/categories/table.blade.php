<table class="min-w-full w-full table-fixed border-collapse">
    <thead class="bg-gray-100 sticky top-0 z-10">
    <tr>
        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">ID</th>
        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Иконка</th>
        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Изображение</th>
        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Название</th>
        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Slug</th>
        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Родитель</th>
        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">Действия</th>
    </tr>
    </thead>

    <tbody>
    @forelse($categories as $cat)
        <tr class="border-t hover:bg-gray-50 odd:bg-gray-50/30 transition">
            <td class="px-4 py-3 text-gray-500">{{ $cat->id }}</td>

            {{-- 🖼 Иконка --}}
            <td class="px-4 py-3">
                @if($cat->icon)
                    <div class="relative group w-10 h-10">
                        <img src="{{ asset('storage/' . $cat->icon) }}"
                             alt="icon"
                             class="w-10 h-10 object-contain rounded border bg-white shadow-sm transition-transform duration-300 group-hover:scale-110">
                        <img src="{{ asset('storage/' . $cat->icon) }}"
                             alt="icon preview"
                             class="absolute hidden group-hover:block z-20 w-32 h-32 object-contain border bg-white rounded shadow-lg top-[-5px] left-[45px]">
                    </div>
                @else
                    <span class="text-gray-400 italic">нет</span>
                @endif
            </td>

            {{-- 🌄 Изображение плитки --}}
            <td class="px-4 py-3">
                @if($cat->image)
                    <div class="relative group w-12 h-12">
                        <img src="{{ asset('storage/' . $cat->image) }}"
                             alt="image"
                             class="w-12 h-12 object-cover rounded border shadow-sm transition-transform duration-300 group-hover:scale-105">
                        <img src="{{ asset('storage/' . $cat->image) }}"
                             alt="image preview"
                             class="absolute hidden group-hover:block z-30 w-40 h-40 object-cover border bg-white rounded shadow-xl top-[-10px] left-[50px]">
                    </div>
                @else
                    <span class="text-gray-400 italic">нет</span>
                @endif
            </td>

            {{-- 📛 Название --}}
            <td class="px-4 py-3 font-medium text-gray-800">{{ $cat->name }}</td>

            {{-- 🔗 Slug --}}
            <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs font-medium bg-gray-200 text-gray-700 rounded">{{ $cat->slug }}</span>
            </td>

            {{-- 🌿 Родитель --}}
            <td class="px-4 py-3 text-gray-600">
                {{ $cat->parent?->name ?? '—' }}
            </td>

            {{-- ⚙️ Действия --}}
            <td class="px-4 py-3 text-right flex items-center gap-2 justify-end">
                <a href="{{ route('admin.categories.edit', $cat) }}"
                   class="inline-flex items-center justify-center w-8 h-8 text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition">
                    ✏️
                </a>
                <form action="{{ route('admin.categories.destroy', $cat) }}" method="POST"
                      onsubmit="return confirm('Удалить категорию {{ $cat->name }}?')" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center justify-center w-8 h-8 text-red-600 bg-red-50 rounded hover:bg-red-100 transition">
                        🗑
                    </button>
                </form>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="px-4 py-6 text-center text-gray-500">Категории пока не добавлены.</td>
        </tr>
    @endforelse
    </tbody>
</table>

{{-- 📄 Пагинация --}}
@if($categories->hasPages())
    <div class="mt-4 px-4">
        {{ $categories->links() }}
    </div>
@endif
