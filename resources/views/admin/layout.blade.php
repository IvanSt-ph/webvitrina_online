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
  <link rel="icon" type="image/svg+xml" href="{{ asset('icons/favicon.svg') }}">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
</head>

@php
  $adminFullHeight = request()->routeIs('admin.chats.*');
  $adminUnreadChats = auth()->check()
      ? \App\Models\Conversation::query()
          ->whereNull('admin_deleted_at')
          ->whereHas('messages', fn ($query) => $query
              ->where('sender_id', '!=', auth()->id())
              ->whereNull('admin_read_at'))
          ->count()
      : 0;
  $pendingSellerPlanRequests = auth()->check()
      ? \App\Models\SellerPlanRequest::where('status', \App\Models\SellerPlanRequest::STATUS_PENDING)->count()
      : 0;
  $pendingReviews = auth()->check()
      ? \App\Models\Review::where('status', \App\Models\Review::STATUS_PENDING)->count()
      : 0;
  $openProductReports = auth()->check()
      ? \App\Models\ProductReport::where('status', \App\Models\ProductReport::STATUS_OPEN)->count()
      : 0;
  $openDisputes = auth()->check()
      ? \App\Models\OrderDispute::where('status', \App\Models\OrderDispute::STATUS_OPEN)->count()
      : 0;
  $attentionOrders = auth()->check()
      ? \App\Models\Order::where(function ($query) {
          $query->where(function ($cancel) {
              $cancel->whereNotNull('cancellation_requested_at')
                  ->whereNotIn('status', [\App\Models\Order::STATUS_CANCELED, \App\Models\Order::STATUS_COMPLETED]);
          })->orWhere(function ($stuck) {
              $stuck->where(function ($pending) {
                  $pending->where('status', \App\Models\Order::STATUS_PENDING)
                      ->where('created_at', '<=', now()->subDay());
              })->orWhere(function ($processing) {
                  $processing->where('status', \App\Models\Order::STATUS_PROCESSING)
                      ->where(function ($dates) {
                          $dates->where('accepted_at', '<=', now()->subDays(2))
                              ->orWhere(function ($fallback) {
                                  $fallback->whereNull('accepted_at')
                                      ->where('created_at', '<=', now()->subDays(2));
                              });
                      });
              });
          });
      })->count()
      : 0;
  $missingMobileBanners = auth()->check()
      ? \App\Models\Banner::whereNull('image_mobile')->count()
      : 0;
@endphp

