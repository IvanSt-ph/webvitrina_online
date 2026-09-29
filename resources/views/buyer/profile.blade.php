{{-- resources/views/buyer/profile.blade.php --}}
<x-buyer-layout title="Настройки профиля">

<div class="max-w-8xl mx-auto px-3 sm:px-6 py-4 sm:py-8 space-y-6 sm:space-y-8 text-gray-800">

    <!-- 🧭 Вкладки -->
    <div class="border-b border-gray-200 overflow-x-auto">
        <nav class="flex gap-5 sm:gap-6 text-sm min-w-max" aria-label="Настройки профиля">

            <a href="{{ route('buyer.profile') }}"
               class="wv-ui-tab inline-flex min-h-11 items-center px-1 pb-3"
               @if(request()->is('buyer/profile')) aria-current="page" @endif>
                Общая информация
            </a>

            <a href="{{ route('buyer.profile.security') }}"
               class="wv-ui-tab inline-flex min-h-11 items-center px-1 pb-3"
               @if(request()->is('buyer/profile/security')) aria-current="page" @endif>
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
