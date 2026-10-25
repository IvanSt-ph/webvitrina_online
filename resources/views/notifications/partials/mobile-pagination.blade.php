@if($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        if ($last <= 5) {
            $pages = range(1, $last);
        } elseif ($current <= 3) {
            $pages = [1, 2, 3, 4, $last];
        } elseif ($current >= $last - 2) {
            $pages = [1, $last - 3, $last - 2, $last - 1, $last];
        } else {
            $pages = [1, $current - 1, $current, $current + 1, $last];
        }
        $previous = null;
        $control = 'inline-flex h-11 min-w-0 flex-1 items-center justify-center rounded-lg text-xs focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600 focus-visible:outline-offset-2';
    @endphp
    <nav aria-label="Страницы уведомлений" class="flex w-full items-center sm:hidden">
        @if($paginator->onFirstPage())
            <span aria-disabled="true" aria-label="Предыдущая страница" class="{{ $control }} text-neutral-300"><i class="ri-arrow-left-s-line text-lg" aria-hidden="true"></i></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Предыдущая страница" class="{{ $control }} text-neutral-600 hover:bg-neutral-50"><i class="ri-arrow-left-s-line text-lg" aria-hidden="true"></i></a>
        @endif
        @foreach($pages as $page)
            @if($previous !== null && $page > $previous + 1)
                <span class="flex h-11 min-w-0 flex-1 items-center justify-center text-xs text-neutral-400" aria-label="Пропущенные страницы">…</span>
            @endif
            @if($page === $current)
                <span aria-current="page" aria-label="Страница {{ $page }}" class="{{ $control }} bg-brand-500 font-semibold text-white">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" aria-label="Страница {{ $page }}" class="{{ $control }} text-neutral-600 hover:bg-neutral-50">{{ $page }}</a>
            @endif
            @php $previous = $page; @endphp
        @endforeach
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Следующая страница" class="{{ $control }} text-neutral-600 hover:bg-neutral-50"><i class="ri-arrow-right-s-line text-lg" aria-hidden="true"></i></a>
        @else
            <span aria-disabled="true" aria-label="Следующая страница" class="{{ $control }} text-neutral-300"><i class="ri-arrow-right-s-line text-lg" aria-hidden="true"></i></span>
        @endif
    </nav>
@endif
