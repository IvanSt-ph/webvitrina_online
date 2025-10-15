@extends('admin.layout')
@section('title', 'Категории')

@section('content')
<div class="space-y-10">

    <!-- 🔖 Заголовок -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">📂 Категории</h1>
        <a href="{{ route('admin.categories.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl shadow hover:bg-indigo-700 transition-all">
            ➕ Добавить категорию
        </a>
    </div>

    <!-- 🧮 Аналитика -->
    <div class="space-y-4 mt-10">
        <h2 class="text-2xl font-semibold text-gray-800 flex items-center gap-2">
            📊 Топ-5 категорий по количеству подкатегорий и товаров
        </h2>

        
        @if($topParents->isNotEmpty())
            <!-- 🔘 Переключатели режимов -->
            <div class="flex items-center gap-2 mb-4">
@foreach([
    'products'   => ['🛍 Популярные категории', 'Больше всего товаров'],
    'subcats'    => ['🏗 Ассортимент', 'Больше подкатегорий'],
    'efficiency' => ['⚖️ Эффективность', 'Больше товаров на подкатегорию']
] as $key => [$label, $tooltip])

    <a href="{{ route('admin.categories.index', array_merge(request()->query(), ['mode' => $key])) }}"
       title="{{ $tooltip }}"
       class="px-3 py-1.5 text-sm rounded-lg {{ $mode === $key ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
        {{ $label }}
    </a>
@endforeach

            </div>

            <!-- 📦 Сетка топ-категорий -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                @foreach($topParents as $cat)
                    <a href="{{ route('admin.categories.index', ['parent_id' => $cat->id]) }}"
                       class="group block p-4 bg-gradient-to-br from-white to-indigo-50 border border-gray-100 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-500 group-hover:text-indigo-600 transition">
                                #{{ $loop->iteration }}
                            </span>
                            @if($cat->icon)
                                <img src="{{ asset('storage/' . $cat->icon) }}" alt="{{ $cat->name }}"
                                     class="w-9 h-9 object-contain rounded-md border border-gray-200 bg-white shadow-sm">
                            @else
                                <span class="text-xl text-gray-400 group-hover:text-indigo-600 transition">🏷️</span>
                            @endif
                        </div>
                        <div class="mt-2 text-lg font-semibold text-gray-800 group-hover:text-indigo-700 transition">
                            {{ $cat->name }}
                        </div>
                        <div class="mt-1 text-sm text-gray-500 leading-tight">
                            Подкатегорий: <span class="font-medium text-indigo-600">{{ $cat->children_count }}</span><br>
                            Товаров: <span class="font-medium text-green-600">{{ $cat->products_count ?? 0 }}</span>
                        </div>
                    </a>
                @endforeach
            </div>

            @if(request('parent_id'))
                <div class="mt-6">
                    <a href="{{ route('admin.categories.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        ⬅️ Вернуться ко всем категориям
                    </a>
                </div>
            @endif
        @else
            <p class="text-gray-500 text-sm">Недостаточно данных для анализа.</p>
        @endif
    </div>




    <!-- 📊 Карточки статистики -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <x-admin.stat-card color="blue" label="Всего категорий" :value="$categories->total()" icon="📦" />
        <x-admin.stat-card color="green" label="Корневые категории" :value="$parents->count()" icon="🌳" />
        <x-admin.stat-card color="purple" label="Подкатегории" :value="$categories->total() - $parents->count()" icon="🧩" />
    </div>

    <!-- 🔍 Панель фильтров -->
    <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-2">
            <label for="parentFilter" class="text-gray-500 text-sm">Фильтр по родителю:</label>
            <select id="parentFilter"
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-400">
                <option value="">Все категории</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" {{ (string)request('parent_id') === (string)$parent->id ? 'selected' : '' }}>
                        {{ $parent->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-2 w-full md:w-1/3">
            <div class="relative w-full">
                <input type="text" id="searchInput"
                       value="{{ request('q') }}"
                       placeholder=" Поиск категорий..."
                       class="w-full border border-gray-300 rounded-lg pl-10 pr-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-400">
                <span class="absolute left-3 top-2.5 text-gray-400">🔍</span>
            </div>
            <button id="resetBtn"
                    class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg border border-gray-200 transition">
                🔄
            </button>
        </div>
    </div>

    <!-- 📋 Таблица -->
    <div id="categoryTableWrapper"
         class="bg-white shadow rounded-xl border border-gray-100 overflow-hidden transition-all">
        @include('admin.categories.table', ['categories' => $categories])
    </div>
</div>

<!-- ⚙️ JS -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput  = document.getElementById('searchInput');
    const parentFilter = document.getElementById('parentFilter');
    const resetBtn     = document.getElementById('resetBtn');
    const wrapper      = document.getElementById('categoryTableWrapper');
    let timer;

    const showSkeleton = () => {
        wrapper.innerHTML = `
            <div class="p-6 animate-pulse space-y-3">
                <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                <div class="h-4 bg-gray-200 rounded w-full"></div>
            </div>`;
    };

    const fetchData = () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const q = searchInput.value.trim();
            const parent_id = parentFilter.value;
            const url = `{{ route('admin.categories.index') }}?ajax=1&q=${encodeURIComponent(q)}&parent_id=${encodeURIComponent(parent_id)}`;
            showSkeleton();
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.text())
                .then(html => wrapper.innerHTML = html)
                .catch(err => console.error('Ошибка AJAX:', err));
        }, 350);
    };

    searchInput.addEventListener('input', fetchData);
    parentFilter.addEventListener('change', fetchData);
    resetBtn.addEventListener('click', () => {
        searchInput.value = '';
        parentFilter.value = '';
        fetchData();
    });

    document.addEventListener('click', e => {
        const link = e.target.closest('.pagination a');
        if (!link) return;
        e.preventDefault();
        const url = link.href + (link.href.includes('?') ? '&' : '?') + 'ajax=1';
        showSkeleton();
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => wrapper.innerHTML = html)
            .catch(err => console.error('Ошибка пагинации:', err));
    });
});
</script>
@endsection
