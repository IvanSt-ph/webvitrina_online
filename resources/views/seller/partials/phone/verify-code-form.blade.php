{{-- resources/views/seller/partials/phone/verify-code-form.blade.php --}}
<form method="POST" 
      action="{{ route('shop.phone.verify') }}" 
      class="relative overflow-hidden rounded-xl bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/80 p-0.5 shadow-lg shadow-indigo-100/20"
      x-data="{ timer: 600, formattedTime: '10:00' }"
      x-init="
        let interval = setInterval(() => {
          timer--;
          let minutes = Math.floor(timer / 60);
          let seconds = timer % 60;
          formattedTime = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
          if(timer <= 0) clearInterval(interval);
        }, 1000);
      ">
    {{-- Анимированная рамка --}}
    <div class="absolute inset-0 bg-gradient-to-r from-indigo-200 via-purple-200 to-indigo-200 animate-gradient-slow"></div>
    
    {{-- Основной контент с эффектом стекла --}}
    <div class="relative rounded-lg bg-white/95 backdrop-blur-sm p-6">
        {{-- Декоративные элементы --}}
        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-indigo-100/30 to-purple-100/30 rounded-full blur-2xl -translate-y-1/2 translate-x-1/2"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-indigo-100/20 to-purple-100/20 rounded-full blur-xl translate-y-1/2 -translate-x-1/2"></div>
        
        @csrf
        <div class="relative space-y-5">
            {{-- Заголовок с таймером --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md shadow-indigo-200/50">
                        <i class="ri-shield-keyhole-line text-white text-sm"></i>
                    </div>
                    <p class="font-semibold text-gray-900">Введите код подтверждения</p>
                </div>
                
                {{-- Таймер --}}
                <div class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50/80 rounded-full border border-indigo-200/50 shadow-sm">
                    <i class="ri-time-line text-indigo-500 text-sm"></i>
                    <span class="text-sm font-mono font-semibold text-indigo-700" x-text="formattedTime"></span>
                </div>
            </div>
            
            {{-- Поле для кода --}}
            <div class="flex items-center gap-3">
                <div class="flex-1 relative group">
                    {{-- Эффект фокуса --}}
                    <div class="absolute -inset-0.5 bg-indigo-400/20 rounded-xl opacity-0 group-focus-within:opacity-100 blur transition-opacity duration-300"></div>
                    
                    <input type="text" 
                           name="code" 
                           placeholder="000000"
                           maxlength="6"
                           autocomplete="off"
                           class="relative w-full text-center text-2xl font-bold tracking-[0.5em] py-3.5 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100/50 transition-all duration-200 outline-none"
                           required>
                </div>
                
                {{-- Кнопка подтверждения в едином стиле --}}
                <button type="submit"
                        class="relative overflow-hidden group px-5 py-3.5 bg-indigo-500/90 hover:bg-indigo-600 
                               text-white font-medium rounded-xl shadow-md hover:shadow-lg 
                               transition-all duration-300 transform hover:-translate-y-0.5
                               flex items-center gap-2 backdrop-blur-sm border border-indigo-400/30">
                    <span class="relative z-10 flex items-center gap-2">
                        <i class="ri-check-line text-lg"></i>
                        Подтвердить
                        <i class="ri-arrow-right-line text-lg opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"></i>
                    </span>
                    <span class="absolute inset-0 bg-indigo-600 translate-y-full 
                                 group-hover:translate-y-0 transition-transform duration-300"></span>
                </button>
            </div>
            
            {{-- Подсказка --}}
            <div class="flex items-center justify-center gap-2 text-xs text-gray-400">
                <i class="ri-information-line text-indigo-300"></i>
                <span>Код отправлен на ваш номер</span>
                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                <span class="flex items-center gap-1">
                    <i class="ri-mail-open-line text-indigo-300"></i>
                    Проверьте SMS
                </span>
            </div>
            
            {{-- Ссылка для повторной отправки --}}
            <div class="text-center">
                <button type="button" 
                        class="relative overflow-hidden group px-4 py-2 bg-transparent 
                               text-indigo-600 hover:text-indigo-700 font-medium rounded-lg 
                               transition-all duration-300 flex items-center gap-1 mx-auto text-xs
                               border border-indigo-200/50 hover:border-indigo-300/70 backdrop-blur-sm">
                    <span class="relative z-10 flex items-center gap-1">
                        <i class="ri-refresh-line"></i>
                        Отправить код повторно
                    </span>
                    <span class="absolute inset-0 bg-indigo-50/50 translate-y-full 
                                 group-hover:translate-y-0 transition-transform duration-300"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Стили для анимации --}}
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
</form>