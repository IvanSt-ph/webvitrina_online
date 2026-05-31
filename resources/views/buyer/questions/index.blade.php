<x-buyer-layout title="Вопросы и ответы">
    <div class="w-full max-w-none space-y-5 bg-white px-3 py-4 pb-24 sm:px-6 sm:py-8">
        <section class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[minmax(0,1fr)_320px] lg:items-center sm:p-5">
            <div class="min-w-0">
                <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-indigo-600">
                    <i class="ri-question-answer-line"></i>
                    Вопросы и ответы
                </span>
                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Вопросы по товарам пока живут в чатах</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                Отдельный публичный Q&A-раздел ещё не включён, поэтому мы не показываем его как готовую функцию.
                Чтобы задать вопрос, откройте карточку товара или заказ и напишите продавцу.
                </p>
            </div>
            <div class="flex flex-col justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
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
