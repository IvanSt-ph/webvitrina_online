{{-- Полная страница магазина продавца с улучшениями и интерактивом --}}

@php
    /** @var \App\Models\User $user */
    /** @var \Illuminate\Pagination\LengthAwarePaginator $products */

    $shop = $shop ?? $user->shop;
@endphp

@if(!$shop)

    <x-app-layout :title="$user->name">
        <div class="max-w-3xl mx-auto py-20 text-center text-gray-500">
            У продавца ещё не создан магазин
        </div>
        @include('layouts.mobile-bottom-nav')
    </x-app-layout>

@else

<x-app-layout :title="$shop->name">

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-8">

<!-- 🔹 Слайдер баннеров магазина --> 
<div class="relative w-full h-56 md:h-64 mt-0 md:mt-8 rounded-xl overflow-hidden shadow-lg">
    <div class="absolute inset-0 overflow-hidden rounded-xl">
        <img src="{{ $shop->banner_url }}" class="w-full h-full object-cover transition-transform duration-500 hover:scale-105" alt="Баннер магазина">
        <div class="absolute inset-0 bg-gradient-to-b from-transparent to-black/60"></div>
    </div>
    <div class="absolute bottom-4 left-4 text-white">
        <h1 class="text-2xl md:text-3xl font-bold drop-shadow-lg">{{ $shop->name }}</h1>
        <p class="text-sm md:text-base drop-shadow-md line-clamp-2">{{ $shop->description ?? 'Описание магазина отсутствует.' }}</p>
    </div>
</div>


        <!-- 🔹 Информация о продавце -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4 hover:shadow-lg transition-all duration-300">

            <div class="flex items-center gap-4">
                <!-- Аватар -->
                <div class="w-20 h-20 rounded-full overflow-hidden bg-gray-200 flex-shrink-0 transform transition hover:scale-105 hover:shadow-md">
                    <img src="{{ $user->avatar_url }}" class="w-full h-full object-cover" alt="{{ $user->name }}">
                </div>

                <!-- Информация -->
                <div class="flex-1">
                    <div class="flex flex-col md:flex-row md:items-center md:gap-4">
                        <h2 class="text-xl md:text-2xl font-semibold text-gray-800">{{ $shop->name }}</h2>
                        <span class="text-sm text-gray-500 bg-indigo-50 px-2 py-1 rounded-full mt-1 md:mt-0">
                            {{ $user->role == 'seller' ? 'Продавец' : 'Пользователь' }}
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 mt-2 text-sm text-gray-500">
                        @if($shop->city)
                            <span class="flex items-center gap-1"><i class="ri-map-pin-line"></i> {{ $shop->city }}</span>
                        @endif
                        @if($shop->phone)
                            <span class="flex items-center gap-1"><i class="ri-phone-line"></i> {{ $shop->phone }}</span>
                        @endif
                        <span class="flex items-center gap-1"><i class="ri-star-fill text-yellow-400"></i> {{ $user->reviews_avg_rating ?? 0 }}<span class="text-gray-400">({{ $user->reviews_count ?? 0 }})</span></span>
                    </div>

<!-- Социальные сети -->
<div class="flex items-center gap-3 mt-2 text-sm">
    @if($shop->facebook)
        <a href="{{ Str::startsWith($shop->facebook, 'http') ? $shop->facebook : 'https://' . $shop->facebook }}" target="_blank" class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 hover:bg-indigo-600 text-gray-700 hover:text-white transition text-lg">
            <i class="ri-facebook-fill"></i>
        </a>
    @endif
    @if($shop->instagram)
        <a href="{{ Str::startsWith($shop->instagram, 'http') ? $shop->instagram : 'https://' . $shop->instagram }}" target="_blank" class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 hover:bg-indigo-600 text-gray-700 hover:text-white transition text-lg">
            <i class="ri-instagram-fill"></i>
        </a>
    @endif
    @if($shop->telegram)
        <a href="{{ Str::startsWith($shop->telegram, 'http') ? $shop->telegram : 'https://' . $shop->telegram }}" target="_blank" class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 hover:bg-indigo-600 text-gray-700 hover:text-white transition text-lg">
            <i class="ri-telegram-fill"></i>
        </a>
    @endif
    @if($shop->whatsapp)
        <a href="{{ Str::startsWith($shop->whatsapp, 'http') ? $shop->whatsapp : 'https://' . $shop->whatsapp }}" target="_blank" class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 hover:bg-indigo-600 text-gray-700 hover:text-white transition text-lg">
            <i class="ri-whatsapp-fill"></i>
        </a>
    @endif
