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

    <div class="orders-mobile-safe min-h-screen w-full max-w-full overflow-x-hidden bg-white px-4 py-5 pb-24 text-neutral-900 sm:px-6 sm:py-7 sm:pb-24 lg:px-8 lg:pb-8" style="max-width:100vw;">
        <div class="w-full max-w-none space-y-5 overflow-hidden sm:space-y-6">
            <header class="flex w-full min-w-0 flex-col gap-4 border-b border-neutral-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                        <i class="ri-shopping-bag-3-line"></i>
                        Заказы
                    </span>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-neutral-900 sm:text-[28px]">Мои заказы</h1>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-neutral-500">Следите за статусами покупок и выполняйте необходимые действия.</p>
                </div>

                @if($orders->count())
                    <div class="flex w-full items-center gap-3 rounded-xl border border-brand-100 bg-brand-50 px-4 py-2.5 sm:w-auto">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-brand-600 shadow-sm"><i class="ri-file-list-3-line"></i></div>
                        <div class="text-sm text-brand-700">
                            Показано <span class="font-semibold text-brand-900">{{ $orders->firstItem() }}–{{ $orders->lastItem() }}</span>
                            из <span class="font-semibold text-brand-900">{{ $orders->total() }}</span>
                        </div>
                    </div>
                @endif
            </header>

            <form method="GET" action="{{ route('orders.index') }}" class="flex w-full flex-col gap-2 rounded-2xl border border-neutral-200 bg-neutral-50/70 p-3 sm:flex-row">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <label class="relative min-w-0 flex-1">
                    <i class="ri-search-line absolute left-3.5 top-1/2 -translate-y-1/2 text-neutral-400"></i>
                    <input type="search"
                           name="q"
                           value="{{ $search }}"
                           placeholder="Номер заказа, товар или магазин"
                           class="h-11 w-full rounded-xl border-neutral-200 bg-white pl-10 pr-3 text-sm text-neutral-800 shadow-sm placeholder:text-neutral-400 focus:border-brand-300 focus:ring-4 focus:ring-brand-100">
                </label>
                <x-action-button>
                    <i class="ri-search-line"></i>
                    Найти
                </x-action-button>
                @if($search !== '')
                    <x-secondary-action as="a" href="{{ route('orders.index', ['tab' => $tab]) }}">Сбросить</x-secondary-action>
                @endif
            </form>

            <nav class="w-full overflow-x-auto border-b border-neutral-200" aria-label="Разделы заказов">
                <div class="flex w-max min-w-full gap-2">
                    @foreach($tabs as $key => $item)
                        <a href="{{ route('orders.index', ['tab' => $key, 'q' => $search ?: null]) }}"
                           class="wv-ui-tab inline-flex min-h-11 shrink-0 items-center justify-start gap-2 whitespace-nowrap px-3 py-3 text-center text-sm"
                           @if($tab === $key) aria-current="page" @endif>
                            <span>{{ $item['label'] }}</span>
                            <span class="shrink-0 rounded-full {{ $tab === $key ? 'bg-brand-50 text-brand-700' : 'bg-neutral-100 text-neutral-500' }} px-1.5 py-0.5 text-[11px] sm:px-2 sm:text-xs">
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

                    <article class="group w-full max-w-full overflow-hidden rounded-2xl border border-neutral-200 bg-white p-3 transition duration-300 hover:border-brand-200 hover:shadow-xl hover:shadow-brand-100/40 sm:p-4 lg:p-5">
                        <div class="grid min-w-0 grid-cols-[64px_minmax(0,1fr)] gap-3 lg:grid-cols-[72px_minmax(0,1fr)_220px] lg:items-center">
                            <div class="h-16 w-16 overflow-hidden rounded-xl border border-neutral-200 bg-neutral-50 lg:h-[72px] lg:w-[72px]">
                                @if($firstItem?->product)
                                    <img data-image-candidates="{{ json_encode($firstItem->product->image_thumb_candidates) }}" data-image-fallback="{{ asset('images/image-placeholder.svg') }}" src="{{ $firstItem->product->image_thumb_url }}"
                                         alt="{{ $firstItem->product->title }}"
                                         class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <div class="flex h-full w-full items-center justify-center text-neutral-400">
                                        <i class="ri-image-off-line text-2xl"></i>
                                    </div>
                                @endif
                            </div>

                            <div class="min-w-0">
                                <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                                    <h2 class="max-w-full truncate text-sm font-semibold text-neutral-900 sm:text-base">Заказ {{ $order->number }}</h2>
                                    <span class="text-xs font-medium text-neutral-400">Статус:</span>
                                    <x-status-badge :status="$order->status" class="max-w-full justify-center truncate px-2 py-0.5 text-xs" />
                                </div>

                                @if($firstItem?->product)
                                    <div class="mt-1 truncate text-sm font-semibold text-neutral-800" title="{{ $firstItem->product->title }}">
                                        {{ $firstItem->product->title }}
                                    </div>
                                @else
                                    <div class="mt-1 text-sm font-semibold text-neutral-500">Товар был удалён продавцом</div>
                                @endif

                                <div class="mt-1 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-xs text-neutral-500">
                                    <span>{{ $order->created_at->format('d.m.Y · H:i') }}</span>
                                    @if($shopName)
                                        <span class="hidden text-neutral-300 sm:inline">•</span>
                                        <span class="truncate font-medium text-brand-600">{{ $shopName }}</span>
                                    @endif
                                    <span class="hidden text-neutral-300 sm:inline">•</span>
                                    <span>{{ $itemsCount }} шт.@if($order->items->count() > 1), {{ $order->items->count() }} позиции@endif</span>
                                </div>
                            </div>

                            <div class="col-span-2 grid min-w-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-3 border-t border-neutral-100 pt-3 lg:col-span-1 lg:block lg:border-t-0 lg:pt-0 lg:text-right">
                                <div class="min-w-0 truncate text-lg font-bold text-neutral-900">
                                    {{ number_format($order->total_price, 2, ',', ' ') }} {{ $order->currency }}
                                </div>
                                <a href="{{ route('orders.show', $order) }}" aria-label="Подробнее о заказе {{ $order->number }}"
                                   class="inline-flex h-11 min-w-11 shrink-0 items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 text-sm font-semibold text-neutral-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 sm:h-10 sm:min-w-0 lg:mt-2">
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
                        class="py-14 sm:py-16"
                    >
                        <x-action-button as="a" :href="route('home')">
                            <i class="ri-store-3-line"></i>
                            Перейти к покупкам
                        </x-action-button>
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
