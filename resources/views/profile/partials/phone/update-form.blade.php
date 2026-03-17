{{-- resources/views/profile/partials/phone/update-form.blade.php --}}
<form method="POST" action="{{ route('profile.shop.update') }}" class="space-y-4" id="update-phone-form">
    @csrf
    @method('PATCH')
    <input type="hidden" name="update_type" value="phone">
    
    <div class="space-y-3">
        <label class="block text-sm font-medium text-gray-700">Новый номер телефона</label>
        <div class="relative">
            <input id="update-phone-input" 
                   type="tel" 
                   name="phone"
                   x-model="newPhone"
                   placeholder="+373 777 00 000"
                   class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-300 
                          focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 
                          transition-all duration-200"
                   required>
            <div class="absolute left-3 top-1/2 -translate-y-1/2 flex items-center gap-2">
                <i class="ri-phone-line text-gray-400"></i>
                <span class="text-gray-300">|</span>
            </div>
        </div>
        <p class="text-xs text-gray-500">Номер будет сохранён, но потребуется повторная верификация</p>
    </div>
    
    <div class="flex items-center gap-3">
        <button type="submit"
                class="px-5 py-3 bg-gradient-to-r from-indigo-500 to-blue-500 
                       hover:from-indigo-600 hover:to-blue-600 text-white font-medium 
                       rounded-xl shadow-sm hover:shadow-md transition-all duration-200 
                       flex items-center gap-2">
            <i class="ri-save-3-line"></i>
            Сохранить номер
        </button>
        <button type="button" 
                @click="editing = false; newPhone = '{{ Auth::user()->shop->phone ?? '' }}'"
                class="px-5 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 
                       rounded-xl hover:bg-gray-50 transition-colors">
            Отмена
        </button>
    </div>
</form>