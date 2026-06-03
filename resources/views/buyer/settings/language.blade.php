@php
    $current = old('locale', auth()->user()->locale ?? session('locale', 'ru'));
@endphp

<x-buyer-layout title="Язык интерфейса">
    <div class="w-full max-w-none space-y-5 bg-white px-3 py-4 pb-24 sm:px-6 sm:py-8">
        <header class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-indigo-600">
                <i class="ri-translate-2"></i>
                Язык
            </span>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Язык интерфейса</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Настройка сохраняется в профиле. Полный перевод интерфейса можно расширять постепенно.</p>
        </header>

        <form method="POST" action="{{ route('settings.language.update') }}" class="space-y-4">
            @csrf
            @method('PATCH')
            <div class="divide-y rounded-xl border border-slate-200 bg-white shadow-sm">
                @foreach([
                    'ru' => ['🇷🇺', 'Русский'],
                    'en' => ['🇬🇧', 'English'],
                    'uk' => ['🇺🇦', 'Українська'],
                    'ro' => ['🇷🇴', 'Română'],
                ] as $code => [$flag, $label])
                    <label class="flex cursor-pointer items-center justify-between px-4 py-3 transition hover:bg-slate-50">
                        <span class="flex items-center gap-3">
                            <span class="text-xl">{{ $flag }}</span>
                            <span class="font-medium text-slate-800">{{ $label }}</span>
                        </span>
                        <input type="radio" name="locale" value="{{ $code }}" @checked($current === $code) class="text-indigo-600 focus:ring-indigo-500">
                    </label>
                @endforeach
            </div>
            <button class="w-full rounded-xl bg-indigo-600 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                Сохранить язык
            </button>
        </form>
    </div>
</x-buyer-layout>
