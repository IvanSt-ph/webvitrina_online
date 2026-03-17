{{-- resources/views/seller/partials/phone/verify-form.blade.php --}}
<form method="POST" action="{{ route('shop.phone.send') }}" class="space-y-4" id="verify-phone-form">
    @csrf
    <div class="space-y-3">
        <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
            <i class="ri-shield-check-line text-indigo-400"></i>
            Подтверждение номера
        </label>
        
        {{-- Информационный блок --}}
        <div class="bg-indigo-50/50 rounded-xl p-4 text-sm border border-indigo-200/50">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                    <i class="ri-information-line text-indigo-600 text-sm"></i>
                </div>
                <div class="space-y-1">
                    <p class="font-medium text-indigo-800">После изменения номера потребуется верификация</p>
                    <p class="text-xs text-indigo-600/70">На текущий номер будет отправлен SMS-код для подтверждения</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex items-center gap-3 pt-2">
        {{-- Кнопка в едином стиле --}}
        <button type="submit"
                class="relative overflow-hidden group px-5 py-3 bg-indigo-500/90 hover:bg-indigo-600 
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
    </div>
</form>