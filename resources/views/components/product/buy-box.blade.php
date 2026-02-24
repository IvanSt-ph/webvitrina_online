<div class="lg:col-span-3 w-full max-w-xl mx-auto">
    <div class="bg-indigo-50/40 border border-indigo-100 rounded-3xl p-6 shadow-[0_18px_45px_rgba(15,23,42,0.12)] space-y-4">

        {{-- Цена --}}
        <div class="space-y-1">
            <div class="flex items-baseline gap-2">
                <div class="text-3xl font-semibold text-gray-900 leading-none">
                    {{ number_format($product->price, 0, ',', ' ') }} ₽
                </div>

                @if ($product->old_price)
                    <div class="text-sm text-gray-400 line-through">
                        {{ number_format($product->old_price, 0, ',', ' ') }} ₽
                    </div>
                @endif
            </div>

            @if ($product->old_price > $product->price)
                <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-700 border border-pink-200 mt-1">
                    -{{ round(100 - $product->price / $product->old_price * 100) }}% выгода
                </div>
            @endif
        </div>

        {{-- Доставка --}}
        <div class="mt-3 space-y-1 text-xs text-gray-600">
            <div class="flex items-center gap-2">🚚 Доставка: уточняется</div>
            <div class="flex items-center gap-2">↩️ 14 дней на возврат</div>
        </div>

        {{-- Кнопки --}}
        <div class="mt-5 flex flex-col gap-3">
            @auth
                {{-- ❗ ЗАЩИТА: свой товар --}}
                @if(auth()->id() === $product->user_id)
                    {{-- Свой товар --}}
                    <div class="bg-white/80 backdrop-blur-sm border border-amber-200 rounded-xl p-4 text-center">
                        <svg class="w-12 h-12 mx-auto text-amber-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <p class="text-gray-800 font-medium">Это ваш товар</p>
                        <p class="text-sm text-gray-500 mt-1">Вы не можете его купить</p>
                        <div class="mt-3 flex gap-2 justify-center">
                            <a href="{{ route('seller.products.edit', $product) }}" 
                               class="text-xs bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-lg hover:bg-indigo-100 transition">
                                ✏️ Редактировать
                            </a>
                        </div>
                    </div>
                @else
                    {{-- Чужой товар --}}
                    <form method="POST" action="{{ route('checkout.quick', $product->id) }}">
                        @csrf
                        <button class="w-full py-3.5 rounded-xl font-semibold text-white bg-gradient-to-r from-indigo-600 to-fuchsia-500 shadow-lg hover:to-fuchsia-600 active:scale-[0.98]">
                            ⚡ Купить сейчас
                        </button>
                    </form>

                    <form method="post" action="{{ route('cart.add', $product) }}">
                        @csrf
                        <button class="w-full py-3 rounded-xl font-medium text-gray-800 bg-white border border-gray-200 shadow-sm hover:bg-gray-50 active:scale-[0.98]">
                            🛒 В корзину
                        </button>
                    </form>
                @endif
            @else
                {{-- Не авторизован --}}
                <a href="{{ route('login') }}"
                   class="w-full py-3.5 rounded-xl text-white bg-gradient-to-r from-indigo-600 to-fuchsia-500 block text-center hover:opacity-90 transition">
                    🔑 Войти, чтобы купить
                </a>
                
                <a href="{{ route('register') }}"
                   class="w-full py-3 rounded-xl text-gray-600 bg-white border border-gray-200 block text-center hover:bg-gray-50 transition text-sm">
                    📝 Зарегистрироваться
                </a>
            @endauth
        </div>

        {{-- Артикул + избранное --}}
        <x-product.favorite :product="$product" :isFav="$isFav" />

    </div>
</div>