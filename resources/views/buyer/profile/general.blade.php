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

            {{-- Телефон --}}
            <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Телефон
                @if(Auth::user()->hasVerifiedPhone())
                   <span class="inline-flex items-center justify-center w-5 h-5 ml-1 rounded-full bg-blue-500">
                        <i class="ri-check-line text-white text-xs"></i>
                    </span>

                @endif
            </label>
                <div class="relative flex items-center">
                    <input type="text" name="phone" value="{{ old('phone', Auth::user()->phone) }}"
                           placeholder="+373 777 77 777"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>
        </div>

        {{-- Кнопка сохранения --}}
        <div class="flex justify-end border-t border-gray-100 pt-4">
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium shadow-sm flex gap-2">
                <i class="ri-save-line"></i> Сохранить изменения
            </button>
        </div>
    </form>
    {{-- Кнопки подтверждения внизу --}}
    <div class="mt-4 flex gap-2">
        @if(!Auth::user()->hasVerifiedEmail())
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">Подтвердить email</button>
            </form>
        @endif       

    </div>
    @if(!Auth::user()->hasVerifiedPhone())
    <div class="mt-4 flex flex-col gap-2">
        <form method="POST" action="{{ route('phone.send') }}">
            @csrf
            <button class="px-3 py-1 bg-yellow-600 text-white text-xs rounded hover:bg-yellow-700">
                Отправить код на телефон
            </button>
        </form>

        @if(session('phone_sent'))
            <form method="POST" action="{{ route('phone.verify') }}" class="flex gap-2">
                @csrf
                <input type="text" name="code" placeholder="Введите код из SMS"
                       class="border rounded px-2 py-1 w-32">
                <button class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                    Подтвердить код
                </button>
            </form>
        @endif
    </div>
@endif


</section>
@endsection
