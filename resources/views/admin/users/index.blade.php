@extends('admin.layout')

@section('title', 'Пользователи')

@section('content')
@php
    $search = request('q');
    $currentRole = request('role');
    $currentState = request('state');
    $currentSort = $sort ?? request('sort', 'latest');

    $roles = [
        null => ['label' => 'Все', 'icon' => 'ri-group-line'],
        'admin' => ['label' => 'Админы', 'icon' => 'ri-shield-user-line', 'class' => 'border-indigo-200 bg-indigo-50 text-indigo-700'],
        'seller' => ['label' => 'Продавцы', 'icon' => 'ri-store-2-line', 'class' => 'border-sky-200 bg-sky-50 text-sky-700'],
        'buyer' => ['label' => 'Покупатели', 'icon' => 'ri-shopping-bag-3-line', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'],
    ];

    $roleBadges = [
        'admin' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
        'seller' => 'border-sky-200 bg-sky-50 text-sky-700',
        'buyer' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
    ];

    $stateLabels = [
        'email_verified' => 'Email подтверждён',
        'phone_verified' => 'Телефон подтверждён',
        'no_password' => 'Без локального пароля',
        'social' => 'Social-вход',
        'sellers_without_shop' => 'Продавцы без магазина',
    ];

    $baseFilters = array_filter([
        'q' => $search,
        'state' => $currentState,
        'sort' => $currentSort !== 'latest' ? $currentSort : null,
    ], fn ($value) => filled($value));
@endphp

<div class="space-y-5">
    @if(session('success'))
        <div class="flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
            <i class="ri-check-line text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="flex items-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 shadow-sm">
            <i class="ri-error-warning-line text-lg"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <section class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                    <i class="ri-user-settings-line"></i>
                    Панель администратора
                </div>
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Пользователи</h1>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                    Управление покупателями, продавцами и администраторами: роли, контакты, магазины, активность и быстрые действия.
                </p>
            </div>

            <a href="{{ route('admin.users.create') }}"
               class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                <i class="ri-user-add-line text-lg"></i>
                Добавить пользователя
            </a>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-slate-500">
                <span>Всего пользователей</span>
                <i class="ri-group-line text-indigo-500"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($summary['total'] ?? 0, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-slate-400">Все роли системы</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-emerald-700">
                <span>Email подтверждён</span>
                <i class="ri-mail-check-line"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-emerald-800">{{ number_format($summary['verified_email'] ?? 0, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-emerald-700/70">Готовы получать письма</p>
        </div>
        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-indigo-700">
                <span>Телефон подтверждён</span>
                <i class="ri-smartphone-line"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-indigo-800">{{ number_format($summary['verified_phone'] ?? 0, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-indigo-700/70">Есть проверенный номер</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-amber-700">
                <span>Продавцы без магазина</span>
                <i class="ri-store-3-line"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-amber-800">{{ number_format($summary['sellers_without_shop'] ?? 0, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-amber-700/70">Нужна проверка профиля</p>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-3 sm:p-4">
            <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_180px_220px_190px_auto]">
                <label class="relative block">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="search"
                           name="q"
                           value="{{ $search }}"
                           placeholder="ID, имя, email, телефон или магазин"
                           class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                </label>

                <select name="role" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Все роли</option>
                    <option value="admin" @selected($currentRole === 'admin')>Админ</option>
                    <option value="seller" @selected($currentRole === 'seller')>Продавец</option>
                    <option value="buyer" @selected($currentRole === 'buyer')>Покупатель</option>
                </select>

                <select name="state" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Любое состояние</option>
                    @foreach($stateLabels as $key => $label)
                        <option value="{{ $key }}" @selected($currentState === $key)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="sort" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <option value="latest" @selected($currentSort === 'latest')>Сначала новые</option>
                    <option value="oldest" @selected($currentSort === 'oldest')>Сначала старые</option>
                    <option value="name" @selected($currentSort === 'name')>По имени</option>
                    <option value="orders_desc" @selected($currentSort === 'orders_desc')>Больше заказов</option>
                    <option value="products_desc" @selected($currentSort === 'products_desc')>Больше товаров</option>
                </select>

                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                    <i class="ri-filter-3-line"></i>
                    Применить
                </button>
            </form>

            @if($search || $currentRole || $currentState || $currentSort !== 'latest')
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                    <span class="font-semibold uppercase tracking-wide text-slate-400">Фильтр:</span>
                    @if($search)
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">Поиск: {{ $search }}</span>
                    @endif
                    @if($currentRole)
                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700">Роль: {{ $roles[$currentRole]['label'] ?? $currentRole }}</span>
                    @endif
                    @if($currentState)
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">Состояние: {{ $stateLabels[$currentState] ?? $currentState }}</span>
                    @endif
                    <a href="{{ route('admin.users.index') }}" class="rounded-full border border-slate-200 px-2.5 py-1 font-semibold text-slate-500 transition hover:border-indigo-200 hover:text-indigo-700">
                        Сбросить
                    </a>
                </div>
            @endif
        </div>

        <div class="overflow-x-auto border-b border-slate-100 px-3 py-2">
            <div class="flex min-w-max items-center gap-2">
                @foreach($roles as $key => $role)
                    @php
                        $isActive = ($currentRole === null && $key === null) || ($currentRole !== null && (string) $currentRole === (string) $key);
                        $count = $key === null ? ($summary['total'] ?? 0) : ($roleCounts[$key] ?? 0);
                        $href = $key === null
                            ? route('admin.users.index', $baseFilters)
                            : route('admin.users.index', array_merge($baseFilters, ['role' => $key]));
                    @endphp
                    <a href="{{ $href }}"
                       class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm transition {{ $isActive ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50' }}">
                        <i class="{{ $role['icon'] }}"></i>
                        <span>{{ $role['label'] }}</span>
                        <span class="rounded-full bg-white px-2 py-0.5 text-xs font-semibold">{{ number_format($count, 0, ',', ' ') }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="hidden xl:block">
            <table class="min-w-full text-sm">
                <thead class="border-b border-slate-100 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">Пользователь</th>
                        <th class="px-4 py-3 text-left font-semibold">Контакты</th>
                        <th class="px-4 py-3 text-left font-semibold">Роль и профиль</th>
                        <th class="px-4 py-3 text-left font-semibold">Активность</th>
                        <th class="px-4 py-3 text-left font-semibold">Создан</th>
                        <th class="px-4 py-3 text-right font-semibold">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                        @php
                            $roleClass = $roleBadges[$user->role] ?? 'border-slate-200 bg-slate-50 text-slate-700';
                            $trustProfile = $trustProfiles[$user->id] ?? null;
                            $roleLabel = match($user->role) {
                                'admin' => 'Админ',
                                'seller' => 'Продавец',
                                default => 'Покупатель',
                            };
                        @endphp
                        <tr class="align-top transition hover:bg-indigo-50/25">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->avatar_url }}"
                                         class="h-11 w-11 rounded-full border border-slate-200 object-cover"
                                         alt="Аватар {{ $user->name }}">
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.users.show', $user) }}" class="block truncate font-bold text-slate-950 transition hover:text-indigo-700">
                                            {{ $user->name }}
                                        </a>
                                        <div class="mt-1 text-xs text-slate-400">ID {{ $user->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <i class="ri-mail-line text-slate-400"></i>
                                        <span class="max-w-[220px] truncate text-slate-700">{{ $user->email }}</span>
                                        @if($user->email_verified_at)
                                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">OK</span>
                                        @else
                                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Не подтверждён</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 text-slate-500">
                                        <i class="ri-smartphone-line text-slate-400"></i>
                                        <span>{{ $user->phone ?: 'Телефон не указан' }}</span>
                                        @if($user->phone_verified_at)
                                            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700">OK</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold {{ $roleClass }}">
                                    <i class="{{ $roles[$user->role]['icon'] ?? 'ri-user-line' }}"></i>
                                    {{ $roleLabel }}
                                </span>
                                <div class="mt-2 space-y-1 text-xs text-slate-500">
                                    @if($trustProfile)
                                        <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 font-bold {{ $trustProfile['class'] }}" title="{{ $trustProfile['label'] }}: {{ $trustProfile['score'] }}%">
                                            <span>{{ $trustProfile['icon'] }}</span>
                                            {{ $trustProfile['short_label'] }}
                                            <span class="text-current/70">{{ $trustProfile['score'] }}%</span>
                                        </span>
                                    @endif
                                    @if($user->role === 'seller')
                                        @if($user->shop)
                                            <a href="{{ route('seller.show', $user->shop->slug) }}" class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-800">
                                                <i class="ri-store-2-line"></i>
                                                {{ $user->shop->name }}
                                            </a>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 font-semibold text-amber-700">
                                                <i class="ri-error-warning-line"></i>
                                                Магазин не создан
                                            </span>
                                        @endif
                                    @else
                                        <a href="{{ route('users.public.show', $user) }}" class="inline-flex items-center gap-1 font-semibold text-slate-600 hover:text-indigo-700">
                                            <i class="ri-user-3-line"></i>
                                            Публичная карточка
                                        </a>
                                    @endif
                                    <div>{{ $user->hasLocalPassword() ? 'Локальный пароль есть' : 'Вход без локального пароля' }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <div class="rounded-xl bg-slate-50 px-2 py-2">
                                        <div class="text-base font-bold text-slate-950">{{ $user->orders_count }}</div>
                                        <div class="text-[11px] text-slate-400">Заказы</div>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-2 py-2">
                                        <div class="text-base font-bold text-slate-950">{{ $user->products_count }}</div>
                                        <div class="text-[11px] text-slate-400">Товары</div>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-2 py-2">
                                        <div class="text-base font-bold text-slate-950">{{ $user->followed_shops_count }}</div>
                                        <div class="text-[11px] text-slate-400">Подписки</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-500">
                                <div>{{ $user->created_at?->format('d.m.Y H:i') }}</div>
                                <div class="mt-1 text-xs text-slate-400">Обновлён {{ $user->updated_at?->diffForHumans() }}</div>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    @if($user->role !== 'admin')
                                        <form method="POST" action="{{ route('admin.chats.support.start', $user) }}">
                                            @csrf
                                            <button class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-700 transition hover:bg-indigo-100" title="Открыть support-чат">
                                                <i class="ri-customer-service-2-line"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('admin.users.show', $user) }}" class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-50 text-slate-700 transition hover:bg-slate-100" title="Просмотр">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <a href="{{ route('admin.users.edit', $user) }}" class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-700 transition hover:bg-amber-100" title="Редактировать">
                                        <i class="ri-edit-2-line"></i>
                                    </a>
                                    @unless($user->is(auth()->user()))
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-700 transition hover:bg-rose-100"
                                                    onclick="return confirm('Удалить этого пользователя?')"
                                                    title="Удалить">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                                    <i class="ri-group-line"></i>
                                </div>
                                <h2 class="mt-4 text-lg font-semibold text-slate-900">Пользователи не найдены</h2>
                                <p class="mt-1 text-sm text-slate-500">Попробуйте снять фильтр или изменить строку поиска.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 xl:hidden">
            @forelse($users as $user)
                @php
                    $roleClass = $roleBadges[$user->role] ?? 'border-slate-200 bg-slate-50 text-slate-700';
                    $trustProfile = $trustProfiles[$user->id] ?? null;
                    $roleLabel = match($user->role) {
                        'admin' => 'Админ',
                        'seller' => 'Продавец',
                        default => 'Покупатель',
                    };
                @endphp
                <article class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <img src="{{ $user->avatar_url }}" class="h-11 w-11 rounded-full border border-slate-200 object-cover" alt="Аватар {{ $user->name }}">
                            <div class="min-w-0">
                                <a href="{{ route('admin.users.show', $user) }}" class="block truncate font-bold text-slate-950">{{ $user->name }}</a>
                                <div class="mt-1 text-xs text-slate-400">ID {{ $user->id }}</div>
                            </div>
                        </div>
                        <span class="inline-flex shrink-0 items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-bold {{ $roleClass }}">
                            <i class="{{ $roles[$user->role]['icon'] ?? 'ri-user-line' }}"></i>
                            {{ $roleLabel }}
                        </span>
                    </div>

                    @if($trustProfile)
                        <div class="mt-3 flex items-center justify-between rounded-xl border px-3 py-2 text-sm {{ $trustProfile['class'] }}">
                            <span class="inline-flex items-center gap-2 font-bold">
                                <span>{{ $trustProfile['icon'] }}</span>
                                {{ $trustProfile['label'] }}
                            </span>
                            <span class="font-extrabold">{{ $trustProfile['score'] }}%</span>
                        </div>
                    @endif

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="text-xs text-slate-400">Email</div>
                            <div class="mt-1 truncate font-semibold text-slate-800">{{ $user->email }}</div>
                            <div class="mt-1 text-xs {{ $user->email_verified_at ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $user->email_verified_at ? 'Подтверждён' : 'Не подтверждён' }}
                            </div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="text-xs text-slate-400">Телефон</div>
                            <div class="mt-1 truncate font-semibold text-slate-800">{{ $user->phone ?: 'Не указан' }}</div>
                            <div class="mt-1 text-xs {{ $user->phone_verified_at ? 'text-indigo-600' : 'text-slate-400' }}">
                                {{ $user->phone_verified_at ? 'Подтверждён' : 'Без проверки' }}
                            </div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="text-xs text-slate-400">Активность</div>
                            <div class="mt-1 text-sm font-semibold text-slate-800">{{ $user->orders_count }} заказов · {{ $user->products_count }} товаров</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-3">
                            <div class="text-xs text-slate-400">Создан</div>
                            <div class="mt-1 text-sm font-semibold text-slate-800">{{ $user->created_at?->format('d.m.Y H:i') }}</div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if($user->role !== 'admin')
                            <form method="POST" action="{{ route('admin.chats.support.start', $user) }}">
                                @csrf
                                <button class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-50 px-3 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                                    <i class="ri-customer-service-2-line"></i>
                                    Support
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.users.show', $user) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-slate-50 px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                            <i class="ri-eye-line"></i>
                            Просмотр
                        </a>
                        <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-amber-50 px-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-100">
                            <i class="ri-edit-2-line"></i>
                            Редактировать
                        </a>
                    </div>
                </article>
            @empty
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                        <i class="ri-group-line"></i>
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-slate-900">Пользователи не найдены</h2>
                    <p class="mt-1 text-sm text-slate-500">Попробуйте снять фильтр или изменить строку поиска.</p>
                </div>
            @endforelse
        </div>
    </section>

    @if($users->hasPages())
        <div>
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
