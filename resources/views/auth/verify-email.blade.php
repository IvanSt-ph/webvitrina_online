<x-guest-layout>

    <!-- Баннер -->
    <div class="w-full h-56 sm:h-64 overflow-hidden">
        <img src="{{ asset('images/help/banner.jpg') }}"
             class="w-full h-full object-cover" alt="Banner">
    </div>

    <div class="px-4 sm:px-10 py-8 sm:py-10">

        <h1 class="text-center text-xl sm:text-2xl font-semibold text-gray-800 mb-6">
            Подтвердите ваш email
        </h1>

        <p class="text-gray-600 text-sm text-center mb-6">
            Мы отправили вам письмо. Перейдите по ссылке, чтобы завершить регистрацию.
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 text-green-600 text-center text-sm font-medium">
                Новая ссылка отправлена на ваш email.
            </div>
        @endif

        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6">

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <x-primary-button>
                    Отправить письмо снова
                </x-primary-button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="text-gray-600 hover:text-gray-900 underline text-sm">
                    Выйти
                </button>
            </form>

        </div>

    </div>

</x-guest-layout>
