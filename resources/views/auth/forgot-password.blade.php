<x-guest-layout>

    <!-- Баннер -->
    <div class="w-full h-56 sm:h-64 overflow-hidden">
        <img src="{{ asset('images/help/banner.jpg') }}"
             class="w-full h-full object-cover" alt="Banner">
    </div>

    <div class="px-4 sm:px-10 py-8 sm:py-10">

        <h1 class="text-center text-xl sm:text-2xl font-semibold text-gray-800 mb-6">
            Восстановление пароля
        </h1>

        <p class="text-gray-600 text-sm mb-6 text-center">
            Укажите ваш email, и мы отправим ссылку для сброса пароля.
        </p>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
            @csrf

            <!-- Email -->
            <div>
                <label class="block text-sm mb-1 font-medium text-gray-700">Email</label>

                <div class="relative">
                    <i class="ri-mail-line absolute left-3 top-3 text-gray-400 text-lg"></i>

                    <input type="email" name="email" required
                           value="{{ old('email') }}"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300
                                  focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <x-input-error :messages="$errors->get('email')" class="mt-1 text-sm" />
            </div>

            <button type="submit"
                    class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700
                           text-white font-semibold transition">
                Отправить ссылку
            </button>

        </form>

    </div>

</x-guest-layout>
