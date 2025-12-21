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

    <!-- 🔷 Блоки -->
    <!-- 🔸 Основное меню -->
    <div class="space-y-1 bg-white rounded-xl border border-gray-100 py-2 mx-4">

        <!-- Заказы -->
        <a href="{{ route('orders.index') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-file-list-3-line text-xl text-indigo-600"></i>
                <span>Мои заказы</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

        <!-- Отзывы -->
        <a href="{{ route('reviews.index') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-star-line text-xl text-yellow-500"></i>
                <span>Мои отзывы</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

        <!-- Вопросы и ответы -->
        <a href="{{ route('questions.index') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-question-answer-line text-xl text-teal-600"></i>
                <span>Вопросы и ответы</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

        <!-- Чаты -->
        <a href="{{ route('chats.index') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-chat-1-line text-xl text-blue-600"></i>
                <span>Чаты</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

    </div>


        <!-- 🔹 БОНУСЫ -->
    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 mx-4 shadow-sm">
        <h3 class="font-semibold text-gray-800 mb-1">Ваши бонусы</h3>
        <p class="text-sm text-gray-600 mb-3">Доступно к использованию: <b>245 ₽</b></p>

        <button class="w-full bg-indigo-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
            Обменять бонусы
        </button>
    </div>

<!-- 🔹 РЕКОМЕНДАЦИИ -->
<div class="overflow-x-auto py-2 px-4 md:hidden">
    <h3 class="font-semibold text-gray-800 mb-2">Рекомендации для вас</h3>
    <div class="flex gap-2">
        @foreach(array_slice($recommendations, 0, 5) as $item)
            <a href="{{ $item['link'] ?? '#' }}" class="flex-none w-16 h-16 rounded-lg border hover:shadow-md overflow-hidden">
                @if($item['image'])
                    <img src="{{ asset('storage/'.$item['image']) }}" class="w-full h-full object-cover" alt="{{ $item['title'] }}">
                @else
                    <div class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-300">
                        <i class="ri-image-line text-xl"></i>
                    </div>
                @endif
            </a>
        @endforeach
    </div>
</div>




    <!-- 🔸 Настройки -->
    <div class="space-y-1 bg-white rounded-xl border border-gray-100 py-2 mx-4">
        <h3 class="px-4 pb-1 text-xs uppercase tracking-wide text-gray-500">Настройки</h3>

        <a href="{{ route('addresses.index') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-map-pin-line text-xl text-blue-500"></i>
                <span>Адреса доставки</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

        <a href="{{ route('buyer.profile') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-user-settings-line text-xl text-gray-700"></i>
                <span>Личные данные</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

        <a href="{{ route('notifications.settings') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-notification-3-line text-xl text-amber-500"></i>
                <span>Уведомления</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

        <a href="{{ route('settings.language') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-translate-2 text-xl text-green-600"></i>
                <span>Язык интерфейса</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

        <a href="{{ route('settings.currency') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-money-dollar-circle-line text-xl text-indigo-600"></i>
                <span>Валюта</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>
    </div>

    <!-- 🔸 Информация -->
    <div class="space-y-1 bg-white rounded-xl border border-gray-100 py-2 mx-4">
        <h3 class="px-4 pb-1 text-xs uppercase tracking-wide text-gray-500">Информация</h3>

        <a href="{{ route('support') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-customer-service-2-line text-xl text-indigo-600"></i>
                <span>Служба поддержки</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

        <a href="{{ route('help') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-information-line text-xl text-gray-600"></i>
                <span>Справка WebVitrina</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

        <a href="{{ route('seller.register') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-store-2-line text-xl text-green-600"></i>
                <span>Стать продавцом</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>

        <a href="{{ route('about') }}" class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <i class="ri-apps-2-line text-xl text-purple-600"></i>
                <span>О приложении</span>
            </div>
            <i class="ri-arrow-right-s-line text-xl text-gray-400"></i>
        </a>
    </div>

    <!-- 🔹 Выход -->
    <form action="{{ route('logout') }}" method="POST" class="px-4 pb-6">
        @csrf
        <button class="w-full text-center text-red-600 font-medium py-3 border rounded-lg border-red-200">
            Выйти
        </button>
    </form>

</div>









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


<!-- Подключение Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css"/>

<!-- Рекомендации как слайдер -->
<div class="swiper mySwiper">
    <div class="swiper-wrapper">
        @foreach($recommendations as $item)
            <div class="swiper-slide">
                <div class="bg-gray-50 border border-gray-100 rounded-lg p-2 text-center hover:bg-white hover:shadow-md transition duration-200">
                    <div class="w-full h-[150px] bg-white rounded-md flex items-center justify-center mb-2 overflow-hidden">
                        @if(!empty($item['image']))
                            <img src="{{ asset('storage/'.$item['image']) }}" alt="{{ $item['title'] }}" class="w-full h-full object-cover">
                        @else
                            <i class="ri-image-2-line text-3xl text-gray-300"></i>
                        @endif
                    </div>

                    <a href="{{ $item['link'] ?? '#' }}" class="block text-sm font-medium text-gray-800 hover:text-indigo-600 truncate" title="{{ $item['title'] }}">
                        {{ $item['title'] }}
                    </a>

                    <p class="text-xs text-gray-500 mt-1">₽{{ number_format($item['price'], 0, '', ' ') }}</p>
                </div>
            </div>
        @endforeach
    </div>
    <!-- Стрелки -->
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
    <!-- Пагинация -->
    <div class="swiper-pagination"></div>
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


<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<script>
  const swiper = new Swiper('.mySwiper', {
    loop: true,
    slidesPerView: 2,
    spaceBetween: 10,
    breakpoints: {
        640: { slidesPerView: 2 },
        768: { slidesPerView: 4 },
        1024: { slidesPerView: 4 }
    },
    navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
    pagination: { el: '.swiper-pagination', clickable: true },
    autoplay: { delay: 3000, disableOnInteraction: false }
  });
</script>