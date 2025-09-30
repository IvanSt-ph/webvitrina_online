@extends('admin.layout')

@section('title', 'Категории')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">📂 Категории</h1>

        <a href="{{ route('admin.categories.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg shadow hover:bg-indigo-700 transition">
            ➕ <span>Добавить</span>
        </a>
    </div>

    <!-- Статистика -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="p-4 bg-white shadow rounded-lg">
            <div class="text-gray-500 text-sm">Всего категорий</div>
            <div class="text-2xl font-bold">{{ $categories->total() }}</div>
        </div>
        <div class="p-4 bg-white shadow rounded-lg">
            <div class="text-gray-500 text-sm">Корневые категории</div>
            <div class="text-2xl font-bold">{{ $parents->count() }}</div>
        </div>
        <div class="p-4 bg-white shadow rounded-lg">
            <div class="text-gray-500 text-sm">Подкатегории</div>
            <div class="text-2xl font-bold">{{ $categories->total() - $parents->count() }}</div>
        </div>
    </div>

    <!-- Панель фильтрации и поиска -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
        <!-- Фильтр по родителю -->
        <form method="GET" action="{{ route('admin.categories.index') }}" class="flex items-center gap-2">
            <select name="parent_id" onchange="this.form.submit()"
                    class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                <option value="">Все категории</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" {{ request('parent_id') == $parent->id ? 'selected' : '' }}>
                        {{ $parent->name }}
                    </option>
                @endforeach
            </select>
        </form>

        <!-- Поиск + сброс -->
        <div class="flex items-center gap-2 w-full md:w-1/3">
            <input type="text" id="searchInput" placeholder="🔍 Поиск категорий..."
                   class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">

            <a href="{{ route('admin.categories.index') }}"
               class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">
                🔄
            </a>
        </div>
    </div>

    <!-- Таблица -->
    <div class="bg-white shadow rounded-lg overflow-x-auto"
         x-data="{ openModal: false, deleteUrl: '', deleteName: '' }">
        <table class="min-w-full w-full table-fixed border-collapse">
            <thead class="bg-gray-100 sticky top-0 z-10">
            <tr>
                <th class="w-1/12 px-4 py-3 text-left text-sm font-semibold text-gray-600 cursor-pointer sort" data-col="0">ID</th>
                <th class="w-2/12 px-4 py-3 text-left text-sm font-semibold text-gray-600">Иконка</th>
                <th class="w-3/12 px-4 py-3 text-left text-sm font-semibold text-gray-600 cursor-pointer sort" data-col="2">Название</th>
                <th class="w-2/12 px-4 py-3 text-left text-sm font-semibold text-gray-600 cursor-pointer sort" data-col="3">Slug</th>
                <th class="w-2/12 px-4 py-3 text-left text-sm font-semibold text-gray-600">Родитель</th>
                <th class="w-2/12 px-4 py-3 text-right text-sm font-semibold text-gray-600">Действия</th>
            </tr>
            </thead>
            <tbody id="categoryTable">
            @forelse($categories as $category)
                <tr class="border-t hover:bg-gray-50 odd:bg-gray-50/30">
                    <td class="px-4 py-3">{{ $category->id }}</td>
                    <td class="px-4 py-3">
                        @if($category->icon)
                            <img src="{{ asset('storage/' . $category->icon) }}"
                                 class="w-8 h-8 object-contain rounded border" alt="icon">
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
                        <!-- Редактировать -->
                        <a href="{{ route('admin.categories.edit', $category) }}"
                           class="inline-flex items-center justify-center w-8 h-8 text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition"
                           title="Редактировать">
                            ✏️
                        </a>

                        <!-- Удалить (через модалку) -->
                        <button
                            @click="openModal = true; deleteUrl = '{{ route('admin.categories.destroy', $category) }}'; deleteName = '{{ $category->name }}'"
                            class="inline-flex items-center justify-center w-8 h-8 text-red-600 bg-red-50 rounded hover:bg-red-100 transition"
                            title="Удалить">
                            🗑
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                        Категории пока не добавлены.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

<!-- Модальное окно -->
<div 
    x-show="openModal"
    x-transition.opacity
    x-cloak
    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
>
    <!-- Окно -->
    <div class="bg-white rounded-xl shadow-xl w-auto max-w-md p-6 mx-4"
         x-transition.scale>
        <!-- Заголовок -->
        <div class="flex items-center gap-3 mb-4">
            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-600">
                ⚠️
            </div>
            <h2 class="text-lg font-semibold text-gray-800">Удаление категории</h2>
        </div>

        <p class="text-gray-600 mb-6">
            Вы уверены, что хотите удалить категорию
            <span class="font-semibold text-red-600" x-text="deleteName"></span>?
        </p>

        <!-- Кнопки -->
        <div class="flex justify-end gap-3">
            <button @click="openModal = false"
                    type="button"
                    class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">
                Отмена
            </button>
            <form x-bind:action="deleteUrl" method="POST">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
                    Удалить
                </button>
            </form>
        </div>
    </div>
</div>


    <!-- Пагинация -->
    <div class="mt-4">
        {{ $categories->links() }}
    </div>

    <!-- JS: поиск с подсветкой + сортировка -->
    <script>
        // 🔍 Поиск с подсветкой
        document.getElementById("searchInput").addEventListener("keyup", function () {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll("#categoryTable tr");

            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = "";
                    row.querySelectorAll("td").forEach(td => {
                        td.innerHTML = td.innerText.replace(
                            new RegExp(filter, "gi"),
                            match => `<mark class="bg-yellow-200">${match}</mark>`
                        );
                    });
                } else {
                    row.style.display = "none";
                }
            });
        });

        // ↕️ Сортировка
        document.querySelectorAll("th.sort").forEach(th => {
            th.addEventListener("click", function () {
                let colIndex = this.dataset.col;
                let rows = Array.from(document.querySelectorAll("#categoryTable tr"));
                let asc = this.classList.toggle("asc");

                rows.sort((a, b) => {
                    let A = a.cells[colIndex].innerText.toLowerCase();
                    let B = b.cells[colIndex].innerText.toLowerCase();
                    return asc ? A.localeCompare(B) : B.localeCompare(A);
                });

                let tbody = document.getElementById("categoryTable");
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    </script>
@endsection
