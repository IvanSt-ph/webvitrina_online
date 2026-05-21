<x-buyer-layout title="Мои заказы">
    @php
        $tabs = [
            'active' => [
                'label' => 'Активные',
                'count' => $statusCounts->except([\App\Models\Order::STATUS_COMPLETED, \App\Models\Order::STATUS_CANCELED])->sum(),
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
            <header class="grid w-full min-w-0 gap-4 sm:flex sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                        <i class="ri-shopping-bag-3-line"></i>
                        Покупки
                    </div>
                    <h1 class="mt-3 text-2xl font-bold text-slate-950 sm:text-3xl">Мои заказы</h1>
                    <p class="mt-1 max-w-2xl text-sm text-slate-500">
                        Статусы, состав заказа и быстрый переход к деталям в одном списке.
                    </p>
                </div>

                @if($orders->count())
                    <div class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 sm:w-auto">
                        Показано <span class="font-semibold text-slate-900">{{ $orders->firstItem() }}-{{ $orders->lastItem() }}</span>
                        из <span class="font-semibold text-slate-900">{{ $orders->total() }}</span>
                    </div>
                @endif
            </header>

            <nav class="w-full overflow-hidden border-b border-slate-200">
                <div class="grid w-full min-w-0 grid-cols-3 gap-1 sm:flex sm:gap-2">
                    @foreach($tabs as $key => $item)
                        <a href="{{ route('orders.index', ['tab' => $key]) }}"
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
                        $shortTitle = $firstTitle ? \Illuminate\Support\Str::limit($firstTitle, 14) : null;
                    @endphp

                    <article class="w-full max-w-full overflow-hidden rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-4">
                        <div class="grid min-w-0 gap-3 sm:gap-4 lg:grid-cols-[1fr_220px] lg:items-start">
                            <div class="min-w-0">
                                <div class="grid min-w-0 gap-2 sm:flex sm:flex-wrap sm:items-center">
                                    <h2 class="max-w-full truncate text-base font-bold text-slate-950">Заказ {{ $order->number }}</h2>
                                    <x-status-badge :status="$order->status" class="max-w-full justify-center truncate px-2 sm:px-3" />
                                </div>
                                <div class="mt-1 text-sm text-slate-500">
                                    {{ $order->created_at->format('d.m.Y · H:i') }}
                                </div>
                            </div>

                            <div class="grid min-w-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-3 lg:block lg:text-right">
                                <div class="min-w-0 truncate text-lg font-bold text-slate-950">
                                    {{ number_format($order->total_price, 2, ',', ' ') }} {{ $order->currency }}
                                </div>
                                <a href="{{ route('orders.show', $order) }}"
                                   class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                    <i class="ri-eye-line"></i>
                                    <span class="hidden sm:inline">Подробнее</span>
                                </a>
                            </div>
                        </div>

                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <div class="flex min-w-0 items-center gap-3 overflow-hidden">
                                @if($firstItem?->product)
                                    <img src="{{ $firstItem->product->image_thumb_url }}"
                                         alt="{{ $firstItem->product->title }}"
                                         class="h-14 w-14 shrink-0 rounded-lg border border-slate-200 object-cover sm:h-16 sm:w-16">
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="sm:hidden max-w-full truncate text-sm font-semibold leading-5 text-slate-900"
                                            title="{{ $firstItem->product->title }}"
                                        >
                                            {{ $shortTitle }}
                                        </div>
                                        <div class="hidden sm:block">
                                            <div
                                                class="max-w-full overflow-hidden text-sm font-semibold leading-5 text-slate-900"
                                                style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow-wrap:anywhere;word-break:break-word;"
                                                title="{{ $firstItem->product->title }}"
                                            >
                                                {{ $firstItem->product->title }}
                                            </div>
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $itemsCount }} шт.
                                            @if($order->items->count() > 1)
                                                · {{ $order->items->count() }} позиции
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-100 text-slate-400 sm:h-16 sm:w-16">
                                        <i class="ri-image-off-line text-2xl"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-semibold text-slate-500">Товар был удалён продавцом</div>
                                        <div class="mt-1 text-xs text-slate-400">{{ $itemsCount }} шт.</div>
                                    </div>
                                @endif

                                @if($order->items->count() > 1)
                                    <div class="hidden rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 sm:block">
                                        +{{ $order->items->count() - 1 }}
                                    </div>
                                @endif
                            </div>
                        </div>
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
