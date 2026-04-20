<x-app-layout :title="$product->title">

    <x-slot name="specs">
        @include('components.product.specs', ['product' => $product])
    </x-slot>


    @php
        $isFav = auth()->check() && $product->isFavoritedBy(auth()->user());
    @endphp
    
    <div class="w-full max-w-[1440px] mx-auto pt-0 sm:pt-20 pb-10 px-3 sm:px-4 md:px-6 lg:px-8">

        {{-- Хлебные крошки --}}
        <x-product.breadcrumbs :product="$product" />


        {{-- Верхний блок: Галерея + Инфо + Цена --}}
        <div class="grid gap-6 lg:grid-cols-12 bg-white border rounded-3xl p-6 shadow-sm">
            <x-product.gallery :product="$product" />
            <x-product.info :product="$product" />
            <x-product.buy-box :product="$product" :isFav="$isFav" />
        </div>




  
        {{-- Карта местоположения (раскрывающаяся) --}}
        <div 
            x-data="{ openMap: false }" 
            class=" mt-6 bg-white border rounded-2xl shadow-sm">

            {{-- Заголовок + стрелка --}}
            <button 
                @click="openMap = !openMap" 
                class="w-full flex items-center justify-between p-4 text-lg font-medium">
                <span>Местоположение продавца</span>

                <svg 
                    :class="openMap ? 'rotate-90' : ''"
                    class="w-6 h-6 text-gray-600 transition-transform duration-300"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            {{-- Контейнер карты --}}
                <div class="mt-0 mr-2 ml-2 p-4" x-show="openMap" x-collapse>
                    <x-product.map :product="$product" />
                </div>


        </div>

        





        {{-- Вкладки: описание, размеры, характеристики, отзывы --}}
      <x-product.tabs :product="$product" :reviews="$reviews" :myReview="$myReview" />

        {{-- Похожие товары --}}
        <x-product.related :items="$related" />

    </div>

</x-app-layout>
