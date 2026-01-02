@extends('buyer.profile')

@section('profile_content')
<section class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 sm:p-8 space-y-6">

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

    {{-- Форма редактирования профиля --}}
    <form method="POST" action="{{ route('buyer.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PATCH')

        <div class="flex flex-col sm:flex-row items-center gap-6">
            {{-- Аватар --}}
            <div class="relative shrink-0">
                <img src="{{ Auth::user()->avatar_url }}" class="w-24 h-24 rounded-full border border-gray-200 shadow-sm object-cover" />
                <label class="absolute bottom-0 right-0 bg-indigo-600 text-white text-xs px-2 py-1 rounded-md cursor-pointer hover:bg-indigo-700 transition">
                    Изменить
                    <input type="file" name="avatar" class="hidden" accept="image/*">
                </label>
            </div>

            {{-- Имя пользователя --}}
            <div class="flex-1 w-full">
                <label class="block text-sm font-medium text-gray-700 mb-1">Имя пользователя</label>
                <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}"
                       class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 px-4 py-2">
            </div>
        </div>

        {{-- Email и телефон --}}
        <div class="grid sm:grid-cols-2 gap-6">
            {{-- Email --}}
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
                           class="w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <i class="ri-mail-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            {{-- Телефон с intl-tel-input --}}
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
                       value="{{ old('phone', Auth::user()->phone) }}"
                       class="w-full py-2.5 sm:py-3 px-4 rounded-xl border border-gray-300
                              focus:ring-indigo-500 focus:border-indigo-500 transition"
                       placeholder="+373..."
                       title="Введите номер телефона">
                <x-input-error :messages="$errors->get('phone')" class="mt-1 text-sm" />
            </div>
        </div>

        {{-- Кнопка сохранения --}}
        <div class="flex justify-end border-t border-gray-100 pt-4">
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium shadow-sm flex gap-2">
                <i class="ri-save-line"></i> Сохранить изменения
            </button>
        </div>
    </form>

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
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-200 flex items-center gap-2 hover:shadow-md active:scale-[0.98]">
                            <i class="ri-send-plane-line"></i>
                            Подтвердить email
                        </button>
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
                            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-200 flex items-center gap-2 hover:shadow-md active:scale-[0.98]">
                                <i class="ri-message-2-line"></i>
                                Отправить SMS код
                            </button>
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
                                           class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:border-transparent transition"
                                           maxlength="6"
                                           autocomplete="off">
                                    <i class="ri-shield-keyhole-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                                <button type="submit" class="px-4 py-2.5 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white text-sm font-medium rounded-lg shadow-sm transition-all duration-200 flex items-center justify-center gap-2 hover:shadow-md active:scale-[0.98]">
                                    <i class="ri-check-line"></i>
                                    Подтвердить код
                                </button>
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

    {{-- Подключение intl-tel-input --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.2.1/css/intlTelInput.min.css">
    <style>
        .iti { width: 100%; }
        .iti input { width: 100%; }
        .iti__selected-dial-code { display: none; }
        .iti--separate-dial-code .iti__selected-flag { padding-left: 12px; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/18.2.1/js/intlTelInput.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const phoneInput = document.querySelector('#phone');
        if (!phoneInput) return;
        if (phoneInput.closest('.iti')) return;

        window.intlTelInput(phoneInput, {
            initialCountry: "md",
            separateDialCode: true,
            nationalMode: false,
            hiddenInput: "phone_full",
            placeholderNumberType: "MOBILE",
        });
    });
    </script>

</section>
@endsection