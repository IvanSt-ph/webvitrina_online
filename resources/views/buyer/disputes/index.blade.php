<x-buyer-layout title="Мои обращения и споры">
    @php
        $statusMap = [
            'all' => ['label' => 'Все', 'icon' => 'ri-inbox-line', 'active' => 'border-brand-200 bg-brand-50 text-brand-700'],
            \App\Models\OrderDispute::STATUS_OPEN => ['label' => 'Открытые', 'icon' => 'ri-error-warning-line', 'active' => 'border-danger-200 bg-danger-50 text-danger-700'],
            \App\Models\OrderDispute::STATUS_RESOLVED => ['label' => 'Решённые', 'icon' => 'ri-checkbox-circle-line', 'active' => 'border-success-200 bg-success-50 text-success-700'],
            \App\Models\OrderDispute::STATUS_CLOSED => ['label' => 'Закрытые', 'icon' => 'ri-lock-line', 'active' => 'border-neutral-300 bg-neutral-100 text-neutral-700'],
        ];
        $badgeMap = [
            \App\Models\OrderDispute::STATUS_OPEN => 'border-danger-200 bg-danger-50 text-danger-700',
            \App\Models\OrderDispute::STATUS_RESOLVED => 'border-success-200 bg-success-50 text-success-700',
            \App\Models\OrderDispute::STATUS_CLOSED => 'border-neutral-200 bg-neutral-100 text-neutral-600',
        ];
    @endphp

    <div class="min-h-screen bg-white pb-24 text-neutral-800 md:pb-0">
        <header class="border-b border-neutral-200 bg-white">
            <div class="flex w-full flex-col gap-5 px-4 py-6 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <div class="min-w-0">
                    <div class="mb-2 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                        <i class="ri-scales-3-line text-base" aria-hidden="true"></i>
                        Поддержка заказов
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight text-neutral-900 sm:text-[28px]">Мои обращения и споры</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-neutral-500">Следите за рассмотрением спорных ситуаций и решениями поддержки.</p>
                </div>
                <x-action-button as="a" :href="route('orders.index')" class="shrink-0">
                    <i class="ri-shopping-bag-3-line" aria-hidden="true"></i>
                    Мои заказы
                </x-action-button>
            </div>
        </header>

        <main class="w-full space-y-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8 lg:py-12">
            <section class="grid overflow-hidden rounded-2xl border border-brand-100 bg-brand-50/50 lg:grid-cols-[minmax(0,1fr)_380px]">
                <div class="flex flex-col justify-center p-5 sm:p-6 lg:p-7">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm ring-1 ring-brand-100">
                        <i class="ri-customer-service-2-line text-xl" aria-hidden="true"></i>
                    </div>
                    <h2 class="mt-4 text-xl font-semibold tracking-tight text-neutral-900 sm:text-2xl">Помощь по вашим заказам</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-600">Здесь собраны причина обращения, данные продавца, текущий статус и итоговое решение поддержки.</p>

                    <form method="GET" action="{{ route('disputes.index') }}" class="mt-5 flex max-w-3xl flex-col gap-3 sm:flex-row">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <label class="relative min-w-0 flex-1">
                            <span class="sr-only">Поиск обращений</span>
                            <i class="ri-search-line pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-lg text-neutral-400" aria-hidden="true"></i>
                            <input name="q" value="{{ $q }}" type="search" placeholder="Номер заказа, продавец или причина"
                                   class="h-12 w-full rounded-xl border-neutral-200 bg-white pl-11 pr-4 text-sm text-neutral-800 shadow-sm transition placeholder:text-neutral-400 hover:border-brand-200 focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                        </label>
                        <x-action-button type="submit" class="h-12 px-6"><i class="ri-search-line"></i>Найти</x-action-button>
                        @if($q !== '')
                            <x-secondary-action as="a" :href="route('disputes.index', ['status' => $status])" class="h-12"><i class="ri-close-line"></i>Сбросить</x-secondary-action>
                        @endif
                    </form>
                </div>

                <div class="grid grid-cols-2 border-t border-brand-100 bg-white/60 lg:border-l lg:border-t-0">
                    @foreach($statusMap as $key => $meta)
                        <a href="{{ route('disputes.index', ['status' => $key, 'q' => $q]) }}"
                           class="flex min-h-24 flex-col justify-center p-4 transition hover:bg-white {{ !$loop->even ? 'border-r border-brand-100' : '' }} {{ !$loop->last && $loop->index < 2 ? 'border-b border-brand-100' : '' }} {{ $status === $key ? $meta['active'] : 'text-neutral-600' }}"
                           @if($status === $key) aria-current="page" @endif>
                            <div class="flex items-center justify-between gap-3"><span class="text-2xl font-semibold tracking-tight">{{ $counters[$key] ?? 0 }}</span><i class="{{ $meta['icon'] }} text-lg opacity-70"></i></div>
                            <span class="mt-2 text-sm font-semibold">{{ $meta['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </section>

            @if(session('success'))
                <div class="flex items-center gap-3 rounded-2xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700" role="status"><i class="ri-checkbox-circle-fill text-lg"></i><span>{{ session('success') }}</span></div>
            @endif

            <div class="border-b border-neutral-200 pb-4">
                <h2 class="text-lg font-semibold text-neutral-900">{{ $statusMap[$status]['label'] ?? 'Все' }} обращения</h2>
                <p class="mt-1 text-sm text-neutral-500">{{ $q !== '' ? 'Результаты поиска по запросу «'.$q.'»' : 'Последние изменения и решения по вашим обращениям.' }}</p>
            </div>

            <section class="space-y-5" aria-label="Список обращений">
                @forelse($disputes as $dispute)
                    @php
                        $order = $dispute->order;
                        $meta = $statusMap[$dispute->status] ?? $statusMap[\App\Models\OrderDispute::STATUS_OPEN];
                        $badgeClass = $badgeMap[$dispute->status] ?? $badgeMap[\App\Models\OrderDispute::STATUS_OPEN];
                    @endphp

                    <article class="overflow-hidden rounded-2xl border border-neutral-200 bg-white transition duration-300 hover:border-brand-200 hover:shadow-xl hover:shadow-brand-100/40">
                        <div class="grid gap-6 p-5 sm:p-6 xl:grid-cols-[minmax(0,1fr)_260px]">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badgeClass }}"><i class="{{ $meta['icon'] }}"></i>{{ $meta['label'] }}</span>
                                    <span class="text-xs text-neutral-400">Обращение #{{ $dispute->id }} · {{ $dispute->created_at->format('d.m.Y H:i') }}</span>
                                </div>

                                @if($order)
                                    <a href="{{ route('orders.show', $order) }}" class="mt-4 block text-xl font-semibold text-neutral-900 transition hover:text-brand-700">Заказ {{ $order->number }}</a>
                                @else
                                    <div class="mt-4 text-xl font-semibold text-neutral-900">Заказ —</div>
                                @endif

                                <div class="mt-5 grid overflow-hidden rounded-xl bg-neutral-50 sm:grid-cols-3 sm:divide-x sm:divide-neutral-200">
                                    <div class="border-b border-neutral-200 p-4 sm:border-b-0"><div class="text-xs font-semibold uppercase tracking-wide text-neutral-400">Продавец</div><div class="mt-1.5 break-words text-sm font-semibold text-neutral-900">{{ $dispute->seller?->name ?? '—' }}</div></div>
                                    <div class="border-b border-neutral-200 p-4 sm:border-b-0"><div class="text-xs font-semibold uppercase tracking-wide text-neutral-400">Статус заказа</div><div class="mt-1.5 text-sm font-semibold text-neutral-900">{{ $order?->status_ru ?? $order?->status ?? '—' }}</div></div>
                                    <div class="p-4"><div class="text-xs font-semibold uppercase tracking-wide text-neutral-400">Сумма</div><div class="mt-1.5 text-sm font-semibold text-neutral-900">{{ $order?->formatted_total_price ?? '—' }}</div></div>
                                </div>

                                <div class="mt-5 border-l-4 border-brand-300 pl-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-neutral-400">Причина обращения</div>
                                    <div class="mt-1.5 break-words text-sm font-semibold text-neutral-900">{{ $dispute->reason }}</div>
                                    @if($dispute->details)<p class="mt-2 break-words text-sm leading-6 text-neutral-600">{{ $dispute->details }}</p>@endif
                                </div>

                                @if($dispute->resolution)
                                    <div class="mt-5 rounded-2xl border border-success-100 bg-success-50 p-4 text-sm text-success-900">
                                        <div class="flex items-center gap-2 font-semibold"><i class="ri-checkbox-circle-fill text-success-600"></i>Решение поддержки</div>
                                        <p class="mt-2 leading-6">{{ $dispute->resolution }}</p>
                                        @if($dispute->resolved_at)<div class="mt-2 text-xs text-success-700">{{ $dispute->resolved_at->format('d.m.Y H:i') }}</div>@endif
                                    </div>
                                @endif
                            </div>

                            <aside class="flex flex-col rounded-2xl bg-neutral-50 p-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-brand-600 shadow-sm"><i class="ri-information-line text-xl"></i></div>
                                <p class="mt-4 text-xs leading-5 text-neutral-500">Важные детали удобнее отправлять со страницы заказа — так поддержка видит его состав и историю статусов.</p>
                                @if($order)
                                    <div class="mt-auto space-y-2 pt-5">
                                        <x-action-button as="a" :href="route('orders.show', $order)" full size="sm"><i class="ri-shopping-bag-3-line"></i>Открыть заказ</x-action-button>
                                        <x-secondary-action as="a" :href="route('orders.show', $order) . '#order-support'" full size="sm"><i class="ri-customer-service-2-line"></i>Поддержка</x-secondary-action>
                                    </div>
                                @endif
                            </aside>
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-neutral-300 bg-neutral-50/60 px-6 py-16 text-center sm:py-20">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-600 ring-8 ring-white"><i class="ri-scales-3-line text-3xl"></i></div>
                        <h2 class="mt-6 text-xl font-semibold text-neutral-900">{{ $q !== '' ? 'Ничего не найдено' : 'Обращений и споров пока нет' }}</h2>
                        <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-neutral-500">{{ $q !== '' ? 'Попробуйте изменить запрос или сбросить поиск.' : 'Если по заказу возникнет проблема, откройте его страницу и создайте обращение в поддержку.' }}</p>
                        <div class="mt-7 flex justify-center">
                            @if($q !== '')
                                <x-secondary-action as="a" :href="route('disputes.index', ['status' => $status])"><i class="ri-refresh-line"></i>Сбросить поиск</x-secondary-action>
                            @else
                                <x-action-button as="a" :href="route('orders.index')"><i class="ri-shopping-bag-3-line"></i>Перейти к заказам</x-action-button>
                            @endif
                        </div>
                    </div>
                @endforelse
            </section>

            @if($disputes->hasPages())
                <div class="border-t border-neutral-200 pt-6">{{ $disputes->links() }}</div>
            @endif
        </main>
    </div>

    @include('layouts.mobile-bottom-nav')
</x-buyer-layout>
