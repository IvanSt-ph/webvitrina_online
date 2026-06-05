<x-seller-layout title="Уровень магазина" :hideHeader="true">
    <div class="min-h-screen bg-white px-3 py-4 pb-[5.5rem] text-slate-900 sm:px-5 sm:py-6 lg:px-6">
        <div class="w-full max-w-none space-y-5">
            <header class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[1fr_340px] lg:items-center">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                        <i class="ri-vip-crown-line"></i>
                        Уровень магазина: {{ $profile['label'] }}
                    </div>
                    <h1 class="mt-3 text-2xl font-bold text-slate-950 sm:text-3xl">Выберите уровень магазина</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                       Уровень магазина влияет на лимит товаров, продвижение и доступ к расширенной аналитике. Повышение уровня оформляется через заявку и ручное одобрение администратором.
                    </p>
                </div>

                <div class="rounded-xl border {{ $profile['class'] }} p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase text-slate-500">Текущий уровень магазина</div>
                            <div class="mt-1 text-2xl font-bold">{{ $profile['label'] }}</div>
                        </div>
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/80 text-xl">
                            <i class="ri-store-2-line"></i>
                        </div>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/80">
                        <div class="h-full rounded-full bg-indigo-600" style="width: {{ $profile['percent'] }}%"></div>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs font-semibold opacity-80">
                        <span>{{ $profile['used'] }} товаров</span>
                        <span>лимит {{ $profile['limit_label'] }}</span>
                    </div>
                </div>
            </header>

            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    <i class="ri-check-line mr-1"></i>{{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            @if($pendingRequest)
                <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="font-bold">Заявка на {{ $plans[$pendingRequest->requested_plan]['label'] ?? $pendingRequest->requested_plan }} уже на проверке</div>
                            <p class="mt-1 text-sm text-amber-800">Администратор увидит её в панели и сможет одобрить вручную.</p>
                        </div>
                        <span class="w-fit rounded-full bg-white px-3 py-1 text-xs font-bold">Ожидает решения</span>
                    </div>
                </section>
            @endif

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @foreach($plans as $key => $plan)
                    @php
                        $isCurrent = $profile['key'] === $key;
                        $isDifferent = $profile['key'] !== $key;
                        $isUpgrade = app(\App\Services\SellerPlanService::class)->isUpgrade($profile['key'], $key);
                    @endphp
                    <article class="flex min-h-[360px] flex-col rounded-xl border {{ $isCurrent ? 'border-indigo-300 bg-indigo-50/60' : 'border-slate-200 bg-white' }} p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-slate-950">{{ $plan['label'] }}</h2>
                                <p class="mt-1 text-sm font-semibold text-indigo-700">{{ $plan['price'] }}</p>
                            </div>
                            @if($isCurrent)
                                <span class="rounded-full bg-indigo-600 px-2.5 py-1 text-xs font-bold text-white">Ваш</span>
                            @endif
                        </div>

                        <p class="mt-3 min-h-[3.5rem] text-sm leading-5 text-slate-500">{{ $plan['description'] }}</p>

                        <div class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-sm font-bold text-slate-800">
                            {{ $plan['limit'] === null ? 'Без лимита товаров' : 'До ' . $plan['limit'] . ' товаров' }}
                        </div>

                        <ul class="mt-4 flex-1 space-y-2 text-sm text-slate-600">
                            @foreach($plan['features'] as $feature)
                                <li class="flex gap-2">
                                    <i class="ri-check-line mt-0.5 text-indigo-600"></i>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        @if($isCurrent)
                            <button type="button" disabled class="mt-5 h-10 rounded-lg bg-slate-200 text-sm font-bold text-slate-500">Текущий уровень</button>
                        @elseif($isDifferent)
                            <form method="POST" action="{{ route('seller.plans.request') }}" class="mt-5 space-y-2">
                                @csrf
                                <input type="hidden" name="requested_plan" value="{{ $key }}">
                                <textarea name="message" rows="2" placeholder="{{ $isUpgrade ? 'Комментарий для администратора' : 'Причина изменения уровня' }}" class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"></textarea>
                                <button type="submit" @disabled($pendingRequest) class="h-10 w-full rounded-lg bg-indigo-600 text-sm font-bold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                                    Подать заявку
                                </button>
                            </form>
                        @endif
                    </article>
                @endforeach
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-bold text-slate-950">История заявок</h2>
                    <span class="text-xs font-semibold text-slate-400">{{ $requests->count() }} последних</span>
                </div>

                <div class="mt-4 divide-y divide-slate-100">
                    @forelse($requests as $request)
                        <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">
                                    {{ $plans[$request->current_plan]['label'] ?? $request->current_plan }} -> {{ $plans[$request->requested_plan]['label'] ?? $request->requested_plan }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">{{ $request->created_at->format('d.m.Y H:i') }}</div>
                            </div>
                            <span class="w-fit rounded-full px-3 py-1 text-xs font-bold {{ $request->status === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($request->status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">
                                {{ ['pending' => 'На проверке', 'approved' => 'Одобрено', 'rejected' => 'Отклонено'][$request->status] ?? $request->status }}
                            </span>
                        </div>
                    @empty
                        <p class="py-6 text-sm text-slate-500">Заявок пока нет.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
