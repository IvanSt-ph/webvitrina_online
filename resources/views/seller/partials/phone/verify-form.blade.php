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

    <button type="submit"
            class="relative overflow-hidden group h-11 px-5 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2 w-full backdrop-blur-sm border border-indigo-400/30">
        <span class="relative z-10 flex items-center gap-2">
            <i class="ri-send-plane-line"></i>
            Отправить код
        </span>
        <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
    </button>
</form>
