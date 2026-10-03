{{-- resources/views/seller/partials/phone/update-form.blade.php --}}
<form method="POST" action="{{ route('profile.shop.update') }}" class="space-y-3" id="update-phone-form">
    @csrf
    @method('PATCH')
    <input type="hidden" name="update_type" value="phone">

    <div>
        <label class="mb-1.5 block text-xs font-medium text-gray-600" for="update-phone-input">Новый номер телефона</label>
        <div class="phone-input-shell">
            <input id="update-phone-input"
                   type="tel"
                   name="phone"
                   x-model="newPhone"
                   placeholder="+373 777 00 000"
                   class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pr-4 shadow-sm transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                   required>
        </div>
    </div>

    <p class="flex items-start gap-2 text-xs text-gray-500">
        <i class="ri-information-line mt-0.5 text-sm text-indigo-500"></i>
        После сохранения подтвердите новый номер через SMS.
    </p>

    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <button type="button"
                @click="editing = false; newPhone = '{{ Auth::user()->shop->phone ?? '' }}'"
                class="h-10 rounded-xl border border-gray-200 bg-white px-4 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
            Отмена
        </button>
        <x-action-button size="sm">
            <i class="ri-save-line"></i>
            Сохранить
        </x-action-button>
    </div>
</form>
