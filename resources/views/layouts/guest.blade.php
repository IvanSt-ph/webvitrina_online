<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    @vite('resources/css/manrope.css')

    <!-- Icons -->
    @once('remixicon-3.5.0')
        @vite('resources/css/remixicon-v3.css')
    @endonce

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-slate-100">

    <x-toast-stack />

    <div class="min-h-screen flex items-center justify-center sm:px-4 sm:py-10">
        <div class="w-full {{ request()->routeIs('register') ? 'max-w-6xl lg:aspect-[16/11]' : 'max-w-5xl' }} bg-white overflow-hidden sm:rounded-3xl sm:border sm:border-indigo-100 sm:shadow-2xl">
            {{ $slot }}
        </div>
    </div>

</body>
</html>
