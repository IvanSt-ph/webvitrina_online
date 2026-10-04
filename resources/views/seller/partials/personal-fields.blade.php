{{-- resources/views/profile/partials/personal-fields.blade.php --}}
<div class="flex-1 w-full space-y-4">
    {{-- Имя --}}
    <div>
        <label class="mb-2 flex items-center gap-2 text-sm font-medium text-neutral-700">
            <i class="ri-user-3-line text-neutral-400"></i>
            Имя пользователя
        </label>
        <div class="relative">
            <input type="text" 
                   name="name" 
                   value="{{ old('name', Auth::user()->name) }}"
                   maxlength="255"
                   class="w-full rounded-xl border border-neutral-200 bg-white py-3 pl-10 pr-4
                          focus:border-brand-300 focus:ring-4 focus:ring-brand-100
                          transition-all duration-200 outline-none @error('name') border-rose-300 bg-rose-50/50 @enderror"
                   placeholder="Введите ваше имя">
            <i class="ri-user-3-line absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400"></i>
        </div>
        @error('name')
            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                <i class="ri-error-warning-line"></i> {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Email с статусом --}}
    <div>
        <label class="mb-2 flex items-center gap-2 text-sm font-medium text-neutral-700">
            <i class="ri-mail-line text-neutral-400"></i>
            Электронная почта
        </label>
        <div class="relative">
            <input type="email" 
                   name="email"
                   value="{{ old('email', Auth::user()->email) }}"
                   maxlength="255"
                   class="w-full rounded-xl border border-neutral-200 bg-white py-3 pl-10 pr-10
                          focus:border-brand-300 focus:ring-4 focus:ring-brand-100
                          transition-all duration-200 outline-none @error('email') border-rose-300 bg-rose-50/50 @enderror"
                   placeholder="email@example.com">
            <i class="ri-mail-line absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400"></i>
            @if (Auth::user()->hasVerifiedEmail())
                <i class="ri-checkbox-circle-fill absolute right-3 top-1/2 -translate-y-1/2 text-emerald-500"
                   title="Email подтверждён"></i>
            @endif
        </div>
        @error('email')
            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                <i class="ri-error-warning-line"></i> {{ $message }}
            </p>
        @enderror
    </div>

    @if(Auth::user()->hasLocalPassword())
        <div>
            <label class="mb-2 flex items-center gap-2 text-sm font-medium text-neutral-700">
                <i class="ri-lock-password-line text-neutral-400"></i>
                Текущий пароль
            </label>
            <div class="relative">
                <input type="password"
                       name="current_password"
                       class="w-full rounded-xl border border-neutral-200 bg-white py-3 pl-10 pr-4
                              focus:border-brand-300 focus:ring-4 focus:ring-brand-100
                              transition-all duration-200 outline-none @error('current_password') border-rose-300 bg-rose-50/50 @enderror"
                       placeholder="Нужен только при смене email">
                <i class="ri-lock-password-line absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400"></i>
            </div>
            @error('current_password')
                <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                    <i class="ri-error-warning-line"></i> {{ $message }}
                </p>
            @enderror
        </div>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Чтобы менять email, сначала установите пароль во вкладке «Безопасность».
        </div>
    @endif
</div>
