@props([
    'as' => 'button',
    'href' => null,
    'full' => false,
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => 'h-10 px-4 text-sm',
        'md' => 'h-11 px-5 text-sm',
        'lg' => 'px-6 py-3.5 text-base',
    ];

    $classes = trim(
        'inline-flex items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-white text-neutral-700 ' .
        'font-semibold shadow-sm hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 ' .
        'transition-all duration-200 ' .
        ($sizes[$size] ?? $sizes['md']) . ' ' .
        ($full ? 'w-full' : '')
    );
@endphp

@if($as === 'a')
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
