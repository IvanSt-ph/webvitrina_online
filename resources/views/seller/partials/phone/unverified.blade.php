{{-- resources/views/seller/partials/phone/unverified.blade.php --}}
<form method="POST" action="{{ route('profile.shop.update') }}" id="shop-phone-save-form" class="space-y-3">
    @csrf
    @method('PATCH')
    <input type="hidden" name="update_type" value="phone">

    <div>
        <label class="mb-1.5 block text-xs font-medium text-neutral-500" for="shop-phone-input">
            {{ Auth::user()->shop?->phone ? 'Текущий номер' : 'Номер телефона магазина' }}
        </label>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start">
            <div class="phone-input-shell min-w-0 flex-1">
                <input id="shop-phone-input"
                       type="tel"
                       name="phone"
                       value="{{ old('phone', Auth::user()->shop?->phone) }}"
                       placeholder="+373 777 00 000"
                       class="w-full rounded-xl border border-neutral-200 bg-white py-2.5 pr-4 transition focus:border-brand-300 focus:ring-4 focus:ring-brand-100"
                       required>
                <x-input-error :messages="$errors->get('phone')" class="mt-1 text-sm" />
            </div>

            <x-action-button size="sm" class="shrink-0 self-start">
                <i class="ri-save-line"></i>
                Сохранить номер
            </x-action-button>
        </div>
    </div>

    @if(Auth::user()->shop?->phone)
        <p class="flex items-start gap-2 text-xs text-neutral-500">
            <i class="ri-information-line mt-0.5 text-sm text-brand-500"></i>
            После изменения номера потребуется повторная SMS-верификация.
        </p>
    @endif
</form>
