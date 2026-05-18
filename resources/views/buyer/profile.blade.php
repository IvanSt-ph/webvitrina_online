{{-- resources/views/buyer/profile.blade.php --}}
<x-buyer-layout title="Настройки профиля">

<div class="max-w-8xl mx-auto px-3 sm:px-6 py-4 sm:py-8 space-y-6 sm:space-y-8 text-gray-800">

    <!-- 🧭 Вкладки -->
    <div class="border-b border-gray-200 overflow-x-auto">
        <nav class="flex gap-5 sm:gap-6 text-sm font-medium min-w-max">

            <a href="{{ route('buyer.profile') }}"
               class="pb-3 border-b-2 {{ request()->is('buyer/profile') ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Общая информация
            </a>

            <a href="{{ route('buyer.profile.security') }}"
               class="pb-3 border-b-2 {{ request()->is('buyer/profile/security') ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Безопасность
            </a>
        </nav>
    </div>

    <!-- 🧩 Контент снизу -->
    <div class="pt-2 sm:pt-4">
        @yield('profile_content')
    </div>

</div>

</x-buyer-layout>
