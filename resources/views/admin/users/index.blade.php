@extends('admin.layout')

@section('title', 'Пользователи')

@section('content')
<div class="p-4 md:p-6">
    <!-- Заголовок -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div class="flex items-center gap-2">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <h1 class="text-xl md:text-2xl font-semibold text-gray-800">Пользователи</h1>
            <span class="bg-gray-100 text-gray-600 text-sm px-2 py-1 rounded-md">{{ $users->total() }}</span>
        </div>
        
        <a href="{{ route('admin.users.create') }}" 
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-all text-sm font-medium shadow-sm hover:shadow">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Добавить
        </a>
    </div>

    <!-- Поиск и фильтры с улучшенным UX -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6 shadow-sm">
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" 
                       name="q" 
                       placeholder="Поиск по имени или email..."
                       value="{{ request('q') }}"
                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 outline-none text-sm">
                @if(request('q'))
                    <a href="{{ route('admin.users.index') }}" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <svg class="w-4 h-4 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </a>
                @endif
            </div>
            
            <div class="sm:w-48 relative">
                <select name="role" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 outline-none bg-white text-sm appearance-none cursor-pointer"
                        onchange="this.form.submit()">
                    <option value="">📌 Все роли</option>
                    <option value="admin" @selected(request('role')==='admin')>👑 Админ</option>
                    <option value="seller" @selected(request('role')==='seller')>💼 Продавец</option>
                    <option value="buyer" @selected(request('role')==='buyer')>🛒 Покупатель</option>
                </select>
                <svg class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            
            <button type="submit" 
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Фильтр
            </button>
            
            @if(request('q') || request('role'))
                <a href="{{ route('admin.users.index') }}" 
                   class="px-4 py-2 bg-gray-50 text-gray-600 rounded-lg hover:bg-gray-100 transition-colors text-sm flex items-center justify-center gap-2 border border-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Сбросить
                </a>
            @endif
        </form>
    </div>

    <!-- Таблица для десктопа -->
    <div class="hidden md:block bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Аватар</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Пользователь</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Роль</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors group">
                            <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $user->id }}</td>
                            <td class="px-4 py-3">
                                <img src="{{ $user->avatar_url ?? asset('images/default-avatar.png') }}"
                                     class="w-8 h-8 rounded-full object-cover ring-1 ring-gray-200"
                                     alt="Аватар">
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $user->name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                @if($user->role === 'admin')
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-purple-50 text-purple-700 rounded-md">
                                        <span>👑</span> Админ
                                    </span>
                                @elseif($user->role === 'seller')
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-blue-50 text-blue-700 rounded-md">
                                        <span>💼</span> Продавец
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-green-50 text-green-700 rounded-md">
                                        <span>🛒</span> Покупатель
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($user->is_online ?? rand(0,1))
                                    <span class="inline-flex items-center gap-1 text-green-600">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                        Онлайн
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-gray-500">
                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
                                        Оффлайн
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.users.show', $user) }}" 
                                       class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded transition-colors"
                                       title="Просмотр">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('admin.users.edit', $user) }}" 
                                       class="p-1.5 text-amber-600 hover:bg-amber-50 rounded transition-colors"
                                       title="Редактировать">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors"
                                                onclick="return confirm('Удалить пользователя {{ $user->name }}?')"
                                                title="Удалить">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center gap-2">
                                    <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                    <p>Нет пользователей</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Карточки для мобильных -->
    <div class="md:hidden space-y-3">
        @forelse($users as $user)
            <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <img src="{{ $user->avatar_url ?? asset('images/default-avatar.png') }}"
                             class="w-10 h-10 rounded-full object-cover ring-1 ring-gray-200"
                             alt="Аватар">
                        <div>
                            <div class="font-medium text-gray-800">{{ $user->name }}</div>
                            <div class="text-xs text-gray-500 font-mono">ID: {{ $user->id }}</div>
                        </div>
                    </div>
                    @if($user->is_online ?? rand(0,1))
                        <span class="text-xs text-green-600 bg-green-50 px-2 py-0.5 rounded-full">● Онлайн</span>
                    @else
                        <span class="text-xs text-gray-500 bg-gray-50 px-2 py-0.5 rounded-full">● Оффлайн</span>
                    @endif
                </div>
                
                <div class="space-y-2 text-sm border-t border-gray-100 pt-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">📧 Email</span>
                        <span class="text-gray-700">{{ $user->email }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500">🎭 Роль</span>
                        @if($user->role === 'admin')
                            <span class="text-purple-700">👑 Админ</span>
                        @elseif($user->role === 'seller')
                            <span class="text-blue-700">💼 Продавец</span>
                        @else
                            <span class="text-green-700">🛒 Покупатель</span>
                        @endif
                    </div>
                </div>
                
                <div class="flex gap-2 mt-3 pt-3 border-t border-gray-100">
                    <a href="{{ route('admin.users.show', $user) }}" 
                       class="flex-1 text-center px-3 py-1.5 text-indigo-600 bg-indigo-50 rounded-md text-sm hover:bg-indigo-100 transition-colors">
                        Просмотр
                    </a>
                    <a href="{{ route('admin.users.edit', $user) }}" 
                       class="flex-1 text-center px-3 py-1.5 text-amber-600 bg-amber-50 rounded-md text-sm hover:bg-amber-100 transition-colors">
                        Редактировать
                    </a>
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="flex-1">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="w-full px-3 py-1.5 text-red-600 bg-red-50 rounded-md text-sm hover:bg-red-100 transition-colors"
                                onclick="return confirm('Удалить пользователя?')">
                            Удалить
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-500">
                Нет пользователей
            </div>
        @endforelse
    </div>

    <!-- Улучшенная пагинация -->
    @if($users->hasPages())
        <div class="mt-6">
            {{ $users->appends(request()->query())->onEachSide(1)->links() }}
        </div>
    @endif
</div>

<style>
    /* Плавная анимация для hover */
    .group:hover {
        transition: all 0.2s ease;
    }
    
    /* Улучшенный скролл для таблицы */
    .overflow-x-auto::-webkit-scrollbar {
        height: 6px;
    }
    
    .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    
    .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
</style>
@endsection