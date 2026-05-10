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

        <x-action-button :full="true">
            <i class="ri-send-plane-line"></i>
            Отправить SMS код
        </x-action-button>
    </form>

    @if(session('shop_phone_verification_sent'))
        @include('seller.partials.phone.verify-code-form')
    @endif
</div>
