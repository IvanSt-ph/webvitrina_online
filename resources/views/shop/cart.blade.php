{{-- resources/views/shop/cart.blade.php --}}
<x-buyer-layout title="Моя корзина">

<div x-data="cartSelection()" x-init="init" class="max-w-8xl mx-auto px-2 sm:px-6 py-4 sm:py-8">

    <!-- 🔝 Элегантный заголовок -->
    <div class="mb-6 sm:mb-10">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm">
                    <i class="ri-shopping-cart-2-line text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl sm:text-4xl font-semibold tracking-tight text-gray-900">Корзина</h1>
                    <p class="text-gray-500 text-sm mt-1">{{ $items->isNotEmpty() ? $items->sum('qty') . ' товара(ов)' : 'пусто' }}</p>
                </div>
            </div>

            @if($items->isNotEmpty())
            <div class="flex items-center gap-3 flex-wrap">
                <button type="button" @click="toggleSelectMode"
                    class="h-11 px-5 rounded-xl border border-indigo-100 bg-indigo-50 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 transition-all duration-200 flex items-center justify-center">
                    <span x-show="!selectMode" class="inline-flex items-center gap-2">
                        <i class="ri-checkbox-multiple-line"></i>
                        Выбрать
                    </span>
                    <span x-show="selectMode" class="inline-flex items-center gap-2">
                        <i class="ri-close-line"></i>
                        Отменить
                    </span>
                </button>

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

        <!-- 🕊 Пустая корзина -->
        <div class="text-center py-16 sm:py-24 px-4 bg-white border border-gray-100 rounded-xl sm:rounded-2xl shadow-sm">
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 sm:w-24 sm:h-24 bg-indigo-50 text-indigo-500 rounded-2xl">
                    <i class="ri-shopping-cart-2-line text-5xl"></i>
                </div>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Ваша корзина пуста</h3>
            <p class="text-gray-400 mb-8">Добавьте товары из каталога</p>
            <a href="{{ route('home') }}"
               class="inline-flex items-center gap-2 bg-indigo-500/90 hover:bg-indigo-600 text-white px-8 py-3 rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl">
                <i class="ri-arrow-left-line"></i>
                Перейти в каталог
            </a>
        </div>

    @else

    <!-- 📊 Прогресс бесплатной доставки -->
    @php
        $cartTotal = $items->sum(fn($i) => $i->product->price * $i->qty);
        $freeShippingThreshold = 5000;
        $remainingForFree = max(0, $freeShippingThreshold - $cartTotal);
    @endphp

    @if($remainingForFree > 0)
    <div class="bg-indigo-50/70 rounded-xl sm:rounded-2xl p-4 mb-6 border border-indigo-100">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-white text-indigo-600 flex items-center justify-center border border-indigo-100 shrink-0">
                <i class="ri-truck-line text-xl"></i>
            </div>
            <div class="flex-1">
                <div class="text-sm text-indigo-900 mb-2 font-medium">
                    Добавьте товаров на <strong class="text-indigo-700">{{ number_format($remainingForFree, 0, ',', ' ') }} ₽</strong> для бесплатной доставки
                </div>
                <div class="h-2 bg-indigo-200 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full transition-all duration-500" 
                         style="width: {{ min(100, ($cartTotal / $freeShippingThreshold) * 100) }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-indigo-600 mt-1">
                    <span>0 ₽</span>
                    <span>{{ number_format($freeShippingThreshold, 0, ',', ' ') }} ₽</span>
                </div>
            </div>
        </div>
    </div>
    @endif

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
                @click="if(selectMode) toggleSelect('{{ $i->id }}', {{ $p->price * $i->qty }})"
            >

                <!-- Чекбокс -->
                <div x-show="selectMode" class="flex-shrink-0 pt-1" @click.stop>
                    <div class="relative">
                        <input 
                            type="checkbox" 
                            :checked="selected.includes('{{ $i->id }}')"
                            @change="toggleSelect('{{ $i->id }}', {{ $p->price * $i->qty }})"
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
                                {{ number_format($p->price, 0, ',', ' ') }} <span class="text-sm font-normal">₽</span>
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
                                        :disabled="updating"
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
                                <button class="relative overflow-hidden group h-10 px-4 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2 backdrop-blur-sm border border-indigo-400/30">
                                    <span class="relative z-10 flex items-center gap-2">
                                        <i class="ri-bank-card-line"></i>
                                    Купить сейчас
                                    </span>
                                    <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
                                </button>
                            </form>

                            <!-- Удалить - форма с перехватом -->
                            <form method="POST" action="{{ route('cart.remove', $i) }}" class="inline delete-cart-form" data-product-title="{{ addslashes($p->title) }}">
                                @csrf 
                                @method('DELETE')
                                <button type="submit" class="w-10 h-10 rounded-xl border border-rose-100 bg-rose-50 text-rose-500 hover:bg-rose-100 transition-colors duration-200 flex items-center justify-center" title="Удалить">
                                    <i class="ri-delete-bin-line text-lg"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
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
                        <button type="submit"
                                class="w-full py-3 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl transition-all duration-300 shadow-lg active:scale-95">
                            Оформить выбранные (<span x-text="selected.length"></span>)
                        </button>
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
                        <button type="submit"
                                class="px-6 py-3 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl transition-all duration-300 shadow-lg hover:shadow-xl">
                            Оформить выбранные (<span x-text="selected.length"></span>)
                        </button>
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
                    <form method="POST" action="{{ route('cart.add', $product->id) }}" class="inline w-full">
                        @csrf
                        <button type="submit" class="w-full mt-2 h-9 px-3 bg-indigo-50 text-indigo-700 rounded-xl text-sm font-semibold hover:bg-indigo-100 transition-all duration-200 flex items-center justify-center gap-1.5">
                            <i class="ri-shopping-cart-line"></i>
                            В корзину
                        </button>
                    </form>
                </a>
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
                    <form method="POST" action="{{ route('cart.add', $product->id) }}" class="inline w-full">
                        @csrf
                        <button type="submit" class="w-full mt-2 h-9 px-3 bg-indigo-50 text-indigo-700 rounded-xl text-sm font-semibold hover:bg-indigo-100 transition-all duration-200 flex items-center justify-center gap-1.5">
                            <i class="ri-shopping-cart-line"></i>
                            В корзину
                        </button>
                    </form>
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @endif

</div>

<script>
function cartSelection() {
    return { 
        selectMode: false, 
        selected: [],
        selectedTotal: 0,
        selectedCount: 0,
        
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

                    if(this.selected.includes(String(itemId)) || this.selected.includes(itemId)) {
                        this.selectedTotal += delta * price;
                    }
                } else {
                    if(itemState) itemState.qty = oldQty;
                    showToast('Ошибка при обновлении количества', 'error');
                }
            } catch(error) {
                if(itemState) itemState.qty = oldQty;
                console.error('Error:', error);
                showToast('Ошибка при обновлении количества', 'error');
            } finally {
                if(itemState) itemState.updating = false;
            }
        },
        
        init() {
            // Nothing extra needed
        }
    }
}

// Toast notification system
function showToast(text, type = 'success') {
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
