@extends('admin.layout')

@section('title', 'Пользователь — ' . $user->name)

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
    $roleIcon = match($user->role) {
        'admin' => 'ri-shield-user-line',
        'seller' => 'ri-store-2-line',
        default => 'ri-shopping-bag-3-line',
    };
    $conversationsCount = ($user->buyer_conversations_count ?? 0) + ($user->seller_conversations_count ?? 0);
@endphp

<div class="space-y-5">
    <section class="rounded-2xl border border-indigo-100 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center">
                <img src="{{ $user->avatar_url }}"
                     class="h-20 w-20 rounded-2xl border border-slate-200 object-cover shadow-sm"
                     alt="Аватар {{ $user->name }}">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold {{ $roleClass }}">
                            <i class="{{ $roleIcon }}"></i>
                            {{ $roleLabel }}
                        </span>
                        @if($user->email_verified_at)
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                <i class="ri-mail-check-line"></i>
                                Email OK
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                <i class="ri-mail-warning-line"></i>
                                Email не подтверждён
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-bold {{ $trustProfile['class'] }}">
                            <span>{{ $trustProfile['icon'] }}</span>
                            {{ $trustProfile['label'] }}
                        </span>
                    </div>
                    <h1 class="mt-3 truncate text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ $user->name }}</h1>
                    <p class="mt-1 text-sm text-slate-500">ID {{ $user->id }} · создан {{ $user->created_at?->format('d.m.Y H:i') }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.users.index') }}"
                   class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                    <i class="ri-arrow-left-line"></i>
                    Назад
                </a>
                @if($user->role !== 'admin')
                    <form method="POST" action="{{ route('admin.chats.support.start', $user) }}">
                        @csrf
                        <button class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                            <i class="ri-customer-service-2-line"></i>
                            Support-чат
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.users.edit', $user) }}"
                   class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700">
                    <i class="ri-edit-2-line"></i>
                    Редактировать
                </a>
            </div>
        </div>
    </section>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-slate-500">
                <span>Заказы</span>
                <i class="ri-shopping-bag-3-line text-indigo-500"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($user->orders_count, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-slate-400">Как покупатель</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-slate-500">
                <span>Товары</span>
                <i class="ri-box-3-line text-indigo-500"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($user->products_count, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-slate-400">Если это продавец</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-slate-500">
                <span>Диалоги</span>
                <i class="ri-chat-3-line text-indigo-500"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($conversationsCount, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-slate-400">Buyer/Seller участия</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm text-slate-500">
                <span>Подписки</span>
                <i class="ri-user-follow-line text-indigo-500"></i>
            </div>
            <div class="mt-2 text-2xl font-bold text-slate-950">{{ number_format($user->followed_shops_count, 0, ',', ' ') }}</div>
            <p class="mt-1 text-xs text-slate-400">Магазины в подписках</p>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_380px]">
        <section class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold text-slate-950">Контакты и безопасность</h2>
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Профиль</span>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="flex items-center gap-2 text-sm font-semibold text-slate-500">
                            <i class="ri-mail-line"></i>
                            Email
                        </div>
                        <div class="mt-2 break-all text-sm font-bold text-slate-900">{{ $user->email }}</div>
                        <div class="mt-2 text-xs {{ $user->email_verified_at ? 'text-emerald-600' : 'text-amber-600' }}">
                            {{ $user->email_verified_at ? 'Подтверждён ' . $user->email_verified_at->format('d.m.Y H:i') : 'Не подтверждён' }}
                        </div>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="flex items-center gap-2 text-sm font-semibold text-slate-500">
                            <i class="ri-smartphone-line"></i>
                            Телефон
                        </div>
                        <div class="mt-2 text-sm font-bold text-slate-900">{{ $user->phone ?: 'Не указан' }}</div>
                        <div class="mt-2 text-xs {{ $user->phone_verified_at ? 'text-indigo-600' : 'text-slate-400' }}">
                            {{ $user->phone_verified_at ? 'Подтверждён ' . $user->phone_verified_at->format('d.m.Y H:i') : 'Без проверки' }}
                        </div>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="flex items-center gap-2 text-sm font-semibold text-slate-500">
                            <i class="ri-lock-password-line"></i>
                            Пароль
                        </div>
                        <div class="mt-2 text-sm font-bold text-slate-900">{{ $user->hasLocalPassword() ? 'Локальный пароль установлен' : 'Локального пароля нет' }}</div>
                        <div class="mt-2 text-xs text-slate-400">{{ $user->password_set_at ? 'Установлен ' . $user->password_set_at->format('d.m.Y H:i') : 'Вероятно social-only вход' }}</div>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="flex items-center gap-2 text-sm font-semibold text-slate-500">
                            <i class="ri-login-circle-line"></i>
                            Провайдер
                        </div>
                        <div class="mt-2 text-sm font-bold text-slate-900">{{ $user->provider ?: 'Локальный аккаунт' }}</div>
                        <div class="mt-2 text-xs text-slate-400">Обновлён {{ $user->updated_at?->diffForHumans() }}</div>
                    </div>
                </div>
            </div>

            @if($user->role === 'seller')
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-slate-950">Магазин продавца</h2>
                        @if($user->shop?->slug)
                            <a href="{{ route('seller.show', $user->shop->slug) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                                Открыть витрину
                            </a>
                        @endif
                    </div>

                    @if($user->shop)
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-bold text-slate-950">{{ $user->shop->name }}</h3>
                                    <p class="mt-1 text-sm text-slate-600">{{ $user->shop->city ?: 'Город не указан' }}</p>
                                </div>
                                <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-indigo-700">Shop ID {{ $user->shop->id }}</span>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $user->shop->description ?: 'Описание магазина пока не заполнено.' }}</p>
                        </div>
                    @else
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                            У продавца ещё нет магазина. Это стоит проверить, потому что товары и витрина без магазина работать полноценно не будут.
                        </div>
                    @endif
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold text-slate-950">Последние заказы</h2>
                    <a href="{{ route('admin.orders.index', ['q' => $user->email]) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">Искать в заказах</a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($recentOrders as $order)
                        <div class="py-3 first:pt-0 last:pb-0">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="font-semibold text-slate-900">#{{ $order->number }}</div>
                                    <div class="mt-1 text-xs text-slate-400">{{ $order->created_at?->format('d.m.Y H:i') }} · {{ $order->seller?->name ?? 'Продавец не указан' }}</div>
                                </div>
                                <div class="text-sm font-bold text-slate-950">{{ $order->formatted_total_price }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-500">Заказов пока нет.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <aside class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Уровень доверия</div>
                        <h2 class="mt-1 flex items-center gap-2 text-xl font-bold text-slate-950">
                            <span>{{ $trustProfile['icon'] }}</span>
                            {{ $trustProfile['label'] }}
                        </h2>
                    </div>
                    <span class="rounded-full border px-3 py-1 text-sm font-extrabold {{ $trustProfile['class'] }}">
                        {{ $trustProfile['score'] }}%
                    </span>
                </div>

                <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full {{ $trustProfile['bar'] }}" style="width: {{ $trustProfile['score'] }}%"></div>
                </div>

                <p class="mt-3 text-sm leading-6 text-slate-500">{{ $trustProfile['description'] }}</p>

                <div class="mt-4 grid gap-2">
                    @foreach($trustProfile['signals'] as $signal)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2 text-sm">
                            <span class="font-semibold text-slate-600">{{ $signal['label'] }}</span>
                            <span class="inline-flex items-center gap-1 font-bold {{ $signal['active'] ? 'text-emerald-700' : 'text-slate-400' }}">
                                <i class="{{ $signal['active'] ? 'ri-check-line' : 'ri-time-line' }}"></i>
                                {{ $signal['value'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            @if($sellerPlanProfile)
                <div class="rounded-2xl border p-4 shadow-sm sm:p-5 {{ $sellerPlanProfile['class'] }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wide opacity-70">Статус продавца</div>
                            <h2 class="mt-1 flex items-center gap-2 text-xl font-bold">
                                <i class="ri-vip-crown-line"></i>
                                {{ $sellerPlanProfile['label'] }}
                            </h2>
                        </div>
                        <span class="rounded-full bg-white/70 px-3 py-1 text-sm font-extrabold">
                            {{ $sellerPlanProfile['used'] }} / {{ $sellerPlanProfile['limit_label'] }}
                        </span>
                    </div>

                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/70">
                        <div class="h-full rounded-full bg-indigo-500" style="width: {{ $sellerPlanProfile['percent'] }}%"></div>
                    </div>

                    <p class="mt-3 text-sm leading-6 opacity-80">{{ $sellerPlanProfile['description'] }}</p>
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-lg font-bold text-slate-950">Быстрые действия</h2>
                <div class="mt-4 grid gap-2">
                    <a href="{{ route('admin.users.edit', $user) }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                        <span class="flex items-center gap-2"><i class="ri-edit-2-line text-lg"></i> Редактировать профиль</span>
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                    <a href="{{ route('users.public.show', $user) }}" class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                        <span class="flex items-center gap-2"><i class="ri-user-3-line text-lg"></i> Публичная карточка</span>
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                    @if($user->role !== 'admin')
                        <form method="POST" action="{{ route('admin.chats.support.start', $user) }}">
                            @csrf
                            <button class="flex w-full items-center justify-between rounded-xl border border-slate-200 px-3 py-3 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                <span class="flex items-center gap-2"><i class="ri-customer-service-2-line text-lg"></i> Открыть support-чат</span>
                                <i class="ri-arrow-right-s-line"></i>
                            </button>
                        </form>
                    @endif
                    @unless($user->is(auth()->user()))
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    onclick="return confirm('Удалить пользователя {{ $user->name }}?')"
                                    class="flex w-full items-center justify-between rounded-xl border border-rose-200 bg-rose-50 px-3 py-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                                <span class="flex items-center gap-2"><i class="ri-delete-bin-line text-lg"></i> Удалить пользователя</span>
                                <i class="ri-arrow-right-s-line"></i>
                            </button>
                        </form>
                    @endunless
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="text-lg font-bold text-slate-950">Последние товары</h2>
                <div class="mt-4 space-y-3">
                    @forelse($recentProducts as $product)
                        <a href="{{ route('product.show', $product->slug ?? $product->id) }}" class="flex items-center gap-3 rounded-xl border border-slate-200 p-3 transition hover:border-indigo-200">
                            <img src="{{ $product->image ? asset('storage/'.$product->image) : asset('images/default-product.png') }}" class="h-12 w-12 rounded-lg object-cover" alt="">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-900">{{ $product->title }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ number_format($product->price, 2, ',', ' ') }} {{ $product->currency_base ?? $product->currency ?? '' }}</div>
                            </div>
                        </a>
                    @empty
                        <div class="rounded-xl bg-slate-50 p-5 text-center text-sm text-slate-500">Товаров пока нет.</div>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
