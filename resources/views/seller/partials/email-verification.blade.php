{{-- resources/views/seller/partials/email-verification.blade.php --}}
<div class="relative mt-8 overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/80 p-0.5 shadow-lg shadow-indigo-100/20">
    {{-- Анимированная рамка-градиент --}}
    <div class="absolute inset-0 bg-gradient-to-r from-indigo-200 via-purple-200 to-indigo-200 animate-gradient-x"></div>
    
    {{-- Основной контент с эффектом стекла --}}
    <div class="relative rounded-2xl bg-white/90 backdrop-blur-xl p-6">
        {{-- Декоративные элементы --}}
        <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br from-indigo-100/30 to-purple-100/30 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-32 h-32 bg-gradient-to-tr from-indigo-100/20 to-purple-100/20 rounded-full blur-2xl translate-y-1/2 -translate-x-1/2"></div>
        
        <div class="relative flex items-start gap-5">
            {{-- Иконка с анимацией пульсации --}}
            <div class="shrink-0">
                <div class="relative">
                    @if (!Auth::user()->hasVerifiedEmail())
                        {{-- Пульсирующий фон только для неподтверждённых --}}
                        <div class="absolute inset-0 rounded-xl bg-indigo-400/20 animate-ping"></div>
                    @endif
                    
                    {{-- Основная иконка --}}
                    <div class="relative w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-200/50">
                        <i class="ri-mail-check-line text-white text-xl"></i>
                    </div>
                    
                    {{-- Индикатор статуса --}}
                    @if (Auth::user()->hasVerifiedEmail())
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-emerald-500 rounded-full border-2 border-white shadow-sm flex items-center justify-center">
                            <i class="ri-check-line text-white text-[10px]"></i>
                        </div>
                    @else
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-amber-400 rounded-full border-2 border-white shadow-sm flex items-center justify-center">
                            <i class="ri-error-warning-line text-white text-[10px]"></i>
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- Контент --}}
            <div class="flex-1 min-w-0">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        {{-- Заголовок с градиентом --}}
                        <h4 class="text-lg font-semibold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            Подтверждение email
                        </h4>
                        
                        {{-- Описание с иконками --}}
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <i class="ri-shield-check-line text-indigo-400 text-base"></i>
                            @if (Auth::user()->hasVerifiedEmail())
                                <span>Ваш email подтверждён</span>
                            @else
                                <span>Для вашей безопасности и важных уведомлений</span>
                            @endif
                        </div>
                        
                        {{-- Email пользователя --}}
                        <div class="flex items-center gap-2 mt-2">
                            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50/70 rounded-xl border border-indigo-100/50">
                                <i class="ri-mail-line text-indigo-400 text-sm"></i>
                                <span class="text-sm text-indigo-700 font-medium">{{ Auth::user()->email }}</span>
                            </div>
                            
                            {{-- Статус --}}
                            @if (Auth::user()->hasVerifiedEmail())
                                <div class="flex items-center gap-1 text-xs text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">
                                    <i class="ri-checkbox-circle-fill"></i>
                                    <span>Подтверждён</span>
                                </div>
                            @else
                                <div class="flex items-center gap-1 text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded-lg">
                                    <i class="ri-time-line"></i>
                                    <span>Ожидает подтверждения</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Кнопка с анимацией (только если не подтверждён) --}}
                    @if (!Auth::user()->hasVerifiedEmail())
                        <form method="POST" action="{{ route('verification.send') }}" class="shrink-0">
                            @csrf
                        <button type="submit"
                                class="relative overflow-hidden group px-5 py-2.5 bg-indigo-500/90 hover:bg-indigo-600 
                                    text-white font-medium rounded-xl shadow-md hover:shadow-lg 
                                    transition-all duration-300 transform hover:-translate-y-0.5
                                    flex items-center gap-2 backdrop-blur-sm border border-indigo-400/30">
                            <span class="relative z-10 flex items-center gap-2">
                                <i class="ri-mail-send-line text-lg"></i>
                                Отправить письмо
                                <i class="ri-arrow-right-line text-lg opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"></i>
                            </span>
                            <span class="absolute inset-0 bg-indigo-600 translate-y-full 
                                        group-hover:translate-y-0 transition-transform duration-300"></span>
                        </button>
                        </form>
                    @else
                        {{-- Плашка "Подтверждён" для красоты --}}
                        <div class="shrink-0 flex items-center gap-2 px-4 py-2 bg-emerald-50 rounded-xl border border-emerald-200">
                            <i class="ri-shield-check-line text-emerald-600"></i>
                            <span class="text-sm font-medium text-emerald-700">Email подтверждён</span>
                        </div>
                    @endif
                </div>
                
                {{-- Уведомление об отправке (только если не подтверждён) --}}
                @if (!Auth::user()->hasVerifiedEmail() && session('status') === 'verification-link-sent')
                    <div x-data="{ show: true }" 
                         x-show="show"
                         x-init="setTimeout(() => show = false, 5000)"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform -translate-y-2"
                         class="mt-4 overflow-hidden rounded-xl bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200/70 shadow-sm">
                        
                        <div class="relative p-3">
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-emerald-400 to-teal-400"></div>
                            <div class="flex items-start gap-3 pl-2">
                                <div class="relative shrink-0">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-400 to-teal-400 flex items-center justify-center shadow-sm">
                                        <i class="ri-check-line text-white text-base"></i>
                                    </div>
                                    <div class="absolute -inset-1 bg-emerald-400/20 rounded-lg blur-sm animate-pulse"></div>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-emerald-800">Письмо успешно отправлено! ✨</p>
                                    <p class="text-xs text-emerald-600 mt-0.5">Проверьте папку "Входящие" или "Спам"</p>
                                </div>
                                <button @click="show = false" class="shrink-0 text-emerald-400 hover:text-emerald-600">
                                    <i class="ri-close-line text-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
                
                {{-- Подсказка (только если не подтверждён) --}}
                @if (!Auth::user()->hasVerifiedEmail())
                    <div class="mt-3 flex items-center gap-2 text-xs text-gray-400">
                        <i class="ri-information-line"></i>
                        <span>Письмо придёт в течение 1-2 минут. Проверьте папку "Спам"</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        @keyframes gradient-x {
            0%, 100% { transform: translateX(0%); }
            50% { transform: translateX(100%); }
        }
        .animate-gradient-x {
            animation: gradient-x 3s ease infinite;
            background-size: 200% 100%;
        }
    </style>
@endpush