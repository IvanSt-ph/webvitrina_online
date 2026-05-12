{{-- resources/views/shop/cart.blade.php --}}
<x-buyer-layout title="Моя корзина">

@php
    $cartTotal = $items->sum(fn($i) => $i->product->price * $i->qty);
    $freeShippingThreshold = 5000;
@endphp

<div x-data="cartSelection({{ $cartTotal }}, {{ $items->sum('qty') }}, {{ $freeShippingThreshold }})" x-init="init" class="max-w-8xl mx-auto px-2 sm:px-6 py-4 sm:py-8 {{ $items->isNotEmpty() ? 'pb-28 sm:pb-8' : '' }}">

    <!-- 🔝 Элегантный заголовок -->
    <div class="mb-6 sm:mb-10">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm">
                    <i class="ri-shopping-cart-2-line text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-4xl font-semibold tracking-tight text-gray-900">Корзина</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        @if($items->isNotEmpty())
                            <span x-text="totalQty"></span> товара(ов)
                        @else
                            пусто
                        @endif
                    </p>
                </div>
            </div>

            @if($items->isNotEmpty())
            <div class="flex items-center gap-3 flex-wrap">
                <x-secondary-action type="button" @click="toggleSelectMode">
                    <span x-show="!selectMode" class="inline-flex items-center gap-2">
                        <i class="ri-checkbox-multiple-line"></i>
                        Выбрать
                    </span>
                    <span x-show="selectMode" class="inline-flex items-center gap-2">
                        <i class="ri-close-line"></i>
                        Отменить
                    </span>
                </x-secondary-action>

                <form method="POST" action="{{ route('checkout.prepare') }}" class="inline">
                    @csrf
                    <x-action-button>
                        <i class="ri-bank-card-line"></i>
                        Оформить всё
                    </x-action-button>
                </form>
            </div>
            @endif
        </div>
    </div>

    @if($items->isEmpty())

        <x-empty-state
            icon="ri-shopping-cart-2-line"
            title="Ваша корзина пуста"
            description="Добавьте товары из каталога, чтобы оформить заказ."
            class="py-16 sm:py-24"
        >
            <a href="{{ route('home') }}"
               class="relative overflow-hidden group inline-flex items-center justify-center gap-2 px-8 py-3 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 backdrop-blur-sm border border-indigo-400/30">
                <span class="relative z-10 flex items-center gap-2">
                    <i class="ri-arrow-left-line"></i>
                    Перейти в каталог
                </span>
                <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
            </a>
        </x-empty-state>

    @else

    <!-- 📊 Прогресс бесплатной доставки -->
    <div x-show="remainingForFree > 0" class="bg-indigo-50/70 rounded-xl sm:rounded-2xl p-4 mb-6 border border-indigo-100">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-white text-indigo-600 flex items-center justify-center border border-indigo-100 shrink-0">
                <i class="ri-truck-line text-xl"></i>
            </div>
            <div class="flex-1">
                <div class="text-sm text-indigo-900 mb-2 font-medium">
                    Добавьте товаров на <strong class="text-indigo-700" x-text="formatPrice(remainingForFree) + ' ₽'"></strong> для бесплатной доставки
                </div>
                <div class="h-2 bg-indigo-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full transition-all duration-500" 
                         :style="`width: ${freeShippingProgress}%`"></div>
                </div>
                <div class="flex justify-between text-xs text-indigo-600 mt-1">
                    <span>0 ₽</span>
                    <span>{{ number_format($freeShippingThreshold, 0, ',', ' ') }} ₽</span>
                </div>
            </div>
        </div>
    </div>

    <div x-show="remainingForFree <= 0" x-cloak class="bg-emerald-50/80 rounded-xl sm:rounded-2xl p-4 mb-6 border border-emerald-100">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-white text-emerald-600 flex items-center justify-center border border-emerald-100 shrink-0">
                <i class="ri-check-line text-xl"></i>
            </div>
            <div>
                <div class="text-sm text-emerald-900 font-semibold">Бесплатная доставка доступна</div>
                <div class="text-xs text-emerald-700 mt-1">Сумма корзины уже выше порога {{ number_format($freeShippingThreshold, 0, ',', ' ') }} ₽.</div>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-[minmax(0,1fr)_360px] gap-6 items-start">

    <!-- 📜 Список товаров -->
    <div class="space-y-3" :class="selectMode && selected.length > 0 ? 'mb-36' : 'mb-0'">
        @foreach($items as $i)
        @php $p = $i->product; @endphp

        <div 
            x-data="{ qty: {{ $i->qty }}, savedQty: {{ $i->qty }}, updating: false }"
            class="cart-item group relative bg-white rounded-xl sm:rounded-2xl border transition-all duration-200 hover:shadow-md"
            :class="{
                'border-indigo-300 shadow-md bg-indigo-50/50': selectMode && selected.includes('{{ $i->id }}'),
                'border-gray-100': !selectMode || !selected.includes('{{ $i->id }}')
            }"
            data-cart-id="{{ $i->id }}"
        >
            <div 
                class="flex gap-3 sm:gap-4 p-4 sm:p-5"
                :class="selectMode ? 'cursor-pointer' : ''"
                @click="if(selectMode) toggleSelect('{{ $i->id }}', Number(qty) * {{ $p->price }})"
            >

                <!-- Чекбокс -->
                <div x-show="selectMode" class="flex-shrink-0 pt-1" @click.stop>
                    <div class="relative">
                        <input 
                            type="checkbox" 
                            :checked="selected.includes('{{ $i->id }}')"
                            @change="toggleSelect('{{ $i->id }}', Number(qty) * {{ $p->price }})"
                            class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 focus:ring-2 transition-all">
                    </div>
                </div>

                <!-- Фото с бейджами -->
                <div class="relative flex-shrink-0">
                    <a href="{{ route('product.show',$p) }}"
                        class="block w-20 h-20 sm:w-24 sm:h-24 rounded-xl overflow-hidden bg-gray-50 border border-gray-100 transition-all duration-200 group-hover:shadow-sm"
                        :class="selectMode ? 'opacity-60 pointer-events-none' : ''"
                    >
                        @if($p->image)
                            <img src="{{ asset('storage/'.$p->image) }}"
                                 class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                 alt="{{ $p->title }}">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-2xl text-gray-300">
                                <i class="ri-image-line"></i>
                            </div>
                        @endif
                    </a>
                    
                    <!-- Бейджи -->
                    <div class="absolute -top-1 -left-1 flex gap-1">
                        @if(isset($p->is_new) && $p->is_new)
                            <span class="bg-green-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-medium shadow-sm">New</span>
                        @endif
                        @if(isset($p->discount_percent) && $p->discount_percent)
                            <span class="bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-medium shadow-sm">-{{ $p->discount_percent }}%</span>
                        @endif
                    </div>
                </div>

                <!-- Информация -->
                <div class="flex-1 min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <!-- Название -->
                            <a href="{{ route('product.show',$p) }}"
                               class="text-base sm:text-lg font-medium text-gray-900 hover:text-indigo-600 transition-colors duration-200 line-clamp-2 break-words"
                               :class="selectMode ? 'opacity-60 pointer-events-none' : ''"
                               style="word-break: break-word; overflow-wrap: break-word;">
                                {{ $p->title }}
                            </a>
                            
                            <!-- Краткое описание -->
                            @if($p->short_description)
                                <p class="text-xs text-gray-500 mt-1 line-clamp-1">{{ $p->short_description }}</p>
                            @endif
                            
                            <!-- Характеристики -->
                            @if(($p->color ?? false) || ($p->size ?? false))
                                <div class="flex flex-wrap gap-2 mt-1 text-xs text-gray-500">
                                    @if($p->color)<span class="inline-flex items-center gap-1"><i class="ri-palette-line"></i> {{ $p->color }}</span>@endif
                                    @if($p->size)<span class="inline-flex items-center gap-1"><i class="ri-ruler-line"></i> {{ $p->size }}</span>@endif
                                </div>
                            @endif
                        </div>

                        <!-- Цена -->
                        <div class="flex-shrink-0">
                            @if(isset($p->old_price) && $p->old_price)
                                <div class="text-sm text-gray-400 line-through text-right">
                                    {{ number_format($p->old_price, 0, ',', ' ') }} ₽
                                </div>
                            @endif
                            <div class="text-xl sm:text-2xl font-semibold text-gray-900">
                                <span x-text="formatPrice(Number(qty) * {{ $p->price }})"></span> <span class="text-sm font-normal">₽</span>
                            </div>
                            <div class="text-xs text-gray-400 text-right mt-0.5">
                                {{ number_format($p->price, 2, ',', ' ') }} ₽ за шт.
                            </div>
                            @if(isset($p->old_price) && $p->old_price)
                                <div class="text-xs text-green-600 text-right">
                                    Экономия: {{ number_format($p->old_price - $p->price, 0, ',', ' ') }} ₽
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Управление -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mt-4"
                         :class="selectMode ? 'opacity-50 pointer-events-none' : ''">

                        <!-- Количество -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-500">Кол-во:</label>
                            <div class="flex items-center border border-gray-200 rounded-xl overflow-hidden bg-white">
                                <button type="button" 
                                        @click="updateQuantity('{{ route('cart.update', $i) }}', '{{ $i->id }}', Math.max(1, Number(qty) - 1), savedQty, {{ $p->price }}, $event, $data)"
                                        :disabled="updating || Number(qty) <= 1"
                                        class="w-9 h-9 hover:bg-gray-50 transition-colors disabled:opacity-50 flex items-center justify-center">
                                    <i class="ri-subtract-line text-gray-500"></i>
                                </button>
                                <input type="number" min="1" 
                                       x-model="qty"
                                       @blur="updateQuantity('{{ route('cart.update', $i) }}', '{{ $i->id }}', qty, savedQty, {{ $p->price }}, $event, $data)"
                                       class="w-14 text-center border-x border-gray-200 py-2 text-sm focus:outline-none">
                                <button type="button"
                                        @click="updateQuantity('{{ route('cart.update', $i) }}', '{{ $i->id }}', Number(qty) + 1, savedQty, {{ $p->price }}, $event, $data)"
                                        :disabled="updating"
                                        class="w-9 h-9 hover:bg-gray-50 transition-colors disabled:opacity-50 flex items-center justify-center">
                                    <i class="ri-add-line text-gray-500"></i>
                                </button>
                            </div>
                            <div x-show="updating" class="text-xs text-indigo-600">Сохранение...</div>
                        </div>

                        <!-- Действия -->
                        <div class="flex items-center gap-2">
                            <form method="POST" action="{{ route('checkout.quick',$p->id) }}" class="inline">
                                @csrf
                                <input type="hidden" name="qty" :value="qty">
                                <x-action-button size="sm">
                                    <i class="ri-bank-card-line"></i>
                                    Купить сейчас
                                </x-action-button>
                            </form>

                            <!-- Удалить - форма с перехватом -->
                            <form method="POST" action="{{ route('cart.remove', $i) }}" class="inline delete-cart-form" data-product-title="{{ addslashes($p->title) }}">
                                @csrf 
                                @method('DELETE')
                                <x-danger-action type="submit" size="icon" title="Удалить">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M4 7h16" />
                                        <path d="M10 11v6" />
                                        <path d="M14 11v6" />
                                        <path d="M6 7l1 14h10l1-14" />
                                        <path d="M9 7V4h6v3" />
                                    </svg>
                                </x-danger-action>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <aside class="hidden lg:block sticky top-24">
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5 space-y-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Сводка заказа</h2>
                    <p class="text-xs text-gray-500 mt-1" x-text="selectMode && selected.length > 0 ? 'По выбранным товарам' : 'По всей корзине'"></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="ri-receipt-line text-xl"></i>
                </div>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between text-gray-600">
                    <span>Товары</span>
                    <span class="font-semibold text-gray-900">
                        <span x-text="summaryCount"></span> шт.
                    </span>
                </div>
                <div class="flex items-center justify-between text-gray-600">
                    <span>Сумма товаров</span>
                    <span class="font-semibold text-gray-900">
                        <span x-text="formatPrice(summaryTotal)"></span> ₽
                    </span>
                </div>
                <div class="flex items-start justify-between gap-3 text-gray-600">
                    <span>Доставка</span>
                    <span class="text-right font-semibold" :class="remainingForFree <= 0 ? 'text-emerald-600' : 'text-gray-900'">
                        <span x-show="remainingForFree <= 0">Бесплатно</span>
                        <span x-show="remainingForFree > 0">рассчитается позже</span>
                    </span>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <div class="flex items-end justify-between gap-3">
                    <span class="text-sm text-gray-500">Итого</span>
                    <div class="text-2xl font-bold text-gray-900">
                        <span x-text="formatPrice(summaryTotal)"></span> <span class="text-sm font-normal">₽</span>
                    </div>
                </div>

                <div class="mt-3 rounded-xl border p-3 text-xs"
                     :class="remainingForFree <= 0 ? 'bg-emerald-50 border-emerald-100 text-emerald-700' : 'bg-indigo-50 border-indigo-100 text-indigo-700'">
                    <span x-show="remainingForFree > 0">
                        До бесплатной доставки осталось <strong x-text="formatPrice(remainingForFree) + ' ₽'"></strong>
                    </span>
                    <span x-show="remainingForFree <= 0">
                        Бесплатная доставка уже доступна для этого заказа.
                    </span>
                </div>
            </div>

            <form method="POST" action="{{ route('checkout.prepare') }}">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="selected_items[]" :value="id">
                </template>
                <x-action-button :full="true">
                    <i class="ri-bank-card-line"></i>
                    Оформить
                </x-action-button>
            </form>
        </div>
    </aside>

    </div>

    <div x-show="!selectMode"
         x-transition
         class="sm:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-xl border-t border-gray-200 shadow-2xl shadow-black/5"
         style="padding-bottom: env(safe-area-inset-bottom, 0px);">
        <div class="max-w-8xl mx-auto px-4 py-3 sm:py-4 mb-12 sm:mb-0">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-xs text-gray-500">
                        <span x-text="totalQty"></span> товара(ов)
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-gray-900">
                        <span x-text="formatPrice(cartTotal)"></span> <span class="text-sm font-normal">₽</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('checkout.prepare') }}">
                    @csrf
                    <x-action-button>
                        <i class="ri-bank-card-line"></i>
                        Оформить
                    </x-action-button>
                </form>
            </div>
        </div>
    </div>

    <!-- Футер внизу страницы для мобильных и десктопа -->
    <div x-show="selectMode && selected.length > 0" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-full"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-xl border-t border-indigo-100 shadow-2xl shadow-black/5"
         style="padding-bottom: env(safe-area-inset-bottom, 0px);">
        
        <div class="px-4 py-3 sm:py-4 mb-12 pb-10">
            <div class="max-w-7xl mx-auto">
                <!-- Мобильная версия -->
                <div class="block sm:hidden">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-indigo-100 rounded-xl flex items-center justify-center">
                                <i class="ri-checkbox-multiple-line text-indigo-600"></i>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Выбрано</div>
                                <div class="text-lg font-bold text-gray-900 leading-tight">
                                    <span x-text="selected.length"></span> <span class="text-xs font-normal">шт.</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500">Сумма</div>
                            <div class="text-lg font-bold text-indigo-600 leading-tight">
                                <span x-text="formatPrice(selectedTotal)"></span> <span class="text-xs font-normal">₽</span>
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('checkout.prepare') }}">
                        @csrf
                        <template x-for="id in selected">
                            <input type="hidden" name="selected_items[]" :value="id">
                        </template>
                        <x-action-button :full="true">
                            Оформить выбранные (<span x-text="selected.length"></span>)
                        </x-action-button>
                    </form>
                </div>
                
                <!-- Десктопная версия -->
                <div class="hidden sm:flex sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                                <i class="ri-checkbox-multiple-line text-indigo-600 text-lg"></i>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Выбрано товаров:</div>
                                <div class="text-xl font-bold text-gray-900 leading-tight">
                                    <span x-text="selected.length"></span> <span class="text-sm font-normal">шт.</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="h-8 w-px bg-gray-200"></div>
                        
                        <div>
                            <div class="text-xs text-gray-500">Сумма выбранных:</div>
                            <div class="text-xl font-bold text-indigo-600 leading-tight">
                                <span x-text="formatPrice(selectedTotal)"></span> <span class="text-sm font-normal">₽</span>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('checkout.prepare') }}">
                        @csrf
                        <template x-for="id in selected">
                            <input type="hidden" name="selected_items[]" :value="id">
                        </template>
                        <x-action-button>
                            Оформить выбранные (<span x-text="selected.length"></span>)
                        </x-action-button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- С этим также покупают (Кросс-сейл) -->
    @php
        $categoryIds = $items->pluck('product.category_id')->unique()->filter();
        $crossSellProducts = \App\Models\Product::whereNotIn('id', $items->pluck('product.id'))
            ->whereIn('category_id', $categoryIds)
            ->limit(4)
            ->get();
    @endphp
    
    @if($crossSellProducts->isNotEmpty())
    <div class="mt-12">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i class="ri-shopping-bag-3-line text-indigo-500"></i>
                С этим также покупают
            </h3>
            <a href="{{ route('home') }}" class="text-sm text-indigo-600 hover:text-indigo-700 transition-colors">Смотреть все →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($crossSellProducts as $product)
            <div class="bg-white rounded-xl border border-gray-100 p-3 hover:shadow-md transition-all duration-200 group">
                <a href="{{ route('product.show', $product) }}" class="block">
                    <div class="relative overflow-hidden rounded-lg mb-2 h-32">
                        <img src="{{ asset('storage/'.$product->image) }}" 
                             class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                             alt="{{ $product->title }}">
                    </div>
                    <h4 class="text-sm font-medium line-clamp-2 mb-1">{{ $product->title }}</h4>
                    <div class="text-indigo-600 font-bold">{{ number_format($product->price, 0, ',', ' ') }} ₽</div>
                </a>
                <form method="POST" action="{{ route('cart.add', $product->id) }}" class="mt-2">
                    @csrf
                    <x-secondary-action type="submit" :full="true" size="sm">
                        <i class="ri-shopping-cart-line"></i>
                        В корзину
                    </x-secondary-action>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Рекомендации для вас -->
    @php
        $recommendedProducts = \App\Models\Product::whereNotIn('id', $items->pluck('product.id'))
            ->inRandomOrder()
            ->limit(4)
            ->get();
    @endphp
    
    @if($recommendedProducts->isNotEmpty())
    <div class="mt-8">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i class="ri-sparkling-line text-indigo-500"></i>
                Рекомендуем для вас
            </h3>
            <a href="{{ route('home') }}" class="text-sm text-indigo-600 hover:text-indigo-700 transition-colors">Все товары →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($recommendedProducts as $product)
            <div class="bg-white rounded-xl border border-gray-100 p-3 hover:shadow-md transition-all duration-200 group">
                <a href="{{ route('product.show', $product) }}" class="block">
                    <div class="relative overflow-hidden rounded-lg mb-2 h-32">
                        <img src="{{ asset('storage/'.$product->image) }}" 
                             class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                             alt="{{ $product->title }}">
                    </div>
                    <h4 class="text-sm font-medium line-clamp-2 mb-1">{{ $product->title }}</h4>
                    <div class="text-indigo-600 font-bold">{{ number_format($product->price, 0, ',', ' ') }} ₽</div>
                </a>
                <form method="POST" action="{{ route('cart.add', $product->id) }}" class="mt-2">
                    @csrf
                    <x-secondary-action type="submit" :full="true" size="sm">
                        <i class="ri-shopping-cart-line"></i>
                        В корзину
                    </x-secondary-action>
                </form>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @endif

