@php
    $user = auth()->user();
    $isSeller = $user?->isSeller();
    $topics = [
        ['value' => 'Проблема с заказом', 'icon' => 'ri-shopping-bag-3-line', 'title' => 'Заказ', 'text' => 'Статус, отмена, оплата, доставка или возврат.'],
        ['value' => 'Проблема с товаром', 'icon' => 'ri-box-3-line', 'title' => 'Товар', 'text' => 'Карточка, фото, цена, остатки или модерация.'],
        ['value' => 'Спор с участником', 'icon' => 'ri-shield-user-line', 'title' => 'Спор', 'text' => 'Конфликт с покупателем или продавцом.'],
        ['value' => 'Безопасность', 'icon' => 'ri-shield-check-line', 'title' => 'Безопасность', 'text' => 'Подозрительные ссылки, спам или просьбы уйти с сайта.'],
    ];
    $statusItems = [
        ['label' => 'Канал', 'value' => 'Внутренний чат', 'icon' => 'ri-message-3-line'],
        ['label' => 'Контекст', 'value' => $isSeller ? 'Продавец' : 'Покупатель', 'icon' => $isSeller ? 'ri-store-2-line' : 'ri-user-3-line'],
        ['label' => 'Доступ', 'value' => 'Администратор', 'icon' => 'ri-customer-service-2-line'],
    ];
@endphp

