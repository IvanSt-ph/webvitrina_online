@props(['p', 'showDescription' => false])

@php
    $url = route('product.show', $p->slug ?? $p->id);

    $image = $p->main_image ?? $p->image ?? ($p->gallery[0] ?? null);
    if ($image && !str_starts_with($image, 'http')) {
        $image = asset('storage/' . $image);
    }

    $price   = $p->price ?? 0;
    $oldPrice = $p->old_price ?? null;
    $hasDiscount = $oldPrice && $oldPrice > $price;
    $discountPercent = $hasDiscount ? round(100 - ($price * 100 / $oldPrice)) : null;

    $avg = round($p->reviews->avg('rating'), 1);
    $rating = $avg > 0 ? $avg : null;

    $city     = $p->city->name ?? null;
    $country  = $p->city->country->name ?? $p->country->name ?? null;
    $category = $p->category->name ?? null;

    $isFav = auth()->check() && $p->isFavoritedBy(auth()->user());
    $currencySymbol = session('currency_symbol', '₽');
@endphp

<div class="relative bg-white rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 
            transition-all duration-300 border border-gray-100 flex flex-col overflow-hidden group">

    {{-- ❤️ Сердце — убран белый фон, чистая кнопка --}}
<button 
    type="button"
    x-data="{
        active: {{ $isFav ? 'true' : 'false' }},
        anim: '',
        toggleFavorite() {
            fetch('{{ route('favorites.toggle', $p) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(() => {
                this.anim = this.active ? 'implode' : 'explode';
                this.active = !this.active;
                setTimeout(() => this.anim = '', 600);
            });
        }
    }"
    @click="toggleFavorite()"
    class="absolute top-2 right-2 z-[60] p-1.5 transition"
>

<svg class="w-6 h-6 transition-transform duration-300"
     :class="active ? 'scale-110' : 'scale-100'"
     :fill="active ? '#74bdfd' : 'transparent'"
     :stroke="active ? 'none' : '#74bdfd'"
     stroke-width="1.7"
     viewBox="0 0 24 24">
    <path d="M12 21.35l-1.45-1.32C5.4 15.36 
             2 12.28 2 8.5 2 5.42 4.42 3 
             7.5 3c1.74 0 3.41 0.81 
             4.5 2.09C13.09 3.81 
             14.76 3 16.5 3c3.08 0 5.5 2.42 5.5 5.5 
             0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
</svg>



    {{-- частицы --}}
    <template x-if="anim === 'explode'">
        <div>
            <template x-for="i in 8">
                <div class="dot" :style="'--i:' + i"></div>
            </template>
        </div>
    </template>

    <template x-if="anim === 'implode'">
        <div>
            <template x-for="i in 8">
                <div class="dot dot-in" :style="'--i:' + i"></div>
            </template>
        </div>
    </template>

</button>


    {{-- Фото --}}
    <div class="relative aspect-[3/3.5] bg-gray-50 overflow-hidden flex items-center justify-center">
        @if($image)
            <img src="{{ $image }}" alt="{{ $p->title }}" loading="lazy"
                 class="w-full h-full object-cover transition duration-500 ease-out
                        group-hover:scale-105 group-hover:brightness-[.85] group-hover:blur-[1px]" />
        @else
            <span class="text-gray-400">Нет фото</span>
        @endif

        {{-- Кнопка "Подробнее" --}}
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <a href="{{ $url }}"
               class="slide-up px-4 py-2 text-sm font-medium bg-black/40 text-white rounded-lg
                      shadow hover:bg-black/80 transition pointer-events-auto">
                Подробнее
            </a>
        </div>

        {{-- Нижняя панель --}}
        <div class="absolute bottom-0 left-0 right-0 bg-black/40 text-white backdrop-blur-sm
                    text-[13px] py-2 px-3 flex flex-col items-start opacity-0 translate-y-full 
                    group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-500 ease-out">

            @if($city || $country)
                <div class="flex items-center gap-1">
              <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" 
                        d="M12 21c4.8-4.9 8-8.4 8-11.5a8 8 0 1 0-16 0c0 3.1 3.2 6.6 8 11.5z" />
                  <circle cx="12" cy="10" r="2.5" fill="currentColor"/>
              </svg>

                    <span>{{ $city ?? '—' }}{{ $country ? ', '.$country : '' }}</span>
                </div>
            @endif

            @if($category)
                <div class="flex items-center gap-1 mt-0.5">
<svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z"/>
</svg>



                    <span>{{ $category }}</span>
                </div>
            @endif

        </div>
    </div>

    {{-- Контент --}}
    <div class="p-2 flex flex-col flex-1">

        {{-- Магазин --}}
@if($p->seller && $p->seller->shop && $p->seller->shop->name)
    <a href="{{ route('seller.show', $p->seller->id) }}"
       class="text-[12px] text-gray-500 truncate hover:text-[#74bdfd] transition mb-1">
        {{ $p->seller->shop->name }}
    </a>
@else
    <p class="text-[12px] text-gray-300 truncate mb-1">Магазин</p>
@endif


        {{-- 🔥 Название + Цена — адаптивно --}}
        <div class="flex flex-col mb-1 md:flex-row md:items-center md:justify-between md:gap-2">

            {{-- Название --}}
            <h3 class="text-[13px] text-neutral-800 font-medium truncate mb-0.5 md:mb-0 md:max-w-[60%]">
                {{ $p->title }}
            </h3>

            {{-- Цена --}}
            <div class="text-[15px] font-semibold text-neutral-800 whitespace-nowrap">
                {{ number_format($price, 0, ',', ' ') }} {{ $currencySymbol }}
            </div>

        </div>



        {{-- Скидка --}}
        @if($hasDiscount)
            <div class="flex items-center gap-2 mb-1">
                <span class="line-through text-gray-400 text-sm">
                    {{ number_format($oldPrice, 0, ',', ' ') }} {{ $currencySymbol }}
                </span>
                <span class="text-red-500 text-xs font-semibold">
                    -{{ $discountPercent }}%
                </span>
            </div>
        @endif

        {{-- ⭐ Рейтинг --}}
        <div class="flex items-center gap-1 mb-4">
            @for($i = 1; $i <= 5; $i++)
                <svg class="w-3.5 h-3.5 {{ $i <= $avg ? 'text-yellow-400' : 'text-gray-300' }}"
                     fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.729 1.516 8.234L12 18.896l-7.452 4.373 
                             1.516-8.234L0 9.306l8.332-1.151z"/>
                </svg>
            @endfor
            <span class="text-xs text-gray-400 ml-1">{{ $rating ? $rating.'/5' : '—' }}</span>
        </div>

        {{-- 🔘 Две кнопки — уменьшенная высота --}}
<div class="mt-auto flex flex-col w-full gap-[2px]">

    {{-- В корзину --}}
    <form method="post" action="{{ route('cart.add', $p) }}" class="w-full mb-1">
        @csrf
        <button type="submit"
            class="w-full py-0.5 text-sm font-medium bg-white border border-gray-300 
                   rounded-lg hover:bg-[#f1f8ff] text-gray-800 active:scale-[0.98] transition">
            В корзину
        </button>
    </form>

    {{-- Купить сейчас --}}
<form method="POST" action="{{ route('checkout.quick', $p->id) }}" class="w-full">
    @csrf
    <button 
        class="w-full py-0.5 text-sm font-medium bg-[#74bdfd] text-white rounded-lg
               hover:bg-[#5aa8e5] active:scale-[0.98] transition">
        Купить сейчас
    </button>
</form>


</div>



    </div>
</div>

<style>
    .slide-up { transform: translateY(10px); opacity: 0; transition: 0.4s; }
    .group:hover .slide-up { transform: translateY(0); opacity: 1; }

    .dot {
        position: absolute; top: 50%; left: 50%;
        width: 6px; height: 6px; background: #74bdfd;
        border-radius: 50%; opacity: 0;
        animation: burst 0.6s ease-out forwards;
    }
    @keyframes burst {
        0%   { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        100% { transform: rotate(calc(var(--i)*45deg)) translate(-50%, -40px) scale(.3); opacity: 0; }
    }

    .dot-in {
        position: absolute; top: 50%; left: 50%;
        width: 6px; height: 6px; background: #74bdfd;
        border-radius: 50%; opacity: 0;
        animation: collapse 0.6s ease-in forwards;
    }
    @keyframes collapse {
        0%   { transform: rotate(calc(var(--i)*45deg)) translate(-50%, -40px) scale(.3); opacity: 1; }
        100% { transform: translate(-50%, -50%) scale(1); opacity: 0; }
    }

    .pulse {
        animation: pulseGlow 2s ease-in-out infinite;
    }
    @keyframes pulseGlow {
        0%,100% { filter: drop-shadow(0 0 0 #74bdfd); transform: scale(1); }
        50%     { filter: drop-shadow(0 0 6px #74bdfd); transform: scale(1.08); }
    }
</style>
