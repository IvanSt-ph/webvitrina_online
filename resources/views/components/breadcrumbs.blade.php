@props(['items' => []])

@if(!empty($items))
    <nav {{ $attributes->merge(['class' => 'flex min-w-0 flex-wrap items-center gap-1 text-sm text-slate-500']) }} aria-label="Breadcrumbs">
        @foreach($items as $key => $item)
            @php
                if (is_array($item)) {
                    $label = $item['label'] ?? (is_string($key) ? $key : '');
                    $href = $item['href'] ?? null;
                } else {
                    $label = is_string($key) ? $key : (string) $item;
                    $href = is_string($key) ? $item : null;
                }

                $href = $href === '#' ? null : $href;
            @endphp
            @if(!$loop->first)
                <i class="ri-arrow-right-s-line text-slate-300"></i>
            @endif
            @if(!empty($href) && !$loop->last)
                <a href="{{ $href }}" class="font-medium hover:text-indigo-600">{{ $label }}</a>
            @else
                <span class="font-semibold text-slate-700">{{ $label }}</span>
            @endif
        @endforeach
    </nav>
@endif
