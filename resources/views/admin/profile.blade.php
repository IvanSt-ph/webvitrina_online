@extends('admin.layout')

@section('title', 'Настройки аккаунта')

@section('content')
@php
    $providerLabel = $user->provider ? ucfirst($user->provider) : 'Email и пароль';
    $activityLabels = [
        'profile.updated' => 'Настройки аккаунта изменены',
        'user.updated' => 'Профиль пользователя изменён',
        'user.created' => 'Пользователь создан',
        'user.deleted' => 'Пользователь удалён',
        'seller_plan_request.approved' => 'Тариф одобрен',
        'seller_plan_request.rejected' => 'Заявка на тариф отклонена',
        'chat.locked' => 'Диалог заблокирован',
        'chat.unlocked' => 'Диалог разблокирован',
        'chat.deleted' => 'Диалог скрыт',
    ];
@endphp

<div class="space-y-5">
    <section class="overflow-hidden rounded-2xl border border-indigo-100 bg-white shadow-sm">
        <div class="border-b border-indigo-100 bg-indigo-50/70 p-4 sm:p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <img src="{{ $user->avatar_url }}"
                         class="h-16 w-16 shrink-0 rounded-2xl border border-white object-cover shadow-sm sm:h-20 sm:w-20"
                         alt="Аватар {{ $user->name }}">
                    <div class="min-w-0">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-indigo-200 bg-white px-3 py-1 text-xs font-bold text-indigo-700">
                            <i class="ri-shield-user-line"></i>
                            Администратор
                        </span>
                        <h1 class="mt-2 truncate text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ $user->name }}</h1>
                        <p class="mt-1 truncate text-sm text-slate-500">{{ $user->email }} · ID {{ $user->id }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                    <a href="{{ route('admin.activity.index') }}"
                       class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-white px-4 text-sm font-semibold text-indigo-700 transition hover:border-indigo-300 hover:bg-indigo-50">
                        <i class="ri-history-line"></i>
                        Журнал
                    </a>
                    <a href="{{ route('admin.dashboard') }}"
                       class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                        <i class="ri-dashboard-line"></i>
                        Главная
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-px bg-slate-100 sm:grid-cols-3">
            <div class="bg-white p-4">
                <div class="text-xs font-semibold uppercase text-slate-400">Email</div>
                <div class="mt-2 flex items-center gap-2 text-sm font-bold {{ $user->email_verified_at ? 'text-emerald-700' : 'text-amber-700' }}">
                    <i class="{{ $user->email_verified_at ? 'ri-checkbox-circle-line' : 'ri-error-warning-line' }}"></i>
                    {{ $user->email_verified_at ? 'Подтверждён' : 'Не подтверждён' }}
                </div>
            </div>
            <div class="bg-white p-4">
                <div class="text-xs font-semibold uppercase text-slate-400">Способ входа</div>
                <div class="mt-2 flex items-center gap-2 text-sm font-bold text-slate-800">
                    <i class="ri-key-2-line text-indigo-600"></i>
                    {{ $providerLabel }}
                </div>
            </div>
            <div class="bg-white p-4">
                <div class="text-xs font-semibold uppercase text-slate-400">Действия в журнале</div>
                <div class="mt-2 flex items-center gap-2 text-sm font-bold text-slate-800">
                    <i class="ri-file-list-3-line text-indigo-600"></i>
                    {{ $activityCount }}
                </div>
            </div>
        </div>
    </section>

    @if (session('success'))
        <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
            <i class="ri-checkbox-circle-fill mt-0.5 text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <div class="mb-2 flex items-center gap-2 font-bold">
                <i class="ri-error-warning-line"></i>
                Проверьте введённые данные
            </div>
            <ul class="space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.profile.update') }}" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        @csrf
        @method('PUT')

        <div class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-5 flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xl text-indigo-600">
                        <i class="ri-user-settings-line"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Личные данные</h2>
                        <p class="mt-1 text-sm text-slate-500">Имя отображается в панели, email используется для входа и уведомлений.</p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="admin-name" class="mb-2 block text-sm font-bold text-slate-800">Имя</label>
                        <input id="admin-name"
                               name="name"
                               type="text"
                               required
                               autocomplete="name"
                               value="{{ old('name', $user->name) }}"
                               class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100 @error('name') border-rose-300 bg-rose-50 @enderror">
                        @error('name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="admin-email" class="mb-2 block text-sm font-bold text-slate-800">Email</label>
                        <input id="admin-email"
                               name="email"
                               type="email"
                               required
                               autocomplete="email"
                               value="{{ old('email', $user->email) }}"
                               class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100 @error('email') border-rose-300 bg-rose-50 @enderror">
                        @error('email') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" x-data="{ showCurrent: false, showNew: false, showConfirm: false }">
                <div class="mb-5 flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xl text-indigo-600">
                        <i class="ri-lock-password-line"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">Безопасность</h2>
                        <p class="mt-1 text-sm text-slate-500">Новый пароль оставьте пустым, если менять его не требуется.</p>
                    </div>
                </div>

                @if($user->hasLocalPassword())
                    <div class="mb-4 rounded-xl border border-indigo-100 bg-indigo-50 p-3 text-sm text-indigo-800">
                        Для смены email или пароля потребуется текущий пароль администратора.
                    </div>
                    <div class="mb-4">
                        <label for="current-password" class="mb-2 block text-sm font-bold text-slate-800">Текущий пароль</label>
                        <div class="relative">
                            <input id="current-password"
                                   name="current_password"
                                   :type="showCurrent ? 'text' : 'password'"
                                   autocomplete="current-password"
                                   class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 pr-12 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100 @error('current_password') border-rose-300 bg-rose-50 @enderror">
                            <button type="button" @click="showCurrent = !showCurrent" class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Показать пароль">
                                <i :class="showCurrent ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                            </button>
                        </div>
                        @error('current_password') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                        У аккаунта пока нет локального пароля. Можно установить его ниже для резервного входа.
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="admin-password" class="mb-2 block text-sm font-bold text-slate-800">Новый пароль</label>
                        <div class="relative">
                            <input id="admin-password"
                                   name="password"
                                   :type="showNew ? 'text' : 'password'"
                                   autocomplete="new-password"
                                   class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 pr-12 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100 @error('password') border-rose-300 bg-rose-50 @enderror">
                            <button type="button" @click="showNew = !showNew" class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Показать пароль">
                                <i :class="showNew ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                            </button>
                        </div>
                        @error('password') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="admin-password-confirmation" class="mb-2 block text-sm font-bold text-slate-800">Повторите пароль</label>
                        <div class="relative">
                            <input id="admin-password-confirmation"
                                   name="password_confirmation"
                                   :type="showConfirm ? 'text' : 'password'"
                                   autocomplete="new-password"
                                   class="h-12 w-full rounded-xl border border-slate-200 bg-white px-4 pr-12 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                            <button type="button" @click="showConfirm = !showConfirm" class="absolute right-2 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Показать пароль">
                                <i :class="showConfirm ? 'ri-eye-off-line' : 'ri-eye-line'"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold text-slate-950">Последние действия</h2>
                    <a href="{{ route('admin.activity.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Все</a>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse($recentActivity as $activity)
                        <div class="rounded-xl bg-slate-50 p-3">
                            <p class="text-sm font-semibold text-slate-800">{{ $activityLabels[$activity->action] ?? $activity->description ?? $activity->action }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $activity->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 p-4 text-center text-sm text-slate-500">
                            Действий пока нет.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-lg font-bold text-slate-950">Состояние аккаунта</h2>
                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Создан</span>
                        <span class="font-semibold text-slate-800">{{ $user->created_at?->format('d.m.Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Пароль</span>
                        <span class="font-semibold text-slate-800">{{ $user->hasLocalPassword() ? 'Установлен' : 'Не задан' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-slate-500">Изменён</span>
                        <span class="font-semibold text-slate-800">{{ $user->updated_at?->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
            </section>

            <div class="sticky bottom-4 rounded-2xl border border-indigo-100 bg-white p-4 shadow-xl shadow-slate-950/5">
                <button type="submit"
                        class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl border border-indigo-400/30 bg-indigo-500/90 px-5 text-sm font-bold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-600 hover:shadow-xl">
                    <i class="ri-save-3-line"></i>
                    Сохранить настройки
                </button>
                <p class="mt-3 text-center text-xs leading-5 text-slate-500">Изменения безопасности фиксируются в журнале администратора.</p>
            </div>
        </aside>
    </form>
</div>
@endsection
