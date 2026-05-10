@props([
    'full' => false,
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'h-10 px-4 text-sm',
        'md' => 'h-11 px-5 text-sm',
        'icon' => 'w-10 h-10 text-lg',
    ];

    $classes = trim(
        'inline-flex items-center justify-center gap-2 rounded-xl border border-rose-100 bg-rose-50 text-rose-600 ' .
        'font-semibold hover:bg-rose-100 transition-all duration-200 ' .
        ($sizes[$size] ?? $sizes['md']) . ' ' .
        ($full ? 'w-full' : '')
    );
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
    {{ $slot }}
</button>
