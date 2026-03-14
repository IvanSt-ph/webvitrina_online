{{-- Полная страница магазина продавца --}}

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
<div class="absolute bottom-4 left-4 right-4 text-white" x-data="{ expanded: false }">
    <h1 class="text-2xl md:text-3xl font-bold drop-shadow-lg mb-2">{{ $shop->name }}</h1>
    
    <!-- Описание (короткое или полное) -->
    <div class="text-sm md:text-base drop-shadow-md bg-black/30 backdrop-blur-sm p-3 rounded-lg whitespace-normal break-words">
        <span x-show="!expanded" class="line-clamp-1">{{ $shop->description ?? 'Описание магазина отсутствует.' }}</span>
        <span x-show="expanded" x-cloak>{{ $shop->description ?? 'Описание магазина отсутствует.' }}</span>
        
        <!-- Кнопка "ещё" - показываем только если описание длинное -->
        @if(strlen($shop->description ?? '') > 50)
            <button @click="expanded = !expanded" class="mt-1 text-indigo-200 hover:text-white font-medium text-sm flex items-center gap-1">
                <span x-show="!expanded">Читать далее <i class="ri-arrow-down-s-line"></i></span>
                <span x-show="expanded" x-cloak>Свернуть <i class="ri-arrow-up-s-line"></i></span>
            </button>
        @endif
    </div>
</div>
</div>

