<x-buyer-layout title="Личный кабинет">

@php
    $cartCount = \App\Models\CartItem::where('user_id', $user->id)->sum('qty');
    $favCount = $user->favorites()->count();
    $ordersCount = \App\Models\Order::where('user_id', $user->id)->count();
    $subscriptionsCount = $followedShopsCount ?? 0;
    $recs = is_array($recommendations ?? null) ? $recommendations : [];
    $actionCards = [
        [
            'title' => 'Новые сообщения',
            'count' => $unreadMessagesCount ?? 0,
            'href' => route('chats.index'),
            'icon' => 'ri-chat-3-line',
            'description' => 'Ответы продавцов и поддержки',
        ],
        [
            'title' => 'Подтвердить получение',
            'count' => $confirmationOrdersCount ?? 0,
            'href' => route('orders.index', ['tab' => 'action']),
            'icon' => 'ri-checkbox-circle-line',
            'description' => 'Заказы уже в доставке',
        ],
        [
            'title' => 'Оставить отзыв',
            'count' => $reviewableOrdersCount ?? 0,
            'href' => route('orders.index', ['tab' => 'action']),
            'icon' => 'ri-star-line',
            'description' => 'Полученные покупки без оценки',
        ],
        [
            'title' => 'Ответы поддержки',
            'count' => $supportUnreadCount ?? 0,
            'href' => route('support'),
            'icon' => 'ri-customer-service-2-line',
            'description' => 'Непрочитанные сообщения',
        ],
    ];

    $quickLinks = [
        [
            'title' => 'Заказы',
            'subtitle' => 'История и статусы',
            'icon' => 'ri-shopping-bag-3-line',
            'href' => route('orders.index'),
            'tone' => 'bg-indigo-50 text-indigo-600',
        ],
        [
            'title' => 'Избранное',
            'subtitle' => 'Сохранённые товары',
            'icon' => 'ri-heart-3-line',
            'href' => route('favorites.index'),
            'tone' => 'bg-indigo-50 text-indigo-600',
        ],
        [
            'title' => 'Корзина',
            'subtitle' => 'Перейти к оплате',
            'icon' => 'ri-shopping-cart-2-line',
            'href' => route('cart.index'),
            'tone' => 'bg-indigo-50 text-indigo-600',
        ],
        [
            'title' => 'Адреса',
            'subtitle' => 'Доставка заказов',
            'icon' => 'ri-map-pin-line',
            'href' => route('addresses.index'),
            'tone' => 'bg-indigo-50 text-indigo-600',
        ],
        [
            'title' => 'Подписки',
            'subtitle' => 'Любимые магазины',
            'icon' => 'ri-user-follow-line',
            'href' => route('subscriptions.index'),
            'tone' => 'bg-indigo-50 text-indigo-600',
        ],
    ];

    $settingsLinks = [
        ['title' => 'Личные данные', 'icon' => 'ri-user-settings-line', 'href' => route('buyer.profile')],
        ['title' => 'Мои отзывы', 'icon' => 'ri-star-line', 'href' => route('reviews.index')],
        ['title' => 'Служба поддержки', 'icon' => 'ri-customer-service-2-line', 'href' => route('support')],
        ['title' => 'Мои подписки', 'icon' => 'ri-user-follow-line', 'href' => route('subscriptions.index')],
    ];
@endphp

