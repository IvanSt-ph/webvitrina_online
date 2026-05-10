{{-- resources/views/seller/partials/phone/update-form.blade.php --}}
<form method="POST" action="{{ route('profile.shop.update') }}" class="space-y-5" id="update-phone-form">
    @csrf
    @method('PATCH')
    <input type="hidden" name="update_type" value="phone">

    <div class="space-y-2">
        <label class="block text-sm font-semibold text-gray-900">Новый номер телефона</label>
        <div class="relative flex items-center">
            <input id="update-phone-input"
                   type="tel"
                   name="phone"
                   x-model="newPhone"
                   placeholder="+373 777 00 000"
                   class="w-full pl-11 pr-4 py-3 rounded-xl border border-gray-300 bg-slate-50/70 shadow-sm focus:bg-white focus:ring-4 focus:ring-indigo-100 focus:border-indigo-500 transition"
                   required>
            <i class="ri-phone-line absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
        </div>
    </div>

    <div class="rounded-xl bg-indigo-50 border border-indigo-100 px-3 py-2.5 text-xs text-indigo-700 flex items-start gap-2">
        <i class="ri-information-line text-base mt-0.5"></i>
        <span>Номер сохранится сразу, а подтверждение можно выполнить через SMS справа.</span>
    </div>

    <div class="grid grid-cols-2 gap-2">
        <button type="button"
                @click="editing = false; newPhone = '{{ Auth::user()->shop->phone ?? '' }}'"
                class="h-11 px-4 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-xl border border-gray-200 shadow-sm transition">
            Отмена
        </button>
        <button type="submit"
                class="relative overflow-hidden group h-11 px-4 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2 backdrop-blur-sm border border-indigo-400/30">
            <span class="relative z-10 flex items-center gap-2">
                <i class="ri-save-line"></i>
                Сохранить
            </span>
            <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
        </button>
    </div>
</form>
