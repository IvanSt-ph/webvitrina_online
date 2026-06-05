@extends('admin.layout')
@section('title', 'Категории')

@section('content')
<div class="space-y-5">

  {{-- ===== Заголовок ===== --}}
  <div class="rounded-3xl border border-indigo-100 bg-gradient-to-br from-white via-white to-indigo-50/70 p-5 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
          <i class="ri-node-tree"></i>
          Дерево каталога
        </div>
        <h1 class="mt-3 text-2xl sm:text-3xl font-semibold text-gray-900 tracking-tight flex items-center gap-2">
          <i class="ri-folder-3-line text-indigo-600 text-2xl"></i>
          Категории
        </h1>
        <p class="text-sm text-gray-500 mt-1 max-w-2xl">
          Корневые категории попадают в основные разделы, подкатегории помогают навигации, а конечные категории должны иметь характеристики для фильтров и карточек товара.
        </p>
      </div>
      <div class="flex flex-col gap-2 sm:flex-row">
        <a href="{{ route('admin.categories.create') }}"
           class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl shadow hover:bg-indigo-700 hover:shadow-md transition-all duration-200">
          <i class="ri-add-line text-base"></i>
          Добавить категорию
        </a>
      </div>
    </div>

    <div class="mt-5 grid gap-3 md:grid-cols-3">
      <div class="rounded-2xl border border-white bg-white/80 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">1. Корень</p>
        <p class="mt-1 text-sm font-semibold text-slate-900">Раздел витрины</p>
        <p class="mt-1 text-xs text-slate-500">Например: Электроника, Одежда, Зоотовары.</p>
      </div>
      <div class="rounded-2xl border border-white bg-white/80 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">2. Ветка</p>
        <p class="mt-1 text-sm font-semibold text-slate-900">Группа товаров</p>
        <p class="mt-1 text-xs text-slate-500">Помогает покупателю быстро сузить каталог.</p>
      </div>
      <div class="rounded-2xl border border-white bg-white/80 p-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">3. Конечная</p>
        <p class="mt-1 text-sm font-semibold text-slate-900">Товары и характеристики</p>
        <p class="mt-1 text-xs text-slate-500">Сюда продавец привязывает товар, здесь нужны фильтры.</p>
      </div>
    </div>
  </div>

  <details class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-bold text-slate-800">
      <span class="inline-flex items-center gap-2">
        <i class="ri-bar-chart-line text-indigo-600"></i>
        Сводка и аналитика категорий
      </span>
      <i class="ri-arrow-down-s-line text-lg text-slate-400 transition group-open:rotate-180"></i>
    </summary>

    {{-- ===== Верхняя аналитика ===== --}}
    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
      <x-admin.stat-card color="blue"   label="Всего категорий"   :value="$stats['total']" icon="ri-grid-line" />
      <x-admin.stat-card color="green"  label="Корневые категории" :value="$stats['roots']" icon="ri-folder-2-line" />
      <x-admin.stat-card color="purple" label="Подкатегории"       :value="$stats['subs']"  icon="ri-folder-open-line" />
      <x-admin.stat-card color="orange" label="Конечные категории" :value="$stats['leafs']" icon="ri-price-tag-3-line" />
      <x-admin.stat-card color="{{ $stats['without_attributes'] ? 'red' : 'green' }}" label="Без характеристик" :value="$stats['without_attributes']" icon="ri-equalizer-line" />
      <x-admin.stat-card color="gray"   label="Всего товаров"      :value="$stats['products']" icon="ri-shopping-bag-3-line" />
    </div>

  {{-- ===== Аналитика ===== --}}
  <div class="mt-5 space-y-5 border-t border-slate-100 pt-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <h2 class="text-lg sm:text-xl font-semibold text-gray-800 flex items-center gap-2">
        <i class="ri-bar-chart-line text-indigo-600"></i>
        Аналитика категорий
      </h2>
      <div class="flex flex-wrap items-center gap-2">
        @foreach([
          'products'   => ['ri-box-3-line', 'Популярные'],
          'subcats'    => ['ri-node-tree', 'Ассортимент'],
          'efficiency' => ['ri-pie-chart-2-line', 'Эффективность']
        ] as $key => [$icon, $label])
          <a href="{{ route('admin.categories.index', ['mode' => $key]) }}"
             title="Показать аналитику: {{ $label }}"
             class="flex items-center gap-1.5 px-3 py-1.5 text-sm rounded-lg transition-all duration-200
                    {{ $mode === $key
                      ? 'bg-indigo-600 text-white shadow-md font-medium'
                      : 'bg-gray-100 hover:bg-indigo-50 hover:text-indigo-700 text-gray-600' }}">
            <i class="{{ $icon }} text-sm"></i>
            <span>{{ $label }}</span>
          </a>
        @endforeach
      </div>
    </div>

    @if($topParents->isNotEmpty())
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-5">
      @foreach($topParents as $cat)
        <a href="{{ route('admin.categories.index', ['parent_id' => $cat->id]) }}"
           class="group block p-5 bg-gradient-to-b from-white to-gray-50 border border-gray-100 rounded-2xl shadow-sm
                  hover:shadow-xl hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">

          {{-- Верхняя часть --}}
          <div class="flex justify-between items-center mb-2">
            <span class="text-xs text-gray-400 font-medium">#{{ $loop->iteration }}</span>
            @if($cat->icon)
              <img src="{{ $cat->icon_url }}" alt="{{ $cat->name }}"
                   class="w-8 h-8 object-contain rounded bg-white border border-gray-100 shadow-sm">
            @else
              <div class="p-1.5 bg-gray-50 border border-gray-100 rounded-md">
                <i class="ri-folder-2-line text-gray-400 text-lg group-hover:text-indigo-500 transition"></i>
              </div>
            @endif
          </div>

          {{-- Название --}}
          <div class="font-semibold text-gray-800 group-hover:text-indigo-600 truncate">{{ $cat->name }}</div>

          {{-- Мини-индикаторы --}}
          <div class="mt-2 text-xs text-gray-500 space-y-1.5">
            <div class="flex justify-between">
              <span>Подкатегорий:</span>
              <span class="text-indigo-600 font-medium">{{ $cat->children_count }}</span>
            </div>
            <div class="flex justify-between">
              <span>Товаров:</span>
              <span class="text-green-600 font-medium">{{ $cat->products_count ?? 0 }}</span>
            </div>
          </div>

          {{-- Мини-график --}}
          <div class="mt-3">
            <canvas id="chart-{{ $cat->id }}" height="40"></canvas>
          </div>
        </a>

        {{-- Chart.js код --}}
        @push('charts')
        <script>
        document.addEventListener('DOMContentLoaded', () => {
          const ctx = document.getElementById('chart-{{ $cat->id }}');
          if (!ctx) return;

          const data = @json($cat->chart_data->values());
          const trend = data[data.length - 1] - data[0];
          const color = trend > 0 ? '#16a34a' : (trend < 0 ? '#dc2626' : '#6b7280');

          new Chart(ctx, {
            type: 'line',
            data: {
              labels: ['6д','5д','4д','3д','2д','Вчера','Сегодня'],
              datasets: [{
                data: data,
                borderColor: color,
                backgroundColor: color + '22',
                tension: 0.4,
                fill: true,
                borderWidth: 2,
                pointRadius: 0,
              }]
            },
            options: {
              maintainAspectRatio: false,
              plugins: { legend: { display: false } },
              scales: { x: { display: false }, y: { display: false } }
            }
          });
        });
        </script>
        @endpush
      @endforeach
    </div>

    @if(request('parent_id'))
      <div class="mt-6">
        <a href="{{ route('admin.categories.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
          <i class="ri-arrow-left-line text-base"></i> Все категории
        </a>
      </div>
    @endif
    @else
      <p class="text-gray-500 text-sm">Недостаточно данных для анализа.</p>
    @endif
  </div>
  </details>

  {{-- ===== Панель фильтров ===== --}}
  <div class="mt-8 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
      <div class="flex-1">
        <label for="searchInput" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">Поиск</label>
        <div class="relative">
          <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-base"></i>
          <input type="text" id="searchInput"
                 value="{{ request('q') }}"
                 placeholder="Название или slug категории..."
                 class="w-full rounded-xl border border-gray-300 py-2.5 pl-10 pr-3 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500">
        </div>
      </div>

      <div class="grid gap-3 sm:grid-cols-2 lg:w-[520px]">
        <div>
          <label for="parentFilter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">Раздел</label>
          <select id="parentFilter"
                  class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500">
            <option value="">Все разделы</option>
            @foreach($parents as $parent)
              <option value="{{ $parent->id }}" {{ request('parent_id') == $parent->id ? 'selected' : '' }}>
                {{ $parent->name }} · {{ $parent->children_count }}
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label for="typeFilter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-400">Тип</label>
          <select id="typeFilter"
                  class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500">
            <option value="">Все типы</option>
            <option value="root" {{ request('type') === 'root' ? 'selected' : '' }}>Корневые</option>
            <option value="child" {{ request('type') === 'child' ? 'selected' : '' }}>Подкатегории</option>
            <option value="with_children" {{ request('type') === 'with_children' ? 'selected' : '' }}>С подкатегориями</option>
            <option value="leaf" {{ request('type') === 'leaf' ? 'selected' : '' }}>Конечные</option>
            <option value="no_attributes" {{ request('type') === 'no_attributes' ? 'selected' : '' }}>Без характеристик</option>
          </select>
        </div>
      </div>

      <button id="resetBtn"
              class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-200">
        <i class="ri-refresh-line text-base"></i>
        Сброс
      </button>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
      <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1">
        <i class="ri-information-line"></i>
        Конечные категории должны иметь характеристики.
      </span>
      <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-3 py-1">
        <i class="ri-price-tag-3-line"></i>
        Товары лучше привязывать к самому нижнему уровню.
      </span>
    </div>
  </div>

  {{-- ===== Таблица категорий ===== --}}
  <div id="ajaxStatus" class="hidden text-sm text-gray-500 text-center mt-2"></div>
  <div id="categoryTableWrapper" class="hidden md:block transition-all mt-6">
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-x-auto">
      @include('admin.categories.table', ['categories' => $categories])
    </div>
  </div>

  {{-- ===== Красивая пагинация ===== --}}
  <div id="categoryPagination" class="mt-6 flex justify-center">
    @include('admin.categories.pagination', ['categories' => $categories])
  </div>

  {{-- ===== Мобильные карточки ===== --}}
  <div id="categoryMobileWrapper" class="mt-4 block space-y-3 md:hidden">
    @include('admin.categories.mobile-list', ['categories' => $categories])
  </div>
