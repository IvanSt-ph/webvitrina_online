
{{-- resources/views/layouts/buyer-layout.blade.php — боковая панель покупателя --}}
<x-app-layout :title="$title ?? 'Личный кабинет'" :hideHeader="true">
  <div class="flex min-h-screen bg-neutral-50 text-gray-800">

    <!-- 🧭 Sidebar -->
    <aside class="hidden md:flex flex-col w-64 bg-white border-r border-gray-100 justify-between fixed left-0 top-0 bottom-0 shadow-sm">
      <div>
        <!-- Логотип -->
        <div class="flex items-center gap-2 px-6 py-6 border-b border-gray-100">
          <img src="{{ asset('images/icon.png') }}" alt="WebVitrina" class="w-8 h-8 rounded-lg shadow-sm">
          <span class="font-semibold text-gray-800 text-sm tracking-tight">WebVitrina</span>
        </div>

        <!-- Навигация -->
        @php
          $active = 'bg-indigo-50 text-indigo-600 font-medium border-l-4 border-indigo-500';
          $link = 'flex items-center gap-2 px-6 py-3 rounded-r-lg transition-all duration-200 hover:bg-indigo-50 hover:text-indigo-600 hover:translate-x-[3px]';
        @endphp
        <nav class="flex flex-col mt-6 text-[17px] font-normal text-gray-700">
          <a href="{{ route('home') }}" class="{{ $link }}">
            <i class="ri-arrow-left-line text-[22px]"></i>
            <span>Вернутся к товарам</span>
          </a>
          <a href="{{ route('cabinet') }}" class="{{ request()->routeIs('cabinet') ? $active : '' }} {{ $link }}">
            <i class="ri-home-5-line text-[22px]"></i>
            <span>Кабинет</span>
          </a>
          <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? $active : '' }} {{ $link }}">
            <i class="ri-shopping-bag-3-line text-[22px]"></i>
            <span>Заказы</span>
          </a>
          <a href="{{ route('favorites.index') }}" class="{{ request()->routeIs('favorites.*') ? $active : '' }} {{ $link }}">
            <i class="ri-heart-line text-[22px]"></i>
            <span>Избранное</span>
          </a>
<a href="{{ route('cart.index') }}" 
   class="{{ request()->routeIs('cart.*') ? $active : '' }} {{ $link }}">

    <div class="relative">
        <i class="ri-shopping-cart-2-line text-[22px]" data-cart-icon></i>

        <!-- 🔥 бейдж количества товаров -->
<span data-cart-count
      class="absolute -top-1.5 -right-2 bg-red-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full
             opacity-0 pointer-events-none transition-none">
</span>

    </div>

    <span>Корзина</span>
</a>

          <a href="{{ route('addresses.index') }}" class="{{ request()->routeIs('addresses.*') ? $active : '' }} {{ $link }}">
            <i class="ri-map-pin-line text-[22px]"></i>
            <span>Адреса доставки</span>
          </a>
<a href="{{ route('buyer.profile') }}" 
   class="{{ request()->routeIs('buyer.profile') ? $active : '' }} {{ $link }}">
  <i class="ri-settings-3-line text-[22px]"></i>
  <span>Настройки</span>
</a>


        </nav>
              <!-- Соц. иконки -->
      <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between text-gray-400 text-lg">
        <a href="#" class="hover:text-indigo-600"><i class="ri-telegram-line"></i></a>
        <a href="#" class="hover:text-indigo-600"><i class="ri-instagram-line"></i></a>
        <a href="#" class="hover:text-indigo-600"><i class="ri-github-line"></i></a>
      </div>
      </div>

      



      <!-- Аккаунт покупателя -->
<div class="px-6 py-4 border-t border-gray-100">
    <div class="flex items-start gap-3">

        {{-- Аватар --}}
        <div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 flex items-center justify-center">
            @php $avatar = auth()->user()->avatar; @endphp

            @if ($avatar && Storage::disk('public')->exists($avatar))
                <img src="{{ asset('storage/'.$avatar) }}" alt="avatar" class="w-full h-full object-cover">
            @else
                <span class="text-base font-semibold text-gray-600">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>
            @endif
        </div>

        <div class="flex flex-col leading-tight">
            <span class="font-semibold text-gray-800 text-sm">
                {{ auth()->user()->name }}
            </span>

            <span class="text-xs text-gray-500">{{ auth()->user()->email }}</span>

            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button class="text-xs text-red-500 hover:text-red-600">
                    Выйти
                </button>
            </form>
        </div>

    </div>
</div>

    </aside>

    

    <!-- 🌤 Контент -->
    <main class="flex-1 md:ml-64 p-2 md:p-10 bg-neutral-50">
      {{ $slot }}
    </main>
  
  </div>

  

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
</x-app-layout>