</div>

<script>
function cartSelection(initialTotal = 0, initialQty = 0, freeShippingThreshold = 5000) {
    return { 
        selectMode: false, 
        selected: [],
        selectedTotal: 0,
        selectedCount: 0,
        cartTotal: Number(initialTotal) || 0,
        totalQty: Number(initialQty) || 0,
        freeShippingThreshold: Number(freeShippingThreshold) || 5000,
        get remainingForFree() {
            return Math.max(0, this.freeShippingThreshold - this.cartTotal);
        },
        get freeShippingProgress() {
            return Math.min(100, Math.round((this.cartTotal / this.freeShippingThreshold) * 100));
        },
        get summaryTotal() {
            return this.selectMode && this.selected.length > 0 ? this.selectedTotal : this.cartTotal;
        },
        get summaryCount() {
            return this.selectMode && this.selected.length > 0 ? this.selected.length : this.totalQty;
        },
        
        formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(price);
        },
        
        toggleSelectMode() {
            this.selectMode = !this.selectMode;
            if(!this.selectMode) {
                this.selected = [];
                this.selectedTotal = 0;
                this.selectedCount = 0;
            }
        },
        
        toggleSelect(id, price) {
            if(this.selected.includes(id)) {
                this.selected = this.selected.filter(x => x !== id);
                this.selectedTotal -= price;
                this.selectedCount--;
            } else {
                this.selected.push(id);
                this.selectedTotal += price;
                this.selectedCount++;
            }
        },
        
        async updateQuantity(updateUrl, itemId, newQty, oldQty, price, event, itemState = null) {
            if(event) event.stopPropagation();
            newQty = Math.max(1, parseInt(newQty, 10) || 1);
            oldQty = Math.max(1, parseInt(oldQty, 10) || 1);

            if(newQty === oldQty) return;
            
            if(itemState) itemState.updating = true;
            
            try {
                const response = await fetch(updateUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ qty: newQty })
                });
                
                if(response.ok) {
                    const delta = newQty - oldQty;

                    if(itemState) {
                        itemState.qty = newQty;
                        itemState.savedQty = newQty;
                    }

                    this.cartTotal += delta * price;
                    this.totalQty += delta;

                    if(this.selected.includes(String(itemId)) || this.selected.includes(itemId)) {
                        this.selectedTotal += delta * price;
                    }
                } else {
                    if(itemState) itemState.qty = oldQty;
                    showToast(await this.errorMessageFromResponse(response), 'error');
                }
            } catch(error) {
                if(itemState) itemState.qty = oldQty;
                console.error('Error:', error);
                showToast('Ошибка при обновлении количества', 'error');
            } finally {
                if(itemState) itemState.updating = false;
            }
        },

        async errorMessageFromResponse(response) {
            const fallback = 'Ошибка при обновлении количества';

            try {
                const data = await response.json();

                if (data?.errors?.qty?.[0]) {
                    return data.errors.qty[0];
                }

                if (data?.message) {
                    return data.message;
                }
            } catch (error) {
                return fallback;
            }

            return fallback;
        },
        
        init() {
            // Nothing extra needed
        }
    }
}