</div>

{{-- ===== JS: AJAX фильтры ===== --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
  const searchInput  = document.getElementById('searchInput');
  const parentFilter = document.getElementById('parentFilter');
  const typeFilter   = document.getElementById('typeFilter');
  const resetBtn     = document.getElementById('resetBtn');
  const wrapper      = document.getElementById('categoryTableWrapper');
  const mobileWrapper = document.getElementById('categoryMobileWrapper');
  const pagination = document.getElementById('categoryPagination');
  const ajaxStatus   = document.getElementById('ajaxStatus');
  let timer;

  const showSkeleton = () => {
    wrapper.innerHTML = `
      <div class="p-6 text-center text-gray-400 animate-pulse">
        Обновляем данные категорий...
      </div>`;
    mobileWrapper.innerHTML = `
      <div class="rounded-xl border border-slate-200 bg-white p-6 text-center text-slate-400 animate-pulse">
        Обновляем категории...
      </div>`;
  };

  const loadUrl = url => {
      const q = searchInput.value.trim();
      const parent_id = parentFilter.value;
      const type = typeFilter.value;
      showSkeleton();
      ajaxStatus.textContent = "Загрузка...";
      ajaxStatus.classList.remove('hidden');
      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => res.json())
        .then(data => {
          wrapper.innerHTML = `<div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-x-auto">${data.desktop}</div>`;
          mobileWrapper.innerHTML = data.mobile;
          pagination.innerHTML = data.pagination;
          ajaxStatus.textContent = "Данные обновлены";
          setTimeout(() => ajaxStatus.classList.add('hidden'), 1200);
        })
        .catch(() => ajaxStatus.textContent = "Не удалось обновить категории");
  };

  const fetchData = () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
      const q = searchInput.value.trim();
      const parent_id = parentFilter.value;
      const type = typeFilter.value;
      const url = `{{ route('admin.categories.index') }}?ajax=1&q=${encodeURIComponent(q)}&parent_id=${encodeURIComponent(parent_id)}&type=${encodeURIComponent(type)}`;
      loadUrl(url);
    }, 350);
  };

  searchInput.addEventListener('input', fetchData);
  parentFilter.addEventListener('change', fetchData);
  typeFilter.addEventListener('change', fetchData);
  resetBtn.addEventListener('click', () => {
    searchInput.value = '';
    parentFilter.value = '';
    typeFilter.value = '';
    fetchData();
  });

  document.addEventListener('click', e => {
    const link = e.target.closest('#categoryPagination a');
    if (!link) return;
    e.preventDefault();
    const url = link.href + (link.href.includes('?') ? '&' : '?') + 'ajax=1';
    loadUrl(url);
  });
});
</script>

{{-- ===== Chart.js подключение и стек ===== --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@stack('charts')
@endsection
