<div class="lg:hidden bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between sticky top-0 z-40">
    <!-- Лого -->
    <a href="{{ route('home') }}" class="flex items-center gap-2">
        <img src="{{ asset('images/icon.png') }}" alt="WebVitrina" class="h-7 w-auto" />
        <span class="font-semibold text-gray-800 text-sm">WebVitrina</span>
    </a>

    <!-- Иконки -->
    <div class="flex items-center gap-6 text-gray-600">
        <!-- Поиск -->
        <button @click="openSearch = true" class="hover:text-indigo-600" title="Поиск">
            <x-icon name="search" class="h-6 w-6"/>
        </button>

        <!-- Фильтры -->
        <button @click="openFilters = true" class="hover:text-indigo-600" title="Фильтры">
            <x-icon name="filter" class="h-6 w-6"/>
        </button>

        <!-- Настройки -->
        <button @click="openSettings = true" class="hover:text-indigo-600" title="Настройки">
            <x-icon name="settings" class="h-6 w-6"/>
        </button>
        
    </div>
</div>
