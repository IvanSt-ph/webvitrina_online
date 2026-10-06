<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('icons/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('icons/favicon-96x96.png') }}">
    <meta name="theme-color" content="#4F46E5">
    <title>{{ request()->routeIs('register') ? 'Регистрация — ' : (request()->routeIs('login') ? 'Вход — ' : (request()->routeIs('password.request') ? 'Восстановление пароля — ' : '')) }}{{ config('app.name', 'Laravel') }}</title>

    <!-- Icons -->
    @once('remixicon-3.5.0')
        @vite('resources/css/remixicon-v3.css')
    @endonce

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .auth-page-background {
            background-color: #f8fafc;
            background-image:
                linear-gradient(rgba(248, 250, 252, 0.55), rgba(248, 250, 252, 0.55)),
                url('/images/auth/webvitrina_auth_background_4k.jpg');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
        }

        @media (max-width: 639px) {
            .auth-page-background {
                background-image:
                    linear-gradient(rgba(248, 250, 252, 0.68), rgba(248, 250, 252, 0.68)),
                    url('/images/auth/webvitrina_auth_background_4k.jpg');
            }

            .auth-register-page {
                background-position: center center, right center;
            }

            .auth-register-page > main {
                min-height: 100vh;
                min-height: 100dvh;
                justify-content: center;
            }
        }

    </style>
</head>

<body class="overflow-x-hidden bg-neutral-50 font-sans text-neutral-900 antialiased {{ request()->routeIs('login', 'register', 'password.request') ? 'auth-page-background' : '' }} {{ request()->routeIs('register') ? 'auth-register-page' : '' }}">

    <x-toast-stack />

    @if(request()->routeIs('login', 'register', 'password.request'))
        <main class="mx-auto flex min-h-screen min-h-[100dvh] w-full max-w-7xl flex-col items-center justify-start gap-3 px-4 py-2.5 sm:justify-center sm:px-6 sm:py-8 lg:px-8">
            <div class="w-full max-w-[1200px] overflow-hidden rounded-2xl border border-neutral-200 bg-white {{ request()->routeIs('register') ? 'shadow-[0_8px_28px_rgba(15,23,42,0.06)]' : '' }}">
                {{ $slot }}
            </div>
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl px-4 text-sm font-semibold text-neutral-600 transition hover:bg-white hover:text-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600 lg:hidden">
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
