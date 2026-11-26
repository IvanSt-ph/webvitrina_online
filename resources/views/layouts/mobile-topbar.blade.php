{{-- resources/views/layouts/mobile-topbar.blade.php --}}
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
    
    // Формируем текст для кнопки
    if ($currentCity) {
        $cityName = $selectedCountry?->cities->firstWhere('id', (int) $currentCity)?->name;
        $filterButtonText = $cityName ?? 'Город';
    } elseif ($currentCountry) {
        $filterButtonText = $selectedCountry?->name ?? 'Страна';
    } else {
        $filterButtonText = '';
    }
@endphp

<div class="lg:hidden" 
     x-data="mobileHeader">
    
    <!-- Поисковая строка -->
    <div x-ref="topbar" class="fixed top-0 left-0 z-40 w-full border-b border-gray-100 bg-white/90 shadow-[0_10px_30px_rgba(15,23,42,0.08)] backdrop-blur-xl">
        <div class="px-3 py-2">
            <div class="mobile-topbar-row flex items-stretch gap-2">
                <form action="{{ route('home') }}" method="GET" class="min-w-0 flex-1">
                    <div class="relative h-full">
                    <input type="text"
                           name="q"
                           x-model="search"
                           placeholder="Найти товар, бренд или категорию"
                           class="mobile-topbar-search w-full rounded-2xl border border-slate-200 bg-slate-50/80 pl-10 pr-10 text-sm
                                  transition-all duration-200 placeholder:text-slate-400
                                  focus:border-indigo-300 focus:bg-white focus:ring-4 focus:ring-indigo-100" />

                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <svg class="h-4 w-4 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z" />
                        </svg>
                    </div>

                    <button type="button"
                            x-show="search.length > 0"
                            x-cloak
                            @click="search = ''; $el.closest('form').querySelector('input[name=q]').value = ''; submitSearch()"
                            class="absolute inset-y-0 right-2.5 my-auto flex h-6 w-6 items-center justify-center
                                   rounded-full text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600"
                            title="Очистить">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    </div>
                </form>

                <button type="button" x-ref="settingsTrigger" @click="settingsOpen ? closeSettings() : openSettings()" aria-controls="mobile-settings" :aria-expanded="settingsOpen" aria-haspopup="dialog" aria-label="Регион и валюта"
                        class="mobile-topbar-settings relative flex w-11 shrink-0 items-center justify-center self-stretch rounded-2xl border border-slate-200 bg-white
                               text-slate-700 shadow-sm transition-colors hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600"
                        title="Регион и валюта">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h10" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 7h2" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 17h4" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 17h8" />
                        <circle cx="16" cy="7" r="2" stroke-width="1.8" />
                        <circle cx="10" cy="17" r="2" stroke-width="1.8" />
                    </svg>
                    @if($currentCountry || $currentCity)
                        <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-indigo-500 ring-2 ring-white"></span>
                    @endif
                </button>
            </div>
        </div>
    </div>

    <div class="h-[60px]"></div>

    <!-- Единый мобильный инструмент: валюта, страна и город -->
    <div x-cloak x-show="settingsOpen" class="wv-mobile-settings-clip"
         :style="{ top: panelTop + 'px', height: panelHeight + 'px' }"
         @keydown.escape.window="if (settingsOpen) closeSettings()">
        <div class="absolute inset-0" @click="closeSettings()" aria-hidden="true"></div>
        <section x-show="settingsOpen" x-ref="settingsPanel" id="mobile-settings" role="dialog"
                 aria-labelledby="mobile-settings-title" tabindex="-1" class="wv-mobile-settings"
                 x-transition:enter="wv-mobile-panel-enter" x-transition:enter-start="-translate-y-full" x-transition:enter-end="translate-y-0"
                 x-transition:leave="wv-mobile-panel-leave" x-transition:leave-start="translate-y-0" x-transition:leave-end="-translate-y-full">
            <header class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                <div><h2 id="mobile-settings-title" class="text-base font-semibold text-slate-900">Регион и валюта</h2>
                    <p class="text-sm text-slate-500">Настройте показ товаров</p></div>
                <button type="button" @click="closeSettings()" aria-label="Закрыть настройки" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100"><span aria-hidden="true" class="text-2xl">×</span></button>
            </header>
            <div class="min-h-0 overflow-y-auto overscroll-contain p-4">
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Валюта</h3>
                <div class="mb-5 grid grid-cols-3 gap-2" role="group" aria-label="Валюта показа товаров">
                    <button type="button" @click="setCurrency('PRB')" :aria-pressed="currency === 'PRB'" class="wv-mobile-currency" :class="currency === 'PRB' ? 'is-selected' : ''">
                        <span class="text-lg font-semibold" aria-hidden="true">₽</span>
                        <span class="font-semibold">RUB</span><span class="text-xs text-slate-500">Рубль</span>
                    </button>
                    <button type="button" @click="setCurrency('MDL')" :aria-pressed="currency === 'MDL'" class="wv-mobile-currency" :class="currency === 'MDL' ? 'is-selected' : ''">
                        <span class="text-lg font-semibold" aria-hidden="true">L</span>
                        <span class="font-semibold">MDL</span><span class="text-xs text-slate-500">Лей</span>
                    </button>
                    <button type="button" @click="setCurrency('UAH')" :aria-pressed="currency === 'UAH'" class="wv-mobile-currency" :class="currency === 'UAH' ? 'is-selected' : ''">
                        <span class="text-lg font-semibold" aria-hidden="true">₴</span>
                        <span class="font-semibold">UAH</span><span class="text-xs text-slate-500">Гривна</span>
                    </button>
                </div>
                <div class="mb-2 flex items-center justify-between"><h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Регион</h3><span class="max-w-[65%] truncate rounded-full bg-indigo-50 px-2 py-1 text-xs text-indigo-700">{{ $filterButtonText ?: 'Все страны' }}</span></div>
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
                            url.searchParams.delete('clear_location');
                            url.searchParams.set('country_id', this.selectedCountry);
                        } else {
                            url.searchParams.delete('country_id');
                            url.searchParams.set('clear_location', '1');
                        }
                        
                        if (this.selectedCity) {
                            url.searchParams.set('city_id', this.selectedCity);
                        } else if (this.selectedCountry) {
                            url.searchParams.set('city_id', '');
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
                        url.searchParams.set('clear_location', '1');
                        window.location.href = url.toString();
                    }
                }">
                
                <!-- Навигация назад -->
                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-gray-100 min-h-[40px]">
                    <template x-if="step === 'city'">
                        <button type="button" @click="goBack"
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
                        <button type="button" @click="selectedCountry = null; selectedCity = null; applySelection()"
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
                            <button type="button" @click="selectCountry(country.id)"
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
                        <button type="button" @click="selectedCity = null; applySelection()"
                                class="w-full text-left px-3 py-3 hover:bg-gray-50 rounded-xl flex items-center gap-3 transition-colors"
                                :class="{ 'bg-indigo-50': !selectedCity }">
                            <img :src="'{{ asset('flags') }}/' + selectedCountryData.slug + '.png'" 
                                 class="w-5 h-5 rounded-sm">
                            <span class="text-base" x-text="'Все города ' + selectedCountryData.name"></span>
                        </button>
                        
                        <!-- Города -->
                        <template x-for="city in selectedCountryData.cities" :key="city.id">
                            <button type="button" @click="selectCity(city.id)"
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
            <!-- Кнопка сброса -->
            <div x-show="selectedCountry || selectedCity" class="mt-4 pt-3 border-t border-gray-100">
                <button type="button" @click="clearAll"
                        class="w-full py-3 text-sm text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Сбросить все фильтры</span>
                </button>
            </div>
        </div>
            </div>
        </section>
    </div>
</div>
