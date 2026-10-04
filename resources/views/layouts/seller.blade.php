@props(['title' => null, 'chatMode' => false, 'flushContent' => true])

{{-- resources/views/layouts/seller.blade.php --}}
<x-seller-base :title="$title ?? 'Панель продавца'">

<div class="wv-buyer-shell flex {{ $chatMode ? 'h-dvh overflow-hidden' : 'min-h-screen' }} flex-col bg-neutral-50 text-neutral-800 md:flex-row"
     x-data="{ sellerMenuOpen: false }" :data-menu-open="sellerMenuOpen"
     @keydown.escape="if (sellerMenuOpen) { sellerMenuOpen = false; $refs.sellerMenuToggle.focus() }">

    <div class="shrink-0 border-b border-neutral-200 bg-white px-3 py-2 md:hidden">
        <button type="button" x-ref="sellerMenuToggle" @click="sellerMenuOpen = !sellerMenuOpen"
                :aria-expanded="sellerMenuOpen" aria-expanded="false" aria-controls="seller-sidebar"
                class="wv-buyer-menu-toggle flex min-h-11 w-full items-center gap-3 rounded-xl px-2 text-sm font-semibold text-neutral-800 transition hover:bg-brand-50">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i class="ri-menu-line text-xl" aria-hidden="true"></i></span>
            <span>Меню продавца</span>
            <i class="ri-arrow-down-s-line ml-auto text-xl transition-transform" :class="{ 'rotate-180': sellerMenuOpen }" aria-hidden="true"></i>
        </button>
    </div>

    <!-- Sidebar -->
