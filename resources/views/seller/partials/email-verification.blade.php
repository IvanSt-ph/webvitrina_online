{{-- resources/views/seller/partials/email-verification.blade.php --}}
<section class="mt-6 overflow-hidden rounded-2xl border border-neutral-200 bg-white" x-data="{ editingEmail: false }">
    <div class="flex flex-col gap-3 border-b border-neutral-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <i class="ri-mail-line text-xl"></i>
            </div>
            <div class="min-w-0">
                <h3 class="text-base font-semibold text-neutral-950">Email аккаунта</h3>
                <p class="mt-0.5 text-sm text-neutral-500">Для входа, уведомлений и восстановления доступа</p>
            </div>
        </div>

        @if(Auth::user()->hasVerifiedEmail())
            <span class="inline-flex w-fit items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                Подтверждён
            </span>
        @else
            <span class="inline-flex w-fit items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                Ожидает письма
            </span>
        @endif
    </div>

    <div class="p-4 sm:p-5">
        <div x-show="!editingEmail" class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-medium text-neutral-500">Текущий email</p>
                    <p class="mt-1 break-all text-lg font-semibold text-neutral-950">{{ Auth::user()->email }}</p>
                </div>

                <x-action-button type="button" size="sm" x-on:click="editingEmail = true" class="self-start sm:self-auto">
                    <i class="ri-pencil-line"></i>
                    Изменить email
                </x-action-button>
            </div>

            @if(Auth::user()->hasVerifiedEmail())
                <p class="flex items-center gap-2 border-t border-gray-100 pt-3 text-xs text-emerald-700">
                    <i class="ri-shield-check-line text-base"></i>
                    Email готов для системных уведомлений и восстановления доступа.
                </p>
            @else
                <div class="flex flex-col gap-3 border-t border-gray-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="flex min-w-0 items-start gap-2 text-xs text-amber-700">
                        <i class="ri-error-warning-line mt-0.5 shrink-0 text-base"></i>
                        Перейдите по ссылке из письма, чтобы подтвердить адрес.
                    </p>

                    <form method="POST" action="{{ route('verification.send') }}" class="shrink-0">
                        @csrf
                        <x-action-button size="sm">
                            <i class="ri-send-plane-line"></i>
                            Отправить письмо
                        </x-action-button>
                    </form>
                </div>
            @endif
        </div>

        <div x-show="editingEmail" x-transition class="rounded-xl border border-neutral-200 bg-neutral-50/70 p-3 sm:p-4">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <h4 class="text-sm font-semibold text-neutral-950">Изменить email</h4>
                    <p class="mt-0.5 text-xs text-neutral-500">После изменения адрес потребуется подтвердить</p>
                </div>
                <button type="button"
                        @click="editingEmail = false"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-500 transition hover:bg-white hover:text-gray-700"
                        aria-label="Закрыть форму изменения email">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-3">
                @csrf
                @method('PATCH')
                <input type="hidden" name="profile_section" value="email">

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-600" for="account-email">Новый email</label>
                        <div class="relative flex items-center">
                            <input id="account-email"
                                   type="email"
                                   name="email"
                                   value="{{ old('email', Auth::user()->email) }}"
                                   class="w-full rounded-xl border border-neutral-200 bg-white py-2.5 pl-11 pr-4 transition focus:border-brand-300 focus:ring-4 focus:ring-brand-100"
                                   required>
                            <i class="ri-mail-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-1 text-sm" />
                    </div>

                    @if(Auth::user()->hasLocalPassword())
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-gray-600" for="email-current-password">Текущий пароль</label>
                            <input id="email-current-password"
                                   type="password"
                                   name="current_password"
                                   class="w-full rounded-xl border border-neutral-200 bg-white px-4 py-2.5 transition focus:border-brand-300 focus:ring-4 focus:ring-brand-100"
                                   required>
                            <x-input-error :messages="$errors->get('current_password')" class="mt-1 text-sm" />
                        </div>
                    @else
                        <div class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-800">
                            <i class="ri-information-line mt-0.5 text-base"></i>
                            Чтобы изменить email, сначала установите пароль во вкладке «Безопасность».
                        </div>
                    @endif
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-200 pt-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="flex items-start gap-2 text-xs text-gray-500">
                        <i class="ri-information-line mt-0.5 text-sm text-indigo-500"></i>
                        Старое подтверждение будет сброшено.
                    </p>

                    <div class="flex flex-col-reverse gap-2 sm:flex-row">
                        <button type="button"
                                @click="editingEmail = false"
                                class="h-10 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                            Отмена
                        </button>
                        <x-action-button size="sm">
                            <i class="ri-save-line"></i>
                            Сохранить
                        </x-action-button>
                    </div>
                </div>
            </form>
        </div>

        @if(!Auth::user()->hasVerifiedEmail() && session('status') === 'verification-link-sent')
            <div x-data="{ show: true }"
                 x-show="show"
                 x-init="setTimeout(() => show = false, 5000)"
                 x-transition
                 class="mt-3 flex items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2 text-xs text-emerald-700">
                <i class="ri-check-line text-base"></i>
                <span class="flex-1">Письмо отправлено. Проверьте входящие или папку «Спам».</span>
                <button type="button" @click="show = false" class="flex h-7 w-7 items-center justify-center rounded-lg transition hover:bg-emerald-100" aria-label="Закрыть уведомление">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        @endif
    </div>
</section>
