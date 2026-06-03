@props(['title' => null, 'chatMode' => false, 'flushContent' => true])

{{-- resources/views/layouts/seller.blade.php --}}
<x-seller-base :title="$title ?? 'Панель продавца'">

<div class="flex min-h-screen overflow-x-hidden overflow-y-auto bg-slate-50 text-slate-800">

    <!-- Sidebar -->
<aside class="fixed bottom-0 left-0 top-0 z-30 hidden w-64 flex-col border-r border-slate-200 wv-sidebar lg:flex">

    <!-- ВЕРХ -->
    <div class="flex-1 flex flex-col">
        <div class="flex items-center gap-2 px-6 py-6 border-b border-gray-100">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/icon.png') }}" class="w-8 h-8 rounded-lg shadow-sm" alt="WebVitrina">
                <span class="font-semibold text-gray-900 text-sm tracking-tight">WebVitrina Seller</span>
            </a>
        </div>

        @php
            $active = 'wv-sidebar-link-active';
            $link   = 'wv-sidebar-link';
            $sellerMenu = [
                'Работа' => [
                    ['route' => 'seller.cabinet', 'active' => 'seller.cabinet', 'icon' => 'ri-home-5-line', 'label' => 'Главная'],
                    ['route' => 'seller.orders.index', 'active' => 'seller.orders.*', 'icon' => 'ri-shopping-bag-3-line', 'label' => 'Заказы'],
                    ['route' => 'chats.index', 'active' => 'chats.*', 'icon' => 'ri-chat-3-line', 'label' => 'Чаты', 'badge' => $unreadChatsCount ?? 0],
                    ['route' => 'support', 'active' => 'support', 'icon' => 'ri-customer-service-2-line', 'label' => 'Поддержка'],
                ],
                'Каталог' => [
                    ['route' => 'seller.products.index', 'active' => 'seller.products.*', 'icon' => 'ri-box-3-line', 'label' => 'Товары'],
                    ['route' => 'seller.followers.index', 'active' => 'seller.followers.*', 'icon' => 'ri-user-follow-line', 'label' => 'Подписчики'],
                ],
                'Финансы и рост' => [
                    ['route' => 'seller.finance.index', 'active' => 'seller.finance.*', 'icon' => 'ri-cash-line', 'label' => 'Финансы'],
                    ['route' => 'seller.analytics.index', 'active' => 'seller.analytics.*', 'icon' => 'ri-line-chart-line', 'label' => 'Аналитика'],
                    ['route' => 'seller.plans.index', 'active' => 'seller.plans.*', 'icon' => 'ri-vip-crown-line', 'label' => 'Тарифы'],
                ],
                'Управление' => [
                    ['route' => 'profile.edit', 'active' => 'profile.*', 'icon' => 'ri-user-3-line', 'label' => 'Профиль'],
                ],
            ];
        @endphp

        <nav class="mt-5 flex flex-col text-[15px] font-normal text-slate-700">
            <div class="px-4 pb-5">
                <a href="{{ route('home') }}"
                   class="flex items-center justify-between rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-700 transition hover:border-indigo-200 hover:bg-indigo-100">
                    <span class="flex items-center gap-2">
                        <i class="ri-store-3-line text-[20px]"></i>
                        <span>К витрине</span>
                    </span>
                    <i class="ri-arrow-right-up-line text-[18px] text-indigo-500"></i>
                </a>
            </div>

            @foreach($sellerMenu as $section => $items)
                <div class="{{ $loop->first ? '' : 'mt-4' }} px-6 pb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">
                    {{ $section }}
                </div>
                @foreach($items as $item)
                    <a href="{{ route($item['route']) }}"
                       class="{{ request()->routeIs($item['active']) ? $active : '' }} {{ $link }}">
                        <i class="{{ $item['icon'] }} text-[22px]"></i>
                        <span>{{ $item['label'] }}</span>
                        @if(($item['badge'] ?? 0) > 0)
                            <span class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 text-[10px] font-bold text-white">
                                {{ min($item['badge'], 99) }}
                            </span>
                        @endif
                    </a>
                @endforeach
            @endforeach
        </nav>
    </div>

    <!-- НИЗ (ПРИЖАТ К НИЗУ) -->
    <div class="px-6 py-4 border-t border-gray-100">
        <div class="flex items-center gap-3">
            <img
                src="{{ auth()->user()->avatar ? asset('storage/'.auth()->user()->avatar) : 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name ?? 'U') }}"
                class="w-9 h-9 rounded-full border border-gray-200" alt="avatar">

            <div class="text-sm">
                <div class="font-semibold text-gray-800">{{ auth()->user()->name ?? 'Продавец' }}</div>
                <div class="text-gray-400">{{ auth()->user()->email }}</div>

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
        <main class="flex flex-1 flex-col overflow-hidden bg-slate-50 lg:ml-64 {{ $chatMode ? 'h-dvh p-0' : (($flushContent ? 'min-h-screen p-0' : 'min-h-screen px-3 sm:px-6 py-6')) }}">

            <div class="{{ $chatMode ? 'min-h-0 flex-1' : 'flex-1' }}">
                {{ $slot }}
            </div>

                @unless($chatMode)
                <footer class="mt-auto border-t pt-6 text-center text-xs text-gray-400">
                    © {{ date('Y') }} WebVitrina — Панель продавца
                </footer>
                @endunless
        </main>

</div>

<link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">

</x-app-layout>
