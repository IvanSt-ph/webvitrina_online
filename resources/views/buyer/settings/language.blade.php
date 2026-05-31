<x-buyer-layout title="Язык интерфейса">
    <div class="w-full max-w-none space-y-5 bg-white px-3 py-4 pb-24 sm:px-6 sm:py-8">
        <header class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[minmax(0,1fr)_320px] lg:items-center sm:p-5">
            <div class="min-w-0">
                <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-indigo-600">
                    <i class="ri-translate-2"></i>
                    Язык
                </span>
                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Язык интерфейса</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Выберите язык приложения. Сохранение будет подключено после включения локализации.</p>
            </div>
            <div class="rounded-xl border border-amber-100 bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                <p class="font-semibold">Предпросмотр настройки</p>
                <p class="mt-1">Пока это честный экран будущей функции, без ложного сохранения.</p>
            </div>
        </header>

        <div class="divide-y rounded-xl border border-slate-200 bg-white shadow-sm">

            <label class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🇷🇺</span>
                    <span>Русский</span>
                </div>
                <input type="radio" name="lang" checked>
            </label>

            <label class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🇬🇧</span>
                    <span>English</span>
                </div>
                <input type="radio" name="lang">
            </label>

            <label class="flex items-center justify-between px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="text-xl">🇺🇦</span>
                    <span>Українська</span>
                </div>
                <input type="radio" name="lang">
            </label>

        </div>

        <button class="w-full rounded-xl bg-slate-200 py-3 text-sm font-semibold text-slate-500" disabled>
            Сохранение скоро будет доступно
        </button>

    </div>
</x-buyer-layout>
