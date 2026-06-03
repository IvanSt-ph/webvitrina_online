@php
    $prefs = auth()->user()->notification_preferences ?? [];
    $enabled = fn (string $key, bool $default = true) => old($key, $prefs[$key] ?? $default);
@endphp

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
                    Выберите, какие события показывать в центре уведомлений и какие отправлять на email.
                </p>
            </div>
            <a href="{{ route('notifications.index') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">
                <i class="ri-inbox-2-line"></i>
                Центр уведомлений
            </a>
        </header>

        <form method="POST" action="{{ route('notifications.settings.update') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            @csrf
            @method('PATCH')

            <div class="grid gap-4 lg:grid-cols-2">
                <section class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <h2 class="font-semibold text-slate-950">На сайте</h2>
                    <p class="mt-1 text-sm text-slate-500">Показываются в центре уведомлений и бейдже меню.</p>
                    <div class="mt-4 divide-y divide-slate-200 rounded-xl bg-white">
                        @foreach([
                            'site_orders' => ['Заказы', 'Новые статусы, отмены и споры.'],
                            'site_messages' => ['Сообщения', 'Новые сообщения в чатах.'],
                            'site_reviews' => ['Отзывы', 'Модерация и ответы по отзывам.'],
                            'site_support' => ['Поддержка', 'Ответы и решения поддержки.'],
                        ] as $key => [$title, $text])
                            <label class="flex items-center justify-between gap-3 px-4 py-3">
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">{{ $title }}</span>
                                    <span class="block text-xs text-slate-500">{{ $text }}</span>
                                </span>
                                <input type="hidden" name="{{ $key }}" value="0">
                                <input type="checkbox" name="{{ $key }}" value="1" @checked($enabled($key)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            </label>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <h2 class="font-semibold text-slate-950">Email уведомления</h2>
                    <p class="mt-1 text-sm text-slate-500">Минимальная рабочая настройка для важных писем.</p>
                    <div class="mt-4 divide-y divide-slate-200 rounded-xl bg-white">
                        @foreach([
                            'email_orders' => ['Заказы', 'Создание, изменение статуса и отмена.'],
                            'email_messages' => ['Сообщения', 'Важные сообщения от продавца или поддержки.'],
                            'email_reviews' => ['Отзывы', 'Результат модерации отзыва.'],
                            'email_security' => ['Безопасность', 'Пароль, email, телефон и вход.'],
                        ] as $key => [$title, $text])
                            <label class="flex items-center justify-between gap-3 px-4 py-3">
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">{{ $title }}</span>
                                    <span class="block text-xs text-slate-500">{{ $text }}</span>
                                </span>
                                <input type="hidden" name="{{ $key }}" value="0">
                                <input type="checkbox" name="{{ $key }}" value="1" @checked($enabled($key)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            </label>
                        @endforeach
                    </div>
                </section>
            </div>

            <button class="mt-5 inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                <i class="ri-save-3-line"></i>
                Сохранить настройки
            </button>
        </form>
    </div>
</x-buyer-layout>
