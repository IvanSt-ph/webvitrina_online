@php
    $user = auth()->user();
    $isSeller = $user?->isSeller();
    $topics = $isSeller
        ? [
            ['value' => 'Проблема с заказом', 'icon' => 'ri-shopping-bag-3-line', 'title' => 'Заказы', 'text' => 'Обработка, отмена, доставка или общение с покупателем.'],
            ['value' => 'Проблема с товаром', 'icon' => 'ri-box-3-line', 'title' => 'Товары', 'text' => 'Карточка, фото, цена, остатки или публикация.'],
            ['value' => 'Финансы и уровень магазина', 'icon' => 'ri-wallet-3-line', 'title' => 'Финансы и уровень', 'text' => 'Расчёты, показатели и возможности магазина.'],
            ['value' => 'Спор с покупателем', 'icon' => 'ri-shield-user-line', 'title' => 'Спор', 'text' => 'Конфликтная ситуация или вопрос по обращению.'],
            ['value' => 'Безопасность', 'icon' => 'ri-shield-check-line', 'title' => 'Безопасность', 'text' => 'Подозрительные ссылки, спам или доступ к аккаунту.'],
        ]
        : [
            ['value' => 'Проблема с заказом', 'icon' => 'ri-shopping-bag-3-line', 'title' => 'Заказ', 'text' => 'Статус, отмена, оплата, доставка или возврат.'],
            ['value' => 'Проблема с товаром', 'icon' => 'ri-box-3-line', 'title' => 'Товар', 'text' => 'Карточка, фото, цена, остатки или модерация.'],
            ['value' => 'Спор с участником', 'icon' => 'ri-shield-user-line', 'title' => 'Спор', 'text' => 'Конфликт с покупателем или продавцом.'],
            ['value' => 'Безопасность', 'icon' => 'ri-shield-check-line', 'title' => 'Безопасность', 'text' => 'Подозрительные ссылки, спам или просьбы уйти с сайта.'],
        ];
    $statusItems = [
        ['label' => 'Канал', 'value' => 'Внутренний чат', 'icon' => 'ri-message-3-line'],
        ['label' => 'Ваш профиль', 'value' => $isSeller ? 'Продавец' : 'Покупатель', 'icon' => $isSeller ? 'ri-store-2-line' : 'ri-user-3-line'],
        ['label' => 'Ответит', 'value' => 'Администратор', 'icon' => 'ri-customer-service-2-line'],
    ];
@endphp

