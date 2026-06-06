<x-buyer-layout title="Мои заказы">
    @php
        $tabs = [
            'active' => [
                'label' => 'Активные',
                'count' => $statusCounts->except([\App\Models\Order::STATUS_COMPLETED, \App\Models\Order::STATUS_CANCELED])->sum(),
            ],
            'action' => [
                'label' => 'Мои действия',
                'count' => $actionCount ?? 0,
            ],
            'completed' => [
                'label' => 'Завершённые',
                'count' => $statusCounts[\App\Models\Order::STATUS_COMPLETED] ?? 0,
            ],
            'canceled' => [
                'label' => 'Отменённые',
                'count' => $statusCounts[\App\Models\Order::STATUS_CANCELED] ?? 0,
            ],
        ];

        $steps = [
            \App\Models\Order::STATUS_PENDING => 1,
            \App\Models\Order::STATUS_PROCESSING => 2,
            \App\Models\Order::STATUS_PAID => 3,
            \App\Models\Order::STATUS_SHIPPED => 4,
            \App\Models\Order::STATUS_DELIVERED => 5,
            \App\Models\Order::STATUS_COMPLETED => 6,
        ];

        $stepLabels = [
            1 => 'Новый',
            2 => 'Принят',
            3 => 'Оплачен',
            4 => 'В пути',
            5 => 'Доставлен',
            6 => 'Завершён',
        ];
    @endphp

    <div class="orders-mobile-safe min-h-screen w-full max-w-full overflow-x-hidden bg-white px-3 py-4 pb-[5.5rem] text-slate-900 sm:px-5 sm:py-6 lg:px-6" style="max-width:100vw;">
        <div class="w-full max-w-none space-y-5 overflow-hidden">
            <header class="grid w-full min-w-0 gap-3 sm:flex sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h1 class="text-2xl font-bold text-slate-950 sm:text-3xl">Мои заказы</h1>
                    <p class="mt-1 max-w-2xl text-sm text-slate-500">Покупки, статусы и действия по заказам.</p>
                </div>

                @if($orders->count())
                    <div class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 sm:w-auto">
                        Показано <span class="font-semibold text-slate-900">{{ $orders->firstItem() }}-{{ $orders->lastItem() }}</span>
                        из <span class="font-semibold text-slate-900">{{ $orders->total() }}</span>
                    </div>
                @endif
            </header>

            <form method="GET" action="{{ route('orders.index') }}" class="flex w-full flex-col gap-2 rounded-xl border border-slate-200 bg-white p-3 sm:flex-row">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <label class="relative min-w-0 flex-1">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="search"
                           name="q"
                           value="{{ $search }}"
                           placeholder="Номер заказа, товар или магазин"
                           class="h-11 w-full rounded-lg border-slate-200 pl-10 pr-3 text-sm focus:border-indigo-300 focus:ring-indigo-100">
                </label>
                <button class="h-11 rounded-lg bg-indigo-600 px-5 text-sm font-semibold text-white transition hover:bg-indigo-700">Найти</button>
                @if($search !== '')
                    <a href="{{ route('orders.index', ['tab' => $tab]) }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-slate-200 px-4 text-sm font-semibold text-slate-600">Сбросить</a>
                @endif
            </form>

            <nav class="w-full overflow-hidden border-b border-slate-200">
                <div class="grid w-full min-w-0 grid-cols-4 gap-1 sm:flex sm:gap-2">
                    @foreach($tabs as $key => $item)
                        <a href="{{ route('orders.index', ['tab' => $key, 'q' => $search ?: null]) }}"
                           class="flex min-w-0 items-center justify-center gap-1 border-b-2 px-1 py-3 text-center text-xs font-semibold transition sm:inline-flex sm:justify-start sm:gap-2 sm:px-3 sm:text-sm {{ $tab === $key ? 'border-indigo-600 text-indigo-700' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                            <span class="min-w-0 truncate">{{ $item['label'] }}</span>
                            <span class="shrink-0 rounded-full {{ $tab === $key ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-500' }} px-1.5 py-0.5 text-[11px] sm:px-2 sm:text-xs">
                                {{ $item['count'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </nav>

            <div class="w-full max-w-full space-y-3 overflow-hidden">
                @forelse($orders as $order)
                    @php
                        $activeStep = $order->status === \App\Models\Order::STATUS_CANCELED ? 0 : ($steps[$order->status] ?? 1);
                        $firstItem = $order->items->first();
                        $itemsCount = $order->items->sum('quantity');
                        $firstTitle = $firstItem?->product?->title;
                        $shopName = $order->seller?->shop?->name ?? $order->seller?->name;
                        $needsConfirmation = $order->status === \App\Models\Order::STATUS_SHIPPED;
                        $needsReview = $tab === 'action' && in_array($order->status, [\App\Models\Order::STATUS_DELIVERED, \App\Models\Order::STATUS_COMPLETED], true);
                    @endphp

                    <article class="w-full max-w-full overflow-hidden rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-indigo-100 hover:shadow-md sm:p-4">
                        <div class="grid min-w-0 grid-cols-[64px_minmax(0,1fr)] gap-3 lg:grid-cols-[72px_minmax(0,1fr)_220px] lg:items-center">
                            <div class="h-16 w-16 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 lg:h-[72px] lg:w-[72px]">
                                @if($firstItem?->product)
                                    <img src="{{ $firstItem->product->image_thumb_url }}"
                                         alt="{{ $firstItem->product->title }}"
                                         class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-slate-400">
                                        <i class="ri-image-off-line text-2xl"></i>
                                    </div>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                                    <h2 class="max-w-full truncate text-sm font-bold text-slate-950 sm:text-base">Заказ {{ $order->number }}</h2>
                                    <span class="text-xs font-semibold text-slate-400">Статус:</span>
                                    <x-status-badge :status="$order->status" class="max-w-full justify-center truncate px-2 py-0.5 text-xs" />
                                </div>

                                @if($firstItem?->product)
                                    <div class="mt-1 truncate text-sm font-semibold text-slate-800" title="{{ $firstItem->product->title }}">
                                        {{ $firstItem->product->title }}
                                    </div>
                                @else
                                    <div class="mt-1 text-sm font-semibold text-slate-500">Товар был удалён продавцом</div>
                                @endif

                                <div class="mt-1 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                                    <span>{{ $order->created_at->format('d.m.Y · H:i') }}</span>
                                    @if($shopName)
                                        <span class="hidden text-slate-300 sm:inline">•</span>
                                        <span class="truncate font-medium text-indigo-600">{{ $shopName }}</span>
                                    @endif
                                    <span class="hidden text-slate-300 sm:inline">•</span>
                                    <span>{{ $itemsCount }} шт.@if($order->items->count() > 1), {{ $order->items->count() }} позиции@endif</span>
                                </div>
                            </div>

                            <div class="col-span-2 grid min-w-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-3 border-t border-slate-100 pt-3 lg:col-span-1 lg:block lg:border-t-0 lg:pt-0 lg:text-right">
                                <div class="min-w-0 truncate text-base font-bold text-slate-950 lg:text-lg">
                                    {{ number_format($order->total_price, 2, ',', ' ') }} {{ $order->currency }}
                                </div>
                                <a href="{{ route('orders.show', $order) }}"
                                   class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                    <i class="ri-eye-line"></i>
                                    <span class="hidden sm:inline">Подробнее</span>
                                </a>
                            </div>
                        </div>

                        @if($needsConfirmation || $needsReview)
                            <div class="mt-3 flex items-center justify-between gap-3 rounded-xl {{ $needsConfirmation ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }} px-3 py-2 text-sm font-semibold">
                                <span>{{ $needsConfirmation ? 'Подтвердите получение товара' : 'Можно оставить отзыв о покупке' }}</span>
                                <a href="{{ route('orders.show', $order) }}" class="shrink-0 underline">Перейти</a>
                            </div>
                        @endif
                    </article>
                @empty
                    <x-empty-state
                        icon="ri-shopping-bag-3-line"
                        title="Заказов здесь пока нет"
                        description="Когда появятся покупки с выбранным статусом, они будут показаны в этом разделе."
                        class="py-16 sm:py-20"
                    >
                        <a href="{{ route('home') }}"
                           class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <i class="ri-store-3-line"></i>
                            Перейти к покупкам
                        </a>
                    </x-empty-state>
                @endforelse
            </div>

            @if($orders->hasPages())
                <div>
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>

    <style>
        @media (max-width: 767px) {
            .orders-mobile-safe,
            .orders-mobile-safe * {
                box-sizing: border-box;
            }

            .orders-mobile-safe {
                inline-size: 100%;
                max-inline-size: 100vw;
            }
        }
    </style>
</x-buyer-layout>
