<div class="lg:hidden fixed bottom-0 left-0 right-0 z-50">
    {{-- Стильный фон с блюром — ТЕПЕРЬ БЕЗ ОТСТУПОВ СНИЗУ --}}
    <div class="bg-white/95 backdrop-blur-xl border-t border-white/20 shadow-2xl shadow-black/5" style="padding-bottom: env(safe-area-inset-bottom, 0px);">
        <div class="flex justify-around items-center py-2 px-1">
            
{{-- 1. ГЛАВНАЯ --}}
            <a href="{{ route('home') }}" 
               class="relative group flex flex-col items-center justify-center gap-0.5 transition-all duration-300 {{ request()->routeIs('home') ? 'scale-105' : 'hover:scale-105' }}">
                <div class="relative">
                    <div class="absolute inset-0 bg-indigo-100 rounded-full blur-xl opacity-0 transition-opacity duration-300 {{ request()->routeIs('home') ? 'opacity-100' : 'group-hover:opacity-60' }}"></div>
                    <x-icon name="home" 
                            class="relative h-5 w-5 transition-all duration-300 {{ request()->routeIs('home') ? 'text-indigo-600 drop-shadow-sm' : 'text-gray-500 group-hover:text-indigo-500' }}"/>
                </div>
                <span class="text-[10px] font-semibold tracking-tight transition-all duration-300 {{ request()->routeIs('home') ? 'text-indigo-600 scale-105' : 'text-gray-500 group-hover:text-indigo-500' }}">Главная</span>
                @if(request()->routeIs('home'))
                    <div class="absolute -top-1.5 w-8 h-0.5 bg-indigo-500 rounded-full animate-pulse"></div>
                @endif
            </a>

{{-- 2. КАТЕГОРИИ --}}
            <button @click="open = true" 
                    class="relative group flex flex-col items-center justify-center gap-0.5 transition-all duration-300 hover:scale-105">
                <div class="relative">
                    <div class="absolute inset-0 bg-indigo-100 rounded-full blur-xl opacity-0 transition-opacity duration-300 group-hover:opacity-60"></div>
                    <svg class="relative z-10 h-5 w-5 transition-all duration-300 text-gray-500 group-hover:text-indigo-500"
                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 8.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25A2.25 2.25 0 0113.5 8.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                    </svg>
                </div>
                <span class="text-[10px] font-semibold tracking-tight text-gray-500 group-hover:text-indigo-500 transition-colors">Категории</span>
            </button>

{{-- 3. ИЗБРАННОЕ --}}
            <a href="{{ route('favorites.index') }}" 
            class="relative group flex flex-col items-center justify-center gap-0.5 transition-all duration-300 hover:scale-105">
                <div class="relative">
                    {{-- Blur background on hover --}}
                    <div class="absolute inset-0 bg-indigo-100 rounded-full blur-xl opacity-0 transition-opacity duration-300 group-hover:opacity-60"></div>
                    
                    {{-- Иконка сердца --}}
                    <x-icon name="heart" 
                            class="relative h-5 w-5 transition-all duration-300 {{ request()->routeIs('favorites.*') ? 'text-indigo-600 drop-shadow-sm' : 'text-gray-500 group-hover:text-indigo-500 group-hover:scale-110' }}"/>
                    
                    {{-- Подсчет избранного --}}
                    @php
                        try {
                            $favoritesCount = auth()->check() ? (\App\Models\Favorite::where('user_id', auth()->id())->count() ?? 0) : 0;
                        } catch (\Exception $e) {
                            $favoritesCount = 0;
                        }
                    @endphp
                    
                    {{-- Компактный бейдж --}}
                    @if($favoritesCount > 0)
                        <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-bold text-white shadow-sm">
                            {{ min($favoritesCount, 99) }}
                        </span>
                    @endif
                </div>
                
                {{-- Текст --}}
                <span class="text-[10px] font-semibold tracking-tight transition-all duration-300 {{ request()->routeIs('favorites.*') ? 'text-indigo-600' : 'text-gray-500 group-hover:text-indigo-500' }}">
                    Избранное
                </span>
                
                {{-- Активный индикатор сверху --}}
                @if(request()->routeIs('favorites.*'))
                    <div class="absolute -top-1.5 w-8 h-0.5 bg-indigo-500 rounded-full"></div>
                @endif
            </a>

