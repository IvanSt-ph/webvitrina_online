@extends('admin.layout')

@section('title', 'Главная панель')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
  <div>
    <h1 class="text-2xl font-bold text-gray-800">Общая информация</h1>
    <p class="text-sm text-gray-500">Статистика за последние 7 дней</p>
  </div>
  <a href="{{ route('admin.products.create') }}"
     class="mt-3 sm:mt-0 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
    <i class="ri-add-line text-lg"></i> Добавить товар
  </a>
</div>

<!-- ========================== -->
<!-- 📊 ОСНОВНЫЕ ПОКАЗАТЕЛИ -->
<!-- ========================== -->
<div x-data="{ open: window.innerWidth >= 640 }" class="mb-10">
  <button @click="open = !open"
          class="sm:hidden w-full flex justify-between items-center px-4 py-3 bg-gray-100 rounded-lg mb-3">
    <span class="font-medium text-gray-700">Основные показатели</span>
    <i class="ri-arrow-down-s-line text-gray-500 text-xl transition-transform duration-300"
       :class="{ 'rotate-180': open }"></i>
  </button>

  <section x-show="open" x-collapse
           class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    @php
      $statsData = [
        ['label' => 'Товаров', 'value' => $stats['products'], 'color' => 'indigo', 'icon' => 'ri-box-3-line', 'delta' => '+4%'],
        ['label' => 'Категорий', 'value' => $stats['categories'], 'color' => 'blue', 'icon' => 'ri-folder-3-line', 'delta' => '+2%'],
        ['label' => 'Заказов', 'value' => $stats['orders'], 'color' => 'green', 'icon' => 'ri-shopping-bag-3-line', 'delta' => '-1%'],
        ['label' => 'Пользователей', 'value' => $stats['users'], 'color' => 'yellow', 'icon' => 'ri-user-3-line', 'delta' => '+8%'],
      ];
    @endphp

    @foreach ($statsData as $s)
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition p-6">
        <div class="flex flex-col items-center text-center">
          <div class="p-2 bg-{{ $s['color'] }}-50 rounded-lg text-{{ $s['color'] }}-600 mb-2">
            <i class="{{ $s['icon'] }} text-2xl"></i>
          </div>
          <div class="text-3xl font-semibold text-gray-800">{{ $s['value'] }}</div>
          <div class="text-sm text-gray-500 mt-1">{{ $s['label'] }}</div>
          <span class="inline-flex items-center gap-1 text-xs font-medium mt-2
                       {{ str_contains($s['delta'], '+') ? 'text-green-600' : 'text-red-600' }}">
            <i class="{{ str_contains($s['delta'], '+') ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}"></i>
            {{ $s['delta'] }} за неделю
          </span>
        </div>
      </div>
    @endforeach
  </section>
</div>

<!-- ========================== -->
<!-- 🔝 ТОП-5 СТАТИСТИКА -->
<!-- ========================== -->
<div x-data="{ open: window.innerWidth >= 640 }" class="mb-10">
  <button @click="open = !open"
          class="sm:hidden w-full flex justify-between items-center px-4 py-3 bg-gray-100 rounded-lg mb-3">
    <span class="font-medium text-gray-700">ТОП-5 статистика</span>
    <i class="ri-arrow-down-s-line text-gray-500 text-xl transition-transform duration-300"
       :class="{ 'rotate-180': open }"></i>
  </button>

  <section x-show="open" x-collapse class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- 📦 Популярные товары -->
    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <i class="ri-fire-line text-orange-500 text-xl"></i> Популярные товары
      </h3>
      <ul class="divide-y divide-gray-100">
        @forelse ($topProducts as $p)
          <li class="py-2 flex justify-between text-sm">
            <span class="truncate max-w-[150px]" title="{{ $p->title }}">{{ $p->title }}</span>
            <span class="text-gray-500">{{ $p->orders_count ?? 0 }} заказов</span>
          </li>
        @empty
          <li class="py-2 text-gray-400 text-sm text-center">Нет данных</li>
        @endforelse
      </ul>
    </div>

    <!-- 🏷️ Популярные категории -->
    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <i class="ri-stack-line text-indigo-500 text-xl"></i> Топ-категории
      </h3>
      <ul class="divide-y divide-gray-100">
        @forelse ($topCategories as $cat)
          <li class="py-2 flex justify-between text-sm">
            <span>{{ $cat->name }}</span>
            <span class="text-gray-500">{{ $cat->products_count ?? 0 }} товаров</span>
          </li>
        @empty
          <li class="py-2 text-gray-400 text-sm text-center">Нет данных</li>
        @endforelse
      </ul>
    </div>

    <!-- 🛒 Лучшие продавцы -->
    <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <i class="ri-user-star-line text-green-500 text-xl"></i> Топ-продавцы
      </h3>
      <ul class="divide-y divide-gray-100">
        @forelse ($topSellers as $s)
          <li class="py-2 flex justify-between text-sm">
            <span>{{ $s->name }}</span>
            <span class="text-gray-500">{{ $s->products_count ?? 0 }} товаров</span>
          </li>
        @empty
          <li class="py-2 text-gray-400 text-sm text-center">Нет данных</li>
        @endforelse
      </ul>
    </div>
  </section>
