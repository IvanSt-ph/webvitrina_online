@extends('layouts.error')

@section('title', '403 — Доступ ограничен')

@section('content')
<x-error-page
    code="403"
    eyebrow="Ошибка 403"
    title="Мяу! Доступ сюда закрыт"
    description="Эта часть сайта защищена. Если вы уверены, что должны быть здесь, войдите в нужный аккаунт или вернитесь к доступным разделам."
    tone="amber"
>
    <x-slot:actions>
        <a href="{{ route('home') }}"
           class="inline-flex items-center justify-center gap-2 rounded-2xl bg-amber-500 px-5 py-3 font-semibold text-white shadow-lg shadow-amber-500/20 transition hover:-translate-y-0.5 hover:bg-amber-600">
            <i class="ri-home-4-line text-lg"></i>
            На главную
        </a>
        @auth
            <a href="{{ route('cabinet') }}"
               class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white/80 px-5 py-3 font-semibold text-slate-700 shadow-sm backdrop-blur transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white">
                <i class="ri-user-3-line text-lg"></i>
                В кабинет
            </a>
        @else
            <a href="{{ route('login') }}"
               class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white/80 px-5 py-3 font-semibold text-slate-700 shadow-sm backdrop-blur transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white">
                <i class="ri-login-box-line text-lg"></i>
                Войти
            </a>
        @endauth
    </x-slot:actions>

    <x-slot:links>
        <div class="rounded-3xl border border-white/80 bg-white/65 p-4 text-left shadow-sm backdrop-blur">
            <div class="text-sm font-semibold text-slate-900">Что можно сделать</div>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                <a href="{{ route('home') }}" class="error-chip">
                    <i class="ri-store-2-line text-base text-amber-500"></i>
                    Вернуться к товарам
                </a>
                <button id="btn-back" type="button" class="error-chip text-left">
                    <i class="ri-arrow-left-line text-base text-amber-500"></i>
                    На предыдущую страницу
                </button>
            </div>
        </div>
    </x-slot:links>

    <x-slot:art>
        <div class="error-art-wrap amber-art">
            <div class="art-badge">403</div>
            <div class="art-caption">Только по пропуску</div>
            <div class="guard-scene" aria-hidden="true">
                <div class="shield"></div>
                <div class="guard-cat">
                    <div class="tail"></div>
                    <div class="body"></div>
                    <div class="head">
                        <span class="ear left"></span>
                        <span class="ear right"></span>
                        <span class="eye left"></span>
                        <span class="eye right"></span>
                        <span class="nose"></span>
                        <span class="whisker left top"></span>
                        <span class="whisker left bottom"></span>
                        <span class="whisker right top"></span>
                        <span class="whisker right bottom"></span>
                    </div>
                    <div class="paw"></div>
                </div>
            </div>
        </div>
    </x-slot:art>
</x-error-page>

@include('errors.partials.modern-styles')

<script>
    document.getElementById('btn-back')?.addEventListener('click', () => history.back());
</script>
@endsection
