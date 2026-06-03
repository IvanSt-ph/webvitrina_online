<x-buyer-layout title="Мои обращения и споры">
    @php
        $statusMap = [
            'all' => ['label' => 'Все', 'class' => 'border-slate-200 bg-white text-slate-600'],
            \App\Models\OrderDispute::STATUS_OPEN => ['label' => 'Открытые', 'class' => 'border-rose-200 bg-rose-50 text-rose-700'],
            \App\Models\OrderDispute::STATUS_RESOLVED => ['label' => 'Решённые', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'],
            \App\Models\OrderDispute::STATUS_CLOSED => ['label' => 'Закрытые', 'class' => 'border-slate-200 bg-slate-50 text-slate-600'],
        ];
    @endphp

    <div class="w-full max-w-none space-y-5 bg-white px-3 py-4 pb-24 sm:px-6 sm:py-8">
        <header class="rounded-3xl border border-indigo-100 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-indigo-600">
                        <i class="ri-scales-3-line"></i>
                        Поддержка заказов
                    </span>
                    <h1 class="mt-3 text-2xl font-bold text-slate-950 sm:text-3xl">Мои обращения и споры</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                        Здесь собраны спорные ситуации по заказам: причина, продавец, статус рассмотрения и решение поддержки.
                    </p>
                </div>
                <a href="{{ route('orders.index') }}"
                   class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    <i class="ri-shopping-bag-3-line"></i>
                    Мои заказы
                </a>
            </div>
        </header>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($statusMap as $key => $meta)
                <a href="{{ route('disputes.index', ['status' => $key, 'q' => $q]) }}"
                   class="rounded-2xl border p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $status === $key ? $meta['class'] : 'border-slate-200 bg-white text-slate-600' }}">
                    <div class="text-2xl font-bold">{{ $counters[$key] ?? 0 }}</div>
                    <div class="mt-1 text-sm font-semibold">{{ $meta['label'] }}</div>
                </a>
            @endforeach
        </section>

        <form method="GET" action="{{ route('disputes.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="grid gap-3 md:grid-cols-[1fr_auto]">
                <label class="relative">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input name="q" value="{{ $q }}" type="search"
                           placeholder="Поиск по номеру заказа, продавцу или причине"
                           class="h-11 w-full rounded-xl border border-slate-200 pl-10 pr-3 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                </label>
                <button class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 text-sm font-semibold text-white hover:bg-indigo-700">
                    <i class="ri-search-line"></i>
                    Найти
                </button>
            </div>
        </form>

        <section class="space-y-3">
            @forelse($disputes as $dispute)
                @php
                    $order = $dispute->order;
                    $badge = $statusMap[$dispute->status] ?? $statusMap[\App\Models\OrderDispute::STATUS_OPEN];
                @endphp

                <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_280px]">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $badge['class'] }}">
                                    {{ $badge['label'] }}
                                </span>
                                <span class="text-xs text-slate-400">#{{ $dispute->id }} · {{ $dispute->created_at->format('d.m.Y H:i') }}</span>
                            </div>

                            @if($order)
                                <a href="{{ route('orders.show', $order) }}" class="mt-3 block text-lg font-bold text-slate-950 hover:text-indigo-600">
                                    Заказ {{ $order->number }}
                                </a>
                            @else
                                <div class="mt-3 text-lg font-bold text-slate-950">Заказ —</div>
                            @endif

                            <div class="mt-3 grid gap-2 text-sm sm:grid-cols-3">
                                <div class="rounded-2xl bg-slate-50 p-3">
                                    <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Продавец</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ $dispute->seller?->name ?? '—' }}</div>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3">
                                    <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Статус заказа</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ $order?->status_ru ?? $order?->status ?? '—' }}</div>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-3">
                                    <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Сумма</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ $order?->formatted_total_price ?? '—' }}</div>
                                </div>
                            </div>

                            <div class="mt-4 rounded-2xl border border-slate-100 bg-white p-3">
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Причина</div>
                                <div class="mt-1 text-sm font-semibold text-slate-800">{{ $dispute->reason }}</div>
                                @if($dispute->details)
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $dispute->details }}</p>
                                @endif
                            </div>

                            @if($dispute->resolution)
                                <div class="mt-3 rounded-2xl border border-emerald-100 bg-emerald-50 p-3 text-sm text-emerald-900">
                                    <div class="font-bold">Решение поддержки</div>
                                    <p class="mt-1 leading-6">{{ $dispute->resolution }}</p>
                                    @if($dispute->resolved_at)
                                        <div class="mt-2 text-xs text-emerald-700">{{ $dispute->resolved_at->format('d.m.Y H:i') }}</div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <aside class="rounded-2xl border border-slate-100 bg-slate-50 p-3">
                            <div class="grid gap-2">
                                @if($order)
                                    <a href="{{ route('orders.show', $order) }}"
                                       class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                        <i class="ri-shopping-bag-3-line"></i>
                                        Открыть заказ
                                    </a>
                                    <a href="{{ route('orders.show', $order) }}#order-support"
                                       class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                        <i class="ri-customer-service-2-line"></i>
                                        Поддержка
                                    </a>
                                @endif
                            </div>
                            <p class="mt-3 text-xs leading-5 text-slate-500">
                                Важные детали лучше писать со страницы заказа: так поддержка видит состав заказа и историю статусов.
                            </p>
                        </aside>
                    </div>
                </article>
            @empty
                <x-empty-state
                    icon="ri-scales-3-line"
                    title="Обращений и споров пока нет"
                    description="Если по заказу возникнет проблема, откройте страницу заказа и создайте обращение в поддержку или спор."
                >
                    <a href="{{ route('orders.index') }}" class="inline-flex h-10 items-center justify-center rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                        Перейти к заказам
                    </a>
                </x-empty-state>
            @endforelse
        </section>

        {{ $disputes->links() }}
    </div>
</x-buyer-layout>
