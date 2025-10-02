@extends('admin.layout')

@section('title', 'Пользователь — ' . $user->name)

@section('content')
<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">👤 Профиль пользователя</h1>

    <div class="bg-white shadow rounded-lg p-6 space-y-4">
        <div class="flex items-center gap-4">
            <img src="{{ $user->avatar_url ?? asset('images/default-avatar.png') }}"
                 class="w-16 h-16 rounded-full border" alt="Аватар">
            <div>
                <h2 class="text-xl font-semibold">{{ $user->name }}</h2>
                <p class="text-gray-600">{{ $user->email }}</p>
            </div>
        </div>

        <p><span class="font-semibold">Роль:</span> {{ $user->role }}</p>
        <p><span class="font-semibold">Дата регистрации:</span> {{ $user->created_at->format('d.m.Y H:i') }}</p>
        <p><span class="font-semibold">Обновлён:</span> {{ $user->updated_at->diffForHumans() }}</p>
    </div>

<div class="mt-6 flex gap-3">
    <a href="{{ route('admin.users.edit', $user) }}" class="px-4 py-2 bg-yellow-500 text-white rounded-lg">✏️ Редактировать</a>
    <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg">⬅ Назад</a>
</div>

</div>
@endsection
