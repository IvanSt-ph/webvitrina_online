<x-app-layout :title="$user->name">
    @php
        $shop = $user->shop;
        $roleLabel = $user->isSeller() ? 'Продавец' : ($user->isBuyer() ? 'Покупатель' : 'Администратор');
        $isOwnProfile = auth()->check() && auth()->id() === $user->id;
        $trustItems = [
            ['label' => 'Email', 'value' => $user->hasVerifiedEmail() ? 'Подтвержден' : 'Не подтвержден', 'active' => $user->hasVerifiedEmail(), 'icon' => 'ri-mail-check-line'],
            ['label' => 'Телефон', 'value' => $user->hasVerifiedPhone() ? 'Подтвержден' : 'Не подтвержден', 'active' => $user->hasVerifiedPhone(), 'icon' => 'ri-smartphone-line'],
            ['label' => 'Профиль', 'value' => $user->created_at?->translatedFormat('M Y'), 'active' => true, 'icon' => 'ri-calendar-line'],
        ];
        $trustScore = 35
            + ($user->hasVerifiedEmail() ? 20 : 0)
            + ($user->hasVerifiedPhone() ? 20 : 0)
            + min(15, ($publicStats['written_reviews'] + $publicStats['completed_orders']) * 3)
            + ($shop ? 10 : 0);
        $trustScore = min(100, $trustScore);
        $trustLabel = $trustScore >= 80 ? 'Высокое доверие' : ($trustScore >= 60 ? 'Надежный профиль' : 'Новый профиль');
        $profileSignals = [
            ['label' => 'Роль', 'value' => $roleLabel, 'icon' => $user->isSeller() ? 'ri-store-2-line' : 'ri-user-smile-line', 'tone' => 'bg-indigo-50 text-indigo-700'],
            ['label' => 'Публичная активность', 'value' => $publicStats['written_reviews'] . ' отзывов', 'icon' => 'ri-chat-smile-3-line', 'tone' => 'bg-amber-50 text-amber-700'],
            ['label' => 'Приватность', 'value' => 'Контакты скрыты', 'icon' => 'ri-lock-line', 'tone' => 'bg-slate-100 text-slate-600'],
            ['label' => 'Проверки', 'value' => ($user->hasVerifiedEmail() || $user->hasVerifiedPhone()) ? 'Есть подтверждения' : 'Пока без подтверждений', 'icon' => 'ri-shield-check-line', 'tone' => 'bg-emerald-50 text-emerald-700'],
        ];
    @endphp

    <div class="min-h-screen bg-slate-50 px-3 py-5 pb-24 text-slate-900 sm:px-5 lg:px-8 lg:py-8">
        <div class="mx-auto grid w-full max-w-7xl gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
            <main class="min-w-0 space-y-5">
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="p-4 sm:p-6">
                        <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between">
                            <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center">
                                <div class="relative mx-auto h-24 w-24 shrink-0 sm:mx-0">
                                    <img src="{{ $user->avatar_url }}"
                                         alt="{{ $user->name }}"
                                         class="h-24 w-24 rounded-2xl border border-slate-200 bg-slate-100 object-cover shadow-sm">
                                    <span class="absolute -bottom-2 -right-2 flex h-9 w-9 items-center justify-center rounded-xl border-4 border-white {{ $user->isSeller() ? 'bg-indigo-600' : 'bg-emerald-600' }} text-white">
                                        <i class="{{ $user->isSeller() ? 'ri-store-2-line' : 'ri-user-smile-line' }} text-lg"></i>
                                    </span>
                                </div>

                                <div class="min-w-0 text-center sm:text-left">
                                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600">
                                        {{ $roleLabel }}
                                    </div>
                                    <h1 class="mt-3 break-words text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">
                                        {{ $user->name }}
                                    </h1>
                                    <div class="mt-2 flex flex-wrap justify-center gap-2 text-xs font-semibold sm:justify-start">
                                        @if($user->hasVerifiedEmail())
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">Email подтвержден</span>
                                        @endif
                                        @if($user->hasVerifiedPhone())
                                            <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-emerald-700">Телефон подтвержден</span>
                                        @endif
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700">
                                            На сайте с {{ $user->created_at?->translatedFormat('M Y') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap justify-center gap-2 md:justify-end">
                                @if($shop?->slug)
                                    <a href="{{ route('seller.show', $shop->slug) }}"
                                       class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-bold text-white transition hover:bg-indigo-700">
                                        <i class="ri-store-2-line"></i>
                                        Магазин
                                    </a>
                                    @auth
                                        @if(! $isOwnProfile)
                                            <form method="POST" action="{{ route('chats.start', $shop) }}">
                                                @csrf
                                                <button class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                                    <i class="ri-chat-1-line"></i>
                                                    Написать
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <a href="{{ route('login') }}"
                                           class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                            <i class="ri-chat-1-line"></i>
                                            Написать
                                        </a>
                                    @endauth
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid border-t border-slate-200 bg-slate-50/80 sm:grid-cols-4">
                        <div class="border-b border-slate-200 p-4 sm:border-b-0 sm:border-r">
                            <div class="text-xs font-bold uppercase text-slate-400">Публичных отзывов</div>
                            <div class="mt-1 text-2xl font-bold text-slate-950">{{ $publicStats['written_reviews'] }}</div>
                        </div>
                        <div class="border-b border-slate-200 p-4 sm:border-b-0 sm:border-r">
                            <div class="text-xs font-bold uppercase text-slate-400">Завершенных покупок</div>
                            <div class="mt-1 text-2xl font-bold text-slate-950">{{ $publicStats['completed_orders'] }}</div>
                        </div>
                        <div class="border-b border-slate-200 p-4 sm:border-b-0 sm:border-r">
                            <div class="text-xs font-bold uppercase text-slate-400">Товаров продавца</div>
                            <div class="mt-1 text-2xl font-bold text-slate-950">{{ $publicStats['seller_products'] }}</div>
                        </div>
                        <div class="p-4">
                            <div class="text-xs font-bold uppercase text-slate-400">Подписчиков</div>
                            <div class="mt-1 text-2xl font-bold text-slate-950">{{ $shop?->followers_count ?? 0 }}</div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach($profileSignals as $signal)
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $signal['tone'] }}">
                                    <i class="{{ $signal['icon'] }} text-xl"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xs font-bold uppercase text-slate-400">{{ $signal['label'] }}</div>
                                    <div class="mt-0.5 truncate text-sm font-bold text-slate-900">{{ $signal['value'] }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <div class="text-xs font-bold uppercase text-slate-400">Паспорт участника</div>
                            <h2 class="mt-1 text-xl font-bold text-slate-950">{{ $trustLabel }}</h2>
                            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                                Профиль собран из публичных сигналов: подтверждения аккаунта, открытая активность, роль на площадке и наличие магазина.
                            </p>
                        </div>
                        <div class="w-full rounded-2xl bg-slate-50 p-4 lg:w-72">
                            <div class="flex items-center justify-between text-sm font-bold text-slate-700">
                                <span>Индекс доверия</span>
                                <span>{{ $trustScore }}%</span>
                            </div>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                                <div class="h-full rounded-full bg-indigo-600" style="width: {{ $trustScore }}%"></div>
                            </div>
                            <div class="mt-2 text-xs text-slate-500">Обновляется по мере публичной активности.</div>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex items-center gap-2 text-sm font-bold text-slate-900">
                                <i class="ri-calendar-check-line text-indigo-600"></i>
                                Регистрация
                            </div>
                            <div class="mt-1 text-sm text-slate-500">{{ $user->created_at?->translatedFormat('d F Y') }}</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex items-center gap-2 text-sm font-bold text-slate-900">
                                <i class="ri-shopping-bag-3-line text-emerald-600"></i>
                                Сделки
                            </div>
                            <div class="mt-1 text-sm text-slate-500">{{ $publicStats['completed_orders'] }} завершенных покупок</div>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex items-center gap-2 text-sm font-bold text-slate-900">
                                <i class="ri-star-smile-line text-amber-500"></i>
                                Отзывы
                            </div>
                            <div class="mt-1 text-sm text-slate-500">{{ $publicStats['written_reviews'] }} публичных отзывов</div>
                        </div>
                    </div>
                </section>

                @if($shop)
                    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex min-w-0 gap-4">
                                <img src="{{ $shop->banner_url }}"
                                     alt="{{ $shop->name ?? 'Магазин' }}"
                                     class="h-20 w-20 shrink-0 rounded-2xl border border-slate-200 object-cover">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold uppercase text-slate-400">Магазин пользователя</div>
                                    <h2 class="mt-1 truncate text-xl font-bold text-slate-950">{{ $shop->name ?? 'Магазин продавца' }}</h2>
                                    <p class="mt-1 line-clamp-2 text-sm leading-6 text-slate-500">
                                        {{ $shop->description ?: 'Описание магазина пока не заполнено.' }}
                                    </p>
                                    <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold">
                                        @if($shop->city)
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600">
                                                <i class="ri-map-pin-line"></i>
                                                {{ $shop->city }}
                                            </span>
                                        @endif
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-amber-700">
                                            <i class="ri-star-fill"></i>
                                            {{ number_format($user->reviews_avg_rating ?? 0, 1) }}
                                        </span>
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700">
                                            {{ $shop->followers_count ?? 0 }} подписчиков
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <a href="{{ route('seller.show', $shop->slug) }}"
                               class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-xl border border-indigo-200 px-4 text-sm font-bold text-indigo-700 transition hover:bg-indigo-50">
                                Открыть магазин
                                <i class="ri-arrow-right-up-line"></i>
                            </a>
                        </div>
                    </section>
                @endif

                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-200 p-4 sm:p-5">
                        <h2 class="text-lg font-bold text-slate-950">Публичная активность</h2>
                        <p class="mt-1 text-sm text-slate-500">Отзывы, которые пользователь оставил и которые прошли модерацию.</p>
                    </div>

                    <div class="divide-y divide-slate-100">
                        @forelse($publicReviews as $review)
                            <article class="p-4 sm:p-5">
                                <div class="flex gap-3">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-500">
                                        <i class="ri-star-fill text-xl"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <div class="font-bold text-slate-950">{{ number_format($review->rating, 1) }}</div>
                                            @if($review->product)
                                                <a href="{{ route('product.show', $review->product->slug) }}"
                                                   class="min-w-0 truncate text-sm font-semibold text-indigo-700 hover:underline">
                                                    {{ $review->product->title }}
                                                </a>
                                            @endif
                                        </div>
                                        <p class="mt-1 line-clamp-3 text-sm leading-6 text-slate-600">{{ $review->body }}</p>
                                        <div class="mt-2 text-xs text-slate-400">{{ $review->created_at?->diffForHumans() }}</div>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="grid gap-3 p-4 sm:grid-cols-3 sm:p-5">
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                                        <i class="ri-chat-smile-3-line text-xl"></i>
                                    </div>
                                    <div class="mt-3 font-bold text-slate-900">Отзывы</div>
                                    <div class="mt-1 text-sm leading-6 text-slate-500">Одобренные отзывы пользователя появятся в этой ленте.</div>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                        <i class="ri-shield-user-line text-xl"></i>
                                    </div>
                                    <div class="mt-3 font-bold text-slate-900">Модерация</div>
                                    <div class="mt-1 text-sm leading-6 text-slate-500">В публичный профиль попадает только проверенная активность.</div>
                                </div>
                                <div class="rounded-xl bg-slate-50 p-4">
                                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                        <i class="ri-lock-line text-xl"></i>
                                    </div>
                                    <div class="mt-3 font-bold text-slate-900">Контакты</div>
                                    <div class="mt-1 text-sm leading-6 text-slate-500">Личные данные остаются скрытыми даже при пустой активности.</div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </section>
            </main>

            <aside class="space-y-5 lg:sticky lg:top-24">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="font-bold text-slate-950">Доверие</h2>
                    <div class="mt-4 space-y-3">
                        @foreach($trustItems as $item)
                            <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $item['active'] ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' }}">
                                    <i class="{{ $item['icon'] }} text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-slate-900">{{ $item['label'] }}</div>
                                    <div class="truncate text-xs text-slate-500">{{ $item['value'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="font-bold text-slate-950">Что видно публично</h2>
                    <div class="mt-3 space-y-2 text-sm leading-6 text-slate-600">
                        <div class="flex gap-2">
                            <i class="ri-eye-line mt-1 text-indigo-500"></i>
                            <span>Имя, аватар, роль, дата регистрации и публичная активность.</span>
                        </div>
                        <div class="flex gap-2">
                            <i class="ri-lock-line mt-1 text-slate-400"></i>
                            <span>Email, телефон, адреса и заказы не раскрываются.</span>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h2 class="font-bold text-slate-950">Сводка</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Тип профиля</dt>
                            <dd class="font-bold text-slate-900">{{ $roleLabel }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Магазин</dt>
                            <dd class="font-bold text-slate-900">{{ $shop ? 'Есть' : 'Нет' }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Публичная активность</dt>
                            <dd class="font-bold text-slate-900">{{ $publicStats['written_reviews'] }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-slate-500">Доверие</dt>
                            <dd class="font-bold text-slate-900">{{ $trustScore }}%</dd>
                        </div>
                    </dl>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
