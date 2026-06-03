@extends('admin.layout')

@section('title', 'Главная панель')

@section('content')
<div class="mb-5 flex flex-col gap-4 rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-5">
  <div>
    <div class="text-xs font-bold uppercase text-indigo-600">Операционный центр</div>
    <h1 class="mt-1 text-2xl font-bold text-slate-950">Что требует внимания</h1>
    <p class="mt-1 text-sm text-slate-500">Сначала работа с обращениями и решениями, ниже - аналитика магазина.</p>
  </div>
  <a href="{{ route('admin.chats.index') }}"
     class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white transition hover:bg-indigo-700">
    <i class="ri-message-3-line text-lg"></i> Открыть чаты
  </a>
</div>

<section class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
  @foreach([
    ['value' => $workQueue['orders'] ?? 0, 'label' => 'Заказы требуют внимания', 'route' => route('admin.orders.index', ['focus' => 'attention']), 'icon' => 'ri-error-warning-line'],
    ['value' => $workQueue['chats'] ?? 0, 'label' => 'Новые сообщения', 'route' => route('admin.chats.index'), 'icon' => 'ri-message-3-line'],
    ['value' => $workQueue['disputes'] ?? 0, 'label' => 'Споры по заказам', 'route' => route('admin.disputes.index'), 'icon' => 'ri-scales-3-line'],
    ['value' => $workQueue['reviews'] ?? 0, 'label' => 'Отзывы на проверке', 'route' => route('admin.reviews.index', ['status' => 'pending']), 'icon' => 'ri-chat-check-line'],
    ['value' => $workQueue['productReports'] ?? 0, 'label' => 'Жалобы на товары', 'route' => route('admin.product-reports.index'), 'icon' => 'ri-alarm-warning-line'],
    ['value' => $workQueue['plans'] ?? 0, 'label' => 'Заявки на тариф', 'route' => route('admin.seller-plan-requests.index'), 'icon' => 'ri-vip-crown-line'],
    ['value' => $workQueue['banners'] ?? 0, 'label' => 'Баннеры без mobile', 'route' => route('admin.banners.index', ['status' => 'missing_mobile']), 'icon' => 'ri-smartphone-line'],
  ] as $task)
    <a href="{{ $task['route'] }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-200 hover:bg-indigo-50">
      <div class="flex items-center justify-between">
        <span class="text-2xl font-bold {{ $task['value'] > 0 ? 'text-indigo-700' : 'text-slate-400' }}">{{ $task['value'] }}</span>
        <i class="{{ $task['icon'] }} text-lg text-indigo-500"></i>
      </div>
      <div class="mt-2 text-sm font-semibold text-slate-700">{{ $task['label'] }}</div>
    </a>
  @endforeach
</section>

