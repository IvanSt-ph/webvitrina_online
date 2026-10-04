<x-seller-layout title="Подписчики магазина">
    <div class="min-h-screen w-full bg-white px-3 py-4 pb-28 text-neutral-900 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">
        <div class="space-y-5 sm:space-y-6">
            <section>
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div class="min-w-0">
                        <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                            <i class="ri-user-follow-line"></i>
                            Аудитория магазина
                        </div>
                        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">Подписчики</h1>
                        <p class="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">
                            Пользователи, которые следят за магазином и его ассортиментом.
                        </p>
                    </div>

                    @if($shop)
                        <a href="{{ route('seller.show', $shop->slug) }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-neutral-200 bg-white px-4 py-2.5 text-sm font-semibold text-neutral-700 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 sm:w-auto">
                            <i class="ri-arrow-right-up-line"></i>
                            Открыть витрину
                        </a>
                    @endif
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                        <div class="flex items-center justify-between"><p class="text-xs font-medium text-neutral-500">Всего</p><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><i class="ri-group-line"></i></span></div>
                        <p class="mt-2 text-xl font-bold text-neutral-950 sm:text-2xl">{{ $stats['total'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                        <div class="flex items-center justify-between"><p class="text-xs font-medium text-neutral-500">За 30 дней</p><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><i class="ri-user-add-line"></i></span></div>
                        <p class="mt-2 text-xl font-bold text-neutral-950 sm:text-2xl">{{ $stats['recent'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                        <div class="flex items-center justify-between"><p class="text-xs font-medium text-neutral-500">Покупатели</p><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"><i class="ri-user-3-line"></i></span></div>
                        <p class="mt-2 text-xl font-bold text-neutral-950 sm:text-2xl">{{ $stats['buyers'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                        <div class="flex items-center justify-between"><p class="text-xs font-medium text-neutral-500">Продавцы</p><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600"><i class="ri-store-2-line"></i></span></div>
                        <p class="mt-2 text-xl font-bold text-neutral-950 sm:text-2xl">{{ $stats['sellers'] ?? 0 }}</p>
                    </div>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
                <div class="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
                    <div class="flex items-center justify-between border-b border-neutral-100 px-4 py-4 sm:px-5">
                        <div>
                            <h2 class="text-base font-semibold text-neutral-950">Список подписчиков</h2>
                            <p class="mt-1 text-xs text-neutral-500">Сначала показываются самые новые подписки.</p>
                        </div>
                    </div>

                    @if(! $shop)
                        <div class="flex min-h-[360px] flex-col items-center justify-center px-6 py-12 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-neutral-100 text-neutral-500">
                                <i class="ri-store-3-line text-2xl"></i>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-neutral-900">Магазин еще не создан</h3>
                            <p class="mt-2 max-w-md text-sm leading-6 text-neutral-500">
                                Когда магазин появится, здесь будет список подписчиков и общая статистика аудитории.
                            </p>
                            <a href="{{ route('profile.edit') }}"
                               class="mt-5 inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600">
                                <i class="ri-edit-2-line"></i>
                                Заполнить магазин
                            </a>
                        </div>
                    @elseif($followers->isEmpty())
                        <div class="flex min-h-[360px] flex-col items-center justify-center px-6 py-12 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                                <i class="ri-user-heart-line text-2xl"></i>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-neutral-900">Пока нет подписчиков</h3>
                            <p class="mt-2 max-w-md text-sm leading-6 text-neutral-500">
                                Когда пользователи подпишутся на магазин, они появятся в этом списке с датой подписки и ссылкой на профиль.
                            </p>
                        </div>
                    @else
                        <div class="divide-y divide-neutral-100">
                            @foreach($followers as $follower)
                                <article class="flex flex-col gap-4 px-4 py-4 transition hover:bg-brand-50/40 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <a href="{{ route('users.public.show', $follower) }}"
                                           class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-brand-50 text-base font-bold text-brand-600 ring-1 ring-brand-100">
                                            @if($follower->avatar)
                                                <img data-image-candidates="{{ json_encode($follower->avatar_candidates ?? []) }}" data-image-fallback="{{ asset('images/avatar-placeholder.svg') }}" src="{{ $follower->avatar_url }}"
                                                     alt="{{ $follower->name }}"
                                                     class="h-full w-full object-cover">
                                            @else
                                                {{ mb_strtoupper(mb_substr($follower->name ?? 'U', 0, 1)) }}
                                            @endif
                                        </a>

                                        <div class="min-w-0">
                                            <a href="{{ route('users.public.show', $follower) }}"
                                               class="block truncate text-sm font-semibold text-neutral-950 hover:text-brand-700">
                                                {{ $follower->name }}
                                            </a>
                                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-neutral-500">
                                                <span class="inline-flex items-center gap-1 rounded-full bg-neutral-100 px-2.5 py-1 font-medium">
                                                    <i class="ri-user-line text-neutral-400"></i>
                                                    {{ $follower->role === 'seller' ? 'Продавец' : 'Покупатель' }}
                                                </span>
                                                <span class="inline-flex items-center gap-1">
                                                    <i class="ri-time-line text-neutral-400"></i>
                                                    {{ optional($follower->pivot?->created_at)->format('d.m.Y H:i') }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 sm:justify-end">
                                        @if($follower->role === 'seller' && $follower->shop)
                                            <a href="{{ route('seller.show', $follower->shop->slug) }}"
                                               class="inline-flex items-center gap-2 rounded-xl border border-neutral-200 bg-white px-3 py-2 text-xs font-semibold text-neutral-700 transition hover:border-brand-200 hover:text-brand-700">
                                                <i class="ri-store-3-line"></i>
                                                Магазин
                                            </a>
                                        @endif
                                        <a href="{{ route('users.public.show', $follower) }}"
                                           class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-brand-600">
                                            <i class="ri-arrow-right-up-line"></i>
                                            Профиль
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        @if(method_exists($followers, 'links'))
                            <div class="border-t border-neutral-100 px-4 py-4 sm:px-5">
                                {{ $followers->links() }}
                            </div>
                        @endif
                    @endif
                </div>

                <aside class="space-y-4">
                    <div class="rounded-2xl border border-neutral-200 bg-white p-5">
                        <h2 class="text-sm font-semibold text-neutral-950">Что делать с аудиторией</h2>
                        <div class="mt-4 space-y-3">
                            <div class="rounded-xl bg-neutral-50 p-4">
                                <p class="text-sm font-semibold text-neutral-800">Публикуйте новинки регулярно</p>
                                <p class="mt-1 text-xs leading-5 text-neutral-500">Подписчики чаще возвращаются, когда витрина выглядит живой.</p>
                            </div>
                            <div class="rounded-xl bg-neutral-50 p-4">
                                <p class="text-sm font-semibold text-neutral-800">Держите быстрый ответ</p>
                                <p class="mt-1 text-xs leading-5 text-neutral-500">Чаты и понятные условия покупки сильнее влияют на доверие, чем голые скидки.</p>
                            </div>
                            <div class="rounded-xl bg-neutral-50 p-4">
                                <p class="text-sm font-semibold text-neutral-800">Следите за профилем</p>
                                <p class="mt-1 text-xs leading-5 text-neutral-500">Логотип, баннер и описание магазина помогают подписке не выглядеть случайной.</p>
                            </div>
                        </div>
                    </div>

                    @if($shop)
                        <div class="rounded-2xl border border-brand-100 bg-brand-50 p-5">
                            <h2 class="text-sm font-semibold text-brand-950">Публичная страница</h2>
                            <p class="mt-2 text-xs leading-5 text-brand-800/80">
                                Это место, где покупатель решает подписаться. Проверьте, что описание, контакты и товары выглядят убедительно.
                            </p>
                            <a href="{{ route('profile.edit') }}"
                               class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-brand-700 hover:text-brand-900">
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
