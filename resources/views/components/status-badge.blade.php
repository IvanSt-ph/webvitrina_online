@props([
    'status',
])

@php
    $colors = [
        'pending' => 'border-amber-200 bg-amber-50 text-amber-800',
        'processing' => 'border-sky-200 bg-sky-50 text-sky-800',
        'paid' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'shipped' => 'border-blue-200 bg-blue-50 text-blue-800',
        'delivered' => 'border-green-200 bg-green-50 text-green-800',
        'completed' => 'border-slate-200 bg-slate-50 text-slate-700',
        'canceled' => 'border-rose-200 bg-rose-50 text-rose-800',
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

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold ' . ($colors[$status] ?? 'border-gray-200 bg-gray-100 text-gray-700')]) }}>
    <i class="{{ $icons[$status] ?? 'ri-information-line' }}"></i>
    {{ $labels[$status] ?? 'Неизвестно' }}
</span>
