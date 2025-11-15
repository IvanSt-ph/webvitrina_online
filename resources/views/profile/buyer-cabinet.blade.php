<x-buyer-layout title="Личный кабинет">

@php
    $cartCount = \App\Models\CartItem::where('user_id', $user->id)->count();
    $favCount = $user->favorites()->count();
    $ordersCount = \App\Models\Order::where('user_id', $user->id)->count();
@endphp


<!-- 📱 МОБИЛЬНАЯ ВЕРСИЯ -->
<div class="md:hidden space-y-6 pt-4">

    <!-- 🔹 Аватар + имя -->
    <div class="flex items-center gap-4 px-4">
        <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-200">
            @if($user->avatar && Storage::disk('public')->exists($user->avatar))
                <img src="{{ asset('storage/'.$user->avatar) }}" class="object-cover w-full h-full">
            @else
                <div class="w-full h-full flex items-center justify-center text-2xl font-bold text-gray-600">
                    {{ strtoupper(substr($user->name,0,1)) }}
                </div>
            @endif
        </div>

        <div>
            <div class="text-xl font-semibold">{{ $user->name }}</div>
            <div class="text-sm text-gray-500">{{ $user->email }}</div>
        </div>
    </div>


    <!-- 🔹 Главное меню -->
    <div class="space-y-1 bg-white rounded-xl border border-gray-100 py-2 mx-4">

        <a href="{{ route('orders.index') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-box-3-line text-xl text-indigo-600"></i>
                <span>Заказы</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

        <a href="{{ route('favorites.index') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-heart-line text-xl text-pink-500"></i>
                <span>Избранное</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

        <a href="{{ route('cart.index') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-shopping-cart-2-line text-xl text-emerald-600"></i>
                <span>Корзина</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

        <a href="{{ route('addresses.index') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-map-pin-line text-xl text-blue-500"></i>
                <span>Адреса доставки</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

        <a href="{{ route('buyer.profile') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-settings-3-line text-xl text-gray-700"></i>
                <span>Личные данные</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

    </div>


<!-- 🔹 Последние заказы -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mx-4">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold">Последние заказы</h3>
        <a href="{{ route('orders.index') }}" class="text-indigo-600 text-sm">Все →</a>
    </div>

    @forelse($latestOrders as $order)
        <div class="py-3 border-b last:border-0">

            <!-- Верхняя строка -->
            <div class="flex justify-between mb-1">
                <span class="font-medium">Заказ #{{ $order->id }}</span>
                <span class="text-xs text-gray-500">{{ $order->created_at->format('d.m.Y') }}</span>
            </div>

            <!-- Статус -->
            <div class="text-sm text-indigo-600 mb-2">
                {{ $order->status_ru }}
            </div>

            <!-- Список товаров -->
            <div class="space-y-1">
                @foreach($order->items->take(2) as $item)
                    <div class="flex items-center gap-2 text-sm">
                        @if($item->product->image)
                            <img src="{{ asset('storage/'.$item->product->image) }}"
                                 class="w-8 h-8 rounded object-cover">
                        @else
                            <div class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center text-xs text-gray-400">
                                нет
                            </div>
                        @endif

                        <span class="line-clamp-1">
                            {{ $item->product->title }}
                        </span>
                    </div>
                @endforeach

                <!-- Показать "+ ещё" если товаров больше -->
                @if($order->items->count() > 2)
                    <div class="text-xs text-gray-500">
                        + ещё {{ $order->items->count() - 2 }}
                    </div>
                @endif
            </div>

        </div>
    @empty
        <p class="text-sm text-gray-500">У вас ещё нет заказов.</p>
    @endforelse
</div>



    <!-- 🔹 Бонусы -->
    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mx-4 shadow-sm">
        <h3 class="font-semibold text-gray-800 mb-1">Ваши бонусы</h3>
        <p class="text-sm text-gray-600 mb-3">Доступно к использованию: <b>245 ₽</b></p>
        <button class="w-full bg-indigo-600 text-white py-2 rounded-lg text-sm font-medium">
            Обменять бонусы
        </button>
    </div>


    <!-- 🔹 Рекомендации -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mx-4">
        <h3 class="text-lg font-semibold mb-3">Рекомендации</h3>

        <div class="grid grid-cols-2 gap-3">
            @foreach($recommendations as $item)
                <div class="bg-gray-50 p-2 rounded-xl border hover:bg-white transition">
                    <div class="h-20 bg-white rounded-lg flex items-center justify-center mb-2 border">
                        <i class="ri-image-line text-2xl text-gray-300"></i>
                    </div>
                    <p class="text-sm font-medium">{{ $item['title'] }}</p>
                    <p class="text-xs text-gray-500">₽{{ $item['price'] }}</p>
                </div>
            @endforeach
        </div>
    </div>


    <!-- 🔹 Выход -->
    <form action="{{ route('logout') }}" method="POST" class="px-4 pb-6">
        @csrf
        <button class="w-full text-center text-red-600 font-medium py-3 border rounded-lg border-red-200">
            Выйти
        </button>
    </form>

</div>
<!-- /Мобильная версия -->









