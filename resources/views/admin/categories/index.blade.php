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

<!-- 📊 Статистика -->
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

<!-- 🔍 Панель фильтрации и поиска -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
    <!-- Фильтр по родителю -->
    <div class="flex items-center gap-2">
        <select id="parentFilter"
                class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
            <option value="">Все категории</option>
            @foreach($parents as $parent)
                <option value="{{ $parent->id }}" {{ (string)request('parent_id') === (string)$parent->id ? 'selected' : '' }}>
                    {{ $parent->name }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Поиск -->
    <div class="flex items-center gap-2 w-full md:w-1/3">
        <input type="text" id="searchInput" value="{{ request('q') }}"
               placeholder="🔍 Поиск категорий..."
               class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
        <button id="resetBtn"
                class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg"
                title="Сбросить фильтры">
            🔄
        </button>
    </div>
</div>

<!-- 📋 Таблица категорий -->
<div id="categoryTableWrapper" class="bg-white shadow rounded-lg overflow-x-auto">
    @include('admin.categories.table', ['categories' => $categories])
</div>

<!-- ⚙️ JS: AJAX-поиск, фильтр и пагинация -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput  = document.getElementById('searchInput');
    const parentFilter = document.getElementById('parentFilter');
    const resetBtn     = document.getElementById('resetBtn');
    const wrapper      = document.getElementById('categoryTableWrapper');
    let timer;

    // 🔁 Запрос данных
    function fetchData() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const q = searchInput.value || '';
            const parent_id = parentFilter.value || '';

            const url = `{{ route('admin.categories.index') }}?ajax=1&q=${encodeURIComponent(q)}&parent_id=${encodeURIComponent(parent_id)}`;

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.text())
            .then(html => {
                wrapper.innerHTML = html;
            })
            .catch(err => console.error('Ошибка AJAX:', err));
        }, 300);
    }

    // 🔍 Поиск при вводе
    searchInput.addEventListener('input', fetchData);

    // 📂 Фильтрация по родителю
    parentFilter.addEventListener('change', fetchData);

    // ♻️ Сброс фильтров
    resetBtn.addEventListener('click', () => {
        searchInput.value = '';
        parentFilter.value = '';
        fetchData();
    });

    // 📄 AJAX-пагинация
    document.addEventListener('click', e => {
        const a = e.target.closest('.pagination a');
        if (!a) return;
        e.preventDefault();
        const url = a.href + (a.href.includes('?') ? '&' : '?') + 'ajax=1';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => wrapper.innerHTML = html)
            .catch(err => console.error('Ошибка пагинации:', err));
    });
});
</script>
@endsection
