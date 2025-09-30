@extends('layouts.error')

@section('title', '500 — Ошибка сервера')

@section('content')
<div class="text-center">
    <h1 class="text-6xl font-bold text-red-700">500</h1>
    <p class="text-xl mt-4 text-gray-700">Упс! Что-то пошло не так на сервере.</p>
    <a href="{{ url('/') }}" 
       class="mt-6 inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg shadow hover:bg-indigo-700 transition">
       Попробовать снова
    </a>
</div>
@endsection
