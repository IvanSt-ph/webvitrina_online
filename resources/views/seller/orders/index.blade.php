{{-- resources/views/seller/orders/index.blade.php --}}
<x-seller-layout title="Заказы">
    @php
        /** @var \Illuminate\Pagination\LengthAwarePaginator|\App\Models\Order[] $orders */

        $status = request('status');

        $tabs = [
            null                         => ['label' => 'Все',              'color' => 'border-indigo-500 text-indigo-600'],
            \App\Models\Order::STATUS_PENDING    => ['label' => 'Ожидают',        'color' => 'border-amber-400 text-amber-500'],
            \App\Models\Order::STATUS_PROCESSING => ['label' => 'Приняты',        'color' => 'border-sky-400 text-sky-500'],
            \App\Models\Order::STATUS_PAID       => ['label' => 'Оплачены',       'color' => 'border-emerald-400 text-emerald-500'],
            \App\Models\Order::STATUS_SHIPPED    => ['label' => 'В пути',         'color' => 'border-blue-400 text-blue-500'],
            \App\Models\Order::STATUS_DELIVERED  => ['label' => 'Доставлены',     'color' => 'border-green-400 text-green-500'],
            \App\Models\Order::STATUS_COMPLETED  => ['label' => 'Завершены',      'color' => 'border-gray-400 text-gray-600'],
            \App\Models\Order::STATUS_CANCELED   => ['label' => 'Отменённые',     'color' => 'border-red-400 text-red-500'],
        ];

        $statusColors = [
            'pending'    => 'bg-amber-50 text-amber-700 border border-amber-200',
            'processing' => 'bg-sky-50 text-sky-700 border border-sky-200',
            'paid'       => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'shipped'    => 'bg-blue-50 text-blue-700 border border-blue-200',
            'delivered'  => 'bg-green-50 text-green-700 border border-green-200',
            'completed'  => 'bg-slate-50 text-slate-700 border border-slate-200',
            'canceled'   => 'bg-red-50 text-red-700 border border-red-200',
        ];
    @endphp

    <div class="space-y-6">

        {{-- Заголовок --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
                    Мои заказы
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Здесь отображаются все заказы, в которых вы являетесь продавцом.
                </p>
            </div>

            @if($orders->count())
                <div class="text-sm text-gray-500">
                    Показано {{ $orders->firstItem() }}–{{ $orders->lastItem() }}
                    из {{ $orders->total() }}
                </div>
            @endif
        </div>

        {{-- Табы статусов --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm px-3 py-2 overflow-x-auto">
            <div class="flex items-center gap-2 text-sm">
                @foreach($tabs as $key => $tab)
                    @php
                        $isActive = ($status === null && $key === null) || ($status !== null && (string)$status === (string)$key);
                    @endphp

                    <a
                        href="{{ $key === null ? route('seller.orders.index') : route('seller.orders.index', ['status' => $key]) }}"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border
                               whitespace-nowrap transition-all duration-150
                               {{ $isActive ? $tab['color'] . ' bg-indigo-50/40 shadow-sm' : 'border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50' }}"
                    >
                        <span class="text-xs font-medium uppercase tracking-wide">
                            {{ $tab['label'] }}
                        </span>
                        @if($isActive)
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Список заказов --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
            @forelse($orders as $order)
                @php
                    $itemsCount = $order->items->sum('quantity');
                    $colorClass = $statusColors[$order->status] ?? 'bg-gray-50 text-gray-700 border border-gray-200';
                @endphp

                <a href="{{ route('seller.orders.show', $order) }}"
                   class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4
                          hover:bg-gray-50/80 transition-colors border-b last:border-b-0 border-gray-100">
                    {{-- Левая часть --}}
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <div class="font-semibold text-gray-900">
                                #{{ $order->number }}
                            </div>
                            <span class="text-xs text-gray-400">
                                ID: {{ $order->id }}
                            </span>
                        </div>

                        <div class="text-sm text-gray-500">
                            Покупатель {{ $order->user->name ?? 'Неизвестен' }}
                            • {{ $order->created_at?->format('d.m.Y H:i') }}
                        </div>

                        <div class="text-xs text-gray-400">
                            Товаров: {{ $itemsCount }}
                        </div>
                    </div>

                    {{-- Правая часть --}}
                    <div class="flex flex-col items-end gap-2">
                        <div class="text-base font-semibold text-gray-900">
                            {{ $order->formatted_total_price ?? (number_format($order->total_price, 2, ',', ' ') . ' ' . ($order->currency ?? '')) }}
                        </div>

                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $colorClass }}">
                            {{ $order->status_ru }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="px-6 py-10 text-center text-gray-500 text-sm">
                    Пока нет заказов по вашим товарам.
                </div>
            @endforelse
        </div>

        {{-- Пагинация --}}
        @if($orders->hasPages())
            <div>
                {{ $orders->withQueryString()->links() }}
            </div>
        @endif

    </div>
</x-seller-layout>
