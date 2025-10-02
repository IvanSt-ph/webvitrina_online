@extends('admin.layout')

@section('title', 'Добавить пользователя')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">➕ Добавить пользователя</h1>

    <form action="{{ route('admin.users.store') }}" method="POST" class="bg-white shadow rounded-lg p-6 space-y-4">
        @csrf

        <!-- Имя -->
        <div>
            <label class="block font-medium mb-1">Имя</label>
            <input type="text" name="name" value="{{ old('name') }}"
                   class="border rounded-lg px-3 py-2 w-full">
            @error('name') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <!-- Email -->
        <div>
            <label class="block font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                   class="border rounded-lg px-3 py-2 w-full">
            @error('email') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <!-- Пароль -->
        <div>
            <label class="block font-medium mb-1">Пароль</label>
            <input type="password" name="password"
                   class="border rounded-lg px-3 py-2 w-full">
            @error('password') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <!-- Подтверждение пароля -->
        <div>
            <label class="block font-medium mb-1">Подтверждение пароля</label>
            <input type="password" name="password_confirmation"
                   class="border rounded-lg px-3 py-2 w-full">
        </div>

        <!-- Роль -->
        <div>
            <label class="block font-medium mb-1">Роль</label>
            <select name="role" class="border rounded-lg px-3 py-2 w-full">
                <option value="buyer" @selected(old('role')==='buyer')>Покупатель</option>
                <option value="seller" @selected(old('role')==='seller')>Продавец</option>
                <option value="admin" @selected(old('role')==='admin')>Админ</option>
            </select>
            @error('role') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <!-- Кнопки -->
        <div class="flex gap-3">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">✅ Создать</button>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg">⬅ Назад</a>
        </div>
    </form>
</div>
@endsection
