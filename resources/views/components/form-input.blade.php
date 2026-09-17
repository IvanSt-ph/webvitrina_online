@props([
    'textarea' => false,
    'rows' => 2,
])

@if($textarea)
    <textarea rows="{{ $rows }}" {{ $attributes->merge(['class' => 'w-full px-4 py-3 rounded-xl border border-neutral-300 bg-neutral-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-brand-100 focus:border-brand-500 transition outline-none resize-none']) }}>{{ $slot }}</textarea>
@else
    <input {{ $attributes->merge(['class' => 'w-full px-4 py-3 rounded-xl border border-neutral-300 bg-neutral-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-brand-100 focus:border-brand-500 transition outline-none']) }}>
@endif
