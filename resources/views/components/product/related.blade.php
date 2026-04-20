@if(!empty($items))
    <div class="mt-12 sm:mt-16">
        {{-- Заголовок --}}
        <div class="flex items-center gap-2 mb-5 sm:mb-6">
            <div class="w-1 h-6 sm:h-7 bg-gradient-to-b from-indigo-500 to-purple-500 rounded-full"></div>
            <h2 class="text-lg sm:text-2xl font-bold text-gray-800">
                Похожие товары
            </h2>
        </div>

        {{-- Сетка товаров --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5">
            @foreach ($items as $item)
                <a href="{{ route('product.show', $item->slug) }}"
                   class="group bg-white rounded-xl lg:rounded-2xl overflow-hidden border border-gray-100 hover:border-indigo-200 hover:shadow-md transition-all duration-300">
                    
                    {{-- Изображение --}}
                    <div class="relative aspect-square bg-gray-100 overflow-hidden">
                        @if ($item->image)
                            <img src="{{ asset('storage/'.$item->image) }}"
                                 class="w-full h-full object-cover transition-transform duration-500 md:group-hover:scale-105"
                                 alt="{{ $item->title }}">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                <svg class="w-8 h-8 sm:w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                        
                        {{-- Бейдж скидки --}}
                        @if(isset($item->old_price) && $item->old_price > $item->price)
                            @php
                                $discount = round((1 - $item->price / $item->old_price) * 100);
                            @endphp
                            <div class="absolute top-2 left-2 bg-red-500 text-white text-[10px] sm:text-xs font-bold px-1.5 sm:px-2 py-0.5 rounded-full">
                                -{{ $discount }}%
                            </div>
                        @endif
                        
                        {{-- Десктоп: лёгкий оверлей при hover --}}
                        <div class="absolute inset-0 bg-black/20 opacity-0 lg:group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                            <span class="bg-white text-gray-800 text-xs font-medium px-3 py-1.5 rounded-full shadow-sm">
                                Подробнее
                            </span>
                        </div>
                    </div>

                    {{-- Информация --}}
                    <div class="p-2.5 sm:p-3 lg:p-3.5">
                        {{-- Название --}}
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-800 line-clamp-2 lg:group-hover:text-indigo-600 transition-colors">
                            {{ $item->title }}
                        </h3>
                        
                        {{-- Десктоп: рейтинг (только если есть данные) --}}
                        @if(isset($item->rating) && $item->rating > 0)
                            <div class="hidden lg:flex items-center gap-1 mt-1.5">
                                <div class="flex items-center">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg class="w-3 h-3 {{ $i <= round($item->rating) ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @endfor
                                </div>
                                <span class="text-[10px] text-gray-500">{{ $item->reviews_count ?? 0 }}</span>
                            </div>
                        @endif
                        
                        {{-- Цена --}}
                        <div class="mt-1.5 flex items-baseline gap-1.5 flex-wrap">
                            <span class="text-sm sm:text-base font-bold text-indigo-600">
                                {{ number_format($item->price, 0, ',', ' ') }} ₽
                            </span>
                            @if(isset($item->old_price) && $item->old_price > $item->price)
                                <span class="text-[10px] text-gray-400 line-through">
                                    {{ number_format($item->old_price, 0, ',', ' ') }} ₽
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif