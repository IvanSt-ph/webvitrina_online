{{-- resources/views/seller/partials/phone/verification-flow.blade.php --}}
<div class="h-full flex flex-col gap-4">
    <form method="POST"
          action="{{ route('shop.phone.send') }}"
          id="shop-phone-verify-form"
          class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 sm:p-5 flex-1 flex flex-col justify-between gap-4 sm:gap-5">
        @csrf

        <div class="flex items-start gap-4">
            <div class="w-11 h-11 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                <i class="ri-message-2-line text-xl"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-gray-950">Подтвердите номер магазина</p>
                <p class="text-sm text-gray-600 mt-1">
                    Код придёт на <span class="font-semibold text-gray-900">{{ Auth::user()->shop->phone }}</span> и будет действителен 10 минут.
                </p>
            </div>
        </div>

        <button type="submit"
                class="relative overflow-hidden group h-11 px-5 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2 w-full backdrop-blur-sm border border-indigo-400/30">
            <span class="relative z-10 flex items-center gap-2">
                <i class="ri-send-plane-line"></i>
                Отправить SMS код
            </span>
            <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
        </button>
    </form>

    @if(session('shop_phone_verification_sent'))
        @include('seller.partials.phone.verify-code-form')
    @endif
</div>
