<x-guest-layout>

    {{-- Видео фон --}}
    <div class="fixed inset-0 -z-10 overflow-hidden bg-slate-950">
        <video
            autoplay
            muted
            loop
            playsinline
            class="h-full w-full scale-[1.03] object-cover opacity-90 blur-[1.5px] saturate-125"
        >
            <source src="{{ asset('videos/login-bg.mp4') }}" type="video/mp4">
        </video>

        {{-- Затемнение и стеклянная пленка поверх видео --}}
        <div class="absolute inset-0 bg-slate-950/60"></div>
        <div class="absolute inset-0 bg-white/[0.03] backdrop-blur-[2px]"></div>
    </div>

    <div class="grid min-h-[680px] lg:grid-cols-[1.05fr_0.95fr]">
        <section class="relative hidden overflow-hidden bg-slate-950 lg:block">
            <img src="{{ asset('images/help/banner.jpg') }}"
                 class="absolute inset-0 h-full w-full object-cover opacity-80"
                 alt="WebVitrina">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-950/90 via-slate-950/45 to-indigo-950/55"></div>

            <div class="relative z-10 flex h-full flex-col justify-between p-8 xl:p-10 text-white">
                <a href="{{ route('home') }}" class="inline-flex w-fit items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 shadow-lg backdrop-blur">
                    <img src="{{ asset('images/logo.png') }}" class="h-9 w-9 rounded-xl bg-white object-contain p-1" alt="WebVitrina">
                    <span class="text-lg font-extrabold tracking-tight">WebVitrina</span>
                </a>

                <div class="max-w-md">
                    <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-indigo-200/30 bg-indigo-100/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-100">
                        <i class="ri-shield-check-line text-base"></i>
                        Безопасный вход
                    </p>
                    <h1 class="text-4xl font-extrabold leading-tight tracking-tight xl:text-5xl">
                        Вернитесь к покупкам, продажам и диалогам.
                    </h1>
                    <p class="mt-5 text-base leading-7 text-white/80">
                        Один аккаунт для заказов, подписок, магазина, поддержки и marketplace-чатов.
                    </p>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <i class="ri-message-3-line text-2xl text-indigo-100"></i>
                        <p class="mt-3 text-sm font-semibold">Чаты</p>
                        <p class="mt-1 text-xs text-white/70">Покупатель, продавец, поддержка</p>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <i class="ri-store-2-line text-2xl text-indigo-100"></i>
                        <p class="mt-3 text-sm font-semibold">Магазин</p>
                        <p class="mt-1 text-xs text-white/70">Товары и подписчики</p>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <i class="ri-lock-password-line text-2xl text-indigo-100"></i>
                        <p class="mt-3 text-sm font-semibold">Защита</p>
                        <p class="mt-1 text-xs text-white/70">Контроль входа</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="flex min-h-[680px] flex-col bg-white/85 backdrop-blur-xl">
            <div class="relative h-40 overflow-hidden lg:hidden">
                <video
                    autoplay
                    muted
                    loop
                    playsinline
                    class="absolute inset-0 h-full w-full scale-[1.03] object-cover blur-[1.5px] saturate-125"
                >
                    <source src="{{ asset('videos/login-bg.mp4') }}" type="video/mp4">
                </video>
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/75 via-slate-950/30 to-transparent"></div>
                <div class="absolute inset-0 bg-white/[0.03] backdrop-blur-[2px]"></div>
                <a href="{{ route('home') }}" class="absolute left-4 top-4 inline-flex items-center gap-2 rounded-xl bg-white/90 px-3 py-2 text-sm font-extrabold text-slate-900 shadow-sm backdrop-blur">
                    <img src="{{ asset('images/logo.png') }}" class="h-7 w-7 rounded-lg object-contain" alt="WebVitrina">
                    WebVitrina
                </a>
            </div>

            <div class="flex flex-1 items-center px-5 py-7 sm:px-8 lg:px-10 xl:px-12">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-7">
                        <p class="text-sm font-semibold text-indigo-600">С возвращением</p>
                        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Войти в аккаунт</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Используйте email или номер телефона, привязанный к профилю.
                        </p>
                    </div>

                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    @if($errors->any())
                        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4">
                            <div class="mb-2 flex items-center gap-2 text-rose-700">
                                <i class="ri-error-warning-line"></i>
                                <span class="font-semibold">Не удалось войти</span>
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

                    @if(!empty($rememberedAccounts))
                        <div class="mb-5 rounded-3xl border border-indigo-100 bg-indigo-50/70 p-3 shadow-sm">
                            <div class="mb-2 flex items-center justify-between gap-3 px-1">
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Запомненные аккаунты</p>
                                    <p class="text-xs text-slate-500">Быстрый вход на этом устройстве без ввода пароля.</p>
                                </div>
                                <form method="POST" action="{{ route('login.remembered.forget-all') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex h-8 items-center gap-1.5 rounded-xl bg-white px-2.5 text-xs font-bold text-slate-500 shadow-sm ring-1 ring-slate-200 transition hover:text-rose-600 hover:ring-rose-200"
                                            title="Убрать все запомненные аккаунты">
                                        <i class="ri-delete-bin-line"></i>
                                        Все
                                    </button>
                                </form>
                            </div>

                            <div class="space-y-2">
                                @foreach($rememberedAccounts as $account)
                                    <div class="flex items-center gap-2 rounded-2xl border border-white bg-white/90 p-2 shadow-sm">
                                        <form method="POST" action="{{ route('login.remembered') }}" class="min-w-0 flex-1">
                                            @csrf
                                            <input type="hidden" name="selector" value="{{ $account['selector'] }}">
                                            <input type="hidden" name="token" value="{{ $account['token'] }}">
                                            <button type="submit" class="flex w-full min-w-0 items-center gap-3 rounded-xl p-1 text-left transition hover:bg-indigo-50/70">
                                                @if(!empty($account['avatar']))
                                                    <img src="{{ asset('storage/' . $account['avatar']) }}" alt="{{ $account['name'] }}" class="h-11 w-11 shrink-0 rounded-2xl object-cover">
                                                @else
                                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-500 text-sm font-bold text-white">
                                                        {{ mb_substr($account['name'], 0, 1) }}
                                                    </span>
                                                @endif
                                                <span class="min-w-0 flex-1">
                                                    <span class="block truncate text-sm font-bold text-slate-900">{{ $account['name'] }}</span>
                                                    <span class="block truncate text-xs text-slate-500">{{ $account['email'] }}</span>
                                                </span>
                                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                                    <i class="ri-arrow-right-line"></i>
                                                </span>
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('login.remembered.forget', $account['selector']) }}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="selector" value="{{ $account['selector'] }}">
                                            <button type="submit"
                                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                                    title="Убрать аккаунт из запомненных">
                                                <i class="ri-close-line text-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" id="login-form" class="space-y-5">
                        @csrf

                        <div x-data="{
                            loginType: 'email',
                            loginValue: @js(old('login')),
                            isEmail(value) {
                                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                                return emailRegex.test(value);
                            }
                        }" x-init="if(loginValue && !isEmail(loginValue)) loginType = 'phone'">
                            <div class="mb-3 flex rounded-2xl border border-slate-200 bg-slate-100 p-1">
                                <button type="button"
                                        @click="loginType = 'email'"
                                        :class="loginType === 'email'
                                            ? 'bg-white text-indigo-700 shadow-sm'
                                            : 'text-slate-500 hover:text-slate-800'"
                                        class="flex h-11 flex-1 items-center justify-center gap-2 rounded-xl text-sm font-bold transition">
                                    <i class="ri-mail-line text-base"></i>
                                    Email
                                </button>
                                <button type="button"
                                        @click="loginType = 'phone'"
                                        :class="loginType === 'phone'
                                            ? 'bg-white text-indigo-700 shadow-sm'
                                            : 'text-slate-500 hover:text-slate-800'"
                                        class="flex h-11 flex-1 items-center justify-center gap-2 rounded-xl text-sm font-bold transition">
                                    <i class="ri-smartphone-line text-base"></i>
                                    Телефон
                                </button>
                            </div>

                            <label class="mb-2 block text-sm font-bold text-slate-800">Email или телефон</label>
                            <div class="relative">
                                <template x-if="loginType === 'email'">
                                    <i class="ri-mail-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                </template>
                                <template x-if="loginType === 'phone'">
                                    <i class="ri-smartphone-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                </template>
                                <input type="text"
                                       name="login"
                                       required
                                       x-model="loginValue"
                                       :placeholder="loginType === 'email' ? 'example@email.com' : '+373 ___ __ __'"
                                       class="h-[52px] w-full rounded-2xl border border-slate-200 bg-slate-50/80 py-3.5 pl-12 pr-4 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100"
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
                                <label class="block text-sm font-bold text-slate-800">Пароль</label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}"
                                       class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                                        Забыли пароль?
                                    </a>
                                @endif
                            </div>

                            <div class="relative">
                                <i class="ri-lock-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                <input :type="show ? 'text' : 'password'"
                                       name="password"
                                       required
                                       class="h-[52px] w-full rounded-2xl border border-slate-200 bg-slate-50/80 py-3.5 pl-12 pr-12 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                                       placeholder="Введите пароль">
                                <button type="button"
                                        @click="show = !show"
                                        class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                        :title="show ? 'Скрыть пароль' : 'Показать пароль'">
                                    <i x-show="!show" class="ri-eye-line text-lg"></i>
                                    <i x-show="show" class="ri-eye-off-line text-lg"></i>
                                </button>
                            </div>

                            <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
                        </div>

                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-100 bg-slate-50/70 px-3.5 py-3 text-sm transition hover:border-indigo-100 hover:bg-indigo-50/50">
                            <input type="checkbox"
                                   name="remember"
                                   value="1"
                                   class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>
                                <span class="block font-semibold text-slate-700">Оставаться в аккаунте</span>
                                <span class="mt-0.5 block text-xs leading-5 text-slate-500">На этом устройстве появится быстрый вход без пароля. Сам пароль не сохраняется.</span>
                            </span>
                        </label>

                        <button type="submit"
                                class="group relative flex h-[52px] w-full items-center justify-center gap-2 overflow-hidden rounded-2xl border border-indigo-400/30 bg-indigo-500/90 px-5 py-3.5 font-bold text-white shadow-lg shadow-indigo-500/25 transition-all duration-300 hover:-translate-y-0.5 hover:bg-indigo-600 hover:shadow-xl hover:shadow-indigo-500/30">
                            <span class="relative z-10 flex items-center gap-2">
                                <i class="ri-login-box-line text-lg"></i>
                                Войти
                            </span>
                        </button>
                    </form>

                    <div class="mt-7">
                        <div class="flex items-center gap-3">
                            <div class="h-px flex-1 bg-slate-200"></div>
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Быстрый вход</span>
                            <div class="h-px flex-1 bg-slate-200"></div>
                        </div>

                        <div class="mt-4 text-sm">
                            <a href="{{ route('auth.google.redirect') }}"
                               class="flex h-12 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white font-semibold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                                <img src="{{ asset('images/icons/google.png') }}" class="h-5 w-5" alt="">
                                Google
                            </a>
                        </div>
                    </div>

                    <p class="mt-7 text-center text-sm text-slate-600">
                        Нет аккаунта?
                        <a href="{{ route('register') }}" class="font-bold text-indigo-600 hover:text-indigo-800">
                            Создать профиль
                        </a>
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
