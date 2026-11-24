@php
    $account = Auth::user();
    $telegramAvailable = \App\Services\TelegramPhoneVerificationService::enabled();
@endphp
<div class="{{ $account->role === 'seller' ? 'h-full w-full min-w-0 space-y-5 rounded-2xl border border-neutral-200 bg-white p-4 shadow-sm sm:p-6' : 'space-y-4 rounded-xl border border-neutral-200 p-4' }}" data-phone-verification>
    @if($account->role === 'seller')
        <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <i class="ri-phone-line text-xl" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <h3 class="text-lg font-semibold tracking-tight text-neutral-950">Основной телефон аккаунта</h3>
                <p class="mt-1 text-sm leading-6 text-neutral-500">Этот номер используется для аккаунта, подтверждения продавца и связи по магазину</p>
            </div>
        </div>
        <div class="flex max-w-xl flex-wrap items-center gap-x-3 gap-y-2">
            <p data-account-phone-display class="min-w-0 break-words text-2xl font-semibold tracking-tight text-neutral-950">{{ $account->phone ?: 'Номер не указан' }}</p>
            <p role="status" data-phone-status class="inline-flex max-w-full items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $account->phone && $account->hasVerifiedPhone() ? 'bg-emerald-50 text-emerald-700' : 'bg-neutral-100 text-neutral-600' }}">
                {{ $account->phone && $account->hasVerifiedPhone() ? 'Подтверждён' : ($account->phone ? 'Не подтверждён' : 'Добавьте номер телефона') }}
            </p>
        </div>
        <form method="POST" action="{{ route('profile.update') }}" class="flex max-w-xl flex-wrap items-end gap-3 border-t border-neutral-100 pt-5"
              x-data x-init="$nextTick(() => initSellerAccountPhone($el))">
            @csrf
            @method('PATCH')
            <input type="hidden" name="profile_section" value="phone">
            <label class="block w-full min-w-0 sm:max-w-xs sm:basis-64 sm:grow">
                <span class="mb-2 block text-sm font-medium text-neutral-700">Изменить номер телефона</span>
                <span class="phone-input-shell block min-w-0">
                    <input type="tel" name="phone" value="{{ old('phone', $account->phone) }}" placeholder="+373…" maxlength="50"
                           autocomplete="tel" data-intl-manual="true"
                           class="h-11 w-full min-w-0 rounded-xl border-neutral-200 bg-white py-2.5 pr-3 focus:border-brand-400 focus:ring-brand-100">
                </span>
            </label>
            <x-action-button type="submit" class="w-full shrink-0 sm:w-auto">Сохранить номер</x-action-button>
        </form>
        <p class="text-xs leading-5 text-neutral-500">Для публикации товаров и новых продаж нужны подтверждённые email и телефон. При смене номера продажи приостанавливаются до повторного подтверждения; ранее принятые заказы остаются доступны.</p>
    @else
        <h3 class="font-semibold">Личный телефон аккаунта</h3>
        <p>{{ $account->phone ?: 'Номер не указан' }}</p>
        <p role="status" data-phone-status>{{ $account->hasVerifiedPhone() ? 'Телефон подтверждён' : 'Телефон не подтверждён' }}</p>
    @endif
    <x-input-error :messages="$errors->get('phone')" />
    <x-input-error :messages="$errors->get('code')" />
    @if($account->role === 'buyer')
        <p class="text-sm text-neutral-600">Телефон необязателен. Для покупок достаточно подтвердить email.</p>
    @endif
    @if($account->role === 'seller' && !$account->hasVerifiedPhone())
        <p class="text-sm text-neutral-600">Сохраните номер, отправьте свой контакт в Telegram и вернитесь в этот же браузер. Затем нажмите «Завершить подтверждение». Нужен подтверждённый email. Вся заявка действует 10 минут; выход из аккаунта отменяет её.</p>
        @if($telegramAvailable && $account->phone)
            <form method="POST" action="{{ route('phone.telegram.store') }}" target="_blank" rel="noopener noreferrer" data-phone-start>
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
function initSellerAccountPhone(form) {
    const input = form.querySelector('input[type="tel"]');
    if (!input || input.dataset.intlInitialized) return;
    if (!window.initializePhoneInput) {
        document.addEventListener('DOMContentLoaded', () => initSellerAccountPhone(form), {once: true});
        return;
    }
    return window.initializePhoneInput(input, () => {
        const phone = window.intlTelInput(input, {
            initialCountry: 'md', countryOrder: ['md', 'ua', 'ro', 'ru'],
            separateDialCode: true, autoPlaceholder: 'aggressive',
            formatAsYouType: true, strictMode: true, showFlags: true,
            dropdownContainer: document.body,
            loadUtils: window.loadIntlTelInputUtils,
        });
        phone.promise.then(() => {
            const display = form.closest('[data-phone-verification]').querySelector('[data-account-phone-display]');
            const utils = window.intlTelInput.utils;
            if (display && utils && display.textContent.trim().startsWith('+')) {
                display.textContent = utils.formatNumber(display.textContent.trim(), '', utils.numberFormat.INTERNATIONAL);
            }
    }).catch(() => {}); // Keep the server-rendered number if formatting is unavailable.
    form.addEventListener('submit', () => {
        const fullNumber = phone.getNumber();
        if (fullNumber) input.value = fullNumber;
    });
    return phone;
    }).catch(() => window.showSiteToast?.('Не удалось загрузить поле телефона. Повторите попытку.', 'error'));
}
async function refreshPhoneVerification(button) {
    if (!button || button.disabled) return;
    button.disabled = true;
    const container = button.closest('[data-phone-verification]');
    const startForm = container.querySelector('[data-phone-start]');
    const submission = startForm?.dataset.submission;
    const status = container.querySelector('[data-phone-status]');
    const form = container.querySelector('[data-phone-finalize]');
    form.hidden = true;
    form.elements.verification_id.value = '';
    try {
        const response = await fetch(button.dataset.url, {headers: {'Accept': 'application/json'}, cache: 'no-store'});
        if (!response.ok) throw new Error('Unavailable');
        const data = await response.json();
        // A status request started before submission must not unlock the start button.
        if (startForm?.dataset.submission !== submission) return;
        const startButton = container.querySelector('[data-phone-start] button[type="submit"]');
        if (startButton) startButton.disabled = data.status !== 'inactive';
        if (startForm) startForm.hidden = data.status === 'verified';
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
document.addEventListener('submit', event => {
    const form = event.target.closest('[data-phone-start]');
    if (!form) return;
    const button = form.querySelector('button[type="submit"]');
    if (button.disabled) {
        event.preventDefault();
        return;
    }
    form.dataset.submission = String(Number(form.dataset.submission || 0) + 1);
    button.disabled = true;
});
window.addEventListener('focus', () => {
    document.querySelectorAll('[data-phone-check]').forEach(refreshPhoneVerification);
});
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-phone-check]').forEach(refreshPhoneVerification);
});
</script>
@endonce
