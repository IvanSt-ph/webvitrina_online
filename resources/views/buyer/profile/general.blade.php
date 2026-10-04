@extends('buyer.profile')

@section('profile_content')
@php $fields = session('updated_fields', []); @endphp

<div class="space-y-8">
    <section class="grid items-center gap-8 lg:grid-cols-[minmax(260px,0.72fr)_minmax(0,1.28fr)]">
        <div class="max-w-xl">
            <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-full bg-[#e8f0fe] text-[#1967d2]"><i class="ri-user-settings-line text-xl"></i></div>
            <h2 class="text-2xl font-normal tracking-tight text-[#202124] sm:text-3xl">Личная информация</h2>
            <p class="mt-3 max-w-lg text-base leading-7 text-[#5f6368]">Управляйте основной информацией аккаунта и контактами, по которым с вами могут связаться.</p>
            <p class="mt-4 text-sm text-[#5f6368]">Последнее обновление: {{ Auth::user()->updated_at?->diffForHumans() ?? '—' }}</p>
        </div>
        <div class="flex min-h-40 items-center justify-center overflow-hidden rounded-2xl bg-[#f8fafd] p-5">
            <div class="relative">
                <div class="absolute -inset-6 rounded-full bg-[#e8f0fe]"></div>
                <img data-image-candidates="{{ json_encode(Auth::user()->avatar_candidates ?? []) }}" data-image-fallback="{{ asset('images/avatar-placeholder.svg') }}"
                     src="{{ Auth::user()->avatar_url }}" alt="Аватар пользователя" class="relative h-24 w-24 rounded-full border-4 border-white object-cover shadow-sm sm:h-28 sm:w-28">
            </div>
        </div>
    </section>

    <div class="space-y-3" role="status">
        @foreach(['name' => ['ri-user-line', 'Имя успешно изменено'], 'email' => ['ri-mail-line', 'Email успешно изменён'], 'phone' => ['ri-phone-line', 'Телефон успешно изменён'], 'avatar' => ['ri-image-line', 'Аватар успешно изменён']] as $field => [$icon, $message])
            @if(in_array($field, $fields))
                <div class="flex items-center gap-3 rounded-2xl border border-[#ceead6] bg-[#e6f4ea] px-4 py-3 text-sm text-[#137333]"><i class="{{ $icon }} text-lg"></i><span>{{ $message }}</span></div>
            @endif
        @endforeach
        @if(session('status') === 'verification-link-sent')
            <div class="flex items-center gap-3 rounded-2xl border border-[#d2e3fc] bg-[#e8f0fe] px-4 py-3 text-sm text-[#1967d2]"><i class="ri-mail-send-line text-lg"></i><span>Письмо для подтверждения email отправлено</span></div>
        @endif
        @if(session('phone_sent'))
            <div class="flex items-center gap-3 rounded-2xl border border-[#fde293] bg-[#fef7e0] px-4 py-3 text-sm text-[#7a4f01]"><i class="ri-message-2-line text-lg"></i><span>Сообщение для подтверждения телефона отправлено</span></div>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <form method="POST" action="{{ route('buyer.profile.update') }}" enctype="multipart/form-data" class="flex flex-col rounded-2xl border border-[#dadce0] bg-white">
            @csrf @method('PATCH')
            <input type="hidden" name="profile_section" value="personal">
            <div class="border-b border-[#dadce0] px-5 py-5 sm:px-7">
                <h3 class="text-xl font-normal text-[#202124]">Основная информация</h3>
                <p class="mt-1 text-sm text-[#5f6368]">Имя и фотография вашего профиля</p>
            </div>
            <div class="flex-1 space-y-7 p-5 sm:p-7">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                    <div class="relative w-fit shrink-0">
                        <img data-image-candidates="{{ json_encode(Auth::user()->avatar_candidates ?? []) }}" data-image-fallback="{{ asset('images/avatar-placeholder.svg') }}"
                             src="{{ Auth::user()->avatar_url }}" alt="Аватар пользователя" class="h-24 w-24 rounded-full border border-[#dadce0] object-cover">
                        <label class="absolute bottom-0 right-0 flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border-2 border-white bg-[#1a73e8] text-white shadow-sm transition hover:bg-[#1765cc]" title="Изменить фото">
                            <i class="ri-camera-fill"></i><span class="sr-only">Выбрать новое фото</span><input type="file" name="avatar" class="hidden" accept="image/jpeg,image/png,image/webp">
                        </label>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-[#202124]">Фотография профиля</p>
                        <p class="mt-1 text-sm leading-5 text-[#5f6368]">JPG, PNG или WebP. До 8 МБ и 16 мегапикселей.</p>
                        <x-input-error :messages="$errors->get('avatar')" class="mt-2 text-sm" />
                    </div>
                </div>
                <div>
                    <label for="profile-name" class="mb-2 block text-sm font-medium text-[#3c4043]">Имя пользователя</label>
                    <input id="profile-name" type="text" name="name" value="{{ old('name', Auth::user()->name) }}" class="h-14 w-full rounded-lg border-[#dadce0] bg-white px-4 text-base text-[#202124] shadow-none transition hover:border-[#bdc1c6] focus:border-[#1a73e8] focus:ring-1 focus:ring-[#1a73e8]">
                    <x-input-error :messages="$errors->get('name')" class="mt-2 text-sm" />
                </div>
            </div>
            <div class="flex justify-end border-t border-[#dadce0] px-5 py-4 sm:px-7">
                <x-action-button type="submit">
                    <i class="ri-save-line" aria-hidden="true"></i>
                    Сохранить
                </x-action-button>
            </div>
        </form>

        <form method="POST" action="{{ route('buyer.profile.update') }}" class="flex flex-col rounded-2xl border border-[#dadce0] bg-white">
            @csrf @method('PATCH')
            <input type="hidden" name="profile_section" value="contacts"><input type="hidden" id="phone_dirty" name="phone_dirty" value="0">
            <div class="border-b border-[#dadce0] px-5 py-5 sm:px-7">
                <h3 class="text-xl font-normal text-[#202124]">Контактная информация</h3>
                <p class="mt-1 text-sm text-[#5f6368]">Email и номер телефона аккаунта</p>
            </div>
            <div class="flex-1 space-y-6 p-5 sm:p-7">
                <div>
                    <label for="profile-email" class="mb-2 flex items-center gap-2 text-sm font-medium text-[#3c4043]">Email
                        @if(Auth::user()->hasVerifiedEmail())<span class="inline-flex items-center gap-1 rounded-full bg-[#e6f4ea] px-2 py-0.5 text-[11px] font-medium text-[#137333]"><i class="ri-check-line"></i> Подтверждён</span>@endif
                    </label>
                    <div class="relative"><i class="ri-mail-line absolute left-4 top-1/2 -translate-y-1/2 text-lg text-[#5f6368]"></i><input id="profile-email" type="email" name="email" value="{{ old('email', Auth::user()->email) }}" class="h-14 w-full rounded-lg border-[#dadce0] bg-white pl-12 pr-4 text-base text-[#202124] shadow-none transition hover:border-[#bdc1c6] focus:border-[#1a73e8] focus:ring-1 focus:ring-[#1a73e8]"></div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
                </div>
                <div>
                    <label for="phone" class="mb-2 flex items-center gap-2 text-sm font-medium text-[#3c4043]">Телефон
                        @if(Auth::user()->hasVerifiedPhone())<span class="inline-flex items-center gap-1 rounded-full bg-[#e6f4ea] px-2 py-0.5 text-[11px] font-medium text-[#137333]"><i class="ri-check-line"></i> Подтверждён</span>@endif
                    </label>
                    <input type="tel" id="phone" name="phone" data-intl-manual="true" value="{{ old('phone', Auth::user()->phone) }}" placeholder="+373..." title="Введите номер телефона" class="h-14 w-full rounded-lg border-[#dadce0] bg-white px-4 text-base text-[#202124] shadow-none transition hover:border-[#bdc1c6] focus:border-[#1a73e8] focus:ring-1 focus:ring-[#1a73e8]">
                    <x-input-error :messages="$errors->get('phone')" class="mt-2 text-sm" />
                </div>
                @if(Auth::user()->hasLocalPassword())
                    <div>
                        <label for="current-password" class="mb-2 block text-sm font-medium text-[#3c4043]">Текущий пароль</label>
                        <input id="current-password" type="password" name="current_password" placeholder="Только если меняете email" class="h-14 w-full rounded-lg border-[#dadce0] bg-white px-4 text-base text-[#202124] shadow-none transition placeholder:text-[#80868b] hover:border-[#bdc1c6] focus:border-[#1a73e8] focus:ring-1 focus:ring-[#1a73e8]">
                        <p class="mt-2 text-xs text-[#5f6368]">Нужен для защиты аккаунта при смене email.</p><x-input-error :messages="$errors->get('current_password')" class="mt-2 text-sm" />
                    </div>
                @else
                    <div class="rounded-xl bg-[#fef7e0] px-4 py-3 text-sm leading-6 text-[#5f4200]">Для изменения email сначала <a href="{{ route('buyer.profile.security') }}" class="font-medium text-[#1967d2] hover:underline">установите пароль</a>.</div>
                @endif
            </div>
            <div class="flex justify-end border-t border-[#dadce0] px-5 py-4 sm:px-7">
                <x-action-button type="submit">
                    <i class="ri-save-line" aria-hidden="true"></i>
                    Сохранить
                </x-action-button>
            </div>
        </form>
    </div>

    <section class="rounded-2xl border border-[#dadce0] bg-white">
        <div class="border-b border-[#dadce0] px-5 py-5 sm:px-7"><h3 class="text-xl font-normal text-[#202124]">Подтверждение данных</h3><p class="mt-1 text-sm text-[#5f6368]">Подтверждённые контакты помогают защитить и восстановить аккаунт</p></div>
        <div class="divide-y divide-[#dadce0]">
            <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-center sm:px-7">
                <div class="flex min-w-0 flex-1 items-center gap-4"><div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#e8f0fe] text-xl text-[#1967d2]"><i class="ri-mail-line"></i></div><div><p class="text-sm font-medium text-[#202124]">Email</p><p class="mt-0.5 text-sm text-[#5f6368]">{{ Auth::user()->hasVerifiedEmail() ? 'Адрес электронной почты подтверждён' : 'Подтвердите email для полного доступа к функциям' }}</p></div></div>
                @if(!Auth::user()->hasVerifiedEmail())
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <x-secondary-action type="submit" size="sm">
                            <i class="ri-mail-send-line" aria-hidden="true"></i>
                            Подтвердить
                        </x-secondary-action>
                    </form>
                @else<span class="inline-flex w-fit items-center gap-1.5 text-sm font-medium text-[#137333]"><i class="ri-checkbox-circle-fill"></i> Подтверждён</span>@endif
            </div>
            <div class="px-5 py-5 sm:px-7">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex min-w-0 flex-1 items-center gap-4"><div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#e8f0fe] text-xl text-[#1967d2]"><i class="ri-phone-line"></i></div><div><p class="text-sm font-medium text-[#202124]">Телефон</p><p class="mt-0.5 text-sm text-[#5f6368]">{{ Auth::user()->hasVerifiedPhone() ? 'Номер телефона подтверждён' : 'Подтвердите телефон для безопасности аккаунта' }}</p></div></div>
                    @if(!Auth::user()->hasVerifiedPhone())
                        <form method="POST" action="{{ route('phone.send') }}">
                            @csrf
                            <x-secondary-action type="submit" size="sm">
                                <i class="ri-message-2-line" aria-hidden="true"></i>
                                Отправить SMS
                            </x-secondary-action>
                        </form>
                    @else<span class="inline-flex w-fit items-center gap-1.5 text-sm font-medium text-[#137333]"><i class="ri-checkbox-circle-fill"></i> Подтверждён</span>@endif
                </div>
                @if(!Auth::user()->hasVerifiedPhone() && session('phone_sent'))
                    <form method="POST" action="{{ route('phone.verify') }}" class="mt-5 flex max-w-xl flex-col gap-3 rounded-xl bg-[#f8fafd] p-4 sm:flex-row">
                        @csrf
                        <input type="text" name="code" placeholder="6-значный код" maxlength="6" autocomplete="one-time-code" class="h-11 flex-1 rounded-lg border-[#dadce0] bg-white px-4 shadow-none focus:border-[#1a73e8] focus:ring-1 focus:ring-[#1a73e8]">
                        <x-action-button type="submit">
                            <i class="ri-check-line" aria-hidden="true"></i>
                            Подтвердить код
                        </x-action-button>
                    </form>
                @endif
            </div>
        </div>
    </section>
</div>

<style>.iti { width: 100%; } .iti input { width: 100%; }</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const phoneInput = document.querySelector('#phone');
    if (!phoneInput || phoneInput.closest('.iti') || !window.intlTelInput) return;
    const iti = window.intlTelInput(phoneInput, { initialCountry: 'md', separateDialCode: false, nationalMode: false, hiddenInput: function () { return { phone: 'phone_full' }; }, placeholderNumberType: 'MOBILE', dropdownContainer: document.body, loadUtils: window.loadIntlTelInputUtils });
    const savedPhone = phoneInput.value.trim(); if (savedPhone) iti.setNumber(savedPhone);
    const phoneDirtyInput = document.querySelector('#phone_dirty');
    const markPhoneDirty = function () { if (phoneDirtyInput) phoneDirtyInput.value = '1'; };
    phoneInput.addEventListener('input', markPhoneDirty); phoneInput.addEventListener('countrychange', markPhoneDirty);
    const form = phoneInput.closest('form'); if (form) form.addEventListener('submit', function () { const fullPhone = iti.getNumber(); if (fullPhone) phoneInput.value = fullPhone; });
});
</script>
@endsection
