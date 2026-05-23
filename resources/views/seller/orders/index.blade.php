{{-- resources/views/seller/orders/index.blade.php --}}
<x-seller-layout title="Заказы">
    @php
        $tabs = [
            null => ['label' => 'Все', 'icon' => 'ri-inbox-line'],
            \App\Models\Order::STATUS_PENDING => ['label' => 'Ожидают', 'icon' => 'ri-time-line'],
            \App\Models\Order::STATUS_PROCESSING => ['label' => 'Приняты', 'icon' => 'ri-user-follow-line'],
            \App\Models\Order::STATUS_PAID => ['label' => 'Оплачены', 'icon' => 'ri-bank-card-line'],
            \App\Models\Order::STATUS_SHIPPED => ['label' => 'В пути', 'icon' => 'ri-truck-line'],
            \App\Models\Order::STATUS_DELIVERED => ['label' => 'Доставлены', 'icon' => 'ri-checkbox-circle-line'],
            \App\Models\Order::STATUS_COMPLETED => ['label' => 'Завершены', 'icon' => 'ri-check-double-line'],
            \App\Models\Order::STATUS_CANCELED => ['label' => 'Отменённые', 'icon' => 'ri-close-circle-line'],
        ];

        $statusColors = [
            \App\Models\Order::STATUS_PENDING => 'border-amber-200 bg-amber-50 text-amber-700',
            \App\Models\Order::STATUS_PROCESSING => 'border-sky-200 bg-sky-50 text-sky-700',
            \App\Models\Order::STATUS_PAID => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            \App\Models\Order::STATUS_SHIPPED => 'border-blue-200 bg-blue-50 text-blue-700',
            \App\Models\Order::STATUS_DELIVERED => 'border-green-200 bg-green-50 text-green-700',
            \App\Models\Order::STATUS_COMPLETED => 'border-slate-200 bg-slate-50 text-slate-700',
            \App\Models\Order::STATUS_CANCELED => 'border-rose-200 bg-rose-50 text-rose-700',
        ];

        $totalOrders = $statusCounts->sum();
        $activeStatus = $status ?? null;
    @endphp

    <div class="min-h-screen bg-white px-3 py-4 pb-[5.5rem] text-slate-900 sm:px-5 sm:py-6 lg:px-6">
        <div class="w-full max-w-none space-y-5">
            <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                        <i class="ri-store-2-line"></i>
                        Кабинет продавца
                    </div>
                    <h1 class="mt-3 text-2xl font-bold text-slate-950 sm:text-3xl">Заказы</h1>
                    <p class="mt-1 max-w-2xl text-sm text-slate-500">
                        Быстрая проверка покупателя, состава заказа и текущего статуса без лишнего перехода глазами по странице.
                    </p>
                </div>

                @if($orders->count())
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        Показано <span class="font-semibold text-slate-900">{{ $orders->firstItem() }}-{{ $orders->lastItem() }}</span>
                        из <span class="font-semibold text-slate-900">{{ $orders->total() }}</span>
                    </div>
                @endif
            </header>

            <section class="hidden gap-3 sm:grid sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <div class="flex items-center justify-between text-sm text-slate-500">
                        <span>Всего</span>
                        <i class="ri-file-list-3-line text-indigo-500"></i>
                    </div>
                    <div class="mt-2 text-2xl font-bold">{{ number_format($totalOrders, 0, ',', ' ') }}</div>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                    <div class="flex items-center justify-between text-sm text-amber-700">
                        <span>Ждут реакции</span>
                        <i class="ri-time-line"></i>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-amber-800">{{ number_format(($statusCounts[\App\Models\Order::STATUS_PENDING] ?? 0) + ($statusCounts[\App\Models\Order::STATUS_PROCESSING] ?? 0), 0, ',', ' ') }}</div>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                    <div class="flex items-center justify-between text-sm text-blue-700">
                        <span>В доставке</span>
                        <i class="ri-truck-line"></i>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-blue-800">{{ number_format($statusCounts[\App\Models\Order::STATUS_SHIPPED] ?? 0, 0, ',', ' ') }}</div>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-center justify-between text-sm text-emerald-700">
                        <span>Завершены</span>
                        <i class="ri-check-double-line"></i>
                    </div>
                    <div class="mt-2 text-2xl font-bold text-emerald-800">{{ number_format($statusCounts[\App\Models\Order::STATUS_COMPLETED] ?? 0, 0, ',', ' ') }}</div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-100 p-3 sm:p-4">
                    <form method="GET" action="{{ route('seller.orders.index') }}" class="grid gap-3 lg:grid-cols-[1fr_260px_auto]">
                        <label class="relative block">
                            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input
                                type="search"
                                name="q"
                                value="{{ $search }}"
                                placeholder="Номер заказа, имя или email покупателя"
                                class="h-11 w-full rounded-lg border border-slate-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                            >
                        </label>

                        <select
                            name="status"
                            class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 pr-9 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                        >
                            <option value="">Все статусы</option>
                            @foreach($tabs as $key => $tab)
                                @continue($key === null)
                                <option value="{{ $key }}" @selected($activeStatus === $key)>{{ $tab['label'] }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <i class="ri-filter-3-line"></i>
                            Применить
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto border-b border-slate-100 px-3 py-2">
                    <div class="flex min-w-max items-center gap-2">
                        @foreach($tabs as $key => $tab)
                            @php
                                $isActive = ($activeStatus === null && $key === null) || ($activeStatus === $key);
                                $count = $key === null ? $totalOrders : ($statusCounts[$key] ?? 0);
                                $href = $key === null
                                    ? route('seller.orders.index', array_filter(['q' => $search]))
                                    : route('seller.orders.index', array_filter(['q' => $search, 'status' => $key]));
                            @endphp
                            <a href="{{ $href }}"
                               class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition {{ $isActive ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}">
                                <i class="{{ $tab['icon'] }}"></i>
                                <span>{{ $tab['label'] }}</span>
                                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold">{{ $count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        @php
                            $itemsCount = $order->items->sum('quantity');
                            $firstItem = $order->items->first();
                            $colorClass = $statusColors[$order->status] ?? 'border-slate-200 bg-slate-50 text-slate-700';
                        @endphp

                        <a href="{{ route('seller.orders.show', $order) }}"
                           class="grid gap-4 px-4 py-4 transition hover:bg-slate-50 lg:grid-cols-[1.1fr_1fr_180px_150px] lg:items-center">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-slate-950">#{{ $order->number }}</span>
                                    <span class="rounded-full border {{ $colorClass }} px-2 py-0.5 text-xs font-medium">{{ $order->status_ru }}</span>
                                </div>
                                <div class="mt-1 text-sm text-slate-500">
                                    {{ $order->created_at?->format('d.m.Y H:i') }} · ID {{ $order->id }}
                                </div>
                            </div>

                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-slate-800">
                                    {{ $order->user->name ?? 'Покупатель не указан' }}
                                </div>
                                <div class="mt-1 truncate text-xs text-slate-500">
                                    {{ $firstItem?->product?->title ?? 'Товар не найден' }}
                                    @if($itemsCount > 1)
                                        · ещё {{ $itemsCount - 1 }}
                                    @endif
                                </div>
                            </div>

                            <div class="text-sm text-slate-600 lg:text-right">
                                <span class="font-semibold text-slate-900">{{ $itemsCount }}</span>
                                {{ trans_choice('товар|товара|товаров', $itemsCount) }}
                            </div>

                            <div class="flex items-center justify-between gap-3 lg:justify-end">
                                <span class="whitespace-nowrap text-base font-bold text-slate-950">
                                    {{ $order->formatted_total_price ?? (number_format($order->total_price, 2, ',', ' ') . ' ' . ($order->currency ?? '')) }}
                                </span>
                                <i class="ri-arrow-right-s-line text-xl text-slate-400"></i>
                            </div>
                        </a>
                    @empty
                        <div class="px-6 py-14 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                                <i class="ri-file-list-3-line"></i>
                            </div>
                            <h2 class="mt-4 text-lg font-semibold text-slate-900">Заказов не найдено</h2>
                            <p class="mt-1 text-sm text-slate-500">Попробуйте снять фильтр или изменить строку поиска.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            @if($orders->hasPages())
                <div>
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>

    @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