<x-dynamic-component :component="$chatLayout ?? 'buyer-layout'" title="Служба поддержки" :hideHeader="true">
    <div class="support-mobile-safe min-h-screen min-w-0 overflow-x-hidden bg-white pb-24 text-neutral-800 md:pb-0">
        <header class="border-b border-neutral-200 bg-white">
            <div class="flex w-full flex-col gap-5 px-4 py-6 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <div class="min-w-0">
                    <div class="mb-2 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                        <i class="ri-customer-service-2-line text-base" aria-hidden="true"></i>
                        {{ $isSeller ? 'Поддержка продавцов' : 'WebVitrina support' }}
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight text-neutral-900 sm:text-[28px]">Служба поддержки</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-neutral-500">{{ $isSeller ? 'Поможем с заказами, товарами, магазином, финансами или спорной ситуацией.' : 'Поможем с заказом, товаром, безопасностью или спорной ситуацией.' }}</p>
                </div>
                @if($supportConversation)
                    <x-action-button as="a" :href="route('chats.show', $supportConversation)" class="shrink-0">
                        <i class="ri-chat-3-line" aria-hidden="true"></i>
                        Продолжить support-чат
                    </x-action-button>
                @else
                    <x-secondary-action as="a" href="mailto:support@webvitrina.com" class="shrink-0">
                        <i class="ri-mail-line" aria-hidden="true"></i>
                        Написать по email
                    </x-secondary-action>
                @endif
            </div>
        </header>

        <main class="w-full space-y-5 px-4 py-5 sm:space-y-6 sm:px-6 sm:py-7 lg:px-8 lg:py-8">
            <section class="grid overflow-hidden rounded-2xl border border-brand-100 bg-brand-50/50 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="flex flex-col justify-center p-5 sm:p-6 lg:p-7">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm ring-1 ring-brand-100">
                        <i class="ri-shield-check-line text-xl" aria-hidden="true"></i>
                    </div>
                    <h2 class="mt-4 text-xl font-semibold tracking-tight text-neutral-900 sm:text-2xl">{{ $isSeller ? 'Поможем решить вопрос магазина' : 'Мы рядом, когда нужна помощь' }}</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-600">{{ $isSeller ? 'Выберите рабочую тему и кратко опишите ситуацию. Администратор получит обращение в приватном чате.' : 'Выберите тему и кратко опишите ситуацию. Мы откроем приватный чат, где администратор увидит контекст обращения.' }}</p>
                </div>
                <div class="flex flex-col justify-center border-t border-brand-100 bg-white/60 p-5 sm:p-6 lg:border-l lg:border-t-0">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-success-50 text-xl text-success-600 ring-8 ring-white"><i class="ri-lock-2-line"></i></span>
                        <div>
                            <h3 class="font-semibold text-neutral-900">Безопасный канал</h3>
                            <p class="mt-1 text-sm leading-6 text-neutral-500">Общение проходит внутри WebVitrina. Поддержка никогда не запрашивает пароль или SMS-код.</p>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-3 divide-x divide-neutral-200 rounded-xl bg-neutral-50 py-2.5">
                        @foreach($statusItems as $item)
                            <div class="px-2 text-center">
                                <i class="{{ $item['icon'] }} text-lg text-brand-600" aria-hidden="true"></i>
                                <p class="mt-1 truncate text-[11px] font-semibold text-neutral-700" title="{{ $item['value'] }}">{{ $item['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                <section class="min-w-0 rounded-2xl border border-neutral-200 bg-white">
                    <form method="POST" action="{{ route('support.start') }}" x-data="{ topic: @js(old('topic', 'Проблема с заказом')) }" class="p-5 sm:p-6">
                        @csrf
                        <div class="flex items-start justify-between gap-4 border-b border-neutral-100 pb-5">
                            <div>
                                <h2 class="text-xl font-semibold text-neutral-900">Открыть обращение</h2>
                                <p class="mt-1 text-sm leading-6 text-neutral-500">{{ $supportConversation ? 'Новое обращение добавится отдельным сообщением в существующий чат.' : 'Создадим приватный чат с поддержкой внутри сайта.' }}</p>
                            </div>
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-xl text-white shadow-md shadow-brand-200"><i class="ri-chat-new-line"></i></div>
                        </div>

                        <fieldset class="mt-6">
                            <legend class="mb-3 text-sm font-semibold text-neutral-800">Выберите тему</legend>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach($topics as $topic)
                                    <label class="relative cursor-pointer rounded-2xl border p-4 transition duration-200"
                                           :class="topic === @js($topic['value']) ? 'border-brand-400 bg-brand-50 ring-2 ring-brand-100' : 'border-neutral-200 bg-white hover:border-brand-200 hover:bg-neutral-50'">
                                        <input type="radio" name="topic" value="{{ $topic['value'] }}" x-model="topic" class="sr-only">
                                        <span class="flex items-start gap-3">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition" :class="topic === @js($topic['value']) ? 'bg-brand-500 text-white shadow-sm' : 'bg-brand-50 text-brand-600'"><i class="{{ $topic['icon'] }} text-xl"></i></span>
                                            <span class="min-w-0 pr-5"><span class="block text-sm font-semibold text-neutral-900">{{ $topic['title'] }}</span><span class="mt-1 block text-xs leading-5 text-neutral-500">{{ $topic['text'] }}</span></span>
                                        </span>
                                        <span x-show="topic === @js($topic['value'])" class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full bg-brand-500 text-white"><i class="ri-check-line text-sm"></i></span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div class="mt-6">
                            <label for="support-details" class="mb-2 block text-sm font-semibold text-neutral-800">Что случилось</label>
                            <textarea id="support-details" name="details" rows="5" maxlength="1000"
                                      class="w-full resize-y rounded-2xl border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition placeholder:text-neutral-400 hover:border-brand-200 focus:border-brand-400 focus:ring-4 focus:ring-brand-100"
                                      placeholder="Опишите ситуацию и укажите номер заказа, товара или диалога, если он есть">{{ old('details') }}</textarea>
                            <div class="mt-2 flex items-start justify-between gap-3">
                                @error('details')<p class="text-sm text-danger-600">{{ $message }}</p>@else<p class="text-xs text-neutral-400">До 1000 символов</p>@enderror
                                <span class="text-xs text-neutral-400">Не указывайте пароли и коды</span>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col gap-3 border-t border-neutral-100 pt-5 sm:flex-row">
                            <x-action-button type="submit" class="sm:min-w-52">
                                <i class="ri-send-plane-line" aria-hidden="true"></i>
                                {{ $supportConversation ? 'Добавить в чат' : 'Начать support-чат' }}
                            </x-action-button>
                            <x-secondary-action as="a" href="mailto:support@webvitrina.com"><i class="ri-mail-line"></i>Email</x-secondary-action>
                        </div>
                    </form>
                </section>

                <aside class="min-w-0 space-y-5">
                    <section class="rounded-2xl border border-warning-200 bg-warning-50 p-5">
                        <div class="flex gap-3">
                            <i class="ri-error-warning-line mt-0.5 shrink-0 text-xl text-warning-600"></i>
                            <div><h2 class="text-sm font-semibold text-warning-950">Не отправляйте коды и пароли</h2><p class="mt-1 text-xs leading-5 text-warning-800">Поддержка не попросит пароль, SMS-код или оплату вне WebVitrina.</p></div>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-neutral-200 bg-white p-5">
                        <h2 class="text-base font-semibold text-neutral-900">Что ускорит ответ</h2>
                        <div class="mt-4 space-y-3">
                            @foreach([
                                ['ri-hashtag', 'Номер заказа, товара или диалога.'],
                                ['ri-file-text-line', 'Что ожидали и что произошло.'],
                                ['ri-image-line', 'Скриншоты можно отправить в чате.'],
                                ['ri-shield-check-line', 'О подозрении на мошенничество пишите сразу.'],
                            ] as [$icon, $text])
                                <div class="flex gap-3 rounded-xl bg-neutral-50 p-3"><i class="{{ $icon }} mt-0.5 shrink-0 text-lg text-brand-600"></i><p class="text-sm leading-5 text-neutral-600">{{ $text }}</p></div>
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-2xl border border-neutral-200 bg-white p-5">
                        <h2 class="text-base font-semibold text-neutral-900">Быстрые переходы</h2>
                        <div class="mt-4 grid gap-2">
                            <a href="{{ route('chats.index') }}" class="flex items-center justify-between rounded-xl border border-neutral-200 px-3 py-3 text-sm font-semibold text-neutral-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700"><span class="flex items-center gap-2"><i class="ri-chat-3-line text-lg"></i>Мои чаты</span><i class="ri-arrow-right-s-line"></i></a>
                            @if($isSeller)
                                <a href="{{ route('seller.orders.index') }}" class="flex items-center justify-between rounded-xl border border-neutral-200 px-3 py-3 text-sm font-semibold text-neutral-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700"><span class="flex items-center gap-2"><i class="ri-shopping-bag-3-line text-lg"></i>Заказы продавца</span><i class="ri-arrow-right-s-line"></i></a>
                                <a href="{{ route('seller.products.index') }}" class="flex items-center justify-between rounded-xl border border-neutral-200 px-3 py-3 text-sm font-semibold text-neutral-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700"><span class="flex items-center gap-2"><i class="ri-box-3-line text-lg"></i>Товары</span><i class="ri-arrow-right-s-line"></i></a>
                            @else
                                <a href="{{ route('orders.index') }}" class="flex items-center justify-between rounded-xl border border-neutral-200 px-3 py-3 text-sm font-semibold text-neutral-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700"><span class="flex items-center gap-2"><i class="ri-shopping-bag-3-line text-lg"></i>Мои заказы</span><i class="ri-arrow-right-s-line"></i></a>
                                <a href="{{ route('disputes.index') }}" class="flex items-center justify-between rounded-xl border border-neutral-200 px-3 py-3 text-sm font-semibold text-neutral-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700"><span class="flex items-center gap-2"><i class="ri-scales-3-line text-lg"></i>Мои обращения</span><i class="ri-arrow-right-s-line"></i></a>
                            @endif
                        </div>
                    </section>
                </aside>
            </div>
        </main>
    </div>

    @if($isSeller)
        @include('layouts.mobile-bottom-seller-nav')
    @else
        @include('layouts.mobile-bottom-nav')
    @endif

    <style>
        .support-mobile-safe, .support-mobile-safe * { box-sizing: border-box; }
        .support-mobile-safe { max-width: 100vw; }
    </style>
</x-dynamic-component>
