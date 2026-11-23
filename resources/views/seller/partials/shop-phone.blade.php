@if ($shop = Auth::user()->shop)
    @php($phoneContact = $shop->phoneContact())
    <section class="mt-6 space-y-4 rounded-2xl border border-neutral-200 bg-white p-5">
        <h3 class="text-base font-semibold text-neutral-950">Телефон магазина</h3>
        <p class="text-sm text-neutral-600">Магазин использует личный номер вашего аккаунта.</p>
        <p class="font-semibold">{{ $phoneContact['phone'] ?: 'Не указан' }}</p>
        <p>{{ $phoneContact['verified'] ? 'Подтверждён' : 'Не подтверждён' }}</p>
        <p class="text-sm text-neutral-500">Изменить и подтвердить номер можно в разделе «Личный телефон аккаунта».</p>
        <form method="POST" action="{{ route('profile.shop.update') }}" class="space-y-3">
            @csrf
            @method('PATCH')
            <input type="hidden" name="phone_public" value="0">
            <input type="hidden" name="phone_public_for" value="{{ $phoneContact['phone'] }}">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="phone_public" value="1"
                       @checked($phoneContact['public'] && $phoneContact['verified'])
                       @disabled(!$phoneContact['verified'])>
                Показывать телефон посетителям магазина
            </label>
            <p class="text-xs text-neutral-500">Публикация доступна после подтверждения номера. При его смене разрешение сбрасывается.</p>
            <x-input-error :messages="$errors->get('phone_public')" />
            <x-input-error :messages="$errors->get('phone')" />
            <x-secondary-action type="submit">Сохранить публичность</x-secondary-action>
        </form>
    </section>
@endif
