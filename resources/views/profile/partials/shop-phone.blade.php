{{-- resources/views/profile/partials/shop-phone.blade.php --}}
<div class="border-t border-gray-100 pt-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i class="ri-phone-line text-gray-400"></i>
                Телефон магазина
            </h3>
            <p class="text-sm text-gray-500 mt-1">Номер для связи с покупателями</p>
        </div>
        @if(Auth::user()->shop->is_phone_verified)
            <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-medium rounded-full 
                       flex items-center gap-1.5">
                <i class="ri-checkbox-circle-fill"></i> Подтверждён
            </span>
        @elseif(Auth::user()->shop->phone)
            <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-sm font-medium rounded-full 
                       flex items-center gap-1.5">
                <i class="ri-shield-line"></i> Не подтверждён
            </span>
        @endif
    </div>

    {{-- Статус верификации --}}
    @if(Auth::user()->shop->is_phone_verified)
        @include('profile.partials.phone.verified')
    @else
        @include('profile.partials.phone.unverified')
    @endif
</div>