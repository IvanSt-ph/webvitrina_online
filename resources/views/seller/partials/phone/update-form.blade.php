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
        <x-action-button>
            <i class="ri-save-line"></i>
            Сохранить
        </x-action-button>
    </div>
</form>
