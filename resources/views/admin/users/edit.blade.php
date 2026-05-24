@extends('admin.layout')

@section('title', 'Редактирование — ' . $user->name)

@section('content')
@php
    $roleClass = match($user->role) {
        'admin' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
        'seller' => 'border-sky-200 bg-sky-50 text-sky-700',
        default => 'border-emerald-200 bg-emerald-50 text-emerald-700',
    };
    $roleLabel = match($user->role) {
        'admin' => 'Администратор',
        'seller' => 'Продавец',
        default => 'Покупатель',
    };
@endphp

<div class="space-y-5">
    <section class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center">
                <img src="{{ $user->avatar_url }}"
                     class="h-20 w-20 rounded-2xl border border-slate-200 object-cover shadow-sm"
                     alt="Аватар {{ $user->name }}">
                <div class="min-w-0">
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold {{ $roleClass }}">
                        <i class="ri-user-settings-line"></i>
                        {{ $roleLabel }}
                    </span>
                    <h1 class="mt-3 truncate text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Редактировать пользователя</h1>
                    <p class="mt-1 text-sm text-slate-500">{{ $user->name }} · ID {{ $user->id }} · создан {{ $user->created_at?->format('d.m.Y H:i') }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.users.show', $user) }}"
                   class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                    <i class="ri-eye-line"></i>
                    Профиль
                </a>
                <a href="{{ route('admin.users.index') }}"
                   class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                    <i class="ri-arrow-left-line"></i>
                    Назад
                </a>
            </div>
        </div>
    </section>

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <div class="mb-2 flex items-center gap-2 font-bold">
                <i class="ri-error-warning-line"></i>
                Проверьте форму
            </div>
            <ul class="space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.users.update', $user) }}" method="POST" enctype="multipart/form-data" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        @csrf
        @method('PUT')

        <section class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-slate-950">Основные данные</h2>
                    <p class="mt-1 text-sm text-slate-500">Имя, контакты, роль и аватар пользователя.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="name" class="mb-2 block text-sm font-bold text-slate-800">Имя</label>
                        <input id="name"
                               type="text"
                               name="name"
                               value="{{ old('name', $user->name) }}"
                               required
                               class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100 @error('name') border-rose-300 bg-rose-50 @enderror">
                        @error('name') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="mb-2 block text-sm font-bold text-slate-800">Email</label>
                        <input id="email"
                               type="email"
                               name="email"
                               value="{{ old('email', $user->email) }}"
                               required
                               class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100 @error('email') border-rose-300 bg-rose-50 @enderror">
                        <p class="mt-2 text-xs {{ $user->email_verified_at ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $user->email_verified_at ? 'Email подтверждён ' . $user->email_verified_at->format('d.m.Y H:i') : 'Email пока не подтверждён' }}
                        </p>
                        @error('email') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="phone" class="mb-2 block text-sm font-bold text-slate-800">Телефон</label>
                        <input id="phone"
                               type="tel"
                               name="phone"
                               value="{{ old('phone', $user->phone) }}"
                               placeholder="+373..."
                               class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100 @error('phone') border-rose-300 bg-rose-50 @enderror">
                        <p class="mt-2 text-xs {{ $user->phone_verified_at ? 'text-indigo-600' : 'text-slate-400' }}">
                            {{ $user->phone_verified_at ? 'Телефон подтверждён ' . $user->phone_verified_at->format('d.m.Y H:i') : 'Телефон не подтверждён или не указан' }}
                        </p>
                        @error('phone') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="role" class="mb-2 block text-sm font-bold text-slate-800">Роль</label>
                        <select id="role"
                                name="role"
                                class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100 @error('role') border-rose-300 bg-rose-50 @enderror">
                            <option value="buyer" @selected(old('role', $user->role) === 'buyer')>Покупатель</option>
                            <option value="seller" @selected(old('role', $user->role) === 'seller')>Продавец</option>
                            <option value="admin" @selected(old('role', $user->role) === 'admin')>Администратор</option>
                        </select>
                        <p class="mt-2 text-xs text-slate-500">Смена роли влияет на доступы. Последнего администратора нельзя понизить.</p>
                        @error('role') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="seller_plan" class="mb-2 block text-sm font-bold text-slate-800">Статус продавца</label>
                        <select id="seller_plan"
                                name="seller_plan"
                                class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100 @error('seller_plan') border-rose-300 bg-rose-50 @enderror">
                            @foreach($sellerPlans as $key => $plan)
                                <option value="{{ $key }}" @selected(old('seller_plan', $user->seller_plan ?? 'starter') === $key)>
                                    {{ $plan['label'] }} — {{ $plan['limit'] ? 'до ' . $plan['limit'] . ' товаров' : 'unlimited' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-slate-500">Используется только для продавцов. Для покупателей и админов лимит товаров не применяется.</p>
                        @error('seller_plan') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="avatar" class="mb-2 block text-sm font-bold text-slate-800">Аватар</label>
                        <input id="avatar"
                               type="file"
                               name="avatar"
                               accept="image/*"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-indigo-50 @error('avatar') border-rose-300 bg-rose-50 @enderror">
                        <p class="mt-2 text-xs text-slate-500">JPG, PNG или WebP до 2MB. Новый файл заменит текущий аватар.</p>
                        @error('avatar') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-slate-950">Пароль</h2>
                    <p class="mt-1 text-sm text-slate-500">Оставьте поля пустыми, если пароль менять не нужно.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="password" class="mb-2 block text-sm font-bold text-slate-800">Новый пароль</label>
                        <input id="password"
                               type="password"
                               name="password"
                               autocomplete="new-password"
                               class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100 @error('password') border-rose-300 bg-rose-50 @enderror">
                        @error('password') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-bold text-slate-800">Повторите пароль</label>
                        <input id="password_confirmation"
                               type="password"
                               name="password_confirmation"
                               autocomplete="new-password"
                               class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    </div>
                </div>
            </div>
        </section>

        <aside class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-lg font-bold text-slate-950">Сводка</h2>
                <div class="mt-4 space-y-3">
                    <div class="rounded-xl bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Текущая роль</div>
                        <div class="mt-1 text-sm font-bold text-slate-900">{{ $roleLabel }}</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Пароль</div>
                        <div class="mt-1 text-sm font-bold text-slate-900">{{ $user->hasLocalPassword() ? 'Локальный пароль установлен' : 'Локального пароля нет' }}</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-3">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Провайдер</div>
                        <div class="mt-1 text-sm font-bold text-slate-900">{{ $user->provider ?: 'Локальный аккаунт' }}</div>
                    </div>
                    @if($user->role === 'seller')
                        @if($sellerPlanProfile)
                            <div class="rounded-xl {{ $sellerPlanProfile['class'] }} p-3">
                                <div class="text-xs font-semibold uppercase tracking-wide opacity-70">Статус продавца</div>
                                <div class="mt-1 text-sm font-bold">{{ $sellerPlanProfile['label'] }} · {{ $sellerPlanProfile['used'] }} / {{ $sellerPlanProfile['limit_label'] }} товаров</div>
                            </div>
                        @endif
                        <div class="rounded-xl {{ $user->shop ? 'bg-indigo-50 text-indigo-800' : 'bg-amber-50 text-amber-800' }} p-3">
                            <div class="text-xs font-semibold uppercase tracking-wide opacity-70">Магазин</div>
                            <div class="mt-1 text-sm font-bold">{{ $user->shop?->name ?? 'Магазин не создан' }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 shadow-sm">
                <div class="flex gap-3">
                    <i class="ri-error-warning-line mt-0.5 text-xl"></i>
                    <div>
                        <h3 class="font-bold">Осторожно со сменой роли</h3>
                        <p class="mt-1 leading-6">Понижение администратора, перевод продавца в покупателя или наоборот может изменить доступ к кабинетам и данным.</p>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl shadow-slate-950/5">
                <button type="submit"
                        class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                    <i class="ri-save-3-line"></i>
                    Сохранить изменения
                </button>
                <a href="{{ route('admin.users.show', $user) }}"
                   class="mt-3 inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                    Отмена
                </a>
            </div>
        </aside>
    </form>
</div>
@endsection
