<x-app-layout title="Контакты">
    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-indigo-600">
                <i class="ri-customer-service-2-line"></i>
                Контакты
            </span>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Связаться с WebVitrina</h1>
            <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                По вопросам заказов лучше писать в поддержку из аккаунта: так команда увидит контекст заказа, товара или спора. Общие контакты площадки указаны ниже.
            </p>

            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <i class="ri-map-pin-line text-2xl text-indigo-600"></i>
                    <h2 class="mt-3 font-bold text-slate-950">Адрес</h2>
                    <p class="mt-2 text-sm text-slate-600">г. Тирасполь</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <i class="ri-phone-line text-2xl text-indigo-600"></i>
                    <h2 class="mt-3 font-bold text-slate-950">Телефон</h2>
                    <p class="mt-2 text-sm text-slate-600">+373 (778) 64495</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <i class="ri-time-line text-2xl text-indigo-600"></i>
                    <h2 class="mt-3 font-bold text-slate-950">График</h2>
                    <p class="mt-2 text-sm text-slate-600">Пн-Вс: 9:00 - 18:00</p>
                </article>
            </div>

            <div class="mt-8 rounded-2xl border border-indigo-100 bg-indigo-50 p-5">
                <h2 class="font-bold text-slate-950">Нужна помощь по заказу?</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">Войдите в аккаунт и откройте поддержку. Если вопрос связан с заказом, лучше открыть обращение со страницы заказа.</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('support') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <i class="ri-customer-service-2-line"></i>
                            Открыть поддержку
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <i class="ri-login-box-line"></i>
                            Войти и написать
                        </a>
                    @endauth
                    <a href="{{ route('faq') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-white px-5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50">
                        <i class="ri-question-answer-line"></i>
                        Вопросы и ответы
                    </a>
                </div>
            </div>
        </section>
    </main>
</x-app-layout>