{{-- 4. КОРЗИНА  --}}
            <a href="{{ route('cart.index') }}" 
            class="relative group flex flex-col items-center justify-center gap-1 transition-all duration-300 hover:scale-105">
                
                {{-- Фиксированный контейнер для иконки --}}
                <div class="relative flex items-center justify-center h-6 w-6">
                    {{-- Blur background on hover (синий) --}}
                    <div class="absolute inset-0 bg-indigo-100 rounded-full blur-xl opacity-0 transition-opacity duration-300 group-hover:opacity-60"></div>
                    
                    {{-- Icon с СИНИМИ цветами --}}
                    <svg xmlns="http://www.w3.org/2000/svg" 
                        class="h-5 w-5 transition-all duration-300 {{ request()->routeIs('cart.*') ? 'text-indigo-600 drop-shadow-sm scale-110' : 'text-gray-500 group-hover:text-indigo-500 group-hover:scale-110' }}" 
                        fill="none" 
                        viewBox="0 0 24 24" 
                        stroke="currentColor" 
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" 
                            d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007z" />
                    </svg>
                    
                    {{-- Cart count calculation --}}
                    @php
                        try {
                            $cartCount = 0;
                            if (session()->has('cart')) {
                                $cart = session()->get('cart', []);
                                $cartCount = array_sum(array_column($cart, 'quantity'));
                            }
                        } catch (\Exception $e) {
                            $cartCount = 0;
                        }
                    @endphp
                    
                    {{-- Badge with pulse animation (СИНИЙ) --}}
                    @if($cartCount > 0)
                        {{-- Pulse ring --}}
                        <span class="absolute -top-1 -right-1 inline-flex h-3.5 w-3.5 animate-ping rounded-full bg-indigo-400 opacity-75"></span>
                        {{-- Badge count --}}
                        <span class="absolute -top-1 -right-1 inline-flex h-3.5 w-3.5 items-center justify-center rounded-full bg-indigo-500 text-[8px] font-bold text-white shadow-sm">
                            {{ min($cartCount, 9) }}
                        </span>
                    @endif
                </div>
                
                <span class="text-[10px] font-semibold tracking-tight {{ request()->routeIs('cart.*') ? 'text-indigo-600' : 'text-gray-500 group-hover:text-indigo-500' }}">
                    Корзина
                </span>
                
                @if(request()->routeIs('cart.*'))
                    <div class="absolute -top-1.5 w-8 h-0.5 bg-indigo-500 rounded-full animate-pulse"></div>
                @endif
            </a>

 {{-- 5. ПРОФИЛЬ --}}
            <a href="{{ route('cabinet') }}" 
               class="relative group flex flex-col items-center justify-center gap-0.5 transition-all duration-300 hover:scale-105">
                <div class="relative">
                    <div class="absolute inset-0 bg-indigo-100 rounded-full blur-xl opacity-0 transition-opacity duration-300 group-hover:opacity-60"></div>
                    
                    @php $avatar = auth()->user()->avatar ?? null @endphp
                    
                    @if($avatar && Storage::disk('public')->exists($avatar))
                        <img src="{{ Storage::url($avatar) }}"
                             class="relative h-5 w-5 rounded-full object-cover border-2 transition-all duration-300 {{ request()->routeIs('cabinet') ? 'border-indigo-500 scale-105 shadow-md' : 'border-gray-200 group-hover:border-indigo-400 group-hover:scale-110' }}"
                             alt="Аватар">
                    @else
                        <x-icon name="user" 
                                class="relative h-5 w-5 transition-all duration-300 {{ request()->routeIs('cabinet') ? 'text-indigo-600' : 'text-gray-500 group-hover:text-indigo-500' }}"/>
                    @endif
                </div>
                <span class="text-[10px] font-semibold tracking-tight {{ request()->routeIs('cabinet') ? 'text-indigo-600' : 'text-gray-500 group-hover:text-indigo-500' }}">Профиль</span>
                @if(request()->routeIs('cabinet'))
                    <div class="absolute -top-1.5 w-8 h-0.5 bg-indigo-500 rounded-full animate-pulse"></div>
                @endif
            </a>

        </div>
    </div>
</div>

<style>
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-3px); }
    }
    
    .scale-105 {
        transform: scale(1.05);
    }
    
    .backdrop-blur-xl {
        backdrop-filter: blur(20px);
    }
    
    @keyframes ping {
        75%, 100% {
            transform: scale(2);
            opacity: 0;
        }
    }
    
    .animate-ping {
        animation: ping 0.8s cubic-bezier(0, 0, 0.2, 1) infinite;
    }
    
    .animate-bounce {
        animation: bounce 0.5s ease-in-out;
    }
    
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-2px); }
    }
    
    .animate-pulse {
        animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>