@props(['product'])

<nav class="mb-4 text-sm text-gray-500 flex flex-wrap items-center gap-1">
    <a href="{{ route('home') }}" class="hover:text-indigo-600">Главная</a>

    @if ($product->category)
        @php
            $breadcrumbs = [];
            $cat = $product->category;
            while ($cat) {
                $breadcrumbs[] = $cat;
                $cat = $cat->parent;
            }
            $breadcrumbs = array_reverse($breadcrumbs);
        @endphp

        @foreach ($breadcrumbs as $cat)
            <span>›</span>
            <a href="{{ route('category.show', $cat->slug) }}" class="hover:text-indigo-600">
                {{ $cat->name }}
            </a>
        @endforeach
    @endif
</nav>
