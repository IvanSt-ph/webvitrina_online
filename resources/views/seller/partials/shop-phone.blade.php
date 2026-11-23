@if ($shop = Auth::user()->shop)
    @php($phoneContact = $shop->phoneContact())
    <section class="h-full w-full min-w-0 space-y-5 rounded-2xl border border-brand-100 bg-brand-50/40 p-4 shadow-sm sm:p-6">
        <div class="flex items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-100/60 text-brand-600">
                <i class="ri-store-2-line text-xl" aria-hidden="true"></i>
            </span>
            <div class="min-w-0">
                <h3 class="text-lg font-semibold tracking-tight text-neutral-950">Отображение в магазине</h3>
                <p class="mt-1 text-sm leading-6 text-neutral-600">Магазин использует основной телефон аккаунта. Вы можете разрешить посетителям видеть этот номер</p>
            </div>
        </div>
        <form method="POST" action="{{ route('profile.shop.update') }}" class="max-w-xl space-y-4"
              x-data="{ changed: false }">
            @csrf
            @method('PATCH')
            <input type="hidden" name="phone_public" value="0">
            <input type="hidden" name="phone_public_for" value="{{ $phoneContact['phone'] }}">
            <label class="inline-flex max-w-full cursor-pointer items-start gap-3">
                <span class="relative mt-0.5 inline-flex h-6 w-11 shrink-0">
                    <input type="checkbox" name="phone_public" value="1" role="switch"
                           class="peer sr-only"
                           @checked((bool) $phoneContact['public_phone'])
                           @disabled(!$phoneContact['verified'])
                           @change="changed = $event.target.checked !== @js((bool) $phoneContact['public_phone'])">
                    <span aria-hidden="true" class="h-6 w-11 rounded-full bg-neutral-300 transition peer-checked:bg-brand-500 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-200 peer-disabled:opacity-50"></span>
                    <span aria-hidden="true" class="pointer-events-none absolute left-1 top-1 h-4 w-4 rounded-full bg-white shadow-sm transition-transform peer-checked:translate-x-5"></span>
                </span>
                <span class="min-w-0">
                    <span class="block text-sm font-medium leading-6 text-neutral-900">Показывать номер посетителям магазина</span>
                    <span class="mt-1 block break-words text-sm leading-5 text-neutral-500" role="status">
                        @if ($phoneContact['public_phone'])
                            Покупатели видят номер: {{ $phoneContact['public_phone'] }}
                        @else
                            Сейчас номер скрыт от покупателей
                        @endif
                    </span>
                    @if (!$phoneContact['verified'])
                        <span class="mt-2 block text-sm leading-5 text-neutral-600">Чтобы включить показ, сохраните и подтвердите основной телефон аккаунта через Telegram.</span>
                    @endif
                </span>
            </label>
            <p x-cloak x-show="changed" role="status" class="text-sm font-medium text-brand-700">Изменение ещё не сохранено. Нажмите «Сохранить публичность».</p>
            <x-input-error :messages="$errors->get('phone_public')" />
            <x-input-error :messages="$errors->get('phone')" />
            <div class="sm:pl-14">
                <x-secondary-action type="submit" :disabled="!$phoneContact['verified']" class="w-full disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">Сохранить публичность</x-secondary-action>
            </div>
        </form>
        <div class="flex items-start gap-2 border-t border-brand-100 pt-4 text-xs leading-5 text-neutral-500">
            <i class="ri-information-line shrink-0 text-base text-brand-500" aria-hidden="true"></i>
            <p>При смене номера его отображение автоматически отключится до повторного подтверждения.</p>
        </div>
    </section>
@endif
