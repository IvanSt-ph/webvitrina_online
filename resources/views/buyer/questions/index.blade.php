<x-buyer-layout title="Вопросы и ответы">
    <div class="min-h-screen w-full overflow-x-hidden bg-white px-3 py-4 pb-28 text-neutral-900 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">
        <div class="w-full space-y-5">
            <header class="min-w-0">
                <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                    <i class="ri-question-answer-line" aria-hidden="true"></i>
                    Вопросы по товарам
                </div>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">Вопросы по товарам пока живут в чатах</h1>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">Отдельный публичный Q&amp;A-раздел ещё не включён, поэтому мы не показываем его как готовую функцию. Чтобы задать вопрос, откройте карточку товара или заказ и напишите продавцу.</p>
            </header>

            <section class="grid overflow-hidden rounded-2xl border border-neutral-200 bg-white lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="flex flex-col justify-center p-5 sm:p-7 lg:p-8">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <i class="ri-chat-smile-3-line text-xl" aria-hidden="true"></i>
                    </span>
                    <h2 class="mt-4 text-xl font-semibold text-neutral-950">Как задать вопрос</h2>
                    <ol class="mt-4 grid gap-3 text-sm text-neutral-600 sm:grid-cols-3">
                        @foreach([
                            ['1', 'Найдите товар', 'Откройте нужную карточку в каталоге.'],
                            ['2', 'Начните чат', 'Нажмите кнопку связи с продавцом.'],
                            ['3', 'Получите ответ', 'История останется в разделе чатов.'],
                        ] as [$number, $title, $text])
                            <li class="rounded-xl bg-neutral-50 p-4">
                                <span class="text-xs font-bold text-brand-600">ШАГ {{ $number }}</span>
                                <strong class="mt-2 block text-neutral-900">{{ $title }}</strong>
                                <span class="mt-1 block text-xs leading-5 text-neutral-500">{{ $text }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="flex flex-col justify-center gap-3 border-t border-neutral-200 bg-neutral-50 p-5 lg:border-l lg:border-t-0 lg:p-6">
                    <a href="{{ route('home') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white transition hover:bg-brand-600">
                        <i class="ri-store-3-line"></i>Найти товар
                    </a>
                    <a href="{{ route('chats.index') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:border-brand-200 hover:text-brand-700">
                        <i class="ri-chat-3-line"></i>Мои чаты
                    </a>
                </div>
            </section>
        </div>
    </div>
</x-buyer-layout>
