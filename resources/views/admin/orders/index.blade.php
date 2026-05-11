@extends('admin.layout')

@section('title', 'Заказы')

@section('content')
@php
    $currentStatus = request('status');
    $search = request('q');

    $tabs = [
        null => 'Все',
        \App\Models\Order::STATUS_PENDING => 'Ожидают',
        \App\Models\Order::STATUS_PROCESSING => 'Приняты',
        \App\Models\Order::STATUS_PAID => 'Оплачены',
        \App\Models\Order::STATUS_SHIPPED => 'В пути',
        \App\Models\Order::STATUS_DELIVERED => 'Доставлены',
        \App\Models\Order::STATUS_COMPLETED => 'Завершены',
        \App\Models\Order::STATUS_CANCELED => 'Отменённые',
    ];

    $statusColors = [
        'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
        'processing' => 'bg-sky-50 text-sky-700 border-sky-200',
        'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'shipped' => 'bg-blue-50 text-blue-700 border-blue-200',
        'delivered' => 'bg-green-50 text-green-700 border-green-200',
        'completed' => 'bg-slate-50 text-slate-700 border-slate-200',
        'canceled' => 'bg-red-50 text-red-700 border-red-200',
    ];

    $totalOrders = $statusCounts->sum();
    $visibleFrom = $orders->firstItem() ?? 0;
    $visibleTo = $orders->lastItem() ?? 0;
@endphp

