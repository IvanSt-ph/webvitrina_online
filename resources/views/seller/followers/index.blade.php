<x-seller-layout title="Подписчики магазина">
    <div class="px-3 py-4 pb-24 sm:px-5 sm:py-6 lg:px-6 lg:pb-6">
        <div class="space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div class="min-w-0">
                        <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                            <i class="ri-user-follow-line"></i>
                            Аудитория магазина
                        </div>
                        <h1 class="mt-3 text-2xl font-bold text-slate-950 sm:text-3xl">Подписчики</h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                            Здесь собраны пользователи, которые хотят следить за вашим магазином. Это уже теплая аудитория: с ними стоит работать через ассортимент, быстрые ответы и аккуратные обновления витрины.
                        </p>
                    </div>

                    @if($shop)
                        <a href="{{ route('seller.show', $shop->slug) }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 sm:w-auto">
                            <i class="ri-arrow-right-up-line"></i>
                            Открыть витрину
                        </a>
                    @endif
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold text-slate-500">Всего</p>
                        <p class="mt-2 text-2xl font-bold text-slate-950">{{ $stats['total'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold text-slate-500">За 30 дней</p>
                        <p class="mt-2 text-2xl font-bold text-indigo-700">{{ $stats['recent'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold text-slate-500">Покупатели</p>
                        <p class="mt-2 text-2xl font-bold text-slate-950">{{ $stats['buyers'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-semibold text-slate-500">Продавцы</p>
                        <p class="mt-2 text-2xl font-bold text-slate-950">{{ $stats['sellers'] ?? 0 }}</p>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-4 sm:px-6">
                        <div>
                            <h2 class="text-base font-semibold text-slate-950">Список подписчиков</h2>
                            <p class="mt-1 text-xs text-slate-500">Сначала показываются самые новые подписки.</p>
                        </div>
                    </div>

                    @if(! $shop)
                        <div class="flex min-h-[360px] flex-col items-center justify-center px-6 py-12 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
                                <i class="ri-store-3-line text-2xl"></i>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-slate-900">Магазин еще не создан</h3>
                            <p class="mt-2 max-w-md text-sm leading-6 text-slate-500">
                                Когда магазин появится, здесь будет список подписчиков и общая статистика аудитории.
                            </p>
                            <a href="{{ route('profile.edit') }}"
                               class="mt-5 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                <i class="ri-edit-2-line"></i>
                                Заполнить магазин
                            </a>
                        </div>
                    @elseif($followers->isEmpty())
                        <div class="flex min-h-[360px] flex-col items-center justify-center px-6 py-12 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                                <i class="ri-user-heart-line text-2xl"></i>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-slate-900">Пока нет подписчиков</h3>
                            <p class="mt-2 max-w-md text-sm leading-6 text-slate-500">
                                Когда пользователи подпишутся на магазин, они появятся в этом списке с датой подписки и ссылкой на профиль.
                            </p>
                        </div>
                    @else
                        <div class="divide-y divide-slate-100">
                            @foreach($followers as $follower)
                                <article class="flex flex-col gap-4 px-4 py-4 transition hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <a href="{{ route('users.public.show', $follower) }}"
                                           class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 text-base font-bold text-slate-600 ring-1 ring-slate-200">
                                            @if($follower->avatar)
                                                <img src="{{ asset('storage/' . $follower->avatar) }}"
                                                     alt="{{ $follower->name }}"
                                                     class="h-full w-full object-cover">
                                            @else
                                                {{ mb_strtoupper(mb_substr($follower->name ?? 'U', 0, 1)) }}
                                            @endif
                                        </a>

                                        <div class="min-w-0">
                                            <a href="{{ route('users.public.show', $follower) }}"
                                               class="block truncate text-sm font-semibold text-slate-950 hover:text-indigo-700">
                                                {{ $follower->name }}
                                            </a>
                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 font-medium">
                                                    <i class="ri-user-line text-slate-400"></i>
                                                    {{ $follower->role === 'seller' ? 'Продавец' : 'Покупатель' }}
                                                </span>
                                                <span class="inline-flex items-center gap-1">
                                                    <i class="ri-time-line text-slate-400"></i>
                                                    {{ optional($follower->pivot?->created_at)->format('d.m.Y H:i') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 sm:justify-end">
                                        @if($follower->role === 'seller' && $follower->shop)
                                            <a href="{{ route('seller.show', $follower->shop->slug) }}"
                                               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                                <i class="ri-store-3-line"></i>
                                                Магазин
                                            </a>
                                        @endif
                                        <a href="{{ route('users.public.show', $follower) }}"
                                           class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700">
                                            <i class="ri-arrow-right-up-line"></i>
                                            Профиль
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        @if(method_exists($followers, 'links'))
                            <div class="border-t border-slate-100 px-4 py-4 sm:px-6">
                                {{ $followers->links() }}
                            </div>
                        @endif
                    @endif
                </div>

                <aside class="space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 class="text-sm font-semibold text-slate-950">Что делать с аудиторией</h2>
                        <div class="mt-4 space-y-3">
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-800">Публикуйте новинки регулярно</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Подписчики чаще возвращаются, когда витрина выглядит живой.</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-800">Держите быстрый ответ</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Чаты и понятные условия покупки сильнее влияют на доверие, чем голые скидки.</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-800">Следите за профилем</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500">Логотип, баннер и описание магазина помогают подписке не выглядеть случайной.</p>
                            </div>
                        </div>
                    </div>

                    @if($shop)
                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-5">
                            <h2 class="text-sm font-semibold text-indigo-950">Публичная страница</h2>
                            <p class="mt-2 text-xs leading-5 text-indigo-800/80">
                                Это место, где покупатель решает подписаться. Проверьте, что описание, контакты и товары выглядят убедительно.
                            </p>
                            <a href="{{ route('profile.edit') }}"
                               class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-indigo-700 hover:text-indigo-900">
                                <i class="ri-edit-2-line"></i>
                                Настроить магазин
                            </a>
                        </div>
                    @endif
                </aside>
            </section>
        </div>
    </div>

    @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
