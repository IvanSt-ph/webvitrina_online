{{-- resources/views/seller/partials/phone/unverified.blade.php --}}
<div class="space-y-6">
    {{-- Форма сохранения/изменения номера --}}
    <div class="bg-gradient-to-br from-indigo-50/80 via-white to-purple-50/80 rounded-xl p-5 border border-indigo-100/50 shadow-sm">
        <h4 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-sm">
                <i class="ri-phone-line text-white text-xs"></i>
            </div>
            Настройка номера телефона
        </h4>
        
        <form method="POST" action="{{ route('profile.shop.update') }}" class="space-y-4" id="shop-phone-save-form">
            @csrf
            @method('PATCH')
            <input type="hidden" name="update_type" value="phone">
            
            <div class="space-y-3">
                <label class="block text-sm font-medium text-gray-700">Номер телефона магазина</label>
                <div class="relative group">
                    {{-- Эффект фокуса --}}
                    <div class="absolute -inset-0.5 bg-indigo-400/20 rounded-xl opacity-0 group-focus-within:opacity-100 blur transition-opacity duration-300"></div>
                    
                    <div class="relative flex items-center">
                        <div class="absolute left-3 top-1/2 -translate-y-1/2">
                            <i class="ri-phone-line text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                        </div>
                        <input id="shop-phone-input" 
                               type="tel" 
                               name="phone"
                               value="{{ old('phone', Auth::user()->shop?->phone) }}"
                               placeholder="+373 777 00 000"
                               class="w-full pl-10 pr-4 py-3.5 rounded-xl border border-gray-200 bg-white/80 backdrop-blur-sm
                                      focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100/50 
                                      transition-all duration-200 outline-none"
                               required>
                    </div>
                </div>
                <x-input-error :messages="$errors->get('phone')" class="text-xs text-rose-600" />
            </div>
            
            <div class="flex items-center gap-3 pt-2">
                {{-- Кнопка сохранения в едином стиле --}}
                <button type="submit"
                        class="relative overflow-hidden group px-5 py-3 bg-indigo-500/90 hover:bg-indigo-600 
                               text-white font-medium rounded-xl shadow-md hover:shadow-lg 
                               transition-all duration-300 transform hover:-translate-y-0.5
                               flex items-center gap-2 backdrop-blur-sm border border-indigo-400/30">
                    <span class="relative z-10 flex items-center gap-2">
                        <i class="ri-save-3-line text-lg"></i>
                        Сохранить номер
                        <i class="ri-arrow-right-line text-lg opacity-0 -translate-x-2 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-300"></i>
                    </span>
                    <span class="absolute inset-0 bg-indigo-600 translate-y-full 
                                 group-hover:translate-y-0 transition-transform duration-300"></span>
                </button>
                
                <p class="text-sm text-gray-500 flex items-center gap-1">
                    <i class="ri-information-line text-indigo-300"></i>
                    После сохранения можно верифицировать
                </p>
            </div>
        </form>
    </div>

    {{-- Форма верификации (только если номер уже сохранен) --}}
    @if(Auth::user()->shop->phone)
        @include('seller.partials.phone.verification-flow')
    @endif
</div>