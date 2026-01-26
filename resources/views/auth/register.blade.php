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
            Создать аккаунт
        </h1>

        <!-- Полоса прогресса -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-3">
                <span class="text-sm font-medium text-gray-700">
                    Шаг <span id="current-step">1</span> из 4
                </span>
                <span id="step-title" class="text-sm font-semibold text-indigo-600">
                    Выбор роли
                </span>
            </div>
            
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div id="progress-bar" 
                     class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 
                            rounded-full transition-all duration-300"
                     style="width: 25%">
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('register') }}" id="registration-form" class="space-y-5 sm:space-y-6">
            @csrf

            <!-- Шаг 1: Выбор роли -->
            <div id="step-1" class="step-content">
                <div class="text-center mb-6">
                    <h2 class="text-lg sm:text-xl font-semibold text-gray-800 mb-2">
                        Кто вы?
                    </h2>
                    <p class="text-gray-600 text-sm">
                        Выберите как вы планируете использовать платформу
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Покупатель -->
                    <label class="relative cursor-pointer">
                        <input type="radio" 
                               name="role" 
                               value="buyer" 
                               @checked(old('role', 'buyer') === 'buyer')
                               class="peer sr-only" required>
                        <div class="p-4 rounded-xl border border-gray-300 bg-white
                                    peer-checked:border-indigo-500 peer-checked:bg-indigo-50
                                    hover:border-indigo-300 transition-all duration-200">
                            <div class="flex flex-col items-center text-center">
                                <div class="w-12 h-12 rounded-full bg-indigo-100 
                                            flex items-center justify-center mb-3">
                                    <i class="ri-shopping-bag-3-line text-xl text-indigo-600"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-1">Покупатель</h3>
                                <p class="text-sm text-gray-600">
                                    Хочу покупать товары и услуги
                                </p>
                            </div>
                        </div>
                    </label>
                    
                    <!-- Продавец -->
                    <label class="relative cursor-pointer">
                        <input type="radio" 
                               name="role" 
                               value="seller" 
                               @checked(old('role') === 'seller')
                               class="peer sr-only">
                        <div class="p-4 rounded-xl border border-gray-300 bg-white
                                    peer-checked:border-indigo-500 peer-checked:bg-indigo-50
                                    hover:border-indigo-300 transition-all duration-200">
                            <div class="flex flex-col items-center text-center">
                                <div class="w-12 h-12 rounded-full bg-purple-100 
                                            flex items-center justify-center mb-3">
                                    <i class="ri-store-3-line text-xl text-purple-600"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-1">Продавец</h3>
                                <p class="text-sm text-gray-600">
                                    Хочу продавать товары и услуги
                                </p>
                            </div>
                        </div>
                    </label>
                </div>
                
                <div class="flex justify-end pt-6">
                    <button type="button" 
                            onclick="goToStep(2)"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium 
                                   rounded-xl transition flex items-center gap-2">
                        Далее
                        <i class="ri-arrow-right-line"></i>
                    </button>
                </div>
            </div>

            <!-- Шаг 2: Личные данные -->
            <div id="step-2" class="step-content hidden">
                <div class="text-center mb-6">
                    <h2 class="text-lg sm:text-xl font-semibold text-gray-800 mb-2">
                        Личные данные
                    </h2>
                    <p class="text-gray-600 text-sm">
                        Расскажите немного о себе
                    </p>
                </div>

                <!-- Name -->
                <div>
                    <label class="block text-sm mb-1 font-medium text-gray-700">Имя *</label>
                    <div class="relative">
                        <i class="ri-user-line absolute left-3 top-3 text-gray-400 text-lg"></i>
                        <input type="text" name="name" required
                               value="{{ old('name') }}"
                               class="w-full pl-10 pr-4 py-2.5 sm:py-3 rounded-xl border border-gray-300
                                      focus:ring-indigo-500 focus:border-indigo-500 transition"
                               placeholder="Например, Иван"
                               title="Введите ваше имя">
                    </div>
                    @if($errors->has('name'))
                        <div class="mt-1 text-sm text-red-600">{{ $errors->first('name') }}</div>
                    @endif
                </div>


                <div class="flex justify-between pt-6">
                    <button type="button" 
                            onclick="goToStep(1)"
                            class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium 
                                   rounded-xl transition flex items-center gap-2">
                        <i class="ri-arrow-left-line"></i>
                        Назад
                    </button>
                    <button type="button" 
                            onclick="goToStep(3)"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium 
                                   rounded-xl transition flex items-center gap-2">
                        Далее
                        <i class="ri-arrow-right-line"></i>
                    </button>
                </div>
            </div>

            <!-- Шаг 3: Контакты -->
            <div id="step-3" class="step-content hidden">
                <div class="text-center mb-6">
                    <h2 class="text-lg sm:text-xl font-semibold text-gray-800 mb-2">
                        Контакты
                    </h2>
                    <p class="text-gray-600 text-sm">
                        Как мы можем с вами связаться?
                    </p>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm mb-1 font-medium text-gray-700">Email *</label>
                    <div class="relative">
                        <i class="ri-mail-line absolute left-3 top-3 text-gray-400 text-lg"></i>
                        <input type="email" name="email" required
                               value="{{ old('email') }}"
                               class="w-full pl-10 pr-4 py-2.5 sm:py-3 rounded-xl border border-gray-300
                                      focus:ring-indigo-500 focus:border-indigo-500 transition"
                               placeholder="Введите email"
                               title="Введите ваш email">
                    </div>
                    @if($errors->has('email'))
                        <div class="mt-1 text-sm text-red-600">{{ $errors->first('email') }}</div>
                    @endif
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm mb-1 font-medium text-gray-700">Телефон</label>
                    <input type="tel" name="phone"
                           value="{{ old('phone') }}"
                           class="w-full py-2.5 sm:py-3 px-4 rounded-xl border border-gray-300
                                  focus:ring-indigo-500 focus:border-indigo-500 transition"
                           placeholder="Введите телефон +373..."
                           title="Введите номер телефона">
                    @if($errors->has('phone'))
                        <div class="mt-1 text-sm text-red-600">{{ $errors->first('phone') }}</div>
                    @endif
                </div>

                <div class="flex justify-between pt-6">
                    <button type="button" 
                            onclick="goToStep(2)"
                            class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium 
                                   rounded-xl transition flex items-center gap-2">
                        <i class="ri-arrow-left-line"></i>
                        Назад
                    </button>
                    <button type="button" 
                            onclick="goToStep(4)"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium 
                                   rounded-xl transition flex items-center gap-2">
                        Далее
                        <i class="ri-arrow-right-line"></i>
                    </button>
                </div>
            </div>

            <!-- Шаг 4: Пароль -->
            <div id="step-4" class="step-content hidden">
                <div class="text-center mb-6">
                    <h2 class="text-lg sm:text-xl font-semibold text-gray-800 mb-2">
                        Безопасность
                    </h2>
                    <p class="text-gray-600 text-sm">
                        Создайте надежный пароль
                    </p>
                </div>

                <!-- Password -->
                <div x-data="{ show: false }">
                    <label class="block text-sm mb-1 font-medium text-gray-700">Пароль *</label>
                    <div class="relative">
                        <i class="ri-lock-line absolute left-3 top-3 text-gray-400 text-lg"></i>
                        <input :type="show ? 'text' : 'password'" name="password" required
                               class="w-full pl-10 pr-12 py-2.5 sm:py-3 rounded-xl border border-gray-300
                                      focus:ring-indigo-500 focus:border-indigo-500 transition"
                               placeholder="Придумайте пароль (мин. 8 символов)"
                               title="Придумайте пароль (не менее 8 символов)">
                        <button type="button" @click="show = !show"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                            <i x-show="!show" class="ri-eye-line text-lg"></i>
                            <i x-show="show" class="ri-eye-off-line text-lg"></i>
                        </button>
                    </div>
                    @if($errors->has('password'))
                        <div class="mt-1 text-sm text-red-600">{{ $errors->first('password') }}</div>
                    @endif
                </div>

                <!-- Confirm Password -->
                <div x-data="{ show: false }">
                    <label class="block text-sm mb-1 font-medium text-gray-700">Повторите пароль *</label>
                    <div class="relative">
                        <i class="ri-lock-password-line absolute left-3 top-3 text-gray-400 text-lg"></i>
                        <input :type="show ? 'text' : 'password'" name="password_confirmation" required
                               class="w-full pl-10 pr-12 py-2.5 sm:py-3 rounded-xl border border-gray-300
                                      focus:ring-indigo-500 focus:border-indigo-500 transition"
                               placeholder="Повторите пароль"
                               title="Введите пароль ещё раз">
                        <button type="button" @click="show = !show"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                            <i x-show="!show" class="ri-eye-line text-lg"></i>
                            <i x-show="show" class="ri-eye-off-line text-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Соглашение -->
                <div class="pt-4">
                    <div class="flex items-start gap-2">
                       <input type="checkbox"
       id="terms"
       name="terms"
       required
       class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">

                        <label for="terms" class="text-sm text-gray-700">
                            Я соглашаюсь с условиями использования и политикой конфиденциальности
                        </label>
                    </div>
                </div>

                <!-- Показ всех ошибок -->
                @if($errors->any())
                <div class="p-4 bg-red-50 border border-red-200 rounded-xl mt-4">
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

                <div class="flex justify-between pt-6">
                    <button type="button" 
                            onclick="goToStep(3)"
                            class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium 
                                   rounded-xl transition flex items-center gap-2">
                        <i class="ri-arrow-left-line"></i>
                        Назад
                    </button>
                    <button type="submit"
                            class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-medium 
                                   rounded-xl transition flex items-center gap-2">
                        <i class="ri-user-add-line"></i>
                        Зарегистрироваться
                    </button>
                </div>
            </div>

        </form>

        <!-- Социальные кнопки -->
        <div class="mt-8 pt-8 border-t border-gray-300">
            <div class="flex items-center my-5 sm:my-6">
                <div class="flex-1 border-t border-gray-300"></div>
                <span class="mx-2 sm:mx-4 text-sm text-gray-500">Или войдите с помощью:</span>
                <div class="flex-1 border-t border-gray-300"></div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 text-xs sm:text-sm">
                <a href="{{ route('auth.google.redirect') }}"
                   class="flex items-center justify-center gap-2 
                          bg-gray-100 hover:bg-gray-200 py-2 sm:py-2.5 rounded-xl transition">
                    <img src="{{ asset('images/icons/google.png') }}" class="w-4 h-4 sm:w-5 sm:h-5">
                    Google
                </a>

                <a href="#"
                   class="flex items-center justify-center gap-2 
                          bg-gray-100 hover:bg-gray-200 py-2 sm:py-2.5 rounded-xl transition">
                    <i class="ri-telegram-line text-base sm:text-lg"></i>
                    Telegram
                </a>

                <a href="#"
                   class="flex items-center justify-center gap-2 
                          bg-gray-100 hover:bg-gray-200 py-2 sm:py-2.5 rounded-xl transition">
                    <i class="ri-smartphone-line text-base sm:text-lg"></i>
                    Телефон
                </a>

                <a href="#"
                   class="flex items-center justify-center gap-2 
                          bg-gray-100 hover:bg-gray-200 py-2 sm:py-2.5 rounded-xl transition">
                    <i class="ri-facebook-circle-line text-base sm:text-lg"></i>
                    Facebook
                </a>
            </div>

            <p class="text-center mt-5 sm:mt-6 text-gray-600 text-sm">
                Уже есть аккаунт?
                <a href="{{ route('login') }}" class="text-indigo-600 font-semibold hover:underline">
                    Войти
                </a>
            </p>
        </div>

    </div>

    <style>
        /* Анимация шагов */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .step-content {
            animation: fadeIn 0.3s ease-out forwards;
        }
    </style>

    <script>
    // ПРОСТАЯ ФУНКЦИЯ ПЕРЕКЛЮЧЕНИЯ ШАГОВ
    let currentStep = 1;
    
    function goToStep(step) {
        // Проверка перед переходом
        if (step === 2) {
            // Проверяем выбрана ли роль на шаге 1
            const roleSelected = document.querySelector('input[name="role"]:checked');
            if (!roleSelected) {
                alert('Пожалуйста, выберите вашу роль');
                return;
            }
        }
        
        if (step === 3) {
            // Проверяем имя на шаге 2
            const nameInput = document.querySelector('input[name="name"]');
            if (!nameInput.value.trim()) {
                alert('Пожалуйста, введите ваше имя');
                nameInput.focus();
                return;
            }
        }
        
        if (step === 4) {
            // Проверяем email на шаге 3
            const emailInput = document.querySelector('input[name="email"]');
            if (!emailInput.value.trim() || !emailInput.checkValidity()) {
                alert('Пожалуйста, введите корректный email');
                emailInput.focus();
                return;
            }
        }
        
        // Скрываем все шаги
        document.querySelectorAll('.step-content').forEach(el => {
            el.classList.add('hidden');
        });
        
        // Показываем нужный шаг
        document.getElementById('step-' + step).classList.remove('hidden');
        
        // Обновляем прогресс-бар
        const progressPercent = ((step - 1) / 3) * 100;
        document.getElementById('progress-bar').style.width = progressPercent + '%';
        document.getElementById('current-step').textContent = step;
        
        // Обновляем заголовок шага
        const titles = ['Выбор роли', 'Личные данные', 'Контакты', 'Пароль'];
        document.getElementById('step-title').textContent = titles[step - 1];
        
        currentStep = step;
        
        // Прокручиваем к верху
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    // Автопереход при выборе роли
    document.querySelectorAll('input[name="role"]').forEach(radio => {
        radio.addEventListener('change', function() {
            setTimeout(() => {
                if (currentStep === 1) {
                    goToStep(2);
                }
            }, 300);
        });
    });
    
    // При загрузке страницы показываем нужный шаг при ошибках
    document.addEventListener('DOMContentLoaded', function() {
        @if($errors->any())
            // Если есть ошибки, показываем соответствующий шаг
            @if($errors->has('name'))
                goToStep(2);
            @elseif($errors->has('email') || $errors->has('phone'))
                goToStep(3);
            @elseif($errors->has('password') || $errors->has('password_confirmation'))
                goToStep(4);
            @endif
        @endif
        
        // Добавляем обработчик для кнопки отправки
        const form = document.getElementById('registration-form');
        form.addEventListener('submit', function(e) {
            const password = form.querySelector('input[name="password"]');
            const confirmPassword = form.querySelector('input[name="password_confirmation"]');
            
            // Проверка пароля
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
            
            // Проверка согласия с условиями
            const terms = document.getElementById('terms');
            if (!terms.checked) {
                e.preventDefault();
                alert('Пожалуйста, согласитесь с условиями использования');
                terms.focus();
                return false;
            }
            
            // Показываем загрузку
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Регистрация...';
            submitBtn.disabled = true;
            
            return true;
        });
    });
    </script>

</x-guest-layout>