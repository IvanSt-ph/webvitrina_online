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
    <link rel="icon" type="image/svg+xml" href="{{ asset('icons/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('icons/favicon-96x96.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('icons/site.webmanifest') }}">
    <meta name="theme-color" content="#4F46E5">

    @stack('meta')
    <title>{{ $title ? $title . ' — ' . config('app.name', 'Laravel') : config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- ⚠️ ВАЖНО: Стили должны быть в HEAD --}}
    @stack('styles')
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
<main class="w-full pt-2 pb-12 px-0 sm:px-4 lg:px-6">
    {{ $slot }}
</main>


{{-- Нижняя панель - только до 768px --}}
@unless(
    request()->routeIs('seller.*') ||   
    request()->routeIs('cabinet')   ||   
    request()->routeIs('profile.*')
)
    <div class="block md:hidden fixed bottom-0 left-0 right-0 z-50">
        @include('layouts.mobile-bottom-nav')
    </div>
@endunless

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