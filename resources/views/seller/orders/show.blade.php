{{-- resources/views/seller/orders/show.blade.php --}}
<x-seller-layout :title="'Заказ ' . ($order->number ?? ('#' . $order->id))">

    @php
        /** @var \App\Models\Order $order */
        $isPickupV2 = $order->workflow_version === \App\Models\Order::WORKFLOW_PICKUP;
        $isLegacy = $order->isLegacyWorkflow();

        $statusColors = [
            'pending'    => 'bg-amber-50 text-amber-700 border border-amber-200',
            'processing' => 'bg-sky-50 text-sky-700 border border-sky-200',
            'ready_for_pickup' => 'bg-violet-50 text-violet-700 border border-violet-200',
            'paid'       => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
            'shipped'    => 'bg-blue-50 text-blue-700 border border-blue-200',
            'delivered'  => 'bg-green-50 text-green-700 border border-green-200',
            'completed'  => 'bg-neutral-50 text-neutral-700 border border-neutral-200',
            'canceled'   => 'bg-red-50 text-red-700 border border-red-200',
        ];

        $currentStatusClass = $statusColors[$order->status] ?? 'bg-neutral-50 text-neutral-700 border border-neutral-200';

        $steps = $isPickupV2 ? [
            \App\Models\Order::STATUS_PENDING => 'Новый заказ',
            \App\Models\Order::STATUS_PROCESSING => 'Принят продавцом',
            \App\Models\Order::STATUS_READY_FOR_PICKUP => 'Готов к самовывозу',
            \App\Models\Order::STATUS_DELIVERED => 'Получение подтверждено покупателем',
            \App\Models\Order::STATUS_COMPLETED => 'Завершён',
        ] : ($isLegacy ? [
            \App\Models\Order::STATUS_PENDING    => 'Новый заказ',
            \App\Models\Order::STATUS_PROCESSING => 'Принят продавцом',
            \App\Models\Order::STATUS_PAID       => 'Оплачен',
            \App\Models\Order::STATUS_SHIPPED    => 'Передан в доставку',
            \App\Models\Order::STATUS_DELIVERED  => 'Доставлен',
            \App\Models\Order::STATUS_COMPLETED  => 'Завершён',
        ] : []);

        // Позиция текущего статуса в прогрессе
        $statusKeys   = array_keys($steps);
        $currentIndex = array_search($order->status, $statusKeys, true);
        if ($currentIndex === false) {
            $currentIndex = 0;
        }
        if ($isPickupV2 && $order->buyer_confirmed_at === null && $currentIndex >= 3) {
            $currentIndex = 2;
        }
        if ($isPickupV2 && $order->status === \App\Models\Order::STATUS_COMPLETED && $order->status_ru !== 'Завершён') {
            $currentIndex = $order->buyer_confirmed_at ? 3 : 2;
        }
        $statusProgress = count($steps) > 1
            ? ($currentIndex / (count($steps) - 1)) * 100
            : 0;

        $itemsCount = $order->items->sum('quantity');
        $primaryProduct = $order->items
            ->map(fn ($item) => $item->product)
            ->first(fn ($product) => $product !== null);
        $validPickupOrder = $isPickupV2
            && $order->delivery_method === 'pickup'
            && in_array($order->payment_method, ['cash', 'card'], true);
        $nextStatus = $isPickupV2 ? ($validPickupOrder ? ([
            \App\Models\Order::STATUS_PENDING => \App\Models\Order::STATUS_PROCESSING,
            \App\Models\Order::STATUS_PROCESSING => \App\Models\Order::STATUS_READY_FOR_PICKUP,
        ][$order->status] ?? null) : null) : ($isLegacy ? ([
            \App\Models\Order::STATUS_PENDING => \App\Models\Order::STATUS_PROCESSING,
            \App\Models\Order::STATUS_PROCESSING => \App\Models\Order::STATUS_PAID,
            \App\Models\Order::STATUS_PAID => \App\Models\Order::STATUS_SHIPPED,
        ][$order->status] ?? null) : null);
        $nextActionLabel = [
            \App\Models\Order::STATUS_PENDING => 'Принять заказ',
            \App\Models\Order::STATUS_PROCESSING => 'Отметить как оплаченный',
            \App\Models\Order::STATUS_PAID => 'Передать в доставку',
            \App\Models\Order::STATUS_SHIPPED => 'Отметить доставленным',
            \App\Models\Order::STATUS_DELIVERED => 'Завершить заказ',
        ][$order->status] ?? 'Действий по статусу нет';
        $nextActionHint = [
            \App\Models\Order::STATUS_PENDING => 'Покупатель ждёт, что продавец подтвердит заказ.',
            \App\Models\Order::STATUS_PROCESSING => 'Подходит для оплаты при получении или ручной проверки оплаты.',
            \App\Models\Order::STATUS_PAID => 'После передачи в доставку покупатель увидит, что заказ уже в пути.',
            \App\Models\Order::STATUS_SHIPPED => 'Отметьте доставку, когда заказ прибыл покупателю.',
            \App\Models\Order::STATUS_DELIVERED => 'Завершайте после финального подтверждения.',
        ][$order->status] ?? 'Заказ уже не требует смены статуса.';
        $canCancel = in_array($order->status, [
            \App\Models\Order::STATUS_PENDING,
            \App\Models\Order::STATUS_PROCESSING,
        ], true) && ($isLegacy || ($validPickupOrder
            && $order->payment_status === \App\Models\Order::PAYMENT_UNPAID
            && $order->paid_at === null
            && $order->buyer_confirmed_at === null
            && $order->delivered_at === null));
        $hasCancellationRequest = ! $order->isUnsupportedWorkflow()
            && $order->cancellation_requested_at !== null
            && ! in_array($order->status, [\App\Models\Order::STATUS_CANCELED, \App\Models\Order::STATUS_COMPLETED], true);
        $canConfirmPayment = $validPickupOrder
            && in_array($order->status, [\App\Models\Order::STATUS_READY_FOR_PICKUP, \App\Models\Order::STATUS_DELIVERED], true)
            && $order->payment_status === \App\Models\Order::PAYMENT_UNPAID
            && $order->paid_at === null;
        $canRequestReceiptConfirmation = $validPickupOrder
            && in_array($order->status, [\App\Models\Order::STATUS_READY_FOR_PICKUP, \App\Models\Order::STATUS_DELIVERED], true)
            && $order->buyer_confirmed_at === null;
        $nextConfirmationRequestAt = $order->confirmation_requested_at?->copy()->addDay();
        if ($isPickupV2) {
            $nextActionLabel = match ($nextStatus) {
                \App\Models\Order::STATUS_PROCESSING => 'Принять заказ',
                \App\Models\Order::STATUS_READY_FOR_PICKUP => 'Готов к самовывозу',
                default => $canConfirmPayment ? 'Подтвердить оплату при получении' : 'Действий по статусу нет',
            };
            $nextActionHint = $validPickupOrder
                ? 'Самовывоз; оплату наличными или картой продавец отмечает отдельно после фактического получения денег.'
                : 'Параметры заказа требуют проверки. Обратитесь в поддержку.';
        } elseif (! $isLegacy) {
            $nextActionLabel = 'Версия процесса заказа не поддерживается';
            $nextActionHint = 'Изменение заказа временно недоступно. Обратитесь в поддержку.';
        }
    @endphp

    <div class="seller-order-show-safe min-h-screen w-full space-y-5 overflow-x-hidden bg-white px-3 py-4 pb-28 text-neutral-900 sm:space-y-6 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">

        {{-- Верхняя панель --}}
        <div class="grid min-w-0 gap-3 sm:flex sm:items-center sm:justify-between">
            <div class="min-w-0 space-y-1">
                <x-breadcrumbs :items="[
                    ['label' => 'Панель', 'href' => route('seller.cabinet')],
                    ['label' => 'Заказы', 'href' => route('seller.orders.index')],
                    ['label' => 'Заказ ' . ($order->number ?? ('#' . $order->id))],
                ]" />

                <h1 class="truncate text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">
                    Заказ {{ $order->number ?? ('#' . $order->id) }}
                </h1>

                <div class="break-words text-sm text-neutral-500">
                    от {{ $order->created_at?->format('d.m.Y H:i') }}
                    • Покупатель: {{ $order->buyer_name }}
                    (ID: {{ $order->user_id }})
                </div>
            </div>

            <div class="min-w-0 space-y-2 sm:shrink-0 sm:text-right">
                <div class="truncate text-lg font-bold text-neutral-950">
                    {{ $order->formatted_total_price ?? (number_format($order->total_price, 2, ',', ' ') . ' ' . ($order->currency ?? '')) }}
                </div>

                <span class="inline-flex max-w-full items-center truncate px-3 py-1 rounded-full text-xs font-medium {{ $currentStatusClass }}">
                    {{ $order->status_ru }}
                </span>
            </div>
        </div>

        {{-- Товары в заказе --}}
        <div x-data="{ showAllItems: false }" class="min-w-0 overflow-hidden rounded-2xl border border-neutral-200 bg-white">
            <div class="flex min-w-0 flex-col gap-2 border-b border-neutral-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">
                        Товары в заказе
                    </h2>
                    <p class="mt-1 text-sm text-neutral-500">Фото, артикул, остаток и сумма по каждой позиции.</p>
                </div>
                <span class="inline-flex w-fit rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 ring-1 ring-brand-100">
                    {{ $itemsCount }} шт.
                </span>
            </div>

            <div id="seller-order-items" class="divide-y divide-neutral-100">
                @forelse($order->items as $item)
                    @php
                        $product = $item->product;
                        $itemTitle = $item->historical_title;
                        $productEditUrl = $product ? route('seller.products.edit', $product) : null;
                    @endphp
                    <div @if($loop->index >= 3) x-show="showAllItems" x-cloak @endif
                         class="grid min-w-0 grid-cols-[88px_minmax(0,1fr)] gap-3 px-4 py-4 sm:grid-cols-[112px_minmax(0,1fr)] sm:gap-5 sm:px-5 lg:grid-cols-[112px_minmax(0,1fr)_auto] lg:items-center">
                        <div class="h-[88px] w-[88px] overflow-hidden rounded-xl border border-neutral-200 bg-neutral-50 sm:h-28 sm:w-28">
                            @if($product)
                                <a href="{{ $productEditUrl }}" class="block h-full w-full" title="Открыть товар продавца">
                                    <img data-image-candidates="{{ json_encode($item->historical_image_candidates) }}" data-image-fallback="{{ asset(\App\Models\Product::IMAGE_FALLBACK_ASSET) }}" src="{{ $item->historical_image_url }}"
                                         alt="{{ $itemTitle }}"
                                         class="h-full w-full object-cover">
                                </a>
                            @else
                                <div class="flex h-full w-full items-center justify-center text-neutral-300">
                                    <i class="ri-image-line text-2xl"></i>
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 space-y-2">
                            <div class="break-words text-sm font-semibold text-neutral-900" style="overflow-wrap:anywhere;">
                                {{ $itemTitle }}
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5 text-xs text-neutral-500">
                                <span class="rounded-full bg-neutral-100 px-2 py-1">ID: {{ $item->product_id }}</span>
                                @if($item->product_sku)
                                    <span class="rounded-full bg-neutral-100 px-2 py-1">SKU: {{ $item->product_sku }}</span>
                                @endif
                                @if($product)
                                    <span class="rounded-full {{ $product->stock <= 0 ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }} px-2 py-1">Остаток: {{ $product->stock }}</span>
                                @endif
                            </div>

                            @if($productEditUrl)
                                <a href="{{ $productEditUrl }}" class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:text-brand-700">
                                    <i class="ri-external-link-line"></i>
                                    Открыть товар
                                </a>
                            @endif
                        </div>

                        <div class="col-span-2 grid min-w-0 grid-cols-3 gap-3 rounded-xl border border-neutral-100 bg-neutral-50 p-3 text-sm lg:col-span-1 lg:min-w-[320px] lg:items-center lg:p-4">
                            <div>
                                <div class="text-xs text-neutral-400">Кол-во</div>
                                <div class="font-semibold text-neutral-900">{{ $item->quantity }}</div>
                            </div>

                            <div>
                                <div class="text-xs text-neutral-400">Цена</div>
                                <div class="font-semibold text-neutral-900">
                                    {{ number_format($item->price, 2, ',', ' ') }} {{ \App\Models\Product::currencySymbol($order->currency ?? '') }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs text-neutral-400">Сумма</div>
                                <div class="font-semibold text-neutral-900 sm:text-right">
                                    {{ number_format($item->total, 2, ',', ' ') }} {{ \App\Models\Product::currencySymbol($order->currency ?? '') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-6 text-sm text-neutral-500">В этом заказе нет сохранённых позиций.</p>
                @endforelse
            </div>

            @if($order->items->count() > 3)
                <div class="border-t border-neutral-100 px-5 py-3">
                    <button type="button"
                            @click="showAllItems = !showAllItems"
                            :aria-expanded="showAllItems.toString()"
                            aria-expanded="false"
                            aria-controls="seller-order-items"
                            class="w-full rounded-xl border border-brand-200 px-4 py-2.5 text-sm font-semibold text-brand-600 transition hover:bg-brand-50"
                            x-text="showAllItems ? 'Свернуть список' : 'Показать все товары ({{ $order->items->count() }})'">
                        Показать все товары ({{ $order->items->count() }})
                    </button>
                </div>
            @endif

            <div class="grid grid-cols-[auto_minmax(0,1fr)] items-center gap-3 border-t border-neutral-100 bg-neutral-50/60 px-5 py-4 sm:flex sm:justify-end">
                <div class="text-sm text-neutral-500">
                    Итого:
                </div>
                <div class="truncate text-right text-lg font-bold text-neutral-950">
                    {{ $order->formatted_total_price ?? (number_format($order->total_price, 2, ',', ' ') . ' ' . ($order->currency ?? '')) }}
                </div>
            </div>
        </div>

        {{-- Прогресс статусов --}}
        <div class="overflow-hidden rounded-2xl border border-neutral-200 bg-white px-4 py-4 sm:px-5">
            <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="font-semibold text-neutral-900">Этап заказа</h2>
                    <p class="mt-1 text-xs text-neutral-500">Текущий путь заказа от принятия до завершения</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $currentStatusClass }}">{{ $order->status_ru }}</span>
                    @if($isPickupV2)
                        <span class="rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 text-xs text-neutral-600">{{ $order->pickup_payment_status_label }}</span>
                        <span class="rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 text-xs text-neutral-600">{{ $order->buyer_confirmed_at ? 'Получение подтверждено покупателем' : 'Получение покупателем не подтверждено' }}</span>
                    @endif
                </div>
            </div>
            <div class="overflow-x-auto pb-1">
                <div class="relative h-[68px] min-w-[680px] pt-1 text-xs font-medium text-neutral-500">
                    <div class="absolute left-[14px] right-[14px] top-[18px] h-[2px] bg-neutral-200">
                        <div class="h-full bg-brand-500" style="width: {{ $statusProgress }}%"></div>
                    </div>
                    @foreach($steps as $key => $label)
                        @php
                            $index = array_search($key, $statusKeys, true);
                            $isDone = $index !== false && $index <= $currentIndex;
                            $position = count($steps) > 1 ? ($loop->index / (count($steps) - 1)) * 100 : 0;
                            $positionStyle = $loop->first
                                ? 'left: 0;'
                                : ($loop->last ? 'right: 0;' : 'left: ' . $position . '%; transform: translateX(-50%);');
                            $alignmentClass = $loop->first
                                ? 'items-start text-left'
                                : ($loop->last ? 'items-end text-right' : 'items-center text-center');
                        @endphp
                        <div class="absolute top-1 flex w-[140px] flex-col {{ $alignmentClass }}" style="{{ $positionStyle }}">
                            <div class="flex h-7 w-7 items-center justify-center rounded-full border text-[11px] font-semibold
                                        {{ $isDone ? 'border-brand-500 bg-brand-500 text-white' : 'border-neutral-300 bg-white text-neutral-400' }}">
                                {{ $loop->iteration }}
                            </div>

                            <div class="mt-2 text-[11px] leading-snug
                                        {{ $isDone ? 'text-neutral-800' : 'text-neutral-400' }}">
                                {{ $label }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Рабочая панель продавца --}}
