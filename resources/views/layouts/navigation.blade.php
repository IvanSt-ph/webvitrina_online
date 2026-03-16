<style>
    [x-cloak] { display: none !important; }
    
    /* Анимация для выпадающего меню */
    .dropdown-enter-active, .dropdown-leave-active {
        transition: all 0.2s ease-out;
    }
    .dropdown-enter-from, .dropdown-leave-to {
        opacity: 0;
        transform: translateY(-8px);
    }
</style>

<nav class="bg-white/80 backdrop-blur-md border-b border-gray-100 shadow-md shadow-gray-200/40 fixed top-0 left-0 w-full z-50">

    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Основная линия хедера -->
        <div class="hidden lg:flex items-center h-16 gap-6">

            <!-- ========== Левая часть: Логотип + Категории ========== -->
            <div class="flex items-center gap-4 flex-shrink-0">
                <!-- Логотип -->
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/icon.png') }}" alt="WebVitrina" class="h-9 w-auto" />
                    <span class="font-semibold text-gray-800">WebVitrina</span>
                </a>

                <!-- Кнопка категорий -->
                <button @click="open = true"
                        class="flex items-center gap-2 px-4 h-10 rounded-lg border border-gray-300 bg-white 
                               text-gray-700 shadow-sm transition-all duration-200 hover:bg-indigo-600 hover:text-white hover:shadow-md">
                    <div class="flex items-center justify-center w-6 h-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" 
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium">Категории</span> 
                </button>
            </div>

            <!-- ========== Центр: Поиск + иерархический выбор страны/города ========== -->
            <div class="flex-1 flex justify-center items-center gap-3 ">
                
                <!-- Поле поиска с крестиком очистки -->
                <form action="{{ route('home') }}" method="GET" class="w-full max-w-2xl m-0" 
                    x-data="{ search: '{{ request('q') }}' }">
                    <div class="relative">
                        <input type="text" 
                            name="q" 
                            x-model="search"
                            placeholder="Искать товары..."
                            class="w-full h-10 rounded-xl border-gray-300 pr-20 pl-4
                                    focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50
                                    transition-all duration-200" />
                        
                        <!-- Крестик очистки -->
                        <button type="button"
                                x-show="search.length > 0"
                                x-cloak
                                @click="
                                    search = ''; 
                                    $el.closest('form').querySelector('input[name=q]').value = '';
                                    // Создаем новый URL без параметра q и переходим
                                    let url = new URL(window.location.href);
                                    url.searchParams.delete('q');
                                    window.location.href = url.toString();
                                "
                                class="absolute inset-y-0 right-8 flex items-center px-2 text-gray-400 hover:text-gray-600 transition-colors"
                                title="Очистить">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Кнопка поиска -->
                        <button type="submit" 
                                class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-indigo-600 transition-colors">
                            <div class="flex items-center justify-center w-6 h-6">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" 
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 
                                                            110-15 7.5 7.5 0 010 15z"/>
                                </svg>
                            </div>
                        </button>
                    </div>
                </form>

                <!-- ИЕРАРХИЧЕСКИЙ ВЫБОР СТРАНЫ/ГОРОДА -->
                @php
                    use App\Models\Country;
                    use App\Models\City;
                    $countries = Country::orderBy('name')->get();
                    $currentCountry = request('country_id', session('country_id'));
                    $currentCity = request('city_id', session('city_id'));
                    $selectedCountry = $countries->firstWhere('id', $currentCountry);
                @endphp

                <div x-data="{ 
                        open: false,
                        step: 'country', // 'country' или 'city'
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
                        
                        get selectedCityName() {
                            if (!this.selectedCountryData || !this.selectedCity) return null;
                            const city = this.selectedCountryData.cities.find(c => c.id === this.selectedCity);
                            return city ? city.name : null;
                        },
                        
                        get displayText() {
                            if (this.selectedCityName) {
                                return this.selectedCityName;
                            }
                            if (this.selectedCountryData) {
                                return this.selectedCountryData.name + ' (все города)';
                            }
                            return 'Все страны';
                        },
                        
                        selectCountry(countryId) {
                            this.selectedCountry = countryId;
                            this.selectedCity = null;
                            this.step = 'city'; // Переходим к выбору города
                            
                            // Если у страны нет городов, сразу применяем
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
                            this.open = false;
                            this.step = 'country';
                            
                            // Обновляем URL
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
                        
                        clearFilters() {
                            this.selectedCountry = null;
                            this.selectedCity = null;
                            this.step = 'country';
                            this.open = false;
                            
                            // Обновляем URL
                            let url = new URL(window.location.href);
                            url.searchParams.delete('country_id');
                            url.searchParams.delete('city_id');
                            window.location.href = url.toString();
                        }
                    }" class="relative flex-shrink-0">
                    
                    <!-- Кнопка-триггер (показывает текущий выбор) -->
                    <button @click="open = !open; if(open) step = 'country'"
                            class="flex items-center gap-2 px-3 h-10 rounded-lg border border-gray-300 bg-white 
                                   text-gray-700 shadow-sm transition-all duration-200 
                                   hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700
                                   min-w-[150px] justify-between group">
                        <div class="flex items-center gap-2 truncate">
                     
                            <!-- Флаг показываем, если страна выбрана -->
                            <template x-if="selectedCountryData && selectedCountryData.slug">
                                <img :src="'{{ asset('flags') }}/' + selectedCountryData.slug + '.png'" 
                                    class="w-5 h-5 rounded-sm flex-shrink-0">
                            </template>

                            <!-- Ваша НОВАЯ иконка-заглушка, если страна НЕ выбрана -->
                            <template x-if="!selectedCountryData">
                                <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke-width="1.5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                                        d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                </svg>
                            </template>

                            <!-- Текст выбора -->
                            <span class="text-sm truncate" x-text="displayText"></span>
                        </div>
                        
                        <!-- Стрелка и индикатор активности -->
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <template x-if="selectedCountry || selectedCity">
                                <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                            </template>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" 
                                 :class="{ 'rotate-180': open }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </button>

                    <!-- Выпадающее меню -->
                    <div x-cloak
                         x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1"
                         @click.away="open = false"
                         class="absolute right-0 mt-1 w-72 bg-white border border-gray-200 rounded-lg shadow-lg py-2 z-50">
                        
                        <!-- Шапка с навигацией -->
                        <div class="px-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                            <template x-if="step === 'city'">
                                <button @click="goBack" 
                                        class="flex items-center gap-1 text-xs text-gray-500 hover:text-indigo-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                    <span>К странам</span>
                                </button>
                            </template>
                            <template x-if="step === 'country'">
                                <span class="text-xs font-medium text-gray-500">Выберите страну</span>
                            </template>
                        </div>
                        
                        <!-- Контент в зависимости от шага -->
                        <div class="max-h-80 overflow-y-auto custom-scrollbar">
                            <!-- ШАГ 1: Выбор страны -->
                            <template x-if="step === 'country'">
                                <div>
                                    <!-- Кнопка "Все страны" -->
                                    <button @click="selectedCountry = null; selectedCity = null; applySelection()"
                                            class="w-full text-left px-4 py-2 hover:bg-gray-50 flex items-center gap-3 transition-colors"
                                            :class="{ 'bg-indigo-50 text-indigo-600': !selectedCountry }">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                                                        d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                                                </svg>
                                        <span class="text-sm font-medium">Все страны</span>
                                    </button>
                                    
                                    <!-- Разделитель -->
                                    <div class="border-t border-gray-100 my-2"></div>
                                    
                                    <!-- Список стран -->
                                    <template x-for="country in countries" :key="country.id">
                                        <button @click="selectCountry(country.id)"
                                                class="w-full text-left px-4 py-2 hover:bg-gray-50 flex items-center gap-3 transition-colors"
                                                :class="{ 'bg-indigo-50 text-indigo-600': selectedCountry === country.id && !selectedCity }">
                                            <img :src="'{{ asset('flags') }}/' + country.slug + '.png'" 
                                                 class="w-5 h-5 rounded-sm">
                                            <span class="text-sm font-medium" x-text="country.name"></span>
                                            <span class="text-xs text-gray-400 ml-auto" x-text="country.cities.length + ' гор.'"></span>
                                        </button>
                                    </template>
                                </div>
                            </template>
                            
                            <!-- ШАГ 2: Выбор города для выбранной страны -->
                            <template x-if="step === 'city' && selectedCountryData">
                                <div>
                                    <!-- Кнопка "Все города" (выбрать страну без конкретного города) -->
                                    <button @click="selectedCity = null; applySelection()"
                                            class="w-full text-left px-4 py-2 hover:bg-gray-50 flex items-center gap-3 transition-colors font-medium"
                                            :class="{ 'bg-indigo-50 text-indigo-600': !selectedCity }">
                                        <img :src="'{{ asset('flags') }}/' + selectedCountryData.slug + '.png'" 
                                             class="w-5 h-5 rounded-sm">
                                        <span class="text-sm" x-text="'Все города ' + selectedCountryData.name"></span>
                                    </button>
                                    
                                    <!-- Разделитель если есть города -->
                                    <template x-if="selectedCountryData.cities.length > 0">
                                        <div class="border-t border-gray-100 my-2"></div>
                                    </template>
                                    
                                    <!-- Список городов -->
                                    <template x-for="city in selectedCountryData.cities" :key="city.id">
                                        <button @click="selectCity(city.id)"
                                                class="w-full text-left px-4 py-2 hover:bg-gray-50 flex items-center gap-3 transition-colors pl-12"
                                                :class="{ 'bg-indigo-50 text-indigo-600': selectedCity === city.id }">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span class="text-sm" x-text="city.name"></span>
                                        </button>
                                    </template>
                                    
                                    <!-- Сообщение если нет городов -->
                                    <div x-show="selectedCountryData.cities.length === 0" 
                                         class="px-4 py-3 text-sm text-gray-500 text-center">
                                        Нет доступных городов
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Кнопка сброса (если что-то выбрано) -->
                        <div x-show="selectedCountry || selectedCity" 
                             class="border-t border-gray-100 mt-2 pt-2 px-3">
                            <button @click="clearFilters()"
                                    class="w-full px-3 py-1.5 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>Сбросить фильтры</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== Правая часть: Избранное / Корзина / Валюта / Язык / Аккаунт ========== -->
            <div class="flex items-center justify-end gap-2 flex-shrink-0">

                <!-- Избранное -->
                <a href="{{ route('favorites.index') }}" 
                   class="flex items-center justify-center w-8 h-8 text-gray-600 border border-gray-200 rounded-lg hover:border-red-200 hover:bg-red-50 hover:text-red-500 transition-all duration-200 group relative"
                   title="Избранное">
                    <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" 
                              d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                    </svg>
                </a>

                <!-- Корзина -->
                <a href="{{ route('cart.index') }}" 
                   class="flex items-center justify-center w-8 h-8 text-gray-600 border border-gray-200 rounded-lg hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200 group relative"
                   title="Корзина">
                    <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" 
                              d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                    </svg>
                </a>

                <!-- Валюта -->
                <div x-data="{ 
                        open: false,
                        currency: localStorage.getItem('currency') || 'RUB',
                        setCurrency(code) {
                            this.currency = code;
                            localStorage.setItem('currency', code);
                            this.open = false;
                            window.location.search = '?currency=' + code;
                        }
                    }" class="relative">

                    <button @click="open = !open"
                            class="flex items-center justify-center w-8 h-8 text-gray-600 border border-gray-200 rounded-lg hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 transition-all duration-200">
                        <template x-if="currency === 'RUB'">
                            <img src='{{ asset("icons/rub.png") }}' alt="RUB" class="w-5 h-5">
                        </template>
                        <template x-if="currency === 'MDL'">
                            <img src='{{ asset("icons/md.png") }}' alt="MDL" class="w-5 h-5">
                        </template>
                        <template x-if="currency === 'UAH'">
                            <img src='{{ asset("icons/ua.png") }}' alt="UAH" class="w-5 h-5">
                        </template>
                    </button>

                    <div x-cloak
                         x-show="open"
                         x-transition
                         @click.away="open = false"
                         class="absolute right-0 mt-2 w-20 bg-white border border-gray-200 rounded-lg shadow-lg py-1 text-sm z-50">
                        <button @click="setCurrency('RUB')" class="block w-full text-left px-3 py-1.5 hover:bg-gray-50">₽ RUB</button>
                        <button @click="setCurrency('MDL')" class="block w-full text-left px-3 py-1.5 hover:bg-gray-50">MDL</button>
                        <button @click="setCurrency('UAH')" class="block w-full text-left px-3 py-1.5 hover:bg-gray-50">₴ UAH</button>
                    </div>
                </div>

                <!-- Язык -->
                <div x-data="{ 
                        open: false,
                        lang: localStorage.getItem('lang') || 'ru',
                        setLang(code) {
                            this.lang = code;
                            localStorage.setItem('lang', code);
                            this.open = false;
                            window.location.search = '?lang=' + code;
                        }
                    }" class="relative">

                    <button @click="open = !open"
                            class="flex items-center justify-center w-8 h-8 border border-gray-200 rounded-lg hover:border-indigo-200 hover:bg-indigo-50 transition-all duration-200">
                        <template x-if="lang === 'ru'">
                            <img src="{{ asset('flags/ru.png') }}" alt="Русский" class="w-5 h-5 rounded-sm">
                        </template>
                        <template x-if="lang === 'en'">
                            <img src="{{ asset('flags/en.png') }}" alt="English" class="w-5 h-5 rounded-sm">
                        </template>
                        <template x-if="lang === 'uk'">
                            <img src="{{ asset('flags/uk.png') }}" alt="Українська" class="w-5 h-5 rounded-sm">
                        </template>
                        <template x-if="lang === 'ro'">
                            <img src="{{ asset('flags/ro.png') }}" alt="Română" class="w-5 h-5 rounded-sm">
                        </template>
                    </button>

                    <div x-cloak
                         x-show="open"
                         x-transition
                         @click.away="open = false"
                         class="absolute right-0 mt-2 w-28 bg-white border border-gray-200 rounded-lg shadow-lg py-1 text-sm z-50">
                        <button @click="setLang('ru')" class="block w-full text-left px-3 py-1.5 hover:bg-gray-50 flex items-center gap-2">
                            <img src="{{ asset('flags/ru.png') }}" class="w-4 h-4 rounded-sm"> Русский
                        </button>
                        <button @click="setLang('en')" class="block w-full text-left px-3 py-1.5 hover:bg-gray-50 flex items-center gap-2">
                            <img src="{{ asset('flags/en.png') }}" class="w-4 h-4 rounded-sm"> English
                        </button>
                        <button @click="setLang('uk')" class="block w-full text-left px-3 py-1.5 hover:bg-gray-50 flex items-center gap-2">
                            <img src="{{ asset('flags/uk.png') }}" class="w-4 h-4 rounded-sm"> Українська
                        </button>
                        <button @click="setLang('ro')" class="block w-full text-left px-3 py-1.5 hover:bg-gray-50 flex items-center gap-2">
                            <img src="{{ asset('flags/ro.png') }}" class="w-4 h-4 rounded-sm"> Română
                        </button>
                    </div>
                </div>

                <!-- Аккаунт -->
                 @auth
                    <x-dropdown align="right" width="64">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 pl-2 pr-1 py-1 rounded-lg hover:bg-gray-100 transition-all duration-200 group">
                                
                                <!-- Если есть аватарка - показываем её, если нет - градиент -->
                                @if(auth()->user()->avatar)
                                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}"
                                        alt="{{ auth()->user()->name }}"
                                        class="w-8 h-8 rounded-full object-cover border-2 border-white shadow-sm group-hover:shadow-md transition-shadow">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-sm font-medium shadow-sm group-hover:shadow-md transition-shadow">
                                        {{ substr(auth()->user()->name, 0, 1) }}
                                    </div>
                                @endif
                                
                                <span class="text-sm text-gray-700 hidden xl:block">{{ auth()->user()->name }}</span>
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </x-slot>
                        
                        <x-slot name="content">
                            @php
                                $dashboard = match (strtolower(auth()->user()->role ?? '')) {
                                    'admin'  => route('admin.dashboard'),
                                    'seller' => route('seller.cabinet'),
                                    default  => route('cabinet'),
                                };

                                $profileRoute = match (strtolower(auth()->user()->role ?? '')) {
                                    'admin'  => route('admin.profile.edit'),
                                    'seller' => route('profile.edit'),
                                    default  => route('buyer.profile'),
                                };
                            @endphp

                            <div class="px-4 py-3 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</p>
                            </div>

                            <div class="py-1">
                                <x-dropdown-link :href="$dashboard" class="flex items-center gap-2 px-4 py-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 9.5l9-7 9 7V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1V9.5z"/>
                                    </svg>
                                    Личный кабинет
                                </x-dropdown-link>
                                
                                <x-dropdown-link :href="$profileRoute" class="flex items-center gap-2 px-4 py-2">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Редактировать профиль
                                </x-dropdown-link>
                                
                                <div class="border-t border-gray-100 my-1"></div>
                                
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')" 
                                        onclick="event.preventDefault(); this.closest('form').submit();"
                                        class="flex items-center gap-2 px-4 py-2 text-red-600 hover:text-red-700 hover:bg-red-50">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Выйти
                                    </x-dropdown-link>
                                </form>
                            </div>

                            @if(auth()->user()->role)
                                <div class="px-4 py-2 bg-gray-50 rounded-b-lg border-t border-gray-100">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                        @if(auth()->user()->role === 'admin') bg-purple-100 text-purple-800
                                        @elseif(auth()->user()->role === 'seller') bg-blue-100 text-blue-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        @if(auth()->user()->role === 'admin') 👑 Администратор
                                        @elseif(auth()->user()->role === 'seller') 🏪 Продавец
                                        @else Покупатель @endif
                                    </span>
                                </div>
                            @endif
                        </x-slot>
                    </x-dropdown>
                @else
                    <div class="flex items-center gap-2">
                        <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-indigo-600 transition-colors">Войти</a>
                        <a href="{{ route('register') }}" class="text-sm px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">Регистрация</a>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</nav>

<!-- Стили для скроллбара -->
<style>
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e0;
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>