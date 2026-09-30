@props(['title' => null, 'chatMode' => false, 'flushContent' => true])

{{-- resources/views/layouts/buyer-layout.blade.php — боковая панель покупателя --}}
<x-app-layout :title="$title ?? 'Личный кабинет'" :hideHeader="true" :flushMain="$flushContent || $chatMode">
    <div class="wv-buyer-shell flex {{ $chatMode ? 'h-dvh overflow-hidden' : 'min-h-screen' }} flex-col bg-neutral-50 text-neutral-800 md:flex-row"
         x-data="{ buyerMenuOpen: false }" :data-menu-open="buyerMenuOpen"
         @keydown.escape="if (buyerMenuOpen) { buyerMenuOpen = false; $refs.buyerMenuToggle.focus() }">
        <div class="shrink-0 border-b border-neutral-200 bg-white px-3 py-2 md:hidden">
            <button type="button" x-ref="buyerMenuToggle" @click="buyerMenuOpen = !buyerMenuOpen"
                    :aria-expanded="buyerMenuOpen" aria-expanded="false" aria-controls="buyer-sidebar"
                    class="wv-buyer-menu-toggle flex min-h-11 w-full items-center gap-3 rounded-xl px-3 text-sm font-semibold text-neutral-800">
                <i class="ri-menu-line text-xl" aria-hidden="true"></i>
                <span>Меню кабинета</span>
                <i class="ri-arrow-down-s-line ml-auto text-xl" :class="{ 'rotate-180': buyerMenuOpen }" aria-hidden="true"></i>
            </button>
        </div>
        <!-- 🧭 Sidebar -->
        <aside id="buyer-sidebar" class="wv-buyer-sidebar wv-sidebar flex-col justify-between border-r border-slate-200 md:fixed md:inset-y-0 md:left-0 md:w-64">
            <div>
                <!-- Логотип -->
                <div class="flex items-center gap-2 border-b border-neutral-100 px-6 py-6">
                    <img src="{{ asset('images/icon.png') }}" alt="WebVitrina" class="h-8 w-8 rounded-lg shadow-sm">
                    <span class="text-sm font-semibold tracking-tight text-neutral-800">WebVitrina</span>
                </div>

                @php
                    $menuGroups = [
                        'Основное' => [
                            ['cabinet', 'cabinet', 'ri-home-5-line', 'Кабинет'],
                            ['orders.index', 'orders.*', 'ri-shopping-bag-3-line', 'Заказы'],
                            ['favorites.index', 'favorites.*', 'ri-heart-line', 'Избранное'],
                            ['cart.index', 'cart.*', 'ri-shopping-cart-2-line', 'Корзина'],
                        ],
                        'Общение' => [
                            ['chats.index', 'chats.*', 'ri-chat-3-line', 'Чаты', $unreadChatsCount ?? 0],
                            ['notifications.index', 'notifications.*', 'ri-notification-3-line', 'Уведомления', $unreadNotificationsCount ?? 0],
                            ['support', 'support', 'ri-customer-service-2-line', 'Поддержка'],
                            ['disputes.index', 'disputes.*', 'ri-scales-3-line', 'Обращения'],
                        ],
                        'Профиль' => [
                            ['addresses.index', 'addresses.*', 'ri-map-pin-line', 'Адреса доставки'],
                            ['reviews.index', 'reviews.index', 'ri-star-line', 'Мои отзывы'],
                            ['subscriptions.index', 'subscriptions.*', 'ri-user-follow-line', 'Мои подписки'],
                            ['buyer.profile', 'buyer.profile*', 'ri-settings-3-line', 'Настройки'],
                        ],
                    ];
                @endphp

                <nav class="py-4" aria-label="Кабинет покупателя">
                    <div class="mx-3 mb-4 border-b border-neutral-200 pb-4">
                        <a href="{{ route('home') }}" class="wv-buyer-store-link flex min-h-11 items-center gap-3 rounded-xl border border-neutral-200 px-3 py-2.5 text-sm font-semibold text-neutral-700 transition hover:bg-neutral-100">
                            <i class="ri-arrow-left-line text-xl" aria-hidden="true"></i>
                            <span>К витрине</span>
                        </a>
                    </div>
                    @foreach($menuGroups as $group => $items)
                        <section class="wv-buyer-menu-group" aria-labelledby="buyer-menu-group-{{ $loop->index }}">
                            <h2 id="buyer-menu-group-{{ $loop->index }}" class="px-6 pb-2 text-[11px] font-semibold uppercase tracking-widest text-neutral-500">{{ $group }}</h2>
                            <ul class="space-y-1">
                                @foreach($items as $item)
                                    @php $isActive = request()->routeIs($item[1]); @endphp
                                    <li>
                                        <a href="{{ route($item[0]) }}" class="wv-sidebar-link {{ $isActive ? 'wv-sidebar-link-active' : '' }}"
                                           @if($isActive) aria-current="page" @endif>
                                            <i class="{{ $item[2] }}" aria-hidden="true" @if($item[0] === 'cart.index') data-cart-icon @endif></i>
                                            <span class="min-w-0 flex-1 break-words">{{ $item[3] }}</span>
                                            @if(($item[4] ?? 0) > 0)
                                                <span class="wv-buyer-badge" aria-label="{{ $item[4] }} непрочитанных">{{ $item[4] > 99 ? '99+' : $item[4] }}</span>
                                            @endif
                                            @if($item[0] === 'cart.index')
                                                <span data-cart-count class="wv-buyer-badge hidden"></span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </nav>
            </div>

            <!-- Аккаунт покупателя -->
            <div class="shrink-0 border-t border-neutral-100 px-6 py-4">
                <div class="flex items-start gap-3">
                    {{-- Аватар --}}
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-neutral-100">
                        @php $avatar = auth()->user()->avatar; @endphp

                        @if($avatar && Storage::disk('public')->exists($avatar))
                            <img data-image-candidates="{{ json_encode(auth()->user()->avatar_candidates ?? []) }}" data-image-fallback="{{ asset('images/avatar-placeholder.svg') }}" src="{{ auth()->user()->avatar_url }}" alt="avatar" class="h-full w-full object-cover">
                        @else
                            <span class="text-base font-semibold text-neutral-600">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                        @endif
                    </div>

                    <div class="flex min-w-0 flex-col break-words leading-tight">
                        <span class="text-sm font-semibold text-neutral-800">
                            {{ auth()->user()->name }}
                        </span>

                        <span class="text-xs text-neutral-500">{{ auth()->user()->email }}</span>

                        <form method="POST" action="{{ route('logout') }}" class="mt-1">
                            @csrf
                            <button class="min-h-11 rounded text-sm text-danger-500 hover:text-danger-600">
                                Выйти
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <!-- 🌤 Контент -->
        <main class="min-w-0 flex-1 bg-neutral-50 {{ $chatMode ? 'min-h-0 overflow-hidden p-0 md:ml-64 md:p-0' : ($flushContent ? 'p-0 md:ml-64 md:p-0' : 'p-2 md:ml-64 md:p-10') }}">
            {{ $slot }}
        </main>
    </div>

    @once('remixicon-4.1.0')
        @vite('resources/css/remixicon.css')
    @endonce
</x-app-layout>
