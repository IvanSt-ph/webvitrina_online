<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('icons/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('icons/favicon-96x96.png') }}">
    <meta name="theme-color" content="#4F46E5">
    <title>{{ request()->routeIs('register') ? 'Регистрация — ' : (request()->routeIs('login') ? 'Вход — ' : '') }}{{ config('app.name', 'Laravel') }}</title>

    <!-- Icons -->
    @once('remixicon-3.5.0')
        @vite('resources/css/remixicon-v3.css')
    @endonce

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="overflow-x-hidden bg-slate-50 font-sans text-slate-900 antialiased">

    <x-toast-stack />

    @if(request()->routeIs('login', 'register'))
        <main class="mx-auto flex w-full max-w-7xl flex-col items-center justify-start gap-3 px-4 py-2.5 sm:min-h-screen sm:justify-center sm:px-6 sm:py-8 lg:px-8">
            <div class="w-full max-w-[1200px] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_8px_28px_rgba(15,23,42,0.06)]">
                {{ $slot }}
            </div>
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl px-4 text-sm font-semibold text-slate-600 transition hover:bg-white hover:text-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 lg:hidden">
                <i class="ri-arrow-left-line" aria-hidden="true"></i> На главную
            </a>
        </main>
    @else
        <div class="flex min-h-screen items-center justify-center sm:px-4 sm:py-10">
            <div class="w-full max-w-5xl overflow-hidden bg-white sm:rounded-3xl sm:border sm:border-slate-200 sm:shadow-[0_16px_48px_rgba(15,23,42,0.06)]">
                {{ $slot }}
            </div>
        </div>
    @endif

</body>
</html>
