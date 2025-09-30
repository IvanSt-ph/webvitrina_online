<nav class="bg-white border-b border-gray-100">
    <div class="w-full mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Основная линия хедера -->
        <div class="hidden lg:flex items-center h-16 gap-6">

            <!-- ========== Левая часть: Логотип + Категории ========== -->
            <div class="flex items-center gap-4 flex-shrink-0">
                <!-- Логотип -->
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/logo.png') }}" alt="WebVitrina" class="h-9 w-auto" />
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

            <!-- ========== Центр: Поиск + выбор страны/города ========== -->
            <div class="flex-1 flex justify-center items-center gap-3">
                <!-- Поле поиска -->
                <form action="{{ route('home') }}" method="GET" class="w-full max-w-2xl">
                    <div class="relative">
                        <input type="text" name="q" value="{{ request('q') }}"
                               placeholder="Искать товары..."
                               class="w-full rounded-xl border-gray-300 pr-10 
                                      focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                        <!-- Кнопка поиска -->
                        <button type="submit" class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700">
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

                <!-- Фильтры: страна + город -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    @php
                        use App\Models\Country;
                        use App\Models\City;
                        $countries = Country::orderBy('name')->get();
                        $currentCountry = request('country_id');
                        $currentCity = request('city_id');
                    @endphp

                    <!-- Флаг -->
                    @if($currentCountry)
                        @php $countryObj = $countries->firstWhere('id', $currentCountry); @endphp
                        @if($countryObj && $countryObj->slug)
                            <img src="{{ asset('flags/' . $countryObj->slug . '.png') }}" alt="{{ $countryObj->name }}" class="h-6 w-6">
                        @else
                            <img src="{{ asset('flags/all.png') }}" alt="Все страны" class="h-6 w-6">
                        @endif
                    @else
                        <img src="{{ asset('flags/all.png') }}" alt="Все страны" class="h-6 w-6">
                    @endif

                    <!-- Форма фильтрации -->
                    <form action="{{ route('home') }}" method="GET" class="flex gap-2">
                        <input type="hidden" name="q" value="{{ request('q') }}">

                        <!-- Страна -->
                        <select name="country_id" id="country-select" onchange="this.form.submit()"
                                class="border-gray-300 rounded-lg p-2 
                                       focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">Все страны</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}" {{ $currentCountry == $country->id ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                            @endforeach
                        </select>

                        <!-- Город -->
                        <select name="city_id" id="city-select" onchange="this.form.submit()"
                                class="border-gray-300 rounded-lg lg:w-36 p-2 
                                       focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">Города</option>
                            @if($currentCountry)
                                @foreach(City::where('country_id', $currentCountry)->orderBy('name')->get() as $city)
                                    <option value="{{ $city->id }}" {{ $currentCity == $city->id ? 'selected' : '' }}>
                                        {{ $city->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </form>
                </div>
            </div>

            <!-- ========== Правая часть: Избранное / Корзина / Валюта / Язык / Аккаунт ========== -->
            <div class="flex items-center justify-end gap-4 flex-shrink-0">

                <!-- Избранное -->
                <a href="{{ route('favorites.index') }}" class="flex items-center justify-center w-6 h-6 text-gray-600 hover:text-red-500" title="Избранное">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" 
                         stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" 
                              d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 
                                 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 
                                 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                    </svg>
                </a>

                <!-- Корзина -->
                <a href="{{ route('cart.index') }}" class="flex items-center justify-center w-6 h-6 text-gray-600 hover:text-indigo-600" title="Корзина">
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
                </a>

                <!-- Валюта -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center justify-center w-6 h-6 text-gray-600 hover:text-indigo-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
</svg>

                    </button>
                    <div x-show="open" @click.away="open = false"
                         class="absolute right-0 mt-2 w-20 bg-white border rounded shadow text-sm">
                        <a href="?currency=RUB" class="block px-2 py-1 hover:bg-gray-100">₽ RUB</a>
                        <a href="?currency=MDL" class="block px-2 py-1 hover:bg-gray-100">MDL</a>
                        <a href="?currency=UAH" class="block px-2 py-1 hover:bg-gray-100">₴ UAH</a>
                    </div>
                </div>

                <!-- Язык -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center justify-center w-6 h-6 text-gray-600 hover:text-indigo-600">
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
  <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
</svg>

                    </button>
                    <div x-show="open" @click.away="open = false"
                         class="absolute right-0 mt-2 w-24 bg-white border rounded shadow text-sm">
                        <a href="?lang=ru" class="block px-2 py-1 hover:bg-gray-100">🇷🇺 Рус</a>
                        <a href="?lang=en" class="block px-2 py-1 hover:bg-gray-100">🇬🇧 Eng</a>
                        <a href="?lang=uk" class="block px-2 py-1 hover:bg-gray-100">🇺🇦 Укр</a>
                    </div>
                </div>

                <!-- Аккаунт -->
                @auth
                    <x-dropdown align="right" width="56">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-2 py-1 text-sm leading-4 rounded-md 
                                           text-gray-600 bg-white hover:text-gray-900">
                                <span class="hidden md:inline">Привет, {{ auth()->user()->name }}</span>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('cabinet')">Личный кабинет</x-dropdown-link>
                            <x-dropdown-link :href="route('profile.edit')">Редактировать профиль</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" 
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    Выйти
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900">Войти</a>
                    <a href="{{ route('register') }}" class="text-sm text-gray-600 hover:text-gray-900">Регистрация</a>
                @endauth
            </div>
        </div>
    </div>
</nav>
