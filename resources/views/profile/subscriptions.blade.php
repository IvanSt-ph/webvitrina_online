<x-buyer-layout title="Мои подписки">
    <div class="min-h-screen bg-white pb-24 text-neutral-800 md:pb-0">
        <header class="border-b border-neutral-200 bg-white">
            <div class="flex w-full flex-col gap-5 px-4 py-6 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <div class="min-w-0">
                    <div class="mb-2 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                        <i class="ri-user-follow-line text-base" aria-hidden="true"></i>
                        Любимые магазины
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight text-neutral-900 sm:text-[28px]">Мои подписки</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-neutral-500">
                        Все продавцы, за которыми вы следите, собраны в одном месте.
                    </p>
                </div>

                <x-action-button as="a" :href="route('home')" class="shrink-0">
                    <i class="ri-store-3-line" aria-hidden="true"></i>
                    Найти магазины
                </x-action-button>
            </div>
        </header>

        <main class="w-full space-y-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8 lg:py-12">
            <section class="grid overflow-hidden rounded-2xl border border-brand-100 bg-brand-50/50 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="flex flex-col justify-center p-5 sm:p-6 lg:p-7">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm ring-1 ring-brand-100">
                        <i class="ri-heart-3-line text-xl" aria-hidden="true"></i>
                    </div>
                    <h2 class="mt-4 text-xl font-semibold tracking-tight text-neutral-900 sm:text-2xl">Магазины, которые вам нравятся</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-600">
                        Возвращайтесь к знакомым продавцам, смотрите новые товары и управляйте подписками без лишних поисков.
                    </p>

                    <form method="GET" action="{{ route('subscriptions.index') }}" class="mt-5 flex max-w-3xl flex-col gap-3 sm:flex-row">
                        <label class="relative min-w-0 flex-1">
                            <span class="sr-only">Поиск по подпискам</span>
                            <i class="ri-search-line pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-lg text-neutral-400" aria-hidden="true"></i>
                            <input type="search" name="q" value="{{ $search }}" placeholder="Название магазина или город"
                                   class="h-12 w-full rounded-xl border-neutral-200 bg-white pl-11 pr-4 text-sm text-neutral-800 shadow-sm transition placeholder:text-neutral-400 hover:border-brand-200 focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                        </label>
                        <x-action-button type="submit" class="h-12 px-6">
                            <i class="ri-search-line" aria-hidden="true"></i>
                            Найти
                        </x-action-button>
                        @if($search !== '')
                            <x-secondary-action as="a" :href="route('subscriptions.index')" class="h-12">
                                <i class="ri-close-line" aria-hidden="true"></i>
                                Сбросить
                            </x-secondary-action>
                        @endif
                    </form>
                </div>

                <div class="grid grid-cols-2 border-t border-brand-100 bg-white/60 lg:border-l lg:border-t-0">
                    <div class="flex flex-col justify-center border-r border-brand-100 p-5 sm:p-6">
                        <span class="text-3xl font-semibold tracking-tight text-neutral-900">{{ $subscriptionsCount }}</span>
                        <span class="mt-2 text-sm font-medium text-neutral-500">Всего подписок</span>
                    </div>
                    <div class="flex flex-col justify-center p-5 sm:p-6">
                        <span class="text-3xl font-semibold tracking-tight text-brand-600">{{ $search === '' ? $shops->count() : $shops->total() }}</span>
                        <span class="mt-2 text-sm font-medium text-neutral-500">{{ $search === '' ? 'На странице' : 'Найдено' }}</span>
                    </div>
                </div>
            </section>

            @if($search !== '')
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-200 pb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900">Результаты поиска</h2>
                        <p class="mt-1 text-sm text-neutral-500">По запросу «{{ $search }}» найдено: {{ $shops->total() }}</p>
                    </div>
                    <x-secondary-action as="a" :href="route('subscriptions.index')" size="sm">
                        Показать все
                    </x-secondary-action>
                </div>
            @else
                <div class="border-b border-neutral-200 pb-4">
                    <h2 class="text-lg font-semibold text-neutral-900">Ваши магазины</h2>
                    <p class="mt-1 text-sm text-neutral-500">Новые подписки отображаются здесь автоматически.</p>
                </div>
            @endif

            @if($shops->isEmpty())
                <section class="rounded-3xl border border-dashed border-neutral-300 bg-neutral-50/60 px-6 py-16 text-center sm:py-20">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-600 ring-8 ring-white">
                        <i class="{{ $search === '' ? 'ri-user-heart-line' : 'ri-search-eye-line' }} text-3xl" aria-hidden="true"></i>
                    </div>
                    <h2 class="mt-6 text-xl font-semibold text-neutral-900">{{ $search === '' ? 'Подписок пока нет' : 'Ничего не найдено' }}</h2>
                    <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-neutral-500">
                        @if($search === '')
                            Подпишитесь на понравившийся магазин, и он появится здесь. Так вы сможете быстро вернуться к любимым продавцам.
                        @else
                            Попробуйте изменить название или город. Ваши подписки остались на месте — под этот запрос просто нет совпадений.
                        @endif
                    </p>
                    <div class="mt-7 flex justify-center">
                        @if($search !== '')
                            <x-secondary-action as="a" :href="route('subscriptions.index')">
                                <i class="ri-arrow-left-line" aria-hidden="true"></i>
                                Ко всем подпискам
                            </x-secondary-action>
                        @else
                            <x-action-button as="a" :href="route('home')">
                                <i class="ri-store-3-line" aria-hidden="true"></i>
                                Перейти к витрине
                            </x-action-button>
                        @endif
                    </div>
                </section>
            @else
                <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4" aria-label="Список подписок">
                    @foreach($shops as $shop)
                        <article class="group flex min-w-0 flex-col overflow-hidden rounded-2xl border border-neutral-200 bg-white transition duration-300 hover:-translate-y-1 hover:border-brand-200 hover:shadow-xl hover:shadow-brand-100/50">
                            <a href="{{ route('seller.show', $shop->slug) }}" class="relative block overflow-hidden bg-neutral-100">
                                <div class="aspect-[16/7]">
                                    <img data-image-fallback="{{ asset('images/image-placeholder.svg') }}" src="{{ $shop->banner_url }}" alt="{{ $shop->name }}"
                                         class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                </div>
                                <span class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-full border border-white/60 bg-white/90 px-3 py-1.5 text-xs font-semibold text-brand-700 shadow-sm backdrop-blur">
                                    <i class="ri-check-line" aria-hidden="true"></i>
                                    Вы подписаны
                                </span>
                            </a>

                            <div class="flex flex-1 flex-col p-5">
                                <div class="min-w-0">
                                    <a href="{{ route('seller.show', $shop->slug) }}" class="block truncate text-lg font-semibold text-neutral-900 transition hover:text-brand-700">
                                        {{ $shop->name }}
                                    </a>
                                    <p class="mt-1 flex items-center gap-1.5 truncate text-sm text-neutral-500">
                                        <i class="ri-map-pin-line shrink-0" aria-hidden="true"></i>
                                        {{ $shop->city ?: 'Город не указан' }}
                                    </p>
                                </div>

                                <div class="my-5 grid grid-cols-2 divide-x divide-neutral-200 rounded-xl bg-neutral-50 py-3 text-center">
                                    <div class="px-3">
                                        <div class="text-base font-semibold text-neutral-900">{{ $shop->products_count }}</div>
                                        <div class="mt-0.5 text-xs text-neutral-500">товаров</div>
                                    </div>
                                    <div class="px-3">
                                        <div class="text-base font-semibold text-neutral-900">{{ $shop->followers_count }}</div>
                                        <div class="mt-0.5 text-xs text-neutral-500">подписчиков</div>
                                    </div>
                                </div>

                                <div class="mt-auto flex flex-col gap-2 sm:flex-row">
                                    <x-action-button as="a" :href="route('seller.show', $shop->slug)" full size="sm">
                                        Открыть магазин
                                        <i class="ri-arrow-right-up-line" aria-hidden="true"></i>
                                    </x-action-button>
                                    <form method="POST" action="{{ route('shops.follow', $shop) }}" class="shrink-0">
                                        @csrf
                                        <button type="submit" title="Отписаться от магазина" aria-label="Отписаться от {{ $shop->name }}"
                                                class="flex h-10 w-full items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 text-sm font-semibold text-neutral-600 transition hover:border-danger-200 hover:bg-danger-50 hover:text-danger-600 sm:w-10 sm:px-0">
                                            <i class="ri-user-unfollow-line text-lg" aria-hidden="true"></i>
                                            <span class="sm:sr-only">Отписаться</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </section>

                @if($shops->hasPages())
                    <div class="border-t border-neutral-200 pt-6">
                        {{ $shops->links() }}
                    </div>
                @endif
            @endif
        </main>
    </div>

    @include('layouts.mobile-bottom-nav')
</x-buyer-layout>
