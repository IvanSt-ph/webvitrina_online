{{-- resources/views/seller/partials/security.blade.php --}}
<section class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 sm:p-8 space-y-8">
    {{-- 🔐 Заголовок --}}
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-sm">
                <i class="ri-shield-keyhole-line text-white text-sm"></i>
            </div>
            Безопасность аккаунта
        </h2>
        <span class="text-xs text-gray-400 flex items-center gap-1">
            <i class="ri-time-line text-indigo-300"></i>
            {{ Auth::user()->updated_at?->diffForHumans() ?? '—' }}
        </span>
    </div>

    {{-- ✅ Уведомление о смене пароля --}}
    @if (session('status') === 'password-updated')
        <div class="overflow-hidden rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200/70 shadow-sm">
            <div class="relative p-4">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-emerald-400 to-teal-400"></div>
                <div class="flex items-center gap-3 pl-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-teal-400 flex items-center justify-center shadow-sm">
                        <i class="ri-lock-line text-white text-sm"></i>
                    </div>
                    <p class="text-sm font-medium text-emerald-800">Пароль успешно обновлён! ✨</p>
                </div>
            </div>
        </div>
    @endif

    {{-- 🧷 Смена пароля --}}
    <form method="POST" action="{{ route('password.update') }}" class="space-y-6 max-w-2xl" x-data="{ showOld: false, showNew: false, showConfirm: false }">
        @csrf
        @method('PUT')

        {{-- 🔑 Текущий пароль --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-1">
                <i class="ri-lock-line text-indigo-400 text-sm"></i>
                Текущий пароль
            </label>
            <div class="relative group">
                <div class="absolute -inset-0.5 bg-indigo-400/20 rounded-xl opacity-0 group-focus-within:opacity-100 blur transition-opacity duration-300"></div>
                <div class="relative">
                    <input :type="showOld ? 'text' : 'password'" 
                           name="current_password"
                           placeholder="Введите текущий пароль"
                           class="w-full pl-4 pr-12 py-3 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm
                                  focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100/50 
                                  transition-all duration-200 outline-none @error('current_password') border-rose-300 bg-rose-50/50 @enderror">
                    <button type="button" 
                            @click="showOld = !showOld"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-indigo-600 transition-colors">
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

        {{-- 🔄 Новый и подтверждение --}}
        <div class="grid sm:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-1">
                    <i class="ri-lock-password-line text-indigo-400 text-sm"></i>
                    Новый пароль
                </label>
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-indigo-400/20 rounded-xl opacity-0 group-focus-within:opacity-100 blur transition-opacity duration-300"></div>
                    <div class="relative">
                        <input :type="showNew ? 'text' : 'password'" 
                               name="password"
                               placeholder="Введите новый пароль"
                               class="w-full pl-4 pr-12 py-3 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm
                                      focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100/50 
                                      transition-all duration-200 outline-none @error('password') border-rose-300 bg-rose-50/50 @enderror">
                        <button type="button" 
                                @click="showNew = !showNew"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-indigo-600 transition-colors">
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
                <label class="block text-sm font-medium text-gray-700 mb-1 flex items-center gap-1">
                    <i class="ri-lock-password-line text-indigo-400 text-sm"></i>
                    Подтверждение
                </label>
                <div class="relative group">
                    <div class="absolute -inset-0.5 bg-indigo-400/20 rounded-xl opacity-0 group-focus-within:opacity-100 blur transition-opacity duration-300"></div>
                    <div class="relative">
                        <input :type="showConfirm ? 'text' : 'password'" 
                               name="password_confirmation"
                               placeholder="Повторите пароль"
                               class="w-full pl-4 pr-12 py-3 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm
                                      focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100/50 
                                      transition-all duration-200 outline-none">
                        <button type="button" 
                                @click="showConfirm = !showConfirm"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-indigo-600 transition-colors">
                            <i :class="showConfirm ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🔘 Кнопка --}}
        <div class="flex justify-end pt-4 border-t border-gray-100">
            <button type="submit"
                    class="relative overflow-hidden group px-6 py-3 bg-indigo-500/90 hover:bg-indigo-600 
                           text-white font-medium rounded-xl shadow-md hover:shadow-lg 
                           transition-all duration-300 transform hover:-translate-y-0.5
                           flex items-center gap-2 backdrop-blur-sm border border-indigo-400/30">
                <span class="relative z-10 flex items-center gap-2">
                    <i class="ri-lock-password-line text-lg"></i>
                    Сменить пароль
                    <i class="ri-arrow-right-line text-lg opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"></i>
                </span>
                <span class="absolute inset-0 bg-indigo-600 translate-y-full 
                             group-hover:translate-y-0 transition-transform duration-300"></span>
            </button>
        </div>
    </form>

    {{-- ⚠️ Удаление аккаунта --}}
    <div class="border-t border-gray-100 pt-8">
        <div class="flex items-start gap-4">
            <div class="shrink-0">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-red-600 flex items-center justify-center shadow-md">
                    <i class="ri-delete-bin-6-line text-white text-lg"></i>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold bg-gradient-to-r from-rose-600 to-red-600 bg-clip-text text-transparent">
                    Удаление аккаунта
                </h3>
                <p class="text-sm text-gray-500 mt-1 max-w-md">
                    При удалении аккаунта все данные будут безвозвратно стерты — включая товары, заказы, избранное и статистику.
                </p>

                <form method="POST" action="{{ route('profile.destroy') }}" class="mt-4 max-w-sm">
                    @csrf
                    @method('DELETE')
                    <div class="relative group mb-3">
                        <div class="absolute -inset-0.5 bg-rose-400/20 rounded-xl opacity-0 group-focus-within:opacity-100 blur transition-opacity duration-300"></div>
                        <input type="password" name="password"
                               placeholder="Введите пароль для подтверждения" required
                               class="w-full pl-4 pr-4 py-3 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm
                                      focus:border-rose-300 focus:ring-4 focus:ring-rose-100/50 
                                      transition-all duration-200 outline-none">
                    </div>
                    <button type="submit"
                            onclick="return confirm('Вы уверены, что хотите удалить аккаунт безвозвратно?')"
                            class="relative overflow-hidden group px-5 py-3 bg-rose-500/90 hover:bg-rose-600 
                                   text-white font-medium rounded-xl shadow-md hover:shadow-lg 
                                   transition-all duration-300 transform hover:-translate-y-0.5
                                   flex items-center gap-2 backdrop-blur-sm border border-rose-400/30">
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