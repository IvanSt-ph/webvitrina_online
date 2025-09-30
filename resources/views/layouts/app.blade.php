<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
<div
    class="min-h-screen bg-gray-100"
    x-data="{
        open: false,
        openSearch: false,
        openFilters: false,
        openSettings: false,
        clearFilters() {
            const url = new URL(window.location.href);
            url.searchParams.delete('country_id');
            url.searchParams.delete('city_id');
            window.location.href = url.toString();
        }
    }"
>

    {{-- Верхнее меню (десктоп) --}}
    @include('layouts.navigation')

    {{-- ====================== МОБИЛЬНЫЙ ВЕРХНИЙ БАР ====================== --}}
    <div class="lg:hidden bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between sticky top-0 z-40">
        <!-- Лого -->
        <a href="{{ route('home') }}" class="flex items-center gap-2">
            <img src="{{ asset('images/logo.png') }}" alt="WebVitrina" class="h-7 w-auto" />
            <span class="font-semibold text-gray-800 text-sm">WebVitrina</span>
        </a>

        <!-- Иконки -->
        <div class="flex items-center gap-4 text-gray-600">
            <!-- Поиск -->
            <button @click="openSearch = true" class="hover:text-indigo-600" title="Поиск">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 110-15 7.5 7.5 0 010 15z"/>
                </svg>
            </button>
            <!-- Фильтры -->
            <button @click="openFilters = true" class="hover:text-indigo-600" title="Фильтры">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M6 12h12M9 19h6"/>
                </svg>
            </button>
            <!-- Настройки -->
            <button @click="openSettings = true" class="hover:text-indigo-600" title="Настройки">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11.983 3.5a1.5 1.5 0 012.53 0l.544.94a1.5 1.5 0 001.221.75l1.086.09a1.5 1.5 0 011.34 1.34l.09 1.086a1.5 1.5 0 00.75 1.22l.94.545a1.5 1.5 0 010 2.53l-.94.545a1.5 1.5 0 00-.75 1.221l-.09 1.086a1.5 1.5 0 01-1.34 1.34l-1.086.09a1.5 1.5 0 00-1.221.75l-.545.94a1.5 1.5 0 01-2.53 0l-.544-.94a1.5 1.5 0 00-1.221-.75l-1.086-.09a1.5 1.5 0 01-1.34-1.34l-.09-1.086a1.5 1.5 0 00-.75-1.221l-.94-.545a1.5 1.5 0 010-2.53l.94-.545a1.5 1.5 0 00.75-1.22l.09-1.087a1.5 1.5 0 011.34-1.34l1.086-.09a1.5 1.5 0 001.221-.75l.545-.94z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </button>
        </div>
    </div>
    {{-- ====================== /МОБИЛЬНЫЙ ВЕРХНИЙ БАР ====================== --}}

    {{-- Заголовок --}}
    @isset($header)
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
    @endisset

    {{-- Контент --}}
    <main class="pb-16">
        {{ $slot }}
    </main>

    {{-- ====================== НИЖНЯЯ ПАНЕЛЬ ====================== --}}
    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50">
        <div class="flex justify-around items-center h-14">
            <!-- Домой -->
            <a href="{{ route('home') }}" 
               class="flex flex-col items-center {{ request()->routeIs('home') ? 'text-indigo-600' : 'text-gray-600' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9v9a3 3 0 01-3 3H6a3 3 0 01-3-3v-9z"/>
                </svg>
                <span class="text-xs">Главная</span>
            </a>

            <!-- Категории -->
            <button @click="open = true" 
                    class="flex flex-col items-center {{ request()->routeIs('category.*') ? 'text-indigo-600' : 'text-gray-600' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <span class="text-xs">Категории</span>
            </button>

            <!-- Избранное -->
            <a href="{{ route('favorites.index') }}" 
               class="flex flex-col items-center {{ request()->routeIs('favorites.*') ? 'text-pink-500' : 'text-gray-600' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 
                             2 6 4 4 6.5 4c1.74 0 3.41 1 4.22 2.44C11.09 5 
                             12.76 4 14.5 4 17 4 19 6 19 8.5c0 3.78-3.4 
                             6.86-8.55 11.54L12 21.35z"/>
                </svg>
                <span class="text-xs">Избранное</span>
            </a>

            <!-- Корзина -->
            <a href="{{ route('cart.index') }}" 
               class="flex flex-col items-center {{ request()->routeIs('cart.*') ? 'text-indigo-600' : 'text-gray-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" 
                         stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" 
                              d="m20.25 7.5-.625 10.632a2.25 2.25 
                                 0 0 1-2.247 2.118H6.622a2.25 2.25 
                                 0 0 1-2.247-2.118L3.75 7.5M10 
                                 11.25h4M3.375 7.5h17.25c.621 
                                 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 
                                 0-1.125.504-1.125 1.125v1.5c0 
                                 .621.504 1.125 1.125 1.125Z"/>
                    </svg>
                <span class="text-xs">Корзина</span>
            </a>

            <!-- Профиль -->
            <a href="{{ route('cabinet') }}" 
               class="flex flex-col items-center {{ request()->routeIs('cabinet') ? 'text-indigo-600' : 'text-gray-600' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 
                             2.3-5 5 2.3 5 5 5zm0 
                             2c-3.3 0-10 1.7-10 
                             5v3h20v-3c0-3.3-6.7-5-10-5z"/>
                </svg>
                <span class="text-xs">Профиль</span>
            </a>
        </div>
    </div>


    {{-- ====================== /НИЖНЯЯ ПАНЕЛЬ ====================== --}}

    {{-- Боковое меню категорий --}}
    @include('profile.partials.category-menu')

    {{-- ====================== МОДАЛКИ ====================== --}}

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

    {{-- Фильтры (динамическая загрузка городов, без автосабмита) --}}
    @php
        $countriesAll = \App\Models\Country::orderBy('name')->get();
        $currentCountryId = request('country_id');
        $currentCityId = request('city_id');
    @endphp
    <div x-show="openFilters" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/30" @click="openFilters = false"></div>

        <div
            class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-xl p-4"
            x-data="{
                selectedCountry: '{{ $currentCountryId ?? '' }}',
                selectedCity: '{{ $currentCityId ?? '' }}',
                cities: [],
                loading: false,
                async fetchCities() {
                    this.loading = true;
                    this.cities = [];
                    this.selectedCity = ''; // сбрасываем город при смене страны
                    if (this.selectedCountry) {
                        try {
                            const res = await fetch(`/countries/${this.selectedCountry}/cities`);
                            const data = await res.json();
                            this.cities = Array.isArray(data) ? data : [];
                        } catch(e) {
                            this.cities = [];
                        }
                    }
                    this.loading = false;
                }
            }"
            x-init="if (selectedCountry) fetchCities()"
        >
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold">Фильтры</h3>
                <button @click="openFilters = false">✕</button>
            </div>

          <form action="{{ route('home') }}" method="GET" class="mt-3 space-y-3">
    {{-- сохраняем строку поиска --}}
    @if(request('q'))
        <input type="hidden" name="q" value="{{ request('q') }}">
    @endif

    {{-- сохраняем выбранную категорию --}}
    @if(request('category_id'))
        <input type="hidden" name="category_id" value="{{ request('category_id') }}">
    @endif

    {{-- сохраняем сортировку --}}
    @if(request('sort'))
        <input type="hidden" name="sort" value="{{ request('sort') }}">
    @endif

    <!-- Страна -->
    <div>
        <select name="country_id"
                class="w-full border rounded-lg p-2"
                x-model="selectedCountry"
                @change="fetchCities()">
            <option value="">Все страны</option>
            @foreach($countriesAll as $c)
                <option value="{{ $c->id }}" @selected($currentCountryId == $c->id)>
                    {{ $c->name }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Город -->
    <div>
        <select name="city_id"
                class="w-full border rounded-lg p-2"
                x-model="selectedCity"
                :disabled="!selectedCountry || loading">
            <option value="">Все города</option>

            <!-- Плейсхолдер загрузки -->
            <template x-if="loading">
                <option disabled>Загрузка...</option>
            </template>

            <!-- Список городов после выбора страны -->
            <template x-for="city in cities" :key="city.id">
                <option :value="city.id" x-text="city.name"
                        :selected="city.id == {{ $currentCityId ?? 'null' }}"></option>
            </template>
        </select>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="flex-1 bg-indigo-600 text-white rounded-lg h-10">
            Применить
        </button>
        <button type="button" @click="$root.clearFilters()" class="h-10 px-4 border rounded-lg">
            Сбросить
        </button>
    </div>
</form>

        </div>
    </div>

    
{{-- Настройки --}}
<div x-show="openSettings" x-cloak class="fixed inset-0 z-50">
    <div class="absolute inset-0 bg-black/30" @click="openSettings = false"></div>
    <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-xl p-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold">Настройки</h3>
            <button @click="openSettings = false">✕</button>
        </div>

        @php
            // Значения из запроса + дефолты (можешь поменять дефолты при желании)
            $currency = request('currency', 'MDL'); // дефолт MDL
            $lang = request('lang', 'ru');          // дефолт ru
        @endphp

        <div class="mt-3 grid grid-cols-2 gap-3">
            <div>
                <div class="text-xs text-gray-500">Валюта</div>

                <a href="?{{ http_build_query(request()->except('currency') + ['currency' => 'RUB']) }}"
                   class="block px-3 py-2 border rounded-lg {{ $currency === 'RUB' ? 'bg-indigo-600 text-white border-indigo-600' : '' }}">
                    ₽ RUB
                </a>

                <a href="?{{ http_build_query(request()->except('currency') + ['currency' => 'MDL']) }}"
                   class="block px-3 py-2 border rounded-lg {{ $currency === 'MDL' ? 'bg-indigo-600 text-white border-indigo-600' : '' }}">
                    MDL
                </a>

                <a href="?{{ http_build_query(request()->except('currency') + ['currency' => 'UAH']) }}"
                   class="block px-3 py-2 border rounded-lg {{ $currency === 'UAH' ? 'bg-indigo-600 text-white border-indigo-600' : '' }}">
                    ₴ UAH
                </a>
            </div>

            <div>
                <div class="text-xs text-gray-500">Язык</div>

                <a href="?{{ http_build_query(request()->except('lang') + ['lang' => 'ru']) }}"
                   class="block px-3 py-2 border rounded-lg {{ $lang === 'ru' ? 'bg-indigo-600 text-white border-indigo-600' : '' }}">
                    🇷🇺 Рус
                </a>

                <a href="?{{ http_build_query(request()->except('lang') + ['lang' => 'en']) }}"
                   class="block px-3 py-2 border rounded-lg {{ $lang === 'en' ? 'bg-indigo-600 text-white border-indigo-600' : '' }}">
                    🇬🇧 Eng
                </a>

                <a href="?{{ http_build_query(request()->except('lang') + ['lang' => 'uk']) }}"
                   class="block px-3 py-2 border rounded-lg {{ $lang === 'uk' ? 'bg-indigo-600 text-white border-indigo-600' : '' }}">
                    🇺🇦 Укр
                </a>
            </div>
        </div>
    </div>
</div>


</div>
</body>
</html>







{{-- необходимые изменения в navigation.blade.php ниже: --}}
{{--

1) Разобратся с икнонкаими (все иконки проверить и заменить на)
2)Сделать чтобы выделялось активное меню (цветом или еще как-то)
3)Сделвать выделение выбранной валюты и языка в настройках



 --}}

