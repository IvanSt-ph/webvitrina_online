<x-app-layout :title="$title ?? 'Панель продавца'" :hideHeader="true">

  <div class="min-h-screen bg-white text-gray-800">

    <!-- 🔝 Верхняя панель -->
    <header class="border-b border-gray-200 bg-white sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-6">
          <a href="{{ route('home') }}" class="flex items-center gap-2">
            <img src="{{ asset('images/logo.png') }}" class="w-7 h-7" alt="WebVitrina">
            <span class="font-semibold text-gray-900 text-sm">WebVitrina Seller</span>
          </a>

          <!-- Навигация -->
          <nav class="hidden sm:flex items-center gap-6 text-sm font-medium text-gray-600">
            <a href="{{ route('cabinet') }}" 
               class="hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-500 transition">
              Главная
            </a>
            <a href="{{ route('seller.products.index') }}"
               class="{{ request()->routeIs('seller.products.*') ? 'text-indigo-600 border-b-2 border-indigo-500 font-semibold' : 'hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-500 transition' }}">
              Товары
            </a>
            <a href="{{ route('seller.orders.index') }}"
               class="{{ request()->routeIs('seller.orders.*') ? 'text-indigo-600 border-b-2 border-indigo-500 font-semibold' : 'hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-500 transition' }}">
              Заказы
            </a>
            <a href="{{ route('seller.finance.index') }}"
               class="{{ request()->routeIs('seller.finance.*') ? 'text-indigo-600 border-b-2 border-indigo-500 font-semibold' : 'hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-500 transition' }}">
              Финансы
            </a>
            <a href="{{ route('seller.analytics.index') }}"
               class="{{ request()->routeIs('seller.analytics.*') ? 'text-indigo-600 border-b-2 border-indigo-500 font-semibold' : 'hover:text-indigo-600 border-b-2 border-transparent hover:border-indigo-500 transition' }}">
              Аналитика
            </a>
          </nav>
        </div>

        <div class="flex items-center gap-3">
          <div class="text-sm text-gray-700">{{ auth()->user()->name ?? 'Продавец' }}</div>
          <img src="{{ auth()->user()->avatar ? asset('storage/'.auth()->user()->avatar) : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name ?? 'U') }}"
               class="w-9 h-9 rounded-full border border-gray-200" alt="avatar">
        </div>
      </div>
    </header>

    <!-- 🌤 Основной контент -->
    <main class="max-w-7xl mx-auto px-6 py-10 space-y-10">
      {{ $slot }}
    </main>

    <!-- FOOTER -->
    <footer class="text-center text-xs text-gray-400 pt-6 border-t">
      © {{ date('Y') }} WebVitrina — Панель продавца
    </footer>

  </div>

</x-app-layout>
