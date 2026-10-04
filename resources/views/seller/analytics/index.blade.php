<x-seller-layout title="Аналитика продавца" :hideHeader="true">

<div class="min-h-screen w-full overflow-x-hidden bg-white px-3 py-4 pb-28 text-neutral-900 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">
<div class="w-full space-y-5 sm:space-y-6">

@php
    function delta($now, $prev) {
        if ($prev == 0) return $now > 0 ? '+100%' : '0%';
        $d = (($now - $prev) / $prev) * 100;
        return ($d >= 0 ? '+' : '') . round($d, 1) . '%';
    }
@endphp

<header class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_420px] lg:items-end">
    <div class="min-w-0">
        <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
            <i class="ri-line-chart-line"></i>
            Аналитика продавца
        </div>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">Понимайте, что происходит с товарами</h1>
        <p class="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">
            Смотрите просмотры, избранное, добавления в корзину и динамику за выбранный период. Данные помогают понять, какие карточки стоит улучшить первыми.
        </p>
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-full border {{ $sellerPlanProfile['class'] }} px-3 py-1 text-xs font-semibold">
                <i class="ri-vip-crown-line"></i>
                {{ $sellerPlanProfile['label'] }}
            </span>
            <span class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-neutral-50 px-3 py-1 text-xs font-semibold text-neutral-600">
                <i class="ri-calendar-line"></i>
                {{ $from }} - {{ $to }}
            </span>
        </div>
    </div>

    <form method="GET" class="rounded-2xl border border-neutral-200 bg-white p-3 sm:p-4">
        <div class="grid grid-cols-3 gap-2">
            @foreach([7,14,30] as $p)
                <button
                    type="submit"
                    name="period"
                    value="{{ $p }}"
                    class="h-10 rounded-xl border text-xs font-semibold transition sm:text-sm
                           {{ $period == $p
                                ? 'border-brand-500 bg-brand-500 text-white'
                                : 'border-neutral-200 bg-white text-neutral-600 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700' }}">
                    {{ $p }} дней
                </button>
            @endforeach
        </div>

        <div class="mt-3 grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
            <input type="date" name="from" value="{{ $from }}"
                   class="h-10 min-w-0 wv-input px-2 text-xs sm:text-sm">

            <input type="date" name="to" value="{{ $to }}"
                   class="h-10 min-w-0 wv-input px-2 text-xs sm:text-sm">

            <button type="submit"
                    class="h-10 rounded-xl bg-brand-500 px-4 text-xs font-semibold text-white transition hover:bg-brand-600 sm:text-sm">
                Обновить
            </button>
        </div>
    </form>
</header>


{{-- KPI --}}
<section class="grid grid-cols-2 gap-3 lg:grid-cols-4">

    {{-- Суммарная активность --}}
    @php
        $totalNow = $summary->views + $summary->favorites + $summary->carts;
        $totalPrev = $prev->views + $prev->favorites + $prev->carts;
        $d = delta($totalNow, $totalPrev);
    @endphp

    <div class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <p class="text-xs font-medium leading-5 text-neutral-500 sm:text-sm">Суммарная активность</p>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i class="ri-pulse-line text-lg"></i></span>
        </div>
        <h2 class="mt-2 text-xl font-bold tracking-tight text-neutral-950 sm:text-2xl">{{ $totalNow }}</h2>
        <p class="mt-1 flex items-center gap-1 text-[11px] leading-4 sm:text-xs {{ str_starts_with($d,'+')?'text-emerald-600':'text-rose-600' }}">
            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ str_starts_with($d,'+')?'bg-emerald-500':'bg-rose-500' }}"></span>
            {{ $d }} к прошлому периоду
        </p>
    </div>

    {{-- Просмотры --}}
    @php $d = delta($summary->views,$prev->views); @endphp
    <div class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <p class="text-xs font-medium leading-5 text-neutral-500 sm:text-sm">Просмотры</p>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600"><i class="ri-eye-line text-lg"></i></span>
        </div>
        <h2 class="mt-2 text-xl font-bold tracking-tight text-neutral-950 sm:text-2xl">{{ $summary->views }}</h2>
        <p class="mt-1 flex items-center gap-1 text-[11px] leading-4 sm:text-xs {{ str_starts_with($d,'+')?'text-emerald-600':'text-rose-600' }}">
            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ str_starts_with($d,'+')?'bg-emerald-500':'bg-rose-500' }}"></span>
            {{ $d }} к прошлому периоду
        </p>
    </div>

    {{-- Избранное --}}
    @php $d = delta($summary->favorites,$prev->favorites); @endphp
    <div class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <p class="text-xs font-medium leading-5 text-neutral-500 sm:text-sm">Избранное</p>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600"><i class="ri-heart-line text-lg"></i></span>
        </div>
        <h2 class="mt-2 text-xl font-bold tracking-tight text-neutral-950 sm:text-2xl">{{ $summary->favorites }}</h2>
        <p class="mt-1 flex items-center gap-1 text-[11px] leading-4 sm:text-xs {{ str_starts_with($d,'+')?'text-emerald-600':'text-rose-600' }}">
            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ str_starts_with($d,'+')?'bg-emerald-500':'bg-rose-500' }}"></span>
            {{ $d }} к прошлому периоду
        </p>
    </div>

    {{-- Корзины --}}
    @php $d = delta($summary->carts,$prev->carts); @endphp
    <div class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <p class="text-xs font-medium leading-5 text-neutral-500 sm:text-sm">Корзины</p>
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><i class="ri-shopping-cart-2-line text-lg"></i></span>
        </div>
        <h2 class="mt-2 text-xl font-bold tracking-tight text-neutral-950 sm:text-2xl">{{ $summary->carts }}</h2>
        <p class="mt-1 flex items-center gap-1 text-[11px] leading-4 sm:text-xs {{ str_starts_with($d,'+')?'text-emerald-600':'text-rose-600' }}">
            <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ str_starts_with($d,'+')?'bg-emerald-500':'bg-rose-500' }}"></span>
            {{ $d }} к прошлому периоду
        </p>
    </div>
