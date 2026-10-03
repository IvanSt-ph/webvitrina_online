{{-- resources/views/seller/partials/phone/verification-flow.blade.php --}}
<div class="space-y-3 border-t border-gray-100 pt-3">
    <form method="POST"
          action="{{ route('shop.phone.send') }}"
          id="shop-phone-verify-form"
          class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        @csrf

        <div class="flex min-w-0 items-start gap-2.5">
            <i class="ri-message-2-line mt-0.5 shrink-0 text-lg text-amber-500"></i>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900">Подтвердите номер по SMS</p>
                <p class="mt-0.5 text-xs text-gray-500">
                    Код придёт на <span class="font-semibold text-gray-700">{{ Auth::user()->shop->phone }}</span> и действует 10 минут.
                </p>
            </div>
        </div>

        <x-action-button size="sm" class="shrink-0 self-start sm:self-auto">
            <i class="ri-send-plane-line"></i>
            Отправить код
        </x-action-button>
    </form>

    @if(session('shop_phone_verification_sent'))
        @include('seller.partials.phone.verify-code-form')
    @endif
</div>
