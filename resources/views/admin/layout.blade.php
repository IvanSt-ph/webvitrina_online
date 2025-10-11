{{-- АДМИНКА с боковым меню  --}}

{{-- Страница: resources/views/admin/layout.blade.php--}}

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Админка') | {{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" href="{{ asset('favicon.ico') }}">
<meta name="theme-color" content="#4f46e5">
<meta name="robots" content="noindex, nofollow">



    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900 font-sans antialiased">

<div 
    x-data="{ sidebarOpen: false }"
    class="flex min-h-screen overflow-x-hidden"
>

    <!-- ===== Боковое меню ===== -->
    <aside 
        class="fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-gray-200 shadow-sm flex flex-col transform transition-transform duration-300 ease-in-out
               md:static md:translate-x-0"
        :class="{ '-translate-x-full': !sidebarOpen }"
    >
        <div class="p-4 text-center border-b border-gray-200 flex items-center justify-between">
            <h1 class="text-xl font-bold text-indigo-600">Админ-панель</h1>
            <!-- Кнопка закрытия на мобилке -->
            <button 
                @click="sidebarOpen = false"
                class="md:hidden text-gray-500 hover:text-gray-700"
            >
                ✖
            </button>
        </div>

        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg transition
                      {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'hover:bg-indigo-50 hover:text-indigo-700' }}">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M3 12l9-9 9 9v9a3 3 0 01-3 3H6a3 3 0 01-3-3v-9z"/></svg>
                Главная
            </a>

            <a href="{{ route('admin.products.index') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg transition
                      {{ request()->routeIs('admin.products.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'hover:bg-indigo-50 hover:text-indigo-700' }}">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M3 3h18l-1 9H4L3 3zm4 13h10l1 5H6l1-5z"/></svg>
                Товары
            </a>

            <a href="{{ route('admin.banners.index') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg transition
                      {{ request()->routeIs('admin.banners.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'hover:bg-indigo-50 hover:text-indigo-700' }}">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M4 4h16v12H4zM4 16l8 4 8-4"/></svg>
                Баннеры
            </a>

            <a href="{{ route('admin.categories.index') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg transition
                      {{ request()->routeIs('admin.categories.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'hover:bg-indigo-50 hover:text-indigo-700' }}">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M3 7h7v7H3zM14 7h7v7h-7zM14 16h7v5h-7zM3 16h7v5H3z"/></svg>
                Категории
            </a>

            <a href="{{ route('admin.orders.index') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg transition
                      {{ request()->routeIs('admin.orders.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'hover:bg-indigo-50 hover:text-indigo-700' }}">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4"/></svg>
                Заказы
            </a>

            <a href="{{ route('admin.users.index') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg transition
                      {{ request()->routeIs('admin.users.*') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'hover:bg-indigo-50 hover:text-indigo-700' }}">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M5.121 17.804A9 9 0 1119.07 8.46M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Пользователи
            </a>

            <a href="{{ route('admin.profile') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-lg transition
                      {{ request()->routeIs('admin.profile') ? 'bg-indigo-50 text-indigo-700 font-semibold' : 'hover:bg-indigo-50 hover:text-indigo-700' }}">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                     d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.89 3.31.877 2.42 2.42a1.724 1.724 0 001.065 2.572c1.757.426 1.757 2.924 0 3.35a1.724 1.724 0 00-1.065 2.572c.89 1.543-.877 3.31-2.42 2.42a1.724 1.724 0 00-2.572 1.065c-.426 1.757-2.924 1.757-3.35 0a1.724 1.724 0 00-2.572-1.065c-1.543.89-3.31-.877-2.42-2.42a1.724 1.724 0 00-1.065-2.572c-1.757-.426-1.757-2.924 0-3.35a1.724 1.724 0 001.065-2.572c-.89-1.543.877-3.31 2.42-2.42.996.574 2.27.06 2.572-1.066z" /></svg>
                Настройки
            </a>
        </nav>

        <div class="p-4 border-t border-gray-200 text-sm text-gray-500 text-center">
            © {{ date('Y') }} {{ config('app.name', 'Laravel') }}
        </div>
    </aside>

    <!-- ===== Контент ===== -->
    <div class="flex-1 flex flex-col min-w-0">

        <!-- Верхняя панель -->
        <header class="bg-white border-b border-gray-200 h-14 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-20">
            <!-- Бургер -->
            <button 
                @click="sidebarOpen = !sidebarOpen"
                class="md:hidden text-gray-600 hover:text-gray-800"
            >
                ☰
            </button>

            <div class="text-base sm:text-lg font-semibold truncate">
                Панель администратора
            </div>

            <div class="flex items-center gap-4 text-sm sm:text-base">
                <span>{{ auth()->user()->name ?? 'Admin' }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-red-500 hover:underline">Выйти</button>
                </form>
            </div>
        </header>

        <!-- Основной контент -->
        <main class="flex-1 p-4 sm:p-6 lg:p-10 w-full overflow-x-hidden">
            @yield('content')
        </main>

    </div>
</div>

</body>
</html>
