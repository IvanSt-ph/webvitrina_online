<x-buyer-layout title="Мои подписки">
    <div class="px-3 py-4 pb-24 sm:px-6 sm:py-8 md:pb-8">
        <div class="space-y-6">
            <section class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                            <i class="ri-user-follow-line"></i>
                            Любимые магазины
                        </div>
                        <h1 class="mt-3 text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">Мои подписки</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-500">
                            Магазины, на которые вы подписались. Здесь удобно возвращаться к продавцам, за обновлениями которых хочется следить.
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <a href="{{ route('home') }}"
                           class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            <i class="ri-store-3-line"></i>
                            Найти магазины
                        </a>
                    </div>
                </div>

                <form method="GET" action="{{ route('subscriptions.index') }}" class="mt-5 flex flex-col gap-2 sm:flex-row">
                    <label class="relative flex-1">
                        <span class="sr-only">Поиск по подпискам</span>
                        <i class="ri-search-line pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input
                            type="search"
                            name="q"
                            value="{{ $search }}"
                            placeholder="Найти магазин по названию или городу"
                            class="h-11 w-full rounded-xl border border-gray-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                        >
                    </label>
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                        <i class="ri-search-line"></i>
                        Найти
                    </button>
                    @if($search !== '')
                        <a href="{{ route('subscriptions.index') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">
                            <i class="ri-close-line"></i>
                            Сбросить
                        </a>
                    @endif
                </form>

                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-semibold text-gray-500">Всего подписок</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900">{{ $subscriptionsCount }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-semibold text-gray-500">{{ $search === '' ? 'На странице' : 'Найдено' }}</p>
                        <p class="mt-2 text-2xl font-bold text-indigo-700">{{ $search === '' ? $shops->count() : $shops->total() }}</p>
                    </div>
                    <div class="col-span-2 rounded-xl border border-gray-100 bg-gray-50 p-4 sm:col-span-1">
                        <p class="text-xs font-semibold text-gray-500">Последнее обновление</p>
                        <p class="mt-2 truncate text-sm font-semibold text-gray-900">{{ now()->format('d.m.Y H:i') }}</p>
                    </div>
                </div>
            </section>

            @if($shops->isEmpty())
                <section class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-14 text-center shadow-sm">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                        <i class="ri-user-heart-line text-2xl"></i>
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-gray-900">{{ $search === '' ? 'Подписок пока нет' : 'Ничего не найдено' }}</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-gray-500">
                        @if($search === '')
                            На странице магазина нажмите “Подписаться”, и он появится здесь. Так не придется искать продавца заново.
                        @else
                            Попробуйте другое название или город. Подписки никуда не исчезли, просто под этот запрос нет совпадений.
                        @endif
                    </p>
                    <div class="mt-5 flex justify-center gap-3">
                        @if($search !== '')
                            <a href="{{ route('subscriptions.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                Сбросить поиск
                                <i class="ri-close-line"></i>
                            </a>
                        @else
                            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                Перейти к витрине
                                <i class="ri-arrow-right-line"></i>
                            </a>
                        @endif
                    </div>
                </section>
            @else
                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($shops as $shop)
                        <article class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <a href="{{ route('seller.show', $shop->slug) }}" class="block">
                                <div class="aspect-[16/7] overflow-hidden bg-gray-50">
                                    <img src="{{ $shop->banner_url }}" alt="{{ $shop->name }}" class="h-full w-full object-cover transition duration-300 hover:scale-105">
                                </div>
                            </a>

                            <div class="space-y-4 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <a href="{{ route('seller.show', $shop->slug) }}" class="block truncate text-base font-semibold text-gray-900 hover:text-indigo-700">
                                            {{ $shop->name }}
                                        </a>
                                        <p class="mt-1 truncate text-xs text-gray-500">
                                            {{ $shop->city ?: 'Город не указан' }}
                                        </p>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                        Подписка
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs text-gray-500">
                                    <div class="rounded-xl bg-gray-50 p-3">
                                        <div class="font-semibold text-gray-900">{{ $shop->products_count }}</div>
                                        <div>товаров</div>
                                    </div>
                                    <div class="rounded-xl bg-gray-50 p-3">
                                        <div class="font-semibold text-gray-900">{{ $shop->followers_count }}</div>
                                        <div>подписчиков</div>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <a href="{{ route('seller.show', $shop->slug) }}"
                                       class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-3 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                                        <i class="ri-arrow-right-up-line"></i>
                                        Открыть
                                    </a>
                                    <form method="POST" action="{{ route('shops.follow', $shop) }}" class="sm:w-auto">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600">
                                            <i class="ri-user-unfollow-line"></i>
                                            Отписаться
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </section>

                <div>
                    {{ $shops->links() }}
                </div>
            @endif
        </div>
    </div>

    @include('layouts.mobile-bottom-nav')
</x-buyer-layout>
