@props(['title' => null, 'chatMode' => false, 'flushContent' => true])

{{-- resources/views/layouts/buyer-layout.blade.php — боковая панель покупателя --}}
<x-app-layout :title="$title ?? 'Личный кабинет'" :hideHeader="true" :flushMain="$flushContent || $chatMode">
    <div class="flex min-h-screen bg-neutral-50 text-gray-800">
        <!-- 🧭 Sidebar -->
        <aside class="fixed bottom-0 left-0 top-0 hidden w-64 flex-col justify-between border-r border-gray-100 bg-white shadow-sm md:flex">
            <div>
                <!-- Логотип -->
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-6">
                    <img src="{{ asset('images/icon.png') }}" alt="WebVitrina" class="h-8 w-8 rounded-lg shadow-sm">
                    <span class="text-sm font-semibold tracking-tight text-gray-800">WebVitrina</span>
                </div>

                <!-- Навигация -->
                @php
                    $active = 'bg-indigo-50 text-indigo-600 font-medium border-l-4 border-indigo-500';
                    $link = 'flex items-center gap-2 px-6 py-3 rounded-r-lg transition-all duration-200 hover:bg-indigo-50 hover:text-indigo-600 hover:translate-x-[3px]';
                @endphp

                <nav class="mt-5 text-[16px] font-normal text-gray-700">
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

                    <div class="px-6 pb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">
                        Основное
                    </div>

                    <div class="flex flex-col">
                        <a href="{{ route('cabinet') }}" class="{{ request()->routeIs('cabinet') ? $active : '' }} {{ $link }}">
                            <i class="ri-home-5-line text-[22px]"></i>
                            <span>Кабинет</span>
                        </a>

                        <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? $active : '' }} {{ $link }}">
                            <i class="ri-shopping-bag-3-line text-[22px]"></i>
                            <span>Заказы</span>
                        </a>

                        <a href="{{ route('favorites.index') }}" class="{{ request()->routeIs('favorites.*') ? $active : '' }} {{ $link }}">
                            <i class="ri-heart-line text-[22px]"></i>
                            <span>Избранное</span>
                        </a>

                        <a href="{{ route('chats.index') }}" class="{{ request()->routeIs('chats.*') ? $active : '' }} {{ $link }}">
                            <span class="relative">
                                <i class="ri-chat-3-line text-[22px]"></i>
                                @if(($unreadChatsCount ?? 0) > 0)
                                    <span class="absolute -right-3 -top-2 inline-flex min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 py-0.5 text-[10px] font-bold text-white">
                                        {{ min($unreadChatsCount, 99) }}
                                    </span>
                                @endif
                            </span>
                            <span>Чаты</span>
                        </a>

                        <a href="{{ route('cart.index') }}" class="{{ request()->routeIs('cart.*') ? $active : '' }} {{ $link }}">
                            <div class="relative">
                                <i class="ri-shopping-cart-2-line text-[22px]" data-cart-icon></i>

                                <!-- 🔥 бейдж количества товаров -->
                                <span data-cart-count
                                      class="pointer-events-none absolute -right-2 -top-1.5 rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] font-bold text-white opacity-0 transition-none">
                                </span>
                            </div>

                            <span>Корзина</span>
                        </a>

                        <a href="{{ route('addresses.index') }}" class="{{ request()->routeIs('addresses.*') ? $active : '' }} {{ $link }}">
                            <i class="ri-map-pin-line text-[22px]"></i>
                            <span>Адреса доставки</span>
                        </a>
                    </div>

                    <div class="mt-5 px-6 pb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">
                        Сервис
                    </div>

                    <div class="flex flex-col">
                        <a href="{{ route('notifications.settings') }}" class="{{ request()->routeIs('notifications.settings') ? $active : '' }} {{ $link }}">
                            <i class="ri-notification-3-line text-[22px]"></i>
                            <span>Уведомления</span>
                        </a>

                        <a href="{{ route('reviews.index') }}" class="{{ request()->routeIs('reviews.index') ? $active : '' }} {{ $link }}">
                            <i class="ri-star-line text-[22px]"></i>
                            <span>Мои отзывы</span>
                        </a>

                        <a href="{{ route('support') }}" class="{{ request()->routeIs('support') ? $active : '' }} {{ $link }}">
                            <i class="ri-customer-service-2-line text-[22px]"></i>
                            <span>Поддержка</span>
                        </a>

                        <a href="{{ route('subscriptions.index') }}" class="{{ request()->routeIs('subscriptions.*') ? $active : '' }} {{ $link }}">
                            <i class="ri-user-follow-line text-[22px]"></i>
                            <span>Мои подписки</span>
                        </a>

                        <a href="{{ route('buyer.profile') }}" class="{{ request()->routeIs('buyer.profile') ? $active : '' }} {{ $link }}">
                            <i class="ri-settings-3-line text-[22px]"></i>
                            <span>Настройки</span>
                        </a>
                    </div>
                </nav>
            </div>

            <!-- Аккаунт покупателя -->
            <div class="border-t border-gray-100 px-6 py-4">
                <div class="flex items-start gap-3">
                    {{-- Аватар --}}
                    <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-gray-100">
                        @php $avatar = auth()->user()->avatar; @endphp

                        @if($avatar && Storage::disk('public')->exists($avatar))
                            <img src="{{ asset('storage/'.$avatar) }}" alt="avatar" class="h-full w-full object-cover">
                        @else
                            <span class="text-base font-semibold text-gray-600">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                        @endif
                    </div>

                    <div class="flex flex-col leading-tight">
                        <span class="text-sm font-semibold text-gray-800">
                            {{ auth()->user()->name }}
                        </span>

                        <span class="text-xs text-gray-500">{{ auth()->user()->email }}</span>

                        <form method="POST" action="{{ route('logout') }}" class="mt-1">
                            @csrf
                            <button class="text-xs text-red-500 hover:text-red-600">
                                Выйти
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <!-- 🌤 Контент -->
        <main class="flex-1 bg-neutral-50 {{ $chatMode ? 'h-dvh overflow-hidden p-0 md:ml-64 md:p-0' : ($flushContent ? 'p-0 md:ml-64 md:p-0' : 'p-2 md:ml-64 md:p-10') }}">
            {{ $slot }}
        </main>
    </div>

    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
</x-app-layout>