</div>


                </div>
            </div>

            <!-- Кнопки -->
            <div class="flex gap-2">
                <a href="#" class="bg-indigo-600 text-white px-4 py-2 rounded-lg opacity-60 cursor-not-allowed text-sm font-medium flex items-center gap-1">
                    <i class="ri-chat-1-line"></i> Чат
                </a>
                <button class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition text-sm font-medium">
                    Подписаться
                </button>
            </div>
        </div>

        <!-- 🔹 Фильтры товаров -->
        <div class="flex flex-wrap items-center gap-3 bg-white rounded-xl p-3 shadow-sm border border-gray-100">
            <span class="text-gray-500 font-medium">Фильтр:</span>
            <button class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition text-sm">Все</button>
            <button class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition text-sm">Новинки</button>
            <button class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition text-sm">Скидки</button>
            <button class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition text-sm">Хиты</button>
        </div>

        <!-- 🔹 Сетка товаров -->
        <div>
            <h2 class="text-xl font-semibold mb-4">Товары магазина</h2>

            @if($products->count())
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">

                    @foreach($products as $product)
                        <a href="{{ route('product.show', $product->slug) }}" class="bg-white border border-gray-100 rounded-xl p-2 hover:shadow-xl hover:scale-105 transition-all duration-300 flex flex-col relative group">

                            <!-- Метки -->
                            @if($product->is_new)
                                <span class="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded-lg z-10">Новый</span>
                            @endif
                            @if($product->discount)
                                <span class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded-lg z-10">-{{ $product->discount }}%</span>
                            @endif
                            @if($product->is_popular)
                                <span class="absolute bottom-2 left-2 bg-yellow-400 text-white text-xs px-2 py-1 rounded-lg z-10">Хит</span>
                            @endif

                            <!-- Изображение -->
                            <div class="w-full h-32 md:h-36 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center mb-2">
                                @if($product->image && Storage::disk('public')->exists($product->image))
                                    <img src="{{ asset('storage/'.$product->image) }}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" alt="{{ $product->title }}">
                                @else
                                    <i class="ri-image-line text-gray-300 text-3xl"></i>
                                @endif
                            </div>

                            <!-- Название -->
                            <div class="text-sm font-medium line-clamp-2 text-gray-800 mb-1">{{ $product->title }}</div>

                            <!-- Цена -->
                            <div class="text-indigo-600 font-semibold text-sm">{{ number_format($product->price, 0, '', ' ') }} ₽</div>

                        </a>
                    @endforeach

                </div>

                <div class="mt-6 flex justify-center">
                    {{ $products->links('vendor.pagination.tailwind') }}
                </div>
            @else
                <p class="text-gray-500">У этого продавца пока нет товаров.</p>
            @endif
        </div>

        <!-- 🔹 Рекомендованные товары -->
        <div class="mt-8">
            <h2 class="text-xl font-semibold mb-4">Рекомендованные</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                @foreach($products->take(4) as $product)
                    <a href="{{ route('product.show', $product->slug) }}" class="bg-white border border-gray-100 rounded-xl p-2 hover:shadow-lg hover:scale-105 transition-all duration-300 flex flex-col">
                        <div class="w-full h-28 bg-gray-100 rounded-lg overflow-hidden flex items-center justify-center mb-2">
                            @if($product->image && Storage::disk('public')->exists($product->image))
                                <img src="{{ asset('storage/'.$product->image) }}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" alt="{{ $product->title }}">
                            @else
                                <i class="ri-image-line text-gray-300 text-3xl"></i>
                            @endif
                        </div>
                        <div class="text-sm font-medium line-clamp-2 text-gray-800 mb-1">{{ $product->title }}</div>
                        <div class="text-indigo-600 font-semibold text-sm">{{ number_format($product->price, 0, '', ' ') }} ₽</div>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- 🔹 Плюшки: доставка, гарантии, акции -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-4 mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-gray-700 text-sm md:text-base flex flex-col md:flex-row md:gap-6 gap-2">
                <span class="flex items-center gap-1"><i class="ri-truck-line text-indigo-600"></i> Быстрая доставка</span>
                <span class="flex items-center gap-1"><i class="ri-shield-check-line text-green-600"></i> Гарантия качества</span>
                <span class="flex items-center gap-1"><i class="ri-star-line text-yellow-400"></i> Топ продавцов</span>
            </div>
            <a href="#" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                Смотреть все акции
            </a>
        </div>

    </div>

    @include('layouts.mobile-bottom-nav')

</x-app-layout>

@endif
