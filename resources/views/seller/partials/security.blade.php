{{-- resources/views/seller/partials/security.blade.php --}}
<section class="space-y-6 rounded-none border-0 bg-transparent p-0 sm:rounded-2xl sm:border sm:border-neutral-200 sm:bg-white sm:p-6 lg:p-8">
    {{-- 🔐 Заголовок --}}
    <div class="flex items-center justify-between">
        <h2 class="flex items-center gap-2 text-lg font-semibold text-neutral-950">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <i class="ri-shield-keyhole-line text-lg"></i>
            </div>
            Безопасность аккаунта
        </h2>
        <span class="flex items-center gap-1 text-xs text-neutral-400">
            <i class="ri-time-line text-brand-300"></i>
            {{ Auth::user()->updated_at?->diffForHumans() ?? '—' }}
        </span>
    </div>

    {{-- ✅ Уведомление о смене пароля --}}
    @if (session('status') === 'password-updated')
        <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50">
            <div class="relative p-4">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-emerald-500"></div>
                <div class="flex items-center gap-3 pl-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center shadow-sm">
                        <i class="ri-lock-line text-white text-sm"></i>
                    </div>
                    <p class="text-sm font-medium text-emerald-800">Пароль успешно обновлён! ✨</p>
                </div>
            </div>
        </div>
    @endif

    {{-- 🧷 Смена пароля --}}
    <form method="POST" action="{{ route('password.update') }}" class="max-w-3xl space-y-6" x-data="{ showOld: false, showNew: false, showConfirm: false }">
        @csrf
        @method('PUT')

        @if(Auth::user()->hasLocalPassword())
        {{-- 🔑 Текущий пароль --}}
        <div>
            <label class="mb-1 flex items-center gap-1 text-sm font-medium text-neutral-700">
                <i class="ri-lock-line text-brand-400 text-sm"></i>
                Текущий пароль
            </label>
            <div class="relative group">
                <div class="absolute -inset-0.5 rounded-xl bg-brand-400/20 opacity-0 blur transition-opacity duration-300 group-focus-within:opacity-100"></div>
                <div class="relative">
                    <input :type="showOld ? 'text' : 'password'" 
                           name="current_password"
                           placeholder="Введите текущий пароль"
                           class="w-full rounded-xl border border-neutral-200 bg-white py-3 pl-4 pr-12
                                  focus:border-brand-300 focus:ring-4 focus:ring-brand-100/50
                                  transition-all duration-200 outline-none @error('current_password') border-rose-300 bg-rose-50/50 @enderror">
                    <button type="button" 
                            @click="showOld = !showOld"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 transition-colors hover:text-brand-600">
                        <i :class="showOld ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                    </button>
                </div>
            </div>
            @error('current_password')
                <p class="text-xs text-rose-600 mt-1 flex items-center gap-1">
                    <i class="ri-error-warning-line"></i> {{ $message }}
                </p>
            @enderror
        </div>
        @else
            <div class="rounded-xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm text-brand-800">
                Вы входите через внешний провайдер. Установите пароль, чтобы иметь резервный способ входа и подтверждать важные изменения.
            </div>
        @endif

        {{-- 🔄 Новый и подтверждение --}}
        <div class="grid sm:grid-cols-2 gap-6">
            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-neutral-700">
                    <i class="ri-lock-password-line text-brand-400 text-sm"></i>
                    Новый пароль
                </label>
                <div class="relative group">
                    <div class="absolute -inset-0.5 rounded-xl bg-brand-400/20 opacity-0 blur transition-opacity duration-300 group-focus-within:opacity-100"></div>
                    <div class="relative">
                        <input :type="showNew ? 'text' : 'password'" 
                               name="password"
                               placeholder="Введите новый пароль"
                               class="w-full rounded-xl border border-neutral-200 bg-white py-3 pl-4 pr-12
                                      focus:border-brand-300 focus:ring-4 focus:ring-brand-100/50
                                      transition-all duration-200 outline-none @error('password') border-rose-300 bg-rose-50/50 @enderror">
                        <button type="button" 
                                @click="showNew = !showNew"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 transition-colors hover:text-brand-600">
                            <i :class="showNew ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                        </button>
                    </div>
                </div>
                @error('password')
                    <p class="text-xs text-rose-600 mt-1 flex items-center gap-1">
                        <i class="ri-error-warning-line"></i> {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label class="mb-1 flex items-center gap-1 text-sm font-medium text-neutral-700">
                    <i class="ri-lock-password-line text-brand-400 text-sm"></i>
                    Подтверждение
                </label>
                <div class="relative group">
                    <div class="absolute -inset-0.5 rounded-xl bg-brand-400/20 opacity-0 blur transition-opacity duration-300 group-focus-within:opacity-100"></div>
                    <div class="relative">
                        <input :type="showConfirm ? 'text' : 'password'" 
                               name="password_confirmation"
                               placeholder="Повторите пароль"
                               class="w-full rounded-xl border border-neutral-200 bg-white py-3 pl-4 pr-12
                                      focus:border-brand-300 focus:ring-4 focus:ring-brand-100/50
                                      transition-all duration-200 outline-none">
                        <button type="button" 
                                @click="showConfirm = !showConfirm"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 transition-colors hover:text-brand-600">
                            <i :class="showConfirm ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🔘 Кнопка --}}
        <div class="flex justify-end border-t border-neutral-100 pt-4">
            <x-action-button>
                <i class="ri-lock-password-line text-lg"></i>
                {{ Auth::user()->hasLocalPassword() ? 'Сменить пароль' : 'Установить пароль' }}
                <i class="ri-arrow-right-line text-lg opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"></i>
            </x-action-button>
        </div>
    </form>

    {{-- ⚠️ Удаление аккаунта --}}
    <div class="border-t border-neutral-100 pt-6 sm:pt-8">
        <div class="flex items-start gap-4">
            <div class="shrink-0">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                    <i class="ri-delete-bin-6-line text-lg"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-rose-700">
                    Удаление аккаунта
                </h3>
                <p class="mt-1 max-w-md text-sm leading-6 text-neutral-500">
                    При удалении аккаунта все данные будут безвозвратно стерты — включая товары, заказы, избранное и статистику.
                </p>

                <form method="POST" action="{{ route('profile.destroy') }}" class="mt-4 max-w-sm">
                    @csrf
                    @method('DELETE')
                    <div class="relative group mb-3">
                        <div class="absolute -inset-0.5 rounded-xl bg-rose-400/20 opacity-0 blur transition-opacity duration-300 group-focus-within:opacity-100"></div>
                        <input type="password" name="password"
                               placeholder="Введите пароль для подтверждения" required
                               class="w-full rounded-xl border border-neutral-200 bg-white px-4 py-3
                                      focus:border-rose-300 focus:ring-4 focus:ring-rose-100/50 
                                      transition-all duration-200 outline-none">
                    </div>
                    <button type="submit"
                            onclick="return confirm('Вы уверены, что хотите удалить аккаунт безвозвратно?')"
                            class="group relative flex items-center gap-2 overflow-hidden rounded-xl bg-rose-500 px-5 py-3 font-semibold text-white transition hover:bg-rose-600">
                        <span class="relative z-10 flex items-center gap-2">
                            <i class="ri-alert-line text-lg"></i>
                            Удалить аккаунт
                            <i class="ri-arrow-right-line text-lg opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"></i>
                        </span>
                        <span class="absolute inset-0 bg-rose-600 translate-y-full 
                                     group-hover:translate-y-0 transition-transform duration-300"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
