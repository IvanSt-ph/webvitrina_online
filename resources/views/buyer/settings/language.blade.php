@php
    $user = auth()->user();
    $current = old('locale', $user->locale ?? session('locale', 'ru'));
    $settingsLayout = $user->isSeller() ? 'seller-layout' : 'buyer-layout';
    $languages = [
        'ru' => ['🇷🇺', 'Русский', 'Русский язык интерфейса'],
        'en' => ['🇬🇧', 'English', 'English interface language'],
        'uk' => ['🇺🇦', 'Українська', 'Українська мова інтерфейсу'],
        'ro' => ['🇷🇴', 'Română', 'Limba română a interfeței'],
    ];
@endphp

<x-dynamic-component :component="$settingsLayout" title="Язык интерфейса">
    <div class="min-h-screen w-full overflow-x-hidden bg-white px-3 py-4 pb-28 text-neutral-900 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">
        <div class="w-full space-y-5">
            <header class="min-w-0">
                <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                    <i class="ri-translate-2" aria-hidden="true"></i>
                    Региональные настройки
                </div>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">Язык интерфейса</h1>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">Настройка сохраняется в профиле и применяется к доступным переводам интерфейса.</p>
            </header>

            @if(session('success'))
                <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                    <i class="ri-checkbox-circle-fill text-lg"></i>{{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('settings.language.update') }}" class="space-y-5">
                @csrf
                @method('PATCH')

                <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
                    <div class="border-b border-neutral-100 px-4 py-4 sm:px-5">
                        <h2 class="font-semibold text-neutral-950">Язык WebVitrina</h2>
                        <p class="mt-1 text-sm text-neutral-500">Некоторые разделы могут переводиться постепенно.</p>
                    </div>
                    <div class="divide-y divide-neutral-100">
                        @foreach($languages as $code => [$short, $label, $description])
                            <label class="group flex cursor-pointer items-center gap-4 px-4 py-4 transition hover:bg-neutral-50 sm:px-5">
                                <input type="radio" name="locale" value="{{ $code }}" @checked($current === $code) class="peer sr-only">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-xs font-bold text-neutral-600 transition peer-checked:bg-brand-50 peer-checked:text-brand-700">{{ $short }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-neutral-900">{{ $label }}</span>
                                    <span class="mt-0.5 block text-xs text-neutral-500">{{ $description }}</span>
                                </span>
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-neutral-300 transition peer-checked:border-brand-500 peer-checked:bg-brand-500">
                                    <i class="ri-check-line text-sm text-white" aria-hidden="true"></i>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </section>

                <div class="flex justify-end border-t border-neutral-200 pt-5">
                    <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 text-sm font-semibold text-white transition hover:bg-brand-600 sm:w-auto">
                        <i class="ri-save-3-line"></i>Сохранить язык
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-dynamic-component>
