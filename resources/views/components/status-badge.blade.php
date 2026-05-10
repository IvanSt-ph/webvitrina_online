@props([
    'status',
])

@php
    $colors = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'processing' => 'bg-indigo-100 text-indigo-700',
        'paid' => 'bg-emerald-100 text-emerald-800',
        'shipped' => 'bg-sky-100 text-sky-800',
        'delivered' => 'bg-emerald-100 text-emerald-800',
        'completed' => 'bg-gray-100 text-gray-700',
        'canceled' => 'bg-red-100 text-red-800',
    ];

    $labels = [
        'pending' => 'Ожидает обработки',
        'processing' => 'Принят продавцом',
        'paid' => 'Оплачен',
        'shipped' => 'Отправлен',
        'delivered' => 'Доставлен',
        'completed' => 'Завершён',
        'canceled' => 'Отменён',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ' . ($colors[$status] ?? 'bg-gray-100 text-gray-700')]) }}>
    {{ $labels[$status] ?? 'Неизвестно' }}
</span>
