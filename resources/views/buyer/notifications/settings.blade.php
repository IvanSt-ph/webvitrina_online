@php
    $user = auth()->user();
    $prefs = $user->notification_preferences ?? [];
    $enabled = fn (string $key, bool $default = true) => old($key, $prefs[$key] ?? $default);
    $settingsLayout = $user->isSeller() ? 'seller-layout' : 'buyer-layout';

    $siteOptions = [
        'site_orders' => ['ri-shopping-bag-3-line', 'Заказы', 'Новые статусы, отмены и споры.'],
        'site_messages' => ['ri-chat-3-line', 'Сообщения', 'Новые сообщения в чатах.'],
        'site_reviews' => ['ri-star-line', 'Отзывы', 'Модерация и ответы по отзывам.'],
        'site_support' => ['ri-customer-service-2-line', 'Поддержка', 'Ответы и решения поддержки.'],
    ];

    $emailOptions = [
        'email_orders' => ['ri-shopping-bag-3-line', 'Заказы', 'Создание, изменение статуса и отмена.'],
        'email_messages' => ['ri-mail-line', 'Сообщения', 'Важные сообщения от продавца или поддержки.'],
        'email_reviews' => ['ri-star-line', 'Отзывы', 'Результат модерации отзыва.'],
        'email_security' => ['ri-shield-check-line', 'Безопасность', 'Изменения пароля, email, телефона и входа.'],
    ];
@endphp

<x-dynamic-component :component="$settingsLayout" title="Настройки уведомлений">
    <div class="notifications-mobile-safe notifications-settings-safe min-h-screen w-full overflow-x-hidden bg-white pb-24 text-neutral-900 md:pb-0">
        <header class="border-b border-neutral-200 bg-white">
            <div class="flex w-full flex-col gap-4 px-4 py-6 sm:px-6 lg:flex-row lg:items-end lg:justify-between lg:px-8">
                <div class="min-w-0">
                    <a href="{{ route('notifications.index') }}"
                       class="inline-flex items-center gap-2 text-sm font-medium text-neutral-500 transition hover:text-brand-600">
                        <i class="ri-arrow-left-line" aria-hidden="true"></i>
                        Центр уведомлений
                    </a>
                    <div class="mt-4 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                        <i class="ri-settings-3-line" aria-hidden="true"></i>
                        Управление событиями
                    </div>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">Настройки уведомлений</h1>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">
                        Выберите, какие события показывать внутри WebVitrina и какие дублировать на электронную почту.
                    </p>
                </div>

                <a href="{{ route('notifications.index') }}"
                   class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 sm:w-auto">
                    <i class="ri-inbox-2-line" aria-hidden="true"></i>
                    Открыть уведомления
                </a>
            </div>
        </header>

        <main class="w-full px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            @if(session('success'))
                <div class="mb-5 flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                    <i class="ri-checkbox-circle-fill shrink-0 text-lg" aria-hidden="true"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                    <i class="ri-error-warning-fill shrink-0 text-lg" aria-hidden="true"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('notifications.settings.update') }}" class="space-y-5">
                @csrf
                @method('PATCH')

                <div class="grid gap-5 xl:grid-cols-2">
                    <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
                        <div class="flex items-start gap-3 border-b border-neutral-100 px-4 py-4 sm:px-5">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                <i class="ri-notification-3-line text-xl" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h2 class="font-semibold text-neutral-950">На сайте</h2>
                                <p class="mt-1 text-sm leading-5 text-neutral-500">Показываются в центре уведомлений и счётчике меню.</p>
                            </div>
                        </div>

                        <div class="divide-y divide-neutral-100">
                            @foreach($siteOptions as $key => [$icon, $title, $text])
                                <label for="notification-{{ $key }}" class="group flex cursor-pointer items-center gap-3 px-4 py-4 transition hover:bg-neutral-50 sm:px-5">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-500 transition group-hover:bg-brand-50 group-hover:text-brand-600">
                                        <i class="{{ $icon }} text-lg" aria-hidden="true"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-neutral-900">{{ $title }}</span>
                                        <span class="mt-0.5 block text-xs leading-5 text-neutral-500">{{ $text }}</span>
                                    </span>
                                    <input type="hidden" name="{{ $key }}" value="0">
                                    <input id="notification-{{ $key }}" type="checkbox" name="{{ $key }}" value="1" @checked($enabled($key)) class="peer sr-only">
                                    <span class="relative h-6 w-11 shrink-0 rounded-full bg-neutral-200 transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:bg-brand-500 peer-checked:after:translate-x-5 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-100 peer-focus-visible:ring-offset-2" aria-hidden="true"></span>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
                        <div class="flex items-start gap-3 border-b border-neutral-100 px-4 py-4 sm:px-5">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                                <i class="ri-mail-send-line text-xl" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h2 class="font-semibold text-neutral-950">Email уведомления</h2>
                                <p class="mt-1 text-sm leading-5 text-neutral-500">Отправляются на адрес, указанный в вашем профиле.</p>
                            </div>
                        </div>

                        <div class="divide-y divide-neutral-100">
                            @foreach($emailOptions as $key => [$icon, $title, $text])
                                <label for="notification-{{ $key }}" class="group flex cursor-pointer items-center gap-3 px-4 py-4 transition hover:bg-neutral-50 sm:px-5">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-500 transition group-hover:bg-sky-50 group-hover:text-sky-600">
                                        <i class="{{ $icon }} text-lg" aria-hidden="true"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-semibold text-neutral-900">{{ $title }}</span>
                                        <span class="mt-0.5 block text-xs leading-5 text-neutral-500">{{ $text }}</span>
                                    </span>
                                    <input type="hidden" name="{{ $key }}" value="0">
                                    <input id="notification-{{ $key }}" type="checkbox" name="{{ $key }}" value="1" @checked($enabled($key)) class="peer sr-only">
                                    <span class="relative h-6 w-11 shrink-0 rounded-full bg-neutral-200 transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition peer-checked:bg-brand-500 peer-checked:after:translate-x-5 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-100 peer-focus-visible:ring-offset-2" aria-hidden="true"></span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs leading-5 text-neutral-500">
                        Настройки можно изменить в любое время. Важные сообщения безопасности могут отправляться независимо от маркетинговых предпочтений.
                    </p>
                    <button type="submit" class="inline-flex h-11 w-full shrink-0 items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 text-sm font-semibold text-white transition hover:bg-brand-600 sm:w-auto">
                        <i class="ri-save-3-line" aria-hidden="true"></i>
                        Сохранить настройки
                    </button>
                </div>
            </form>
        </main>
    </div>

    @unless($user->isSeller())
        @include('layouts.mobile-bottom-nav')
    @endunless

    <style>
        .notifications-settings-safe,
        .notifications-settings-safe * {
            box-sizing: border-box;
        }
    </style>
</x-dynamic-component>
