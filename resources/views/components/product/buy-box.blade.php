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
            @else
                <a href="{{ route('login') }}"
                   class="w-full py-3.5 rounded-xl text-white bg-gradient-to-r from-indigo-600 to-fuchsia-500 block text-center">
                    Войти, чтобы купить
                </a>
            @endauth
        </div>

        {{-- Артикул + избранное --}}
        <x-product.favorite :product="$product" :isFav="$isFav" />

    </div>
</div>
