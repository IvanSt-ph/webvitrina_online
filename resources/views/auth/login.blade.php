<x-guest-layout>

    <!-- Верхний баннер -->
    <div class="w-full h-56 sm:h-64 overflow-hidden">
        <img src="{{ asset('images/help/banner.jpg') }}" 
             class="w-full h-full object-cover" alt="Banner">
    </div>

    <!-- Контент -->
    <div class="px-4 sm:px-6 lg:px-8 py-8 sm:py-10 w-11/12 lg:w-3/4 mx-auto">

        <!-- Заголовок -->
        <h1 class="text-center text-xl sm:text-2xl font-semibold text-gray-800 mb-6 sm:mb-8">
            Вход в аккаунт
        </h1>

        <!-- Статус -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" id="login-form" class="space-y-5 sm:space-y-6">
            @csrf

            <!-- Login - С полем выбора -->
            <div x-data="{ 
                loginType: 'email', 
                loginValue: '{{ old('login') }}',
                isEmail(value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return emailRegex.test(value);
                }
            }" x-init="if(loginValue && !isEmail(loginValue)) loginType = 'phone'">
                <label class="block text-sm mb-2 font-medium text-gray-700">
                    Вход по Email или телефону
                </label>

                <!-- Переключатель Email/Телефон -->
                <div class="flex mb-4">
                    <button type="button"
                            @click="loginType = 'email'"
                            :class="loginType === 'email' 
                                ? 'bg-indigo-600 text-white' 
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="flex-1 py-3 px-4 rounded-l-xl border border-gray-300 transition flex items-center justify-center gap-2">
                        <i class="ri-mail-line"></i>
                        Email
                    </button>
                    <button type="button"
                            @click="loginType = 'phone'"
                            :class="loginType === 'phone' 
                                ? 'bg-indigo-600 text-white' 
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            class="flex-1 py-3 px-4 rounded-r-xl border border-gray-300 border-l-0 transition flex items-center justify-center gap-2">
                        <i class="ri-smartphone-line"></i>
                        Телефон
                    </button>
                </div>

                <!-- Поле ввода -->
                <div class="relative">
                    <template x-if="loginType === 'email'">
                        <i class="ri-mail-line absolute left-3 top-3.5 text-gray-400 text-lg"></i>
                    </template>
                    <template x-if="loginType === 'phone'">
                        <i class="ri-smartphone-line absolute left-3 top-3.5 text-gray-400 text-lg"></i>
                    </template>

                    <input type="text" 
                           name="login" 
                           required
                           x-model="loginValue"
                           :placeholder="loginType === 'email' ? 'example@email.com' : '+373 ___ __ __'"
                           class="w-full pl-10 pr-4 py-2.5 sm:py-3 rounded-xl border border-gray-300
                                  focus:ring-indigo-500 focus:border-indigo-500 transition"
                           @input="if(loginType === 'phone') {
                               let val = $event.target.value.replace(/\D/g,'');
                               if(val && !val.startsWith('373')) val = '373' + val;
                               $event.target.value = '+' + val;
                               loginValue = '+' + val;
                           }">
                </div>

                <!-- Скрытое поле для типа логина -->
                <input type="hidden" name="login_type" x-model="loginType">

                <x-input-error :messages="$errors->get('login')" class="mt-1 text-sm" />
            </div>

            <!-- Password -->
            <div x-data="{ show: false }">
                <label class="block text-sm mb-2 font-medium text-gray-700">Пароль</label>

                <div class="relative">
                    <i class="ri-lock-line absolute left-3 top-3.5 text-gray-400 text-lg"></i>

                    <input :type="show ? 'text' : 'password'" 
                           name="password" 
                           required
                           class="w-full pl-10 pr-12 py-2.5 sm:py-3 rounded-xl border border-gray-300
                                  focus:ring-indigo-500 focus:border-indigo-500 transition"
                           placeholder="Введите ваш пароль">

                    <button type="button" 
                            @click="show = !show"
                            class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600"
                            :title="show ? 'Скрыть пароль' : 'Показать пароль'">
                        <i x-show="!show" class="ri-eye-line text-lg"></i>
                        <i x-show="show" class="ri-eye-off-line text-lg"></i>
                    </button>
                </div>

                <x-input-error :messages="$errors->get('password')" class="mt-1 text-sm" />
            </div>

            <!-- Remember & Forgot Password -->
            <div class="flex items-center justify-between text-sm text-gray-600">
                <label class="flex items-center gap-2 cursor-pointer hover:text-gray-800">
                    <input type="checkbox" 
                           name="remember"
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Запомнить меня
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" 
                       class="text-indigo-600 hover:text-indigo-800 hover:underline whitespace-nowrap">
                        Забыли пароль?
                    </a>
                @endif
            </div>

            <!-- Ошибки формы -->
            @if($errors->any())
            <div class="p-4 bg-red-50 border border-red-200 rounded-xl">
                <div class="flex items-center gap-2 text-red-700 mb-2">
                    <i class="ri-error-warning-line"></i>
                    <span class="font-medium">Ошибки:</span>
                </div>
                <ul class="text-sm text-red-600 space-y-1">
                    @foreach($errors->all() as $error)
                        <li class="flex items-center gap-2">
                            <i class="ri-close-circle-fill text-xs"></i>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Submit -->
            <button type="submit"
                    class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700
                           text-white font-semibold transition shadow-md hover:shadow-lg
                           flex items-center justify-center gap-2">
                <i class="ri-login-box-line"></i>
                Войти
            </button>

        </form>

        <!-- Разделитель -->
        <div class="mt-8 pt-8 border-t border-gray-300">
            <div class="flex items-center my-5 sm:my-6">
                <div class="flex-1 border-t border-gray-300"></div>
                <span class="mx-2 sm:mx-4 text-sm text-gray-500">Или войдите с помощью:</span>
                <div class="flex-1 border-t border-gray-300"></div>
            </div>

            <!-- Social buttons -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 text-xs sm:text-sm">

                <!-- Google -->
                <a href="{{ route('auth.google.redirect') }}"
                   class="flex items-center justify-center gap-2 
                          bg-gray-100 hover:bg-gray-200 py-2 sm:py-2.5 rounded-xl transition
                          hover:shadow-sm">
                    <img src="{{ asset('images/icons/google.png') }}" class="w-4 h-4 sm:w-5 sm:h-5">
                    Google
                </a>

                <!-- Telegram -->
                <a href="#"
                   class="flex items-center justify-center gap-2 
                          bg-gray-100 hover:bg-gray-200 py-2 sm:py-2.5 rounded-xl transition
                          hover:shadow-sm">
                    <i class="ri-telegram-line text-base sm:text-lg"></i>
                    Telegram
                </a>

                <!-- Phone -->
                <a href="#"
                   class="flex items-center justify-center gap-2 
                          bg-gray-100 hover:bg-gray-200 py-2 sm:py-2.5 rounded-xl transition
                          hover:shadow-sm">
                    <i class="ri-smartphone-line text-base sm:text-lg"></i>
                    Телефон
                </a>

                <!-- Facebook -->
                <a href="#"
                   class="flex items-center justify-center gap-2 
                          bg-gray-100 hover:bg-gray-200 py-2 sm:py-2.5 rounded-xl transition
                          hover:shadow-sm">
                    <i class="ri-facebook-circle-line text-base sm:text-lg"></i>
                    Facebook
                </a>
            </div>

            <p class="text-center mt-5 sm:mt-6 text-gray-600 text-sm">
                Нет аккаунта?
                <a href="{{ route('register') }}" class="text-indigo-600 font-semibold hover:underline">
                    Создать
                </a>
            </p>
        </div>

    </div>

    <style>
        /* Анимации */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        form > * {
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        /* Плавные переходы */
        button, input, a {
            transition: all 0.2s ease;
        }
        
        input:focus {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Обработка отправки формы
        const form = document.getElementById('login-form');
        form.addEventListener('submit', function(e) {
            // Автоматическое форматирование телефона если выбрано
            const loginInput = form.querySelector('input[name="login"]');
            const loginTypeInput = form.querySelector('input[name="login_type"]');
            const loginType = loginTypeInput ? loginTypeInput.value : 'email';
            
            if (loginType === 'phone') {
                let phone = loginInput.value.replace(/\D/g, '');
                if (!phone.startsWith('373')) {
                    phone = '373' + phone;
                }
                loginInput.value = '+' + phone;
            }
            
            // Показываем загрузку
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Вход...';
            submitBtn.disabled = true;
            
            // Восстановление кнопки через 5 секунд (на случай ошибки)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
            
            return true;
        });
        
        // Автоматическое определение типа логина при загрузке
        const loginInput = document.querySelector('input[name="login"]');
        if (loginInput && loginInput.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const loginType = emailRegex.test(loginInput.value) ? 'email' : 'phone';
            
            // Находим компонент Alpine.js
            const alpineElement = document.querySelector('[x-data]');
            if (alpineElement && alpineElement.__x) {
                alpineElement.__x.$data.loginType = loginType;
            }
        }
    });
    </script>

</x-guest-layout>