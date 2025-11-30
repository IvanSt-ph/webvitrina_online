<x-seller-layout :title="'Статистика за ' . $date">

<div class="max-w-7xl mx-auto px-4 py-8 space-y-10">

    {{-- ===== Заголовок ===== --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-2">
                <span class="inline-block w-3 h-3 rounded-full bg-indigo-600"></span>
                Статистика за {{ $date }}
            </h1>
            <p class="text-gray-500 text-sm mt-1">
                Данные по активности всех опубликованных товаров
            </p>
        </div>

        <a href="{{ route('seller.analytics.index') }}"
           class="px-4 py-2.5 text-sm bg-gray-900 text-white rounded-xl hover:bg-black transition">
            ← Вернуться к аналитике
        </a>
    </div>

    {{-- ===== KPI ===== --}}
    @php
        $totalViews = $stats->sum('views');
        $totalFavs  = $stats->sum('favorites');
        $totalCarts = $stats->sum('carts');
    @endphp

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">

        {{-- Просмотры --}}
        <div class="bg-white p-6 rounded-2xl shadow-sm border flex items-start justify-between">
            <div>
                <p class="text-gray-500 text-xs">Просмотры</p>
                <h2 class="text-3xl font-bold mt-1">{{ $totalViews }}</h2>
            </div>

            {{-- ICON: Eye Line --}}
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="w-7 h-7 text-indigo-500 opacity-70"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1.5 12s4-7.5 10.5-7.5S22.5 12 22.5 12s-4 7.5-10.5 7.5S1.5 12 1.5 12z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </div>

        {{-- Избранное --}}
        <div class="bg-white p-6 rounded-2xl shadow-sm border flex items-start justify-between">
            <div>
                <p class="text-gray-500 text-xs">Избранное</p>
                <h2 class="text-3xl font-bold mt-1">{{ $totalFavs }}</h2>
            </div>

            {{-- ICON: Heart Line --}}
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="w-7 h-7 text-pink-500 opacity-70"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 4.5c-1.5-1.5-4-1.5-5.5 0L12 6l-1.5-1.5C9 3 6.5 3 5 4.5s-1.5 4 0 5.5l1.5 1.5L12 18l5.5-6.5 1.5-1.5c1.5-1.5 1.5-4 0-5.5z"/>
            </svg>
        </div>

        {{-- Корзины --}}
        <div class="bg-white p-6 rounded-2xl shadow-sm border flex items-start justify-between">
            <div>
                <p class="text-gray-500 text-xs">Корзины</p>
                <h2 class="text-3xl font-bold mt-1">{{ $totalCarts }}</h2>
            </div>

            {{-- ICON: Shopping Cart Line --}}
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="w-7 h-7 text-yellow-500 opacity-70"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="20" r="1.5"/>
                <circle cx="17" cy="20" r="1.5"/>
                <path d="M3 3h2l2 12h10l2-8H6"/>
            </svg>
        </div>

    </div>

    {{-- ===== Таблица ===== --}}
    <div class="bg-white p-6 rounded-2xl shadow-sm border">

        <h2 class="text-lg font-semibold mb-4">Товары с активностью</h2>

        @if($stats->count())
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-3 pr-4">Товар</th>
                            <th class="py-3 pr-4">Просмотры</th>
                            <th class="py-3 pr-4">Избранное</th>
                            <th class="py-3 pr-4">Корзины</th>
                            <th class="py-3 pr-4">CTR</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($stats as $s)
                            @php
                                $ctr = $s->views > 0
                                    ? round(($s->favorites + $s->carts) * 100 / $s->views, 1)
                                    : 0;
                            @endphp

                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="py-3 pr-4 font-medium">
                                    <a href="{{ route('product.show', $s->id) }}"
                                       class="text-indigo-600 hover:underline">
                                        {{ $s->title }}
                                    </a>
                                </td>
                                <td class="py-3 pr-4 font-semibold">{{ $s->views }}</td>
                                <td class="py-3 pr-4">{{ $s->favorites }}</td>
                                <td class="py-3 pr-4">{{ $s->carts }}</td>
                                <td class="py-3 pr-4">
                                    <span class="px-2 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-semibold">
                                        {{ $ctr }}%
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @else
            <div class="py-10 text-center text-gray-500">
                <p class="text-sm">За этот день активность отсутствует.</p>
                <p class="text-xs mt-1 text-gray-400">Попробуй выбрать другой день или расширить период.</p>
            </div>
        @endif

    </div>

</div>

</x-seller-layout>