<aside id="seller-sidebar" class="wv-buyer-sidebar wv-sidebar flex-col justify-between border-r border-neutral-200 md:fixed md:inset-y-0 md:left-0 md:w-64">

    <!-- ВЕРХ -->
    <div class="flex flex-1 shrink-0 flex-col">
        <div class="flex items-center gap-3 border-b border-neutral-100 px-4 py-4">
            <a href="{{ route('seller.cabinet') }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ asset('images/icon.png') }}" class="h-10 w-10 rounded-xl shadow-sm ring-1 ring-neutral-200" alt="WebVitrina">
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold tracking-tight text-neutral-900">WebVitrina</span>
                    <span class="mt-0.5 block text-[11px] font-medium text-neutral-500">Панель продавца</span>
                </span>
            </a>
        </div>

        @php
            $active = 'wv-sidebar-link-active';
            $link = 'wv-sidebar-link';
            $shop = auth()->user()->shop;
            $storefrontUrl = $shop?->slug
                ? route('seller.show', ['identifier' => $shop->slug])
                : route('home');
            $sellerMenu = [
                'Обзор' => [
                    ['route' => 'seller.cabinet', 'active' => 'seller.cabinet', 'icon' => 'ri-dashboard-line', 'label' => 'Рабочий стол'],
                    ['url' => $storefrontUrl, 'active' => 'seller.show', 'icon' => 'ri-store-3-line', 'label' => 'Моя витрина', 'external' => true],
                    ['route' => 'chats.index', 'active' => 'chats.*', 'icon' => 'ri-chat-3-line', 'label' => 'Сообщения', 'badge' => $unreadChatsCount ?? 0],
                    ['route' => 'support', 'active' => 'support', 'icon' => 'ri-customer-service-2-line', 'label' => 'Поддержка'],
                ],
                'Продажи' => [
                    ['route' => 'seller.orders.index', 'active' => 'seller.orders.*', 'icon' => 'ri-shopping-bag-3-line', 'label' => 'Заказы'],
                    ['route' => 'seller.finance.index', 'active' => 'seller.finance.*', 'icon' => 'ri-wallet-3-line', 'label' => 'Финансы'],
                ],
                'Каталог' => [
                    ['route' => 'seller.products.index', 'active' => 'seller.products.*', 'icon' => 'ri-box-3-line', 'label' => 'Товары'],
                    ['route' => 'seller.followers.index', 'active' => 'seller.followers.*', 'icon' => 'ri-user-follow-line', 'label' => 'Подписчики'],
                ],
                'Рост' => [
                    ['route' => 'seller.analytics.index', 'active' => 'seller.analytics.*', 'icon' => 'ri-line-chart-line', 'label' => 'Аналитика'],
                    ['route' => 'seller.plans.index', 'active' => 'seller.plans.*', 'icon' => 'ri-vip-crown-line', 'label' => 'Уровень магазина'],
                ],
                'Настройки' => [
                    ['route' => 'profile.edit', 'active' => 'profile.*', 'icon' => 'ri-user-3-line', 'label' => 'Профиль'],
                ],
            ];
        @endphp

        <nav class="py-3" aria-label="Кабинет продавца">
            @foreach($sellerMenu as $section => $items)
                <section class="wv-buyer-menu-group" aria-labelledby="seller-menu-group-{{ $loop->index }}">
                    <h2 id="seller-menu-group-{{ $loop->index }}" class="px-5 pb-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-neutral-400">{{ $section }}</h2>
                    <ul class="space-y-1">
                        @foreach($items as $item)
                            @php $isActive = request()->routeIs($item['active']); @endphp
                            <li>
                                <a href="{{ $item['url'] ?? route($item['route']) }}"
                                   class="{{ $isActive ? $active : '' }} {{ $link }}"
                                   @if($isActive) aria-current="page" @endif>
                                    <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                                    <span class="min-w-0 flex-1 break-words">{{ $item['label'] }}</span>
                                    @if(($item['badge'] ?? 0) > 0)
                                        <span class="wv-buyer-badge" aria-label="{{ min($item['badge'], 99) }} непрочитанных">
                                            {{ min($item['badge'], 99) }}
                                        </span>
                                    @endif
                                    @if($item['external'] ?? false)
                                        <i class="ri-arrow-right-up-line ml-auto text-base text-neutral-400"></i>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </nav>
    </div>

    <!-- НИЗ (ПРИЖАТ К НИЗУ) -->
    <div class="shrink-0 px-3 pb-3 pt-2">
        <div class="flex items-center gap-3 rounded-2xl border border-neutral-200 bg-neutral-50/80 p-3">
            @if(auth()->user()->avatar)
                <img data-image-candidates="{{ json_encode(auth()->user()->avatar_candidates ?? []) }}" data-image-fallback="{{ asset('images/avatar-placeholder.svg') }}"
                    src="{{ auth()->user()->avatar_url }}"
                    class="h-10 w-10 shrink-0 rounded-xl border border-neutral-200 object-cover"
                    alt="avatar"
                    loading="lazy"
                    decoding="async">
            @else
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-sm font-semibold text-brand-700 ring-1 ring-brand-100">
                    {{ mb_substr(auth()->user()->name ?? 'U', 0, 1) }}
                </div>
            @endif

            <div class="min-w-0 flex-1 text-sm leading-tight">
                <div class="truncate font-semibold text-neutral-900">{{ auth()->user()->name ?? 'Продавец' }}</div>
                <div class="mt-1 truncate text-[11px] text-neutral-500">{{ auth()->user()->email }}</div>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-xl text-neutral-400 transition hover:bg-rose-50 hover:text-rose-600" aria-label="Выйти из аккаунта" title="Выйти">
                    <i class="ri-logout-box-r-line text-lg" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>

</aside>

            <!-- 🌤 Контент -->
        <main class="flex min-w-0 flex-1 flex-col bg-neutral-50 {{ $chatMode ? 'min-h-0 overflow-hidden p-0 md:ml-64' : (($flushContent ? 'min-h-screen p-0 md:ml-64' : 'min-h-screen px-3 py-6 sm:px-6 md:ml-64')) }}">

            <div class="{{ $chatMode ? 'min-h-0 flex-1' : 'flex-1' }}">
                {{ $slot }}
            </div>

                @unless($chatMode)
                <footer class="mt-auto border-t pt-6 text-center text-xs text-neutral-400">
                    © {{ date('Y') }} WebVitrina — Панель продавца
                </footer>
                @endunless
        </main>

</div>

@once('remixicon-4.1.0')
    @vite('resources/css/remixicon.css')
@endonce

</x-seller-base>
