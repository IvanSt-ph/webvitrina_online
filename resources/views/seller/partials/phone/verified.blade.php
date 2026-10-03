{{-- resources/views/seller/partials/phone/verified.blade.php --}}
<div x-data="{ editing: false, newPhone: '{{ Auth::user()->shop->phone ?? '' }}' }">
    <div x-show="!editing" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-medium text-gray-500">Текущий номер</p>
            <p class="mt-1 break-all text-lg font-semibold text-gray-950">
                {{ Auth::user()->shop->phone ?? 'Телефон не указан' }}
            </p>
        </div>

        <x-action-button type="button" size="sm" x-on:click="editing = true" class="self-start sm:self-auto">
            <i class="ri-pencil-line"></i>
            Изменить номер
        </x-action-button>
    </div>

    <div x-show="editing" x-transition class="rounded-xl border border-gray-200 bg-gray-50/70 p-3 sm:p-4">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h4 class="text-sm font-semibold text-gray-950">Изменить номер</h4>
                <p class="mt-0.5 text-xs text-gray-500">Новый номер потребуется подтвердить</p>
            </div>
            <button type="button"
                    @click="editing = false; newPhone = '{{ Auth::user()->shop->phone ?? '' }}'"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-500 transition hover:bg-white hover:text-gray-700"
                    aria-label="Закрыть форму изменения номера">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>

        @include('seller.partials.phone.update-form')
    </div>
</div>
