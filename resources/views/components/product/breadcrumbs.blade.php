@props(['product'])

<nav class="mb-4 text-sm text-gray-500 flex flex-wrap items-center gap-1">
    <a href="{{ route('home') }}" class="hover:text-indigo-600">Главная</a>

    {{-- Если категории нет --}}
    @if(!$product->category)
        <span>›</span>
        <span class="text-gray-400 italic">Категория удалена</span>
        @php return; @endphp
    @endif

    {{-- Построение цепочки категорий --}}
    @php
        $breadcrumbs = [];
        $cat = $product->category;
        while ($cat) {
            $breadcrumbs[] = $cat;
            $cat = $cat->parent;
        }
        $breadcrumbs = array_reverse($breadcrumbs);
    @endphp

    {{-- Вывод цепочки --}}
    @foreach ($breadcrumbs as $cat)
        <span>›</span>

        @if($cat->slug)
            <a href="{{ route('category.show', $cat->slug) }}" 
               class="hover:text-indigo-600">
                {{ $cat->name }}
            </a>
        @else
            <span class="text-gray-400 italic">
                {{ $cat->name }} (нет категории(SLUG))
            </span>
        @endif
    @endforeach
</nav>