<section class="mb-7 rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm">
  <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <div class="text-xs font-bold uppercase tracking-wide text-indigo-600">Сегодня</div>
      <h2 class="mt-1 text-xl font-bold text-slate-950">Конкретные задачи для проверки</h2>
      <p class="mt-1 text-sm text-slate-500">Не только счётчики: ниже последние объекты, куда админ должен зайти руками.</p>
    </div>
    <a href="{{ route('admin.production-checklist') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-indigo-100 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
      <i class="ri-rocket-line"></i>
      Релиз-чеклист
    </a>
  </div>

  <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2 font-bold text-slate-900"><i class="ri-alarm-warning-line text-indigo-600"></i> Жалобы сегодня</div>
        <a href="{{ route('admin.product-reports.index', ['status' => \App\Models\ProductReport::STATUS_OPEN]) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Все</a>
      </div>
      <div class="mt-3 space-y-2">
        @forelse(($todayQueue['productReports'] ?? collect()) as $report)
          <a href="{{ route('admin.product-reports.index', ['q' => $report->product?->title ?? $report->reason]) }}" class="block rounded-xl bg-white p-3 text-sm transition hover:bg-indigo-50">
            <div class="truncate font-semibold text-slate-900">{{ $report->product?->title ?? 'Товар удалён' }}</div>
            <div class="mt-1 truncate text-xs text-slate-500">{{ $report->reason }} · {{ $report->user?->name ?? 'Покупатель' }}</div>
          </a>
        @empty
          <div class="rounded-xl bg-white p-3 text-xs leading-5 text-slate-500">Новых жалоб сегодня нет.</div>
        @endforelse
      </div>
    </article>

    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2 font-bold text-slate-900"><i class="ri-scales-3-line text-indigo-600"></i> Споры сегодня</div>
        <a href="{{ route('admin.disputes.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Все</a>
      </div>
      <div class="mt-3 space-y-2">
        @forelse(($todayQueue['disputes'] ?? collect()) as $dispute)
          <a href="{{ route('admin.disputes.index', ['q' => $dispute->order?->number]) }}" class="block rounded-xl bg-white p-3 text-sm transition hover:bg-indigo-50">
            <div class="truncate font-semibold text-slate-900">Заказ {{ $dispute->order?->number ?? '—' }}</div>
            <div class="mt-1 truncate text-xs text-slate-500">{{ $dispute->reason }} · {{ $dispute->user?->name ?? 'Покупатель' }}</div>
          </a>
        @empty
          <div class="rounded-xl bg-white p-3 text-xs leading-5 text-slate-500">Новых споров сегодня нет.</div>
        @endforelse
      </div>
    </article>

    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2 font-bold text-slate-900"><i class="ri-chat-check-line text-indigo-600"></i> Отзывы сегодня</div>
        <a href="{{ route('admin.reviews.index', ['status' => \App\Models\Review::STATUS_PENDING]) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Все</a>
      </div>
      <div class="mt-3 space-y-2">
        @forelse(($todayQueue['reviews'] ?? collect()) as $review)
          <a href="{{ route('admin.reviews.index', ['q' => $review->product?->title]) }}" class="block rounded-xl bg-white p-3 text-sm transition hover:bg-indigo-50">
            <div class="truncate font-semibold text-slate-900">{{ $review->product?->title ?? 'Товар' }}</div>
            <div class="mt-1 truncate text-xs text-slate-500">Оценка {{ $review->rating }} · {{ $review->user?->name ?? 'Покупатель' }}</div>
          </a>
        @empty
          <div class="rounded-xl bg-white p-3 text-xs leading-5 text-slate-500">Новых отзывов сегодня нет.</div>
        @endforelse
      </div>
    </article>

    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2 font-bold text-slate-900"><i class="ri-vip-crown-line text-indigo-600"></i> Заявки сегодня</div>
        <a href="{{ route('admin.seller-plan-requests.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Все</a>
      </div>
      <div class="mt-3 space-y-2">
        @forelse(($todayQueue['plans'] ?? collect()) as $planRequest)
          <a href="{{ $planRequest->user ? route('admin.users.show', $planRequest->user) : route('admin.seller-plan-requests.index') }}" class="block rounded-xl bg-white p-3 text-sm transition hover:bg-indigo-50">
            <div class="truncate font-semibold text-slate-900">{{ $planRequest->user?->name ?? 'Продавец' }}</div>
            <div class="mt-1 truncate text-xs text-slate-500">{{ ucfirst($planRequest->current_plan) }} -> {{ ucfirst($planRequest->requested_plan) }}</div>
          </a>
        @empty
          <div class="rounded-xl bg-white p-3 text-xs leading-5 text-slate-500">Новых заявок на тариф сегодня нет.</div>
        @endforelse
      </div>
    </article>

    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2 font-bold text-slate-900"><i class="ri-shopping-bag-3-line text-indigo-600"></i> Заказы сегодня</div>
        <a href="{{ route('admin.orders.index', ['focus' => 'attention']) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Все</a>
      </div>
      <div class="mt-3 space-y-2">
        @forelse(($todayQueue['orders'] ?? collect()) as $order)
          <a href="{{ route('admin.orders.show', $order) }}" class="block rounded-xl bg-white p-3 text-sm transition hover:bg-indigo-50">
            <div class="truncate font-semibold text-slate-900">#{{ $order->number }}</div>
            <div class="mt-1 truncate text-xs text-slate-500">{{ $order->user?->name ?? 'Покупатель' }} / {{ $order->seller?->name ?? 'Продавец' }}</div>
          </a>
        @empty
          <div class="rounded-xl bg-white p-3 text-xs leading-5 text-slate-500">Подозрительных заказов сегодня нет.</div>
        @endforelse
      </div>
    </article>
  </div>
