@extends('admin.layout')

@section('title', 'Пользователи')

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Пользователи</h1>

    <!-- 🔍 Поиск и фильтры -->
    <div class="flex items-center justify-between mb-4">
        <form method="GET" class="flex gap-2">
            <input type="text" name="q" placeholder="Поиск по имени или email"
                   value="{{ request('q') }}"
                   class="border rounded-lg px-3 py-2 w-64" />

            <select name="role" class="border rounded-lg px-3 py-2">
                <option value="">Все роли</option>
                <option value="admin" @selected(request('role')==='admin')>Админ</option>
                <option value="seller" @selected(request('role')==='seller')>Продавец</option>
                <option value="buyer" @selected(request('role')==='buyer')>Покупатель</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">
                Применить
            </button>
        </form>

<a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg">
    ➕ Добавить пользователя
</a>

    </div>

    <!-- 📋 Таблица пользователей -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Аватар</th>
                    <th class="px-4 py-3">Имя</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Роль</th>
                    <th class="px-4 py-3">Статус</th>
                    <th class="px-4 py-3 text-right">Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3">{{ $user->id }}</td>
                        <td class="px-4 py-3">
                            <img src="{{ $user->avatar_url ?? asset('images/default-avatar.png') }}"
                                 class="w-8 h-8 rounded-full" alt="Аватар">
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @if($user->role === 'admin')
                                <span class="px-2 py-1 text-xs bg-purple-100 text-purple-700 rounded">Админ</span>
                            @elseif($user->role === 'seller')
                                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded">Продавец</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">Покупатель</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if(rand(0,1)) 
                                <span class="text-green-600">● Онлайн</span>
                            @else 
                                <span class="text-gray-500">● Оффлайн</span>
                            @endif
                        </td>

<td class="px-4 py-3 text-right space-x-2">
    <!-- 👁️ Смотреть -->
    <a href="{{ route('admin.users.show', $user) }}" class="text-indigo-600 hover:underline">👁️</a>

    <!-- ✏️ Редактировать -->
    <a href="{{ route('admin.users.edit', $user) }}" class="text-yellow-600 hover:underline">✏️</a>

    <!-- 🗑️ Удалить -->
    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Удалить пользователя?')">🗑️</button>
    </form>
</td>


                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                            Нет пользователей
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- 📑 Пагинация -->
    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
@endsection
