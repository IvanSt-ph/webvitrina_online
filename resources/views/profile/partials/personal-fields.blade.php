{{-- resources/views/profile/partials/personal-fields.blade.php --}}
<div class="flex-1 w-full space-y-4">
    {{-- Имя --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
            <i class="ri-user-3-line text-gray-400"></i>
            Имя пользователя
        </label>
        <div class="relative">
            <input type="text" 
                   name="name" 
                   value="{{ old('name', Auth::user()->name) }}"
                   maxlength="255"
                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 
                          focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 
                          transition-all duration-200 @error('name') border-red-500 @enderror"
                   placeholder="Введите ваше имя">
            <i class="ri-user-3-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>
        @error('name')
            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                <i class="ri-error-warning-line"></i> {{ $message }}
            </p>
        @enderror
    </div>

    {{-- Email с статусом --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
            <i class="ri-mail-line text-gray-400"></i>
            Электронная почта
        </label>
        <div class="relative">
            <input type="email" 
                   name="email"
                   value="{{ old('email', Auth::user()->email) }}"
                   maxlength="255"
                   class="w-full pl-10 pr-10 py-3 rounded-xl border border-gray-300 
                          focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 
                          transition-all duration-200 @error('email') border-red-500 @enderror"
                   placeholder="email@example.com">
            <i class="ri-mail-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            @if (Auth::user()->hasVerifiedEmail())
                <i class="ri-checkbox-circle-fill absolute right-3 top-1/2 -translate-y-1/2 text-green-500" 
                   title="Email подтверждён"></i>
            @endif
        </div>
        @error('email')
            <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                <i class="ri-error-warning-line"></i> {{ $message }}
            </p>
        @enderror
    </div>
</div>