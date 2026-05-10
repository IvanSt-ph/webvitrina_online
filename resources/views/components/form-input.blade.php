@props([
    'textarea' => false,
    'rows' => 2,
])

@if($textarea)
    <textarea rows="{{ $rows }}" {{ $attributes->merge(['class' => 'w-full px-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none resize-none']) }}>{{ $slot }}</textarea>
@else
    <input {{ $attributes->merge(['class' => 'w-full px-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none']) }}>
@endif
