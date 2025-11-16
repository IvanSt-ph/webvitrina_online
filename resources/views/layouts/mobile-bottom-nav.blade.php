<div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg z-50">
    <div class="flex justify-around items-center h-14">

        <!-- Главная -->
        <a href="{{ route('home') }}" 
           class="flex flex-col items-center {{ request()->routeIs('home') ? 'text-indigo-600' : 'text-gray-600' }}">
            <x-icon name="home" class="h-6 w-6"/>
            <span class="text-xs">Главная</span>
        </a>

        <!-- Категории (кнопка, открывающая меню) -->
        <button 
            @click="open = true" 
            class="flex flex-col items-center {{ request()->routeIs('category.*') ? 'text-indigo-600' : 'text-gray-600' }}">
            <x-icon name="menu" class="h-6 w-6"/>
            <span class="text-xs">Категории</span>
        </button>

        <!-- Акции -->
        <a href="#" class="flex flex-col items-center text-gray-600">
            <img src="/images/icons/sale.png" class="h-6 w-6" alt="">
            <span class="text-xs">Акции</span>
        </a>



        <!-- Корзина -->
        <a href="{{ route('cart.index') }}" 
           class="flex flex-col items-center {{ request()->routeIs('cart.*') ? 'text-indigo-600' : 'text-gray-600' }}">
               <img src="/images/icons/cart.png" class="h-6 w-6" alt="">
            <span class="text-xs">Корзина</span>
        </a>

        <!-- Профиль (аватар) -->
        <a href="{{ route('cabinet') }}" 
           class="flex flex-col items-center {{ request()->routeIs('cabinet') ? 'text-indigo-600' : 'text-gray-600' }}">

            @php $avatar = auth()->user()->avatar ?? null @endphp

            @if($avatar && Storage::disk('public')->exists($avatar))
                <img src="{{ asset('storage/'.$avatar) }}"
                     class="w-6 h-6 rounded-full object-cover border border-gray-300"/>
            @else
                <x-icon name="user" class="h-6 w-6"/>
            @endif

            <span class="text-xs">Профиль</span>
        </a>

    </div>
</div>
