@props(['items' => []])

@if(!empty($items))
    <nav {{ $attributes->merge(['class' => 'flex min-w-0 flex-wrap items-center gap-1 text-sm text-slate-500']) }} aria-label="Breadcrumbs">
        @foreach($items as $item)
            @if(!$loop->first)
                <i class="ri-arrow-right-s-line text-slate-300"></i>
            @endif
            @if(!empty($item['href']) && !$loop->last)
                <a href="{{ $item['href'] }}" class="font-medium hover:text-indigo-600">{{ $item['label'] }}</a>
            @else
                <span class="font-semibold text-slate-700">{{ $item['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
