<x-guest-layout>
    <div class="grid min-h-[620px] grid-cols-1 lg:grid-cols-[0.82fr_1.18fr]">
        <section class="hidden flex-col justify-between gap-6 border-r border-neutral-200 bg-neutral-50 p-8 lg:flex xl:p-10">
            <a href="{{ route('home') }}" class="relative inline-flex w-fit items-center gap-2 rounded-xl outline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-500">
                    <img src="{{ asset('images/icon.png') }}" class="h-8 w-8" alt="">
                </span>
                <span class="text-[15px] font-bold tracking-tight text-neutral-800">WebVitrina</span>
            </a>

            <div>
                <span class="wv-page-eyebrow"><i class="ri-key-2-line text-base" aria-hidden="true"></i> Восстановление доступа</span>
                <h1 class="mt-5 text-3xl font-semibold leading-tight tracking-tight text-neutral-950 xl:text-[36px]">Вернём доступ к аккаунту.</h1>
                <p class="mt-3 text-base leading-7 text-neutral-600">Укажите email, привязанный к профилю. Мы отправим на него ссылку для создания нового пароля.</p>

                <div class="mt-8 space-y-3">
                    <div class="flex items-center gap-3 rounded-xl border border-neutral-200 bg-white p-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-xl text-brand-600"><i class="ri-mail-send-line" aria-hidden="true"></i></span>
                        <span class="text-[15px] font-semibold text-neutral-800">Инструкции придут на ваш email</span>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-neutral-200 bg-white p-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-xl text-brand-600"><i class="ri-shield-check-line" aria-hidden="true"></i></span>
                        <span class="text-[15px] font-semibold text-neutral-800">Ссылка действует ограниченное время</span>
                    </div>
                </div>
            </div>

            <a href="{{ route('home') }}" class="inline-flex min-h-11 w-fit items-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 text-sm font-semibold text-neutral-600 transition hover:border-brand-200 hover:text-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600">
                <i class="ri-arrow-left-line" aria-hidden="true"></i> На главную
            </a>
        </section>

        <section class="flex min-w-0 flex-col bg-white">
            <div class="flex flex-1 items-center px-5 py-8 sm:px-8 lg:px-10 lg:py-9 xl:px-12">
                <div class="mx-auto w-full min-w-0 max-w-[500px]">
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-brand-700">Поможем войти</p>
                        <h2 class="mt-1 text-3xl font-semibold tracking-tight text-neutral-950 sm:text-[36px]">Восстановление пароля</h2>
                        <p class="mt-2 text-base leading-6 text-neutral-600">Введите email от аккаунта — на него придут дальнейшие инструкции.</p>
                    </div>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-neutral-800">Email</label>
                            <div class="relative">
                                <i class="ri-mail-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-neutral-400" aria-hidden="true"></i>
                                <input id="email"
                                       type="email"
                                       name="email"
                                       required
                                       autofocus
                                       autocomplete="email"
                                       value="{{ old('email') }}"
                                       class="wv-input h-12 w-full pl-12 pr-4 text-neutral-900 placeholder:text-neutral-400"
                                       placeholder="example@email.com">
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
                        </div>

                        <button type="submit" class="wv-btn-primary h-12 w-full text-base">
                            <i class="ri-send-plane-line text-lg" aria-hidden="true"></i>
                            Отправить ссылку
                        </button>
                    </form>

                    <div class="mt-5 rounded-xl border border-neutral-200 bg-neutral-50 p-3.5">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-lg text-brand-600"><i class="ri-lock-2-line" aria-hidden="true"></i></span>
                            <p class="pt-0.5 text-sm leading-6 text-neutral-600">Никому не передавайте ссылку из письма. Поддержка WebVitrina никогда не запрашивает пароль или код из SMS.</p>
                        </div>
                    </div>

                    <p class="mt-6 text-center text-sm text-neutral-600">
                        Вспомнили пароль?
                        <a href="{{ route('login') }}" class="font-bold text-brand-600 hover:text-brand-800">Вернуться ко входу</a>
                    </p>
                </div>
            </div>
        </section>
    </div>
</x-guest-layout>