<!-- 🖥 ПК ВЕРСИЯ -->
<div class="hidden md:block">

    <div class="space-y-8">

        <p class="text-gray-500 text-sm">Добро пожаловать в ваш личный кабинет!</p>

        <!-- 🔹 Статистика -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

            <div class="bg-white rounded-xl p-5 border border-gray-100 hover:shadow-md transition">
                <p class="text-sm text-gray-500">Мои заказы</p>
                <div class="text-2xl font-semibold mt-2 text-gray-800">{{ $ordersCount }}</div>
                <p class="text-xs text-gray-400 mt-1">История покупок</p>
            </div>

            <div class="bg-white rounded-xl p-5 border border-gray-100 hover:shadow-md transition">
                <p class="text-sm text-gray-500">Избранное</p>
                <div class="text-2xl font-semibold mt-2 text-gray-800">{{ $favCount }}</div>
                <p class="text-xs text-gray-400 mt-1">Все понравившееся</p>
            </div>

            <div class="bg-white rounded-xl p-5 border border-gray-100 hover:shadow-md transition">
                <p class="text-sm text-gray-500">Корзина</p>
                <div class="text-2xl font-semibold mt-2 text-gray-800">{{ $cartCount }}</div>
                <p class="text-xs text-gray-400 mt-1">Готово к оплате</p>
            </div>

            <div class="bg-white rounded-xl p-5 border border-gray-100 hover:shadow-md transition">
                <p class="text-sm text-gray-500">Бонусы</p>
                <div class="text-2xl font-semibold mt-2 text-gray-800">₽245</div>
                <p class="text-xs text-gray-400 mt-1">За отзывы и покупки</p>
            </div>

        </div>


        <!-- 🔹 Основной контент -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- ЛЕВАЯ ПАНЕЛЬ -->
            <div class="lg:col-span-2 space-y-6">

<!-- 🔹 Последние заказы -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">

    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold">Последние заказы</h3>
        <a href="{{ route('orders.index') }}" class="text-indigo-600 text-sm">Все →</a>
    </div>

    @forelse($latestOrders as $order)
        <div class="py-3 border-b last:border-0">

            <!-- Верхняя строка -->
            <div class="flex justify-between mb-1">
                <span class="font-medium">Заказ #{{ $order->id }}</span>
                <span class="text-xs text-gray-500">{{ $order->created_at->format('d.m.Y') }}</span>
            </div>

            <!-- Статус -->
            <div class="text-sm text-indigo-600 mb-2">
                {{ $order->status_ru }}
            </div>

            <!-- Список товаров -->
            <div class="space-y-1">
                @foreach($order->items->take(2) as $item)
                    <div class="flex items-center gap-2 text-sm">
                        @if($item->product->image)
                            <img src="{{ asset('storage/'.$item->product->image) }}"
                                 class="w-8 h-8 rounded object-cover">
                        @else
                            <div class="w-8 h-8 bg-gray-100 rounded flex items-center justify-center text-xs text-gray-400">
                                нет
                            </div>
                        @endif

                        <span class="line-clamp-1">
                            {{ $item->product->title }}
                        </span>
                    </div>
                @endforeach

                <!-- Показать "+ ещё" если товаров больше -->
                @if($order->items->count() > 2)
                    <div class="text-xs text-gray-500">
                        + ещё {{ $order->items->count() - 2 }}
                    </div>
                @endif
            </div>

        </div>
    @empty
        <p class="text-sm text-gray-500">У вас ещё нет заказов.</p>
    @endforelse
</div>


                <!-- Рекомендации -->
                <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Рекомендации для вас</h2>
                        <a href="#" class="text-sm text-indigo-600 hover:underline">Обновить</a>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        @foreach($recommendations as $item)
                            <div class="bg-gray-50 border border-gray-100 rounded-xl p-3 text-center hover:bg-white hover:shadow-sm transition">
                                <div class="h-24 bg-white rounded-lg flex items-center justify-center mb-3">
                                    <i class="ri-image-2-line text-2xl text-gray-300"></i>
                                </div>
                                <p class="text-sm font-medium text-gray-800">{{ $item['title'] }}</p>
                                <p class="text-xs text-gray-500 mt-1">₽{{ $item['price'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>


            <!-- ПРАВАЯ ПАНЕЛЬ -->
            <div class="space-y-6">

                <!-- Активность -->
                <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition">
                    <h3 class="font-semibold text-lg mb-3 text-gray-800">Активность</h3>
                    <ul class="space-y-3 text-sm text-gray-600">
                        <li class="flex justify-between"><span>Добавлен отзыв</span> <span class="text-gray-400">08.10</span></li>
                        <li class="flex justify-between"><span>Товар в избранное</span> <span class="text-gray-400">07.10</span></li>
                        <li class="flex justify-between"><span>Оплачен заказ</span> <span class="text-gray-400">06.10</span></li>
                    </ul>
                </div>

                <!-- Бонусы -->
                <div class="bg-gradient-to-br from-indigo-50 to-white rounded-xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition">
                    <h3 class="font-semibold text-lg mb-3 text-gray-800">Ваши бонусы</h3>
                    <p class="text-sm text-gray-500 mb-4">Вы можете обменять бонусы на скидки или купоны.</p>
                    <button class="w-full bg-indigo-600 text-white font-medium py-2 rounded-lg hover:bg-indigo-700 transition">
                        Обменять бонусы
                    </button>
                </div>

            </div>
        </div>

    </div>

</div>
<!-- /ПК ВЕРСИЯ -->


@include('layouts.mobile-bottom-nav')

</x-buyer-layout>

