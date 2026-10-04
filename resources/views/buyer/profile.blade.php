{{-- resources/views/buyer/profile.blade.php --}}
<x-buyer-layout title="Настройки профиля">
    <div class="min-h-screen bg-white text-[#202124]">
        <header class="border-b border-[#dadce0] bg-white">
            <div class="flex w-full items-center justify-between gap-5 px-4 py-5 sm:px-6 lg:px-8">
                <div class="min-w-0">
                    <p class="mb-1 text-xs font-medium uppercase tracking-[0.12em] text-[#5f6368]">Аккаунт WebVitrina</p>
                    <h1 class="text-2xl font-normal tracking-tight text-[#202124] sm:text-[28px]">Настройки профиля</h1>
                </div>
                <div class="hidden items-center gap-3 sm:flex">
                    <div class="text-right">
                        <p class="max-w-56 truncate text-sm font-medium text-[#202124]">{{ Auth::user()->name }}</p>
                        <p class="max-w-56 truncate text-xs text-[#5f6368]">{{ Auth::user()->email }}</p>
                    </div>
                    <img data-image-candidates="{{ json_encode(Auth::user()->avatar_candidates ?? []) }}" data-image-fallback="{{ asset('images/avatar-placeholder.svg') }}"
                         src="{{ Auth::user()->avatar_url }}" alt="Аватар пользователя" class="h-10 w-10 rounded-full border border-[#dadce0] object-cover">
                </div>
            </div>
            <div class="w-full overflow-x-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex min-w-max gap-8" aria-label="Настройки профиля">
                    <a href="{{ route('buyer.profile') }}"
                       class="relative inline-flex min-h-12 items-center px-1 text-sm font-medium transition-colors {{ request()->routeIs('buyer.profile') && !request()->routeIs('buyer.profile.security') ? 'text-[#1a73e8]' : 'text-[#5f6368] hover:text-[#202124]' }}"
                       @if(request()->routeIs('buyer.profile') && !request()->routeIs('buyer.profile.security')) aria-current="page" @endif>
                        Общая информация
                        @if(request()->routeIs('buyer.profile') && !request()->routeIs('buyer.profile.security'))<span class="absolute inset-x-0 bottom-0 h-[3px] rounded-t-full bg-[#1a73e8]"></span>@endif
                    </a>
                    <a href="{{ route('buyer.profile.security') }}"
                       class="relative inline-flex min-h-12 items-center px-1 text-sm font-medium transition-colors {{ request()->routeIs('buyer.profile.security') ? 'text-[#1a73e8]' : 'text-[#5f6368] hover:text-[#202124]' }}"
                       @if(request()->routeIs('buyer.profile.security')) aria-current="page" @endif>
                        Безопасность
                        @if(request()->routeIs('buyer.profile.security'))<span class="absolute inset-x-0 bottom-0 h-[3px] rounded-t-full bg-[#1a73e8]"></span>@endif
                    </a>
                </nav>
            </div>
        </header>
        <main class="w-full px-4 py-8 sm:px-6 sm:py-10 lg:px-8 lg:py-12">
            @yield('profile_content')
        </main>
    </div>
</x-buyer-layout>