</section>

<section class="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
  <form method="GET" action="{{ route('admin.orders.index') }}" class="grid gap-2 sm:grid-cols-[1fr_auto]">
    <label class="relative">
      <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
      <input name="q" type="search" placeholder="Поиск по заказам, пользователям, товарам" class="h-11 w-full rounded-xl border border-slate-200 pl-10 pr-3 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
    </label>
    <button class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
      <i class="ri-search-line"></i>
      Найти
    </button>
  </form>
</section>

<section class="mb-7 grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(300px,0.8fr)]">
  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-center justify-between gap-3">
      <h2 class="font-bold text-slate-900">Заказы, где нужен контроль</h2>
      <a href="{{ route('admin.orders.index', ['focus' => 'attention']) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Открыть фокус</a>
    </div>
    <div class="mt-3 divide-y divide-slate-100">
      @forelse($attentionOrders as $order)
        <a href="{{ route('admin.orders.show', $order) }}" class="flex items-center justify-between gap-3 py-3 text-sm transition hover:text-indigo-700">
          <div class="min-w-0">
            <div class="truncate font-semibold text-slate-900">#{{ $order->number }}</div>
            <div class="truncate text-xs text-slate-500">{{ $order->user?->name }} / {{ $order->seller?->name }}</div>
            <div class="mt-1 flex flex-wrap gap-1">
              @if($order->cancellation_requested_at && !in_array($order->status, [\App\Models\Order::STATUS_CANCELED, \App\Models\Order::STATUS_COMPLETED], true))
                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">запрос отмены</span>
              @endif
              @if(($order->status === \App\Models\Order::STATUS_PENDING && $order->created_at?->lte(now()->subDay())) || ($order->status === \App\Models\Order::STATUS_PROCESSING && (($order->accepted_at && $order->accepted_at->lte(now()->subDays(2))) || (!$order->accepted_at && $order->created_at?->lte(now()->subDays(2))))))
                <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700">долго без движения</span>
              @endif
            </div>
          </div>
          <div class="shrink-0 text-right">
            <div class="font-bold text-slate-800">{{ $order->formatted_total_price }}</div>
            <div class="text-xs text-slate-400">{{ $order->created_at->diffForHumans() }}</div>
          </div>
        </a>
      @empty
        <p class="py-6 text-center text-sm text-slate-500">Нет заказов с запросом отмены или долгим простоем.</p>
      @endforelse
    </div>
  </div>

  <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-center justify-between gap-3">
      <h2 class="font-bold text-slate-900">Изменение тарифов</h2>
      <a href="{{ route('admin.seller-plan-requests.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Все</a>
    </div>
    <div class="mt-3 space-y-2">
      @forelse($pendingPlans as $planRequest)
        @if($planRequest->user)
        <a href="{{ route('admin.users.show', $planRequest->user) }}" class="block rounded-xl bg-slate-50 p-3 transition hover:bg-indigo-50">
          <div class="truncate text-sm font-semibold text-slate-900">{{ $planRequest->user?->name ?? 'Пользователь удален' }}</div>
          <div class="mt-1 text-xs text-slate-500">{{ ucfirst($planRequest->current_plan) }} -> {{ ucfirst($planRequest->requested_plan) }} · {{ $planRequest->created_at->diffForHumans() }}</div>
        </a>
        @else
          <div class="rounded-xl bg-slate-50 p-3 text-sm text-slate-500">Пользователь этой заявки удалён.</div>
        @endif
      @empty
        <p class="py-6 text-center text-sm text-slate-500">Нет новых заявок.</p>
      @endforelse
    </div>
  </div>
</section>

