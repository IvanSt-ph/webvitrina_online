{{-- resources/views/shop/cart.blade.php --}}
<x-buyer-layout title="Моя корзина">

@php
    $unavailableItems = $unavailableItems ?? collect();
    $cartTotal = $total;
@endphp

<div x-data="cartSelection({{ $cartTotal }}, {{ $items->sum('qty') }}, {{ $freeShippingThreshold }})" x-init="init" class="cart-mobile-safe min-h-screen w-full space-y-5 overflow-x-hidden bg-white px-4 py-5 text-neutral-800 sm:space-y-6 sm:px-6 sm:py-7 lg:px-8 {{ $items->isNotEmpty() ? 'pb-44 lg:pb-8' : 'pb-24 lg:pb-8' }}">

    <header class="flex flex-col gap-4 border-b border-neutral-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                <i class="ri-shopping-cart-2-line"></i>
                Корзина
            </span>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-neutral-900 sm:text-[28px]">Моя корзина</h1>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-neutral-500">Проверьте товары, количество и итоговую сумму перед оформлением.</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex min-w-0 flex-1 items-center gap-3 rounded-xl border border-brand-100 bg-brand-50 px-4 py-2.5 sm:flex-none">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-lg text-brand-600 shadow-sm">
                    <i class="ri-shopping-bag-3-line"></i>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-brand-500">В корзине</p>
                    <p class="text-lg font-semibold leading-5 text-brand-800">
                        @if($items->isNotEmpty())
                            <span x-text="totalQty"></span> шт.
                        @else
                            0 шт.
                        @endif
                    </p>
                </div>
            </div>

            @if($items->isNotEmpty())
                <div class="shrink-0">
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

                </div>
            @else
                <x-action-button as="a" :href="route('home')">
                    <i class="ri-store-3-line"></i>
                    В каталог
                </x-action-button>
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
            class="py-14 sm:py-16"
        >
            <x-action-button as="a" :href="route('home')">
                <i class="ri-arrow-left-line"></i>
                Перейти в каталог
            </x-action-button>
        </x-empty-state>

    @else

    <!-- 🚚 Текущий режим доставки -->
    <div class="rounded-2xl border border-brand-100 bg-brand-50/70 p-4">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-brand-100 bg-white text-brand-600">
                <i class="ri-truck-line text-xl"></i>
            </div>
            <div class="flex-1">
                <div class="text-sm font-semibold text-brand-900">Доставка согласуется с продавцом</div>
                <div class="mt-1 text-xs leading-5 text-brand-700">
                    Сейчас сайт не выполняет доставку как отдельную услугу. При оформлении заказа вы выберете удобный вариант, а продавец подтвердит стоимость, срок и способ передачи товара.
                </div>
            </div>
        </div>
    </div>

    <div class="grid min-w-0 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">

    <!-- 📜 Список товаров -->
    <div class="min-w-0 space-y-3">
        @foreach($items as $i)
        @php
            $p = $i->product;
            $shortProductTitle = $p ? Str::limit($p->title, 18) : '';
            $price = (float) $i->checkout_price;
            $oldPrice = $i->checkout_old_price !== null ? (float) $i->checkout_old_price : null;
            $discountPercent = $p?->discount_percent;
        @endphp
        @continue(! $p)

        <div 
            x-data="{ qty: {{ $i->qty }}, savedQty: {{ $i->qty }}, updating: false }"
            class="cart-item group relative min-w-0 overflow-hidden rounded-2xl border bg-white transition-all duration-200 hover:border-brand-200 hover:shadow-lg hover:shadow-brand-100/40"
            :class="{
                'border-brand-300 shadow-md bg-brand-50/50': selectMode && selected.includes('{{ $i->id }}'),
                'border-neutral-200': !selectMode || !selected.includes('{{ $i->id }}')
            }"
            data-cart-id="{{ $i->id }}"
            data-cart-qty="{{ $i->qty }}"
            data-cart-price="{{ $price }}"
        >
            <div 
                class="grid min-w-0 grid-cols-[80px_minmax(0,1fr)] gap-3 p-3 sm:grid-cols-[96px_minmax(0,1fr)] sm:p-4 lg:flex lg:gap-5 lg:p-5"
                :class="selectMode ? 'cursor-pointer' : ''"
                @click="if(selectMode) toggleSelect('{{ $i->id }}', Number(qty) * {{ $price }})"
            >

                <!-- Чекбокс -->
                <div x-show="selectMode" class="col-span-2 flex-shrink-0 pt-1 lg:col-span-1" @click.stop>
                    <div class="relative">
                        <input 
                            type="checkbox" 
                            :checked="selected.includes('{{ $i->id }}')"
                            @change="toggleSelect('{{ $i->id }}', Number(qty) * {{ $price }})"
                            class="h-5 w-5 rounded border-neutral-300 text-brand-600 transition-all focus:ring-2 focus:ring-brand-500">
                    </div>
                </div>

                <!-- Фото с бейджами -->
                <div class="relative flex-shrink-0">
                    <a href="{{ route('product.show',$p) }}"
                        class="block w-20 h-20 sm:w-24 sm:h-24 rounded-xl overflow-hidden bg-gray-50 border border-gray-100 transition-all duration-200 group-hover:shadow-sm"
                        :class="selectMode ? 'opacity-60 pointer-events-none' : ''"
                    >
                        @if($p->image)
                            <img data-image-candidates="{{ json_encode($p->image_thumb_candidates) }}" data-image-fallback="{{ asset(\App\Models\Product::IMAGE_FALLBACK_ASSET) }}" src="{{ $p->image_thumb_url }}"
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
                <div class="min-w-0 lg:flex-1">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <!-- Название -->
                            <a href="{{ route('product.show',$p) }}"
                               class="line-clamp-2 break-words text-base font-medium text-neutral-900 transition-colors duration-200 hover:text-brand-600 sm:text-lg"
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
                            @if($oldPrice && $oldPrice > $price)
                                <div class="text-sm text-gray-400 line-through sm:text-right">
                                    {{ number_format($oldPrice, 0, ',', ' ') }} {{ $currencySymbol }}
                                </div>
                            @endif
                            <div class="text-xl sm:text-2xl font-semibold text-gray-900">
                                <span x-text="formatPrice(Number(qty) * {{ $price }})"></span> <span class="text-sm font-normal">{{ $currencySymbol }}</span>
                            </div>
                            <div class="text-xs text-gray-400 sm:text-right mt-0.5">
                                {{ number_format($price, 2, ',', ' ') }} {{ $currencySymbol }} за шт.
                            </div>
                            @if($oldPrice && $oldPrice > $price)
                                <div class="text-xs text-green-600 sm:text-right">
                                    Экономия: {{ number_format($oldPrice - $price, 0, ',', ' ') }} {{ $currencySymbol }}
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

                <!-- Управление: снизу на мобильном, справа на широком экране -->
                <div class="col-span-2 flex min-w-0 items-center justify-between gap-2 border-t border-neutral-100 pt-3 lg:col-span-1 lg:w-auto lg:flex-col lg:items-end lg:justify-center lg:border-t-0 lg:pt-0"
                     :class="selectMode ? 'opacity-50 pointer-events-none' : ''">

                    <!-- Количество -->
                    <div class="relative flex min-w-0 flex-col items-end gap-1">
                            <div class="flex items-center overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm">
                                <button type="button" 
                                        @click="updateQuantity('{{ route('cart.update', $i) }}', '{{ $i->id }}', Math.max(1, Number(qty) - 1), savedQty, {{ $price }}, $event, $data)"
                                        :disabled="updating || Number(qty) <= 1"
                                        aria-label="Уменьшить количество"
                                        class="flex h-10 w-9 items-center justify-center transition-colors hover:bg-neutral-50 disabled:opacity-50">
                                    <i class="ri-subtract-line text-gray-500"></i>
                                </button>
                                <input type="number" min="1" aria-label="Количество товара"
                                       x-model="qty"
                                       @blur="updateQuantity('{{ route('cart.update', $i) }}', '{{ $i->id }}', qty, savedQty, {{ $price }}, $event, $data)"
                                       class="h-10 w-10 border-x border-neutral-200 p-0 text-center text-sm focus:outline-none focus:ring-0">
                                <button type="button"
                                        @click="updateQuantity('{{ route('cart.update', $i) }}', '{{ $i->id }}', Number(qty) + 1, savedQty, {{ $price }}, $event, $data)"
                                        :disabled="updating"
                                        aria-label="Увеличить количество"
                                        class="flex h-10 w-9 items-center justify-center transition-colors hover:bg-neutral-50 disabled:opacity-50">
                                    <i class="ri-add-line text-gray-500"></i>
                                </button>
                            </div>
                            <div x-show="updating" class="hidden text-xs text-brand-600 lg:block">Сохранение...</div>
                    </div>

                    <!-- Действия -->
                    <div class="flex min-w-0 items-center gap-2">
                            <form method="POST" action="{{ route('checkout.quick',$p->id) }}" class="min-w-0">
                                @csrf
                                <input type="hidden" name="qty" :value="qty">
                                <x-action-button size="sm" class="px-3">
                                    <i class="ri-bank-card-line hidden min-[360px]:inline"></i>
                                    <span class="sm:hidden">Купить</span>
                                    <span class="hidden sm:inline">Купить сейчас</span>
                                </x-action-button>
                            </form>

                            <!-- Удалить - форма с перехватом -->
                            <form method="POST" action="{{ route('cart.remove', $i) }}" class="delete-cart-form min-w-0" data-product-title="{{ addslashes($p->title) }}" @submit.prevent="removeItem($event, '{{ $i->id }}', {{ $i->qty }}, {{ $price }})">
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
        @endforeach
    </div>

    <aside class="hidden lg:block sticky top-24">
        <div class="wv-card space-y-5 p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Сводка заказа</h2>
                    <p class="text-xs text-gray-500 mt-1" x-text="selectMode && selected.length > 0 ? 'По выбранным товарам' : 'По всей корзине'"></p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
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
                        <span x-text="formatPrice(summaryTotal)"></span> {{ $currencySymbol }}
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
                        <span x-text="formatPrice(summaryTotal)"></span> <span class="text-sm font-normal">{{ $currencySymbol }}</span>
                    </div>
                </div>

                <div class="mt-3 rounded-xl border border-brand-100 bg-brand-50 p-3 text-xs text-brand-700">
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

    <div x-cloak
         x-show="!selectMode"
         x-transition
         class="fixed bottom-[calc(60px+env(safe-area-inset-bottom,0px))] left-0 right-0 z-40 border-t border-neutral-200 bg-white/95 shadow-[0_-12px_32px_rgba(15,23,42,0.08)] backdrop-blur-xl lg:hidden">
        <div class="w-full px-4 py-3 sm:px-6">
            <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3">
                <div class="min-w-0">
                    <div class="text-xs text-gray-500">
                        <span x-text="totalQty"></span> товара(ов)
                    </div>
                    <div class="text-lg sm:text-xl font-bold text-gray-900">
                        <span x-text="formatPrice(cartTotal)"></span> <span class="text-sm font-normal">{{ $currencySymbol }}</span>
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

    <!-- Панель выбранных товаров: над мобильной навигацией, у края экрана на десктопе -->
    <div x-cloak
         x-show="selectMode && selected.length > 0"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-full"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="fixed bottom-[calc(60px+env(safe-area-inset-bottom,0px))] left-0 right-0 z-40 border-t border-brand-100 bg-white/95 shadow-[0_-12px_32px_rgba(15,23,42,0.08)] backdrop-blur-xl lg:hidden">
        
        <div class="px-4 py-3 sm:px-6 sm:py-4">
            <div class="w-full max-w-none">
                <!-- Мобильная версия -->
                <div class="block sm:hidden">
                    <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <div class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-100 min-[360px]:flex">
                                <i class="ri-checkbox-multiple-line text-brand-600"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="text-xs text-neutral-500"><span x-text="selected.length"></span> шт. выбрано</div>
                                <div class="truncate text-lg font-bold leading-tight text-brand-600">
                                    <span x-text="formatPrice(selectedTotal)"></span> <span class="text-xs font-normal">{{ $currencySymbol }}</span>
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('checkout.prepare') }}" class="shrink-0">
                            @csrf
                            <template x-for="id in selected">
                                <input type="hidden" name="selected_items[]" :value="id">
                            </template>
                            <x-action-button size="sm" class="px-3">
                                Оформить (<span x-text="selected.length"></span>)
                            </x-action-button>
                        </form>
                    </div>
                </div>
                
                <!-- Десктопная версия -->
                <div class="hidden sm:flex sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-100">
                                <i class="ri-checkbox-multiple-line text-lg text-brand-600"></i>
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
                            <div class="text-xl font-bold leading-tight text-brand-600">
                                <span x-text="formatPrice(selectedTotal)"></span> <span class="text-sm font-normal">{{ $currencySymbol }}</span>
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
    <div class="mt-6 min-w-0">
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
                        <img data-image-candidates="{{ json_encode($product->image_thumb_candidates) }}" data-image-fallback="{{ asset(\App\Models\Product::IMAGE_FALLBACK_ASSET) }}" src="{{ $product->image_thumb_url }}"
                             class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                             alt="{{ $product->title }}">
                    </div>
                    <h4 class="text-sm font-medium line-clamp-2 mb-1" style="overflow-wrap: anywhere;">{{ $product->title }}</h4>
                    <div class="text-indigo-600 font-bold">{{ number_format($product->checkout_price, 0, ',', ' ') }} {{ $currencySymbol }}</div>
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

    <!-- Рекомендации на странице корзины -->
    @if($recommendedProducts->isNotEmpty())
    <section class="min-w-0">
        <div class="mb-3 flex min-w-0 flex-wrap items-center justify-between gap-2">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-neutral-900">
                <i class="ri-sparkling-line text-brand-600" aria-hidden="true"></i>
                Вам может понравиться
            </h2>
            <a href="{{ route('home') }}" class="shrink-0 text-sm font-medium text-brand-600 transition-colors hover:text-brand-700">Все товары →</a>
        </div>
        <div class="grid grid-cols-2 gap-2.5 md:grid-cols-4 md:gap-3">
            @foreach($recommendedProducts as $product)
            <article class="group flex min-w-0 flex-col rounded-xl border border-neutral-200 bg-white p-2.5 transition-colors hover:border-brand-200 focus-within:border-brand-300 sm:p-3">
                <a href="{{ route('product.show', $product) }}" class="flex min-w-0 flex-1 flex-col rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    <div class="mb-2 flex aspect-[4/3] w-full items-center justify-center overflow-hidden rounded-lg bg-neutral-50">
                        <img data-image-candidates="{{ json_encode($product->image_thumb_candidates) }}" data-image-fallback="{{ asset(\App\Models\Product::IMAGE_FALLBACK_ASSET) }}" src="{{ $product->image_thumb_url }}"
                             class="h-full w-full object-contain p-2"
                             alt="{{ $product->title }}" loading="lazy" decoding="async">
                    </div>
                    <h3 class="mb-1 line-clamp-2 min-h-10 text-xs font-medium leading-5 text-neutral-800 sm:text-sm" style="overflow-wrap: anywhere;">{{ $product->title }}</h3>
                    <div class="mt-auto text-sm font-bold text-brand-600">{{ number_format($product->checkout_price, 0, ',', ' ') }} {{ $currencySymbol }}</div>
                </a>
                <form method="POST" action="{{ route('cart.add', $product->id) }}" class="mt-2">
                    @csrf
                    <x-secondary-action type="submit" :full="true" size="sm">
                        <i class="ri-shopping-cart-line"></i>
                        В корзину
                    </x-secondary-action>
                </form>
            </article>
            @endforeach
        </div>
    </section>
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
    window.showAppToast(text, type);
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
