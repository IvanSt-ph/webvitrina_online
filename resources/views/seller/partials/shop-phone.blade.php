{{-- resources/views/seller/partials/shop-phone.blade.php --}}
@if (Auth::user()->shop)
    <div class="relative mt-8">
        {{-- Основной контейнер с элегантным свечением --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-50/90 via-white to-purple-50/90 p-0.5 shadow-xl shadow-indigo-200/30 hover:shadow-2xl hover:shadow-indigo-300/40 transition-shadow duration-500">
            {{-- Анимированная рамка с градиентом (более плавная) --}}
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-300/30 via-purple-300/40 to-indigo-300/30 animate-gradient-slow"></div>
            
            {{-- Основной контент с улучшенным эффектом стекла --}}
            <div class="relative rounded-2xl bg-white/95 backdrop-blur-md p-7">
                {{-- Элегантные декоративные элементы --}}
                <div class="absolute -top-20 -right-20 w-64 h-64 bg-gradient-to-br from-indigo-200/20 to-purple-200/20 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-gradient-to-tr from-indigo-200/20 to-purple-200/20 rounded-full blur-3xl"></div>
                
                {{-- Тонкая верхняя линия с градиентом --}}
                <div class="absolute top-0 left-6 right-6 h-[2px] bg-gradient-to-r from-transparent via-indigo-300/50 to-transparent"></div>
                
                <div class="relative">
                    {{-- Заголовок --}}
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-6">
                        <div class="flex items-start gap-4">
                            {{-- Иконка с элегантным свечением --}}
                            <div class="shrink-0">
                                <div class="relative group">
                                    {{-- Мягкое свечение вокруг иконки --}}
                                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-xl blur-lg opacity-60 group-hover:opacity-80 transition-opacity duration-300"></div>
                                    
                                    {{-- Основная иконка с градиентом --}}
                                    <div class="relative w-14 h-14 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-xl shadow-indigo-300/50 transform group-hover:scale-105 transition-transform duration-300">
                                        <i class="ri-phone-line text-white text-2xl"></i>
                                    </div>
                                    
                                    {{-- Маленький индикатор статуса (более элегантный) --}}
                                    @if(Auth::user()->shop->is_phone_verified)
                                        <div class="absolute -top-1 -right-1 w-5 h-5 bg-gradient-to-br from-emerald-400 to-emerald-500 rounded-full border-2 border-white shadow-md flex items-center justify-center">
                                            <i class="ri-check-line text-white text-[10px]"></i>
                                        </div>
                                    @elseif(Auth::user()->shop->phone)
                                        <div class="absolute -top-1 -right-1 w-5 h-5 bg-gradient-to-br from-amber-400 to-amber-500 rounded-full border-2 border-white shadow-md flex items-center justify-center">
                                            <i class="ri-time-line text-white text-[10px]"></i>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="space-y-1.5">
                                {{-- Заголовок с градиентом и тонкой тенью --}}
                                <h3 class="text-xl font-bold bg-gradient-to-r from-indigo-600 via-indigo-600 to-purple-600 bg-clip-text text-transparent drop-shadow-sm">
                                    Телефон магазина
                                </h3>
                                
                                {{-- Описание с иконкой --}}
                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                    <i class="ri-customer-service-2-line text-indigo-400 text-base"></i>
                                    <span>Номер для связи с покупателями</span>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Статус-бейдж в стиле индиго --}}
                        @if(Auth::user()->shop->is_phone_verified)
                            <div class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-emerald-50 to-emerald-100/80 text-emerald-700 text-sm font-medium rounded-full border border-emerald-200/70 shadow-sm">
                                <div class="w-5 h-5 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-500 flex items-center justify-center">
                                    <i class="ri-check-line text-white text-xs"></i>
                                </div>
                                <span>Подтверждён</span>
                                <i class="ri-shield-check-line text-emerald-500 text-sm ml-1"></i>
                            </div>
                        @elseif(Auth::user()->shop->phone)
                            <div class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-amber-50 to-amber-100/80 text-amber-700 text-sm font-medium rounded-full border border-amber-200/70 shadow-sm">
                                <div class="w-5 h-5 rounded-full bg-gradient-to-br from-amber-400 to-amber-500 flex items-center justify-center">
                                    <i class="ri-shield-line text-white text-xs"></i>
                                </div>
                                <span>Не подтверждён</span>
                                <i class="ri-information-line text-amber-500 text-sm ml-1"></i>
                            </div>
                        @endif
                    </div>
                    
                    {{-- Контент (подключаемые файлы) --}}
                    <div class="relative">
                        @if(Auth::user()->shop->is_phone_verified)
                            @include('seller.partials.phone.verified')
                        @else
                            @include('seller.partials.phone.unverified')
                        @endif
                    </div>
                    
                    {{-- Элегантный разделитель с информацией --}}
                    <div class="mt-6 pt-4 border-t border-indigo-100/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                        <div class="flex items-center gap-2 text-gray-400">
                            <i class="ri-shield-check-line text-indigo-300 text-sm"></i>
                            <span>Номер конфиденциален и защищён</span>
                        </div>
                        
                        @if(Auth::user()->shop->phone_verified_at)
                            <div class="flex items-center gap-2 text-gray-400">
                                <i class="ri-time-line text-indigo-300"></i>
                                <span>Подтверждён <span class="text-gray-600 font-medium">{{ Auth::user()->shop->phone_verified_at->diffForHumans() }}</span></span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Добавляем ключевые кадры для плавной анимации --}}
    @push('styles')
        <style>
            @keyframes gradient-slow {
                0%, 100% { transform: translateX(0%); }
                50% { transform: translateX(100%); }
            }
            .animate-gradient-slow {
                animation: gradient-slow 4s ease infinite;
                background-size: 200% 100%;
            }
        </style>
    @endpush
@endif