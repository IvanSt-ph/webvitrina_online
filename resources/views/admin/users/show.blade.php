@extends('admin.layout')

@section('title', 'Пользователь - ' . $user->name)

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
    $primaryOrdersCount = $user->isSeller() ? $user->sales_orders_count : $user->orders_count;
    $primaryOrdersLabel = $user->isSeller() ? 'Заказы магазина' : 'Покупки';
    $recentOrdersLabel = $user->isSeller() ? 'Последние заказы магазина' : 'Последние покупки';
    $orderPartyLabel = $user->isSeller() ? 'Покупатель' : 'Продавец';
    $orderStatusClasses = [
        \App\Models\Order::STATUS_PENDING => 'bg-amber-50 text-amber-700',
        \App\Models\Order::STATUS_PROCESSING => 'bg-sky-50 text-sky-700',
        \App\Models\Order::STATUS_PAID => 'bg-emerald-50 text-emerald-700',
        \App\Models\Order::STATUS_SHIPPED => 'bg-blue-50 text-blue-700',
        \App\Models\Order::STATUS_DELIVERED => 'bg-green-50 text-green-700',
        \App\Models\Order::STATUS_COMPLETED => 'bg-slate-100 text-slate-700',
        \App\Models\Order::STATUS_CANCELED => 'bg-rose-50 text-rose-700',
    ];
    $attentionCount = (int) ! $user->email_verified_at
        + (int) ($user->isSeller() && ! $user->shop)
        + (int) (bool) $pendingPlanRequest
        + (int) ($user->isSeller() && $commerceSummary['out_of_stock_products'] > 0)
        + (int) ($sellerPlanProfile && $sellerPlanProfile['near_limit'] && ! $pendingPlanRequest);
    $profileTabs = [
        'overview' => ['Обзор', 'ri-layout-2-line'],
        'orders' => ['Заказы', 'ri-shopping-bag-3-line'],
    ];
    if ($user->isSeller()) {
        $profileTabs['products'] = ['Товары', 'ri-box-3-line'];
    }
    $profileTabs['history'] = ['Журнал', 'ri-history-line'];
    $followersCount = (int) ($user->shop?->followers_count ?? 0);
    $followersWord = ($followersCount % 10 === 1 && $followersCount % 100 !== 11)
        ? 'подписчик'
        : (in_array($followersCount % 10, [2, 3, 4], true) && ! in_array($followersCount % 100, [12, 13, 14], true)
            ? 'подписчика'
            : 'подписчиков');
@endphp

