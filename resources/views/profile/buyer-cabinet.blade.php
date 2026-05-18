<x-buyer-layout title="Личный кабинет">

@php
    $cartCount = \App\Models\CartItem::where('user_id', $user->id)->sum('qty');
    $favCount = $user->favorites()->count();
    $ordersCount = \App\Models\Order::where('user_id', $user->id)->count();
    $recs = is_array($recommendations ?? null) ? $recommendations : [];

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
            'tone' => 'bg-rose-50 text-rose-500',
        ],
        [
            'title' => 'Корзина',
            'subtitle' => 'Перейти к оплате',
            'icon' => 'ri-shopping-cart-2-line',
            'href' => route('cart.index'),
            'tone' => 'bg-emerald-50 text-emerald-600',
        ],
        [
            'title' => 'Адреса',
            'subtitle' => 'Доставка заказов',
            'icon' => 'ri-map-pin-line',
            'href' => route('addresses.index'),
            'tone' => 'bg-sky-50 text-sky-600',
        ],
    ];

    $settingsLinks = [
        ['title' => 'Личные данные', 'icon' => 'ri-user-settings-line', 'href' => route('buyer.profile')],
        ['title' => 'Уведомления', 'icon' => 'ri-notification-3-line', 'href' => route('notifications.settings')],
        ['title' => 'Мои отзывы', 'icon' => 'ri-star-line', 'href' => route('reviews.index')],
        ['title' => 'Вопросы и ответы', 'icon' => 'ri-question-answer-line', 'href' => route('questions.index')],
        ['title' => 'Служба поддержки', 'icon' => 'ri-customer-service-2-line', 'href' => route('support')],
        ['title' => 'Стать продавцом', 'icon' => 'ri-store-2-line', 'href' => route('seller.register')],
    ];
@endphp

<div class="max-w-8xl mx-auto px-3 sm:px-6 py-4 sm:py-8 pb-24 md:pb-8 space-y-6 sm:space-y-8">

    <section class="bg-white border border-gray-100 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">
        <div class="p-4 sm:p-6 lg:p-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div class="flex items-center gap-4 min-w-0">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 border border-indigo-100">
                    @if($user->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar))
                        <img src="{{ asset('storage/'.$user->avatar) }}" class="object-cover w-full h-full" alt="{{ $user->name }}">
                    @else
                        <span class="text-2xl sm:text-3xl font-bold">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    @endif
                </div>

                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold mb-2">
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

        <div class="grid grid-cols-3 border-t border-gray-100 bg-gray-50/60">
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
        </div>
    </section>

    <section class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
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
                                            <img src="{{ asset('storage/'.$item->product->image) }}" class="w-full h-full object-cover" alt="{{ $item->product->title }}">
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
                                        <img src="{{ asset('storage/'.$item['image']) }}" alt="{{ $item['title'] ?? 'Товар' }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
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

            <section class="bg-indigo-50 border border-indigo-100 rounded-xl sm:rounded-2xl shadow-sm p-4 sm:p-5">
                <div class="flex items-start gap-3">
                    <div class="w-11 h-11 rounded-xl bg-white text-indigo-600 flex items-center justify-center border border-indigo-100">
                        <i class="ri-gift-line text-xl"></i>
                    </div>
                    <div class="min-w-0">
                        <h2 class="font-semibold text-gray-900">Бонусы</h2>
                        <p class="text-sm text-gray-600 mt-1">Доступно к использованию: <strong>245 ₽</strong></p>
                    </div>
                </div>
                <button type="button" class="mt-4 w-full h-10 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">
                    Обменять бонусы
                </button>
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
