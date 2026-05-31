@props(['order', 'compact' => false])

@php
    $statusMeta = [
        \App\Models\Order::STATUS_PENDING => ['label' => 'Ожидает обработки', 'icon' => 'ri-time-line'],
        \App\Models\Order::STATUS_PROCESSING => ['label' => 'Принят продавцом', 'icon' => 'ri-user-follow-line'],
        \App\Models\Order::STATUS_PAID => ['label' => 'Оплачен', 'icon' => 'ri-bank-card-line'],
        \App\Models\Order::STATUS_SHIPPED => ['label' => 'В пути', 'icon' => 'ri-truck-line'],
        \App\Models\Order::STATUS_DELIVERED => ['label' => 'Доставлен', 'icon' => 'ri-checkbox-circle-line'],
        \App\Models\Order::STATUS_COMPLETED => ['label' => 'Завершён', 'icon' => 'ri-check-double-line'],
        \App\Models\Order::STATUS_CANCELED => ['label' => 'Отменён', 'icon' => 'ri-close-circle-line'],
    ];

    $events = collect([
        ['label' => 'Заказ создан', 'at' => $order->created_at, 'icon' => 'ri-add-circle-line', 'tone' => 'indigo'],
        ['label' => 'Покупатель запросил отмену', 'at' => $order->cancellation_requested_at, 'icon' => 'ri-error-warning-line', 'tone' => 'rose', 'description' => $order->cancellation_reason],
        ['label' => 'Принят продавцом', 'at' => $order->accepted_at, 'icon' => 'ri-user-follow-line', 'tone' => 'sky'],
        ['label' => 'Оплачен', 'at' => $order->paid_at, 'icon' => 'ri-bank-card-line', 'tone' => 'emerald'],
        ['label' => 'Передан в доставку', 'at' => $order->shipped_at, 'icon' => 'ri-truck-line', 'tone' => 'blue'],
        ['label' => 'Доставлен', 'at' => $order->delivered_at, 'icon' => 'ri-checkbox-circle-line', 'tone' => 'green'],
        ['label' => 'Отменён', 'at' => $order->canceled_at, 'icon' => 'ri-close-circle-line', 'tone' => 'rose'],
    ])->filter(fn ($event) => $event['at'])->sortBy('at')->values();

    if ($order->status === \App\Models\Order::STATUS_COMPLETED && ! $events->contains('label', 'Завершён')) {
        $events->push([
            'label' => 'Завершён',
            'at' => $order->updated_at,
            'icon' => 'ri-check-double-line',
            'tone' => 'slate',
        ]);
    }

    $current = $statusMeta[$order->status] ?? ['label' => $order->status, 'icon' => 'ri-information-line'];
    $toneClass = fn ($tone) => match ($tone) {
        'rose' => 'bg-rose-50 text-rose-600 border-rose-100',
        'sky' => 'bg-sky-50 text-sky-600 border-sky-100',
        'emerald' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
        'blue' => 'bg-blue-50 text-blue-600 border-blue-100',
        'green' => 'bg-green-50 text-green-600 border-green-100',
        default => 'bg-indigo-50 text-indigo-600 border-indigo-100',
    };
@endphp

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-slate-200 bg-white shadow-sm ' . ($compact ? 'p-4' : 'p-4 sm:p-5')]) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <h2 class="font-bold text-slate-900">Ход заказа</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $compact ? 'Ключевые события по заказу.' : 'Важные события по заказу в одном месте.' }}</p>
        </div>
        <span class="inline-flex shrink-0 items-center gap-1 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">
            <i class="{{ $current['icon'] }}"></i>
            {{ $current['label'] }}
        </span>
    </div>

    <div class="{{ $compact ? 'mt-4 grid gap-2 sm:grid-cols-2' : 'mt-4 space-y-3' }}">
        @forelse($events as $event)
            <div class="flex gap-3 rounded-xl {{ $compact ? 'bg-slate-50 px-3 py-2' : '' }} text-sm">
                <div class="flex {{ $compact ? 'h-7 w-7' : 'h-8 w-8' }} shrink-0 items-center justify-center rounded-full border {{ $toneClass($event['tone'] ?? 'indigo') }}">
                    <i class="{{ $event['icon'] }}"></i>
                </div>
                <div class="min-w-0">
                    <div class="font-semibold leading-5 text-slate-800">{{ $event['label'] }}</div>
                    <div class="text-xs text-slate-500">{{ $event['at']->format('d.m.Y H:i') }}</div>
                    @if(!empty($event['description']))
                        <div class="mt-1 break-words rounded-lg bg-slate-50 px-2 py-1 text-xs text-slate-600">{{ $event['description'] }}</div>
                    @endif
                </div>
            </div>
        @empty
            <p class="rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-500">Событий пока нет.</p>
        @endforelse
    </div>
</section>
