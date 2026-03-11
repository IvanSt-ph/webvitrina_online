<div 
  x-data="{ openOrdersMenu: false, openCabinetMenu: false }"
  class="lg:hidden fixed bottom-0 left-0 right-0 z-50 backdrop-blur-md bg-white/90 border-t border-gray-200 shadow-xl">

  <nav class="flex justify-around items-center h-16 text-xs font-medium">

    <!-- В магазин (главная сайта) -->
    <a href="{{ url('/') }}" 
       class="flex flex-col items-center gap-0.5 transition-all duration-200 {{ request()->is('/') ? 'text-indigo-600' : 'text-gray-500 hover:text-indigo-500' }}">
        <i class="ri-store-3-line text-xl"></i>
        <span>В магазин</span>
    </a>

    <!-- Товары -->
    <a href="{{ route('seller.products.index') }}" 
       class="flex flex-col items-center gap-0.5 transition-all duration-200 {{ request()->routeIs('seller.products.*') ? 'text-indigo-600' : 'text-gray-500 hover:text-indigo-500' }}">
        <i class="ri-store-2-line text-xl"></i>
        <span>Товары</span>
    </a>

    <!-- Добавить (центральная) -->
    <a href="{{ route('seller.products.create') }}" 
       class="relative flex flex-col items-center justify-center -mt-3">
        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white shadow-lg border-4 border-white">
            <i class="ri-add-line text-2xl"></i>
        </div>
        <span class="text-[11px] text-gray-500 mt-1">Добавить</span>
    </a>

    <!-- Заказы (только заказы) -->
    <button 
      @click="openOrdersMenu = !openOrdersMenu; openCabinetMenu = false"
      class="flex flex-col items-center gap-0.5 transition-all duration-200 {{ request()->routeIs('seller.orders.*') ? 'text-indigo-600' : 'text-gray-500 hover:text-indigo-500' }} relative">
        <i class="ri-file-list-3-line text-xl"></i>
        <span>Заказы</span>
        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-semibold px-1.5 rounded-full">5</span>
    </button>

    <!-- Кабинет (меню) -->
    <button 
      @click="openCabinetMenu = !openCabinetMenu; openOrdersMenu = false"
      class="flex flex-col items-center gap-0.5 transition-all duration-200 {{ request()->routeIs('cabinet') || request()->routeIs('seller.finance.*') || request()->routeIs('seller.analytics.*') ? 'text-indigo-600' : 'text-gray-500 hover:text-indigo-500' }}">
        <i class="ri-user-settings-line text-xl"></i>
        <span>Кабинет</span>
    </button>

  </nav>

  <!-- Меню заказов (только заказы) -->
  <div 
    x-show="openOrdersMenu"
    @click.away="openOrdersMenu = false"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="translate-y-full opacity-0"
    class="fixed bottom-20 left-0 right-0 bg-white rounded-t-2xl p-5 border-t border-gray-200 z-40 shadow-xl">

    <div class="flex items-center justify-between mb-4">
      <h3 class="text-base font-semibold text-gray-800">Заказы</h3>
      <button @click="openOrdersMenu = false" class="text-gray-400 hover:text-gray-600">
        <i class="ri-close-line text-xl"></i>
      </button>
    </div>

    <ul class="space-y-3 text-sm text-gray-700">
      <li><a href="{{ route('seller.orders.index') }}" class="flex items-center gap-2 hover:text-indigo-600"><i class="ri-list-unordered text-lg"></i> Все заказы</a></li>
      <li><a href="{{ route('seller.orders.index') }}?status=new" class="flex items-center gap-2 hover:text-indigo-600"><i class="ri-time-line text-lg"></i> Новые</a></li>
      <li><a href="{{ route('seller.orders.index') }}?status=delivery" class="flex items-center gap-2 hover:text-indigo-600"><i class="ri-truck-line text-lg"></i> В пути</a></li>
      <li><a href="{{ route('seller.orders.index') }}?status=completed" class="flex items-center gap-2 hover:text-indigo-600"><i class="ri-check-double-line text-lg"></i> Завершённые</a></li>
    </ul>
  </div>

  <!-- Меню кабинета (кабинет, финансы, аналитика, выход) -->
  <div 
    x-show="openCabinetMenu"
    @click.away="openCabinetMenu = false"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="translate-y-full opacity-0"
    class="fixed bottom-20 left-0 right-0 bg-white rounded-t-2xl p-5 border-t border-gray-200 z-40 shadow-xl">

    <div class="flex items-center justify-between mb-4">
      <h3 class="text-base font-semibold text-gray-800">Меню кабинета</h3>
      <button @click="openCabinetMenu = false" class="text-gray-400 hover:text-gray-600">
        <i class="ri-close-line text-xl"></i>
      </button>
    </div>

    <ul class="space-y-3 text-sm text-gray-700">
      <!-- Кабинет (главная продавца) -->
      <li><a href="{{ route('cabinet') }}" class="flex items-center gap-2 hover:text-indigo-600"><i class="ri-dashboard-line text-lg"></i> Кабинет</a></li>
      
      <!-- Финансы -->
      <li><a href="{{ route('seller.finance.index') }}" class="flex items-center gap-2 hover:text-indigo-600"><i class="ri-wallet-3-line text-lg"></i> Финансы</a></li>
      
      <!-- Аналитика -->
      <li><a href="{{ route('seller.analytics.index') }}" class="flex items-center gap-2 hover:text-indigo-600"><i class="ri-bar-chart-2-line text-lg"></i> Аналитика</a></li>
      
      <!-- Разделитель -->
      <li class="border-t border-gray-100 pt-3 mt-3"></li>
      
      <!-- Кнопка выхода -->
      <li>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="flex items-center gap-2 text-red-500 hover:text-red-700 w-full text-left transition-colors duration-200">
            <i class="ri-logout-box-r-line text-lg"></i>
            <span class="text-sm font-medium">Выйти из аккаунта</span>
          </button>
        </form>
      </li>
    </ul>
  </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">