// Toast notification system
function showToast(text, type = 'success') {
    if (window.showAppToast) {
        window.showAppToast(text, type);
        return;
    }

    const existing = document.querySelector('.toast');
    if (existing) existing.remove();
    
    const el = document.createElement('div');
    el.className = 'toast ' + (type === 'error' ? 'toast-error' : 'toast-success');
    el.innerHTML = `
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'error' ? 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'}"></path>
            </svg>
            <span>${text}</span>
        </div>
    `;
    document.body.appendChild(el);
    
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateX(20px)';
        setTimeout(() => el.remove(), 300);
    }, 2500);
}

// Обработка удаления товаров
document.addEventListener('DOMContentLoaded', function() {
    // Перехват отправки форм удаления
    document.querySelectorAll('.delete-cart-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const productTitle = this.dataset.productTitle;
            const card = this.closest('.cart-item');
            
            // Добавляем анимацию удаления
            if (card) {
                card.style.transition = 'all 0.3s ease-out';
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';
            }
            
            // Показываем уведомление
            showToast(`${productTitle} удалён из корзины`, 'success');
            
            // Отправляем форму через небольшую задержку (для анимации)
            setTimeout(() => {
                this.submit();
            }, 300);
        });
    });
});
</script>

<style>
.cart-item {
    transition: all 0.25s cubic-bezier(0.2, 0, 0, 1);
}

.toast {
    position: fixed;
    right: 16px;
    top: 80px;
    padding: 10px 18px;
    background: #1e293b;
    color: white;
    border-radius: 40px;
    font-size: 13px;
    font-weight: 500;
    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
    animation: slideInRight 0.3s ease;
    z-index: 99999;
    backdrop-filter: blur(8px);
    background: rgba(30, 41, 59, 0.95);
}
.toast-success {
    border-left: 3px solid #10b981;
}
.toast-error {
    background: rgba(239, 68, 68, 0.95);
    border-left: 3px solid #fecaca;
}
@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
}
</style>

</x-buyer-layout>
