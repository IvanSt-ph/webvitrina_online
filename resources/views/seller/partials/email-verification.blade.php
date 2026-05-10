{{-- resources/views/seller/partials/email-verification.blade.php --}}
<section class="mt-6 sm:mt-8 overflow-hidden rounded-xl sm:rounded-2xl border border-gray-200/70 bg-white shadow-sm" x-data="{ editingEmail: false }">
    <div class="border-b border-gray-100 bg-gradient-to-r from-slate-50 via-white to-blue-50/60 px-4 py-4 sm:px-6 sm:py-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-sm">
                    <i class="ri-mail-check-line text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-950">Email аккаунта</h3>
                    <p class="text-sm text-gray-500 mt-0.5">Адрес для уведомлений, восстановления доступа и доверия к магазину</p>
                </div>
            </div>

            @if (Auth::user()->hasVerifiedEmail())
                <span class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    Подтверждён
                </span>
            @else
                <span class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm font-semibold">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    Ожидает письма
                </span>
            @endif
        </div>
    </div>

    <div class="p-4 sm:p-6">
        <div class="grid lg:grid-cols-2 gap-4 sm:gap-5 items-stretch">
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Email и изменение</p>
                        <p class="text-xs text-gray-500 mt-0.5">Обновите email для входа и уведомлений</p>
                    </div>
                    <i class="ri-edit-line text-blue-500 text-lg"></i>
                </div>

                <div x-show="!editingEmail" class="h-full rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm">
                    <div class="h-full flex flex-col justify-between gap-4 sm:gap-5">
                        <div class="flex items-start gap-4 min-w-0">
                            <div class="w-11 h-11 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                                <i class="ri-mail-line text-xl"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Текущий email</p>
                                <p class="text-base font-semibold text-gray-950 mt-1 break-all">{{ Auth::user()->email }}</p>
                                <p class="text-sm text-gray-500 mt-1">Этот адрес используется для входа и системных уведомлений.</p>
                            </div>
                        </div>

                        <button type="button"
                                @click="editingEmail = true"
                                class="relative overflow-hidden group h-11 px-5 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2 w-full backdrop-blur-sm border border-indigo-400/30">
                            <span class="relative z-10 flex items-center gap-2">
                                <i class="ri-pencil-line"></i>
                                Изменить email
                            </span>
                            <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
                        </button>
                    </div>
                </div>

                <div x-show="editingEmail"
                     x-transition
                     class="h-full rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm space-y-4 sm:space-y-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-950">Изменить email</h4>
                            <p class="text-xs text-gray-500 mt-0.5">После изменения email потребуется подтверждение</p>
                        </div>
                        <button type="button"
                                @click="editingEmail = false"
                                class="w-9 h-9 rounded-lg bg-gray-50 hover:bg-gray-100 text-gray-500 transition flex items-center justify-center">
                            <i class="ri-close-line text-xl"></i>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="profile_section" value="email">

                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-900">Новый email</label>
                            <div class="relative flex items-center">
                                <input type="email"
                                       name="email"
                                       value="{{ old('email', Auth::user()->email) }}"
                                       class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition"
                                       required>
                                <i class="ri-mail-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-1 text-sm" />
                        </div>

                        <div class="rounded-xl bg-blue-50 border border-blue-100 px-3 py-2.5 text-xs text-blue-700 flex items-start gap-2">
                            <i class="ri-information-line text-base mt-0.5"></i>
                            <span>Старое подтверждение будет сброшено, письмо можно отправить справа.</span>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <button type="button"
                                    @click="editingEmail = false"
                                    class="h-11 px-4 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-xl border border-gray-200 shadow-sm transition">
                                Отмена
                            </button>
                            <button type="submit"
                                    class="relative overflow-hidden group h-11 px-4 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2 backdrop-blur-sm border border-indigo-400/30">
                                <span class="relative z-10 flex items-center gap-2">
                                    <i class="ri-save-line"></i>
                                    Сохранить
                                </span>
                                <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Подтверждение email</p>
                        <p class="text-xs text-gray-500 mt-0.5">Подтверждение защищает аккаунт продавца</p>
                    </div>
                    <i class="ri-shield-check-line text-emerald-500 text-lg"></i>
                </div>

                @if (Auth::user()->hasVerifiedEmail())
                    <div class="h-full rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 sm:p-5 flex flex-col justify-between">
                        <div class="flex items-start gap-4">
                            <div class="w-11 h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                                <i class="ri-shield-check-line text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-950">Email подтверждён</p>
                                <p class="text-sm text-gray-600 mt-1">Адрес готов для системных уведомлений и восстановления доступа.</p>
                            </div>
                        </div>
                        <div class="mt-5 rounded-xl bg-white/70 border border-emerald-100 px-3 py-2 text-xs text-emerald-700 flex items-center gap-2">
                            <i class="ri-check-double-line"></i>
                            Дополнительных действий не требуется
                        </div>
                    </div>
                @else
                    <div class="h-full rounded-2xl border border-amber-200 bg-amber-50/70 p-4 sm:p-5 flex flex-col justify-between gap-4 sm:gap-5">
                        <div class="flex items-start gap-4">
                            <div class="w-11 h-11 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                                <i class="ri-mail-send-line text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-950">Отправьте письмо подтверждения</p>
                                <p class="text-sm text-gray-600 mt-1">Перейдите по ссылке из письма, чтобы завершить подтверждение email.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('verification.send') }}" class="w-full">
                            @csrf
                            <button type="submit"
                                    class="relative overflow-hidden group h-11 px-5 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2 w-full backdrop-blur-sm border border-indigo-400/30">
                                <span class="relative z-10 flex items-center gap-2">
                                    <i class="ri-send-plane-line"></i>
                                    Отправить письмо
                                </span>
                                <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        @if (!Auth::user()->hasVerifiedEmail() && session('status') === 'verification-link-sent')
            <div x-data="{ show: true }"
                 x-show="show"
                 x-init="setTimeout(() => show = false, 5000)"
                 x-transition
                 class="mt-5 flex items-start gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700">
                <i class="ri-check-line text-xl mt-0.5"></i>
                <div class="flex-1">
                    <p class="text-sm font-semibold">Письмо отправлено</p>
                    <p class="text-xs text-emerald-600 mt-0.5">Проверьте входящие или папку спам.</p>
                </div>
                <button @click="show = false" class="w-8 h-8 rounded-lg hover:bg-emerald-100 text-emerald-600 flex items-center justify-center transition">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        @endif
    </div>
</section>
