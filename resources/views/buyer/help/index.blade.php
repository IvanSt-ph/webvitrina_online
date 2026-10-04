<x-buyer-layout title="Справка WebVitrina">
    <div class="min-h-screen w-full overflow-x-hidden bg-white px-3 py-4 pb-28 text-neutral-900 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">
        <div class="w-full space-y-6">
            <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                        <i class="ri-question-line" aria-hidden="true"></i>
                        Справка покупателя
                    </div>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">Как пользоваться WebVitrina</h1>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">Только рабочие подсказки: заказ, доставка, связь с продавцом, отмена и поддержка.</p>
                </div>
                <a href="{{ route('support') }}" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white transition hover:bg-brand-600 sm:w-auto">
                    <i class="ri-customer-service-2-line"></i>Написать в поддержку
                </a>
            </header>

            <section class="grid gap-4 md:grid-cols-2">
                @foreach([
                    ['title' => 'Как оформить заказ?', 'icon' => 'ri-shopping-bag-3-line', 'tone' => 'bg-brand-50 text-brand-600', 'text' => 'Добавьте товары в корзину, проверьте продавцов, адрес доставки и способ оплаты. После подтверждения заказ появится в разделе «Заказы».', 'href' => route('cart.index'), 'action' => 'Открыть корзину'],
                    ['title' => 'Как работает доставка?', 'icon' => 'ri-truck-line', 'tone' => 'bg-sky-50 text-sky-600', 'text' => 'Способ доставки и адрес видны на странице заказа. Когда продавец передаст заказ в доставку, статус изменится на «В пути».', 'href' => route('orders.index'), 'action' => 'Мои заказы'],
                    ['title' => 'Как связаться с продавцом?', 'icon' => 'ri-chat-3-line', 'tone' => 'bg-emerald-50 text-emerald-600', 'text' => 'Откройте заказ и нажмите «Написать продавцу». Если в заказе несколько товаров, выберите конкретный товар, чтобы ссылка сразу появилась в чате.', 'href' => route('chats.index'), 'action' => 'Открыть чаты'],
                    ['title' => 'Как отменить заказ?', 'icon' => 'ri-close-circle-line', 'tone' => 'bg-rose-50 text-rose-600', 'text' => 'До отправки можно отправить продавцу запрос на отмену со страницы заказа. Причина сохранится в истории заказа.', 'href' => route('orders.index', ['tab' => 'active']), 'action' => 'Проверить заказы'],
                ] as $item)
                    <article class="group flex min-w-0 flex-col rounded-2xl border border-neutral-200 bg-white p-5 transition hover:border-brand-200 sm:p-6">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl text-xl {{ $item['tone'] }}"><i class="{{ $item['icon'] }}"></i></span>
                        <h2 class="mt-4 font-semibold text-neutral-950">{{ $item['title'] }}</h2>
                        <p class="mt-2 flex-1 text-sm leading-6 text-neutral-500">{{ $item['text'] }}</p>
                        <a href="{{ $item['href'] }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-brand-600">
                            {{ $item['action'] }}<i class="ri-arrow-right-line transition group-hover:translate-x-0.5"></i>
                        </a>
                    </article>
                @endforeach
            </section>

            <section class="flex flex-col gap-3 rounded-2xl border border-brand-100 bg-brand-50 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div>
                    <h2 class="font-semibold text-brand-950">Не нашли ответ?</h2>
                    <p class="mt-1 text-sm leading-6 text-brand-800/80">Опишите ситуацию поддержке — обращение откроется в приватном чате.</p>
                </div>
                <a href="{{ route('support') }}" class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl border border-brand-200 bg-white px-4 text-sm font-semibold text-brand-700 transition hover:bg-brand-100">Получить помощь</a>
            </section>
        </div>
    </div>
</x-buyer-layout>
