<x-buyer-layout title="Уведомления">
    <div class="notifications-mobile-safe w-full max-w-none overflow-x-hidden px-3 py-4 pb-[5.5rem] sm:px-6 sm:py-8 sm:pb-8 space-y-5">

        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm">
                <i class="ri-notification-3-line text-xl"></i>
            </div>
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold text-gray-900">Уведомления</h1>
                <p class="mt-1 text-sm text-gray-500">Управление каналами уведомлений скоро будет доступно.</p>
            </div>
        </div>

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
