<x-buyer-layout title="Справка WebVitrina">
    <div class="w-full max-w-none space-y-5 bg-white px-3 py-4 pb-24 sm:px-6 sm:py-8">
        <header class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[minmax(0,1fr)_320px] lg:items-center sm:p-5">
            <div class="min-w-0">
                <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-indigo-600">
                    <i class="ri-question-line"></i>
                    Справка покупателя
                </span>
                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Как пользоваться WebVitrina</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Только рабочие подсказки: заказ, доставка, связь с продавцом, отмена и поддержка.
                </p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-slate-950">Нужна помощь по заказу?</p>
                <p class="mt-1 text-sm leading-6 text-slate-500">Поддержка увидит ваш профиль и поможет быстрее, если обращение связано с заказом.</p>
                <a href="{{ route('support') }}" class="mt-4 inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    <i class="ri-customer-service-2-line"></i>
                    Написать в поддержку
                </a>
            </div>
        </header>

        <section class="grid gap-3 md:grid-cols-2">
            @foreach([
                [
                    'title' => 'Как оформить заказ?',
                    'icon' => 'ri-shopping-bag-3-line',
                    'text' => 'Добавьте товары в корзину, проверьте продавцов, адрес доставки и способ оплаты. После подтверждения заказ появится в разделе “Заказы”.',
                    'href' => route('cart.index'),
                    'action' => 'Открыть корзину',
                ],
                [
                    'title' => 'Как работает доставка?',
                    'icon' => 'ri-truck-line',
                    'text' => 'Способ доставки и адрес видны на странице заказа. Когда продавец передаст заказ в доставку, статус изменится на “В пути”.',
                    'href' => route('orders.index'),
                    'action' => 'Мои заказы',
                ],
                [
                    'title' => 'Как связаться с продавцом?',
                    'icon' => 'ri-chat-3-line',
                    'text' => 'Откройте заказ и нажмите “Написать продавцу”. Если в заказе несколько товаров, выберите конкретный товар, чтобы ссылка сразу появилась в чате.',
                    'href' => route('chats.index'),
                    'action' => 'Открыть чаты',
                ],
                [
                    'title' => 'Как отменить заказ?',
                    'icon' => 'ri-close-circle-line',
                    'text' => 'До отправки можно отправить продавцу запрос на отмену со страницы заказа. Причина сохранится в истории заказа.',
                    'href' => route('orders.index', ['tab' => 'active']),
                    'action' => 'Проверить заказы',
                ],
            ] as $item)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xl text-indigo-600">
                            <i class="{{ $item['icon'] }}"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-slate-950">{{ $item['title'] }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['text'] }}</p>
                            <a href="{{ $item['href'] }}" class="mt-4 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                {{ $item['action'] }}
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </section>
    </div>
</x-buyer-layout>
