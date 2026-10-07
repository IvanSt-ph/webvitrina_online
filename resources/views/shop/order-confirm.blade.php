<x-buyer-layout title="Подтверждение заказа">

<div class="checkout-confirm-safe min-h-screen w-full overflow-x-hidden bg-white px-3 py-4 pb-28 text-neutral-900 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">
    <div class="w-full space-y-5 sm:space-y-6">

        <header class="min-w-0">
            <a href="{{ route('cart.index') }}"
               class="inline-flex min-h-10 items-center gap-2 rounded-xl px-1 text-sm font-medium text-neutral-500 transition hover:text-brand-600 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100">
                <i class="ri-arrow-left-line" aria-hidden="true"></i>
                Вернуться в корзину
            </a>

            <div class="mt-4 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                <i class="ri-bank-card-line" aria-hidden="true"></i>
                Оформление
            </div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">Подтверждение заказа</h1>
            <p class="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">
                Проверьте товары и выберите удобные условия. Для каждого магазина будет создан отдельный заказ.
            </p>
        </header>

        @if(session('error'))
            <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-800" role="alert">
                <i class="ri-error-warning-line mt-0.5 shrink-0 text-lg" aria-hidden="true"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-800" role="alert">
                <i class="ri-error-warning-line mt-0.5 shrink-0 text-lg" aria-hidden="true"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        @if($pricesUpdated)
            <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900" role="status">
                <i class="ri-price-tag-3-line mt-0.5 shrink-0 text-lg" aria-hidden="true"></i>
                <span>Цена одного или нескольких товаров изменилась. Ниже показана актуальная сумма — проверьте её перед оформлением.</span>
            </div>
        @endif

        <form action="{{ route('checkout.create') }}" method="POST" class="grid min-w-0 gap-5 xl:grid-cols-[minmax(0,1fr)_360px] xl:items-start">
            @csrf
            <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">

            <div class="min-w-0 space-y-5">
                {{-- Товары по магазинам --}}
                <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
                    <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-4 py-4 sm:px-5">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                <i class="ri-shopping-bag-3-line text-lg" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <h2 class="font-semibold text-neutral-950">Ваш заказ</h2>
                                <p class="mt-0.5 text-xs text-neutral-500">{{ collect($cart)->sum('qty') }} шт. · {{ $orderCount }} {{ trans_choice('заказ|заказа|заказов', $orderCount) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-neutral-100">
                        @foreach($orderGroups as $group)
                            <article data-seller-order data-subtotal="{{ $group['subtotal'] }}">
                                <div class="flex items-center justify-between gap-3 bg-neutral-50/80 px-4 py-3 sm:px-5">
                                    <div class="flex min-w-0 items-center gap-2 text-sm font-semibold text-neutral-900">
                                        <i class="ri-store-2-line shrink-0 text-brand-500" aria-hidden="true"></i>
                                        <span class="truncate">{{ $group['seller_name'] }}</span>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-neutral-500 ring-1 ring-neutral-200">Отдельный заказ</span>
                                </div>

                                <div class="divide-y divide-neutral-100">
                                    @foreach($group['items'] as $item)
                                        @php
                                            $itemTitle = $item['title'] ?? 'Товар';
                                            $shortItemTitle = Str::limit($itemTitle, 18);
                                        @endphp
                                        <div class="grid min-w-0 grid-cols-[64px_minmax(0,1fr)] gap-3 px-4 py-4 sm:grid-cols-[72px_minmax(0,1fr)_auto] sm:items-center sm:gap-4 sm:px-5">
                                            <img data-image-candidates="{{ json_encode(\App\Models\Product::storageThumbCandidates($item['image'] ?? null)) }}"
                                                 data-image-fallback="{{ asset('images/image-placeholder.svg') }}"
                                                 src="{{ \App\Models\Product::storageThumbUrl($item['image'] ?? null) }}"
                                                 class="h-16 w-16 rounded-xl border border-neutral-200 object-cover sm:h-[72px] sm:w-[72px]"
                                                 alt="{{ $itemTitle }}">

                                            <div class="min-w-0">
                                                <p class="line-clamp-2 text-sm font-semibold leading-5 text-neutral-900 sm:text-base" style="overflow-wrap: anywhere; word-break: break-word;">
                                                    <span class="sm:hidden">{{ $shortItemTitle }}</span>
                                                    <span class="hidden sm:inline">{{ $itemTitle }}</span>
                                                </p>
                                                <p class="mt-1 text-xs text-neutral-500">
                                                    Количество: <span class="font-semibold text-neutral-700">{{ $item['qty'] }}</span>
                                                </p>
                                            </div>

                                            <div class="col-span-2 min-w-0 rounded-xl bg-neutral-50 px-3 py-2 sm:col-span-1 sm:bg-transparent sm:p-0 sm:text-right">
                                                <div class="break-words text-base font-semibold text-neutral-950 sm:text-lg">
                                                    {{ number_format($item['price'] * $item['qty'], 2, ',', ' ') }} {{ $currencySymbol }}
                                                </div>
                                                <div class="text-xs text-neutral-400">
                                                    {{ number_format($item['price'], 2, ',', ' ') }} {{ $currencySymbol }} / шт.
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="space-y-2 border-t border-neutral-100 px-4 py-3 text-sm sm:px-5">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-neutral-500">Товары магазина</span>
                                        <span class="font-semibold text-neutral-950">{{ number_format($group['subtotal'], 2, ',', ' ') }} {{ $currencySymbol }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-neutral-500">Доставка этого заказа</span>
                                        <span data-seller-delivery class="font-semibold text-neutral-950">{{ number_format($deliveryCost, 2, ',', ' ') }} {{ $currencySymbol }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3 border-t border-neutral-100 pt-2">
                                        <span class="font-semibold text-neutral-700">Итого по продавцу</span>
                                        <span data-seller-total class="font-bold text-neutral-950">{{ number_format($group['subtotal'] + $deliveryCost, 2, ',', ' ') }} {{ $currencySymbol }}</span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                @if($orderCount > 1)
                    <div class="flex items-start gap-3 rounded-2xl border border-brand-100 bg-brand-50 px-4 py-3 text-sm leading-6 text-brand-800">
                        <i class="ri-information-line mt-0.5 shrink-0 text-lg" aria-hidden="true"></i>
                        <span>Будет создано заказов: <strong>{{ $orderCount }}</strong>. Выбранные доставка и способ оплаты применяются к каждому заказу; стоимость доставки начисляется отдельно для каждого продавца.</span>
                    </div>
                @endif

                <div class="grid min-w-0 gap-5 lg:grid-cols-2">
                    {{-- Доставка --}}
                    <section class="min-w-0 rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                                <i class="ri-truck-line text-lg" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h2 class="font-semibold text-neutral-950">Способ доставки</h2>
                                <p class="mt-0.5 text-xs text-neutral-500">Выберите предпочтительный вариант</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            @if(isset($deliveryMethods) && count($deliveryMethods))
                                @foreach($deliveryMethods as $key => $label)
                                    <label class="flex min-w-0 cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 p-3 transition hover:border-brand-200 hover:bg-brand-50/50 focus-within:border-brand-300 focus-within:bg-brand-50/70 focus-within:ring-4 focus-within:ring-brand-100">
                                        <input type="radio" name="delivery_method" value="{{ $key }}"
                                               class="mt-0.5 h-4 w-4 shrink-0 border-neutral-300 text-brand-600 focus:ring-brand-500"
                                               {{ $loop->first ? 'checked' : '' }} required>
                                        <span class="min-w-0 text-sm leading-5 text-neutral-700">
                                            <span class="block">{{ $label }}</span>
                                            <span class="block text-xs text-neutral-500">{{ ($deliveryPrices[$key] ?? 0) > 0 ? number_format($deliveryPrices[$key], 2, ',', ' ') . ' ' . $currencySymbol : 'Бесплатно' }} за каждый заказ</span>
                                        </span>
                                    </label>
                                @endforeach
                            @else
                                @foreach([
                                    'courier' => 'Доставка продавцом по договорённости',
                                    'pickup' => 'Самовывоз по договорённости с продавцом',
                                    'post' => 'Отправка почтой по договорённости',
                                ] as $key => $label)
                                    <label class="flex min-w-0 cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 p-3 transition hover:border-brand-200 hover:bg-brand-50/50 focus-within:border-brand-300 focus-within:bg-brand-50/70 focus-within:ring-4 focus-within:ring-brand-100">
                                        <input type="radio" name="delivery_method" value="{{ $key }}"
                                               class="mt-0.5 h-4 w-4 shrink-0 border-neutral-300 text-brand-600 focus:ring-brand-500"
                                               {{ $loop->first ? 'checked' : '' }} required>
                                        <span class="min-w-0 text-sm leading-5 text-neutral-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            @endif
                        </div>

                        <p class="mt-3 text-xs leading-5 text-neutral-500">Указанная стоимость фиксируется в каждом заказе. Срок и детали передачи можно уточнить у соответствующего продавца.</p>
                    </section>

                    {{-- Оплата --}}
                    <section class="min-w-0 rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                <i class="ri-wallet-3-line text-lg" aria-hidden="true"></i>
                            </span>
                            <div>
                                <h2 class="font-semibold text-neutral-950">Способ оплаты</h2>
                                <p class="mt-0.5 text-xs text-neutral-500">Сообщим выбранный вариант продавцу</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            @if(isset($paymentMethods) && count($paymentMethods))
                                @foreach($paymentMethods as $key => $label)
                                    <label class="flex min-w-0 cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 p-3 transition hover:border-brand-200 hover:bg-brand-50/50 focus-within:border-brand-300 focus-within:bg-brand-50/70 focus-within:ring-4 focus-within:ring-brand-100">
                                        <input type="radio" name="payment_method" value="{{ $key }}"
                                               class="mt-0.5 h-4 w-4 shrink-0 border-neutral-300 text-brand-600 focus:ring-brand-500"
                                               {{ $loop->first ? 'checked' : '' }} required>
                                        <span class="min-w-0 text-sm leading-5 text-neutral-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            @else
                                @foreach([
                                    'cash' => 'Наличными при получении или передаче товара',
                                    'card' => 'Картой при получении',
                                    'bank_transfer' => 'Перевод по согласованию с продавцом',
                                ] as $key => $label)
                                    <label class="flex min-w-0 cursor-pointer items-start gap-3 rounded-xl border border-neutral-200 p-3 transition hover:border-brand-200 hover:bg-brand-50/50 focus-within:border-brand-300 focus-within:bg-brand-50/70 focus-within:ring-4 focus-within:ring-brand-100">
                                        <input type="radio" name="payment_method" value="{{ $key }}"
                                               class="mt-0.5 h-4 w-4 shrink-0 border-neutral-300 text-brand-600 focus:ring-brand-500"
                                               {{ $loop->first ? 'checked' : '' }} required>
                                        <span class="min-w-0 text-sm leading-5 text-neutral-700">{{ $label }}</span>
                                    </label>
                                @endforeach
                            @endif
                        </div>

                        <p class="mt-3 text-xs leading-5 text-neutral-500">Онлайн-платёж на сайте пока не выполняется. Расчёт подтверждается с продавцом.</p>
                    </section>
                </div>

                <div class="grid min-w-0 gap-5 lg:grid-cols-2">
                    {{-- Получатель --}}
                    <section class="min-w-0 rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                <i class="ri-user-3-line text-lg" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <h2 class="font-semibold text-neutral-950">Получатель</h2>
                                <p class="mt-0.5 text-xs text-neutral-500">Контактные данные из вашего профиля</p>
                            </div>
                        </div>

                        <dl class="space-y-3 text-sm">
                            <div class="flex min-w-0 items-start justify-between gap-3">
                                <dt class="shrink-0 text-neutral-500">Имя</dt>
                                <dd class="min-w-0 break-words text-right font-semibold text-neutral-900">{{ auth()->user()->name }}</dd>
                            </div>
                            <div class="flex min-w-0 items-start justify-between gap-3">
                                <dt class="shrink-0 text-neutral-500">Телефон</dt>
                                <dd class="min-w-0 break-words text-right font-semibold text-neutral-900">{{ auth()->user()->phone ?: 'Не указан' }}</dd>
                            </div>
                        </dl>
                    </section>

                    {{-- Адрес --}}
                    <section class="min-w-0 rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                <i class="ri-map-pin-line text-lg" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <h2 class="font-semibold text-neutral-950">Адрес доставки</h2>
                                <p class="mt-0.5 text-xs text-neutral-500">Для самовывоза адрес не обязателен</p>
                            </div>
                        </div>

                        @if($addresses->count())
                            <select id="checkout-address" name="address_id" aria-label="Адрес доставки" class="h-12 w-full min-w-0 rounded-xl border-neutral-200 bg-white px-3 text-sm text-neutral-700 focus:border-brand-300 focus:ring-4 focus:ring-brand-100">
                                @foreach($addresses as $address)
                                    <option value="{{ $address->id }}" {{ ($defaultAddressId == $address->id) ? 'selected' : '' }}>
                                        {{ $address->country }}, {{ $address->city }}, {{ $address->street }} {{ $address->house }}, кв. {{ $address->apartment }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs leading-5 text-neutral-500">Основной адрес выбран по умолчанию. При необходимости укажите другой.</p>
                        @else
                            <div class="flex flex-col gap-3 rounded-xl bg-neutral-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-neutral-600">У вас пока нет сохранённых адресов.</p>
                                <a href="{{ route('addresses.index') }}" class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl border border-brand-200 bg-white px-4 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100">
                                    Добавить адрес
                                </a>
                            </div>
                        @endif
                    </section>
                </div>
            </div>

            {{-- Сводка --}}
            <aside class="min-w-0 xl:sticky xl:top-6">
                <section class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                            <i class="ri-file-list-3-line text-xl" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h2 class="font-semibold text-neutral-950">Сводка заказа</h2>
                            <p class="mt-0.5 text-xs text-neutral-500">По всем магазинам</p>
                        </div>
                    </div>

                    <dl class="mt-5 space-y-3 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-neutral-500">Товаров</dt>
                            <dd class="font-semibold text-neutral-900">{{ collect($cart)->sum('qty') }} шт.</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-neutral-500">Заказов</dt>
                            <dd class="font-semibold text-neutral-900">{{ $orderCount }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-neutral-500">Валюта</dt>
                            <dd class="font-semibold text-neutral-900">{{ $checkoutCurrency }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-neutral-500">Сумма товаров</dt>
                            <dd id="subtotal" class="font-semibold text-neutral-900">{{ number_format($total, 2, ',', ' ') }} {{ $currencySymbol }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-neutral-500">Доставка</dt>
                            <dd id="delivery-cost" class="max-w-[190px] text-right font-semibold text-neutral-900">{{ number_format($totalDeliveryCost, 2, ',', ' ') }} {{ $currencySymbol }}</dd>
                        </div>
                    </dl>

                    <div class="my-4 border-t border-neutral-100"></div>

                    <div class="flex items-end justify-between gap-3">
                        <span class="text-sm font-medium text-neutral-500">Итого</span>
                        <span id="total-with-delivery" class="text-xl font-bold tracking-tight text-neutral-950 sm:text-2xl">
                            {{ number_format($totalWithDelivery, 2, ',', ' ') }} {{ $currencySymbol }}
                        </span>
                    </div>

                    <div class="mt-4 rounded-xl bg-brand-50 px-3 py-3 text-xs leading-5 text-brand-800">
                        В итог уже включена доставка для каждого отдельного заказа. Онлайн-списание оплаты на сайте не выполняется.
                    </div>

                    <button type="submit"
                            class="mt-4 inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 text-base font-semibold text-white transition hover:bg-brand-600 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-200 disabled:cursor-not-allowed disabled:bg-neutral-300">
                        <i class="ri-bank-card-line" aria-hidden="true"></i>
                        Оформить заказ
                    </button>
                </section>
            </aside>
        </form>
    </div>
</div>

@once('remixicon-4.1.0')
    @vite('resources/css/remixicon.css')
@endonce

<script>
document.addEventListener('DOMContentLoaded', () => {
    const deliveryRadios = document.querySelectorAll('input[name="delivery_method"]');
    const addressSelect = document.querySelector('select[name="address_id"]');
    const submitButton = document.querySelector('button[type="submit"]');
    const subtotal = Number(@json($total));
    const prices = @json($deliveryPrices ?? []);
    const currencySymbol = @json($currencySymbol);
    const orderCount = Number(@json($orderCount));

    const deliveryEl = document.getElementById('delivery-cost');
    const totalEl = document.getElementById('total-with-delivery');

    const format = value =>
        value.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + currencySymbol;

    function updateTotal(radio) {
        if (!radio) return;

        const price = Number(prices[radio.value] ?? 0) * orderCount;
        deliveryEl.textContent = price > 0 ? format(price) : 'Бесплатно';
        deliveryEl.className = 'max-w-[190px] text-right font-semibold text-neutral-900';
        totalEl.textContent = format(subtotal + price);

        document.querySelectorAll('[data-seller-order]').forEach(order => {
            const orderSubtotal = Number(order.dataset.subtotal ?? 0);
            const delivery = Number(prices[radio.value] ?? 0);
            order.querySelector('[data-seller-delivery]').textContent = delivery > 0 ? format(delivery) : 'Бесплатно';
            order.querySelector('[data-seller-total]').textContent = format(orderSubtotal + delivery);
        });
    }

    function updateButtonState() {
        const checkedRadio = document.querySelector('input[name="delivery_method"]:checked');
        const isPickup = checkedRadio && checkedRadio.value === 'pickup';
        const hasAddress = addressSelect && addressSelect.value;

        if (isPickup || hasAddress) {
            submitButton.removeAttribute('disabled');
        } else {
            submitButton.setAttribute('disabled', 'disabled');
        }
    }

    deliveryRadios.forEach(radio => {
        radio.addEventListener('change', event => {
            updateTotal(event.target);
            updateButtonState();
        });
    });

    if (addressSelect) {
        addressSelect.addEventListener('change', updateButtonState);
    }

    submitButton.closest('form').addEventListener('submit', () => {
        submitButton.setAttribute('disabled', 'disabled');
        submitButton.textContent = 'Оформляем заказ...';
    });

    const checkedRadio = document.querySelector('input[name="delivery_method"]:checked');
    updateTotal(checkedRadio);
    updateButtonState();
});
</script>

<style>
    .checkout-confirm-safe,
    .checkout-confirm-safe * {
        box-sizing: border-box;
    }

    .checkout-confirm-safe .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

</x-buyer-layout>
