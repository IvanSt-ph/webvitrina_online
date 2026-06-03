@props(['title' => null, 'hideHeader' => false, 'flushMain' => false])

@php
    $hideMobileBottomNav = request()->routeIs('chats.show');
    $isAdminUser = auth()->check() && auth()->user()->role === 'admin';
    $showSellerMobileBottomNav = auth()->check()
        && ! $isAdminUser
        && auth()->user()->isSeller()
        && ! $hideMobileBottomNav;
    $showBuyerMobileBottomNav = ! $isAdminUser && ! $showSellerMobileBottomNav && ! (
        request()->routeIs('seller.*') ||
        request()->routeIs('cabinet') ||
        request()->routeIs('profile.*') ||
        $hideMobileBottomNav
    );

    $mainTopPadding = $hideHeader ? 'pt-0' : 'pt-0 lg:pt-4';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

    <!-- 🌐 Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('icons/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('icons/favicon-96x96.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('icons/site.webmanifest') }}">
    <meta name="theme-color" content="#4F46E5">

    @stack('meta')
    <title>{{ $title ? $title . ' — ' . config('app.name', 'Laravel') : config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- ⚠️ ВАЖНО: Стили должны быть в HEAD --}}
    @stack('styles')
</head>
<body class="overflow-x-hidden font-sans antialiased"
      data-search-query="{{ request('q') }}"
      data-currency="{{ session('currency', 'PRB') }}">

<div
    class="min-h-screen overflow-x-hidden bg-white-100"
    x-data="appShell"
>
<x-toast-stack />

{{-- 🌐 Верхнее меню (десктоп) --}}
@unless($hideHeader)
    @include('layouts.navigation')
    @include('layouts.mobile-topbar')
@endunless

{{-- Заголовок --}}
@isset($header)
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            {{ $header }}
        </div>
    </header>
@endisset

{{-- Контент --}}
<main class="w-full overflow-x-hidden {{ $mainTopPadding }} {{ $flushMain ? 'px-0 pb-0' : (($showBuyerMobileBottomNav || $showSellerMobileBottomNav) ? 'pb-12' : 'pb-0') . ' px-0 sm:px-4 lg:px-6' }}">
    {{ $slot }}
</main>

@unless($hideHeader)
    <footer class="border-t border-slate-100 bg-white px-4 py-10 text-sm text-slate-500 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-8 sm:grid-cols-2 lg:grid-cols-5">
            <div class="sm:col-span-2 lg:col-span-1">
                <div class="flex items-center gap-2 font-bold text-slate-900">
                    <img src="{{ asset('images/icon.png') }}" alt="WebVitrina" class="h-8 w-8 rounded-lg">
                    WebVitrina
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Маркетплейс для покупателей и продавцов Приднестровья.
                </p>
                <a href="{{ route('faq') }}" class="mt-4 inline-flex font-semibold text-indigo-600 hover:text-indigo-700">
                    Вопросы и ответы
                </a>
            </div>

            <nav class="space-y-2">
                <div class="font-bold text-slate-900">Маркетплейс</div>
                <a href="{{ route('about') }}" class="block hover:text-indigo-600">О компании</a>
                <a href="{{ route('contacts') }}" class="block hover:text-indigo-600">Обратная связь</a>
                <a href="{{ route('sitemap') }}" class="block hover:text-indigo-600">Карта сайта</a>
                <a href="{{ route('faq') }}#buyers" class="block hover:text-indigo-600">Вопросы и ответы</a>
            </nav>

            <nav class="space-y-2">
                <div class="font-bold text-slate-900">Покупателю</div>
                <a href="{{ route('orders.index') }}" class="block hover:text-indigo-600">Ваши заказы</a>
                <a href="{{ route('favorites.index') }}" class="block hover:text-indigo-600">Отложенные</a>
                <a href="{{ route('legal.delivery-returns') }}" class="block hover:text-indigo-600">Доставка и возврат</a>
                <a href="{{ route('faq') }}#buyers" class="block hover:text-indigo-600">Помощь покупателю</a>
            </nav>

            <nav class="space-y-2">
                <div class="font-bold text-slate-900">Для продавцов</div>
                <a href="{{ route('seller.cabinet') }}" class="block hover:text-indigo-600">Кабинет продавца</a>
                <a href="{{ route('register') }}" class="block hover:text-indigo-600">Стать продавцом</a>
                <a href="{{ route('faq') }}#sellers" class="block hover:text-indigo-600">Инструкции для продавцов</a>
                <a href="{{ route('legal.seller-terms') }}" class="block hover:text-indigo-600">Правила для продавцов</a>
            </nav>

            <nav class="space-y-2">
                <div class="font-bold text-slate-900">Документы</div>
                <a href="{{ route('legal.privacy') }}" class="block hover:text-indigo-600">Политика обработки персональных данных</a>
                <a href="{{ route('legal.rules') }}" class="block hover:text-indigo-600">Пользовательское соглашение</a>
                <a href="{{ route('legal.seller-terms') }}" class="block hover:text-indigo-600">Требования к товарам продавцов</a>
                <a href="{{ route('contacts') }}" class="block hover:text-indigo-600">Контакты</a>
                <div class="pt-2 text-xs leading-5 text-slate-400">
                    г. Тирасполь<br>
                    +373 (777) 14272<br>
                    Пн-Вс: 9.00 - 18.00
                </div>
            </nav>
        </div>
    </footer>
@endunless


{{-- Нижняя панель - только до 768px --}}
@if($showBuyerMobileBottomNav)
    <div data-mobile-bottom-nav class="block md:hidden fixed bottom-0 left-0 right-0 z-50">
        @include('layouts.mobile-bottom-nav')
    </div>
@endif

@if($showSellerMobileBottomNav)
    <div data-mobile-bottom-seller-nav>
        @include('layouts.mobile-bottom-seller-nav')
    </div>
@endif

{{-- Боковое меню категорий --}}
@include('profile.partials.category-menu')

{{-- Модалки (поиск, фильтры, настройки) --}}
@include('layouts.modals')

</div>

<style>[x-cloak]{display:none!important}</style>

{{-- ⚠️ ВАЖНО: Скрипты должны быть ПЕРЕД закрывающим body --}}
@stack('scripts')

</body>
</html>
