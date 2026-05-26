@extends('admin.layout')

@section('title', 'Публичный профиль - ' . $user->name)

@section('content')
    <section class="mb-4 rounded-2xl border border-indigo-100 bg-white p-3 shadow-sm sm:p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="inline-flex items-center gap-2 text-xs font-bold uppercase text-indigo-600">
                    <i class="ri-eye-line"></i>
                    Режим администратора
                </div>
                <h1 class="mt-1 truncate text-base font-bold text-slate-950">Публичная карточка: {{ $user->name }}</h1>
                <p class="mt-1 text-xs text-slate-500">
                    Ниже показано то, что видят посетители. Закрытые данные и решения остаются в карточке управления.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.users.show', $user) }}"
                   class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-700">
                    <i class="ri-arrow-left-line"></i>
                    К управлению
                </a>
                @if($user->role !== 'admin')
                    <form method="POST" action="{{ route('admin.chats.support.start', $user) }}">
                        @csrf
                        <button class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-white px-4 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50">
                            <i class="ri-customer-service-2-line"></i>
                            Support-чат
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.users.edit', $user) }}"
                   class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                    <i class="ri-edit-2-line"></i>
                    Редактировать
                </a>
            </div>
        </div>
    </section>

    @include('users.partials.profile-content', ['isAdminPublicPreview' => true])
@endsection
