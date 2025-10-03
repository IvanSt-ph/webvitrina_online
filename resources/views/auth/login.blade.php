<x-guest-layout>
    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
        <div class="w-full max-w-md bg-white shadow-lg rounded-2xl p-8">
            
            <!-- Заголовок -->
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">
                Войти в аккаунт
            </h1>

            <!-- Session Status -->
            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <!-- Email -->
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" type="email" name="email" 
                        class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                        :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-1 text-sm" />
                </div>

                <!-- Password -->
                <div x-data="{ show: false }" class="relative">
                    <x-input-label for="password" :value="__('Пароль')" />
                    <x-text-input id="password" x-bind:type="show ? 'text' : 'password'" name="password"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 pr-10"
                        required autocomplete="current-password" />
                    <!-- Глазик -->
                    <button type="button" @click="show = !show" class="absolute right-3 top-9 text-gray-500 hover:text-gray-700">
                        <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 
                                     9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 
                                     0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 
                                     0-8.268-2.943-9.542-7a9.96 9.96 0 
                                     012.187-3.568M6.1 6.1A9.953 9.953 
                                     0 0112 5c4.477 0 8.268 2.943 
                                     9.542 7a9.978 9.978 0 01-4.132 
                                     5.411M15 12a3 3 0 11-6 0 3 3 
                                     0 016 0zM3 3l18 18" />
                        </svg>
                    </button>
                    <x-input-error :messages="$errors->get('password')" class="mt-1 text-sm" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox"
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                               name="remember">
                        <span class="ms-2 text-sm text-gray-600">Запомнить меня</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" 
                           class="text-sm text-indigo-600 hover:text-indigo-800">
                            Забыли пароль?
                        </a>
                    @endif
                </div>

                <!-- Кнопки -->
                <div class="flex items-center justify-between mt-6">
                    <a href="{{ route('register') }}" class="text-sm text-indigo-600 hover:text-indigo-800">
                        Нет аккаунта?
                    </a>

                    <x-primary-button class="px-6 py-2 rounded-lg">
                        {{ __('Войти') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
