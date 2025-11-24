{{-- Поиск --}}
<div x-show="openSearch" x-cloak class="fixed inset-0 z-50 bg-white flex flex-col">
    <div class="h-12 px-4 flex items-center justify-between border-b">
        <span class="text-sm font-medium">Поиск</span>
        <button @click="openSearch = false">✕</button>
    </div>
    <div class="p-4">
        <form action="{{ route('home') }}" method="GET" class="space-y-3">
            <input type="hidden" name="country_id" value="{{ request('country_id') }}">
            <input type="hidden" name="city_id" value="{{ request('city_id') }}">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Искать товары..."
                   class="w-full rounded-lg border-gray-300 focus:ring-indigo-200">
            <button type="submit" class="w-full bg-indigo-600 text-white rounded-lg h-10">Найти</button>
        </form>
    </div>
</div>

{{-- Фильтры --}}
@php
    $countriesAll = \App\Models\Country::orderBy('name')->get();
    $currentCountryId = request('country_id');
    $currentCityId = request('city_id');
@endphp
<div x-show="openFilters" x-cloak class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/30" @click="openFilters = false"></div>
    <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-xl p-4"
         x-data="{
             selectedCountry: '{{ $currentCountryId ?? '' }}',
             selectedCity: '{{ $currentCityId ?? '' }}',
             cities: [],
             loading: false,
             async fetchCities() {
                 this.loading = true;
                 this.cities = [];
                 this.selectedCity = '';
                 if (this.selectedCountry) {
                     try {
                         const res = await fetch(`/countries/${this.selectedCountry}/cities`);
                         const data = await res.json();
                         this.cities = Array.isArray(data) ? data : [];
                     } catch(e) { this.cities = []; }
                 }
                 this.loading = false;
             }
         }"
         x-init="if (selectedCountry) fetchCities()">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold">Фильтры</h3>
            <button @click="openFilters = false">✕</button>
        </div>

        <form action="{{ route('home') }}" method="GET" class="mt-3 space-y-3">
            @if(request('q')) <input type="hidden" name="q" value="{{ request('q') }}"> @endif
            @if(request('category_id')) <input type="hidden" name="category_id" value="{{ request('category_id') }}"> @endif
            @if(request('sort')) <input type="hidden" name="sort" value="{{ request('sort') }}"> @endif

            <!-- Страна -->
            <select name="country_id" class="w-full border rounded-lg p-2"
                    x-model="selectedCountry" @change="fetchCities()">
                <option value="">Все страны</option>
                @foreach($countriesAll as $c)
                    <option value="{{ $c->id }}" @selected($currentCountryId == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>

            <!-- Город -->
            <select name="city_id" class="w-full border rounded-lg p-2"
                    x-model="selectedCity" :disabled="!selectedCountry || loading">
                <option value="">Все города</option>
                <template x-if="loading"><option disabled>Загрузка...</option></template>
                <template x-for="city in cities" :key="city.id">
                    <option :value="city.id" x-text="city.name"
                            :selected="city.id == {{ $currentCityId ?? 'null' }}"></option>
                </template>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 text-white rounded-lg h-10">Применить</button>
                <button type="button" @click="$root.clearFilters()" class="h-10 px-4 border rounded-lg">Сбросить</button>
            </div>
        </form>
    </div>
</div>

{{-- Настройки --}}
@php
    $currency = request('currency', 'MDL');
    $lang = request('lang', 'ru');
@endphp
<div x-show="openSettings" x-cloak class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/30" @click="openSettings = false"></div>
    <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-xl p-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold">Настройки</h3>
            <button @click="openSettings = false">✕</button>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-3">
            <div>
                <div class="text-xs text-gray-500">Валюта</div>
                @foreach (['RUB'=>'₽ RUB', 'MDL'=>'MDL', 'UAH'=>'₴ UAH'] as $code=>$label)
                    <a href="?{{ http_build_query(request()->except('currency') + ['currency' => $code]) }}"
                       class="block px-3 py-2 border rounded-lg {{ $currency === $code ? 'bg-indigo-600 text-white border-indigo-600' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            <div>
                <div class="text-xs text-gray-500">Язык</div>
                @foreach (['ru'=>'🇷🇺 Рус', 'en'=>'🇬🇧 Eng', 'uk'=>'🇺🇦 Укр'] as $code=>$label)
                    <a href="?{{ http_build_query(request()->except('lang') + ['lang' => $code]) }}"
                       class="block px-3 py-2 border rounded-lg {{ $lang === $code ? 'bg-indigo-600 text-white border-indigo-600' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>






{{-- 🌟 Модалка характеристик товара --}}
<div 
    x-show="$store.specs?.open"
    x-transition.opacity
    class="fixed inset-0 bg-black/40 z-[999]"
    @click="$store.specs.open = false"
    x-cloak
>
    <div 
        @click.stop
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in duration-300"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="absolute right-0 top-0 h-full w-full sm:w-[420px] bg-white shadow-2xl p-6 overflow-y-auto"
    >
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Характеристики товара</h2>
            <button @click="$store.specs.open = false">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>

        {{-- Slot со спецификациями --}}
        {{ $specs ?? '' }}

    </div>
</div>
