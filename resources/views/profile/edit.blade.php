@extends('layouts.app')

@section('title', 'Мой профиль')

@section('content')
<div class="max-w-4xl mx-auto px-6 py-10">

    <h1 class="text-3xl font-bold mb-8">⚙ Настройки профиля</h1>

    {{-- ✅ Уведомления --}}
    @if (session('status') === 'profile-updated')
        <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg">
            ✅ Профиль успешно обновлён!
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg">
            <strong>Ошибка!</strong>
            <ul class="list-disc ml-6 mt-2 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- 👤 Общие поля --}}
    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PATCH')

            <div class="flex items-center gap-6">
                <img src="{{ Auth::user()->avatar_url }}" 
                     class="w-20 h-20 rounded-full border shadow" 
                     alt="Аватар">

                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1">Изменить аватар</label>
                    <input type="file" name="avatar" class="border rounded-lg px-3 py-2 w-full text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Имя</label>
                <input type="text" name="name" value="{{ old('name', Auth::user()->name) }}"
                       class="border rounded-lg px-3 py-2 w-full">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', Auth::user()->email) }}"
                       class="border rounded-lg px-3 py-2 w-full">
            </div>

            {{-- 🔹 Дополнительные поля для продавцов --}}
            @if(Auth::user()->role === 'seller')
                <hr class="my-6">
                <h2 class="text-xl font-semibold mb-2">🏪 Информация о магазине</h2>

                <div>
                    <label class="block text-sm font-medium mb-1">Название магазина</label>
                    <input type="text" name="shop_name" 
                           value="{{ old('shop_name', Auth::user()->shop_name) }}"
                           placeholder="Например: ТехноМаркет 24"
                           class="border rounded-lg px-3 py-2 w-full">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Описание магазина</label>
                    <textarea name="shop_description" rows="3"
                              class="border rounded-lg px-3 py-2 w-full"
                              placeholder="Кратко опишите, чем вы занимаетесь...">{{ old('shop_description', Auth::user()->shop_description) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Контактный номер</label>
                    <input type="text" name="phone" 
                           value="{{ old('phone', Auth::user()->phone) }}"
                           placeholder="+373 777 77 777"
                           class="border rounded-lg px-3 py-2 w-full">
                </div>
            @endif

            <div class="flex gap-3">
                <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    💾 Сохранить
                </button>
                <a href="{{ url('/') }}" class="px-5 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    ⬅ На главную
                </a>
            </div>
        </form>
    </div>

    {{-- 🔒 Смена пароля --}}
    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">🔒 Смена пароля</h2>
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium mb-1">Новый пароль</label>
                <input type="password" name="password" class="border rounded-lg px-3 py-2 w-full">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Подтверждение пароля</label>
                <input type="password" name="password_confirmation" class="border rounded-lg px-3 py-2 w-full">
            </div>

            <button type="submit" class="px-5 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                🔑 Обновить пароль
            </button>
        </form>
    </div>

    {{-- 🗑 Удаление аккаунта --}}
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4 text-red-600">🗑️ Удаление аккаунта</h2>
        <form method="POST" action="{{ route('profile.destroy') }}">
            @csrf
            @method('DELETE')

            <p class="text-gray-700 mb-4">Удаление аккаунта приведёт к безвозвратной потере данных.</p>

            <button type="submit" class="px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                    onclick="return confirm('Вы уверены, что хотите удалить аккаунт?')">
                ❌ Удалить аккаунт
            </button>
        </form>
    </div>

</div>
@endsection
