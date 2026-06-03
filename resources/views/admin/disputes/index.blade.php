@extends('admin.layout')

@section('title', 'Споры по заказам')

@section('content')
@php
    $statusMap = [
        'all' => ['label' => 'Все', 'class' => 'border-slate-200 bg-white text-slate-600'],
        \App\Models\OrderDispute::STATUS_OPEN => ['label' => 'Открытые', 'class' => 'border-rose-200 bg-rose-50 text-rose-700'],
        \App\Models\OrderDispute::STATUS_RESOLVED => ['label' => 'Решённые', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'],
        \App\Models\OrderDispute::STATUS_CLOSED => ['label' => 'Закрытые', 'class' => 'border-slate-200 bg-slate-50 text-slate-600'],
    ];
@endphp

<div class="space-y-5">
    <header class="rounded-2xl border border-rose-100 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-rose-600">
                    <i class="ri-scales-3-line"></i>
                    Поддержка заказов
                </div>
                <h1 class="mt-3 text-2xl font-bold text-slate-950">Споры по заказам</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">
                    Очередь конфликтных заказов: товар не получен, повреждён, не совпадает с описанием или стороны не договорились.
                </p>
            </div>

            <a href="{{ route('admin.orders.index') }}"
               class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="ri-shopping-bag-3-line"></i>
                Все заказы
            </a>
        </div>
    </header>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($statusMap as $key => $meta)
            <a href="{{ route('admin.disputes.index', ['status' => $key, 'q' => $q]) }}"
               class="rounded-xl border p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $status === $key ? $meta['class'] : 'border-slate-200 bg-white text-slate-600' }}">
                <div class="text-2xl font-bold">{{ $counters[$key] ?? 0 }}</div>
                <div class="mt-1 text-sm font-semibold">{{ $meta['label'] }}</div>
            </a>
        @endforeach
    </section>

    <form method="GET" action="{{ route('admin.disputes.index') }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <input type="hidden" name="status" value="{{ $status }}">
        <div class="grid gap-3 md:grid-cols-[1fr_auto]">
            <label class="relative">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input name="q" value="{{ $q }}" type="search"
                       placeholder="Поиск по номеру заказа, покупателю, продавцу, причине"
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

            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold {{ $badge['class'] }}">
                                {{ $badge['label'] }}
                            </span>
                            <span class="text-xs text-slate-400">#{{ $dispute->id }} · {{ $dispute->created_at->diffForHumans() }}</span>
                        </div>

                        <a href="{{ route('admin.orders.show', $order) }}" class="mt-2 block text-lg font-bold text-slate-950 hover:text-indigo-600">
                            Заказ {{ $order?->number ?? '—' }}
                        </a>

                        <div class="mt-2 grid gap-2 text-sm text-slate-600 md:grid-cols-3">
                            <div class="rounded-xl bg-slate-50 p-3">
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Покупатель</div>
                                <div class="mt-1 font-semibold text-slate-900">{{ $dispute->user?->name ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $dispute->user?->email ?? '—' }}</div>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3">
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Продавец</div>
                                <div class="mt-1 font-semibold text-slate-900">{{ $dispute->seller?->name ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $dispute->seller?->email ?? '—' }}</div>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-3">
                                <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Статус заказа</div>
                                <div class="mt-1 font-semibold text-slate-900">{{ $order?->status_ru ?? $order?->status ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $order?->formatted_total_price ?? '' }}</div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl border border-slate-100 bg-white p-3">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Причина спора</div>
                            <div class="mt-1 text-sm font-semibold text-slate-800">{{ $dispute->reason }}</div>
                            @if($dispute->details)
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $dispute->details }}</p>
                            @endif
                        </div>

                        <div class="mt-3 rounded-xl bg-slate-50 p-3">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-400">Товары в заказе</div>
                            <div class="mt-2 space-y-2">
                                @forelse($order?->items ?? [] as $item)
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <span class="truncate font-semibold text-slate-800">{{ $item->product?->title ?? $item->product_title ?? 'Товар удалён' }}</span>
                                        <span class="shrink-0 text-slate-500">{{ $item->quantity }} × {{ number_format((float) $item->price, 2, ',', ' ') }}</span>
                                    </div>
                                @empty
                                    <div class="text-sm text-slate-400">Состав заказа не найден.</div>
                                @endforelse
                            </div>
                        </div>

                        @if($dispute->resolved_at)
                            <div class="mt-3 rounded-xl border border-slate-100 bg-white p-3 text-sm text-slate-600">
                                <span class="font-semibold text-slate-800">Решение:</span>
                                {{ $dispute->resolution ?: '—' }}
                                <div class="mt-1 text-xs text-slate-400">
                                    {{ $dispute->resolver?->name ?? 'Администратор' }} · {{ $dispute->resolved_at->format('d.m.Y H:i') }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <aside class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                        <div class="grid gap-2">
                            <a href="{{ route('admin.orders.show', $order) }}"
                               class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-slate-900 px-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                <i class="ri-shopping-bag-3-line"></i>
                                Открыть заказ
                            </a>
                            <a href="{{ route('admin.chats.index', ['q' => $order?->number]) }}"
                               class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                <i class="ri-message-3-line"></i>
                                Найти чаты
                            </a>
                        </div>

                        @if($dispute->status === \App\Models\OrderDispute::STATUS_OPEN)
                            <div class="mt-3 space-y-2">
                                <form method="POST" action="{{ route('admin.disputes.resolve', $dispute) }}" class="space-y-2">
                                    @csrf
                                    <textarea name="resolution" rows="4" maxlength="1200" required placeholder="Решение для покупателя и продавца"
                                              class="w-full rounded-xl border border-slate-200 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"></textarea>
                                    <button class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                        <i class="ri-check-line"></i>
                                        Решить спор
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('admin.disputes.close', $dispute) }}" class="space-y-2">
                                    @csrf
                                    <textarea name="resolution" rows="3" maxlength="1200" required placeholder="Почему спор закрыт без решения в пользу стороны"
                                              class="w-full rounded-xl border border-slate-200 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"></textarea>
                                    <button class="inline-flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                        <i class="ri-close-line"></i>
                                        Закрыть
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="mt-3 rounded-xl bg-white p-3 text-sm text-slate-500">
                                Спор уже обработан. История решения сохранена в карточке.
                            </div>
                        @endif
                    </aside>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-10 text-center shadow-sm">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <i class="ri-shield-check-line text-2xl"></i>
                </div>
                <h2 class="mt-4 text-lg font-bold text-slate-900">Споров нет</h2>
                <p class="mt-1 text-sm text-slate-500">Когда покупатель откроет спор по заказу, он появится здесь.</p>
            </div>
        @endforelse
    </section>

    {{ $disputes->links() }}
</div>
@endsection
