@extends('admin.layout')

@section('title', 'Редактирование — ' . $user->name)

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">✏️ Редактировать пользователя</h1>

    <form action="{{ route('admin.users.update', $user) }}" method="POST" class="bg-white shadow rounded-lg p-6 space-y-4">
        @csrf
        @method('PUT')

        <!-- Имя -->
        <div>
            <label class="block font-medium mb-1">Имя</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                   class="border rounded-lg px-3 py-2 w-full">
            @error('name') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <!-- Email -->
        <div>
            <label class="block font-medium mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                   class="border rounded-lg px-3 py-2 w-full">
            @error('email') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <!-- Роль -->
        <div>
            <label class="block font-medium mb-1">Роль</label>
            <select name="role" class="border rounded-lg px-3 py-2 w-full">
                <option value="admin" @selected($user->role === 'admin')>Админ</option>
                <option value="seller" @selected($user->role === 'seller')>Продавец</option>
                <option value="buyer" @selected($user->role === 'buyer')>Покупатель</option>
            </select>
            @error('role') <p class="text-red-600 text-sm">{{ $message }}</p> @enderror
        </div>

        <!-- Кнопки -->
        <div class="flex gap-3">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">💾 Сохранить</button>
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg">⬅ Назад</a>
        </div>

        
    </form>
</div>
@endsection