<!-- 🔹 Карточка продавца -->
<div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-all duration-300">
    <div class="p-6">
        <!-- Верхний блок с аватаром -->
        <div class="flex flex-col md:flex-row gap-6 items-center md:items-start">
            
            <!-- Аватар с бейджем -->
            <div class="relative flex-shrink-0">
                <div class="w-24 h-24 rounded-2xl overflow-hidden bg-gradient-to-br from-indigo-100 to-purple-100 ring-4 ring-white shadow-xl">
                    <img src="{{ $user->avatar_url }}" class="w-full h-full object-cover" alt="{{ $user->name }}">
                </div>

                <!-- Бейдж верификации -->
                @if($user->hasVerifiedEmail() && $shop->is_phone_verified)
                <div class="absolute -top-2 -left-2">
                    <div class="relative">
                        <div class="absolute w-7 h-7 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl opacity-30 animate-ping"></div>
                        <div class="relative w-7 h-7 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center ring-2 ring-white shadow-lg cursor-help" title="Продавец верифицирован">
                            <i class="ri-shield-check-fill text-white text-sm"></i>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Информация -->
            <div class="flex-1 w-full md:w-auto">

                <!-- Заголовок с именем и бейджами -->
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-2">
                    
                    <!-- Левая часть: имя и магазин (на мобилке по центру) -->
                    <div class="text-center md:text-left">
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center justify-center md:justify-start gap-2 flex-wrap">
                            {{ $user->name }}
                            @if($user->hasVerifiedEmail() && $shop->is_phone_verified)
                                <span class="px-2 py-1 bg-gradient-to-r from-green-50 to-emerald-50 text-green-700 text-xs font-medium rounded-full border border-green-200">
                                    <i class="ri-shield-check-fill text-green-500 mr-0.5"></i>
                                    Проверенный
                                </span>
                            @elseif($user->hasVerifiedEmail() || $shop->is_phone_verified)
                                <span class="px-2 py-1 bg-gradient-to-r from-blue-50 to-indigo-50 text-blue-700 text-xs font-medium rounded-full border border-blue-200">
                                    <i class="ri-shield-user-line text-blue-500 mr-0.5"></i>
                                    Верифицирован
                                </span>
                            @endif
                        </h1>
                        
                        <!-- Название магазина (по центру на мобилке) -->
                        <div class="flex items-center justify-center md:justify-start gap-2 mt-1.5">
                            <span class="text-xs text-gray-500">Магазин:</span>
                            <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md">
                                {{ $shop->name }}
                            </span>
                        </div>
                    </div>

                    <!-- ПРАВАЯ ЧАСТЬ: Репутация + Рейтинг -->
                    <div class="flex flex-row sm:flex-col gap-2 items-center sm:items-end">
                        
                        <!-- БЛОК РЕПУТАЦИИ -->
                        <div class="flex items-center">
                            @switch($shop->seller_reputation)
                                @case('top')
                                    <div class="relative group">
                                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-slate-100 to-gray-100 border-r-4 border-slate-400 rounded-l-full shadow-sm hover:shadow-md transition-all duration-300">
                                            <span class="text-sm">💎</span>
                                            <span class="text-xs font-semibold text-slate-700">Платиновый уровень</span>
                                            <i class="ri-information-line text-slate-400 text-xs ml-0.5 opacity-60 group-hover:opacity-100"></i>
                                        </div>
                                        <div class="absolute top-full right-0 mt-1 hidden group-hover:block w-48 p-2 bg-gray-900 text-white text-[10px] rounded-lg shadow-xl z-50">
                                            <div class="font-medium mb-0.5">💎 Платиновый уровень</div>
                                            <div class="text-gray-300">Высший уровень доверия и качества</div>
                                            <div class="absolute top-0 right-3 transform -translate-y-1/2 rotate-45 w-1.5 h-1.5 bg-gray-900"></div>
                                        </div>
                                    </div>
                                    @break
                                    
                                @case('trusted')
                                    <div class="relative group">
                                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-amber-50 to-yellow-50 border-r-4 border-amber-400 rounded-l-full shadow-sm">
                                            <span class="text-sm">🥇</span>
                                            <span class="text-xs font-semibold text-amber-700">Золотой уровень</span>
                                            <i class="ri-information-line text-amber-400 text-xs ml-0.5 opacity-60 group-hover:opacity-100"></i>
                                        </div>
                                        <div class="absolute top-full right-0 mt-1 hidden group-hover:block w-48 p-2 bg-gray-900 text-white text-[10px] rounded-lg shadow-xl z-50">
                                            <div class="font-medium mb-0.5">🥇 Золотой уровень</div>
                                            <div class="text-gray-300">Много продаж, высокий рейтинг</div>
                                            <div class="absolute top-0 right-3 transform -translate-y-1/2 rotate-45 w-1.5 h-1.5 bg-gray-900"></div>
                                        </div>
                                    </div>
                                    @break
                                    
                                @case('verified')
                                    <div class="relative group">
                                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-gray-100 to-slate-200 border-r-4 border-gray-500 rounded-l-full shadow-sm">
                                            <span class="text-sm">🥈</span>
                                            <span class="text-xs font-semibold text-gray-700">Серебряный уровень</span>
                                            <i class="ri-information-line text-gray-500 text-xs ml-0.5 opacity-60 group-hover:opacity-100"></i>
                                        </div>
                                        <div class="absolute top-full right-0 mt-1 hidden group-hover:block w-48 p-2 bg-gray-900 text-white text-[10px] rounded-lg shadow-xl z-50">
                                            <div class="font-medium mb-0.5">🥈 Серебряный уровень</div>
                                            <div class="text-gray-300">Проверенный временем, хорошие отзывы</div>
                                            <div class="absolute top-0 right-3 transform -translate-y-1/2 rotate-45 w-1.5 h-1.5 bg-gray-900"></div>
                                        </div>
                                    </div>
                                    @break
                                    
                                @case('new')
                                    <div class="relative group">
                                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-amber-100 to-orange-100 border-r-4 border-amber-600 rounded-l-full shadow-sm">
                                            <span class="text-sm">🥉</span>
                                            <span class="text-xs font-semibold text-amber-800">Бронзовый уровень</span>
                                            <i class="ri-information-line text-amber-600 text-xs ml-0.5 opacity-60 group-hover:opacity-100"></i>
                                        </div>
                                        <div class="absolute top-full right-0 mt-1 hidden group-hover:block w-48 p-2 bg-gray-900 text-white text-[10px] rounded-lg shadow-xl z-50">
                                            <div class="font-medium mb-0.5">🥉 Бронзовый уровень</div>
                                            <div class="text-gray-300">Надёжный базовый уровень</div>
                                            <div class="absolute top-0 right-3 transform -translate-y-1/2 rotate-45 w-1.5 h-1.5 bg-gray-900"></div>
                                        </div>
                                    </div>
                                    @break
                                    
                                @case('low_rating')
                                    <div class="relative group">
                                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-rose-50 to-red-50 border-r-4 border-rose-400 rounded-l-full shadow-sm">
                                            <span class="text-sm">⚠️</span>
                                            <span class="text-xs font-semibold text-rose-700">Низкий уровень</span>
                                            <i class="ri-information-line text-rose-400 text-xs ml-0.5 opacity-60 group-hover:opacity-100"></i>
                                        </div>
                                        <div class="absolute top-full right-0 mt-1 hidden group-hover:block w-48 p-2 bg-gray-900 text-white text-[10px] rounded-lg shadow-xl z-50">
                                            <div class="font-medium mb-0.5">⚠️ Низкий уровень</div>
                                            <div class="text-gray-300">Есть негативные отзывы, ознакомьтесь перед покупкой</div>
                                            <div class="absolute top-0 right-3 transform -translate-y-1/2 rotate-45 w-1.5 h-1.5 bg-gray-900"></div>
                                        </div>
                                    </div>
                                    @break
                            @endswitch
                        </div>

                        <!-- БЛОК РЕЙТИНГА -->
                        <div class="flex items-center">
                            <div class="flex items-center gap-2 bg-yellow-50/80 px-3 py-1.5 rounded-lg border border-yellow-100">
                                <span class="text-[10px] font-medium text-gray-500 tracking-wide">РЕЙТИНГ:</span>
                                <div class="flex items-center gap-1.5">
                                    <div class="flex items-center gap-0.5">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= round($user->reviews_avg_rating ?? 0))
                                                <i class="ri-star-fill text-yellow-400 text-xs"></i>
                                            @else
                                                <i class="ri-star-line text-yellow-200 text-xs"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <span class="text-sm font-bold text-gray-800 leading-none">{{ number_format($user->reviews_avg_rating ?? 0, 1) }}</span>
                                    <span class="text-[10px] text-gray-400">({{ $user->reviews_count ?? 0 }})</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Город и дата регистрации (по центру на мобилке) -->
                <div class="flex items-center justify-center md:justify-start gap-3 mt-2 text-sm text-gray-500">
                    @if($shop->city)
                        <span class="flex items-center gap-1">
                            <i class="ri-map-pin-line text-gray-400"></i>
                            {{ $shop->city }}
                        </span>
                    @endif
                    <span class="flex items-center gap-1">
                        <i class="ri-calendar-line text-gray-400"></i>
                        На сайте с {{ $user->created_at?->format('M Y') }}
                    </span>
                </div>

                <!-- Статусы верификации, соцсети и кнопки в одну строку -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-3 mt-3 border-t border-gray-100">
                    <div class="flex items-center gap-3">
                        <!-- Email статус -->
                        <span class="flex items-center gap-1 text-xs {{ $user->hasVerifiedEmail() ? 'text-green-600' : 'text-gray-400' }}" title="{{ $user->hasVerifiedEmail() ? 'Email подтверждён' : 'Email не подтверждён' }}">
                            <i class="ri-mail-{{ $user->hasVerifiedEmail() ? 'check-fill' : 'line' }} text-base"></i>
                        </span>

                        <!-- Телефон статус -->
                        @if($shop->phone)
                        <span class="flex items-center gap-1 text-xs {{ $shop->is_phone_verified ? 'text-green-600' : 'text-gray-400' }}" title="{{ $shop->is_phone_verified ? 'Телефон подтверждён' : 'Телефон не подтверждён' }}">
                            <i class="ri-smartphone-{{ $shop->is_phone_verified ? 'fill' : 'line' }} text-base"></i>
                        </span>
                        @endif

                        <!-- Социальные сети -->
                        @if($shop->facebook || $shop->instagram || $shop->telegram || $shop->whatsapp)
                        <span class="w-px h-4 bg-gray-200 mx-1"></span>
                        <div class="flex items-center gap-4">
                            @if($shop->facebook)
                                <a href="{{ Str::startsWith($shop->facebook, 'http') ? $shop->facebook : 'https://' . $shop->facebook }}" target="_blank" class="text-gray-400 hover:text-indigo-600 transition-colors" title="Facebook">
                                    <i class="ri-facebook-fill text-lg"></i>
                                </a>
                            @endif
                            @if($shop->instagram)
                                <a href="{{ Str::startsWith($shop->instagram, 'http') ? $shop->instagram : 'https://' . $shop->instagram }}" target="_blank" class="text-gray-400 hover:text-pink-600 transition-colors" title="Instagram">
                                    <i class="ri-instagram-fill text-lg"></i>
                                </a>
                            @endif
                            @if($shop->telegram)
                                <a href="{{ Str::startsWith($shop->telegram, 'http') ? $shop->telegram : 'https://' . $shop->telegram }}" target="_blank" class="text-gray-400 hover:text-sky-600 transition-colors" title="Telegram">
                                    <i class="ri-telegram-fill text-lg"></i>
                                </a>
                            @endif
                            @if($shop->whatsapp)
                                <a href="{{ Str::startsWith($shop->whatsapp, 'http') ? $shop->whatsapp : 'https://' . $shop->whatsapp }}" target="_blank" class="text-gray-400 hover:text-green-600 transition-colors" title="WhatsApp">
                                    <i class="ri-whatsapp-fill text-lg"></i>
                                </a>
                            @endif
                        </div>
                        @endif
                    </div>

                    <!-- Кнопки -->
                    <div class="flex items-center gap-2">
                        <button class="px-4 py-2 bg-gradient-to-r from-indigo-50 to-purple-50 text-indigo-700 rounded-xl hover:from-indigo-100 hover:to-purple-100 transition-all duration-300 text-sm font-medium flex items-center gap-1">
                            <i class="ri-heart-3-line"></i>
                            <span class="hidden sm:inline">Подписаться</span>
                        </button>
                        <button class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 text-sm font-medium flex items-center gap-1 shadow-lg shadow-indigo-200">
                            <i class="ri-chat-1-line"></i>
                            <span class="hidden sm:inline">Написать</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
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

    <style>
    .line-clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    [x-cloak] { display: none !important; }
</style>
</x-app-layout>

@endif