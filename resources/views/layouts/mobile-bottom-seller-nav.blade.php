@once('seller-bottom-nav-geometry')
  <style>
    :root {
      --seller-bottom-nav-height: 4.5rem;
      --seller-bottom-nav-offset: calc(var(--seller-bottom-nav-height) + env(safe-area-inset-bottom, 0px));
    }

    .wv-seller-dock {
      background: #fff;
      border-top: 1px solid #e2e8f0;
      border-radius: 0;
      box-shadow: 0 -6px 24px -12px rgb(15 23 42 / 16%);
    }
    .wv-seller-dock > nav {
      position: relative;
      z-index: 50;
      background: #fff;
      border-radius: inherit;
    }
    .wv-seller-dock > nav > a, .wv-seller-dock > nav > button {
      gap: 4px;
      border-radius: 12px;
      color: #64748b;
      transition: color 180ms ease, background-color 180ms ease;
      -webkit-tap-highlight-color: transparent;
    }
    .wv-seller-dock > nav > .text-indigo-600,
    .wv-seller-dock > nav > [aria-expanded="true"] { color: #4f46e5; }
    .wv-seller-dock > nav > a > i, .wv-seller-dock > nav > button > i {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 26px;
      border-radius: 10px;
      font-size: 22px;
      line-height: 26px;
      transition: background-color 180ms ease;
    }
    .wv-seller-dock > nav > .text-indigo-600 > i,
    .wv-seller-dock > nav > [aria-expanded="true"] > i { background: #eef2ff; }
    .wv-seller-dock > nav > a:hover > i,
    .wv-seller-dock > nav > button:hover > i { background: #eef2ff; }
    .wv-seller-dock > nav > a:active,
    .wv-seller-dock > nav > button:active { background: #f1f5f9; }
    .wv-seller-dock > nav > a > span:not(.absolute),
    .wv-seller-dock > nav > button > span {
      font-size: 11px;
      line-height: 14px;
      font-weight: 600;
      white-space: nowrap;
    }
    .wv-seller-dock > nav .absolute {
      top: calc((var(--seller-bottom-nav-height) - 44px) / 2 - 3px);
      right: auto;
      left: calc(50% + 6px);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 15px;
      height: 15px;
      padding: 0 3px;
      background: #ef4444;
      color: #fff;
      font-size: 9px;
      line-height: 15px;
      box-shadow: 0 0 0 2px #fff;
    }
    .wv-seller-dock a:focus-visible, .wv-seller-dock button:focus-visible {
      outline: 2px solid #4f46e5;
      outline-offset: -2px;
    }
    .wv-seller-dock > [id] {
      border-color: #e2e8f0;
      border-radius: 18px 18px 0 0;
      box-shadow: 0 -8px 24px -12px rgb(15 23 42 / 16%);
    }
    .wv-seller-dock > [id] ul { --tw-space-y-reverse: 0; }
    .wv-seller-dock > [id] li + li { margin-top: 6px; }
    .wv-seller-dock > [id] li > a,
    .wv-seller-dock > [id] form > button {
      padding: 8px 10px;
      gap: 10px;
      border-radius: 12px;
      transition: color 180ms ease, background-color 180ms ease;
    }
    .wv-seller-dock > [id] li > a > i { color: #64748b; }
    .wv-seller-dock > [id] li > a:hover { background: #eef2ff; color: #4f46e5; }
    .wv-seller-dock > [id] li > a:hover > i { color: inherit; }
    .wv-seller-dock > [id] form > button:hover { background: #fff1f2; }
    .wv-seller-dock > [id] > div > button { border-radius: 12px; }
    .wv-seller-dock > [id] > div > button:hover { background: #f1f5f9; }
    @media (prefers-reduced-motion: reduce) {
      .wv-seller-dock *, .wv-seller-dock > [id] li > a,
      .wv-seller-dock > [id] form > button,
      .wv-seller-dock > nav > a, .wv-seller-dock > nav > button,
      .wv-seller-dock > nav > a > i, .wv-seller-dock > nav > button > i {
        transition: none !important;
        animation: none !important;
      }
    }
  </style>
@endonce

<div data-toast-bottom-nav data-mobile-bottom-seller-nav
  x-data="{ openOrdersMenu: false, openCabinetMenu: false }"
  @keydown.escape.window="if (openOrdersMenu) { openOrdersMenu = false; $refs.ordersToggle.focus() } else if (openCabinetMenu) { openCabinetMenu = false; $refs.cabinetToggle.focus() }"
  class="wv-seller-dock lg:hidden fixed bottom-0 left-0 right-0 z-50"
  style="padding-bottom: env(safe-area-inset-bottom, 0px);">

  <nav class="grid grid-cols-5 items-stretch text-xs font-medium" style="height: var(--seller-bottom-nav-height);">

    <!-- В магазин (главная сайта) -->
    <a href="{{ url('/') }}" 
       class="flex h-full min-w-0 flex-col items-center justify-center gap-0.5 transition-all duration-200 {{ request()->is('/') ? 'text-indigo-600' : 'text-neutral-500 hover:text-indigo-500' }}">
        <i class="ri-store-3-line text-xl"></i>
        <span>В магазин</span>
    </a>

    <!-- Товары -->
    <a href="{{ route('seller.products.index') }}" 
       class="flex h-full min-w-0 flex-col items-center justify-center gap-0.5 transition-all duration-200 {{ request()->routeIs('seller.products.*') ? 'text-indigo-600' : 'text-neutral-500 hover:text-indigo-500' }}">
        <i class="ri-store-2-line text-xl"></i>
        <span>Товары</span>
    </a>

    <!-- Чаты -->
    <a href="{{ route('chats.index') }}"
       class="relative flex h-full min-w-0 flex-col items-center justify-center gap-0.5 transition-all duration-200 {{ request()->routeIs('chats.*') ? 'text-indigo-600' : 'text-neutral-500 hover:text-indigo-500' }}">
        <i class="ri-chat-3-line text-xl"></i>
        <span>Чаты</span>
        @if(($unreadChatsCount ?? 0) > 0)
            <span class="absolute -top-2 -right-2 bg-indigo-600 text-white text-[10px] font-semibold px-1.5 rounded-full">
                {{ min($unreadChatsCount, 9) }}
            </span>
        @endif
    </a>

    <!-- Заказы (только заказы) -->
    <button 
      type="button" x-ref="ordersToggle" aria-controls="seller-orders-menu" :aria-expanded="openOrdersMenu"
      @click="openOrdersMenu = !openOrdersMenu; openCabinetMenu = false"
      class="relative flex h-full min-w-0 flex-col items-center justify-center gap-0.5 transition-all duration-200 {{ request()->routeIs('seller.orders.*') ? 'text-indigo-600' : 'text-neutral-500 hover:text-indigo-500' }}">
        <i class="ri-file-list-3-line text-xl"></i>
        <span>Заказы</span>
    </button>

    <!-- Кабинет (меню) -->
    <button
      type="button" x-ref="cabinetToggle" aria-controls="seller-cabinet-menu" :aria-expanded="openCabinetMenu"
      @click="openCabinetMenu = !openCabinetMenu; openOrdersMenu = false"
      class="flex h-full min-w-0 flex-col items-center justify-center gap-0.5 transition-all duration-200 {{ request()->routeIs('cabinet', 'seller.cabinet', 'seller.finance.*', 'seller.analytics.*', 'seller.followers.*', 'seller.plans.*', 'profile.*') ? 'text-indigo-600' : 'text-neutral-500 hover:text-indigo-500' }}">
        <i class="ri-user-settings-line text-xl"></i>
        <span>Кабинет</span>
    </button>

  </nav>

  <!-- Меню заказов (только заказы) -->
  <div 
    id="seller-orders-menu" x-cloak x-show="openOrdersMenu"
    @click.away="openOrdersMenu = false"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="translate-y-full opacity-0"
    class="max-h-[calc(100dvh-6rem-env(safe-area-inset-bottom,0px))] overflow-y-auto overscroll-contain absolute left-0 right-0 bg-white rounded-t-2xl p-5 border-t border-neutral-200 z-40 shadow-[0_-8px_24px_rgba(15,23,42,0.08)]"
    style="bottom: var(--seller-bottom-nav-offset);">

    <div class="flex items-center justify-between mb-4">
      <h3 class="text-base font-semibold text-neutral-800">Заказы</h3>
      <button type="button" aria-label="Закрыть меню заказов" @click="openOrdersMenu = false; $refs.ordersToggle.focus()" class="flex h-11 w-11 items-center justify-center text-neutral-400 hover:text-neutral-600">
        <i class="ri-close-line text-xl"></i>
      </button>
    </div>

    <ul class="space-y-3 text-sm text-neutral-700">
      <li><a href="{{ route('seller.orders.index') }}" class="flex min-h-11 items-center gap-2 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 hover:text-indigo-600"><i class="ri-list-unordered text-lg"></i> Все заказы</a></li>
      <li><a href="{{ route('seller.orders.index', ['status' => \App\Models\Order::STATUS_PENDING]) }}" class="flex min-h-11 items-center gap-2 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 hover:text-indigo-600"><i class="ri-time-line text-lg"></i> Ожидают</a></li>
      <li><a href="{{ route('seller.orders.index', ['status' => \App\Models\Order::STATUS_SHIPPED]) }}" class="flex min-h-11 items-center gap-2 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 hover:text-indigo-600"><i class="ri-truck-line text-lg"></i> В пути</a></li>
      <li><a href="{{ route('seller.orders.index', ['status' => \App\Models\Order::STATUS_COMPLETED]) }}" class="flex min-h-11 items-center gap-2 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 hover:text-indigo-600"><i class="ri-check-double-line text-lg"></i> Завершённые</a></li>
    </ul>
  </div>

  <!-- Меню кабинета (кабинет, финансы, аналитика, выход) -->
  <div 
    id="seller-cabinet-menu" x-cloak x-show="openCabinetMenu"
    @click.away="openCabinetMenu = false"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="translate-y-full opacity-0"
    class="max-h-[calc(100dvh-6rem-env(safe-area-inset-bottom,0px))] overflow-y-auto overscroll-contain absolute left-0 right-0 bg-white rounded-t-2xl p-5 border-t border-neutral-200 z-40 shadow-[0_-8px_24px_rgba(15,23,42,0.08)]"
    style="bottom: var(--seller-bottom-nav-offset);">

    <div class="flex items-center justify-between mb-4">
      <h3 class="text-base font-semibold text-neutral-800">Меню кабинета</h3>
      <button type="button" aria-label="Закрыть меню кабинета" @click="openCabinetMenu = false; $refs.cabinetToggle.focus()" class="flex h-11 w-11 items-center justify-center text-neutral-400 hover:text-neutral-600">
        <i class="ri-close-line text-xl"></i>
      </button>
    </div>

    <ul class="space-y-3 text-sm text-neutral-700">
      <!-- Кабинет (главная продавца) -->
      <li><a href="{{ route('cabinet') }}" class="flex min-h-11 items-center gap-2 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 hover:text-indigo-600"><i class="ri-dashboard-line text-lg"></i> Кабинет</a></li>

      <li>
        <a href="{{ route('seller.followers.index') }}" class="flex min-h-11 items-center gap-2 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 hover:text-indigo-600">
          <i class="ri-user-follow-line text-lg"></i>
          Подписчики
        </a>
      </li>

      <li>
        <a href="{{ route('support') }}" class="flex min-h-11 items-center gap-2 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 hover:text-indigo-600">
          <i class="ri-customer-service-2-line text-lg"></i>
          Поддержка
        </a>
      </li>

      <li>
        <a href="{{ route('seller.plans.index') }}" class="flex min-h-11 items-center gap-2 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 hover:text-indigo-600">
          <i class="ri-vip-crown-line text-lg"></i>
          Уровень магазина
        </a>
      </li>
      
      <!-- Финансы -->
      <li><a href="{{ route('seller.finance.index') }}" class="flex min-h-11 items-center gap-2 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 hover:text-indigo-600"><i class="ri-wallet-3-line text-lg"></i> Финансы</a></li>
      
      <!-- Аналитика -->
      <li><a href="{{ route('seller.analytics.index') }}" class="flex min-h-11 items-center gap-2 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 hover:text-indigo-600"><i class="ri-bar-chart-2-line text-lg"></i> Аналитика</a></li>
      
      <li><a href="{{ route('profile.edit') }}" class="flex min-h-11 items-center gap-2 rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 hover:text-indigo-600"><i class="ri-user-3-line text-lg" aria-hidden="true"></i> Профиль</a></li>
      <!-- Разделитель -->
      <li class="border-t border-neutral-100 pt-3 mt-3"></li>
      
      <!-- Кнопка выхода -->
      <li>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="flex min-h-11 items-center gap-2 text-danger-500 hover:text-danger-700 w-full text-left transition-colors duration-200">
            <i class="ri-logout-box-r-line text-lg"></i>
            <span class="text-sm font-medium">Выйти из аккаунта</span>
          </button>
        </form>
      </li>
    </ul>
  </div>
</div>

@once('remixicon-4.1.0')
    @vite('resources/css/remixicon.css')
@endonce
