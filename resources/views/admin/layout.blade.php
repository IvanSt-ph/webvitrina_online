{{-- 🌐 WebVitrina Admin Layout — Hybrid Flow Pro v3.0 --}}
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Админка') | WebVitrina</title>
  <meta name="theme-color" content="#4f46e5">
  <meta name="robots" content="noindex, nofollow">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" href="{{ asset('favicon.ico') }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased" x-data="{ sidebarOpen: false }">

  <div class="flex min-h-screen overflow-hidden relative">

    <!-- 🔲 Затемнение при открытом меню -->
    <div 
      x-show="sidebarOpen"
      x-transition.opacity
      @click="sidebarOpen = false"
      class="fixed inset-0 bg-black/30 z-30 md:hidden">
    </div>

    <!-- ===== Sidebar ===== -->
    <aside 
      class="fixed left-0 top-0 bottom-0 z-40 w-64 bg-white border-r border-gray-200 shadow-lg flex flex-col 
             transform transition-transform duration-300 ease-in-out 
             md:translate-x-0"
      :class="{ '-translate-x-full': !sidebarOpen }">

      <!-- Header -->
      <div class="p-4 flex items-center justify-between border-b border-gray-100">
        <div class="flex items-center gap-2">
          <i class="ri-store-3-line text-indigo-600 text-2xl"></i>
          <span class="text-xl font-bold text-indigo-600">WebVitrina</span>
        </div>
        <button @click="sidebarOpen = false" class="md:hidden text-gray-400 hover:text-gray-600 text-xl">
          <i class="ri-close-line"></i>
        </button>
      </div>

      <!-- Navigation -->
      @php
        $menu = [
          ['route'=>'admin.dashboard','icon'=>'ri-home-5-line','label'=>'Главная'],
          ['route'=>'admin.products.index','icon'=>'ri-box-3-line','label'=>'Товары'],
          ['route'=>'admin.categories.index','icon'=>'ri-folder-3-line','label'=>'Категории'],
          ['route'=>'admin.orders.index','icon'=>'ri-shopping-bag-3-line','label'=>'Заказы'],
          ['route'=>'admin.users.index','icon'=>'ri-user-3-line','label'=>'Пользователи'],
          ['route'=>'admin.reviews.index','icon'=>'ri-chat-3-line','label'=>'Отзывы'],
          ['route'=>'admin.banners.index','icon'=>'ri-image-line','label'=>'Баннеры'],
          ['route'=>'admin.profile','icon'=>'ri-settings-3-line','label'=>'Настройки'],
        ];
      @endphp

      <nav class="flex-1 p-4 overflow-y-auto space-y-1">
        @foreach ($menu as $item)
          <a href="{{ route($item['route']) }}"
             class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200
                    {{ request()->routeIs($item['route'].'*')
                        ? 'bg-indigo-50 text-indigo-700 font-semibold shadow-sm'
                        : 'text-gray-600 hover:text-indigo-600 hover:bg-indigo-50' }}">
            <i class="{{ $item['icon'] }} text-lg"></i>
            <span>{{ $item['label'] }}</span>
          </a>
        @endforeach
      </nav>

      <div class="p-4 border-t border-gray-100 text-xs text-gray-500 text-center">
        © {{ date('Y') }} WebVitrina<br><span class="text-gray-400">Admin Panel</span>
      </div>
    </aside>

    <!-- ===== Контент ===== -->
    <div class="flex-1 flex flex-col w-full md:ml-[16rem] transition-all duration-300">

      <!-- Topbar -->
      <header class="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-gray-200 h-14 flex items-center justify-between px-4 sm:px-6 shadow-sm">
        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-600 hover:text-indigo-600 text-2xl md:hidden">
          <i class="ri-menu-line"></i>
        </button>

        <h1 class="text-base sm:text-lg font-semibold text-gray-700 truncate">@yield('title','Админка')</h1>

        <div class="flex items-center gap-4 text-sm sm:text-base">
          <div class="hidden sm:flex items-center gap-2 text-gray-600">
            <i class="ri-user-line"></i>
            <span>{{ auth()->user()->name ?? 'Admin' }}</span>
          </div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-1 text-red-500 hover:text-red-600 transition">
              <i class="ri-logout-box-line"></i>
              <span class="hidden sm:inline">Выйти</span>
            </button>
          </form>
        </div>
      </header>

      <!-- Main content -->
      <main class="flex-1 p-6 lg:p-10 w-full bg-gray-50">
        @yield('content')
      </main>

      <footer class="text-center text-xs text-gray-400 py-6 border-t border-gray-100 bg-white/80">
        WebVitrina © {{ date('Y') }} — Панель администратора
      </footer>
    </div>

  </div>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

</body>
</html>