<body class="bg-slate-50 text-slate-800 font-sans antialiased {{ $adminFullHeight ? 'overflow-hidden' : '' }}" x-data="{ sidebarOpen: false }">

  <div class="flex min-h-screen overflow-hidden relative">

    <!-- 🔲 Затемнение при открытом меню -->
    <div 
      x-show="sidebarOpen"
      x-cloak
      x-transition.opacity
      @click="sidebarOpen = false"
      class="fixed inset-0 bg-black/30 z-30 md:hidden">
    </div>

    <!-- ===== Sidebar ===== -->
    <aside 
      class="fixed left-0 top-0 bottom-0 z-40 w-64 border-r border-slate-200 wv-sidebar flex flex-col
             -translate-x-full transform transition-transform duration-300 ease-in-out
             md:translate-x-0"
      :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }">

      <!-- Header -->
      <div class="p-4 flex items-center justify-between border-b border-slate-100">
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
          'Работа' => [
            ['route'=>'admin.dashboard','icon'=>'ri-home-5-line','label'=>'Главная'],
            ['route'=>'admin.orders.index','params'=>['focus'=>'attention'],'icon'=>'ri-shopping-bag-3-line','label'=>'Заказы','badge'=>$attentionOrders,'badgeTitle'=>'Требуют внимания: отмена или долго без движения'],
            ['route'=>'admin.chats.index','active'=>'admin.chats.*','icon'=>'ri-message-3-line','label'=>'Чаты','badge'=>$adminUnreadChats],
            ['route'=>'admin.disputes.index','active'=>'admin.disputes.*','icon'=>'ri-scales-3-line','label'=>'Споры','badge'=>$openDisputes],
            ['route'=>'admin.reviews.index','icon'=>'ri-chat-3-line','label'=>'Отзывы','badge'=>$pendingReviews],
            ['route'=>'admin.product-reports.index','active'=>'admin.product-reports.*','icon'=>'ri-alarm-warning-line','label'=>'Жалобы','badge'=>$openProductReports],
            ['route'=>'admin.users.index','icon'=>'ri-user-3-line','label'=>'Пользователи'],
          ],
          'Каталог' => [
            ['route'=>'admin.products.index','icon'=>'ri-box-3-line','label'=>'Товары'],
            ['route'=>'admin.categories.index','icon'=>'ri-folder-3-line','label'=>'Категории'],
            ['route'=>'admin.banners.index','icon'=>'ri-image-line','label'=>'Баннеры','badge'=>$missingMobileBanners],
          ],
          'Управление' => [
            ['route'=>'admin.seller-plan-requests.index','active'=>'admin.seller-plan-requests.*','icon'=>'ri-vip-crown-line','label'=>'Тарифы','badge'=>$pendingSellerPlanRequests],
            ['route'=>'admin.production-checklist','icon'=>'ri-rocket-line','label'=>'Релиз-чеклист'],
            ['route'=>'admin.activity.index','active'=>'admin.activity.*','icon'=>'ri-history-line','label'=>'Журнал'],
            ['route'=>'admin.profile','icon'=>'ri-settings-3-line','label'=>'Настройки'],
          ],
        ];
      @endphp

      <nav class="flex-1 overflow-y-auto p-4">
        @foreach ($menu as $section => $items)
          <div class="{{ $loop->first ? '' : 'mt-5' }} mb-2 px-3 text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ $section }}</div>
          <div class="space-y-1">
          @foreach ($items as $item)
          <a href="{{ route($item['route'], $item['params'] ?? []) }}"
             class="wv-sidebar-link {{ request()->routeIs($item['active'] ?? $item['route'].'*') ? 'wv-sidebar-link-active' : '' }}">
            <i class="{{ $item['icon'] }} text-lg"></i>
            <span>{{ $item['label'] }}</span>
            @if(($item['badge'] ?? 0) > 0)
              <span @if($item['route'] === 'admin.chats.index') data-admin-chat-unread="{{ $item['badge'] }}" @endif title="{{ $item['badgeTitle'] ?? '' }}" class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 text-[11px] font-bold text-white">
                {{ $item['badge'] > 99 ? '99+' : $item['badge'] }}
              </span>
            @endif
          </a>
          @endforeach
          </div>
        @endforeach
      </nav>

      <div class="p-4 border-t border-slate-100 text-xs text-slate-500 text-center">
        © {{ date('Y') }} WebVitrina<br><span class="text-gray-400">Admin Panel</span>
      </div>
    </aside>

    <!-- ===== Контент ===== -->
    <div class="flex-1 flex flex-col w-full md:ml-[16rem] transition-all duration-300 {{ $adminFullHeight ? 'h-[100dvh] overflow-hidden' : '' }}">

      <!-- Topbar -->
      <header class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-200 bg-white/90 shadow-sm backdrop-blur-md {{ $adminFullHeight ? 'h-11 px-3 sm:h-14 sm:px-6' : 'h-14 px-4 sm:px-6' }}">
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
      <main class="flex-1 w-full bg-slate-50 {{ $adminFullHeight ? 'min-h-0 overflow-hidden p-2 sm:p-4 lg:p-5' : 'p-6 lg:p-10' }}">
        @yield('content')
      </main>

      @unless($adminFullHeight)
        <footer class="text-center text-xs text-gray-400 py-6 border-t border-gray-100 bg-white/80">
          WebVitrina © {{ date('Y') }} — Панель администратора
        </footer>
      @endunless
    </div>

  </div>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

</body>
</html>
