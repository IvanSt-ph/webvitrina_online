{{-- resources/views/seller/partials/phone/verified.blade.php --}}
<div x-data="{ editing: false, newPhone: '{{ Auth::user()->shop->phone ?? '' }}' }" class="h-full">
    <div x-show="!editing" class="h-full rounded-2xl border border-emerald-200 bg-white p-4 sm:p-5 shadow-sm">
        <div class="h-full flex flex-col justify-between gap-4 sm:gap-5">
            <div class="flex items-start gap-4 min-w-0">
                <div class="w-11 h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <i class="ri-phone-fill text-xl"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wide">Подтверждённый номер</p>
                    <p class="text-base font-semibold text-gray-950 mt-1 break-all">
                        {{ Auth::user()->shop->phone ?? 'Телефон не указан' }}
                    </p>
                    <p class="text-sm text-gray-500 mt-1">Этот номер виден покупателям как контакт магазина.</p>
                </div>
            </div>

            <button type="button"
                    @click="editing = true"
                    class="relative overflow-hidden group h-11 px-5 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2 w-full backdrop-blur-sm border border-indigo-400/30">
                <span class="relative z-10 flex items-center gap-2">
                    <i class="ri-pencil-line"></i>
                    Изменить номер
                </span>
                <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
            </button>
        </div>
    </div>

    <div x-show="editing"
         x-transition
         class="h-full rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-sm space-y-4 sm:space-y-5">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h4 class="text-sm font-semibold text-gray-950">Изменить номер телефона</h4>
                <p class="text-xs text-gray-500 mt-0.5">Новый номер нужно будет подтвердить</p>
            </div>
            <button type="button"
                    @click="editing = false; newPhone = '{{ Auth::user()->shop->phone ?? '' }}'"
                    class="w-9 h-9 rounded-lg bg-gray-50 hover:bg-gray-100 text-gray-500 transition flex items-center justify-center">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>

        @include('seller.partials.phone.update-form')
    </div>
</div>
