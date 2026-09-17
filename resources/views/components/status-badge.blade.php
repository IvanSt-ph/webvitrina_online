@props([
    'status',
])

@php
    $colors = [
        'pending' => 'border-warning-200 bg-warning-50 text-warning-800',
        'processing' => 'border-sky-200 bg-sky-50 text-sky-800',
        'paid' => 'border-success-200 bg-success-50 text-success-800',
        'shipped' => 'border-blue-200 bg-blue-50 text-blue-800',
        'delivered' => 'border-green-200 bg-green-50 text-green-800',
        'completed' => 'border-neutral-200 bg-neutral-50 text-neutral-700',
        'canceled' => 'border-danger-200 bg-danger-50 text-danger-800',
    ];

    $labels = [
        'pending' => 'Ожидает обработки',
        'processing' => 'Принят продавцом',
        'paid' => 'Оплачен',
        'shipped' => 'В пути',
        'delivered' => 'Доставлен',
        'completed' => 'Завершён',
        'canceled' => 'Отменён',
    ];

    $icons = [
        'pending' => 'ri-time-line',
        'processing' => 'ri-user-follow-line',
        'paid' => 'ri-bank-card-line',
        'shipped' => 'ri-truck-line',
        'delivered' => 'ri-checkbox-circle-line',
        'completed' => 'ri-check-double-line',
        'canceled' => 'ri-close-circle-line',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold ' . ($colors[$status] ?? 'border-neutral-200 bg-neutral-100 text-neutral-700')]) }}>
    <i class="{{ $icons[$status] ?? 'ri-information-line' }}"></i>
    {{ $labels[$status] ?? 'Неизвестно' }}
</span>