<div class="space-y-6">
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl px-4 py-3 text-sm shadow-sm flex items-center gap-2">
            <i class="ri-check-line text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl px-4 py-3 text-sm shadow-sm flex items-center gap-2">
            <i class="ri-error-warning-line text-lg"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-medium border border-indigo-100 mb-3">
                <i class="ri-shield-user-line"></i>
                Панель администратора
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Заказы</h1>
            <p class="text-sm text-gray-500 mt-1">Управление заказами покупателей по всем продавцам.</p>
        </div>

        <form method="GET" action="{{ route('admin.orders.index') }}" class="flex flex-col sm:flex-row gap-2 w-full xl:w-auto">
            @if($currentStatus)
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif
            <div class="relative flex-1 xl:w-80">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="search" name="q" value="{{ $search }}" placeholder="ID, номер, покупатель, продавец"
                       class="w-full pl-10 pr-4 py-2.5 text-sm border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition">
            </div>
            <button class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-xl hover:bg-indigo-700 transition">
                <i class="ri-search-line"></i>
                Найти
            </button>
            @if($search || $currentStatus)
                <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white text-gray-600 border border-gray-300 text-sm font-medium rounded-xl hover:bg-gray-50 transition">
                    <i class="ri-close-line"></i>
                    Сбросить
                </a>
            @endif
        </form>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-gray-400">Всего</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $totalOrders }}</div>
        </div>
        <div class="bg-white border border-amber-200 rounded-2xl p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-amber-500">Ожидают</div>
            <div class="mt-1 text-2xl font-bold text-amber-700">{{ $statusCounts[\App\Models\Order::STATUS_PENDING] ?? 0 }}</div>
        </div>
        <div class="bg-white border border-emerald-200 rounded-2xl p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-emerald-500">Оплачены</div>
            <div class="mt-1 text-2xl font-bold text-emerald-700">{{ $statusCounts[\App\Models\Order::STATUS_PAID] ?? 0 }}</div>
        </div>
        <div class="bg-white border border-red-200 rounded-2xl p-4 shadow-sm">
            <div class="text-xs uppercase tracking-wide text-red-500">Отменены</div>
            <div class="mt-1 text-2xl font-bold text-red-700">{{ $statusCounts[\App\Models\Order::STATUS_CANCELED] ?? 0 }}</div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm px-3 py-2 overflow-x-auto">
        <div class="flex items-center gap-2 text-sm">
            @foreach($tabs as $key => $label)
                @php
                    $isActive = ($currentStatus === null && $key === null) || ($currentStatus !== null && (string) $currentStatus === (string) $key);
                    $count = $key === null ? $totalOrders : ($statusCounts[$key] ?? 0);
                @endphp
                <a href="{{ $key === null ? route('admin.orders.index', array_filter(['q' => $search])) : route('admin.orders.index', array_filter(['status' => $key, 'q' => $search])) }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border whitespace-nowrap transition {{ $isActive ? 'bg-indigo-50 border-indigo-500 text-indigo-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                    <span class="text-xs font-medium uppercase tracking-wide">{{ $label }}</span>
                    <span class="text-[11px] px-1.5 py-0.5 rounded-full {{ $isActive ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500' }}">{{ $count }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="flex items-center justify-between text-sm text-gray-500">
        <div>
            @if($orders->count())
                Показано {{ $visibleFrom }}-{{ $visibleTo }} из {{ $orders->total() }}
            @else
                Нет заказов по текущим фильтрам
            @endif
        </div>
    </div>

    <div class="hidden lg:block bg-white border border-gray-100 rounded-2xl shadow-sm overflow-x-auto">
        <table class="min-w-full text-sm text-gray-800">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500 border-b">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Заказ</th>
                    <th class="px-4 py-3 text-left font-semibold">Участники</th>
                    <th class="px-4 py-3 text-left font-semibold">Детали</th>
                    <th class="px-4 py-3 text-left font-semibold">Сумма</th>
                    <th class="px-4 py-3 text-left font-semibold">Статус</th>
                    <th class="px-4 py-3 text-right font-semibold">Действие</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $order)
                    @php
                        $itemsCount = $order->items->sum('quantity');
                        $colorClass = $statusColors[$order->status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
                    @endphp
                    <tr class="hover:bg-indigo-50/30 transition">
                        <td class="px-4 py-4 align-top">
                            <div class="font-semibold text-gray-900">#{{ $order->number }}</div>
                            <div class="text-xs text-gray-400 mt-1">ID: {{ $order->id }}</div>
                            <div class="text-xs text-gray-400">{{ $order->created_at?->format('d.m.Y H:i') }}</div>
                        </td>
                        <td class="px-4 py-4 align-top space-y-2">
                            <div>
                                <div class="text-xs text-gray-400">Покупатель</div>
                                <div class="font-medium text-gray-800">{{ $order->user?->name ?? '—' }}</div>
                                <div class="text-xs text-gray-400">{{ $order->user?->email ?? '' }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400">Продавец</div>
                                <div class="font-medium text-gray-800">{{ $order->seller?->name ?? '—' }}</div>
                            </div>
                        </td>
                        <td class="px-4 py-4 align-top text-gray-600">
                            <div>{{ $itemsCount }} товар(ов)</div>
                            <div class="text-xs text-gray-400 mt-1">{{ $order->payment_method ?? '—' }} / {{ $order->delivery_method ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-4 align-top font-semibold whitespace-nowrap">
                            {{ number_format($order->total_price, 2, ',', ' ') }} {{ $order->currency }}
                        </td>
                        <td class="px-4 py-4 align-top">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border {{ $colorClass }}">
                                {{ $order->status_ru }}
                            </span>
                        </td>
                        <td class="px-4 py-4 align-top text-right">
                            <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" class="inline-flex items-center gap-2">
                                @csrf
                                <select name="status" class="text-xs border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach(\App\Models\Order::allStatuses() as $status)
                                        <option value="{{ $status }}" @selected($order->status === $status)>{{ $tabs[$status] ?? $status }}</option>
                                    @endforeach
                                </select>
                                <button class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-xs font-medium hover:bg-indigo-700 transition">Сохранить</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-500 text-sm">Заказов пока нет.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="lg:hidden space-y-3">
        @forelse($orders as $order)
            @php
                $itemsCount = $order->items->sum('quantity');
                $colorClass = $statusColors[$order->status] ?? 'bg-gray-50 text-gray-700 border-gray-200';
            @endphp
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-semibold text-gray-900">#{{ $order->number }}</div>
                        <div class="text-xs text-gray-400">ID: {{ $order->id }} • {{ $order->created_at?->format('d.m.Y H:i') }}</div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border {{ $colorClass }}">{{ $order->status_ru }}</span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <div class="text-xs text-gray-400">Покупатель</div>
                        <div class="font-medium text-gray-800">{{ $order->user?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400">Продавец</div>
                        <div class="font-medium text-gray-800">{{ $order->seller?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400">Товары</div>
                        <div class="font-medium text-gray-800">{{ $itemsCount }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400">Сумма</div>
                        <div class="font-semibold text-gray-900">{{ number_format($order->total_price, 2, ',', ' ') }} {{ $order->currency }}</div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" class="flex gap-2">
                    @csrf
                    <select name="status" class="flex-1 text-sm border-gray-300 rounded-xl focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach(\App\Models\Order::allStatuses() as $status)
                            <option value="{{ $status }}" @selected($order->status === $status)>{{ $tabs[$status] ?? $status }}</option>
                        @endforeach
                    </select>
                    <button class="px-3 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition">OK</button>
                </form>
            </div>
        @empty
            <div class="bg-white rounded-2xl p-8 text-center text-gray-500 text-sm border border-gray-200">Заказов пока нет.</div>
        @endforelse
    </div>

    @if($orders->hasPages())
        <div>{{ $orders->links() }}</div>
    @endif
</div>
@endsection
