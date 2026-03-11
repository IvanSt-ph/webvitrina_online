{{-- resources/views/layouts/mobile-topbar.blade.php --}}
@php
    use App\Models\Country;
    use App\Models\City;
    $countries = Country::orderBy('name')->get();
    $currentCountry = request('country_id', session('country_id'));
    $currentCity = request('city_id', session('city_id'));
    $selectedCountry = $countries->firstWhere('id', $currentCountry);
    
    // Формируем текст для кнопки
    if ($currentCity) {
        $cityName = City::find($currentCity)?->name;
        $filterButtonText = $cityName ?? 'Город';
    } elseif ($currentCountry) {
        $filterButtonText = $selectedCountry?->name ?? 'Страна';
    } else {
        $filterButtonText = '';
    }
@endphp

<div class="lg:hidden" 
     x-data="mobileHeader()"
     x-init="init()">
    
    <!-- Поисковая строка -->
    <div class="bg-white/80 backdrop-blur-md border-b border-gray-100 shadow-sm fixed top-0 left-0 w-full z-40">
        <div class="px-2 py-1.5"> <!-- Уменьшенные отступы -->
            <div class="flex items-stretch gap-1.5"> <!-- items-stretch для одинаковой высоты -->
                
                <!-- Поиск (занимает всё доступное место) -->
                <form action="{{ route('home') }}" method="GET" class="flex-1">
                    <div class="relative h-full">
                        <input type="text" 
                               name="q" 
                               x-model="search"
                               placeholder="Поиск товаров..."
                               class="w-full h-9 pl-8 pr-7 text-sm rounded-lg border border-gray-200
                                      focus:border-indigo-200 focus:ring-2 focus:ring-indigo-100
                                      transition-all duration-200 placeholder:text-gray-400" />
                        
                        <!-- Иконка поиска слева -->
                        <div class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z" />
                            </svg>
                        </div>
                        
                        <!-- Крестик очистки -->
                        <button type="button"
                                x-show="search.length > 0"
                                x-cloak
                                @click="search = ''; $el.closest('form').querySelector('input[name=q]').value = ''; submitSearch()"
                                class="absolute right-1.5 top-1/2 -translate-y-1/2 flex items-center justify-center w-5 h-5
                                       text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full
                                       transition-colors"
                                title="Очистить">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </form>

                    <!-- Кнопка выбора страны/города -->
                    <button @click="filtersOpen = true" 
                            class="flex items-center gap-1 px-2.5 h-9 bg-indigo-50 text-indigo-700 rounded-lg 
                                hover:bg-indigo-100 transition-colors whitespace-nowrap text-sm font-medium
                                border border-indigo-200 flex-shrink-0"
                            title="Выбрать регион">
                        
                        <!-- Если выбрана страна - показываем её флаг, иначе - глобус -->
                        @if($selectedCountry && $selectedCountry->slug)
                            <img src="{{ asset('flags/' . $selectedCountry->slug . '.png') }}" 
                                alt="{{ $selectedCountry->name }}"
                                class="w-3 h-3 rounded-sm object-cover">
                        @else
                            <img src="{{ asset('icons/globe.png') }}" 
                                alt="Все страны"
                                class="w-7 h-7 opacity-50">
                        @endif
                        
                        <span class="max-w-[60px] truncate">{{ $filterButtonText }}</span>
                        
                        @if($currentCountry || $currentCity)
                            <span class="w-1.5 h-1.5 bg-indigo-600 rounded-full"></span>
                        @endif
                    </button>
            </div>
        </div>
    </div>

    <!-- Компактный отступ для контента -->
    <div class="h-[45px]"></div>

    <!-- Модалка выбора страны/города (без изменений, она и так нормальная) -->
    <div x-show="filtersOpen" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50"
         @keydown.escape.window="filtersOpen = false">
        
        <!-- Затемнение -->
        <div class="absolute inset-0 bg-black/20 backdrop-blur-sm" @click="filtersOpen = false"></div>
        
        <!-- Модалка снизу -->
        <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-2xl p-4 border-t border-gray-100 max-h-[90vh] overflow-y-auto mb-10 mx-1"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-full"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-full">
            
            <!-- Заголовок -->
            <div class="flex items-center justify-between mb-4 sticky top-0 bg-white pt-1">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                    </svg>
                    <span class="text-base font-medium text-gray-900">Выбор региона</span>
                </div>
                <button @click="filtersOpen = false" 
                        class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 
                               hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Иерархический выбор (без изменений) -->
            <div x-data="{
                    step: 'country',
                    selectedCountry: {{ $currentCountry ?: 'null' }},
                    selectedCity: {{ $currentCity ?: 'null' }},
                    countries: {{ json_encode($countries->map(function($country) {
                        return [
                            'id' => $country->id,
                            'name' => $country->name,
                            'slug' => $country->slug,
                            'cities' => $country->cities->map(function($city) {
                                return ['id' => $city->id, 'name' => $city->name];
                            })
                        ];
                    })) }},
                    
                    get selectedCountryData() {
                        return this.countries.find(c => c.id === this.selectedCountry) || null;
                    },
                    
                    selectCountry(countryId) {
                        this.selectedCountry = countryId;
                        this.selectedCity = null;
                        this.step = 'city';
                        
                        const country = this.countries.find(c => c.id === countryId);
                        if (!country.cities.length) {
                            this.applySelection();
                        }
                    },
                    
                    selectCity(cityId) {
                        this.selectedCity = cityId;
                        this.applySelection();
                    },
                    
                    applySelection() {
                        let url = new URL(window.location.href);
                        if (this.selectedCountry) {
                            url.searchParams.set('country_id', this.selectedCountry);
                        } else {
                            url.searchParams.delete('country_id');
                        }
                        
                        if (this.selectedCity) {
                            url.searchParams.set('city_id', this.selectedCity);
                        } else {
                            url.searchParams.delete('city_id');
                        }
                        
                        window.location.href = url.toString();
                    },
                    
                    goBack() {
                        this.step = 'country';
                    },
                    
                    clearAll() {
                        this.selectedCountry = null;
                        this.selectedCity = null;
                        this.step = 'country';
                        
                        let url = new URL(window.location.href);
                        url.searchParams.delete('country_id');
                        url.searchParams.delete('city_id');
                        window.location.href = url.toString();
                    }
                }">
                
                <!-- Навигация назад -->
                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-gray-100 min-h-[40px]">
                    <template x-if="step === 'city'">
                        <button @click="goBack" 
                                class="flex items-center gap-1 text-sm text-gray-600 hover:text-indigo-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            <span>Назад к странам</span>
                        </button>
                    </template>
                    <template x-if="step === 'country'">
                        <span class="text-sm text-gray-500">Выберите страну</span>
                    </template>
                </div>
                
                <!-- Список стран -->
                <template x-if="step === 'country'">
                    <div class="space-y-1">
                        <!-- Все страны -->
                        <button @click="selectedCountry = null; selectedCity = null; applySelection()"
                                class="w-full text-left px-3 py-3 hover:bg-gray-50 rounded-xl flex items-center gap-3 transition-colors"
                                :class="{ 'bg-indigo-50': !selectedCountry }">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke-width="1.5"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                                      d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                            </svg>
                            <span class="text-base">Все страны</span>
                        </button>
                        
                        <!-- Список стран -->
                        <template x-for="country in countries" :key="country.id">
                            <button @click="selectCountry(country.id)"
                                    class="w-full text-left px-3 py-3 hover:bg-gray-50 rounded-xl flex items-center gap-3 transition-colors"
                                    :class="{ 'bg-indigo-50': selectedCountry === country.id }">
                                <img :src="'{{ asset('flags') }}/' + country.slug + '.png'" 
                                     class="w-5 h-5 rounded-sm">
                                <span class="text-base" x-text="country.name"></span>
                                <span class="ml-auto text-sm text-gray-400" x-text="country.cities.length + ' гор.'"></span>
                            </button>
                        </template>
                    </div>
                </template>
                
                <!-- Список городов -->
                <template x-if="step === 'city' && selectedCountryData">
                    <div class="space-y-1">
                        <!-- Все города выбранной страны -->
                        <button @click="selectedCity = null; applySelection()"
                                class="w-full text-left px-3 py-3 hover:bg-gray-50 rounded-xl flex items-center gap-3 transition-colors"
                                :class="{ 'bg-indigo-50': !selectedCity }">
                            <img :src="'{{ asset('flags') }}/' + selectedCountryData.slug + '.png'" 
                                 class="w-5 h-5 rounded-sm">
                            <span class="text-base" x-text="'Все города ' + selectedCountryData.name"></span>
                        </button>
                        
                        <!-- Города -->
                        <template x-for="city in selectedCountryData.cities" :key="city.id">
                            <button @click="selectCity(city.id)"
                                    class="w-full text-left px-3 py-3 hover:bg-gray-50 rounded-xl flex items-center gap-3 transition-colors pl-11"
                                    :class="{ 'bg-indigo-50': selectedCity === city.id }">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-base" x-text="city.name"></span>
                            </button>
                        </template>
                    </div>
                </template>
            </div>
            
            <!-- Кнопка сброса -->
            <div x-show="selectedCountry || selectedCity" class="mt-4 pt-3 border-t border-gray-100">
                <button @click="clearAll"
                        class="w-full py-3 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Сбросить все фильтры</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function mobileHeader() {
        return {
            search: '{{ request('q') }}',
            filtersOpen: false,
            
            init() {
                // Инициализация
            },
            
            submitSearch() {
                if (this.search.length === 0) {
                    let url = new URL(window.location.href);
                    url.searchParams.delete('q');
                    window.location.href = url.toString();
                }
            }
        }
    }
</script>