<div class="max-w-8xl mx-auto px-3 sm:px-6 py-4 sm:py-8 pb-24 md:pb-8 space-y-6 sm:space-y-8">

    <section class="bg-white border border-gray-100 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">
        <div class="p-4 sm:p-6 lg:p-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div class="flex items-center gap-4 min-w-0">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100">
                    @if($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar))
                        <img src="{{ $user->avatar_url }}" class="object-cover w-full h-full" alt="{{ $user->name }}" loading="lazy" decoding="async">
                    @else
                        <span class="text-2xl sm:text-3xl font-bold">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    @endif
                </div>

                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold mb-2">
                        <i class="ri-shield-check-line"></i>
                        Покупатель
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-gray-900 truncate">
                        {{ $user->name }}
                    </h1>
                    <p class="text-sm text-gray-500 mt-1 truncate">{{ $user->email }}</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-2">
                <x-action-button as="a" href="{{ route('home') }}">
                    <i class="ri-store-2-line"></i>
                    К покупкам
                </x-action-button>

                <x-secondary-action as="a" href="{{ route('buyer.profile') }}">
                    <i class="ri-edit-line"></i>
                    Профиль
                </x-secondary-action>
            </div>
        </div>

        <div class="grid grid-cols-2 border-t border-gray-100 bg-gray-50/60 sm:grid-cols-4">
            <a href="{{ route('orders.index') }}" class="p-4 sm:p-5 hover:bg-white transition">
                <div class="text-xs text-gray-500">Заказы</div>
                <div class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">{{ $ordersCount }}</div>
            </a>
            <a href="{{ route('favorites.index') }}" class="p-4 sm:p-5 border-x border-gray-100 hover:bg-white transition">
                <div class="text-xs text-gray-500">Избранное</div>
                <div class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">{{ $favCount }}</div>
            </a>
            <a href="{{ route('cart.index') }}" class="p-4 sm:p-5 hover:bg-white transition">
                <div class="text-xs text-gray-500">В корзине</div>
                <div class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">{{ $cartCount }}</div>
            </a>
            <a href="{{ route('subscriptions.index') }}" class="p-4 sm:p-5 border-t border-gray-100 hover:bg-white transition sm:border-l sm:border-t-0">
                <div class="text-xs text-gray-500">Подписки</div>
                <div class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">{{ $subscriptionsCount }}</div>
            </a>
        </div>
    </section>

    <section class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 shadow-sm sm:rounded-2xl sm:p-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Требует внимания</h2>
                <p class="mt-1 text-xs text-gray-500">Ваши ближайшие действия и новые ответы</p>
            </div>
            <a href="{{ route('orders.index', ['tab' => 'action']) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Все действия</a>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($actionCards as $card)
                <a href="{{ $card['href'] }}" class="rounded-xl border border-white bg-white p-3 transition hover:border-indigo-100 hover:shadow-sm">
                    <div class="flex items-center justify-between gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                            <i class="{{ $card['icon'] }} text-lg"></i>
                        </span>
                        <span class="text-xl font-bold {{ $card['count'] > 0 ? 'text-indigo-700' : 'text-gray-400' }}">{{ $card['count'] }}</span>
                    </div>
                    <div class="mt-3 text-sm font-semibold text-gray-900">{{ $card['title'] }}</div>
                    <div class="mt-1 text-xs text-gray-500">{{ $card['description'] }}</div>
                </a>
            @endforeach
        </div>
    </section>

    <section class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
        @foreach($quickLinks as $link)
            <a href="{{ $link['href'] }}" class="group bg-white border border-gray-100 rounded-xl sm:rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-indigo-100 transition">
                <div class="flex items-start justify-between gap-3">
                    <div class="w-11 h-11 rounded-xl {{ $link['tone'] }} flex items-center justify-center">
                        <i class="{{ $link['icon'] }} text-xl"></i>
                    </div>
                    <i class="ri-arrow-right-up-line text-gray-300 group-hover:text-indigo-500 transition"></i>
                </div>
                <div class="mt-4 font-semibold text-gray-900">{{ $link['title'] }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ $link['subtitle'] }}</div>
            </a>
        @endforeach
    </section>

    <div class="grid lg:grid-cols-[minmax(0,1fr)_360px] gap-6 items-start">
        <div class="space-y-6">
            <section class="bg-white border border-gray-100 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Последние заказы</h2>
                        <p class="text-xs text-gray-500 mt-1">Быстрый доступ к последним покупкам</p>
                    </div>
                    <x-secondary-action as="a" href="{{ route('orders.index') }}" size="sm">
                        Все
                        <i class="ri-arrow-right-s-line"></i>
                    </x-secondary-action>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse($latestOrders as $order)
                        <a href="{{ route('orders.show', $order) }}" class="block p-4 sm:p-5 hover:bg-gray-50 transition">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-semibold text-gray-900">Заказ {{ $order->number ?? '#'.$order->id }}</span>
                                        <x-status-badge :status="$order->status" />
                                    </div>
                                    @if($order->seller?->shop)
                                        <div class="mt-1 text-xs font-medium text-indigo-600">{{ $order->seller->shop->name }}</div>
                                    @endif
                                    <div class="text-xs text-gray-500 mt-1">{{ $order->created_at->format('d.m.Y · H:i') }}</div>
                                </div>

                                <div class="sm:text-right font-semibold text-gray-900">
                                    {{ number_format($order->total_price, 2, ',', ' ') }} {{ $order->currency ?? '₽' }}
                                </div>
                            </div>

                            <div class="mt-4 flex items-center gap-2 overflow-hidden">
                                @foreach($order->items->take(4) as $item)
                                    <div class="w-11 h-11 rounded-xl overflow-hidden bg-gray-50 border border-gray-100 shrink-0">
                                        @if($item->product?->image)
                                            <img src="{{ $item->product->image_thumb_url }}" class="w-full h-full object-cover" alt="{{ $item->product->title }}">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                                <i class="ri-image-line"></i>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                @if($order->items->count() > 4)
                                    <div class="w-11 h-11 rounded-xl bg-gray-100 text-gray-500 flex items-center justify-center text-xs font-semibold shrink-0">
                                        +{{ $order->items->count() - 4 }}
                                    </div>
                                @endif
                            </div>

                            @if($order->status === \App\Models\Order::STATUS_SHIPPED)
                                <div class="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                                    Получили товар? Подтвердите получение в заказе.
                                </div>
                            @endif
                        </a>
                    @empty
                        <x-empty-state
                            icon="ri-shopping-bag-3-line"
                            title="Заказов пока нет"
                            description="Когда вы оформите покупку, она появится в этом блоке."
                            class="border-0 shadow-none rounded-none"
                        >
                            <a href="{{ route('home') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                Перейти к товарам
                            </a>
                        </x-empty-state>
                    @endforelse
                </div>
            </section>

            <section class="bg-white border border-gray-100 rounded-xl sm:rounded-2xl shadow-sm p-4 sm:p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Мои подписки</h2>
                        <p class="text-xs text-gray-500 mt-1">Магазины, за которыми вы следите</p>
                    </div>
                    <a href="{{ route('subscriptions.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Все</a>
                </div>

                @if(($latestFollowedShops ?? collect())->isNotEmpty())
                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach($latestFollowedShops as $shop)
                            <a href="{{ route('seller.show', $shop->slug) }}" class="group rounded-xl border border-gray-100 p-3 transition hover:border-indigo-100 hover:shadow-md">
                                <div class="aspect-[5/3] overflow-hidden rounded-lg bg-gray-50">
                                    <img src="{{ $shop->banner_url }}" alt="{{ $shop->name }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                </div>
                                <div class="mt-3 min-w-0">
                                    <div class="truncate text-sm font-semibold text-gray-900 group-hover:text-indigo-700">{{ $shop->name }}</div>
                                    <div class="mt-1 flex items-center gap-2 text-xs text-gray-500">
                                        <i class="ri-user-follow-line text-gray-400"></i>
                                        {{ $shop->followers_count }} подписчиков
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-gray-200 p-6 text-center">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto mb-3">
                            <i class="ri-user-follow-line text-xl"></i>
                        </div>
                        <div class="text-sm font-semibold text-gray-900">Подписок пока нет</div>
                        <div class="text-xs text-gray-500 mt-1">Подпишитесь на магазин, чтобы быстро вернуться к нему позже.</div>
                    </div>
                @endif
            </section>

            <section class="bg-white border border-gray-100 rounded-xl sm:rounded-2xl shadow-sm p-4 sm:p-5">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Рекомендации для вас</h2>
                        <p class="text-xs text-gray-500 mt-1">Товары на основе ваших покупок</p>
                    </div>
                    <a href="{{ route('home') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">Каталог</a>
                </div>

                @if(count($recs))
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                        @foreach(array_slice($recs, 0, 8) as $item)
                            <a href="{{ $item['link'] ?? '#' }}" class="group block border border-gray-100 rounded-xl p-2 hover:shadow-md hover:border-indigo-100 transition">
                                <div class="aspect-square rounded-lg overflow-hidden bg-gray-50 flex items-center justify-center">
                                    @if(!empty($item['image']))
                                        <img src="{{ \App\Models\Product::storageThumbUrl($item['image']) }}" alt="{{ $item['title'] ?? 'Товар' }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    @else
                                        <i class="ri-image-2-line text-3xl text-gray-300"></i>
                                    @endif
                                </div>
                                <div class="mt-2 text-sm font-medium text-gray-900 line-clamp-2">{{ $item['title'] ?? 'Товар' }}</div>
                                <div class="mt-1 text-sm font-bold text-indigo-600">{{ number_format($item['price'] ?? 0, 0, ',', ' ') }} ₽</div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-gray-200 p-6 text-center">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto mb-3">
                            <i class="ri-sparkling-line text-xl"></i>
                        </div>
                        <div class="text-sm font-semibold text-gray-900">Рекомендации появятся после покупок</div>
                        <div class="text-xs text-gray-500 mt-1">Пока можно заглянуть в каталог.</div>
                    </div>
                @endif
            </section>
        </div>

        <aside class="space-y-6 lg:sticky lg:top-24">
            <section class="bg-white border border-gray-100 rounded-xl sm:rounded-2xl shadow-sm p-4 sm:p-5">
                <h2 class="text-lg font-semibold text-gray-900">Настройки и помощь</h2>
                <div class="mt-4 divide-y divide-gray-100">
                    @foreach($settingsLinks as $link)
                        <a href="{{ $link['href'] }}" class="flex items-center justify-between gap-3 py-3 hover:text-indigo-600 transition">
                            <span class="flex items-center gap-3 text-sm font-medium">
                                <i class="{{ $link['icon'] }} text-lg text-gray-400"></i>
                                {{ $link['title'] }}
                            </span>
                            <i class="ri-arrow-right-s-line text-gray-300"></i>
                        </a>
                    @endforeach
                </div>
            </section>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="w-full h-11 rounded-xl border border-rose-100 bg-rose-50 text-rose-600 text-sm font-semibold hover:bg-rose-100 transition">
                    <i class="ri-logout-box-r-line mr-1"></i>
                    Выйти
                </button>
            </form>
        </aside>
    </div>
</div>

@include('layouts.mobile-bottom-nav')

</x-buyer-layout>
