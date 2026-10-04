<x-seller-layout title="Уровень магазина" :hideHeader="true">
    <div class="min-h-screen w-full bg-white px-3 py-4 pb-28 text-neutral-900 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">
        <div class="w-full space-y-5 sm:space-y-6">
            <header class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-end">
                <div class="min-w-0">
                    <div class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                        <i class="ri-vip-crown-line"></i>
                        Уровни магазина
                    </div>
                    <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">Выберите уровень магазина</h1>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-neutral-500">
                       Уровень магазина влияет на лимит товаров, продвижение и доступ к расширенной аналитике. Повышение уровня оформляется через заявку и ручное одобрение администратором.
                    </p>
                </div>

                <div class="rounded-2xl border {{ $profile['class'] }} p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-[11px] font-semibold uppercase tracking-wide opacity-70">Текущий уровень</div>
                            <div class="mt-1 text-xl font-bold sm:text-2xl">{{ $profile['label'] }}</div>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/80 text-xl ring-1 ring-black/5">
                            <i class="ri-store-2-line"></i>
                        </div>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/80">
                        <div class="h-full rounded-full bg-brand-500" style="width: {{ $profile['percent'] }}%"></div>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs font-semibold opacity-80">
                        <span>{{ $profile['used'] }} товаров</span>
                        <span>лимит {{ $profile['limit_label'] }}</span>
                    </div>
                </div>
            </header>

            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    <i class="ri-check-line mr-1"></i>{{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            @if($pendingRequest)
                <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-900 sm:p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/80 text-amber-700"><i class="ri-time-line text-xl"></i></span>
                            <div>
                                <div class="font-semibold">Заявка на {{ $plans[$pendingRequest->requested_plan]['label'] ?? $pendingRequest->requested_plan }} уже на проверке</div>
                                <p class="mt-1 text-sm leading-6 text-amber-800">Администратор увидит её в панели и сможет одобрить вручную.</p>
                            </div>
                        </div>
                        <span class="w-fit shrink-0 rounded-full bg-white px-3 py-1 text-xs font-semibold">Ожидает решения</span>
                    </div>
                </section>
            @endif

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
                @foreach($plans as $key => $plan)
                    @php
                        $isCurrent = $profile['key'] === $key;
                        $isDifferent = $profile['key'] !== $key;
                        $isUpgrade = app(\App\Services\SellerPlanService::class)->isUpgrade($profile['key'], $key);
                    @endphp
                    <article class="flex flex-col rounded-2xl border p-4 sm:p-5 md:min-h-[390px] {{ $isCurrent ? 'border-brand-300 bg-brand-50/60 ring-1 ring-brand-100' : 'border-neutral-200 bg-white' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-neutral-950">{{ $plan['label'] }}</h2>
                                <p class="mt-1 text-sm font-semibold text-brand-700">{{ $plan['price'] }}</p>
                            </div>
                            @if($isCurrent)
                                <span class="rounded-full bg-brand-500 px-2.5 py-1 text-xs font-semibold text-white">Ваш</span>
                            @endif
                        </div>

                        <p class="mt-3 text-sm leading-6 text-neutral-500 md:min-h-[4.5rem]">{{ $plan['description'] }}</p>

                        <div class="mt-4 rounded-xl bg-neutral-50 px-3 py-2.5 text-sm font-semibold text-neutral-800">
                            {{ $plan['limit'] === null ? 'Без лимита товаров' : 'До ' . $plan['limit'] . ' товаров' }}
                        </div>

                        <ul class="mt-4 flex-1 space-y-2.5 text-sm leading-5 text-neutral-600">
                            @foreach($plan['features'] as $feature)
                                <li class="flex gap-2">
                                    <i class="ri-check-line mt-0.5 shrink-0 text-brand-600"></i>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        @if($isCurrent)
                            <button type="button" disabled class="mt-5 h-10 rounded-xl bg-neutral-100 text-sm font-semibold text-neutral-500">Текущий уровень</button>
                        @elseif($isDifferent)
                            <form method="POST" action="{{ route('seller.plans.request') }}" class="mt-5 space-y-2">
                                @csrf
                                <input type="hidden" name="requested_plan" value="{{ $key }}">
                                <textarea name="message" rows="2" placeholder="{{ $isUpgrade ? 'Комментарий для администратора' : 'Причина изменения уровня' }}" class="w-full resize-none rounded-xl border border-neutral-200 px-3 py-2 text-sm text-neutral-800 outline-none transition placeholder:text-neutral-400 focus:border-brand-300 focus:ring-4 focus:ring-brand-100"></textarea>
                                <button type="submit" @disabled($pendingRequest) class="h-10 w-full rounded-xl bg-brand-500 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:bg-neutral-300">
                                    Подать заявку
                                </button>
                            </form>
                        @endif
                    </article>
                @endforeach
            </section>

            <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
                <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-4 py-4 sm:px-5">
                    <div>
                        <h2 class="font-semibold text-neutral-950">История заявок</h2>
                        <p class="mt-1 text-xs text-neutral-500">Изменения уровня магазина и их текущий статус.</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-neutral-100 px-2.5 py-1 text-xs font-semibold text-neutral-500">{{ $requests->count() }} последних</span>
                </div>

                <div class="divide-y divide-neutral-100 px-4 sm:px-5">
                    @forelse($requests as $request)
                        <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-neutral-100 text-neutral-500"><i class="ri-arrow-left-right-line text-lg"></i></span>
                                <div class="min-w-0">
                                    <div class="text-sm font-semibold text-neutral-900">
                                        {{ $plans[$request->current_plan]['label'] ?? $request->current_plan }} → {{ $plans[$request->requested_plan]['label'] ?? $request->requested_plan }}
                                    </div>
                                    <div class="mt-1 text-xs text-neutral-500">{{ $request->created_at->format('d.m.Y H:i') }}</div>
                                </div>
                            </div>
                            <span class="w-fit shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $request->status === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($request->status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">
                                {{ ['pending' => 'На проверке', 'approved' => 'Одобрено', 'rejected' => 'Отклонено'][$request->status] ?? $request->status }}
                            </span>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-10 text-center">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-neutral-100 text-neutral-500"><i class="ri-file-list-3-line text-xl"></i></span>
                            <p class="mt-3 text-sm font-medium text-neutral-700">Заявок пока нет</p>
                            <p class="mt-1 text-xs text-neutral-500">Здесь появится история запросов на изменение уровня.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
