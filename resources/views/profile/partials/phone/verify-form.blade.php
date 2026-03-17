{{-- resources/views/profile/partials/phone/verify-form.blade.php --}}
<form method="POST" action="{{ route('shop.phone.send') }}" class="space-y-4" id="verify-phone-form">
    @csrf
    <div class="space-y-3">
        <label class="block text-sm font-medium text-gray-700 flex items-center gap-2">
            <i class="ri-shield-check-line text-green-600"></i>
            Верифицировать текущий номер
        </label>
        <div class="bg-amber-50 border border-amber-100 rounded-lg p-4 text-sm text-amber-800">
            <div class="flex items-start gap-2">
                <i class="ri-information-line text-amber-600 mt-0.5"></i>
                <div>
                    <p class="font-medium">После изменения номера потребуется верификация</p>
                    <p class="text-xs mt-1">На текущий номер будет отправлен SMS-код для подтверждения</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="flex items-center gap-3">
        <button type="submit"
                class="px-5 py-3 bg-gradient-to-r from-amber-500 to-orange-500 
                       hover:from-amber-600 hover:to-orange-600 text-white font-medium 
                       rounded-xl shadow-sm hover:shadow-md transition-all duration-200 
                       flex items-center gap-2">
            <i class="ri-send-plane-line"></i>
            Отправить код подтверждения
        </button>
    </div>
</form>