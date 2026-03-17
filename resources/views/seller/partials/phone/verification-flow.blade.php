{{-- resources/views/profile/partials/phone/verification-flow.blade.php --}}
<div class="space-y-6">
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl p-5 border border-amber-200">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                <i class="ri-shield-keyhole-line text-amber-600"></i>
            </div>
            <div>
                <p class="font-medium text-gray-900">Подтвердите телефон магазина</p>
                <p class="text-sm text-gray-600 mt-1">
                    Это повысит доверие покупателей и откроет дополнительные возможности
                </p>
            </div>
        </div>
    </div>

    {{-- Форма отправки кода --}}
    <form method="POST" 
          action="{{ route('shop.phone.send') }}" 
          class="bg-gray-50 rounded-xl p-5 space-y-4"
          id="shop-phone-verify-form">
        @csrf
        <div class="space-y-3">
            <label class="block text-sm font-medium text-gray-700">
                Отправить код подтверждения на номер:
                <span class="font-semibold text-gray-900">{{ Auth::user()->shop->phone }}</span>
            </label>
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 text-sm text-blue-800">
                <div class="flex items-start gap-2">
                    <i class="ri-information-line text-blue-600 mt-0.5"></i>
                    <p>На указанный номер будет отправлен SMS с 6-значным кодом подтверждения</p>
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
                Отправить код
            </button>
            <p class="text-sm text-gray-500">Код придёт в течение минуты</p>
        </div>
    </form>

    {{-- Форма ввода кода --}}
    @if(session('shop_phone_verification_sent'))
        @include('profile.partials.phone.verify-code-form')
    @endif
</div>