@if($activeFilters)
    <div class="mb-4">
        <h4 class="text-xs text-gray-500 mb-2">Вы выбрали:</h4>

        <div class="flex flex-wrap gap-2">
            @foreach($category->attributes as $attr)

                @php
                    $v = $activeFilters[$attr->id] ?? null;
                @endphp

                @if(!$v) @continue @endif

                {{-- 🎨 Цвет --}}
                @if($attr->type === 'color')
                    @foreach($v as $colorId)
                        @php
                            $color = $attr->colors->firstWhere('id', $colorId);

                            $newFilters = $activeFilters;
                            $newFilters[$attr->id] = array_values(
                                array_filter($v, fn($x) => $x != $colorId)
                            );
                            if (empty($newFilters[$attr->id])) unset($newFilters[$attr->id]);

                            $url = url()->current() . '?' . http_build_query([
                                'sort' => $currentSort,
                                'filters' => $newFilters,
                            ]);
                        @endphp

                        @if($color)
                            <a href="{{ $url }}"
                               class="flex items-center gap-2 px-3 py-1 bg-indigo-100 text-indigo-700 text-xs rounded-full">
                                <span class="w-3 h-3 rounded-full border" style="background: {{ $color->hex }}"></span>
                                {{ $color->name }}
                                <span class="text-indigo-600">×</span>
                            </a>
                        @endif
                    @endforeach

                {{-- 🧩 Текстовые --}}
                @else
                    @foreach($v as $val)
                        @php
                            $newFilters = $activeFilters;

                            $newFilters[$attr->id] = array_values(
                                array_filter($v, fn($x) => $x != $val)
                            );
                            if (empty($newFilters[$attr->id])) unset($newFilters[$attr->id]);

                            $url = url()->current() . '?' . http_build_query([
                                'sort' => $currentSort,
                                'filters' => $newFilters,
                            ]);
                        @endphp

                        <a href="{{ $url }}"
                           class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs rounded-full flex items-center gap-1">
                            {{ $val }}
                            <span class="text-indigo-600">×</span>
                        </a>
                    @endforeach
                @endif

            @endforeach
        </div>
    </div>
@endif
