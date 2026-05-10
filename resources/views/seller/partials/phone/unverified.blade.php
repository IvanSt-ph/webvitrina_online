{{-- resources/views/seller/partials/phone/unverified.blade.php --}}
<div class="h-full">
    <div class="h-full rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm">
        <form method="POST" action="{{ route('profile.shop.update') }}" id="shop-phone-save-form" class="h-full flex flex-col gap-4 sm:gap-5">
            @csrf
            @method('PATCH')
            <input type="hidden" name="update_type" value="phone">

            <div class="space-y-2">
                <label class="block text-sm font-semibold text-gray-900">Номер телефона магазина</label>
                <div class="relative flex items-center">
                    <input id="shop-phone-input"
                           type="tel"
                           name="phone"
                           value="{{ old('phone', Auth::user()->shop?->phone) }}"
                           placeholder="+373 777 00 000"
                           class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition"
                           required>
                    <i class="ri-phone-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
                <x-input-error :messages="$errors->get('phone')" class="mt-1 text-sm" />
            </div>

            <div class="mt-auto space-y-4">
                <div class="rounded-xl bg-indigo-50 border border-indigo-100 px-3 py-2.5 text-xs text-indigo-700 flex items-start gap-2">
                    <i class="ri-information-line text-base mt-0.5"></i>
                    <span>После изменения номера потребуется повторная SMS-верификация.</span>
                </div>

                <x-action-button :full="true">
                    <i class="ri-save-line"></i>
                    Сохранить номер
                </x-action-button>
            </div>
        </form>
    </div>
</div>