</div>

<!-- ========================== -->
<!-- 📈 ГРАФИКИ -->
<!-- ========================== -->
<div x-data="{ open: window.innerWidth >= 640 }" class="mb-10">
  <button @click="open = !open"
          class="sm:hidden w-full flex justify-between items-center px-4 py-3 bg-gray-100 rounded-lg mb-3">
    <span class="font-medium text-gray-700">Графики активности</span>
    <i class="ri-arrow-down-s-line text-gray-500 text-xl transition-transform duration-300"
       :class="{ 'rotate-180': open }"></i>
  </button>

  <section x-show="open" x-collapse class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 flex flex-col">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Активность заказов</h2>
        <span class="text-xs text-gray-400">14 дней</span>
      </div>
      <div class="h-64"><canvas id="ordersChart"></canvas></div>
    </div>

    <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 flex flex-col">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Распределение по категориям</h2>
        <span class="text-xs text-gray-400">Топ-5 категорий</span>
      </div>
      <div class="h-64"><canvas id="categoryChart"></canvas></div>
    </div>

    <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 flex flex-col">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Прирост пользователей</h2>
        <span class="text-xs text-gray-400">7 дней</span>
      </div>
      <div class="h-64"><canvas id="userChart"></canvas></div>
    </div>
  </section>
</div>

<!-- ========================== -->
<!-- 🧾 ПОСЛЕДНИЕ ТОВАРЫ -->
<!-- ========================== -->
<div x-data="{ open: window.innerWidth >= 640 }">
  <button @click="open = !open"
          class="sm:hidden w-full flex justify-between items-center px-4 py-3 bg-gray-100 rounded-lg mb-3">
    <span class="font-medium text-gray-700">Последние товары</span>
    <i class="ri-arrow-down-s-line text-gray-500 text-xl transition-transform duration-300"
       :class="{ 'rotate-180': open }"></i>
  </button>

  <section x-show="open" x-collapse>
    <div class="bg-white rounded-xl shadow border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Название</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Категория</th>
              <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Продавец</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse($products as $p)
              <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3 text-sm text-gray-700">{{ $p->id }}</td>
                <td class="px-4 py-3 text-sm text-indigo-600 font-medium truncate max-w-[200px]" title="{{ $p->title }}">{{ $p->title }}</td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ $p->category->name ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ $p->seller->name ?? '—' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="px-4 py-6 text-center text-gray-400">Нет товаров</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    <div class="mt-4">{{ $products->links() }}</div>
  </section>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { x: { grid: { display: false } }, y: { grid: { color: '#F3F4F6' } } }
  };

  // ✅ Реальные данные или fallback при пустых массивах
  const ordersData = {!! json_encode(array_values($ordersActivity ?? [])) !!} || [0,0,0,0,0,0,0];
  const ordersLabels = {!! json_encode(array_keys($ordersActivity ?? [])) !!} || ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];

  const categoryLabels = {!! json_encode($categoryData->pluck('name') ?? []) !!} || ['Категория 1','Категория 2'];
  const categoryValues = {!! json_encode($categoryData->pluck('products_count') ?? []) !!} || [10,20];

  const userLabels = {!! json_encode(array_keys($userGrowth ?? [])) !!} || ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];
  const userValues = {!! json_encode(array_values($userGrowth ?? [])) !!} || [1,3,2,4,3,5,2];

  // === 📈 Графики ===
  new Chart(document.getElementById('ordersChart'), {
    type: 'line',
    data: {
      labels: ordersLabels,
      datasets: [{
        data: ordersData,
        borderColor: '#4F46E5',
        backgroundColor: 'rgba(79,70,229,0.08)',
        fill: true,
        tension: 0.35,
        pointRadius: 0
      }]
    },
    options: commonOptions
  });

  new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
      labels: categoryLabels,
      datasets: [{
        data: categoryValues,
        backgroundColor: ['#6366F1','#22C55E','#F59E0B','#3B82F6','#EC4899'],
        borderWidth: 2,
        borderColor: '#fff',
        hoverOffset: 6
      }]
    },
    options: { 
      plugins: { 
        legend: { position: 'bottom', labels: { color: '#6B7280' } }
      }
    }
  });

  new Chart(document.getElementById('userChart'), {
    type: 'bar',
    data: {
      labels: userLabels,
      datasets: [{
        data: userValues,
        backgroundColor: 'rgba(99,102,241,0.7)',
        borderRadius: 6
      }]
    },
    options: commonOptions
  });
</script>


@endsection
