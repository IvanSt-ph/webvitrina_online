@php
    $keep = request()->except(['page', 'sort']);
    $currentSort = request('sort', 'popular');

    // проверка конечной категории
    $isLeafCategory = isset($category) && $category->children()->count() === 0;

    $labels = [
        'popular'     => 'По популярности',
        'rating'      => 'По рейтингу',
        'price_asc'   => 'По возрастанию цены',
        'price_desc'  => 'По убыванию цены',
        'new'         => 'По новинкам',
        'benefit'     => 'Сначала выгодные',
    ];
@endphp


<div x-data="{ openFilters: false }" class="max-w-7xl mx-auto px-4 lg:px-6 mt-16 mb-6">

    <div class="flex flex-wrap items-center gap-2 md:gap-3 text-sm">

        <!-- СОРТИРОВКА -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open"
                class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-[15px] shadow-sm 
                       hover:border-indigo-400 hover:bg-indigo-50 transition text-sm text-gray-700">

                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 4h18M3 12h18M3 20h18"/>
                </svg>

                <span>{{ $labels[$currentSort] ?? 'Сортировка' }}</span>

                <svg class="w-4 h-4 text-gray-500 transition-transform duration-200"
                     :class="{ 'rotate-180': open }"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" @click.away="open = false" x-transition x-cloak
                 class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-[15px] shadow-lg z-50">

                <form method="GET" action="{{ url()->current() }}" class="p-2 space-y-1">

                    {{-- Сохранение активных параметров --}}
                    @foreach($keep as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $subKey => $subValue)
                                @if(is_array($subValue))
                                    @foreach($subValue as $subSubKey => $subSubValue)
                                        <input type="hidden" 
                                               name="{{ $key }}[{{ $subKey }}][{{ $subSubKey }}]"
                                               value="{{ $subSubValue }}">
                                    @endforeach
                                @else
                                    <input type="hidden" 
                                           name="{{ $key }}[{{ $subKey }}]"
                                           value="{{ $subValue }}">
                                @endif
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach

                    @foreach($labels as $value => $label)
                        <label class="flex items-center justify-between gap-2 px-3 py-2 rounded-[12px]
                                       hover:bg-gray-100 cursor-pointer">
                            <span class="text-gray-700 text-sm">{{ $label }}</span>

                            <input type="radio" name="sort" value="{{ $value }}"
                                   onchange="this.form.submit()"
                                   @checked($currentSort === $value)
                                   class="text-indigo-600 focus:ring-indigo-500 border-gray-300">
                        </label>
                    @endforeach

                </form>
            </div>
        </div>


        <!-- КНОПКА "ВСЕ ФИЛЬТРЫ" -->
        @if($isLeafCategory)
        <button @click="openFilters = true"
            class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-[15px] shadow-sm 
                   hover:border-indigo-400 hover:bg-indigo-50 transition">
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3 4h18M3 12h18M3 20h18"/>
            </svg>
            Фильтры товаров
        </button>
        @endif

    </div>


    <!-- ===================== -->
    <!--   ВЫЕЗЖАЮЩАЯ ПАНЕЛЬ   -->
    <!-- ===================== -->

    @if($isLeafCategory)
    <div x-show="openFilters" x-cloak class="fixed inset-0 z-50 flex justify-end bg-black/10 backdrop-blur-sm">

        <!-- Фон -->
        <div class="absolute inset-0 bg-black/10" @click="openFilters = false"></div>

        <!-- ПАНЕЛЬ -->
        <div x-show="openFilters"
            x-transition
            class="relative w-full max-w-sm bg-white h-full shadow-xl border-l p-6 overflow-y-auto">

            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Фильтры</h2>
                <button @click="openFilters = false" class="text-gray-500 hover:text-black text-xl">✕</button>
            </div>


            {{-- Проверка наличия фильтров --}}
            @if($category->attributes->count())

<form method="GET" action="{{ url()->current() }}" class="space-y-6">

    @if($currentSort)
        <input type="hidden" name="sort" value="{{ $currentSort }}">
    @endif


{{-- ВЕРХНИЕ ЧИПЫ АКТИВНЫХ ФИЛЬТРОВ --}}
@php
    $activeFilters = request('filters', []);

    // Приводим строки к массивам
    foreach ($activeFilters as $k => $v) {
        if (!is_array($v)) {
            $activeFilters[$k] = [$v];
        }
    }
