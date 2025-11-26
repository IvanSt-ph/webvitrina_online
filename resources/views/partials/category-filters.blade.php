@php
    $keep = request()->except(['page', 'sort']);
    $currentSort = request('sort', 'popular');

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


        <!-- 🔽 Сортировка -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open"
                class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-[15px] shadow-sm hover:border-indigo-400 hover:bg-indigo-50 transition text-sm text-gray-700">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M3 12h18M3 20h18"/>
                </svg>
                <span>{{ $labels[$currentSort] ?? 'Сортировка' }}</span>
                <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" 
                     :class="{ 'rotate-180': open }"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" @click.away="open = false" x-transition x-cloak
                class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-[15px] shadow-lg z-50">

                <form method="GET" action="{{ url()->current() }}" class="p-2 space-y-1">

                    {{-- Сохранение фильтров в hidden --}}
                    @foreach($keep as $key => $value)
                        @if(is_array($value))
                            @foreach($value as $subKey => $subValue)
                                @if(is_array($subValue))
                                    @foreach($subValue as $subSubKey => $subSubValue)
                                        <input type="hidden" name="{{ $key }}[{{ $subKey }}][{{ $subSubKey }}]"
                                            value="{{ $subSubValue }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}[{{ $subKey }}]"
                                        value="{{ $subValue }}">
                                @endif
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach

                    @foreach($labels as $value => $label)
                        <label class="flex items-center justify-between gap-2 px-3 py-2 rounded-[12px] hover:bg-gray-100 cursor-pointer">
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


        <!-- Кнопка Все фильтры -->
        <button @click="openFilters = true"
            class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-[15px] shadow-sm hover:border-indigo-400 hover:bg-indigo-50 transition">
            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2H3V4zm0 8h20v2H3v-2zm0 8h20v2H3v-2z"/>
            </svg>
            Все фильтры
        </button>

    </div>

    <!-- 🧭 Выезжающая панель -->
    <div x-show="openFilters" x-cloak class="fixed inset-0 z-50 flex justify-end bg-black/10 backdrop-blur-sm">

        <div class="absolute inset-0 bg-black/10" @click="openFilters = false"></div>

        <div x-show="openFilters"
            x-transition
            class="relative w-full max-w-sm bg-white h-full shadow-xl border-l p-6 overflow-y-auto">

            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Фильтры</h2>
                <button @click="openFilters = false" class="text-gray-500 hover:text-black text-xl">✕</button>
            </div>



@if($category->attributes->count())
<form method="GET" action="{{ url()->current() }}" class="space-y-6">

    {{-- Сохранение сортировки --}}
    @if($currentSort)
        <input type="hidden" name="sort" value="{{ $currentSort }}">
    @endif

    {{-- ФИЛЬТРЫ --}}
    @foreach($category->attributes as $attr)
        <div>
            <h3 class="text-sm font-medium text-gray-700 mb-2">
                {{ $attr->name }}
            </h3>

            @php
                $input = "filters[{$attr->id}]";
                $selected = request("filters.$attr->id", []);
                $options = is_array($attr->options) ? $attr->options : json_decode($attr->options ?? '[]', true);

                 // 👉 ВРЕМЕННЫЙ ДЕБАГ (сделай для одной категории, не на проде)
            if ($attr->id == 3) { // подставь нужный id
                dump('ATTR ID = '.$attr->id);
                dump('OPTIONS (из attributes.options):', $options);
                dump('VALUES (из attribute_values):', \App\Models\AttributeValue::where('attribute_id', $attr->id)->distinct()->pluck('value')->toArray());
            }
            @endphp

            {{-- SELECT / TEXT --}}
            @if($attr->type !== 'number' && $attr->type !== 'color')
                <div class="space-y-1">
                    @foreach($options as $optRaw)
                        @php
                            $opt = is_array($optRaw)
                                ? json_encode($optRaw, JSON_UNESCAPED_UNICODE)
                                : (string)$optRaw;
                        @endphp

                        <label class="flex items-center gap-2">
                            <input type="checkbox" class="text-indigo-600 rounded"
                                name="{{ $input }}[]" value="{{ $opt }}"
                                @checked(in_array($opt, (array)$selected))>

                            <span>{{ $opt }}</span>
                        </label>
                    @endforeach
                </div>
            @endif


            {{-- COLOR --}}
            @if($attr->type === 'color')
                <div class="flex gap-2">
                    @foreach($options as $colorRaw)
                        @php
                            $color = is_array($colorRaw)
                                ? json_encode($colorRaw, JSON_UNESCAPED_UNICODE)
                                : (string)$colorRaw;
                        @endphp

                        <label>
                            <input type="checkbox" name="{{ $input }}[]" value="{{ $color }}"
                                class="w-5 h-5 rounded-full border"
                                style="background: {{ $color }}"
                                @checked(in_array($color, (array)$selected))>
                        </label>
                    @endforeach
                </div>
            @endif


            {{-- NUMBER --}}
            @if($attr->type === 'number')
                <div class="flex gap-3">
                    <input type="number"
                        name="{{ $input }}[from]"
                        value="{{ request("filters.$attr->id.from") }}"
                        placeholder="От" class="border w-1/2 rounded p-2">

                    <input type="number"
                        name="{{ $input }}[to]"
                        value="{{ request("filters.$attr->id.to") }}"
                        placeholder="До" class="border w-1/2 rounded p-2">
                </div>
            @endif

        </div>
    @endforeach


    <!-- КНОПКА ПРИМЕНИТЬ -->
    <button class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-[15px] font-medium transition">
        Показать товары
    </button>

    <!-- 🔥 КНОПКА СБРОСА ФИЛЬТРОВ -->
    <a href="{{ url()->current() }}"
       class="w-full block text-center py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-[15px] font-medium transition">
        Сбросить фильтры
    </a>

</form>
@endif

        </div>
    </div>

</div>
