<x-seller-layout title="Аналитика продавца">

<div class="min-h-screen bg-white text-gray-800 pb-[5.5rem] px-3 sm:px-6 overflow-x-hidden">

@php
    function delta($now, $prev) {
        if ($prev == 0) return $now > 0 ? '+100%' : '0%';
        $d = (($now - $prev) / $prev) * 100;
        return ($d >= 0 ? '+' : '') . round($d, 1) . '%';
    }
@endphp

{{-- Заголовок --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Аналитика продавца</h1>
        <p class="text-sm text-gray-500 mt-1">
            Период: {{ $from }} — {{ $to }}
        </p>
        <div class="mt-2 inline-flex items-center gap-2 rounded-full border {{ $sellerPlanProfile['class'] }} px-3 py-1 text-xs font-semibold">
            <i class="ri-vip-crown-line"></i>
            {{ $sellerPlanProfile['label'] }}
        </div>
    </div>

    {{-- Фильтры --}}
    <form method="GET" class="flex flex-wrap gap-2 mt-4 sm:mt-0 w-full sm:w-auto">

        <div class="flex gap-2 w-full sm:w-auto justify-between sm:justify-start">
            @foreach([7,14,30] as $p)
                <button
                    type="submit"
                    name="period"
                    value="{{ $p }}"
                    class="flex-1 sm:flex-none text-center px-3 py-1.5 rounded-lg border text-xs sm:text-sm transition
                           {{ $period == $p 
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100' }}">
                    {{ $p }} дней
                </button>
            @endforeach
        </div>

        <div class="hidden sm:block h-6 w-px bg-gray-200"></div>

        <div class="flex-wrap gap-2 w-full sm:w-auto justify-between sm:justify-start mt-2 sm:mt-0 flex">
            <input type="date" name="from" value="{{ $from }}"
                   class="w-[48%] sm:w-auto border border-gray-300 rounded-lg px-2 py-1 text-xs sm:text-sm">

            <input type="date" name="to" value="{{ $to }}"
                   class="w-[48%] sm:w-auto border border-gray-300 rounded-lg px-2 py-1 text-xs sm:text-sm">

            <button type="submit"
                    class="w-full sm:w-auto px-3 py-1.5 rounded-lg bg-gray-900 text-white text-xs sm:text-sm">
                Обновить
            </button>
        </div>
    </form>
</div>


{{-- KPI --}}
<section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">

    {{-- Суммарная активность --}}
    @php
        $totalNow = $summary->views + $summary->favorites + $summary->carts;
        $totalPrev = $prev->views + $prev->favorites + $prev->carts;
        $d = delta($totalNow, $totalPrev);
    @endphp

    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
        <p class="text-xs text-gray-500">Суммарная активность</p>
        <h2 class="text-2xl font-bold mt-1 text-gray-900">{{ $totalNow }}</h2>
        <p class="text-xs mt-1 flex items-center gap-1 {{ str_starts_with($d,'+')?'text-green-600':'text-red-600' }}">
            <span class="w-1.5 h-1.5 rounded-full {{ str_starts_with($d,'+')?'bg-green-500':'bg-red-500' }}"></span>
            {{ $d }} к прошлому периоду
        </p>
    </div>

    {{-- Просмотры --}}
    @php $d = delta($summary->views,$prev->views); @endphp
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
        <p class="text-xs text-gray-500">Просмотры</p>
        <h2 class="text-2xl font-bold mt-1">{{ $summary->views }}</h2>
        <p class="text-xs mt-1 flex items-center gap-1 {{ str_starts_with($d,'+')?'text-green-600':'text-red-600' }}">
            <span class="w-1.5 h-1.5 rounded-full {{ str_starts_with($d,'+')?'bg-green-500':'bg-red-500' }}"></span>
            {{ $d }} к прошлому периоду
        </p>
    </div>

    {{-- Избранное --}}
    @php $d = delta($summary->favorites,$prev->favorites); @endphp
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
        <p class="text-xs text-gray-500">Избранное</p>
        <h2 class="text-2xl font-bold mt-1">{{ $summary->favorites }}</h2>
        <p class="text-xs mt-1 flex items-center gap-1 {{ str_starts_with($d,'+')?'text-green-600':'text-red-600' }}">
            <span class="w-1.5 h-1.5 rounded-full {{ str_starts_with($d,'+')?'bg-green-500':'bg-red-500' }}"></span>
            {{ $d }} к прошлому периоду
        </p>
    </div>

    {{-- Корзины --}}
    @php $d = delta($summary->carts,$prev->carts); @endphp
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
        <p class="text-xs text-gray-500">Корзины</p>
        <h2 class="text-2xl font-bold mt-1">{{ $summary->carts }}</h2>
        <p class="text-xs mt-1 flex items-center gap-1 {{ str_starts_with($d,'+')?'text-green-600':'text-red-600' }}">
            <span class="w-1.5 h-1.5 rounded-full {{ str_starts_with($d,'+')?'bg-green-500':'bg-red-500' }}"></span>
            {{ $d }} к прошлому периоду
        </p>
    </div>
</section>

@if($sellerPlanProfile['analytics_enabled'] && $advanced)
    <section class="grid grid-cols-1 gap-4 mb-10 md:grid-cols-3">
        <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-4 shadow-sm">
            <p class="text-xs font-semibold text-cyan-700">Конверсия в избранное</p>
            <h2 class="mt-1 text-2xl font-bold text-cyan-950">{{ $advanced['favorite_rate'] }}%</h2>
            <p class="mt-1 text-xs text-cyan-700">Доля добавлений в избранное от просмотров</p>
        </div>

        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
            <p class="text-xs font-semibold text-indigo-700">Конверсия в корзину</p>
            <h2 class="mt-1 text-2xl font-bold text-indigo-950">{{ $advanced['cart_rate'] }}%</h2>
            <p class="mt-1 text-xs text-indigo-700">Показывает товары с реальным покупательским интересом</p>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <p class="text-xs font-semibold text-amber-700">Без просмотров</p>
            <h2 class="mt-1 text-2xl font-bold text-amber-950">{{ $advanced['inactive_products'] }}</h2>
            <p class="mt-1 text-xs text-amber-700">{{ $advanced['products_with_cart_interest'] }} товаров попадали в корзину</p>
        </div>
    </section>
@else
    <section class="mb-10 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-slate-900">Расширенная аналитика доступна с Pro</h2>
                <p class="mt-1 text-sm text-slate-500">Конверсия в корзину, товары без просмотров и дополнительные сигналы помогают быстрее понимать, что улучшать.</p>
            </div>
            <a href="{{ route('seller.plans.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                Посмотреть тарифы
            </a>
        </div>
    </section>
@endif


{{-- Пончики и ТОП --}}
<section class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">

    {{-- Пончик --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
        <h2 class="text-base font-semibold mb-4">Распределение активности</h2>

        <div class="relative w-full overflow-x-hidden" style="min-height:240px;">
            <canvas id="donutChart" class="w-full max-w-full"></canvas>
        </div>
    </div>

    {{-- ТОП --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm lg:col-span-2">
        <h2 class="text-base font-semibold mb-4">ТОП по просмотрам</h2>

        @if($topProducts->count())
            <div class="relative w-full overflow-x-hidden" style="min-height:{{ max($topProducts->count(),3)*42 }}px;">
                <canvas id="barChart"></canvas>
            </div>
            <p class="text-xs text-gray-400 mt-2">Клик по полосе → карточка товара</p>
        @else
            <p class="text-sm text-gray-500">Нет данных</p>
        @endif
    </div>
</section>


{{-- Таймлайн --}}
<section>
    <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm mb-10">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-base font-semibold">Активность по дням</h2>
            <p class="text-xs text-gray-400">Клик по точке → детали дня</p>
        </div>

        <div class="relative w-full overflow-x-hidden" style="min-height:260px;">
            <canvas id="timelineChart"></canvas>
        </div>
    </div>
</section>

</div>

{{-- Мобильная нижняя навигация --}}
@include('layouts.mobile-bottom-seller-nav')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
