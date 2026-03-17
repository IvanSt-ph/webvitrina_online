{{-- resources/views/seller/partials/phone/verification-flow.blade.php --}}
<div class="space-y-6">
    {{-- Уведомление о подтверждении --}}
    <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/80 p-0.5 shadow-lg shadow-indigo-100/20">
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-200 via-purple-200 to-indigo-200 animate-gradient-slow"></div>
        <div class="relative rounded-lg bg-white/95 backdrop-blur-sm p-5">
            <div class="flex items-start gap-4">
                <div class="shrink-0">
                    <div class="relative">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-200/50">
                            <i class="ri-shield-keyhole-line text-white text-xl"></i>
                        </div>
                        <div class="absolute -inset-1 bg-indigo-400/20 rounded-xl blur-md animate-pulse"></div>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <h4 class="text-lg font-semibold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                        Подтверждение телефона
                    </h4>
                    <p class="text-sm text-gray-600 flex items-center gap-1.5">
                        <i class="ri-shield-check-line text-indigo-400 text-sm"></i>
                        <span>Повысит доверие покупателей и откроет новые возможности</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Форма отправки кода --}}
    <form method="POST" 
          action="{{ route('shop.phone.send') }}" 
          class="relative overflow-hidden rounded-xl bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/80 p-0.5 shadow-lg shadow-indigo-100/20"
          id="shop-phone-verify-form">
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-200 via-purple-200 to-indigo-200 animate-gradient-slow"></div>
        
        <div class="relative rounded-lg bg-white/95 backdrop-blur-sm p-5">
            @csrf
            <div class="space-y-4">
                {{-- Номер телефона --}}
                <div class="flex items-center gap-3 p-3 bg-indigo-50/50 rounded-xl border border-indigo-100/70">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md">
                        <i class="ri-phone-line text-white text-base"></i>
                    </div>
                    <div>
                        <p class="text-xs text-indigo-600/70">Получатель</p>
                        <p class="text-base font-semibold text-gray-900">{{ Auth::user()->shop->phone }}</p>
                    </div>
                </div>

                {{-- Информационное сообщение --}}
                <div class="bg-indigo-50/50 rounded-xl p-4 border border-indigo-200/50">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                            <i class="ri-information-line text-indigo-600 text-sm"></i>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-indigo-900">SMS с кодом подтверждения</p>
                            <p class="text-xs text-indigo-600/70">6-значный код придёт в течение минуты</p>
                        </div>
                    </div>
                </div>
                
                {{-- Кнопка отправки --}}
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 pt-2">
<button type="submit"
        class="relative overflow-hidden group px-6 py-3 bg-indigo-500/90 hover:bg-indigo-600 
               text-white font-medium rounded-xl shadow-md hover:shadow-lg 
               transition-all duration-300 transform hover:-translate-y-0.5
               flex items-center gap-2 backdrop-blur-sm border border-indigo-400/30">
    <span class="relative z-10 flex items-center gap-2">
        <i class="ri-send-plane-line text-lg"></i>
        Отправить код
        <i class="ri-arrow-right-line text-lg opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"></i>
    </span>
    <span class="absolute inset-0 bg-indigo-600 translate-y-full 
                 group-hover:translate-y-0 transition-transform duration-300"></span>
</button>
                    <p class="text-sm text-gray-500 flex items-center gap-1">
                        <i class="ri-time-line text-indigo-300"></i>
                        Код действителен 10 минут
                    </p>
                </div>
            </div>
        </div>
    </form>

    {{-- Форма ввода кода --}}
    @if(session('shop_phone_verification_sent'))
        <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/80 p-0.5 shadow-lg shadow-indigo-100/20">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-200 via-purple-200 to-indigo-200 animate-gradient-slow"></div>
            <div class="relative rounded-lg bg-white/95 backdrop-blur-sm p-5">
                @include('seller.partials.phone.verify-code-form')
            </div>
        </div>
    @endif
</div>

{{-- Добавляем ключевые кадры для анимации --}}
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