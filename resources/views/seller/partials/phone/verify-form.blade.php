{{-- resources/views/seller/partials/phone/verify-form.blade.php --}}
<form method="POST" action="{{ route('shop.phone.send') }}" class="rounded-xl border border-indigo-100 bg-indigo-50/70 p-4 space-y-4" id="verify-phone-form">
    @csrf

    <div>
        <p class="text-sm font-semibold text-gray-950 flex items-center gap-2">
            <i class="ri-shield-check-line text-indigo-600"></i>
            Подтверждение номера
        </p>
        <p class="text-xs text-gray-600 mt-1">После изменения номера отправьте SMS-код для подтверждения.</p>
    </div>

    <x-action-button :full="true">
        <i class="ri-send-plane-line"></i>
        Отправить код
    </x-action-button>
</form>
