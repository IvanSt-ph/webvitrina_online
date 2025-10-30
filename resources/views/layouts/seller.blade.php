{{-- resources/views/components/seller-layout.blade.php --}}
<x-app-layout :title="$title ?? 'Панель продавца'" :hideHeader="true">
  <div class="flex min-h-screen bg-neutral-50 text-gray-800">

    <!-- 🧭 Sidebar (слева) -->
    <aside class="hidden md:flex flex-col w-64 bg-white border-r border-gray-100 justify-between fixed left-0 top-0 bottom-0 shadow-sm">
      <div>
        <!-- Логотип / бренд -->
        <div class="flex items-center gap-2 px-6 py-6 border-b border-gray-100">
          <a href="{{ route('home') }}" class="flex items-center gap-2">
            <img src="{{ asset('images/icon.png') }}" class="w-8 h-8 rounded-lg shadow-sm" alt="WebVitrina">
            <span class="font-semibold text-gray-900 text-sm tracking-tight">WebVitrina Seller</span>
          </a>
        </div>

        @php
          $active = 'bg-indigo-50 text-indigo-600 font-medium border-l-4 border-indigo-500';
          $link   = 'flex items-center gap-2 px-6 py-3 rounded-r-lg transition-all duration-200 hover:bg-indigo-50 hover:text-indigo-600 hover:translate-x-[3px]';
        @endphp

        <!-- Навигация -->
        <nav class="flex flex-col mt-6 text-[16px] font-normal text-gray-700">
          <a href="{{ route('seller.cabinet') }}"
             class="{{ request()->routeIs('seller.cabinet') ? $active : '' }} {{ $link }}">
            <i class="ri-home-5-line text-[22px]"></i>
            <span>Главная</span>
          </a>

          <a href="{{ route('seller.products.index') }}"
             class="{{ request()->routeIs('seller.products.*') ? $active : '' }} {{ $link }}">
            <i class="ri-box-3-line text-[22px]"></i>
            <span>Товары</span>
          </a>

          <a href="{{ route('seller.orders.index') }}"
             class="{{ request()->routeIs('seller.orders.*') ? $active : '' }} {{ $link }}">
            <i class="ri-shopping-bag-3-line text-[22px]"></i>
            <span>Заказы</span>
          </a>

          <a href="{{ route('seller.finance.index') }}"
             class="{{ request()->routeIs('seller.finance.*') ? $active : '' }} {{ $link }}">
            <i class="ri-cash-line text-[22px]"></i>
            <span>Финансы</span>
          </a>

          <a href="{{ route('seller.analytics.index') }}"
             class="{{ request()->routeIs('seller.analytics.*') ? $active : '' }} {{ $link }}">
            <i class="ri-line-chart-line text-[22px]"></i>
            <span>Аналитика</span>
          </a>

          <a href="{{ route('profile.edit') }}"
             class="{{ request()->routeIs('profile.*') ? $active : '' }} {{ $link }}">
            <i class="ri-settings-3-line text-[22px]"></i>
            <span>Профиль</span>
          </a>
        </nav>
      </div>

      <!-- Низ сайдбара -->
      <div class="px-6 py-4 border-t border-gray-100">
        <div class="flex items-center gap-3">
          <img
            src="{{ auth()->user()->avatar ? asset('storage/'.auth()->user()->avatar) : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name ?? 'U') }}"
            class="w-9 h-9 rounded-full border border-gray-200" alt="avatar">
          <div class="text-sm">
            <div class="font-semibold text-gray-800">{{ auth()->user()->name ?? 'Продавец' }}</div>
            <div class="text-gray-400">{{ auth()->user()->email }}</div>
            {{-- кнопка выхода из аккаунта --}}
            <form method="POST" action="{{ route('logout') }}" class="mt-4">
              @csrf
              <button type="submit" class="text-sm text-red-600 hover:text-red-800 transition">
                Выйти
              </button>
            </form>
          </div>
        </div>
      </div>
    </aside>

<!-- 🌤 Контент -->
<main class="flex-1 md:ml-64 pl-4 pr-6 py-6 bg-neutral-50 min-h-screen">
  {{ $slot }}
  <footer class="text-center text-xs text-gray-400 pt-6 border-t mt-10">
    © {{ date('Y') }} WebVitrina — Панель продавца
  </footer>
</main>


  </div>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
</x-app-layout>
