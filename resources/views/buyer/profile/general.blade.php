@extends('buyer.profile')

@section('profile_content')
<section class="bg-white border border-gray-100 rounded-xl sm:rounded-2xl shadow-sm p-3 sm:p-8 space-y-6">

    {{-- Уведомления профиля --}}
    @php $fields = session('updated_fields', []); @endphp
    <div class="space-y-2">
        @if(in_array('name', $fields))
            <div class="flex items-center gap-2 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 shadow-sm">
                <i class="ri-user-line text-green-500"></i> Имя успешно изменено
            </div>
        @endif
        @if(in_array('email', $fields))
            <div class="flex items-center gap-2 p-3 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 shadow-sm">
                <i class="ri-mail-line text-blue-500"></i> Email успешно изменён
            </div>
        @endif
        @if(in_array('phone', $fields))
            <div class="flex items-center gap-2 p-3 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-700 shadow-sm">
                <i class="ri-phone-line text-yellow-500"></i> Телефон успешно изменён
            </div>
        @endif
        @if(in_array('avatar', $fields))
            <div class="flex items-center gap-2 p-3 rounded-lg bg-purple-50 border border-purple-200 text-purple-700 shadow-sm">
                <i class="ri-image-line text-purple-500"></i> Аватар успешно изменён
            </div>
        @endif

        {{-- Статус подтверждения email --}}
        @if(session('status') === 'verification-link-sent')
            <div class="flex items-center gap-2 p-3 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 shadow-sm">
                <i class="ri-mail-line text-blue-500"></i> Письмо для подтверждения email отправлено
            </div>
        @endif

        {{-- Статус подтверждения телефона --}}
        @if(session('phone_sent'))
            <div class="flex items-center gap-2 p-3 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-700 shadow-sm">
                <i class="ri-phone-line text-yellow-500"></i> Сообщение для подтверждения телефона отправлено
            </div>
        @endif
    </div>

    {{-- Заголовок --}}
    <div class="flex items-center justify-between flex-wrap gap-2">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <i class="ri-user-line text-indigo-500 text-xl"></i> Личная информация
        </h2>
        <span class="text-xs text-gray-400">
            Обновлено: {{ Auth::user()->updated_at?->diffForHumans() ?? '—' }}
        </span>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Имя и аватар --}}
        <form method="POST" action="{{ route('buyer.profile.update') }}" enctype="multipart/form-data" class="border border-gray-100 rounded-xl p-4 sm:p-5 space-y-5">
            @csrf
            @method('PATCH')
            <input type="hidden" name="profile_section" value="personal">

            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="ri-user-smile-line text-lg"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Имя и аватар</h3>
                    <p class="text-xs text-gray-500">Основная информация профиля</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-5">
                <div class="relative shrink-0">
                    <img src="{{ Auth::user()->avatar_url }}" class="w-24 h-24 rounded-full border border-gray-200 shadow-sm object-cover" />
                    <label class="absolute bottom-0 right-0 w-9 h-9 rounded-full bg-indigo-500/90 hover:bg-indigo-600 text-white cursor-pointer shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center border border-indigo-400/30">
                        <i class="ri-camera-line"></i>
                        <input type="file" name="avatar" class="hidden" accept="image/*">
                    </label>
                </div>

                <div class="flex-1 w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Имя пользователя</label>
                    <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}"
                           class="w-full px-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none">
                    <x-input-error :messages="$errors->get('name')" class="mt-1 text-sm" />
                </div>
            </div>

            <div class="flex justify-end border-t border-gray-100 pt-4">
                <x-action-button>
                    <i class="ri-save-line"></i>
                    Сохранить имя и аватар
                </x-action-button>
            </div>
        </form>

        {{-- Контакты --}}
        <form method="POST" action="{{ route('buyer.profile.update') }}" class="border border-gray-100 rounded-xl p-4 sm:p-5 space-y-5">
            @csrf
            @method('PATCH')
            <input type="hidden" name="profile_section" value="contacts">
            <input type="hidden" id="phone_dirty" name="phone_dirty" value="0">

            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i class="ri-contacts-line text-lg"></i>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Email и телефон</h3>
                    <p class="text-xs text-gray-500">Контакты и подтверждение аккаунта</p>
                </div>
            </div>

            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email
                        @if(Auth::user()->hasVerifiedEmail())
                           <span class="inline-flex items-center justify-center w-5 h-5 ml-1 rounded-full bg-blue-500">
                                <i class="ri-check-line text-white text-xs"></i>
                            </span>
                        @endif
                    </label>
                    <div class="relative flex items-center">
                        <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}"
                               class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none">
                        <i class="ri-mail-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-1 text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Телефон
                        @if(Auth::user()->hasVerifiedPhone())
                           <span class="inline-flex items-center justify-center w-5 h-5 ml-1 rounded-full bg-blue-500">
                                <i class="ri-check-line text-white text-xs"></i>
                            </span>
                        @endif
                    </label>
                    <input type="tel" id="phone" name="phone"
                           data-intl-manual="true"
                           value="{{ old('phone', Auth::user()->phone) }}"
                           class="w-full py-3 px-4 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm
                                  focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none"
                           placeholder="+373..."
                           title="Введите номер телефона">
                    <x-input-error :messages="$errors->get('phone')" class="mt-1 text-sm" />
                </div>

                @if(Auth::user()->hasLocalPassword())
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Текущий пароль</label>
                        <input type="password" name="current_password"
                               class="w-full py-3 px-4 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm
                                      focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none"
                               placeholder="Нужен только при смене email или телефона">
                        <x-input-error :messages="$errors->get('current_password')" class="mt-1 text-sm" />
                    </div>
                @else
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Для изменения email или телефона сначала
                        <a href="{{ route('buyer.profile.security') }}" class="font-semibold underline">установите пароль</a>.
                    </div>
                @endif
            </div>

            <div class="flex justify-end border-t border-gray-100 pt-4">
                <x-action-button>
                    <i class="ri-save-line"></i>
                    Сохранить контакты
                </x-action-button>
            </div>
        </form>
    </div>

    {{-- Блок подтверждения данных с улучшенным дизайном --}}
    <div class="mt-8 pt-6 border-t border-gray-100">
        <h3 class="text-sm font-medium text-gray-700 mb-4 flex items-center gap-2">
            <i class="ri-shield-check-line text-gray-400"></i> Подтверждение данных
        </h3>
        
        <div class="space-y-4">
            {{-- Подтверждение email --}}
            @if(!Auth::user()->hasVerifiedEmail())
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 bg-blue-50/50 rounded-xl border border-blue-100">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="ri-mail-line text-blue-500"></i>
                            <span class="text-sm font-medium text-gray-700">Email не подтверждён</span>
                        </div>
                        <p class="text-xs text-gray-500">Для полного доступа к функциям подтвердите email</p>
                    </div>
                    <form method="POST" action="{{ route('verification.send') }}" class="shrink-0">
                        @csrf
                        <x-action-button size="sm">
                            <i class="ri-send-plane-line"></i>
                            Подтвердить email
                        </x-action-button>
                    </form>
                </div>
            @else
                <div class="flex items-center justify-between p-4 bg-green-50/50 rounded-xl border border-green-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="ri-check-double-line text-green-600"></i>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Email подтверждён</span>
                            <p class="text-xs text-gray-500">Ваш email успешно верифицирован</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Подтверждение телефона --}}
            @if(!Auth::user()->hasVerifiedPhone())
                <div class="space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 bg-yellow-50/50 rounded-xl border border-yellow-100">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <i class="ri-phone-line text-yellow-500"></i>
                                <span class="text-sm font-medium text-gray-700">Телефон не подтверждён</span>
                            </div>
                            <p class="text-xs text-gray-500">Подтвердите телефон для безопасности аккаунта</p>
                        </div>
                        <form method="POST" action="{{ route('phone.send') }}" class="shrink-0">
                            @csrf
                            <x-action-button size="sm">
                                <i class="ri-message-2-line"></i>
                                Отправить SMS код
                            </x-action-button>
                        </form>
                    </div>

                    {{-- Форма ввода кода --}}
                    @if(session('phone_sent'))
                        <div class="p-4 bg-gray-50 rounded-xl border border-gray-200">
                            <p class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                                <i class="ri-key-line text-gray-500"></i>
                                Введите код из SMS
                            </p>
                            <form method="POST" action="{{ route('phone.verify') }}" class="flex flex-col sm:flex-row gap-3">
                                @csrf
                                <div class="relative flex-1">
                                    <input type="text" name="code" 
                                           placeholder="6-значный код"
                                           class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition outline-none"
                                           maxlength="6"
                                           autocomplete="off">
                                    <i class="ri-shield-keyhole-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                                <x-action-button>
                                    <i class="ri-check-line"></i>
                                    Подтвердить код
                                </x-action-button>
                            </form>
                            <p class="text-xs text-gray-400 mt-2">Код действителен в течение 10 минут</p>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex items-center justify-between p-4 bg-green-50/50 rounded-xl border border-green-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="ri-phone-fill text-green-600"></i>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Телефон подтверждён</span>
                            <p class="text-xs text-gray-500">Ваш номер телефона успешно верифицирован</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Инициализация intl-tel-input для телефона покупателя --}}
    <style>
        .iti { width: 100%; }
        .iti input { width: 100%; }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const phoneInput = document.querySelector('#phone');
        if (!phoneInput) return;
        if (phoneInput.closest('.iti')) return;
        if (!window.intlTelInput) return;

        const iti = window.intlTelInput(phoneInput, {
            initialCountry: "md",
            separateDialCode: false,
            nationalMode: false,
            hiddenInput: function () {
                return {
                    phone: "phone_full",
                };
            },
            placeholderNumberType: "MOBILE",
            utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@25.12.5/build/js/utils.js",
        });

        const savedPhone = phoneInput.value.trim();
        if (savedPhone) {
            iti.setNumber(savedPhone);
        }

        const phoneDirtyInput = document.querySelector('#phone_dirty');
        const markPhoneDirty = function () {
            if (phoneDirtyInput) {
                phoneDirtyInput.value = '1';
            }
        };

        phoneInput.addEventListener('input', markPhoneDirty);
        phoneInput.addEventListener('countrychange', markPhoneDirty);

        const form = phoneInput.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                const fullPhone = iti.getNumber();

                if (fullPhone) {
                    phoneInput.value = fullPhone;
                }
            });
        }
    });
    </script>

</section>
@endsection
