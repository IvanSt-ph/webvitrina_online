@php
    $account = Auth::user();
    $telegramAvailable = \App\Services\TelegramPhoneVerificationService::enabled();
@endphp
<div class="space-y-4 rounded-xl border border-neutral-200 p-4" data-phone-verification>
    <h3 class="font-semibold">Личный телефон аккаунта</h3>
    <p>{{ $account->phone ?: 'Номер не указан' }}</p>
    <p role="status" data-phone-status>{{ $account->hasVerifiedPhone() ? 'Телефон подтверждён' : 'Телефон не подтверждён' }}</p>
    @if($account->role === 'seller')
        <p class="text-sm text-neutral-600">Для публикации товаров и новых продаж подтвердите email и личный телефон. Телефон магазина указывается отдельно. При смене личного номера продажи приостанавливаются до повторного подтверждения; ранее принятые заказы остаются доступны.</p>
        <form method="POST" action="{{ route('profile.update') }}" class="flex flex-wrap gap-3">
            @csrf
            @method('PATCH')
            <input type="hidden" name="profile_section" value="phone">
            <label>Личный телефон <input type="tel" name="phone" value="{{ old('phone', $account->phone) }}" placeholder="+373…" maxlength="50" class="rounded-lg border-neutral-300"></label>
            <x-secondary-action type="submit">Сохранить номер</x-secondary-action>
        </form>
    @endif
    <x-input-error :messages="$errors->get('phone')" />
    <x-input-error :messages="$errors->get('code')" />
    @if($account->role === 'buyer')
        <p class="text-sm text-neutral-600">Телефон необязателен. Для покупок достаточно подтвердить email.</p>
    @endif
    @if($account->role === 'seller' && !$account->hasVerifiedPhone())
        <p class="text-sm text-neutral-600">Сохраните номер, отправьте свой контакт в Telegram и вернитесь в этот же браузер. Затем нажмите «Завершить подтверждение». Нужен подтверждённый email. Вся заявка действует 10 минут; выход из аккаунта отменяет её.</p>
        @if($telegramAvailable && $account->phone)
            <form method="POST" action="{{ route('phone.telegram.store') }}">
                @csrf
                <x-action-button type="submit">Подтвердить через Telegram</x-action-button>
            </form>
            <button type="button" data-phone-check data-url="{{ route('phone.telegram.status') }}" class="text-sm underline">Проверить статус</button>
            <form method="POST" action="{{ route('phone.telegram.finalize') }}" data-phone-finalize hidden>
                @csrf
                <input type="hidden" name="verification_id" value="">
                <x-action-button type="submit">Завершить подтверждение</x-action-button>
            </form>
        @endif
        <p class="text-sm text-neutral-600">Нужен Telegram с тем же номером. Если Telegram недоступен или вы не хотите передавать контакт, сохраните черновики и обратитесь в поддержку. SMS временно отключено.</p>
        @if(!$telegramAvailable)
            <p class="text-sm">Подтверждение телефона временно недоступно.</p>
        @endif
    @endif
</div>
@once
<script>
async function refreshPhoneVerification(button) {
    if (!button || button.disabled) return;
    button.disabled = true;
    const container = button.closest('[data-phone-verification]');
    const status = container.querySelector('[data-phone-status]');
    const form = container.querySelector('[data-phone-finalize]');
    form.hidden = true;
    form.elements.verification_id.value = '';
    try {
        const response = await fetch(button.dataset.url, {headers: {'Accept': 'application/json'}, cache: 'no-store'});
        if (!response.ok) throw new Error('Unavailable');
        const data = await response.json();
        status.textContent = data.status === 'verified' ? 'Телефон подтверждён'
            : data.status === 'contact_verified' ? 'Контакт проверен. Завершите подтверждение в этом браузере.'
            : data.status === 'pending' ? 'Ожидание Telegram'
            : 'Активной заявки нет. Создайте новую заявку.';
        if (data.status === 'contact_verified' && Number.isSafeInteger(data.verification_id)) {
            form.elements.verification_id.value = data.verification_id;
            form.hidden = false;
        }
    } catch (_) {
        status.textContent = 'Не удалось проверить статус. Обновите страницу или повторите позже.';
    } finally {
        button.disabled = false;
    }
}
document.addEventListener('click', event => refreshPhoneVerification(event.target.closest('[data-phone-check]')));
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-phone-check]').forEach(refreshPhoneVerification);
});
</script>
@endonce
