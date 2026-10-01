<x-guest-layout>
    <div class="grid grid-cols-1 lg:grid-cols-[0.8fr_1.2fr]">
        <section class="hidden flex-col justify-between gap-6 border-r border-indigo-100 bg-gradient-to-br from-indigo-50 via-indigo-50 to-violet-50 p-8 lg:flex xl:p-10">
            <a href="{{ route('home') }}" class="relative inline-flex w-fit items-center gap-2 rounded-xl outline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 shadow-lg shadow-indigo-500/20">
                    <img src="{{ asset('images/icon.png') }}" class="h-8 w-8" alt="">
                </span>
                <span class="text-[15px] font-bold tracking-tight text-slate-800">WebVitrina</span>
            </a>

            <div>
                <span class="wv-page-eyebrow"><i class="ri-user-add-line text-base"></i> Новый аккаунт</span>
                <h1 class="mt-5 text-3xl font-bold leading-tight tracking-tight text-slate-950 xl:text-[38px]">Начните с WebVitrina.</h1>
                <p class="mt-4 text-[17px] leading-7 text-slate-600">Выберите роль и получите доступ к покупкам или своему магазину.</p>

                <div class="mt-8 space-y-3">
                    <div class="flex items-center gap-3 rounded-2xl border border-indigo-100 bg-white/90 p-3 shadow-sm">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xl text-indigo-600"><i class="ri-shopping-bag-3-line"></i></span>
                        <span class="text-[15px] font-semibold text-slate-800">Покупайте и сохраняйте избранное</span>
                    </div>
                    <div class="flex items-center gap-3 rounded-2xl border border-indigo-100 bg-white/90 p-3 shadow-sm">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xl text-indigo-600"><i class="ri-store-2-line"></i></span>
                        <span class="text-[15px] font-semibold text-slate-800">Откройте и развивайте магазин</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('home') }}" class="inline-flex min-h-11 w-fit items-center gap-2 rounded-xl border border-indigo-100 bg-white/70 px-4 text-sm font-semibold text-slate-600 transition hover:border-indigo-200 hover:bg-white hover:text-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600">
                <i class="ri-arrow-left-line" aria-hidden="true"></i> На главную
            </a>
        </section>

        <section class="flex min-w-0 flex-col bg-white">
            <div class="flex flex-1 items-center px-5 py-7 sm:px-8 lg:px-10 lg:py-8 xl:px-12">
                <div class="mx-auto w-full min-w-0 max-w-[520px]">
                    <div class="mb-6">
                        <p class="text-sm font-bold text-indigo-700">Регистрация</p>
                        <h2 class="mt-1 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl lg:text-[38px]">Создать аккаунт</h2>
                        <p class="mt-2 text-base leading-6 text-slate-600 sm:text-[17px] sm:leading-7">Четыре шага — и ваш профиль готов.</p>
                    </div>

                    <div class="mb-6 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <span class="text-sm font-bold text-slate-800">
                                Шаг <span id="current-step">1</span> из 4
                            </span>
                            <span id="step-title" class="text-sm font-bold text-indigo-600">Выбор роли</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                            <div id="progress-bar" class="h-full rounded-full bg-indigo-600 transition-all duration-300" style="width: 25%"></div>
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

                        <div id="step-1" class="step-content space-y-4">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-950 sm:text-xl">Как будете использовать WebVitrina?</h3>
                                <p class="mt-1 text-sm text-slate-500 sm:text-[15px] sm:leading-6">Выберите роль. Данные магазина можно заполнить позже.</p>
                            </div>

                            <div class="space-y-3">
                                <label class="relative cursor-pointer">
                                    <input type="radio"
                                           name="role"
                                           value="buyer"
                                           @checked(old('role', 'buyer') === 'buyer')
                                           class="peer sr-only"
                                           required>
                                    <div class="flex h-full items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-indigo-200 hover:bg-indigo-50/40 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:ring-4 peer-checked:ring-indigo-100">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                                            <i class="ri-shopping-bag-3-line text-2xl"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[17px] font-bold text-slate-950">Покупатель</p>
                                            <p class="mt-1 text-sm leading-5 text-slate-500 sm:text-[15px] sm:leading-6">Покупать товары, писать продавцам, сохранять избранное.</p>
                                        </div>
                                        <i class="ri-arrow-right-s-line shrink-0 text-xl text-indigo-600" aria-hidden="true"></i>
                                    </div>
                                </label>

                                <label class="relative cursor-pointer">
                                    <input type="radio"
                                           name="role"
                                           value="seller"
                                           @checked(old('role') === 'seller')
                                           class="peer sr-only">
                                    <div class="flex h-full items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-indigo-200 hover:bg-indigo-50/40 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-brand-500 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 peer-checked:ring-4 peer-checked:ring-indigo-100">
                                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                                            <i class="ri-store-3-line text-2xl"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[17px] font-bold text-slate-950">Продавец</p>
                                            <p class="mt-1 text-sm leading-5 text-slate-500 sm:text-[15px] sm:leading-6">Размещать товары, принимать заказы и вести магазин.</p>
                                        </div>
                                        <i class="ri-arrow-right-s-line shrink-0 text-xl text-indigo-600" aria-hidden="true"></i>
                                    </div>
                                </label>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="button"
                                        onclick="goToStep(2)"
                                        class="wv-btn-primary h-12 px-5 text-base">
                                    Далее
                                    <i class="ri-arrow-right-line"></i>
                                </button>
                            </div>
                        </div>

                        <div id="step-2" class="step-content hidden space-y-5">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-950">Личные данные</h3>
                                <p class="mt-1 text-sm text-slate-500">Имя будет видно в профиле, чатах и заказах.</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-800">Имя *</label>
                                <div class="relative">
                                    <i class="ri-user-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                    <input type="text"
                                           name="name"
                                           required
                                           value="{{ old('name') }}"
                                           class="wv-input h-12 w-full pl-12 pr-4 text-slate-900 placeholder:text-slate-400"
                                           placeholder="Например, Иван">
                                </div>
                                <x-input-error :messages="$errors->get('name')" class="mt-2 text-sm" />
                            </div>

                            <div class="flex justify-between gap-3 pt-2">
                                <button type="button" onclick="goToStep(1)" class="wv-btn-secondary h-12 px-5 text-base">
                                    <i class="ri-arrow-left-line"></i>
                                    Назад
                                </button>
                                <button type="button" onclick="goToStep(3)" class="wv-btn-primary h-12 px-5 text-base">
                                    Далее
                                    <i class="ri-arrow-right-line"></i>
                                </button>
                            </div>
                        </div>

                        <div id="step-3" class="step-content hidden space-y-5">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-950">Контакты</h3>
                                <p class="mt-1 text-sm text-slate-500">Email нужен для входа и восстановления доступа. Телефон можно добавить сразу.</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-800">Email *</label>
                                <div class="relative">
                                    <i class="ri-mail-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                    <input type="email"
                                           name="email"
                                           required
                                           value="{{ old('email') }}"
                                           class="wv-input h-12 w-full pl-12 pr-4 text-slate-900 placeholder:text-slate-400"
                                           placeholder="example@email.com">
                                </div>
                                <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-800">Телефон</label>
                                <div class="phone-input-shell">
                                    <input type="tel"
                                           id="registration-phone"
                                           name="phone"
                                           inputmode="tel"
                                           autocomplete="tel"
                                           value="{{ old('phone') }}"
                                           class="wv-input h-12 w-full pr-4 text-slate-900 placeholder:text-slate-400"
                                           aria-describedby="registration-phone-hint">
                                </div>
                                <p id="registration-phone-hint" class="mt-2 text-xs text-slate-500">Выберите страну и введите номер без кода страны — код добавится автоматически.</p>
                                <x-input-error :messages="$errors->get('phone')" class="mt-2 text-sm" />
                            </div>

                            <div class="flex justify-between gap-3 pt-2">
                                <button type="button" onclick="goToStep(2)" class="wv-btn-secondary h-12 px-5 text-base">
                                    <i class="ri-arrow-left-line"></i>
                                    Назад
                                </button>
                                <button type="button" onclick="goToStep(4)" class="wv-btn-primary h-12 px-5 text-base">
                                    Далее
                                    <i class="ri-arrow-right-line"></i>
                                </button>
                            </div>
                        </div>

                        <div id="step-4" class="step-content hidden space-y-5">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-950">Безопасность</h3>
                                <p class="mt-1 text-sm text-slate-500">Создайте пароль и подтвердите согласие с условиями.</p>
                            </div>

                            <div x-data="{ show: false }">
                                <label class="mb-2 block text-sm font-medium text-slate-800">Пароль *</label>
                                <div class="relative">
                                    <i class="ri-lock-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                    <input :type="show ? 'text' : 'password'"
                                           name="password"
                                           required
                                           class="wv-input h-12 w-full pl-12 pr-12 text-slate-900 placeholder:text-slate-400"
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
                                <label class="mb-2 block text-sm font-medium text-slate-800">Повторите пароль *</label>
                                <div class="relative">
                                    <i class="ri-lock-password-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400"></i>
                                    <input :type="show ? 'text' : 'password'"
                                           name="password_confirmation"
                                           required
                                           class="wv-input h-12 w-full pl-12 pr-12 text-slate-900 placeholder:text-slate-400"
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
                                <button type="button" onclick="goToStep(3)" class="wv-btn-secondary h-12 px-5 text-base">
                                    <i class="ri-arrow-left-line"></i>
                                    Назад
                                </button>
                                <button type="submit"
                                        class="wv-btn-primary h-12 px-5 text-base">
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

                        <div class="mt-4 text-sm">
                            <a href="{{ route('auth.google.redirect') }}"
                               class="wv-btn-secondary h-12 w-full text-base">
                                <img src="{{ asset('images/icons/google.png') }}" class="h-5 w-5" alt="">
                                Google
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

        const progressPercent = (step / 4) * 100;
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
