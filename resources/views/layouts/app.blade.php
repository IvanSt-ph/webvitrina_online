@props(['title' => null, 'hideHeader' => false])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
<link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">

<!-- 🌐 Favicon -->

<link rel="icon" type="image/x-icon" href="{{ asset('icons/favicon.ico') }}">
<!-- Основной favicon в формате .ico — используется в старых браузерах и для обратной совместимости. -->

<link rel="icon" type="image/svg+xml" href="{{ asset('icons/favicon.svg') }}">
<!-- Современный вариант favicon в формате SVG — масштабируется без потери качества и используется в новых браузерах. -->

<link rel="icon" type="image/png" sizes="96x96" href="{{ asset('icons/favicon-96x96.png') }}">
<!-- Альтернативный PNG favicon с размером 96x96 пикселей — обычно нужен для Android и некоторых десктопных систем. -->

<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
<!-- Специальная иконка для устройств Apple (iPhone, iPad). Отображается при добавлении сайта на домашний экран. -->

<link rel="manifest" href="{{ asset('icons/site.webmanifest') }}">
<!-- Файл манифеста Progressive Web App (PWA) — содержит метаданные сайта (иконки, название, цвета и т.д.) для установки как приложение. -->

<meta name="theme-color" content="#4F46E5">
<!-- Цвет оформления для мобильных браузеров (адресная строка и системные элементы интерфейса). Помогает создать фирменный стиль. -->

    @stack('meta')
    <title>{{ $title ? $title . ' — ' . config('app.name', 'Laravel') : config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">

    <script>
document.addEventListener('alpine:init', () => {
    Alpine.store('specs', { open: false });
});
</script>

<div
    class="min-h-screen bg-white-100"
    x-data="{
        open: false,
        openSearch: false,
        openFilters: false,
        openSettings: false,
        clearFilters() {
            const url = new URL(window.location.href);
            url.searchParams.delete('country_id');
            url.searchParams.delete('city_id');
            window.location.href = url.toString();
        }
    }"
>

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
<main class="pt-2 {{-- mt-8 --}} md:pt-1 pb-12 py-20  mx-auto px-4 sm:px-6 lg:px-8">
    {{ $slot }}
</main>

{{-- Нижняя панель (мобилка) --}}
@unless(
    request()->routeIs('seller.*') ||   {{-- все маршруты seller/... --}}
    request()->routeIs('cabinet')   ||   {{-- личный кабинет --}}
    request()->routeIs('profile.*')      {{-- профиль продавца --}}
)
    @include('layouts.mobile-bottom-nav')
@endunless


{{-- Боковое меню категорий --}}
@include('profile.partials.category-menu')

{{-- Модалки (поиск, фильтры, настройки) --}}
@include('layouts.modals')

</div>

<style>[x-cloak]{display:none!important}</style>


@stack('styles')
@stack('scripts')


</body>
</html>