</section>

@if($sellerPlanProfile['analytics_enabled'] && $advanced)
    <section class="grid gap-3 md:grid-cols-3">
        <div class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium text-neutral-500">Конверсия в избранное</p>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600"><i class="ri-heart-add-line text-lg"></i></span>
            </div>
            <h2 class="mt-2 text-2xl font-bold text-neutral-950">{{ $advanced['favorite_rate'] }}%</h2>
            <p class="mt-1 text-xs leading-5 text-neutral-500">Доля добавлений в избранное от просмотров</p>
        </div>

        <div class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium text-neutral-500">Конверсия в корзину</p>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i class="ri-shopping-cart-line text-lg"></i></span>
            </div>
            <h2 class="mt-2 text-2xl font-bold text-neutral-950">{{ $advanced['cart_rate'] }}%</h2>
            <p class="mt-1 text-xs leading-5 text-neutral-500">Показывает товары с реальным покупательским интересом</p>
        </div>

        <div class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium text-neutral-500">Без просмотров</p>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><i class="ri-eye-off-line text-lg"></i></span>
            </div>
            <h2 class="mt-2 text-2xl font-bold text-neutral-950">{{ $advanced['inactive_products'] }}</h2>
            <p class="mt-1 text-xs leading-5 text-neutral-500">{{ $advanced['products_with_cart_interest'] }} товаров попадали в корзину</p>
        </div>
    </section>
@else
    <section class="rounded-2xl border border-brand-100 bg-brand-50 p-4 sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-brand-950">Расширенная аналитика доступна с Pro</h2>
                <p class="mt-1 text-sm leading-6 text-brand-800/80">Конверсия в корзину, товары без просмотров и дополнительные сигналы помогают быстрее понимать, что улучшать.</p>
            </div>
            <a href="{{ route('seller.plans.index') }}" class="inline-flex h-10 shrink-0 items-center justify-center rounded-xl bg-brand-500 px-4 text-sm font-semibold text-white transition hover:bg-brand-600">
                Посмотреть уровни магазина
            </a>
        </div>
    </section>
@endif


{{-- Пончики и ТОП --}}
<section class="grid gap-5 lg:grid-cols-3">

    {{-- Пончик --}}
    <div class="min-w-0 rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
        <div class="mb-4 flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i class="ri-pie-chart-2-line text-lg"></i></span>
            <h2 class="text-base font-semibold text-neutral-950">Распределение активности</h2>
        </div>

        <div class="relative w-full overflow-x-hidden" style="min-height:240px;">
            <canvas id="donutChart" class="w-full max-w-full"></canvas>
        </div>
    </div>

    {{-- ТОП --}}
    <div class="min-w-0 rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5 lg:col-span-2">
        <div class="mb-4 flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-600"><i class="ri-bar-chart-horizontal-line text-lg"></i></span>
            <h2 class="text-base font-semibold text-neutral-950">ТОП по просмотрам</h2>
        </div>

        @if($topProducts->count())
            <div class="relative w-full overflow-x-hidden" style="min-height:{{ max($topProducts->count(),3)*42 }}px;">
                <canvas id="barChart"></canvas>
            </div>
            <p class="mt-2 text-xs text-neutral-400">Клик по полосе → карточка товара</p>
        @else
            <div class="flex min-h-60 items-center justify-center rounded-xl bg-neutral-50 px-4 text-sm text-neutral-500">Нет данных за выбранный период</div>
        @endif
    </div>
</section>


{{-- Таймлайн --}}
<section class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><i class="ri-line-chart-line text-lg"></i></span>
                <h2 class="text-base font-semibold text-neutral-950">Активность по дням</h2>
            </div>
            <p class="text-xs text-neutral-400">Клик по точке → детали дня</p>
        </div>

        <div class="relative w-full overflow-x-hidden" style="min-height:260px;">
            <canvas id="timelineChart"></canvas>
        </div>
</section>

</div>
</div>

{{-- Мобильная нижняя навигация --}}
@include('layouts.mobile-bottom-seller-nav')

<script src="{{ asset('js/seller-analytics.js') }}"></script>

<script>
window.donutData = @json(array_values($distribution));
window.topTitles = @json($topProducts->pluck('title'));
window.topViews  = @json($topProducts->pluck('views'));
window.topUrls   = @json($topProducts->pluck('url'));
window.tlLabels = @json($timeline->pluck('date'));
window.tlViews  = @json($timeline->pluck('views'));
window.tlFavs   = @json($timeline->pluck('favorites'));
window.tlCarts  = @json($timeline->pluck('carts'));
window.timelineDayUrlBase = @json(route('seller.analytics.day', ['date' => '___DATE___']));
</script>

</x-seller-layout>
