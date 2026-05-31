<x-buyer-layout title="Уведомления">
    <div class="notifications-mobile-safe w-full max-w-none space-y-5 overflow-x-hidden bg-white px-3 py-4 pb-[5.5rem] sm:px-6 sm:py-8 sm:pb-8">

        <header class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[minmax(0,1fr)_320px] lg:items-center sm:p-5">
            <div class="min-w-0">
                <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-indigo-600">
                    <i class="ri-notification-3-line"></i>
                    Уведомления
                </span>
                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Настройки уведомлений</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Каналы уведомлений готовятся. Пока мы честно показываем их как будущую настройку, а не как рабочие переключатели.
                </p>
            </div>
            <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4 text-sm leading-6 text-indigo-800">
                <p class="font-semibold">Скоро будет доступно</p>
                <p class="mt-1">Email, SMS и Push появятся здесь после подключения отправки и центра предпочтений.</p>
            </div>
        </header>

        <div class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm sm:rounded-2xl">
            <div class="grid min-w-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-3 border-b border-gray-100 p-4">
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-gray-900">Email уведомления</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Заказы, безопасность и важные изменения.</span>
                </span>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500">Скоро</span>
            </div>

            <div class="grid min-w-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-3 border-b border-gray-100 p-4">
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-gray-900">SMS уведомления</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Короткие сообщения по срочным событиям.</span>
                </span>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500">Скоро</span>
            </div>

            <div class="grid min-w-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-3 p-4">
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-gray-900">Push уведомления</span>
                    <span class="mt-0.5 block text-xs text-gray-500">Быстрые уведомления в браузере.</span>
                </span>
                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500">Скоро</span>
            </div>
        </div>

    </div>

    <style>
        .notifications-mobile-safe,
        .notifications-mobile-safe * {
            box-sizing: border-box;
        }

        .notifications-mobile-safe {
            max-width: 100vw;
        }
    </style>
</x-buyer-layout>
