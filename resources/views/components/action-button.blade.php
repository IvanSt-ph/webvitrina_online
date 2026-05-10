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
        'relative overflow-hidden group bg-indigo-500/90 hover:bg-indigo-600 text-white font-semibold rounded-xl ' .
        'shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 ' .
        'inline-flex items-center justify-center gap-2 backdrop-blur-sm border border-indigo-400/30 ' .
        ($sizes[$size] ?? $sizes['md']) . ' ' .
        ($full ? 'w-full' : '')
    );
@endphp

@if($as === 'a')
<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    <span class="relative z-10 flex items-center gap-2">
        {{ $slot }}
    </span>
    <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
</a>
@else
<button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>
    <span class="relative z-10 flex items-center gap-2">
        {{ $slot }}
    </span>
    <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
</button>
@endif
