<x-app-layout title="О компании">
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-3xl border border-indigo-100 bg-white shadow-sm">
            <div class="grid gap-8 p-6 lg:grid-cols-[minmax(0,1fr)_380px] lg:p-10">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-indigo-600">
                        <i class="ri-store-3-line"></i>
                        О WebVitrina
                    </span>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Маркетплейс для локальной торговли</h1>
                    <p class="mt-4 max-w-3xl text-base leading-8 text-slate-600">
                        WebVitrina помогает покупателям находить товары у локальных продавцов, а продавцам — вести каталог, получать заказы, общаться с покупателями и развивать магазин в одном кабинете.
                    </p>
                    <div class="mt-6 grid gap-3 sm:grid-cols-3">
                        @foreach([
                            ['value' => 'Каталог', 'label' => 'товары, категории и фильтры'],
                            ['value' => 'Заказы', 'label' => 'корзина, статусы и история'],
                            ['value' => 'Поддержка', 'label' => 'чаты, споры и жалобы'],
                        ] as $item)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="text-lg font-bold text-slate-950">{{ $item['value'] }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ $item['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <aside class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <h2 class="font-bold text-slate-950">Что уже работает</h2>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                        <li class="flex gap-2"><i class="ri-check-line text-emerald-600"></i><span>Публичная витрина товаров и магазинов.</span></li>
                        <li class="flex gap-2"><i class="ri-check-line text-emerald-600"></i><span>Корзина, избранное, заказы и отзывы.</span></li>
                        <li class="flex gap-2"><i class="ri-check-line text-emerald-600"></i><span>Чаты покупателя, продавца и поддержки.</span></li>
                        <li class="flex gap-2"><i class="ri-check-line text-emerald-600"></i><span>Модерация жалоб, отзывов и спорных ситуаций.</span></li>
                    </ul>
                    <a href="{{ route('faq') }}" class="mt-5 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        <i class="ri-question-answer-line"></i>
                        Вопросы и ответы
                    </a>
                </aside>
            </div>
        </section>

        <section class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach([
                ['icon' => 'ri-shield-check-line', 'title' => 'Безопаснее', 'text' => 'Покупатель и продавец сохраняют историю заказа, чата, статусов и обращений.'],
                ['icon' => 'ri-dashboard-3-line', 'title' => 'Удобнее', 'text' => 'У каждой роли свой кабинет: покупатель, продавец и администратор работают с нужными задачами.'],
                ['icon' => 'ri-line-chart-line', 'title' => 'С перспективой', 'text' => 'Площадка готовится к развитию: оплатам, уведомлениям, расширенной аналитике и новым инструментам.'],
            ] as $card)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-xl text-indigo-600">
                        <i class="{{ $card['icon'] }}"></i>
                    </div>
                    <h2 class="mt-4 font-bold text-slate-950">{{ $card['title'] }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $card['text'] }}</p>
                </article>
            @endforeach
        </section>
    </main>
</x-app-layout>