<div x-data="{ tab: 'overview' }" class="space-y-4">
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 p-4 sm:p-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <img src="{{ $user->avatar_url }}"
                     class="h-16 w-16 shrink-0 rounded-2xl border border-slate-200 object-cover"
                     alt="Аватар {{ $user->name }}">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-bold {{ $roleClass }}">
                            <i class="{{ $roleIcon }}"></i>
                            {{ $roleLabel }}
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-bold {{ $trustProfile['class'] }}">
                            <span>{{ $trustProfile['icon'] }}</span>
                            {{ $trustProfile['short_label'] }} {{ $trustProfile['score'] }}%
                        </span>
                        @if($attentionCount)
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">
                                <i class="ri-alarm-warning-line"></i>
                                Проверить: {{ $attentionCount }}
                            </span>
                        @endif
                    </div>
                    <h1 class="mt-2 truncate text-xl font-bold text-slate-950 sm:text-2xl">{{ $user->name }}</h1>
                    <p class="mt-0.5 truncate text-xs text-slate-500">
                        ID {{ $user->id }} · {{ $user->email }} · зарегистрирован {{ $user->created_at?->format('d.m.Y') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.users.index') }}"
                   class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-200 px-3 text-slate-600 transition hover:border-indigo-200 hover:text-indigo-700"
                   title="Вернуться к пользователям">
                    <i class="ri-arrow-left-line"></i>
                </a>
                @if($user->role !== 'admin')
                    <form method="POST" action="{{ route('admin.chats.support.start', $user) }}">
                        @csrf
                        <button class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-indigo-100 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                            <i class="ri-customer-service-2-line"></i>
                            Support-чат
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.users.edit', $user) }}"
                   class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-700">
                    <i class="ri-edit-2-line"></i>
                    Редактировать
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 border-t border-slate-100 md:grid-cols-4">
            <button type="button" @click="tab = 'orders'" class="border-b border-r border-slate-100 px-4 py-3 text-left transition hover:bg-slate-50 md:border-b-0">
                <div class="text-xs text-slate-400">{{ $primaryOrdersLabel }}</div>
                <div class="mt-1 text-xl font-bold text-slate-950">{{ number_format($primaryOrdersCount, 0, ',', ' ') }}</div>
            </button>
            @if($user->isSeller())
                <button type="button" @click="tab = 'products'" class="border-b border-slate-100 px-4 py-3 text-left transition hover:bg-slate-50 md:border-b-0 md:border-r">
                    <div class="text-xs text-slate-400">Товары</div>
                    <div class="mt-1 text-xl font-bold text-slate-950">{{ number_format($user->products_count, 0, ',', ' ') }}</div>
                </button>
            @else
                <div class="border-b border-slate-100 px-4 py-3 md:border-b-0 md:border-r">
                    <div class="text-xs text-slate-400">Подписки</div>
                    <div class="mt-1 text-xl font-bold text-slate-950">{{ number_format($user->followed_shops_count, 0, ',', ' ') }}</div>
                </div>
            @endif
            <a href="{{ route('admin.chats.index', ['q' => $user->email]) }}" class="border-r border-slate-100 px-4 py-3 transition hover:bg-slate-50">
                <div class="text-xs text-slate-400">Диалоги</div>
                <div class="mt-1 text-xl font-bold text-slate-950">{{ number_format($conversationsCount, 0, ',', ' ') }}</div>
            </a>
            <button type="button" @click="tab = 'history'" class="px-4 py-3 text-left transition hover:bg-slate-50">
                <div class="text-xs text-slate-400">Админ-действия</div>
                <div class="mt-1 text-xl font-bold text-slate-950">{{ number_format($recentAdminActivity->count(), 0, ',', ' ') }}</div>
            </button>
        </div>
    </section>

    @if($attentionCount)
        <section class="rounded-2xl border border-amber-200 bg-amber-50/70 p-3 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="flex shrink-0 items-center gap-2 text-sm font-bold text-amber-900">
                    <i class="ri-alarm-warning-line text-lg"></i>
                    Нужно проверить
                </div>
                <div class="flex flex-1 flex-wrap gap-2 text-xs font-semibold">
                    @unless($user->email_verified_at)
                        <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg border border-amber-200 bg-white/80 px-3 py-2 text-amber-800">
                            Email не подтверждён
                        </a>
                    @endunless
                    @if($user->isSeller() && ! $user->shop)
                        <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg border border-amber-200 bg-white/80 px-3 py-2 text-amber-800">
                            У продавца нет магазина
                        </a>
                    @endif
                    @if($pendingPlanRequest)
                        <a href="{{ route('admin.seller-plan-requests.index') }}" class="rounded-lg border border-amber-200 bg-white/80 px-3 py-2 text-amber-800">
                            Запрос: {{ $sellerPlanOptions[$pendingPlanRequest->current_plan]['label'] ?? $pendingPlanRequest->current_plan }}
                            -> {{ $sellerPlanOptions[$pendingPlanRequest->requested_plan]['label'] ?? $pendingPlanRequest->requested_plan }}
                        </a>
                    @endif
                    @if($user->isSeller() && $commerceSummary['out_of_stock_products'] > 0)
                        <a href="{{ route('admin.products.index', ['seller_id' => $user->id, 'status' => 'active', 'stock' => 'out']) }}" class="rounded-lg border border-amber-200 bg-white/80 px-3 py-2 text-amber-800">
                            Товары без остатка: {{ $commerceSummary['out_of_stock_products'] }}
                        </a>
                    @endif
                    @if($sellerPlanProfile && $sellerPlanProfile['near_limit'] && ! $pendingPlanRequest)
                        <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg border border-amber-200 bg-white/80 px-3 py-2 text-amber-800">
                            Близко к лимиту тарифа
                        </a>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_350px]">
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex gap-1 overflow-x-auto border-b border-slate-100 p-2">
                @foreach($profileTabs as $key => [$label, $icon])
                    <button type="button"
                            @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-500 hover:bg-slate-50'"
                            class="inline-flex h-10 shrink-0 items-center gap-2 rounded-xl px-4 text-sm font-semibold transition">
                        <i class="{{ $icon }}"></i>
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div x-show="tab === 'overview'" class="space-y-4 p-4 sm:p-5">
                <div>
                    <h2 class="text-base font-bold text-slate-950">Контакты и безопасность</h2>
                    <div class="mt-3 overflow-hidden rounded-xl border border-slate-100">
                        <div class="grid sm:grid-cols-2">
                            <div class="flex items-center gap-3 border-b border-slate-100 px-3 py-3 sm:border-r">
                                <i class="ri-mail-line text-lg text-slate-400"></i>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-800">{{ $user->email }}</div>
                                    <div class="text-xs {{ $user->email_verified_at ? 'text-emerald-700' : 'text-amber-700' }}">
                                        {{ $user->email_verified_at ? 'Email подтверждён' : 'Email не подтверждён' }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 border-b border-slate-100 px-3 py-3">
                                <i class="ri-smartphone-line text-lg text-slate-400"></i>
                                <div>
                                    <div class="text-sm font-semibold text-slate-800">{{ $user->phone ?: 'Телефон не указан' }}</div>
                                    <div class="text-xs {{ $user->phone_verified_at ? 'text-emerald-700' : 'text-slate-400' }}">
                                        {{ $user->phone_verified_at ? 'Телефон подтверждён' : 'Без проверки' }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 px-3 py-3 sm:border-r">
                                <i class="ri-lock-password-line text-lg text-slate-400"></i>
                                <div>
                                    <div class="text-sm font-semibold text-slate-800">{{ $user->hasLocalPassword() ? 'Пароль установлен' : 'Без локального пароля' }}</div>
                                    <div class="text-xs text-slate-400">{{ $user->password_set_at?->format('d.m.Y H:i') ?: 'Social-only вход' }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 border-t border-slate-100 px-3 py-3 sm:border-t-0">
                                <i class="ri-login-circle-line text-lg text-slate-400"></i>
                                <div>
                                    <div class="text-sm font-semibold text-slate-800">{{ $user->provider ?: 'Локальный аккаунт' }}</div>
                                    <div class="text-xs text-slate-400">Изменён {{ $user->updated_at?->diffForHumans() }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($user->isSeller())
                    <div>
                        <h2 class="text-base font-bold text-slate-950">Магазин продавца</h2>
                        <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200">
                            @if($user->shop)
                                <div class="relative min-h-[112px] overflow-hidden bg-slate-900">
                                    <img src="{{ $user->shop->banner_url }}"
                                         class="absolute inset-0 h-full w-full object-cover opacity-45"
                                         alt="">
                                    <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-950/65 to-indigo-950/35"></div>
                                    <div class="relative flex flex-col justify-between gap-4 p-4 sm:flex-row sm:items-center">
                                        <div class="min-w-0 text-white">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="truncate text-lg font-bold">{{ $user->shop->name }}</h3>
                                                <span class="rounded-full border border-white/20 bg-white/10 px-2 py-0.5 text-[11px] font-semibold text-white/90">
                                                    {{ $sellerPlanProfile['label'] }}
                                                </span>
                                            </div>
                                            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-white/75">
                                                <span class="inline-flex items-center gap-1.5">
                                                    <i class="ri-map-pin-line"></i>
                                                    {{ $user->shop->city ?: 'Локация не указана' }}
                                                </span>
                                                <span class="inline-flex items-center gap-1.5">
                                                    <i class="ri-user-follow-line"></i>
                                                    {{ $followersCount }} {{ $followersWord }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2">
                                            @if($user->shop?->slug)
                                                <a href="{{ route('seller.show', $user->shop->slug) }}"
                                                   class="inline-flex h-10 items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-3 text-xs font-semibold text-white backdrop-blur transition hover:bg-white/20">
                                                    <i class="ri-store-2-line"></i>
                                                    Открыть витрину
                                                </a>
                                            @endif
                                            <a href="{{ route('admin.products.index', ['seller_id' => $user->id]) }}"
                                               class="inline-flex h-10 items-center gap-2 rounded-xl bg-white px-3 text-xs font-bold text-indigo-700 transition hover:bg-indigo-50">
                                                <i class="ri-box-3-line"></i>
                                                Товары
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="border-b border-amber-100 bg-amber-50 p-4 text-sm text-amber-800">
                                    У продавца ещё нет созданной витрины.
                                </div>
                            @endif

                            <div class="grid gap-px bg-slate-100 md:grid-cols-2">
                                <div class="bg-white p-4">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <div class="inline-flex items-center gap-2 text-sm font-bold text-slate-800">
                                            <i class="ri-store-3-line text-indigo-500"></i>
                                            Каталог
                                        </div>
                                        <button type="button" @click="tab = 'products'" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Открыть список</button>
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <div class="text-3xl font-bold text-slate-950">{{ $commerceSummary['active_products'] }}</div>
                                        <div class="pb-1 text-xs text-slate-500">товаров на витрине</div>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <a href="{{ route('admin.products.index', ['seller_id' => $user->id, 'status' => 'draft']) }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-indigo-50 hover:text-indigo-700">
                                            <i class="ri-draft-line"></i>
                                            {{ $commerceSummary['draft_products'] }} черновиков
                                        </a>
                                        <a href="{{ route('admin.products.index', ['seller_id' => $user->id, 'status' => 'active', 'stock' => 'out']) }}"
                                           class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold {{ $commerceSummary['out_of_stock_products'] ? 'bg-amber-50 text-amber-800 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700' }}">
                                            <i class="{{ $commerceSummary['out_of_stock_products'] ? 'ri-alarm-warning-line' : 'ri-checkbox-circle-line' }}"></i>
                                            {{ $commerceSummary['out_of_stock_products'] ? $commerceSummary['out_of_stock_products'] . ' без остатка' : 'Остатки в порядке' }}
                                        </a>
                                    </div>
                                </div>
                                <div class="bg-white p-4">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <div class="inline-flex items-center gap-2 text-sm font-bold text-slate-800">
                                            <i class="ri-shopping-bag-3-line text-indigo-500"></i>
                                            Заказы магазина
                                        </div>
                                        <button type="button" @click="tab = 'orders'" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">Смотреть</button>
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <div class="text-3xl font-bold {{ $commerceSummary['needs_action_orders'] ? 'text-amber-700' : 'text-slate-950' }}">{{ $commerceSummary['needs_action_orders'] }}</div>
                                        <div class="pb-1 text-xs text-slate-500">ожидают решения</div>
                                    </div>
                                    <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                                        <div class="rounded-lg bg-slate-50 px-2 py-1.5">
                                            <div class="text-sm font-bold text-slate-800">{{ $commerceSummary['fulfillment_orders'] }}</div>
                                            <div class="text-[10px] text-slate-500">В работе</div>
                                        </div>
                                        <div class="rounded-lg bg-emerald-50 px-2 py-1.5">
                                            <div class="text-sm font-bold text-emerald-700">{{ $commerceSummary['completed_orders'] }}</div>
                                            <div class="text-[10px] text-emerald-700">Завершены</div>
                                        </div>
                                        <div class="rounded-lg {{ $commerceSummary['canceled_orders'] ? 'bg-rose-50' : 'bg-slate-50' }} px-2 py-1.5">
                                            <div class="text-sm font-bold {{ $commerceSummary['canceled_orders'] ? 'text-rose-700' : 'text-slate-800' }}">{{ $commerceSummary['canceled_orders'] }}</div>
                                            <div class="text-[10px] {{ $commerceSummary['canceled_orders'] ? 'text-rose-700' : 'text-slate-500' }}">Отменены</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-3 divide-x divide-slate-100 rounded-xl bg-slate-50 py-3 text-center">
                        <div>
                            <div class="text-lg font-bold text-slate-900">{{ $commerceSummary['active_orders'] }}</div>
                            <div class="text-xs text-slate-400">Активные</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-slate-900">{{ $commerceSummary['completed_orders'] }}</div>
                            <div class="text-xs text-slate-400">Завершены</div>
                        </div>
                        <div>
                            <div class="text-lg font-bold text-slate-900">{{ $commerceSummary['canceled_orders'] }}</div>
                            <div class="text-xs text-slate-400">Отменены</div>
                        </div>
                    </div>
                @endif
            </div>

            <div x-cloak x-show="tab === 'orders'" class="p-4 sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-950">{{ $recentOrdersLabel }}</h2>
                        <p class="text-xs text-slate-500">Активные {{ $commerceSummary['active_orders'] }} · завершены {{ $commerceSummary['completed_orders'] }} · отменены {{ $commerceSummary['canceled_orders'] }}</p>
                    </div>
                    <a href="{{ route('admin.orders.index', ['q' => $user->email]) }}" class="text-xs font-semibold text-indigo-600">Все заказы</a>
                </div>
                <div class="divide-y divide-slate-100 rounded-xl border border-slate-100 px-3">
                    @forelse($recentOrders as $order)
                        @php $party = $user->isSeller() ? $order->user : $order->seller; @endphp
                        <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="truncate text-sm font-semibold text-slate-900">#{{ $order->number }}</span>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $orderStatusClasses[$order->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $order->status_ru }}</span>
                                </div>
                                <p class="mt-1 truncate text-xs text-slate-400">{{ $orderPartyLabel }}: {{ $party?->name ?: 'Не указан' }} · {{ $order->created_at?->format('d.m.Y H:i') }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-bold text-slate-800">{{ $order->formatted_total_price }}</span>
                        </div>
                    @empty
                        <div class="py-10 text-center text-sm text-slate-500">Заказов пока нет.</div>
                    @endforelse
                </div>
            </div>

            @if($user->isSeller())
            <div x-cloak x-show="tab === 'products'" class="p-4 sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-base font-bold text-slate-950">Последние товары</h2>
                    @if($user->isSeller())
                        <a href="{{ route('admin.products.index', ['seller_id' => $user->id]) }}" class="text-xs font-semibold text-indigo-600">Каталог продавца</a>
                    @endif
                </div>
                <div class="divide-y divide-slate-100 rounded-xl border border-slate-100 px-3">
                    @forelse($recentProducts as $product)
                        <a href="{{ route('admin.products.edit', $product) }}" class="flex items-center gap-3 py-3 transition hover:text-indigo-700">
                            <img src="{{ $product->image ? asset('storage/' . $product->image) : asset('images/default-product.png') }}" class="h-11 w-11 rounded-lg border border-slate-100 object-cover" alt="">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-semibold">{{ $product->title }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ number_format((float) $product->price, 2, ',', ' ') }} {{ $product->currency_base ?? '' }} · {{ $product->stock }} шт.</div>
                            </div>
                            <i class="ri-edit-2-line text-slate-300"></i>
                        </a>
                    @empty
                        <div class="py-10 text-center text-sm text-slate-500">Товаров пока нет.</div>
                    @endforelse
                </div>
            </div>
            @endif

            <div x-cloak x-show="tab === 'history'" class="p-4 sm:p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-950">Журнал действий по пользователю</h2>
                        <p class="text-xs text-slate-500">Изменения профиля и решения по тарифу.</p>
                    </div>
                    <a href="{{ route('admin.activity.index') }}" class="text-xs font-semibold text-indigo-600">Общий журнал</a>
                </div>
                <div class="divide-y divide-slate-100 rounded-xl border border-slate-100 px-3">
                    @forelse($recentAdminActivity as $activity)
                        <div class="flex items-start justify-between gap-3 py-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">{{ $activity->description ?: $activity->action }}</div>
                                <div class="mt-1 font-mono text-xs text-indigo-600">{{ $activity->action }}</div>
                            </div>
                            <div class="shrink-0 text-right text-xs text-slate-400">
                                <div>{{ $activity->admin?->name ?? 'Система' }}</div>
                                <div class="mt-1">{{ $activity->created_at?->format('d.m.Y H:i') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="py-10 text-center text-sm text-slate-500">Действий по пользователю пока нет.</div>
                    @endforelse
                </div>
            </div>
        </section>

        <aside class="space-y-4">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-bold uppercase text-slate-400">Уровень доверия</div>
                        <div class="mt-1 flex items-center gap-2 text-base font-bold text-slate-950">
                            <span>{{ $trustProfile['icon'] }}</span>
                            {{ $trustProfile['label'] }}
                        </div>
                    </div>
                    <span class="rounded-full border px-2.5 py-1 text-sm font-extrabold {{ $trustProfile['class'] }}">{{ $trustProfile['score'] }}%</span>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full {{ $trustProfile['bar'] }}" style="width: {{ $trustProfile['score'] }}%"></div>
                </div>
                <div class="mt-3 space-y-1.5">
                    @foreach($trustProfile['signals'] as $signal)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">{{ $signal['label'] }}</span>
                            <span class="inline-flex items-center gap-1 font-semibold {{ $signal['active'] ? 'text-emerald-700' : 'text-slate-400' }}">
                                <i class="{{ $signal['active'] ? 'ri-check-line' : 'ri-time-line' }}"></i>
                                {{ $signal['value'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </section>

            @if($sellerPlanProfile)
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-bold uppercase text-slate-400">Тариф продавца</div>
                            <div class="mt-1 font-bold text-slate-950">{{ $sellerPlanProfile['label'] }}</div>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-extrabold {{ $sellerPlanProfile['class'] }}">
                            {{ $sellerPlanProfile['used'] }} / {{ $sellerPlanProfile['limit_label'] }}
                        </span>
                    </div>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-indigo-500" style="width: {{ $sellerPlanProfile['percent'] }}%"></div>
                    </div>
                    <p class="mt-2 text-xs leading-5 text-slate-500">{{ $sellerPlanProfile['description'] }}</p>
                    @if($pendingPlanRequest)
                        <a href="{{ route('admin.seller-plan-requests.index') }}" class="mt-3 flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">
                            <span>Запрос: {{ $sellerPlanOptions[$pendingPlanRequest->current_plan]['label'] ?? $pendingPlanRequest->current_plan }} -> {{ $sellerPlanOptions[$pendingPlanRequest->requested_plan]['label'] ?? $pendingPlanRequest->requested_plan }}</span>
                            <span>Рассмотреть</span>
                        </a>
                    @endif
                </section>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-bold text-slate-950">Быстрые переходы</h2>
                <div class="mt-3 grid gap-2 text-sm font-semibold">
                    @if($user->isSeller())
                        <a href="{{ route('admin.products.index', ['seller_id' => $user->id]) }}" class="flex h-10 items-center justify-between rounded-lg bg-slate-50 px-3 text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700">
                            <span class="flex items-center gap-2"><i class="ri-box-3-line"></i> Все товары</span>
                            <span>{{ $user->products_count }}</span>
                        </a>
                    @endif
                    <button type="button" @click="tab = 'orders'" class="flex h-10 items-center justify-between rounded-lg bg-slate-50 px-3 text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700">
                        <span class="flex items-center gap-2"><i class="ri-shopping-bag-3-line"></i> Заказы</span>
                        <span>{{ $primaryOrdersCount }}</span>
                    </button>
                    <a href="{{ route('admin.chats.index', ['q' => $user->email]) }}" class="flex h-10 items-center justify-between rounded-lg bg-slate-50 px-3 text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700">
                        <span class="flex items-center gap-2"><i class="ri-chat-3-line"></i> Диалоги</span>
                        <span>{{ $conversationsCount }}</span>
                    </a>
                    <a href="{{ route('users.public.show', $user) }}" class="flex h-10 items-center justify-between rounded-lg bg-slate-50 px-3 text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700">
                        <span class="flex items-center gap-2"><i class="ri-user-3-line"></i> Публичная карточка</span>
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                    @unless($user->is(auth()->user()))
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Удалить этого пользователя?')" class="flex h-10 w-full items-center justify-between rounded-lg bg-rose-50 px-3 text-rose-700 transition hover:bg-rose-100">
                                <span class="flex items-center gap-2"><i class="ri-delete-bin-line"></i> Удалить</span>
                                <i class="ri-arrow-right-s-line"></i>
                            </button>
                        </form>
                    @endunless
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
