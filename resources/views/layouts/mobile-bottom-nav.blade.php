<div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50">
    <div class="flex justify-around items-center h-14">
        <!-- Домой -->
        <a href="{{ route('home') }}" 
           class="flex flex-col items-center {{ request()->routeIs('home') ? 'text-indigo-600' : 'text-gray-600' }}">
            <x-icon name="home" class="h-6 w-6"/>
            <span class="text-xs">Главная</span>
        </a>

        <!-- Категории -->
        <button @click="open = true" 
                class="flex flex-col items-center {{ request()->routeIs('category.*') ? 'text-indigo-600' : 'text-gray-600' }}">
            <x-icon name="menu" class="h-6 w-6"/>
            <span class="text-xs">Категории</span>
        </button>

        <!-- Избранное -->
        <a href="{{ route('favorites.index') }}" 
           class="flex flex-col items-center {{ request()->routeIs('favorites.*') ? 'text-pink-500' : 'text-gray-600' }}">
            <x-icon name="heart" class="h-6 w-6"/>
            <span class="text-xs">Избранное</span>
        </a>

        <!-- Корзина -->
        <a href="{{ route('cart.index') }}" 
           class="flex flex-col items-center {{ request()->routeIs('cart.*') ? 'text-indigo-600' : 'text-gray-600' }}">
            <x-icon name="cart" class="h-6 w-6"/>
            <span class="text-xs">Корзина</span>
        </a>

        <!-- Профиль -->
        <a href="{{ route('cabinet') }}" 
           class="flex flex-col items-center {{ request()->routeIs('cabinet') ? 'text-indigo-600' : 'text-gray-600' }}">
            <x-icon name="user" class="h-6 w-6"/>
            <span class="text-xs">Профиль</span>
        </a>
    </div>
</div>
