@props(['title' => null, 'chatMode' => false, 'flushContent' => true])

{{-- resources/views/layouts/buyer-layout.blade.php — боковая панель покупателя --}}
<x-app-layout :title="$title ?? 'Личный кабинет'" :hideHeader="true" :flushMain="$flushContent || $chatMode">
    <div class="wv-buyer-shell flex {{ $chatMode ? 'h-dvh overflow-hidden' : 'min-h-screen' }} flex-col bg-neutral-50 text-neutral-800 md:flex-row"
         x-data="{ buyerMenuOpen: false }" :data-menu-open="buyerMenuOpen"
         @keydown.escape="if (buyerMenuOpen) { buyerMenuOpen = false; $refs.buyerMenuToggle.focus() }">
        <div class="shrink-0 border-b border-neutral-200 bg-white px-3 py-2 md:hidden">
            <button type="button" x-ref="buyerMenuToggle" @click="buyerMenuOpen = !buyerMenuOpen"
                    :aria-expanded="buyerMenuOpen" aria-expanded="false" aria-controls="buyer-sidebar"
                    class="wv-buyer-menu-toggle flex min-h-11 w-full items-center gap-3 rounded-xl px-2 text-sm font-semibold text-neutral-800 transition hover:bg-brand-50">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i class="ri-menu-line text-xl" aria-hidden="true"></i></span>
                <span>Меню кабинета</span>
                <i class="ri-arrow-down-s-line ml-auto text-xl" :class="{ 'rotate-180': buyerMenuOpen }" aria-hidden="true"></i>
            </button>
        </div>
        <!-- 🧭 Sidebar -->
        <aside id="buyer-sidebar" class="wv-buyer-sidebar wv-sidebar flex-col justify-between border-r border-neutral-200 md:fixed md:inset-y-0 md:left-0 md:w-64">
            <div>
                <!-- Логотип -->
                <div class="flex items-center gap-3 border-b border-neutral-100 px-4 py-4">
                    <img src="{{ asset('images/icon.png') }}" alt="WebVitrina" class="h-10 w-10 rounded-xl shadow-sm ring-1 ring-neutral-200">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold tracking-tight text-neutral-900">WebVitrina</div>
                        <div class="mt-0.5 text-[11px] font-medium text-neutral-500">Кабинет покупателя</div>
                    </div>
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

                <nav class="py-3" aria-label="Кабинет покупателя">
                    <div class="mx-3 mb-4 border-b border-neutral-100 pb-4">
                        <a href="{{ route('home') }}" class="wv-buyer-store-link flex min-h-11 items-center gap-3 rounded-xl border border-brand-100 bg-brand-50/70 px-3 py-2.5 text-sm font-semibold text-brand-700 transition hover:border-brand-200 hover:bg-brand-100">
                            <i class="ri-arrow-left-line text-lg" aria-hidden="true"></i>
                            <span>К витрине</span>
                        </a>
                    </div>
                    @foreach($menuGroups as $group => $items)
                        <section class="wv-buyer-menu-group" aria-labelledby="buyer-menu-group-{{ $loop->index }}">
                            <h2 id="buyer-menu-group-{{ $loop->index }}" class="px-5 pb-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">{{ $group }}</h2>
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
            <div class="shrink-0 px-3 pb-3 pt-2">
                <div class="flex items-center gap-3 rounded-2xl border border-neutral-200 bg-neutral-50/80 p-3">
                    {{-- Аватар --}}
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                        @php $avatar = auth()->user()->avatar; @endphp

                        @if($avatar && Storage::disk('public')->exists($avatar))
                            <img data-image-candidates="{{ json_encode(auth()->user()->avatar_candidates ?? []) }}" data-image-fallback="{{ asset('images/avatar-placeholder.svg') }}" src="{{ auth()->user()->avatar_url }}" alt="avatar" class="h-full w-full object-cover">
                        @else
                            <span class="text-base font-semibold text-neutral-600">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                        @endif
                    </div>

                    <div class="flex min-w-0 flex-1 flex-col leading-tight">
                        <span class="truncate text-sm font-semibold text-neutral-900">
                            {{ auth()->user()->name }}
                        </span>

                        <span class="mt-1 truncate text-[11px] text-neutral-500">{{ auth()->user()->email }}</span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                        @csrf
                        <button class="flex h-9 w-9 items-center justify-center rounded-xl text-neutral-400 transition hover:bg-rose-50 hover:text-rose-600" aria-label="Выйти из аккаунта" title="Выйти">
                            <i class="ri-logout-box-r-line text-lg" aria-hidden="true"></i>
                        </button>
                    </form>
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
