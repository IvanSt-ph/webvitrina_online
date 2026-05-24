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

    <div class="grid min-h-[720px] lg:h-full lg:min-h-0 lg:grid-cols-[0.88fr_1.12fr]">
        <section class="relative hidden overflow-hidden bg-slate-950 lg:block lg:h-full">
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
                        <i class="ri-user-add-line text-base"></i>
                        Новый аккаунт
                    </p>
                    <h1 class="text-4xl font-extrabold leading-tight tracking-tight xl:text-5xl">
                        Создайте профиль для покупок или магазина.
                    </h1>
                    <p class="mt-5 text-base leading-7 text-white/80">
                        Выберите роль, укажите контакты и получите доступ к заказам, чатам, подпискам и продаже товаров.
                    </p>
                </div>

                <div class="space-y-3">
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <div class="flex items-start gap-3">
                            <i class="ri-shopping-bag-3-line mt-0.5 text-2xl text-indigo-100"></i>
                            <div>
                                <p class="text-sm font-semibold">Покупатель</p>
                                <p class="mt-1 text-xs leading-5 text-white/70">Заказы, избранное, подписки на магазины и поддержка.</p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <div class="flex items-start gap-3">
                            <i class="ri-store-2-line mt-0.5 text-2xl text-indigo-100"></i>
                            <div>
                                <p class="text-sm font-semibold">Продавец</p>
                                <p class="mt-1 text-xs leading-5 text-white/70">Магазин создаётся автоматически, детали можно заполнить позже.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="flex min-h-[720px] flex-col bg-white/85 backdrop-blur-xl lg:h-full lg:min-h-0">
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

            <div class="flex flex-1 items-center px-5 py-7 sm:px-8 lg:px-8 lg:py-6 xl:px-10">
                <div class="mx-auto w-full max-w-2xl xl:max-w-3xl">
                    <div class="mb-5">
                        <p class="text-sm font-semibold text-indigo-600">Регистрация</p>
                        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Создать аккаунт</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Четыре коротких шага. Ничего лишнего, только данные для входа и роли.
                        </p>
                    </div>

                    <div class="mb-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <span class="text-sm font-bold text-slate-800">
                                Шаг <span id="current-step">1</span> из 4
                            </span>
                            <span id="step-title" class="text-sm font-bold text-indigo-600">Выбор роли</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                            <div id="progress-bar" class="h-full rounded-full bg-indigo-500 transition-all duration-300" style="width: 25%"></div>
                        </div>
                    </div>

                    @if($errors->any())
                        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4">
                            <div class="mb-2 flex items-center gap-2 text-rose-700">
                                <i class="ri-error-warning-line"></i>
                                <span class="font-semibold">Проверьте данные</span>
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

                    <form method="POST" action="{{ route('register') }}" id="registration-form">
                        @csrf

                        <div id="step-1" class="step-content space-y-5">
                            <div>
                                <h3 class="text-lg font-extrabold text-slate-950">Как будете использовать WebVitrina?</h3>
                                <p class="mt-1 text-sm text-slate-500">Роль можно выбрать сразу, а данные магазина продавец заполнит в кабинете.</p>
                            </div>

                            <div class="space-y-3">
                                <label class="relative cursor-pointer">
                                    <input type="radio"
                                           name="role"
                                           value="buyer"
                                           @checked(old('role', 'buyer') === 'buyer')
                                           class="peer sr-only"
                                           required>
                                    <div class="flex h-full items-start gap-4 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-indigo-200 hover:bg-indigo-50/40 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:ring-4 peer-checked:ring-indigo-100">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
                                            <i class="ri-shopping-bag-3-line text-2xl"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-extrabold text-slate-950">Покупатель</p>
                                            <p class="mt-1 text-sm leading-6 text-slate-500">Покупать товары, писать продавцам, сохранять избранное.</p>
                                            <span class="mt-2 inline-flex items-center gap-1 text-sm font-bold text-indigo-600">
                                                Выбрать
                                                <i class="ri-arrow-right-line"></i>
                                            </span>
                                        </div>
                                    </div>
                                </label>

                                <label class="relative cursor-pointer">
                                    <input type="radio"
                                           name="role"
                                           value="seller"
                                           @checked(old('role') === 'seller')
                                           class="peer sr-only">
                                    <div class="flex h-full items-start gap-4 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-indigo-200 hover:bg-indigo-50/40 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:ring-4 peer-checked:ring-indigo-100">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
                                            <i class="ri-store-3-line text-2xl"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-extrabold text-slate-950">Продавец</p>
                                            <p class="mt-1 text-sm leading-6 text-slate-500">Размещать товары, принимать заказы и вести магазин.</p>
                                            <span class="mt-2 inline-flex items-center gap-1 text-sm font-bold text-indigo-600">
                                                Выбрать
                                                <i class="ri-arrow-right-line"></i>
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="button"
                                        onclick="goToStep(2)"
                                        class="inline-flex h-[48px] items-center justify-center gap-2 rounded-2xl border border-indigo-400/30 bg-indigo-500/90 px-5 font-bold text-white shadow-lg shadow-indigo-500/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-indigo-600">
                                    Далее
                                    <i class="ri-arrow-right-line"></i>
                                </button>
                            </div>
                        </div>

                        <div id="step-2" class="step-content hidden space-y-5">
                            <div>
                                <h3 class="text-lg font-extrabold text-slate-950">Личные данные</h3>
                                <p class="mt-1 text-sm text-slate-500">Имя будет видно в профиле, чатах и заказах.</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-800">Имя *</label>
                                <div class="relative">
                                    <i class="ri-user-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                    <input type="text"
                                           name="name"
                                           required
                                           value="{{ old('name') }}"
                                           class="h-[52px] w-full rounded-2xl border border-slate-200 bg-slate-50/80 py-3.5 pl-12 pr-4 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                                           placeholder="Например, Иван">
                                </div>
                                <x-input-error :messages="$errors->get('name')" class="mt-2 text-sm" />
                            </div>

                            <div class="flex justify-between gap-3 pt-2">
                                <button type="button" onclick="goToStep(1)" class="inline-flex h-[48px] items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 font-bold text-slate-700 transition hover:bg-slate-50">
                                    <i class="ri-arrow-left-line"></i>
                                    Назад
                                </button>
                                <button type="button" onclick="goToStep(3)" class="inline-flex h-[48px] items-center justify-center gap-2 rounded-2xl border border-indigo-400/30 bg-indigo-500/90 px-5 font-bold text-white shadow-lg shadow-indigo-500/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-indigo-600">
                                    Далее
                                    <i class="ri-arrow-right-line"></i>
                                </button>
                            </div>
                        </div>

                        <div id="step-3" class="step-content hidden space-y-5">
                            <div>
                                <h3 class="text-lg font-extrabold text-slate-950">Контакты</h3>
                                <p class="mt-1 text-sm text-slate-500">Email нужен для входа и восстановления доступа. Телефон можно добавить сразу.</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-800">Email *</label>
                                <div class="relative">
                                    <i class="ri-mail-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                    <input type="email"
                                           name="email"
                                           required
                                           value="{{ old('email') }}"
                                           class="h-[52px] w-full rounded-2xl border border-slate-200 bg-slate-50/80 py-3.5 pl-12 pr-4 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                                           placeholder="example@email.com">
                                </div>
                                <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-800">Телефон</label>
                                <div class="relative">
                                    <i class="ri-smartphone-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                    <input type="tel"
                                           name="phone"
                                           value="{{ old('phone') }}"
                                           class="h-[52px] w-full rounded-2xl border border-slate-200 bg-slate-50/80 py-3.5 pl-12 pr-4 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                                           placeholder="+373 ___ __ __">
                                </div>
                                <x-input-error :messages="$errors->get('phone')" class="mt-2 text-sm" />
                            </div>

                            <div class="flex justify-between gap-3 pt-2">
                                <button type="button" onclick="goToStep(2)" class="inline-flex h-[48px] items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 font-bold text-slate-700 transition hover:bg-slate-50">
                                    <i class="ri-arrow-left-line"></i>
                                    Назад
                                </button>
                                <button type="button" onclick="goToStep(4)" class="inline-flex h-[48px] items-center justify-center gap-2 rounded-2xl border border-indigo-400/30 bg-indigo-500/90 px-5 font-bold text-white shadow-lg shadow-indigo-500/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-indigo-600">
                                    Далее
                                    <i class="ri-arrow-right-line"></i>
                                </button>
                            </div>
                        </div>

                        <div id="step-4" class="step-content hidden space-y-5">
                            <div>
                                <h3 class="text-lg font-extrabold text-slate-950">Безопасность</h3>
                                <p class="mt-1 text-sm text-slate-500">Создайте пароль и подтвердите согласие с условиями.</p>
                            </div>

                            <div x-data="{ show: false }">
                                <label class="mb-2 block text-sm font-bold text-slate-800">Пароль *</label>
                                <div class="relative">
                                    <i class="ri-lock-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                    <input :type="show ? 'text' : 'password'"
                                           name="password"
                                           required
                                           class="h-[52px] w-full rounded-2xl border border-slate-200 bg-slate-50/80 py-3.5 pl-12 pr-12 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                                           placeholder="Минимум 8 символов">
                                    <button type="button"
                                            @click="show = !show"
                                            class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                                        <i x-show="!show" class="ri-eye-line text-lg"></i>
                                        <i x-show="show" class="ri-eye-off-line text-lg"></i>
                                    </button>
                                </div>
                                <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
                            </div>

                            <div x-data="{ show: false }">
                                <label class="mb-2 block text-sm font-bold text-slate-800">Повторите пароль *</label>
                                <div class="relative">
                                    <i class="ri-lock-password-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                    <input :type="show ? 'text' : 'password'"
                                           name="password_confirmation"
                                           required
                                           class="h-[52px] w-full rounded-2xl border border-slate-200 bg-slate-50/80 py-3.5 pl-12 pr-12 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100"
                                           placeholder="Повторите пароль">
                                    <button type="button"
                                            @click="show = !show"
                                            class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                                        <i x-show="!show" class="ri-eye-line text-lg"></i>
                                        <i x-show="show" class="ri-eye-off-line text-lg"></i>
                                    </button>
                                </div>
                            </div>

                            <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50/40">
                                <input type="checkbox"
                                       id="terms"
                                       name="terms"
                                       required
                                       class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span>Я соглашаюсь с условиями использования и политикой конфиденциальности.</span>
                            </label>

                            <div class="flex justify-between gap-3 pt-2">
                                <button type="button" onclick="goToStep(3)" class="inline-flex h-[48px] items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 font-bold text-slate-700 transition hover:bg-slate-50">
                                    <i class="ri-arrow-left-line"></i>
                                    Назад
                                </button>
                                <button type="submit"
                                        class="inline-flex h-[48px] items-center justify-center gap-2 rounded-2xl border border-indigo-400/30 bg-indigo-500/90 px-5 font-bold text-white shadow-lg shadow-indigo-500/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-indigo-600">
                                    <i class="ri-user-add-line"></i>
                                    Зарегистрироваться
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="mt-5">
                        <div class="flex items-center gap-3">
                            <div class="h-px flex-1 bg-slate-200"></div>
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Быстрая регистрация</span>
                            <div class="h-px flex-1 bg-slate-200"></div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <a href="{{ route('auth.google.redirect') }}"
                               class="flex h-12 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white font-semibold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                                <img src="{{ asset('images/icons/google.png') }}" class="h-5 w-5" alt="">
                                Google
                            </a>
                            <a href="#"
                               class="flex h-12 items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white font-semibold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                                <i class="ri-telegram-line text-lg"></i>
                                Telegram
                            </a>
                        </div>
                    </div>

                    <p class="mt-5 text-center text-sm text-slate-600">
                        Уже есть аккаунт?
                        <a href="{{ route('login') }}" class="font-bold text-indigo-600 hover:text-indigo-800">
                            Войти
                        </a>
                    </p>
                </div>
            </div>
        </section>
    </div>

    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .step-content {
            animation: fadeIn 0.22s ease-out forwards;
        }
    </style>

    <script>
    let currentStep = 1;

    function goToStep(step) {
        if (step === 2) {
            const roleSelected = document.querySelector('input[name="role"]:checked');
            if (!roleSelected) {
                alert('Пожалуйста, выберите вашу роль');
                return;
            }
        }

        if (step === 3) {
            const nameInput = document.querySelector('input[name="name"]');
            if (!nameInput.value.trim()) {
                alert('Пожалуйста, введите ваше имя');
                nameInput.focus();
                return;
            }
        }

        if (step === 4) {
            const emailInput = document.querySelector('input[name="email"]');
            if (!emailInput.value.trim() || !emailInput.checkValidity()) {
                alert('Пожалуйста, введите корректный email');
                emailInput.focus();
                return;
            }
        }

        document.querySelectorAll('.step-content').forEach(el => {
            el.classList.add('hidden');
        });

        document.getElementById('step-' + step).classList.remove('hidden');

        const progressPercent = ((step - 1) / 3) * 100;
        document.getElementById('progress-bar').style.width = progressPercent + '%';
        document.getElementById('current-step').textContent = step;

        const titles = ['Выбор роли', 'Личные данные', 'Контакты', 'Пароль'];
        document.getElementById('step-title').textContent = titles[step - 1];

        currentStep = step;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[name="role"]').forEach(radio => {
            radio.addEventListener('change', function() {
                setTimeout(() => {
                    if (currentStep === 1) {
                        goToStep(2);
                    }
                }, 220);
            });
        });

        @if($errors->any())
            @if($errors->has('name'))
                goToStep(2);
            @elseif($errors->has('email') || $errors->has('phone'))
                goToStep(3);
            @elseif($errors->has('password') || $errors->has('password_confirmation') || $errors->has('terms'))
                goToStep(4);
            @endif
        @endif

        const form = document.getElementById('registration-form');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            const password = form.querySelector('input[name="password"]');
            const confirmPassword = form.querySelector('input[name="password_confirmation"]');

            if (password.value.length < 8) {
                e.preventDefault();
                alert('Пароль должен содержать минимум 8 символов');
                password.focus();
                return false;
            }

            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Пароли не совпадают');
                confirmPassword.focus();
                return false;
            }

            const terms = document.getElementById('terms');
            if (!terms.checked) {
                e.preventDefault();
                alert('Пожалуйста, согласитесь с условиями использования');
                terms.focus();
                return false;
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Регистрация...';
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