@endphp

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

                            // новый набор фильтров
                            $newFilters = $activeFilters;

                            // удаляем только одно значение
                            $newFilters[$attr->id] = array_values(
                                array_filter($v, fn($x) => $x != $colorId)
                            );

                            // очищаем пустые атрибуты
                            if (empty($newFilters[$attr->id])) {
                                unset($newFilters[$attr->id]);
                            }

                            // собираем URL
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

                {{-- 🧩 Простые значения --}}
                @else
                    @foreach($v as $val)
                        @if(is_array($val)) @continue @endif

                        @php
                            $newFilters = $activeFilters;

                            $newFilters[$attr->id] = array_values(
                                array_filter($v, fn($x) => $x != $val)
                            );

                            if (empty($newFilters[$attr->id])) {
                                unset($newFilters[$attr->id]);
                            }

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




    {{-- СПИСОК ФИЛЬТРОВ --}}
    @foreach($category->attributes as $attr)
        <div x-data="{ open: true }" class="border-b border-gray-100 pb-4">

            <button type="button"
                    @click="open = !open"
                    class="w-full flex items-center justify-between py-2">

                <span class="text-sm font-medium text-gray-800">{{ $attr->name }}</span>

                <svg class="w-4 h-4 text-gray-500 transition-transform duration-200"
                     :class="{ 'rotate-180': open }"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-transition class="mt-2 space-y-2">

                @php
                    $input = "filters[{$attr->id}]";
                    $selected = request("filters.$attr->id", []);
                    $options = is_array($attr->options)
                        ? $attr->options
                        : json_decode($attr->options ?? '[]', true);
                @endphp


                {{-- ОПЦИИ --}}
                @if($attr->type !== 'number' && $attr->type !== 'color')
                    <div class="space-y-1">
                        @foreach($options as $optRaw)
                            @php
                                $opt = is_array($optRaw)
                                    ? json_encode($optRaw, JSON_UNESCAPED_UNICODE)
                                    : (string)$optRaw;
                            @endphp

                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox"
                                       name="{{ $input }}[]"
                                       value="{{ $opt }}"
                                       @checked(in_array($opt, (array)$selected))
                                       class="hidden peer">

                                <div class="w-5 h-5 rounded border border-gray-300 
                                            peer-checked:border-indigo-600 
                                            peer-checked:bg-indigo-600 transition"></div>

                                <span class="text-sm text-gray-700 group-hover:text-black">{{ $opt }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif


                {{-- ЦВЕТ --}}
                @if($attr->type === 'color')
                    @php
                        $colors = $attr->colors;
                        $selectedColors = request("filters.$attr->id", []);
                    @endphp

                    <div class="flex flex-wrap gap-3">
                        @foreach($colors as $color)
                            <label class="flex flex-col items-center cursor-pointer group">

                                <input type="checkbox" 
                                       name="{{ $input }}[]" 
                                       value="{{ $color->id }}"
                                       @checked(in_array($color->id, (array)$selectedColors))
                                       class="hidden peer">

                                <div class="w-7 h-7 rounded-full border-2 transition
                                    peer-checked:border-indigo-600 peer-checked:scale-110"
                                    style="background: {{ $color->hex }}">
                                </div>

                                <span class="text-[11px] text-gray-500 mt-1">{{ $color->name }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif


                {{-- ЧИСЛА --}}
                @if($attr->type === 'number')
                    <div class="flex gap-3">
                        <input type="number" name="{{ $input }}[from]"
                               value="{{ request("filters.$attr->id.from") }}"
                               placeholder="От"
                               class="w-1/2 p-2 border rounded-lg focus:border-indigo-500">

                        <input type="number" name="{{ $input }}[to]"
                               value="{{ request("filters.$attr->id.to") }}"
                               placeholder="До"
                               class="w-1/2 p-2 border rounded-lg focus:border-indigo-500">
                    </div>
                @endif

            </div>
        </div>
    @endforeach


    <!-- APPLY -->
    <button class="w-full py-3 bg-indigo-600 text-white font-medium rounded-[15px] mt-6">
        Показать товары
    </button>

    <!-- RESET -->
    <a href="{{ url()->current() }}"
       class="w-full block text-center py-2.5 bg-gray-100 text-gray-700 rounded-[15px] mt-2">
        Сбросить фильтры
    </a>

</form>

            @endif

        </div>
    </div>
    @endif

</div>
