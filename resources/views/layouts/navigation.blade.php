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

<nav class="fixed left-0 top-0 z-50 w-full border-b border-slate-200/70 bg-white/90 shadow-[0_10px_30px_rgba(15,23,42,0.06)] backdrop-blur-xl">

    <div class="w-full mx-auto px-4 sm:px-6 lg:px-7">
        <!-- Основная линия хедера -->
        <div class="hidden lg:flex items-center h-16 gap-5">

            <!-- ========== Левая часть: Логотип + Категории ========== -->
            <div class="flex items-center gap-3 flex-shrink-0">
                <!-- Логотип -->
                <a href="{{ route('home') }}" class="group flex items-center gap-2 rounded-2xl px-1.5 py-1 transition hover:bg-slate-50">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 shadow-lg shadow-indigo-500/20 transition group-hover:-translate-y-0.5 group-hover:shadow-indigo-500/30">
                        <img src="{{ asset('images/icon.png') }}" alt="WebVitrina" class="h-8 w-8" />
                    </span>
                    <span class="text-[15px] font-bold tracking-tight text-slate-800">WebVitrina</span>
                </a>

                <!-- Кнопка категорий -->
                <button @click="open = true"
                        class="group flex h-10 items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50/80 px-3.5 text-slate-700 shadow-sm shadow-slate-900/5 transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 hover:shadow-md hover:shadow-indigo-900/5">
                    <div class="flex h-6 w-6 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-900/5 transition group-hover:text-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" 
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </div>
                    <span class="text-sm font-semibold">Категории</span>
                </button>
            </div>

            <!-- ========== Центр: Поиск + иерархический выбор страны/города ========== -->
            <div class="flex-1 flex justify-center items-center gap-3">
                
                <!-- Поле поиска с крестиком очистки -->
                <form action="{{ route('home') }}" method="GET" class="w-full max-w-3xl m-0"
                    x-data="{ search: '{{ request('q') }}' }">
                    <div class="group relative">
                        <input type="text" 
                            name="q" 
                            x-model="search"
                            placeholder="Искать товары..."
                            class="h-11 w-full rounded-2xl border-slate-200 bg-slate-50/80 pl-4 pr-20 text-sm text-slate-800 shadow-inner shadow-slate-200/50 transition-all duration-200 placeholder:text-slate-400 focus:border-indigo-300 focus:bg-white focus:ring-4 focus:ring-indigo-100" />
                        
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
                                class="absolute inset-y-0 right-12 flex items-center px-2 text-slate-400 transition-colors hover:text-slate-600"
                                title="Очистить">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Кнопка поиска -->
                        <button type="submit" 
                                class="absolute right-1.5 top-1/2 flex h-8 w-9 -translate-y-1/2 items-center justify-center rounded-xl text-slate-500 transition-colors hover:bg-indigo-600 hover:text-white">
                            <div class="flex h-6 w-6 items-center justify-center">
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
                    use Illuminate\Support\Facades\Cache;
                    $countries = Cache::remember('countries.with_cities', 3600, fn () =>
                        Country::with(['cities' => fn ($q) => $q->select('id', 'country_id', 'name')->orderBy('name')])
                            ->orderBy('name')
                            ->get()
                    );
                    $currentCountry = request('country_id', session('country_id'));
                    $currentCity = request('city_id', session('city_id'));
                    $selectedCountry = $countries->firstWhere('id', $currentCountry);
                @endphp

                <div x-data="{ 
                        open: false,
                        step: 'country', // 'country' или 'city'
                        selectedCountry: {{ json_encode($currentCountry) }},
                        selectedCity: {{ json_encode($currentCity) }},
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
                            class="group flex h-11 min-w-[164px] items-center justify-between gap-2 rounded-2xl border border-slate-200 bg-slate-50/80 px-3.5 text-slate-700 shadow-sm shadow-slate-900/5 transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 hover:shadow-md hover:shadow-indigo-900/5">
                        <div class="flex items-center gap-2 truncate">
                     
                            <!-- Флаг показываем, если страна выбрана -->
                            <template x-if="selectedCountryData && selectedCountryData.slug">
                                <img :src="'{{ asset('flags') }}/' + selectedCountryData.slug + '.png'" 
                                    class="w-5 h-5 rounded-md flex-shrink-0 shadow-sm">
                            </template>

                            <!-- Ваша НОВАЯ иконка-заглушка, если страна НЕ выбрана -->
                            <template x-if="!selectedCountryData">
                                <svg class="w-5 h-5 text-slate-400 flex-shrink-0 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke-width="1.5"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                                        d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                                </svg>
                            </template>

                            <!-- Текст выбора -->
                            <span class="text-sm font-medium truncate" x-text="displayText"></span>
                        </div>
                        
                        <!-- Стрелка и индикатор активности -->
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <template x-if="selectedCountry || selectedCity">
                                <span class="w-2 h-2 bg-indigo-500 rounded-full shadow-sm shadow-indigo-500/40"></span>
                            </template>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200 group-hover:text-indigo-500"
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
                         class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-3xl border border-white/80 bg-white/95 py-2 shadow-2xl shadow-slate-950/15 ring-1 ring-slate-900/5 backdrop-blur-xl">
                        
                        <!-- Шапка с навигацией -->
                        <div class="px-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                            <template x-if="step === 'city'">
                                <button @click="goBack" 
                                        class="flex items-center gap-1 text-xs font-semibold text-slate-500 hover:text-indigo-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                    <span>К странам</span>
                                </button>
                            </template>
                            <template x-if="step === 'country'">
                                <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Выберите страну</span>
                            </template>
                        </div>
                        
                        <!-- Контент в зависимости от шага -->
                        <div class="max-h-80 overflow-y-auto custom-scrollbar">
                            <!-- ШАГ 1: Выбор страны -->
                            <template x-if="step === 'country'">
                                <div>
                                    <!-- Кнопка "Все страны" -->
                                    <button @click="selectedCountry = null; selectedCity = null; applySelection()"
                                    class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center gap-3 transition-colors"
                                            :class="{ 'bg-indigo-50 text-indigo-600': !selectedCountry }">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <circle cx="12" cy="12" r="10" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                                                        d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                                                </svg>
                                        <span class="text-sm font-medium">Все страны</span>
                                    </button>
                                    
                                    <!-- Разделитель -->
                                    <div class="border-t border-slate-100 my-2"></div>
                                    
                                    <!-- Список стран -->
                                    <template x-for="country in countries" :key="country.id">
                                        <button @click="selectCountry(country.id)"
                                                class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center gap-3 transition-colors"
                                                :class="{ 'bg-indigo-50 text-indigo-600': selectedCountry === country.id && !selectedCity }">
                                            <img :src="'{{ asset('flags') }}/' + country.slug + '.png'" 
                                                 class="w-5 h-5 rounded-md shadow-sm">
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
                                    class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center gap-3 transition-colors font-medium"
                                            :class="{ 'bg-indigo-50 text-indigo-600': !selectedCity }">
                                        <img :src="'{{ asset('flags') }}/' + selectedCountryData.slug + '.png'" 
                                             class="w-5 h-5 rounded-md shadow-sm">
                                        <span class="text-sm" x-text="'Все города ' + selectedCountryData.name"></span>
                                    </button>
                                    
                                    <!-- Разделитель если есть города -->
                                    <template x-if="selectedCountryData.cities.length > 0">
                                        <div class="border-t border-slate-100 my-2"></div>
                                    </template>
                                    
                                    <!-- Список городов -->
                                    <template x-for="city in selectedCountryData.cities" :key="city.id">
                                        <button @click="selectCity(city.id)"
                                                class="w-full text-left px-4 py-2.5 hover:bg-slate-50 flex items-center gap-3 transition-colors pl-12"
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
                             class="border-t border-slate-100 mt-2 pt-2 px-3">
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
                   class="group relative flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white/90 text-slate-600 shadow-sm shadow-slate-900/5 transition-all duration-200 hover:-translate-y-0.5 hover:border-red-200 hover:bg-red-50 hover:text-red-500 hover:shadow-md hover:shadow-red-900/5"
                   title="Избранное">
                    <svg class="w-5 h-5 transition-transform duration-200 group-hover:scale-110" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" 
                              d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                    </svg>
                </a>

                <!-- Корзина -->
                <a href="{{ route('cart.index') }}" 
                   class="group relative flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white/90 text-slate-600 shadow-sm shadow-slate-900/5 transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 hover:shadow-md hover:shadow-indigo-900/5"
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
                        currency: @js(session('currency', 'PRB')),
                        setCurrency(code) {
                            this.currency = code;
                            this.open = false;
                            fetch(@js(route('currency.set')), {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': @js(csrf_token()),
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({ currency: code })
                            }).then(response => {
                                if (!response.ok) throw new Error('Currency update failed');
                                window.location.reload();
                            });
                        }
                    }" class="relative">

                    <button @click="open = !open"
                            class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white/90 text-slate-600 shadow-sm shadow-slate-900/5 transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 hover:shadow-md hover:shadow-indigo-900/5">
                        <template x-if="currency === 'PRB' || currency === 'RUB'">
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
                         class="absolute right-0 z-50 mt-2 w-24 overflow-hidden rounded-2xl border border-white/80 bg-white/95 py-1 text-sm shadow-2xl shadow-slate-950/15 ring-1 ring-slate-900/5 backdrop-blur-xl">
                        <button @click="setCurrency('PRB')" class="block w-full px-3 py-2 text-left font-medium text-slate-600 hover:bg-slate-50 hover:text-indigo-600">₽ RUB</button>
                        <button @click="setCurrency('MDL')" class="block w-full px-3 py-2 text-left font-medium text-slate-600 hover:bg-slate-50 hover:text-indigo-600">MDL</button>
                        <button @click="setCurrency('UAH')" class="block w-full px-3 py-2 text-left font-medium text-slate-600 hover:bg-slate-50 hover:text-indigo-600">₴ UAH</button>
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
                            class="flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white/90 shadow-sm shadow-slate-900/5 transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-50 hover:shadow-md hover:shadow-indigo-900/5">
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
                         class="absolute right-0 z-50 mt-2 w-36 overflow-hidden rounded-2xl border border-white/80 bg-white/95 py-1 text-sm shadow-2xl shadow-slate-950/15 ring-1 ring-slate-900/5 backdrop-blur-xl">
                        <button @click="setLang('ru')" class="flex w-full items-center gap-2 px-3 py-2 text-left font-medium text-slate-600 hover:bg-slate-50 hover:text-indigo-600">
                            <img src="{{ asset('flags/ru.png') }}" class="w-4 h-4 rounded-sm"> Русский
                        </button>
                        <button @click="setLang('en')" class="flex w-full items-center gap-2 px-3 py-2 text-left font-medium text-slate-600 hover:bg-slate-50 hover:text-indigo-600">
                            <img src="{{ asset('flags/en.png') }}" class="w-4 h-4 rounded-sm"> English
                        </button>
                        <button @click="setLang('uk')" class="flex w-full items-center gap-2 px-3 py-2 text-left font-medium text-slate-600 hover:bg-slate-50 hover:text-indigo-600">
                            <img src="{{ asset('flags/uk.png') }}" class="w-4 h-4 rounded-sm"> Українська
                        </button>
                        <button @click="setLang('ro')" class="flex w-full items-center gap-2 px-3 py-2 text-left font-medium text-slate-600 hover:bg-slate-50 hover:text-indigo-600">
                            <img src="{{ asset('flags/ro.png') }}" class="w-4 h-4 rounded-sm"> Română
                        </button>
                    </div>
                </div>

                <!-- Аккаунт -->
                 @auth
                    <a href="{{ route('notifications.index') }}"
                       class="relative flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white/90 shadow-sm shadow-slate-900/5 transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-indigo-50">
                        <i class="ri-notification-3-line text-lg text-slate-600"></i>
                        @if(($unreadNotificationsCount ?? 0) > 0)
                            <span class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                {{ min($unreadNotificationsCount, 99) }}
                            </span>
                        @endif
                    </a>

                    <x-dropdown align="right" width="72">
                        <x-slot name="trigger">
                            <button class="group flex h-11 items-center gap-2 rounded-2xl border border-slate-200 bg-white/90 py-1 pl-1.5 pr-2.5 shadow-sm shadow-slate-900/5 transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:bg-white hover:shadow-md hover:shadow-indigo-900/5">
                                
                                <!-- Если есть аватарка - показываем её, если нет - градиент -->
                                @if(auth()->user()->avatar)
                                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}"
                                        alt="{{ auth()->user()->name }}"
                                        class="h-9 w-9 rounded-2xl object-cover border-2 border-white shadow-sm group-hover:shadow-md transition-shadow">
                                @else
                                    <div class="flex h-9 w-9 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-500 text-sm font-semibold text-white shadow-sm shadow-indigo-500/20 group-hover:shadow-md transition-shadow">
                                        {{ substr(auth()->user()->name, 0, 1) }}
                                    </div>
                                @endif
                                
                                <span class="hidden max-w-[9rem] truncate text-sm font-bold text-slate-700 xl:block">{{ auth()->user()->name }}</span>
                                <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </x-slot>
                        
                        <x-slot name="content">
                            @php
                                $role = strtolower(auth()->user()->role ?? '');
                                $dashboard = match ($role) {
                                    'admin'  => route('admin.dashboard'),
                                    'seller' => route('seller.cabinet'),
                                    default  => route('cabinet'),
                                };

                                $profileRoute = match ($role) {
                                    'admin'  => route('admin.profile'),
                                    'seller' => route('profile.edit'),
                                    default  => route('buyer.profile'),
                                };

                                $roleMeta = match ($role) {
                                    'admin' => ['label' => 'Администратор', 'icon' => 'ri-shield-star-line', 'class' => 'bg-purple-100 text-purple-800'],
                                    'seller' => ['label' => 'Продавец', 'icon' => 'ri-store-2-line', 'class' => 'bg-blue-100 text-blue-800'],
                                    default => ['label' => 'Покупатель', 'icon' => 'ri-shopping-bag-3-line', 'class' => 'bg-white text-slate-700 ring-1 ring-slate-200'],
                                };

                                $quickLinks = match ($role) {
                                    'admin' => [
                                        ['href' => route('admin.orders.index'), 'label' => 'Заказы', 'icon' => 'ri-file-list-3-line'],
                                        ['href' => route('admin.products.index'), 'label' => 'Товары', 'icon' => 'ri-store-2-line'],
                                        ['href' => route('admin.users.index'), 'label' => 'Пользователи', 'icon' => 'ri-group-line'],
                                        ['href' => route('admin.chats.index'), 'label' => 'Чаты', 'icon' => 'ri-chat-3-line'],
                                    ],
                                    'seller' => [
                                        ['href' => route('chats.index'), 'label' => 'Чаты', 'icon' => 'ri-chat-3-line'],
                                        ['href' => route('seller.products.index'), 'label' => 'Товары', 'icon' => 'ri-store-2-line'],
                                        ['href' => route('seller.orders.index'), 'label' => 'Заказы', 'icon' => 'ri-file-list-3-line'],
                                        ['href' => route('seller.analytics.index'), 'label' => 'Аналитика', 'icon' => 'ri-line-chart-line'],
                                    ],
                                    default => [
                                        ['href' => route('chats.index'), 'label' => 'Чаты', 'icon' => 'ri-chat-3-line'],
                                        ['href' => route('orders.index'), 'label' => 'Заказы', 'icon' => 'ri-shopping-bag-3-line'],
                                        ['href' => route('favorites.index'), 'label' => 'Избранное', 'icon' => 'ri-heart-3-line'],
                                        ['href' => route('cart.index'), 'label' => 'Корзина', 'icon' => 'ri-shopping-cart-2-line'],
                                    ],
                                };
                            @endphp

                            <div class="relative mb-3 overflow-hidden rounded-[1.45rem] border border-indigo-100/80 bg-gradient-to-br from-indigo-50 via-white to-sky-50 p-4 shadow-sm shadow-indigo-900/5">
                                <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-indigo-200/35 blur-2xl"></div>
                                <div class="relative flex items-start gap-3">
                                    @if(auth()->user()->avatar)
                                        <img src="{{ asset('storage/' . auth()->user()->avatar) }}"
                                             alt="{{ auth()->user()->name }}"
                                             class="h-11 w-11 rounded-2xl object-cover ring-2 ring-white shadow-sm">
                                    @else
                                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-500 text-sm font-semibold text-white shadow-sm shadow-indigo-500/20">
                                            {{ substr(auth()->user()->name, 0, 1) }}
                                        </div>
                                    @endif

                                    <div class="relative min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>

                                        <span class="mt-2 inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $roleMeta['class'] }}">
                                            <i class="{{ $roleMeta['icon'] }}"></i>
                                            {{ $roleMeta['label'] }}
                                        </span>
                                    </div>
                                </div>

                                <a href="{{ $dashboard }}"
                                   class="relative mt-4 flex h-11 items-center justify-center gap-2 rounded-2xl bg-slate-950 text-sm font-bold text-white shadow-lg shadow-slate-950/15 transition hover:-translate-y-0.5 hover:bg-indigo-600">
                                    <i class="ri-dashboard-line"></i>
                                    Открыть кабинет
                                </a>
                            </div>

                            <div class="mb-3">
                                <p class="mb-2 px-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                                    Быстрый доступ
                                </p>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach($quickLinks as $link)
                                        <a href="{{ $link['href'] }}"
                                           class="group flex items-center gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 px-3 py-3 transition duration-200 hover:-translate-y-0.5 hover:border-indigo-100 hover:bg-white hover:shadow-lg hover:shadow-indigo-900/5">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-slate-500 shadow-sm ring-1 ring-slate-900/5 transition group-hover:text-indigo-600">
                                                <i class="{{ $link['icon'] }} text-base"></i>
                                            </span>
                                            <span class="truncate text-sm font-semibold text-slate-700 group-hover:text-slate-900">
                                                {{ $link['label'] }}
                                            </span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            <div class="space-y-1 rounded-2xl border border-slate-100 bg-slate-50/70 p-1">
                                <x-dropdown-link :href="$profileRoute" class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    </span>
                                    Редактировать профиль
                                </x-dropdown-link>
                                
                                <div class="my-1 border-t border-slate-200/70"></div>
                                
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')" 
                                        onclick="event.preventDefault(); this.closest('form').submit();"
                                        class="flex items-center gap-3 text-red-600 hover:bg-red-50 hover:text-red-700">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-red-50 text-red-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        </span>
                                        Выйти
                                    </x-dropdown-link>
                                </form>
                            </div>
                        </x-slot>
                    </x-dropdown>
                @else
                    <div class="flex items-center gap-2">
                        <a href="{{ route('login') }}" class="inline-flex h-10 items-center rounded-2xl px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-indigo-600">Войти</a>
                        <a href="{{ route('register') }}" class="inline-flex h-10 items-center rounded-2xl bg-indigo-600 px-4 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-indigo-600/30">Регистрация</a>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</nav>

<script>
    // Глобальные переменные для всех скриптов на странице
    window.selectedCountry = {{ json_encode($currentCountry) }};
    window.selectedCity = {{ json_encode($currentCity) }};
</script>

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
