@extends('admin.layout')

@section('title', 'Добавить пользователя')

@section('content')
<div class="p-4 md:p-6">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.users.index') }}" 
           class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">Добавить пользователя</h1>
            <p class="text-sm text-gray-500 mt-0.5">Заполните информацию о новом пользователе</p>
        </div>
    </div>

    @error('error')
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            {{ $message }}
        </div>
    @enderror

    <form action="{{ route('admin.users.store') }}" method="POST" class="bg-white rounded-xl border border-gray-100 shadow-sm" enctype="multipart/form-data">
        @csrf
        
        <div class="p-6 space-y-5">
            <!-- Имя -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Имя <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       value="{{ old('name') }}"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none transition-all @error('name') border-red-300 bg-red-50 @enderror">
                @error('name') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Email <span class="text-red-500">*</span>
                </label>
                <input type="email" 
                       name="email" 
                       value="{{ old('email') }}"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none transition-all @error('email') border-red-300 bg-red-50 @enderror">
                @error('email') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Телефон -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Телефон <span class="text-gray-400 text-xs">(необязательно)</span>
                </label>
                <input type="tel" 
                       name="phone" 
                       value="{{ old('phone') }}"
                       placeholder="+7 (999) 123-45-67"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none transition-all @error('phone') border-red-300 bg-red-50 @enderror">
                <p class="mt-1 text-xs text-gray-500">Необязательно. Формат: +7 (999) 123-45-67</p>
                @error('phone') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Пароль -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Пароль <span class="text-red-500">*</span>
                </label>
                <input type="password" 
                       name="password" 
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none transition-all @error('password') border-red-300 bg-red-50 @enderror">
                @error('password') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Подтверждение пароля -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Подтверждение пароля <span class="text-red-500">*</span>
                </label>
                <input type="password" 
                       name="password_confirmation" 
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none transition-all">
            </div>

            <!-- Роль -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Роль <span class="text-red-500">*</span>
                </label>
                <select name="role" 
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none bg-white">
                    <option value="buyer" @selected(old('role')==='buyer')>🛒 Покупатель</option>
                    <option value="seller" @selected(old('role')==='seller')>💼 Продавец</option>
                    <option value="admin" @selected(old('role')==='admin')>👑 Администратор</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">
                    👑 Админ: полный доступ<br>
                    💼 Продавец: автоматически создастся магазин<br>
                    🛒 Покупатель: только покупки
                </p>
                @error('role') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Аватар -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Аватар
                </label>
                <input type="file" 
                       name="avatar" 
                       accept="image/*"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 outline-none transition-all file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                <p class="mt-1 text-xs text-gray-500">PNG, JPG, GIF до 2MB</p>
                @error('avatar') 
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Кнопки -->
        <div class="flex gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100 rounded-b-xl">
            <button type="submit" 
                    class="flex-1 md:flex-none px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all font-medium shadow-sm hover:shadow-md">
                ✅ Создать пользователя
            </button>
            <a href="{{ route('admin.users.index') }}" 
               class="flex-1 md:flex-none px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-medium text-center">
                Отмена
            </a>
        </div>
    </form>
</div>
@endsection