<section class="rounded-2xl border border-brand-100 bg-brand-50/50 p-4 sm:p-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">Рабочая панель продавца</p>
            <h2 class="mt-1 text-xl font-semibold text-neutral-950">{{ $nextActionLabel }}</h2>
            <p class="mt-2 max-w-2xl text-sm text-neutral-600">{{ $nextActionHint }}</p>
        </div>

        <div class="flex shrink-0 flex-col gap-2 sm:flex-row lg:flex-col xl:flex-row">
            <form method="POST" action="{{ route('seller.orders.chat.buyer', $order) }}">
                @csrf
                <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-neutral-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-neutral-800 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                        @disabled(! $primaryProduct || ! $order->user->exists)>
                    <i class="ri-chat-3-line"></i>
                    Написать покупателю
                </button>
            </form>

            <form method="POST" action="{{ route('support.start') }}">
                @csrf
                <input type="hidden" name="topic" value="Вопрос по заказу {{ $order->number }}">
                <input type="hidden" name="details" value="Заказ {{ $order->number }}, покупатель {{ $order->buyer_name }}, статус: {{ $order->status_ru }}.">
                <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 transition hover:border-brand-200 hover:bg-brand-50 sm:w-auto">
                    <i class="ri-customer-service-2-line"></i>
                    Поддержка
                </button>
            </form>
        </div>
    </div>

    <div class="mt-5 grid gap-3 md:grid-cols-3">
        <div class="rounded-xl border border-white bg-white p-4">
            <div class="flex items-center gap-2 text-sm font-semibold text-neutral-900">
                <i class="ri-flag-line text-brand-500"></i>
                Следующий шаг
            </div>
            <div class="mt-3">
                @if($nextStatus)
                    <form method="POST" action="{{ route('seller.orders.updateStatus', $order) }}">
                        @csrf
                        <input type="hidden" name="status" value="{{ $nextStatus }}">
                        <button class="wv-btn-primary inline-flex w-full items-center justify-center gap-2 px-4 py-2.5">
                            {{ $nextActionLabel }}
                            <i class="ri-arrow-right-line"></i>
                        </button>
                    </form>
                @endif
                @if($canConfirmPayment)
                    <form method="POST" action="{{ route('seller.orders.confirmPayment', $order) }}" class="mt-2">
                        @csrf
                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100">
                            Подтвердить получение оплаты
                        </button>
                    </form>
                @endif
                @if($canRequestReceiptConfirmation)
                    @if($nextConfirmationRequestAt?->isFuture())
                        <p class="mt-2 rounded-xl bg-neutral-50 px-3 py-2 text-sm text-neutral-600">
                            Следующее напоминание доступно {{ $nextConfirmationRequestAt->format('d.m.Y H:i') }}.
                        </p>
                    @elseif($order->user->exists)
                        <form method="POST" action="{{ route('seller.orders.requestReceiptConfirmation', $order) }}" class="mt-2">
                            @csrf
                            <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-800 transition hover:bg-sky-100">
                                Напомнить покупателю
                            </button>
                        </form>
                    @else
                        <p class="mt-2 rounded-xl bg-neutral-50 px-3 py-2 text-sm text-neutral-600">Аккаунт покупателя недоступен. Обратитесь в поддержку.</p>
                    @endif
                @endif
                @if(! $nextStatus && ! $canConfirmPayment && ! $canRequestReceiptConfirmation)
                    <p class="rounded-xl bg-neutral-50 px-3 py-2 text-sm text-neutral-600">По этому заказу нет доступного следующего шага.</p>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-white bg-white p-4">
            <div class="flex items-center gap-2 text-sm font-semibold text-neutral-900">
                <i class="ri-user-smile-line text-emerald-500"></i>
                Покупатель ждёт
            </div>
            <p class="mt-3 text-sm text-neutral-600">
                @if(! $order->isUnsupportedWorkflow() && $order->cancellation_requested_at && $order->status !== \App\Models\Order::STATUS_CANCELED)
                    Решения по отмене заказа.
                @elseif($isPickupV2 && $order->status === \App\Models\Order::STATUS_READY_FOR_PICKUP)
                    Согласования времени самовывоза.
                @elseif($isPickupV2 && $order->status === \App\Models\Order::STATUS_DELIVERED)
                    {{ $order->buyer_confirmed_at ? 'Подтверждения оплаты продавцом, если она ещё не отмечена.' : 'Проверки статуса получения.' }}
                @elseif(! $order->isUnsupportedWorkflow() && $order->status === \App\Models\Order::STATUS_PENDING)
                    Подтверждения заказа продавцом.
                @elseif($isLegacy && $order->status === \App\Models\Order::STATUS_PAID)
                    Передачи товара в доставку.
                @elseif($isLegacy && $order->status === \App\Models\Order::STATUS_SHIPPED)
                    Обновления по доставке.
                @else
                    Актуального статуса и ответа при вопросах.
                @endif
            </p>
        </div>

        <div class="rounded-xl border border-white bg-white p-4">
            <div class="flex items-center gap-2 text-sm font-semibold text-neutral-900">
                <i class="ri-error-warning-line text-rose-500"></i>
                Безопасное действие
            </div>
            <div class="mt-3">
                @if($canCancel)
                    <form method="POST" action="{{ route('seller.orders.updateStatus', $order) }}"
                          onsubmit="return confirm('Вы точно хотите отменить заказ?');">
                        @csrf
                        <input type="hidden" name="status" value="canceled">
                        @if($isPickupV2)
                            <label for="seller-cancellation-reason" class="mb-1 block text-sm font-medium text-neutral-700">Причина отмены</label>
                            <textarea id="seller-cancellation-reason" name="cancellation_reason" required maxlength="700" rows="3" class="mb-3 w-full rounded-xl border-neutral-200 text-sm" placeholder="Укажите причину отмены">{{ old('cancellation_reason') }}</textarea>
                        @endif
                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                            {{ $hasCancellationRequest && $isPickupV2 ? 'Подтвердить отмену' : 'Отменить заказ' }}
                        </button>
                    </form>
                @elseif($order->isUnsupportedWorkflow())
                    <p class="rounded-xl bg-neutral-50 px-3 py-2 text-sm text-neutral-600">Версия процесса заказа не поддерживается. Изменения недоступны; обратитесь в поддержку.</p>
                @elseif($order->status === \App\Models\Order::STATUS_CANCELED)
                    <p class="rounded-xl bg-neutral-50 px-3 py-2 text-sm text-neutral-600">Заказ уже отменён.</p>
                @elseif($order->status === \App\Models\Order::STATUS_COMPLETED)
                    <p class="rounded-xl bg-neutral-50 px-3 py-2 text-sm text-neutral-600">Заказ завершён. Вопросы по нему решаются через поддержку или спор.</p>
                @elseif($isPickupV2 && ($order->paid_at || $order->buyer_confirmed_at || $order->delivered_at || $order->payment_status !== \App\Models\Order::PAYMENT_UNPAID))
                    <p class="rounded-xl bg-neutral-50 px-3 py-2 text-sm text-neutral-600">После подтверждения оплаты или получения обычная отмена недоступна. Обратитесь в поддержку или откройте спор.</p>
                @elseif($isPickupV2 && $order->status === \App\Models\Order::STATUS_READY_FOR_PICKUP)
                    <p class="rounded-xl bg-neutral-50 px-3 py-2 text-sm text-neutral-600">Заказ готов к самовывозу. Обычная отмена после подготовки недоступна; обратитесь в поддержку.</p>
                @else
                    <p class="rounded-xl bg-neutral-50 px-3 py-2 text-sm text-neutral-600">Отмена недоступна для текущего статуса.</p>
                @endif
            </div>
        </div>
    </div>
