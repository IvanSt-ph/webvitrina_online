@props(['title' => null, 'hideHeader' => false, 'flushMain' => false])

@php
    $hideMobileBottomNav = request()->routeIs('chats.show');
    $isAdminUser = auth()->check() && auth()->user()->role === 'admin';
    $hideMobileBottomNav = $hideMobileBottomNav || (
        $isAdminUser && request()->routeIs('product.show') && request()->integer('admin_chat')
    );
    $isAdminSection = request()->routeIs('admin.*');
    $showSellerMobileBottomNav = auth()->check()
        && ! $isAdminUser
        && auth()->user()->isSeller()
        && ! $hideMobileBottomNav;
    $showBuyerMobileBottomNav = ! $isAdminSection && ! $showSellerMobileBottomNav && ! (
        request()->routeIs('seller.*') ||
        request()->routeIs('cabinet') ||
        request()->routeIs('profile.*') ||
        $hideMobileBottomNav
    );

    $mainTopPadding = $hideHeader ? 'pt-0' : 'pt-0';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @once('remixicon-3.5.0')
        @vite('resources/css/remixicon-v3.css')
    @endonce

    <!-- 🌐 Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('icons/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('icons/favicon-96x96.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('icons/site.webmanifest') }}">
    <meta name="theme-color" content="#4F46E5">

    @stack('meta')
    <title>{{ $title ? $title . ' — ' . config('app.name', 'Laravel') : config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- ⚠️ ВАЖНО: Стили должны быть в HEAD --}}
    @stack('styles')
</head>
<body class="overflow-x-hidden bg-slate-50 font-sans antialiased"
      data-search-query="{{ request('q') }}"
      data-currency="{{ session('currency', 'PRB') }}">

<div
    class="flex min-h-screen flex-col overflow-x-hidden bg-slate-50"
    x-data="appShell"
>
<x-toast-stack />

{{-- 🌐 Верхнее меню (десктоп) --}}
@unless($hideHeader)
    @include('layouts.navigation')
    @include('layouts.mobile-topbar')
    <div class="hidden h-16 lg:block" aria-hidden="true"></div>
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
<main class="w-full flex-1 overflow-x-hidden {{ $mainTopPadding }} {{ $flushMain ? 'px-0 pb-0' : (($showBuyerMobileBottomNav || $showSellerMobileBottomNav) ? 'pb-12 md:pb-20 lg:pb-12' : 'pb-0') . ' px-0 sm:px-4 lg:px-6' }}">
    {{ $slot }}
</main>

@unless($hideHeader)
    <footer class="border-t border-slate-200 bg-white/95 px-4 py-10 text-sm text-slate-500 shadow-[0_-10px_40px_rgba(15,23,42,0.03)] sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-8 sm:grid-cols-2 lg:grid-cols-5">
            <div class="sm:col-span-2 lg:col-span-1">
                <div class="flex items-center gap-2 font-bold text-slate-900">
                    <img src="{{ asset('images/icon.png') }}" alt="WebVitrina" class="h-8 w-8 rounded-lg">
                    WebVitrina
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-500">
                    Онлайн-площадка для покупателей и продавцов: каталог товаров, заказы, чаты, отзывы и поддержка.
                </p>
                <a href="{{ route('faq') }}" class="mt-4 inline-flex font-semibold text-indigo-600 hover:text-indigo-700">
                    Центр помощи
                </a>
            </div>

            <nav class="space-y-2">
                <div class="font-bold text-slate-900">Площадка</div>
                <a href="{{ route('about') }}" class="block hover:text-indigo-600">О WebVitrina</a>
                <a href="{{ route('contacts') }}" class="block hover:text-indigo-600">Контакты</a>
                <a href="{{ route('sitemap') }}" class="block hover:text-indigo-600">Карта сайта</a>
                <a href="{{ route('faq') }}" class="block hover:text-indigo-600">Вопросы и ответы</a>
            </nav>

            <nav class="space-y-2">
                <div class="font-bold text-slate-900">Покупателю</div>
                <a href="{{ route('orders.index') }}" class="block hover:text-indigo-600">Мои заказы</a>
                <a href="{{ route('favorites.index') }}" class="block hover:text-indigo-600">Избранное</a>
                <a href="{{ route('legal.buyer-rules') }}" class="block hover:text-indigo-600">Покупки, заказы и отмены</a>
                <a href="{{ route('legal.review-rules') }}" class="block hover:text-indigo-600">Правила отзывов</a>
                <a href="{{ route('faq') }}#buyers" class="block hover:text-indigo-600">Помощь покупателю</a>
            </nav>

            <nav class="space-y-2">
                <div class="font-bold text-slate-900">Для продавцов</div>
                <a href="{{ route('seller.cabinet') }}" class="block hover:text-indigo-600">Кабинет продавца</a>
                <a href="{{ route('register') }}" class="block hover:text-indigo-600">Стать продавцом</a>
                <a href="{{ route('faq') }}#sellers" class="block hover:text-indigo-600">Помощь продавцу</a>
                <a href="{{ route('legal.seller-rules') }}" class="block hover:text-indigo-600">Правила для продавцов</a>
                <a href="{{ route('legal.prohibited-products') }}" class="block hover:text-indigo-600">Запрещённые товары</a>
            </nav>

            <nav class="space-y-2">
                <div class="font-bold text-slate-900">Правовая информация</div>
                <a href="{{ route('legal.privacy') }}" class="block hover:text-indigo-600">Политика конфиденциальности</a>
                <a href="{{ route('legal.terms') }}" class="block hover:text-indigo-600">Условия использования</a>
                <a href="{{ route('legal.cookies') }}" class="block hover:text-indigo-600">Политика cookies</a>
                <a href="{{ route('contacts') }}" class="block hover:text-indigo-600">Контакты и реквизиты</a>
            </nav>
        </div>
        <div class="mx-auto mt-8 flex max-w-7xl flex-col gap-2 border-t border-slate-100 pt-5 pb-8 text-xs leading-5 text-slate-400 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} WebVitrina. Все права защищены.</p>
            <p>Информация на сайте не является публичной офертой, если иное прямо не указано в карточке товара или условиях заказа.</p>
        </div>
    </footer>
@endunless


{{-- Нижняя панель видна до появления desktop header. --}}
@if($showBuyerMobileBottomNav)
    <div data-mobile-bottom-nav class="block lg:hidden fixed bottom-0 left-0 right-0 z-50">
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
