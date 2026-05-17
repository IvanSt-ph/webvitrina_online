@extends('layouts.error')

@section('title', '500 — Ошибка сервера')

@section('content')
<x-error-page
    code="500"
    eyebrow="Ошибка 500"
    title="Что-то пошло не так на сервере"
    description="Мы уже заметили сбой. Попробуйте обновить страницу чуть позже или вернитесь на главную, пока система приходит в себя."
    tone="rose"
>
    <x-slot:actions>
        <button onclick="location.reload()"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-rose-600 px-5 py-3 font-semibold text-white shadow-lg shadow-rose-600/20 transition hover:-translate-y-0.5 hover:bg-rose-700">
            <i class="ri-refresh-line text-lg"></i>
            Попробовать снова
        </button>
        <a href="{{ route('home') }}"
           class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white/80 px-5 py-3 font-semibold text-slate-700 shadow-sm backdrop-blur transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white">
            <i class="ri-home-4-line text-lg"></i>
            На главную
        </a>
    </x-slot:actions>

    <x-slot:links>
        @auth
            @if(auth()->user()->role === 'admin')
                <div class="rounded-3xl border border-white/80 bg-white/65 p-4 text-left shadow-sm backdrop-blur">
                    <div class="text-sm font-semibold text-slate-900">Для администратора</div>
                    <div class="mt-3">
                        <a href="{{ route('admin.dashboard') }}" class="error-chip">
                            <i class="ri-dashboard-line text-base text-rose-500"></i>
                            Перейти в админку
                        </a>
                    </div>
                </div>
            @endif
        @endauth
    </x-slot:links>

    <x-slot:art>
        <div class="error-art-wrap rose-art">
            <div class="art-badge">500</div>
            <div class="art-caption">Перезагружаем мысли</div>
            <div class="server-scene" aria-hidden="true">
                <div class="server-card back"></div>
                <div class="server-card front">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="pulse"></div>
            </div>
        </div>
    </x-slot:art>
</x-error-page>

@include('errors.partials.modern-styles')
@endsection