</section>

        @if($hasCancellationRequest)
            <section class="rounded-2xl border border-rose-200 bg-rose-50 p-4 sm:p-5">
                <h2 class="font-semibold text-rose-900">Покупатель запросил отмену заказа</h2>
                <p class="mt-1 text-sm text-rose-700">{{ $order->cancellation_requested_at->format('d.m.Y H:i') }}</p>
                <p class="mt-3 rounded-xl bg-white px-3 py-2 text-sm text-neutral-700">{{ $order->cancellation_reason }}</p>
                <p class="mt-3 text-sm text-rose-800">{{ $isPickupV2 ? 'Обычная отмена возможна только до готовности, оплаты и получения; дальнейшие случаи требуют разбора.' : 'Если заказ ещё не отправлен, отмените его в блоке действий ниже или свяжитесь с покупателем.' }}</p>
                @if($isPickupV2)
                    <form method="POST" action="{{ route('seller.orders.rejectCancellation', $order) }}" class="mt-3">
                        @csrf
                        <label for="seller-rejection-reason" class="mb-1 block text-sm font-medium text-rose-900">Причина отказа</label>
                        <textarea id="seller-rejection-reason" name="reason" required maxlength="700" rows="3" class="mb-3 w-full rounded-xl border-rose-200 bg-white text-sm" placeholder="Объясните покупателю причину отказа">{{ old('reason') }}</textarea>
                        <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-rose-300 bg-white px-4 py-2.5 text-sm font-semibold text-rose-800 hover:bg-rose-100">Отклонить запрос</button>
                    </form>
                @endif
            </section>
        @endif

        @if($order->openDispute)
            <section class="rounded-2xl border border-rose-200 bg-rose-50 p-4 sm:p-5">
                <h2 class="font-semibold text-rose-900">Покупатель открыл спор</h2>
                <p class="mt-1 text-sm text-rose-700">{{ $order->openDispute->created_at?->format('d.m.Y H:i') }}</p>
                <div class="mt-3 rounded-xl bg-white px-3 py-2 text-sm text-neutral-700">
                    <div class="font-semibold">{{ $order->openDispute->reason }}</div>
                    @if($order->openDispute->details)
                        <div class="mt-1">{{ $order->openDispute->details }}</div>
                    @endif
                </div>
                <p class="mt-3 text-sm text-rose-800">Сохраните переписку и отвечайте покупателю через чат. Поддержка увидит спор в админке.</p>
            </section>
        @endif

        <div class="grid gap-4 xl:grid-cols-[360px_minmax(0,1fr)]">
            {{-- Покупатель --}}
            <div class="min-w-0 overflow-hidden rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
                <div class="flex items-start gap-4">
                    <img data-image-candidates="{{ json_encode($order->user->avatar_candidates ?? []) }}" data-image-fallback="{{ asset('images/avatar-placeholder.svg') }}" src="{{ $order->user->avatar_url ?? asset('images/avatar-placeholder.svg') }}"
                         class="h-14 w-14 rounded-xl border border-neutral-200 object-cover" alt="avatar">

                    <div class="min-w-0 flex-1 space-y-1">
                        <h2 class="text-sm font-semibold text-neutral-800">Покупатель</h2>

                        <div class="text-sm font-medium text-neutral-900">
                            {{ $order->buyer_name }}
                        </div>

                        <div class="text-xs text-neutral-500">
                            ID: {{ $order->user_id }}
                        </div>

                        @if(!empty($order->buyer_phone))
                            <div class="flex items-center gap-1 pt-1 text-xs text-neutral-700">
                                <i class="ri-phone-line text-neutral-500 text-sm"></i>
                                <span>{{ $order->buyer_phone }}</span>
                            </div>
                        @endif

                        @if(isset($order->buyer_email))
                            <div class="flex min-w-0 items-center gap-1 text-xs text-neutral-500">
                                <i class="ri-mail-line text-neutral-500 text-sm"></i>
                                <span class="min-w-0 break-all">{{ $order->buyer_email }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('seller.orders.chat.buyer', $order) }}" class="mt-4">
                    @csrf
                    <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm font-semibold text-neutral-700 transition hover:border-brand-200 hover:bg-brand-50 disabled:cursor-not-allowed disabled:opacity-50"
                            @disabled(! $primaryProduct || ! $order->user->exists)>
                        <i class="ri-chat-3-line"></i>
                        Написать покупателю
                    </button>
                </form>
            </div>

            <x-order-timeline :order="$order" :compact="true" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            {{-- Доставка --}}
            <div class="space-y-3 rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i class="ri-truck-line text-lg"></i></span>
                    <div>
                        <h2 class="font-semibold text-neutral-900">Доставка</h2>
                        <p class="text-xs text-neutral-500">Способ и адрес передачи</p>
                    </div>
                </div>

                <div class="text-sm font-semibold text-neutral-900">
                    {{ $order->delivery_method_label }}
                </div>

                <div class="break-words text-sm leading-6 text-neutral-500">
                    {{ ($order->address_snapshot['full'] ?? null) ?: 'Адрес не указан' }}
                    @if(filled($order->address_snapshot['comment'] ?? null))
                        <p>{{ $order->address_snapshot['comment'] }}</p>
                    @endif
                </div>
                <div class="text-sm font-semibold text-neutral-900">
                    Стоимость: {{ number_format($order->delivery_cost, 2, ',', ' ') }} {{ \App\Models\Product::currencySymbol($order->currency) }}
                </div>
                <div class="rounded-xl bg-brand-50 px-3 py-2 text-xs leading-5 text-brand-700">
                    Уточните с покупателем срок и детали передачи товара в чате или при обработке заказа.
                </div>
            </div>

            {{-- Оплата --}}
            <div class="space-y-3 rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i class="ri-bank-card-line text-lg"></i></span>
                    <div>
                        <h2 class="font-semibold text-neutral-900">Оплата</h2>
                        <p class="text-xs text-neutral-500">Способ и важные даты</p>
                    </div>
                </div>

                <div class="text-sm font-semibold text-neutral-900">
                    {{ $order->payment_method_label }}
                </div>
                @if($isPickupV2)
                    <div class="text-sm text-neutral-700">{{ $order->pickup_payment_status_label }}</div>
                    <div class="text-xs text-neutral-500">{{ $order->buyer_confirmed_at ? 'Получение подтверждено покупателем' : 'Получение покупателем не подтверждено' }}</div>
                @endif

                <div class="space-y-1 text-xs text-neutral-500">
                    <div>
                        Создан: {{ $order->created_at?->format('d.m.Y H:i') }}
                    </div>
                    @if($isPickupV2 && $order->ready_for_pickup_at)
                        <div>Готов к самовывозу: {{ $order->ready_for_pickup_at->format('d.m.Y H:i') }}</div>
                    @endif
                    @if($isPickupV2 && $order->buyer_confirmed_at)
                        <div>Покупатель подтвердил получение: {{ $order->buyer_confirmed_at->format('d.m.Y H:i') }}</div>
                    @endif
                    @if($isPickupV2 && $order->status_ru === 'Завершён' && $order->completed_at)
                        <div>Завершён: {{ $order->completed_at->format('d.m.Y H:i') }}</div>
                    @endif
                    @if($isLegacy && $order->paid_at)
                        <div>
                            Оплачен: {{ $order->paid_at->format('d.m.Y H:i') }}
                        </div>
                    @endif
                    @if($isLegacy && $order->shipped_at)
                        <div>
                            Отправлен: {{ $order->shipped_at->format('d.m.Y H:i') }}
                        </div>
                    @endif
                    @if($isLegacy && $order->delivered_at)
                        <div>
                            Доставлен: {{ $order->delivered_at->format('d.m.Y H:i') }}
                        </div>
                    @endif
                    @if($order->canceled_at)
                        <div class="text-rose-500">
                            Отменён: {{ $order->canceled_at->format('d.m.Y H:i') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
