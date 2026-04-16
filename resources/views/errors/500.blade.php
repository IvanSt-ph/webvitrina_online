@extends('layouts.error')

@section('title', '500 — Ошибка сервера')

@section('content')
<div class="text-center">
    <h1 class="text-6xl font-bold text-red-700">500</h1>
    <p class="text-xl mt-4 text-gray-700">Упс! Что-то пошло не так на сервере.</p>
    
    <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
        {{-- Кнопка: На главную --}}
        <a href="{{ url('/') }}" 
           class="bg-indigo-600 text-white px-6 py-3 rounded-lg shadow hover:bg-indigo-700 transition">
            🏠 На главную
        </a>
        
        {{-- Кнопка: Попробовать снова (обновить страницу) --}}
        <button onclick="location.reload()" 
                class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition">
            🔄 Попробовать снова
        </button>
    </div>
    
    {{-- Доп. ссылка для админа (опционально) --}}
    @auth
        @if(auth()->user()->role === 'admin')
            <p class="mt-6 text-sm text-gray-400">
                <a href="{{ route('admin.dashboard') }}" class="hover:underline">← Вернуться в админку</a>
            </p>
        @endif
    @endauth
</div>
@endsection