<!-- ========================== -->
<!-- 📊 ОСНОВНЫЕ ПОКАЗАТЕЛИ -->
<!-- ========================== -->
<div x-data="{ open: false }" class="mb-7">
  <button @click="open = !open"
          class="mb-3 flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
    <span class="font-semibold text-slate-700">Показатели и динамика</span>
    <i class="ri-arrow-down-s-line text-gray-500 text-xl transition-transform duration-300"
       :class="{ 'rotate-180': open }"></i>
  </button>

  <section x-show="open" 
           class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    @php
      $statsData = [
        ['key' => 'products', 'label' => 'Товаров', 'value' => $stats['products'], 'color' => 'indigo', 'icon' => 'ri-box-3-line'],
        ['key' => 'categories', 'label' => 'Категорий', 'value' => $stats['categories'], 'color' => 'blue', 'icon' => 'ri-folder-3-line'],
        ['key' => 'orders', 'label' => 'Заказов', 'value' => $stats['orders'], 'color' => 'green', 'icon' => 'ri-shopping-bag-3-line'],
        ['key' => 'users', 'label' => 'Пользователей', 'value' => $stats['users'], 'color' => 'yellow', 'icon' => 'ri-user-3-line'],
      ];
    @endphp

    @foreach ($statsData as $s)
      @php
        $delta = $statDeltas[$s['key']] ?? ['label' => '0%', 'positive' => true];
      @endphp
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition p-6">
        <div class="flex flex-col items-center text-center">
          <div class="p-2 bg-{{ $s['color'] }}-50 rounded-lg text-{{ $s['color'] }}-600 mb-2">
            <i class="{{ $s['icon'] }} text-2xl"></i>
          </div>
          <div class="text-3xl font-semibold text-gray-800">{{ $s['value'] }}</div>
          <div class="text-sm text-gray-500 mt-1">{{ $s['label'] }}</div>
          <span class="inline-flex items-center gap-1 text-xs font-medium mt-2
                       {{ $delta['positive'] ? 'text-green-600' : 'text-red-600' }}">
            <i class="{{ $delta['positive'] ? 'ri-arrow-up-s-line' : 'ri-arrow-down-s-line' }}"></i>
            {{ $delta['label'] }} за неделю
          </span>
        </div>
      </div>
    @endforeach
  </section>
</div>

<!-- ========================== -->
<!-- 🔝 ТОП-5 СТАТИСТИКА -->
<!-- ========================== -->
<div x-data="{ open: false }" class="mb-7">
  <button @click="open = !open"
          class="mb-3 flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
    <span class="font-medium text-gray-700">ТОП-5 статистика</span>
    <i class="ri-arrow-down-s-line text-gray-500 text-xl transition-transform duration-300"
       :class="{ 'rotate-180': open }"></i>
  </button>

  <section x-show="open" class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
<div x-data="{ open: false }" class="mb-7">
  <button @click="open = !open"
          class="mb-3 flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
    <span class="font-medium text-gray-700">Графики активности</span>
    <i class="ri-arrow-down-s-line text-gray-500 text-xl transition-transform duration-300"
       :class="{ 'rotate-180': open }"></i>
  </button>

  <section x-show="open"  class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
<div x-data="{ open: false }">
  <button @click="open = !open"
          class="mb-3 flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
    <span class="font-medium text-gray-700">Последние товары</span>
    <i class="ri-arrow-down-s-line text-gray-500 text-xl transition-transform duration-300"
       :class="{ 'rotate-180': open }"></i>
  </button>

  <section x-show="open">
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
  const ordersData = @js(array_values($ordersActivity ?? [])) || [0,0,0,0,0,0,0];
  const ordersLabels = @js(array_keys($ordersActivity ?? [])) || ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];

  const categoryLabels = @js($categoryData->pluck('name') ?? []) || ['Категория 1','Категория 2'];
  const categoryValues = @js($categoryData->pluck('products_count') ?? []) || [10,20];

  const userLabels = @js(array_keys($userGrowth ?? [])) || ['Пн','Вт','Ср','Чт','Пт','Сб','Вс'];
  const userValues = @js(array_values($userGrowth ?? [])) || [1,3,2,4,3,5,2];

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
