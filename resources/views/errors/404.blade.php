@extends('layouts.error')

@section('title', '404 — Страница не найдена')

@section('content')
<x-error-page
    code="404"
    eyebrow="Ошибка 404"
    title="Мяу! Страница не найдена"
    description="Кот-сыщик всё проверил своей лупой, но нужной странички здесь нет. Возможно, адрес изменился или в ссылке закралась опечатка."
>
    <x-slot:actions>
        <a href="{{ route('home') }}"
           class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:-translate-y-0.5 hover:bg-indigo-700">
            <i class="ri-home-4-line text-lg"></i>
            На главную
        </a>
        <button id="btn-back" type="button"
                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white/80 px-5 py-3 font-semibold text-slate-700 shadow-sm backdrop-blur transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white">
            <i class="ri-arrow-left-line text-lg"></i>
            Назад
        </button>
    </x-slot:actions>

    <x-slot:links>
        <div class="rounded-3xl border border-white/80 bg-white/65 p-4 text-left shadow-sm backdrop-blur">
            <div class="text-sm font-semibold text-slate-900">Куда можно пойти дальше</div>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                <a href="{{ route('category.index') }}" class="error-chip">
                    <i class="ri-layout-grid-line text-base text-indigo-500"></i>
                    Категории
                </a>
                <a href="{{ route('home') }}" class="error-chip">
                    <i class="ri-store-2-line text-base text-indigo-500"></i>
                    Каталог товаров
                </a>
                @auth
                    <a href="{{ route('cabinet') }}" class="error-chip">
                        <i class="ri-user-3-line text-base text-indigo-500"></i>
                        Мой кабинет
                    </a>
                @else
                    <a href="{{ route('login') }}" class="error-chip">
                        <i class="ri-login-box-line text-base text-indigo-500"></i>
                        Войти
                    </a>
                @endauth
            </div>
        </div>
    </x-slot:links>

    <x-slot:art>
        <div class="error-art-wrap">
            <div class="art-badge">404</div>
            <div class="art-caption">Ищем дальше…</div>
            <div class="cat-scene" aria-hidden="true">
                <div class="cat-orbit"></div>
                <div class="magnifier">
                    <div class="glass"><span></span></div>
                    <div class="handle"></div>
                </div>
                <div class="cat">
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
