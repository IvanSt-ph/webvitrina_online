@php
    $account = Auth::user();
    $telegramAvailable = \App\Services\TelegramPhoneVerificationService::enabled();
    $smsAvailable = config('services.twilio.enabled') && config('services.twilio.sid')
        && config('services.twilio.token') && config('services.twilio.verify_sid');
@endphp
<div class="space-y-4 rounded-xl border border-neutral-200 p-4" data-phone-verification>
    <h3 class="font-semibold">Личный телефон аккаунта</h3>
    <p>{{ $account->phone ?: 'Номер не указан' }}</p>
    <p role="status" data-phone-status>{{ $account->hasVerifiedPhone() ? 'Телефон подтверждён' : 'Телефон не подтверждён' }}</p>
    @if($account->role === 'seller')
        <p class="text-sm text-neutral-600">Этот номер относится к вашему аккаунту. Телефон магазина указывается отдельно.</p>
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
    @if(!$account->hasVerifiedPhone())
        <p class="text-sm text-neutral-600">Сначала сохраните номер с кодом страны. В Telegram отправьте свой контакт, затем вернитесь сюда. Заявка действует 10 минут.</p>
        @if($telegramAvailable && $account->phone)
            <form method="POST" action="{{ route('phone.telegram.store') }}" target="_blank" rel="noopener noreferrer">
                @csrf
                <x-action-button type="submit">Подтвердить через Telegram</x-action-button>
            </form>
            <button type="button" data-phone-check data-url="{{ route('phone.telegram.status') }}" class="text-sm underline">Проверить статус</button>
        @endif
        @if($smsAvailable && $account->phone)
            <form method="POST" action="{{ route('phone.send') }}">
                @csrf
                <x-secondary-action type="submit">Подтвердить через SMS</x-secondary-action>
            </form>
            @if(session('phone_sent') || session('phone_verification_sent'))
                <form method="POST" action="{{ route('phone.verify') }}" class="flex flex-wrap gap-3">
                    @csrf
                    <input type="text" name="code" placeholder="6-значный код" maxlength="6" inputmode="numeric" autocomplete="one-time-code" class="rounded-lg border-neutral-300" required>
                    <x-action-button type="submit">Подтвердить код</x-action-button>
                </form>
            @endif
        @endif
        @if(!$telegramAvailable && !$smsAvailable)
            <p class="text-sm">Подтверждение телефона временно недоступно.</p>
        @endif
    @endif
</div>
@once
<script>
document.addEventListener('click', async function (event) {
    const button = event.target.closest('[data-phone-check]');
    if (!button || button.disabled) return;
    button.disabled = true;
    const status = button.closest('[data-phone-verification]').querySelector('[data-phone-status]');
    try {
        const response = await fetch(button.dataset.url, {headers: {'Accept': 'application/json'}, cache: 'no-store'});
        if (!response.ok) throw new Error('Unavailable');
        const data = await response.json();
        status.textContent = data.status === 'verified' ? 'Телефон подтверждён'
            : data.status === 'pending' ? 'Ожидаем контакт в Telegram'
            : 'Активной заявки нет. Создайте новую заявку.';
    } catch (_) {
        status.textContent = 'Не удалось проверить статус. Обновите страницу или повторите позже.';
    } finally {
        button.disabled = false;
    }
});
</script>
@endonce
