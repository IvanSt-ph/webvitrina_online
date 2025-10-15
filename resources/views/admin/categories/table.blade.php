<table class="min-w-full w-full table-fixed border-collapse">
    <thead class="bg-gray-100 sticky top-0 z-10">
    <tr>
        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">ID</th>
        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Иконка</th>
        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Название</th>
        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Slug</th>
        <th class="px-4 py-3 text-left text-sm font-semibold text-gray-600">Родитель</th>
        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-600">Действия</th>
    </tr>
    </thead>
    <tbody>
    @forelse($categories as $category)
        <tr class="border-t hover:bg-gray-50 odd:bg-gray-50/30">
            <td class="px-4 py-3">{{ $category->id }}</td>
            <td class="px-4 py-3">
                @if($category->icon)
                    <img src="{{ asset('storage/' . $category->icon) }}" class="w-8 h-8 object-contain rounded border" alt="icon">
                @else
                    <span class="text-gray-400 italic">нет</span>
                @endif
            </td>
            <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs font-medium bg-gray-200 text-gray-700 rounded">{{ $category->slug }}</span>
            </td>
            <td class="px-4 py-3">
                @if($category->parent)
                    <span class="px-2 py-1 text-xs bg-indigo-100 text-indigo-700 rounded">
                        {{ $category->parent->name }}
                    </span>
                @else
                    <span class="text-gray-400">—</span>
                @endif
            </td>
            <td class="px-4 py-3 text-right flex items-center gap-2 justify-end">
                <a href="{{ route('admin.categories.edit', $category) }}"
                   class="inline-flex items-center justify-center w-8 h-8 text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition">✏️</a>

                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Удалить категорию?')">
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
            <td colspan="6" class="px-4 py-6 text-center text-gray-500">Категории пока не добавлены.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<div class="mt-4 px-4">
    {{ $categories->links() }}
</div>
