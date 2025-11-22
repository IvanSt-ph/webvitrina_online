<x-guest-layout>

    <!-- Верхний баннер -->
    <div class="w-full h-56 sm:h-64 overflow-hidden">
        <img src="{{ asset('images/help/banner.jpg') }}" 
             class="w-full h-full object-cover" alt="Banner">
    </div>

    <!-- Контент -->
    <div class="px-4 sm:px-10 py-8 sm:py-10">

        <!-- Заголовок -->
        <h1 class="text-center text-xl sm:text-2xl font-semibold text-gray-800 mb-6 sm:mb-8">
            Создать аккаунт
        </h1>

        <form method="POST" action="{{ route('register') }}" class="space-y-5 sm:space-y-6">
            @csrf

            <!-- Name -->
            <div>
                <label class="block text-sm mb-1 font-medium text-gray-700">Имя</label>

                <div class="relative">
                    <i class="ri-user-line absolute left-3 top-3 text-gray-400 text-lg"></i>

                    <input type="text" name="name" required
                           value="{{ old('name') }}"
                           class="w-full pl-10 pr-4 py-2.5 sm:py-3 rounded-xl border border-gray-300
                                  focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <x-input-error :messages="$errors->get('name')" class="mt-1 text-sm" />
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm mb-1 font-medium text-gray-700">Email</label>

                <div class="relative">
                    <i class="ri-mail-line absolute left-3 top-3 text-gray-400 text-lg"></i>

                    <input type="email" name="email" required
                           value="{{ old('email') }}"
                           class="w-full pl-10 pr-4 py-2.5 sm:py-3 rounded-xl border border-gray-300
                                  focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <x-input-error :messages="$errors->get('email')" class="mt-1 text-sm" />
            </div>

            <!-- Phone -->
            <div>
                <label class="block text-sm mb-1 font-medium text-gray-700">Телефон (необязательно)</label>

                <div class="relative">
                    <i class="ri-smartphone-line absolute left-3 top-3 text-gray-400 text-lg"></i>

                    <input type="text" name="phone"
                           value="{{ old('phone') }}"
                           placeholder="+373 6xx xx xxx"
                           class="w-full pl-10 pr-4 py-2.5 sm:py-3 rounded-xl border border-gray-300
                                  focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <x-input-error :messages="$errors->get('phone')" class="mt-1 text-sm" />
            </div>

            <!-- Role -->
            <div>
                <label class="block text-sm mb-1 font-medium text-gray-700">Роль</label>

                <div class="relative">
                    <i class="ri-user-star-line absolute left-3 top-3 text-gray-400 text-lg"></i>

                    <select name="role"
                            class="w-full pl-10 pr-4 py-2.5 sm:py-3 rounded-xl border border-gray-300
                                   focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="buyer" @selected(old('role')==='buyer')>Покупатель</option>
                        <option value="seller" @selected(old('role')==='seller')>Продавец</option>
                    </select>
                </div>

                <x-input-error :messages="$errors->get('role')" class="mt-1 text-sm" />
            </div>

            <!-- Password -->
            <div x-data="{ show: false }">
                <label class="block text-sm mb-1 font-medium text-gray-700">Пароль</label>

                <div class="relative">
                    <i class="ri-lock-line absolute left-3 top-3 text-gray-400 text-lg"></i>

                    <input :type="show ? 'text' : 'password'" name="password" required
                           class="w-full pl-10 pr-12 py-2.5 sm:py-3 rounded-xl border border-gray-300
                                  focus:ring-indigo-500 focus:border-indigo-500 transition">

                    <button type="button" @click="show = !show"
                            class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                        <i x-show="!show" class="ri-eye-line text-lg"></i>
                        <i x-show="show" class="ri-eye-off-line text-lg"></i>
                    </button>
                </div>

                <x-input-error :messages="$errors->get('password')" class="mt-1 text-sm" />
            </div>

            <!-- Confirm Password -->
            <div x-data="{ show: false }">
                <label class="block text-sm mb-1 font-medium text-gray-700">Повторите пароль</label>

                <div class="relative">
                    <i class="ri-lock-password-line absolute left-3 top-3 text-gray-400 text-lg"></i>

                    <input :type="show ? 'text' : 'password'" name="password_confirmation" required
                           class="w-full pl-10 pr-12 py-2.5 sm:py-3 rounded-xl border border-gray-300
                                  focus:ring-indigo-500 focus:border-indigo-500 transition">

                    <button type="button" @click="show = !show"
                            class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                        <i x-show="!show" class="ri-eye-line text-lg"></i>
                        <i x-show="show" class="ri-eye-off-line text-lg"></i>
                    </button>
                </div>

                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-sm" />
            </div>

            <!-- Submit -->
            <button type="submit"
                    class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700
                           text-white font-semibold transition">
                Регистрация
            </button>

            <!-- Divider -->
            <div class="flex items-center my-5 sm:my-6">
                <div class="flex-1 border-t border-gray-300"></div>
                <span class="mx-2 sm:mx-4 text-sm text-gray-500">Или войдите с помощью:</span>
                <div class="flex-1 border-t border-gray-300"></div>
            </div>

            <!-- Social buttons -->
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

        </form>

    </div>

</x-guest-layout>
