{{-- resources/views/seller/partials/phone/verify-form.blade.php --}}
<form method="POST" action="{{ route('shop.phone.send') }}" class="space-y-4 rounded-xl border border-brand-100 bg-brand-50/70 p-4" id="verify-phone-form">
    @csrf

    <div>
        <p class="flex items-center gap-2 text-sm font-semibold text-neutral-950">
            <i class="ri-shield-check-line text-brand-600"></i>
            Подтверждение номера
        </p>
        <p class="mt-1 text-xs text-neutral-600">После изменения номера отправьте SMS-код для подтверждения.</p>
    </div>

    <x-action-button :full="true">
        <i class="ri-send-plane-line"></i>
        Отправить код
    </x-action-button>
</form>