<x-dynamic-component :component="$chatLayout ?? 'buyer-layout'" title="Служба поддержки" :hideHeader="true">
    <div class="support-mobile-safe mx-auto w-full max-w-8xl min-w-0 px-3 py-4 pb-24 sm:px-5 sm:py-6 lg:px-6 lg:pb-8">
        <div class="space-y-5">
            <header class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[1fr_340px] lg:items-center">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                        <i class="ri-customer-service-2-line"></i>
                        WebVitrina support
                    </div>
                    <h1 class="mt-3 text-2xl font-bold text-slate-950 sm:text-3xl">Поможем с заказом, товаром или спором</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Выберите тему и отправьте короткое описание. Мы откроем приватный support-чат, где администратор увидит контекст обращения.
                    </p>
                </div>

                <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-xl text-indigo-600 shadow-sm">
                            <i class="ri-shield-check-line"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-950">Безопасный канал</p>
                            <p class="mt-1 text-xs leading-5 text-slate-600">Администратор отвечает внутри WebVitrina. Пароли и SMS-коды не нужны.</p>
                        </div>
                    </div>
                    @if($supportConversation)
                        <a href="{{ route('chats.show', $supportConversation) }}"
                           class="mt-4 inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                            <i class="ri-arrow-right-line"></i>
                            Продолжить support-чат
                        </a>
                    @endif
                </div>
            </header>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_380px]">
            <section class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="grid gap-4 p-4 lg:grid-cols-[minmax(0,1fr)_320px] sm:p-6">
                    <form method="POST" action="{{ route('support.start') }}" x-data="{ topic: 'Проблема с заказом' }" class="min-w-0 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                        @csrf
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-bold text-slate-950">Открыть обращение</h2>
                                <p class="mt-1 text-sm leading-6 text-slate-500">
                                    {{ $supportConversation ? 'Чат уже создан, новое обращение добавится в него отдельным сообщением.' : 'Создадим отдельный чат с поддержкой внутри сайта.' }}
                                </p>
                            </div>
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/20">
                                <i class="ri-chat-3-line text-2xl"></i>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach($topics as $topic)
                                <label class="group relative cursor-pointer rounded-2xl border p-3 shadow-sm transition"
                                       :class="topic === @js($topic['value'])
                                           ? 'border-indigo-500 bg-indigo-600 text-white shadow-lg shadow-indigo-600/20 ring-2 ring-indigo-200'
                                           : 'border-white bg-white text-slate-900 ring-1 ring-slate-200 hover:border-indigo-200 hover:ring-indigo-200'">
                                    <input type="radio" name="topic" value="{{ $topic['value'] }}" x-model="topic" class="sr-only" @checked($loop->first)>
                                    <span class="flex items-start gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition"
                                              :class="topic === @js($topic['value']) ? 'bg-white/18 text-white ring-1 ring-white/25' : 'bg-indigo-50 text-indigo-600'">
                                            <i class="{{ $topic['icon'] }} text-xl"></i>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-sm font-bold">{{ $topic['title'] }}</span>
                                            <span class="mt-1 block text-xs leading-5"
                                                  :class="topic === @js($topic['value']) ? 'text-indigo-50' : 'text-slate-500'">{{ $topic['text'] }}</span>
                                        </span>
                                    </span>
                                    <span x-show="topic === @js($topic['value'])" class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full bg-white text-indigo-600">
                                        <i class="ri-check-line text-sm"></i>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <div class="mt-5">
                            <label for="support-details" class="mb-2 block text-sm font-bold text-slate-800">Что случилось</label>
                            <textarea id="support-details"
                                      name="details"
                                      rows="4"
                                      maxlength="1000"
                                      class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                                      placeholder="Например: покупатель не отвечает, товар заблокирован, нужна помощь с заказом..."></textarea>
                            @error('details')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                            <button class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                                <i class="ri-send-plane-line"></i>
                                {{ $supportConversation ? 'Добавить в чат' : 'Начать support-чат' }}
                            </button>
                            <a href="mailto:support@webvitrina.com"
                               class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                <i class="ri-mail-line"></i>
                                Email
                            </a>
                        </div>
                    </form>

                    <div class="grid min-w-0 gap-3">
                        @foreach($statusItems as $item)
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                        <i class="{{ $item['icon'] }} text-xl"></i>
                                    </span>
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $item['label'] }}</p>
                                        <p class="mt-1 text-sm font-bold text-slate-900">{{ $item['value'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                            <div class="flex gap-3">
                                <i class="ri-error-warning-line mt-0.5 text-xl text-amber-600"></i>
                                <div>
                                    <h3 class="text-sm font-bold text-amber-950">Не отправляйте коды и пароли</h3>
                                    <p class="mt-1 text-xs leading-5 text-amber-800">
                                        Поддержка не попросит пароль, SMS-код или оплату вне WebVitrina.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="grid min-w-0 gap-4">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="text-base font-bold text-slate-950">Что ускорит ответ</h2>
                    <div class="mt-4 space-y-3">
                        @foreach([
                            ['icon' => 'ri-hashtag', 'text' => 'Номер заказа, товара или диалога, если он есть.'],
                            ['icon' => 'ri-file-text-line', 'text' => 'Короткое описание: что ожидали и что произошло.'],
                            ['icon' => 'ri-image-line', 'text' => 'Скриншоты можно отправить уже в открытом чате.'],
                            ['icon' => 'ri-shield-check-line', 'text' => 'Если есть подозрение на мошенничество, пишите сразу.'],
                        ] as $item)
                            <div class="flex gap-3 rounded-xl bg-slate-50 p-3">
                                <i class="{{ $item['icon'] }} mt-0.5 text-lg text-indigo-600"></i>
                                <p class="text-sm leading-6 text-slate-600">{{ $item['text'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="text-base font-bold text-slate-950">Быстрые переходы</h2>
                    <div class="mt-4 grid gap-2">
                        <a href="{{ route('chats.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                            <span class="flex items-center gap-2"><i class="ri-chat-3-line text-lg"></i> Мои чаты</span>
                            <i class="ri-arrow-right-s-line"></i>
                        </a>
                        @if($isSeller)
                            <a href="{{ route('seller.orders.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                <span class="flex items-center gap-2"><i class="ri-shopping-bag-3-line text-lg"></i> Заказы продавца</span>
                                <i class="ri-arrow-right-s-line"></i>
                            </a>
                            <a href="{{ route('seller.products.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                <span class="flex items-center gap-2"><i class="ri-box-3-line text-lg"></i> Товары</span>
                                <i class="ri-arrow-right-s-line"></i>
                            </a>
                        @else
                            <a href="{{ route('orders.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                <span class="flex items-center gap-2"><i class="ri-shopping-bag-3-line text-lg"></i> Мои заказы</span>
                                <i class="ri-arrow-right-s-line"></i>
                            </a>
                            <a href="{{ route('cart.index') }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                <span class="flex items-center gap-2"><i class="ri-shopping-cart-line text-lg"></i> Корзина</span>
                                <i class="ri-arrow-right-s-line"></i>
                            </a>
                        @endif
                    </div>
                </section>
            </aside>
        </div>
        </div>
    </div>
</x-dynamic-component>
