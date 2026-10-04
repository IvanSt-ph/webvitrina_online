<x-app-layout title="О компании">
    <main class="min-h-screen w-full overflow-x-hidden bg-white px-4 py-6 text-neutral-900 sm:px-6 sm:py-8 lg:px-8">
        <div class="w-full space-y-6">
            <section class="grid overflow-hidden rounded-2xl border border-neutral-200 bg-white lg:grid-cols-[minmax(0,1fr)_380px]">
                <div class="p-5 sm:p-7 lg:p-8">
                    <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                        <i class="ri-store-3-line"></i>О WebVitrina
                    </div>
                    <h1 class="mt-3 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-3xl">Маркетплейс для локальной торговли</h1>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-neutral-600 sm:text-base">WebVitrina помогает покупателям находить товары у локальных продавцов, а продавцам — вести каталог, получать заказы, общаться с покупателями и развивать магазин в одном кабинете.</p>

                    <div class="mt-6 grid gap-3 sm:grid-cols-3">
                        @foreach([
                            ['ri-layout-grid-line', 'Каталог', 'Товары, категории и фильтры'],
                            ['ri-shopping-bag-3-line', 'Заказы', 'Корзина, статусы и история'],
                            ['ri-customer-service-2-line', 'Поддержка', 'Чаты, споры и обращения'],
                        ] as [$icon, $value, $label])
                            <div class="rounded-xl bg-neutral-50 p-4">
                                <i class="{{ $icon }} text-lg text-brand-600"></i>
                                <div class="mt-2 font-semibold text-neutral-950">{{ $value }}</div>
                                <div class="mt-1 text-xs leading-5 text-neutral-500">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <aside class="border-t border-neutral-200 bg-neutral-50 p-5 sm:p-6 lg:border-l lg:border-t-0">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><i class="ri-checkbox-circle-line text-xl"></i></span>
                    <h2 class="mt-4 font-semibold text-neutral-950">Что уже работает</h2>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-neutral-600">
                        <li class="flex gap-2"><i class="ri-check-line shrink-0 text-emerald-600"></i><span>Публичная витрина товаров и магазинов.</span></li>
                        <li class="flex gap-2"><i class="ri-check-line shrink-0 text-emerald-600"></i><span>Корзина, избранное, заказы и отзывы.</span></li>
                        <li class="flex gap-2"><i class="ri-check-line shrink-0 text-emerald-600"></i><span>Чаты покупателя, продавца и поддержки.</span></li>
                        <li class="flex gap-2"><i class="ri-check-line shrink-0 text-emerald-600"></i><span>Модерация жалоб, отзывов и спорных ситуаций.</span></li>
                    </ul>
                    <a href="{{ route('faq') }}" class="mt-5 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white transition hover:bg-brand-600">
                        <i class="ri-question-answer-line"></i>Вопросы и ответы
                    </a>
                </aside>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                @foreach([
                    ['ri-shield-check-line', 'Безопаснее', 'Покупатель и продавец сохраняют историю заказа, чата, статусов и обращений.'],
                    ['ri-dashboard-3-line', 'Удобнее', 'У каждой роли свой кабинет: покупатель, продавец и администратор работают с нужными задачами.'],
                    ['ri-line-chart-line', 'С перспективой', 'Площадка готовится к развитию: оплатам, уведомлениям, расширенной аналитике и новым инструментам.'],
                ] as [$icon, $title, $text])
                    <article class="rounded-2xl border border-neutral-200 bg-white p-5">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i class="{{ $icon }} text-lg"></i></span>
                        <h2 class="mt-4 font-semibold text-neutral-950">{{ $title }}</h2>
                        <p class="mt-2 text-sm leading-6 text-neutral-500">{{ $text }}</p>
                    </article>
                @endforeach
            </section>
        </div>
    </main>
</x-app-layout>
