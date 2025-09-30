<x-app-layout>
    <div class="max-w-2xl mx-auto py-10 text-center">
        <h1 class="text-2xl font-bold mb-4">Личный кабинет</h1>
        <p class="text-gray-600 mb-6">
            Чтобы воспользоваться кабинетом, пожалуйста, войдите или зарегистрируйтесь.
        </p>

        <div class="flex justify-center gap-4">
            @if (Route::has('login'))
                <a href="{{ route('login') }}"
                   class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                    Войти
                </a>
            @endif

            @if (Route::has('register'))
                <a href="{{ route('register') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                    Регистрация
                </a>
            @endif
        </div>
    </div>
</x-app-layout>
