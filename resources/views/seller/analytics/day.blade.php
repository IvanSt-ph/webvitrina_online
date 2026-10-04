<x-seller-layout :title="'Статистика за ' . $date" :hideHeader="true">

@php
    $totalViews = $stats->sum('views');
    $totalFavs  = $stats->sum('favorites');
    $totalCarts = $stats->sum('carts');
    $totalActions = $totalFavs + $totalCarts;
    $totalCtr = $totalViews > 0
        ? round($totalActions * 100 / $totalViews, 1)
        : 0;
@endphp

<div class="min-h-screen w-full overflow-x-hidden bg-white px-3 py-4 pb-28 text-neutral-900 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">
    <div class="w-full space-y-5 sm:space-y-6">

        {{-- Заголовок --}}
        <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <a href="{{ route('seller.analytics.index') }}"
                   class="inline-flex items-center gap-2 text-sm font-medium text-neutral-500 transition hover:text-brand-600">
                    <i class="ri-arrow-left-line" aria-hidden="true"></i>
                    Вся аналитика
                </a>

                <div class="mt-4 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                    <i class="ri-calendar-check-line" aria-hidden="true"></i>
                    Детализация дня
                </div>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">
                    Статистика за {{ $date }}
                </h1>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">
                    Активность опубликованных товаров за выбранный день: просмотры, добавления в избранное и корзину.
                </p>
            </div>

            <a href="{{ route('seller.analytics.index') }}"
               class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 text-sm font-semibold text-neutral-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 sm:w-auto">
                <i class="ri-line-chart-line" aria-hidden="true"></i>
                Вернуться к аналитике
            </a>
        </header>

        {{-- KPI --}}
        <section class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Итоги за день">
            <article class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-medium leading-5 text-neutral-500 sm:text-sm">Просмотры</p>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                        <i class="ri-eye-line text-lg" aria-hidden="true"></i>
                    </span>
                </div>
                <p class="mt-2 text-xl font-bold tracking-tight text-neutral-950 sm:text-2xl">{{ $totalViews }}</p>
                <p class="mt-1 text-[11px] leading-4 text-neutral-400 sm:text-xs">Открытий карточек</p>
            </article>

            <article class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-medium leading-5 text-neutral-500 sm:text-sm">Избранное</p>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                        <i class="ri-heart-line text-lg" aria-hidden="true"></i>
                    </span>
                </div>
                <p class="mt-2 text-xl font-bold tracking-tight text-neutral-950 sm:text-2xl">{{ $totalFavs }}</p>
                <p class="mt-1 text-[11px] leading-4 text-neutral-400 sm:text-xs">Добавлений за день</p>
            </article>

            <article class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-medium leading-5 text-neutral-500 sm:text-sm">Корзины</p>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <i class="ri-shopping-cart-2-line text-lg" aria-hidden="true"></i>
                    </span>
                </div>
                <p class="mt-2 text-xl font-bold tracking-tight text-neutral-950 sm:text-2xl">{{ $totalCarts }}</p>
                <p class="mt-1 text-[11px] leading-4 text-neutral-400 sm:text-xs">Добавлений за день</p>
            </article>

            <article class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-medium leading-5 text-neutral-500 sm:text-sm">Общий CTR</p>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <i class="ri-percent-line text-lg" aria-hidden="true"></i>
                    </span>
                </div>
                <p class="mt-2 text-xl font-bold tracking-tight text-neutral-950 sm:text-2xl">{{ $totalCtr }}%</p>
                <p class="mt-1 text-[11px] leading-4 text-neutral-400 sm:text-xs">Избранное и корзины</p>
            </article>
        </section>

        {{-- Товары --}}
        <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
            <div class="flex flex-col gap-2 border-b border-neutral-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <i class="ri-box-3-line text-lg" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-neutral-950">Товары с активностью</h2>
                        <p class="mt-0.5 text-xs text-neutral-500">Отсортированы по количеству просмотров</p>
                    </div>
                </div>

                @if($stats->count())
                    <span class="inline-flex w-fit items-center rounded-full bg-neutral-100 px-3 py-1 text-xs font-semibold text-neutral-600">
                        {{ $stats->count() }} {{ trans_choice('товар|товара|товаров', $stats->count()) }}
                    </span>
                @endif
            </div>

            @if($stats->count())
                <div class="hidden grid-cols-[minmax(0,1fr)_repeat(4,minmax(90px,0.42fr))] gap-3 border-b border-neutral-100 bg-neutral-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-neutral-400 md:grid">
                    <div>Товар</div>
                    <div class="text-right">Просмотры</div>
                    <div class="text-right">Избранное</div>
                    <div class="text-right">Корзины</div>
                    <div class="text-right">CTR</div>
                </div>

                <div class="divide-y divide-neutral-100">
                    @foreach($stats as $s)
                        @php
                            $ctr = $s->views > 0
                                ? round(($s->favorites + $s->carts) * 100 / $s->views, 1)
                                : 0;
                        @endphp

                        <article class="grid gap-4 px-4 py-4 transition hover:bg-neutral-50/70 sm:px-5 md:grid-cols-[minmax(0,1fr)_repeat(4,minmax(90px,0.42fr))] md:items-center md:gap-3">
                            <div class="min-w-0">
                                <a href="{{ route('product.show', $s->slug ?? $s->id) }}"
                                   class="group inline-flex max-w-full items-center gap-2 font-semibold text-neutral-900 transition hover:text-brand-600">
                                    <span class="truncate">{{ $s->title }}</span>
                                    <i class="ri-arrow-right-up-line shrink-0 text-neutral-300 transition group-hover:text-brand-500" aria-hidden="true"></i>
                                </a>
                            </div>

                            <dl class="grid grid-cols-2 gap-2 sm:grid-cols-4 md:contents">
                                <div class="rounded-xl bg-neutral-50 px-3 py-2 md:bg-transparent md:p-0 md:text-right">
                                    <dt class="text-[11px] text-neutral-400 md:hidden">Просмотры</dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-neutral-900 md:mt-0">{{ $s->views }}</dd>
                                </div>
                                <div class="rounded-xl bg-rose-50/60 px-3 py-2 md:bg-transparent md:p-0 md:text-right">
                                    <dt class="text-[11px] text-neutral-400 md:hidden">Избранное</dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-neutral-900 md:mt-0">{{ $s->favorites }}</dd>
                                </div>
                                <div class="rounded-xl bg-amber-50/70 px-3 py-2 md:bg-transparent md:p-0 md:text-right">
                                    <dt class="text-[11px] text-neutral-400 md:hidden">Корзины</dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-neutral-900 md:mt-0">{{ $s->carts }}</dd>
                                </div>
                                <div class="rounded-xl bg-brand-50 px-3 py-2 md:bg-transparent md:p-0 md:text-right">
                                    <dt class="text-[11px] text-brand-500 md:hidden">CTR</dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-brand-700 md:mt-0">
                                        <span class="md:inline-flex md:rounded-lg md:bg-brand-50 md:px-2.5 md:py-1">{{ $ctr }}%</span>
                                    </dd>
                                </div>
                            </dl>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="flex min-h-52 flex-col items-center justify-center px-4 py-10 text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-neutral-100 text-neutral-400">
                        <i class="ri-bar-chart-box-line text-2xl" aria-hidden="true"></i>
                    </span>
                    <h3 class="mt-3 font-semibold text-neutral-900">За этот день активности нет</h3>
                    <p class="mt-1 max-w-sm text-sm leading-6 text-neutral-500">Вернитесь к общей аналитике и выберите другой день или более широкий период.</p>
                    <a href="{{ route('seller.analytics.index') }}"
                       class="mt-4 inline-flex h-10 items-center justify-center rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white transition hover:bg-brand-600">
                        Выбрать другой период
                    </a>
                </div>
            @endif
        </section>
    </div>
</div>

@include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
