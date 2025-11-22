<x-guest-layout>

    <!-- Баннер -->
    <div class="w-full h-56 sm:h-64 overflow-hidden">
        <img src="{{ asset('images/help/banner.jpg') }}"
             class="w-full h-full object-cover" alt="Banner">
    </div>

    <div class="px-4 sm:px-10 py-8 sm:py-10">

        <h1 class="text-center text-xl sm:text-2xl font-semibold text-gray-800 mb-6">
            Новый пароль
        </h1>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <!-- Email -->
            <div>
                <label class="block text-sm mb-1 font-medium text-gray-700">Email</label>

                <div class="relative">
                    <i class="ri-mail-line absolute left-3 top-3 text-gray-400 text-lg"></i>

                    <input type="email" name="email" required
                           value="{{ old('email', $request->email) }}"
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300
                                  focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <x-input-error :messages="$errors->get('email')" class="mt-1 text-sm" />
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm mb-1 font-medium text-gray-700">Новый пароль</label>

                <div class="relative">
                    <i class="ri-lock-line absolute left-3 top-3 text-gray-400 text-lg"></i>

                    <input type="password" name="password" required
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300
                                  focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <x-input-error :messages="$errors->get('password')" class="mt-1 text-sm" />
            </div>

            <!-- Confirm -->
            <div>
                <label class="block text-sm mb-1 font-medium text-gray-700">Повторите пароль</label>

                <div class="relative">
                    <i class="ri-lock-password-line absolute left-3 top-3 text-gray-400 text-lg"></i>

                    <input type="password" name="password_confirmation" required
                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300
                                  focus:ring-indigo-500 focus:border-indigo-500 transition">
                </div>

                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-sm" />
            </div>

            <button type="submit"
                    class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700
                           text-white font-semibold transition">
                Сбросить пароль
            </button>

        </form>
    </div>

</x-guest-layout>
