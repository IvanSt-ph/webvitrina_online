<x-buyer-layout title="Вопросы и ответы">
    <div class="mx-auto max-w-4xl space-y-5 px-3 py-4 pb-24 sm:px-6 sm:py-8">
        <section class="rounded-2xl border border-indigo-100 bg-white p-5 text-center shadow-sm sm:p-8">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-indigo-600">
                <i class="ri-question-answer-line"></i>
            </div>
            <h1 class="mt-4 text-2xl font-bold text-slate-950">Вопросы по товарам пока живут в чатах</h1>
            <p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                Отдельный публичный Q&A-раздел ещё не включён, поэтому мы не показываем его как готовую функцию.
                Чтобы задать вопрос, откройте карточку товара или заказ и напишите продавцу.
            </p>
            <div class="mt-5 flex flex-col justify-center gap-2 sm:flex-row">
                <a href="{{ route('home') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    <i class="ri-store-3-line"></i>
                    Найти товар
                </a>
                <a href="{{ route('chats.index') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    <i class="ri-chat-3-line"></i>
                    Мои чаты
                </a>
            </div>
        </section>
    </div>
</x-buyer-layout>
