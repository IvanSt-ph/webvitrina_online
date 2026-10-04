<x-guest-layout>
    <div class="grid min-h-[680px] grid-cols-1 lg:grid-cols-[0.82fr_1.18fr]">
        <section class="hidden flex-col justify-between gap-6 border-r border-neutral-200 bg-neutral-50 p-8 lg:flex xl:p-10">
            <a href="{{ route('home') }}" class="relative inline-flex w-fit items-center gap-2 rounded-xl outline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-500">
                    <img src="{{ asset('images/icon.png') }}" class="h-8 w-8" alt="">
                </span>
                <span class="text-[15px] font-bold tracking-tight text-neutral-800">WebVitrina</span>
            </a>

            <div>
                <span class="wv-page-eyebrow"><i class="ri-shield-check-line text-base"></i> Ваш аккаунт</span>
                <h1 class="mt-5 text-3xl font-semibold leading-tight tracking-tight text-neutral-950 xl:text-[36px]">Всё важное рядом.</h1>
                <p class="mt-3 text-base leading-7 text-neutral-600">Заказы, товары и диалоги с продавцами — в вашем профиле WebVitrina.</p>

                <div class="mt-8 space-y-3">
                    <div class="flex items-center gap-3 rounded-xl border border-neutral-200 bg-white p-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-xl text-brand-600"><i class="ri-shopping-bag-3-line"></i></span>
                        <span class="text-[15px] font-semibold text-neutral-800">Следите за заказами и покупками</span>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-neutral-200 bg-white p-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-xl text-brand-600"><i class="ri-store-2-line"></i></span>
                        <span class="text-[15px] font-semibold text-neutral-800">Управляйте магазином и чатами</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('home') }}" class="inline-flex min-h-11 w-fit items-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 text-sm font-semibold text-neutral-600 transition hover:border-brand-200 hover:text-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600">
                <i class="ri-arrow-left-line" aria-hidden="true"></i> На главную
            </a>
        </section>

        <section class="flex min-w-0 flex-col bg-white">
            <div class="flex flex-1 items-center px-5 py-7 sm:px-8 lg:px-10 lg:py-9 xl:px-12">
                <div class="mx-auto w-full min-w-0 max-w-[500px]">
                    <div class="mb-6">
                        <p class="text-sm font-semibold text-brand-700">С возвращением</p>
                        <h2 class="mt-1 text-3xl font-semibold tracking-tight text-neutral-950 sm:text-[36px]">Войти в аккаунт</h2>
                        <p class="mt-2 text-base leading-6 text-neutral-600">
                            Используйте email или телефон, привязанный к вашему профилю.
                        </p>
                    </div>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    @if($errors->any())
                        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4">
                            <div class="mb-2 flex items-center gap-2 font-bold text-rose-700">
                                <i class="ri-error-warning-line"></i>
                                Не удалось войти
                            </div>
                            <ul class="space-y-1 text-sm text-rose-600">
                                @foreach($errors->all() as $error)
                                    <li class="flex items-center gap-2">
                                        <i class="ri-close-circle-fill text-xs"></i>
                                        {{ $error }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" id="login-form" class="space-y-4">
                        @csrf

                        <div x-data="{
                            loginType: 'email',
                            loginValue: @js(old('login')),
                            isEmail(value) {
                                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                                return emailRegex.test(value);
                            }
                        }" x-init="if(loginValue && !isEmail(loginValue)) loginType = 'phone'">
                            <div class="mb-3 grid grid-cols-2 gap-1 rounded-xl border border-neutral-200 bg-neutral-100 p-1">
                                <button type="button"
                                        @click="loginType = 'email'"
                                        :class="loginType === 'email' ? 'bg-white text-brand-700' : 'text-neutral-500 hover:text-neutral-800'"
                                        class="flex h-11 items-center justify-center gap-2 rounded-xl text-sm font-medium transition">
                                    <i class="ri-mail-line"></i>
                                    Email
                                </button>
                                <button type="button"
                                        @click="loginType = 'phone'"
                                        :class="loginType === 'phone' ? 'bg-white text-brand-700' : 'text-neutral-500 hover:text-neutral-800'"
                                        class="flex h-11 items-center justify-center gap-2 rounded-xl text-sm font-medium transition">
                                    <i class="ri-smartphone-line"></i>
                                    Телефон
                                </button>
                            </div>

                            <label class="mb-2 block text-sm font-medium text-neutral-800">Email или телефон</label>
                            <div class="relative">
                                <template x-if="loginType === 'email'">
                                    <i class="ri-mail-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-neutral-400"></i>
                                </template>
                                <template x-if="loginType === 'phone'">
                                    <i class="ri-smartphone-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-neutral-400"></i>
                                </template>
                                <input type="text"
                                       name="login"
                                       required
                                       x-model="loginValue"
                                       :placeholder="loginType === 'email' ? 'example@email.com' : '+373 ___ __ __'"
                                       class="wv-input h-12 w-full pl-12 pr-4 text-neutral-900 placeholder:text-neutral-400"
                                       @input="if(loginType === 'phone') {
                                           let val = $event.target.value.replace(/\D/g,'');
                                           if(val && !val.startsWith('373')) val = '373' + val;
                                           $event.target.value = '+' + val;
                                           loginValue = '+' + val;
                                       }">
                            </div>

                            <input type="hidden" name="login_type" x-model="loginType">
                            <x-input-error :messages="$errors->get('login')" class="mt-2 text-sm" />
                        </div>

                        <div x-data="{ show: false }">
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label class="block text-sm font-medium text-neutral-800">Пароль</label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-800">
                                        Забыли пароль?
                                    </a>
                                @endif
                            </div>

                            <div class="relative">
                                <i class="ri-lock-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-neutral-400"></i>
                                <input :type="show ? 'text' : 'password'"
                                       name="password"
                                       required
                                       class="wv-input h-12 w-full pl-12 pr-12 text-neutral-900 placeholder:text-neutral-400"
                                       placeholder="Введите пароль">
                                <button type="button"
                                        @click="show = !show"
                                        class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700"
                                        :title="show ? 'Скрыть пароль' : 'Показать пароль'">
                                    <i x-show="!show" class="ri-eye-line text-lg"></i>
                                    <i x-show="show" class="ri-eye-off-line text-lg"></i>
                                </button>
                            </div>

                            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
                        </div>

                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 bg-neutral-50 px-3.5 py-3 text-sm transition hover:border-brand-100 hover:bg-brand-50/50">
                            <input type="checkbox" name="remember" value="1" class="mt-0.5 rounded border-neutral-300 text-brand-600 focus:ring-brand-500">
                            <span>
                                <span class="block font-semibold text-neutral-700">Оставаться в аккаунте</span>
                                <span class="mt-0.5 block text-xs leading-5 text-neutral-500">На этом устройстве появится быстрый вход без пароля.</span>
                            </span>
                        </label>

                        <button type="submit" class="wv-btn-primary h-12 w-full text-base">
                            <i class="ri-login-box-line text-lg"></i>
                            Войти
                        </button>
                    </form>

                    @if(!empty($rememberedAccounts))
                        <details class="mt-4 overflow-hidden rounded-xl border border-neutral-200 bg-neutral-50">
                            <summary class="flex min-h-12 cursor-pointer list-none items-center gap-3 px-4 py-3 text-sm font-semibold text-neutral-700 transition hover:bg-brand-50 hover:text-brand-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600 [&::-webkit-details-marker]:hidden">
                                <i class="ri-account-circle-line text-xl text-brand-600" aria-hidden="true"></i>
                                <span class="min-w-0 flex-1">Сохранённые аккаунты <span class="font-normal text-neutral-500">({{ count($rememberedAccounts) }})</span></span>
                                <i class="ri-arrow-down-s-line text-lg text-neutral-500" aria-hidden="true"></i>
                            </summary>
                            <div class="border-t border-neutral-200 bg-white p-3">
                                <div class="mb-3 flex items-center justify-between gap-3 px-1">
                                    <p class="text-xs leading-5 text-neutral-500">Быстрый вход на этом устройстве</p>
                                    <form method="POST" action="{{ route('login.remembered.forget-all') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-10 items-center gap-1.5 rounded-xl px-2.5 text-xs font-semibold text-neutral-500 transition hover:bg-rose-50 hover:text-rose-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-rose-600">
                                            <i class="ri-delete-bin-line" aria-hidden="true"></i> Удалить все
                                        </button>
                                    </form>
                                </div>

                                <div class="max-h-64 space-y-2 overflow-y-auto">
                                    @foreach($rememberedAccounts as $account)
                                        <div class="flex items-center gap-2 rounded-xl border border-neutral-200 bg-white p-2">
                                            <form method="POST" action="{{ route('login.remembered') }}" class="min-w-0 flex-1">
                                                @csrf
                                                <input type="hidden" name="selector" value="{{ $account['selector'] }}">
                                                <input type="hidden" name="token" value="{{ $account['token'] }}">
                                                <button type="submit" class="flex w-full min-w-0 items-center gap-3 rounded-xl p-1 text-left transition hover:bg-brand-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600">
                                                    @if(!empty($account['avatar_url']))
                                                        <img data-image-candidates="{{ json_encode($account['avatar_candidates'] ?? []) }}" data-image-fallback="{{ asset('images/avatar-placeholder.svg') }}" src="{{ $account['avatar_url'] }}" alt="{{ $account['name'] }}" class="h-10 w-10 shrink-0 rounded-xl object-cover" loading="lazy" decoding="async">
                                                    @else
                                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-sm font-bold text-white">
                                                            {{ mb_substr($account['name'], 0, 1) }}
                                                        </span>
                                                    @endif
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block truncate text-sm font-bold text-neutral-900">{{ $account['name'] }}</span>
                                                        <span class="block truncate text-xs text-neutral-500">{{ $account['email'] }}</span>
                                                    </span>
                                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                                        <i class="ri-arrow-right-line" aria-hidden="true"></i>
                                                    </span>
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('login.remembered.forget', $account['selector']) }}">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="selector" value="{{ $account['selector'] }}">
                                                <button type="submit" aria-label="Убрать аккаунт из запомненных" class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-neutral-400 transition hover:bg-rose-50 hover:text-rose-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-rose-600" title="Убрать аккаунт из запомненных">
                                                    <i class="ri-close-line text-lg" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    @endif

                    <div class="mt-5">
                        <div class="flex items-center gap-3">
                            <div class="h-px flex-1 bg-neutral-200"></div>
                            <span class="text-xs font-bold uppercase tracking-[0.14em] text-neutral-400">Быстрый вход</span>
                            <div class="h-px flex-1 bg-neutral-200"></div>
                        </div>

                        <a href="{{ route('auth.google.redirect') }}" class="wv-btn-secondary mt-4 h-12 w-full text-base">
                            <img src="{{ asset('images/icons/google.png') }}" class="h-5 w-5" alt="">
                            Google
                        </a>
                    </div>

                    <p class="mt-6 text-center text-sm text-neutral-600">
                        Нет аккаунта?
                        <a href="{{ route('register') }}" class="font-bold text-brand-600 hover:text-brand-800">Создать профиль</a>
                    </p>
                </div>
            </div>
        </section>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('login-form');
        if (!form) return;

        form.addEventListener('submit', function() {
            const loginInput = form.querySelector('input[name="login"]');
            const loginTypeInput = form.querySelector('input[name="login_type"]');
            const loginType = loginTypeInput ? loginTypeInput.value : 'email';

            if (loginType === 'phone' && loginInput) {
                let phone = loginInput.value.replace(/\D/g, '');
                if (!phone.startsWith('373')) {
                    phone = '373' + phone;
                }
                loginInput.value = '+' + phone;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Вход...';
            submitBtn.disabled = true;

            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);

            return true;
        });
    });
    </script>
</x-guest-layout>
