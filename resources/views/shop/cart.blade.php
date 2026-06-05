{{-- resources/views/shop/cart.blade.php --}}
<x-buyer-layout title="Моя корзина">

@php
    $unavailableItems = $unavailableItems ?? collect();
    $cartTotal = $items->sum(fn($i) => $i->product ? $i->product->price * $i->qty : 0);
    $freeShippingThreshold = 5000;
@endphp

<div x-data="cartSelection({{ $cartTotal }}, {{ $items->sum('qty') }}, {{ $freeShippingThreshold }})" x-init="init" class="cart-mobile-safe wv-page-shell max-w-none overflow-x-hidden {{ $items->isNotEmpty() ? 'pb-28 sm:pb-8' : '' }}">

    <header class="wv-page-header grid lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="min-w-0">
            <span class="wv-page-eyebrow">
                <i class="ri-shopping-cart-2-line"></i>
                Корзина
            </span>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Проверьте товары перед оформлением</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                Здесь видны доступные товары, недоступные позиции и сумма заказа до перехода к подтверждению.
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">В корзине</p>
                    <p class="mt-1 text-2xl font-bold text-slate-950">
                        @if($items->isNotEmpty())
                            <span x-text="totalQty"></span>
                        @else
                            0
                        @endif
                    </p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-xl text-indigo-600 shadow-sm">
                    <i class="ri-shopping-bag-3-line"></i>
                </div>
            </div>

            @if($items->isNotEmpty())
                <div class="mt-4 grid grid-cols-2 gap-2">
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

                    <form method="POST" action="{{ route('checkout.prepare') }}" class="min-w-0">
                        @csrf
                        <x-action-button :full="true">
                            <i class="ri-bank-card-line"></i>
                            Оформить
                        </x-action-button>
                    </form>
                </div>
            @else
                <a href="{{ route('home') }}" class="mt-4 inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    <i class="ri-store-3-line"></i>
                    В каталог
                </a>
            @endif
        </div>
    </header>

    @if($unavailableItems->isNotEmpty())
        <section class="mb-6 overflow-hidden rounded-2xl border border-amber-200 bg-amber-50/70">
            <div class="border-b border-amber-100 px-4 py-3 sm:px-5">
                <h2 class="font-semibold text-amber-900">Недоступно для оформления</h2>
                <p class="mt-1 text-sm text-amber-700">Мы сохранили эти позиции в корзине. Удалите их вручную, когда решите.</p>
            </div>
            <div class="divide-y divide-amber-100">
                @foreach($unavailableItems as $item)
                    @php
                        $product = $item->product;
                    @endphp
                    <div class="flex min-w-0 items-center gap-3 px-4 py-3 sm:px-5">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white text-amber-500">
                            <i class="ri-shopping-bag-line text-xl"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ $product?->title ?? 'Товар больше недоступен' }}</p>
                            <p class="text-xs text-amber-700">
                                @if($product && $product->stock <= 0)
                                    Сейчас нет в наличии
                                @else
                                    Товар снят с продажи или удалён продавцом
                                @endif
                            </p>
                        </div>
                        <form method="POST" action="{{ route('cart.remove', $item) }}">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-xl border border-amber-200 bg-white px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-100">Удалить</button>
                        </form>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if($items->isEmpty())

        <x-empty-state
            icon="ri-shopping-cart-2-line"
            title="{{ $unavailableItems->isNotEmpty() ? 'Нет товаров для оформления' : 'Ваша корзина пуста' }}"
            description="{{ $unavailableItems->isNotEmpty() ? 'Недоступные позиции сохранены выше, но оформить их сейчас нельзя.' : 'Добавьте товары из каталога, чтобы оформить заказ.' }}"
            class="py-16 sm:py-24"
        >
            <a href="{{ route('home') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                <span class="relative z-10 flex items-center gap-2">
                    <i class="ri-arrow-left-line"></i>
                    Перейти в каталог
                </span>
                <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
            </a>
        </x-empty-state>

    @else

    <!-- 🚚 Текущий режим доставки -->
    <div class="mb-6 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-white text-indigo-600 flex items-center justify-center border border-indigo-100 shrink-0">
                <i class="ri-truck-line text-xl"></i>
            </div>
            <div class="flex-1">
                <div class="text-sm font-semibold text-indigo-900">Доставка согласуется с продавцом</div>
                <div class="mt-1 text-xs leading-5 text-indigo-700">
                    Сейчас сайт не выполняет доставку как отдельную услугу. При оформлении заказа вы выберете удобный вариант, а продавец подтвердит стоимость, срок и способ передачи товара.
                </div>
            </div>
        </div>
    </div>

    <div class="grid min-w-0 lg:grid-cols-[minmax(0,1fr)_360px] gap-6 items-start">

    <!-- 📜 Список товаров -->
    <div class="min-w-0 space-y-3" :class="selectMode && selected.length > 0 ? 'mb-36' : 'mb-0'">
        @foreach($items as $i)
        @php
            $p = $i->product;
            $shortProductTitle = $p ? Str::limit($p->title, 18) : '';
            $oldPriceData = $p?->old_price_for_current_currency;
            $oldPrice = $oldPriceData['amount'] ?? null;
            $discountPercent = $p?->discount_percent;
        @endphp
        @continue(! $p)

        <div 
            x-data="{ qty: {{ $i->qty }}, savedQty: {{ $i->qty }}, updating: false }"
            class="cart-item group relative min-w-0 overflow-hidden rounded-2xl border bg-white transition-all duration-200 hover:border-indigo-200 hover:shadow-md hover:shadow-indigo-950/5"
            :class="{
                'border-indigo-300 shadow-md bg-indigo-50/50': selectMode && selected.includes('{{ $i->id }}'),
                'border-slate-200': !selectMode || !selected.includes('{{ $i->id }}')
            }"
            data-cart-id="{{ $i->id }}"
            data-cart-qty="{{ $i->qty }}"
            data-cart-price="{{ $p->price }}"
        >
            <div 
                class="grid min-w-0 grid-cols-[80px_minmax(0,1fr)] gap-3 p-3 sm:flex sm:gap-4 sm:p-5"
                :class="selectMode ? 'cursor-pointer' : ''"
                @click="if(selectMode) toggleSelect('{{ $i->id }}', Number(qty) * {{ $p->price }})"
            >

                <!-- Чекбокс -->
                <div x-show="selectMode" class="col-span-2 flex-shrink-0 pt-1 sm:col-span-1" @click.stop>
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
                            <img src="{{ $p->image_thumb_url }}"
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
                        @if($discountPercent)
                            <span class="bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full font-medium shadow-sm">-{{ $discountPercent }}%</span>
                        @endif
                    </div>
                </div>

                <!-- Информация -->
                <div class="min-w-0 sm:flex-1">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <!-- Название -->
                            <a href="{{ route('product.show',$p) }}"
                               class="text-base sm:text-lg font-medium text-gray-900 hover:text-indigo-600 transition-colors duration-200 line-clamp-2 break-words"
                               :class="selectMode ? 'opacity-60 pointer-events-none' : ''"
                               style="word-break: break-word; overflow-wrap: anywhere;">
                                <span class="sm:hidden">{{ $shortProductTitle }}</span>
                                <span class="hidden sm:inline">{{ $p->title }}</span>
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
                        <div class="min-w-0 flex-shrink-0 sm:text-right">
                            @if($oldPrice && $oldPrice > $p->price)
                                <div class="text-sm text-gray-400 line-through sm:text-right">
                                    {{ number_format($oldPrice, 0, ',', ' ') }} ₽
                                </div>
                            @endif
                            <div class="text-xl sm:text-2xl font-semibold text-gray-900">
                                <span x-text="formatPrice(Number(qty) * {{ $p->price }})"></span> <span class="text-sm font-normal">₽</span>
                            </div>
                            <div class="text-xs text-gray-400 sm:text-right mt-0.5">
                                {{ number_format($p->price, 2, ',', ' ') }} ₽ за шт.
                            </div>
                            @if($oldPrice && $oldPrice > $p->price)
                                <div class="text-xs text-green-600 sm:text-right">
                                    Экономия: {{ number_format($oldPrice - $p->price, 0, ',', ' ') }} ₽
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Управление -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mt-4"
                         :class="selectMode ? 'opacity-50 pointer-events-none' : ''">

                        <!-- Количество -->
                        <div class="flex min-w-0 flex-wrap items-center gap-2">
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
                        <div class="grid w-full grid-cols-[minmax(0,1fr)_40px] items-center gap-2 sm:flex sm:w-auto">
                            <form method="POST" action="{{ route('checkout.quick',$p->id) }}" class="min-w-0">
                                @csrf
                                <input type="hidden" name="qty" :value="qty">
                                <x-action-button size="sm">
                                    <i class="ri-bank-card-line"></i>
                                    Купить сейчас
                                </x-action-button>
                            </form>

                            <!-- Удалить - форма с перехватом -->
                            <form method="POST" action="{{ route('cart.remove', $i) }}" class="delete-cart-form min-w-0" data-product-title="{{ addslashes($p->title) }}" @submit.prevent="removeItem($event, '{{ $i->id }}', {{ $i->qty }}, {{ $p->price }})">
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
        <div class="wv-card space-y-5 p-5">
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
                    <span class="text-right font-semibold text-gray-900">согласуется с продавцом</span>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-4">
                <div class="flex items-end justify-between gap-3">
                    <span class="text-sm text-gray-500">Итого</span>
                    <div class="text-2xl font-bold text-gray-900">
                        <span x-text="formatPrice(summaryTotal)"></span> <span class="text-sm font-normal">₽</span>
                    </div>
                </div>

                <div class="mt-3 rounded-xl border border-indigo-100 bg-indigo-50 p-3 text-xs text-indigo-700">
                    В итог ниже входит только стоимость товаров. Доставка и способ оплаты подтверждаются после создания заказа.
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
         class="fixed bottom-0 left-0 right-0 z-40 border-t border-slate-200 bg-white/95 shadow-[0_-12px_32px_rgba(15,23,42,0.08)] backdrop-blur-xl sm:hidden"
         style="padding-bottom: env(safe-area-inset-bottom, 0px);">
        <div class="w-full max-w-none px-3 py-3 sm:py-4 mb-12 sm:mb-0">
            <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3">
                <div class="min-w-0">
                    <div class="text-xs text-gray-500">
                        <span x-text="totalQty"></span> товара(ов)
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-gray-900">
                        <span x-text="formatPrice(cartTotal)"></span> <span class="text-sm font-normal">₽</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('checkout.prepare') }}" class="min-w-0">
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
         class="fixed bottom-0 left-0 right-0 z-50 border-t border-indigo-100 bg-white/95 shadow-[0_-12px_32px_rgba(15,23,42,0.08)] backdrop-blur-xl"
         style="padding-bottom: env(safe-area-inset-bottom, 0px);">
        
        <div class="px-3 py-3 sm:py-4 mb-12 pb-10">
            <div class="w-full max-w-none">
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
    @if($crossSellProducts->isNotEmpty())
    <div class="mt-12 min-w-0">
        <div class="flex min-w-0 items-center justify-between gap-3 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i class="ri-shopping-bag-3-line text-indigo-500"></i>
                С этим также покупают
            </h3>
            <a href="{{ route('home') }}" class="shrink-0 text-sm text-indigo-600 hover:text-indigo-700 transition-colors">Смотреть все →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($crossSellProducts as $product)
            <div class="min-w-0 rounded-xl border border-slate-200 bg-white p-3 transition-all duration-200 hover:border-indigo-200 hover:shadow-[0_10px_24px_rgba(15,23,42,0.06)] group">
                <a href="{{ route('product.show', $product) }}" class="block">
                    <div class="relative overflow-hidden rounded-lg mb-2 h-32">
                        <img src="{{ $product->image_thumb_url }}" 
                             class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                             alt="{{ $product->title }}">
                    </div>
                    <h4 class="text-sm font-medium line-clamp-2 mb-1" style="overflow-wrap: anywhere;">{{ $product->title }}</h4>
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
    @if($recommendedProducts->isNotEmpty())
    <div class="mt-8 min-w-0">
        <div class="flex min-w-0 items-center justify-between gap-3 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i class="ri-sparkling-line text-indigo-500"></i>
                Рекомендуем для вас
            </h3>
            <a href="{{ route('home') }}" class="shrink-0 text-sm text-indigo-600 hover:text-indigo-700 transition-colors">Все товары →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            @foreach($recommendedProducts as $product)
            <div class="min-w-0 rounded-xl border border-slate-200 bg-white p-3 transition-all duration-200 hover:border-indigo-200 hover:shadow-[0_10px_24px_rgba(15,23,42,0.06)] group">
                <a href="{{ route('product.show', $product) }}" class="block">
                    <div class="relative overflow-hidden rounded-lg mb-2 h-32">
                        <img src="{{ $product->image_thumb_url }}" 
                             class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                             alt="{{ $product->title }}">
                    </div>
                    <h4 class="text-sm font-medium line-clamp-2 mb-1" style="overflow-wrap: anywhere;">{{ $product->title }}</h4>
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
                showToast('Ошибка при обновлении количества', 'error');
            } finally {
                if(itemState) itemState.updating = false;
            }
        },

        async errorMessageFromResponse(response) {
            const fallback = 'Не удалось выполнить действие';

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

        async removeItem(event, itemId, qty, price) {
            if (event) event.stopPropagation();

            const form = event?.target;
            const card = form?.closest('.cart-item');
            const title = form?.dataset?.productTitle || 'Товар';
            const submitButton = form?.querySelector('button');
            const itemQty = Math.max(1, Number(qty) || 1);
            const itemPrice = Math.max(0, Number(price) || 0);

            if (!form || !card) return;

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'pointer-events-none');
            }

            try {
                const response = await fetch(form.action, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}',
                    },
                });

                if (!response.ok) {
                    showToast(await this.errorMessageFromResponse(response), 'error');
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.classList.remove('opacity-60', 'pointer-events-none');
                    }
                    return;
                }

                card.style.transition = 'all 0.25s ease-out';
                card.style.opacity = '0';
                card.style.transform = 'translateX(-16px)';
                card.style.maxHeight = `${card.offsetHeight}px`;

                this.cartTotal = Math.max(0, this.cartTotal - itemQty * itemPrice);
                this.totalQty = Math.max(0, this.totalQty - itemQty);

                if (this.selected.includes(String(itemId)) || this.selected.includes(itemId)) {
                    this.selected = this.selected.filter(id => String(id) !== String(itemId));
                    this.selectedTotal = Math.max(0, this.selectedTotal - itemQty * itemPrice);
                    this.selectedCount = Math.max(0, this.selectedCount - 1);
                }

                showToast(`${title} удалён из корзины`, 'success');

                setTimeout(() => {
                    card.style.maxHeight = '0';
                    card.style.marginTop = '0';
                    card.style.marginBottom = '0';
                    card.style.paddingTop = '0';
                    card.style.paddingBottom = '0';
                    setTimeout(() => card.remove(), 220);
                }, 180);
            } catch (error) {
                showToast('Не удалось удалить товар из корзины', 'error');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-60', 'pointer-events-none');
                }
            }
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
            <span></span>
        </div>
    `;
    el.querySelector('span').textContent = String(text ?? '');
    document.body.appendChild(el);
    
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateX(20px)';
        setTimeout(() => el.remove(), 300);
    }, 2500);
}

</script>

<style>
.cart-item {
    transition: all 0.25s cubic-bezier(0.2, 0, 0, 1);
}

.cart-mobile-safe,
.cart-mobile-safe * {
    box-sizing: border-box;
}

.cart-mobile-safe {
    max-width: 100vw;
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
    overflow-wrap: anywhere;
}
</style>

</x-buyer-layout>
