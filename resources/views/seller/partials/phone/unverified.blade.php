{{-- resources/views/profile/partials/phone/unverified.blade.php --}}
<div class="space-y-6">
    {{-- Форма сохранения/изменения номера --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-5 border border-blue-200">
        <h4 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
            <i class="ri-phone-line text-blue-600"></i>
            Настройка номера телефона
        </h4>
        
        <form method="POST" action="{{ route('profile.shop.update') }}" class="space-y-4" id="shop-phone-save-form">
            @csrf
            @method('PATCH')
            <input type="hidden" name="update_type" value="phone">
            
            <div class="space-y-3">
                <label class="block text-sm font-medium text-gray-700">Номер телефона магазина</label>
                <div class="relative">
                    <input id="shop-phone-input" 
                           type="tel" 
                           name="phone"
                           value="{{ old('phone', Auth::user()->shop?->phone) }}"
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
                <x-input-error :messages="$errors->get('phone')" class="mt-1 text-sm" />
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
                <p class="text-sm text-gray-500">После сохранения можно будет верифицировать</p>
            </div>
        </form>
    </div>

    {{-- Форма верификации (только если номер уже сохранен) --}}
    @if(Auth::user()->shop->phone)
@include('seller.partials.phone.verification-flow')
    @endif
</div>