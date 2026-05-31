<x-buyer-layout title="Чаты">
    <div class="mx-auto max-w-3xl px-3 py-4 pb-24 sm:px-6 sm:py-8">
        <section class="rounded-2xl border border-indigo-100 bg-white p-6 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-indigo-600">
                <i class="ri-chat-3-line"></i>
            </div>
            <h1 class="mt-4 text-2xl font-bold text-slate-950">Чаты уже доступны</h1>
            <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">
                Основной экран сообщений находится в общем разделе чатов. Там видны диалоги с продавцами и поддержкой.
            </p>
            <a href="{{ route('chats.index') }}" class="mt-5 inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">
                <i class="ri-arrow-right-line"></i>
                Открыть мои чаты
            </a>
        </section>
    </div>
</x-buyer-layout>
