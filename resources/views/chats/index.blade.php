@php
    $chatLayout = auth()->user()->isSeller() ? 'seller-layout' : 'buyer-layout';
@endphp

<x-dynamic-component :component="$chatLayout" title="Чаты" :chat-mode="true">
    <div class="flex h-full w-full min-w-0 flex-col overflow-hidden bg-white px-4 py-5 pb-24 sm:px-6 lg:px-8 lg:pb-6">
        <div class="sticky top-0 z-20 mb-4 shrink-0 border-b border-neutral-200 bg-white/95 pb-4 backdrop-blur">
            @if(auth()->user()->isSeller())
                <a href="{{ route('seller.cabinet') }}"
                   class="mb-3 inline-flex items-center gap-1.5 text-sm font-medium text-neutral-500 transition hover:text-brand-600 lg:hidden">
                    <i class="ri-arrow-left-line"></i>
                    Назад в кабинет
                </a>
            @endif
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <div class="mb-1 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600"><i class="ri-chat-3-line text-base"></i>Сообщения</div>
                    <div class="flex min-w-0 flex-wrap items-baseline gap-x-3 gap-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight text-neutral-900 sm:text-[28px]">Мои чаты</h1>
                        <p class="min-w-0 truncate text-sm text-neutral-500 sm:max-w-xl">Товары, заказы и поддержка</p>
                    </div>
                </div>

                @if($conversations->isNotEmpty())
                    <div class="inline-flex w-fit items-center gap-2 rounded-full border border-brand-100 bg-brand-50 px-3 py-1.5 text-sm font-semibold text-brand-700">
                        <i class="ri-chat-check-line"></i>
                        {{ $conversations->total() ?? $conversations->count() }} диалогов
                    </div>
                @endif
            </div>

            <details class="mt-4 rounded-xl border border-neutral-200 bg-neutral-50/60" @if($search !== '' || $activeFilter) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-2.5 text-sm font-semibold text-neutral-700">
                    <span class="flex items-center gap-2">
                        <i class="ri-equalizer-3-line text-brand-500"></i>
                        Поиск и фильтры
                        @if($search !== '' || $activeFilter)
                            <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-bold text-brand-700">активно</span>
                        @endif
                    </span>
                    <i class="ri-arrow-down-s-line text-lg text-neutral-400"></i>
                </summary>

                <div class="border-t border-neutral-200 px-3 pb-3 pt-3">
                    <form method="GET" action="{{ route('chats.index') }}" class="grid gap-2 lg:grid-cols-[minmax(0,1fr)_auto]">
                        <label class="relative min-w-0">
                            <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400"></i>
                            <input
                                type="search"
                                name="q"
                                value="{{ $search }}"
                                placeholder="Поиск: покупатель, магазин, товар, заказ или текст сообщения"
                                class="h-11 w-full rounded-xl border border-neutral-200 bg-white pl-10 pr-3 text-sm outline-none transition focus:border-brand-300 focus:ring-4 focus:ring-brand-100"
                            >
                            @if($activeFilter)
                                <input type="hidden" name="filter" value="{{ $activeFilter }}">
                            @endif
                        </label>
                        <x-action-button type="submit">
                            <i class="ri-search-line"></i>
                            Найти
                        </x-action-button>
                    </form>

                    <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                        <a href="{{ route('chats.index', array_filter(['q' => $search])) }}"
                           class="wv-ui-pill inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-sm"
                               @if($activeFilter === null) aria-current="true" @endif>
                            Все
                        </a>
                        @foreach($chatFilters as $key => $meta)
                            <a href="{{ route('chats.index', array_filter(['q' => $search, 'filter' => $key])) }}"
                               class="wv-ui-pill inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-sm"
                               @if($activeFilter === $key) aria-current="true" @endif>
                                <i class="{{ $meta['icon'] }}"></i>
                                {{ $meta['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </details>
        </div>

        <div class="grid min-h-0 min-w-0 flex-1 gap-5 overflow-hidden lg:grid-cols-[360px_minmax(0,1fr)]">
            <section class="min-h-0 min-w-0 overflow-y-auto rounded-2xl border border-neutral-200 bg-neutral-50 p-2 lg:h-full">
                @include('chats.partials.list', ['currentConversation' => $selectedConversation, 'inlineDesktop' => true])
                @if(method_exists($conversations, 'links'))
                    <div class="mt-4 pb-3">{{ $conversations->links() }}</div>
                @endif
            </section>

            @if($selectedConversation)
                @php
                    $conversation = $selectedConversation;
                    $other = $conversation->otherParticipant(auth()->user());
                @endphp
                <section
                    x-data
                    x-init="$nextTick(() => { $refs.thread?.scrollTo({ top: $refs.thread.scrollHeight, behavior: 'auto' }) })"
                    class="hidden min-h-0 min-w-0 overflow-hidden rounded-2xl border border-neutral-200 bg-white lg:flex lg:h-full lg:flex-col">
                    <header class="flex shrink-0 items-center gap-3 border-b border-neutral-100 bg-white/95 px-4 py-3">
                        <img data-image-candidates="{{ json_encode($other->avatar_candidates ?? []) }}" data-image-fallback="{{ asset('images/avatar-placeholder.svg') }}" src="{{ $other->avatar_url }}" alt="{{ $other->name }}" class="h-11 w-11 rounded-xl object-cover">
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-semibold text-neutral-900">{{ $other->name }}</div>
                            <div class="text-sm text-neutral-500">
                                {{ $other->isSeller() ? ($other->shop?->name ?? 'Продавец') : 'Покупатель' }}
                            </div>
                        </div>
                        <a href="{{ route('chats.show', $conversation) }}"
                           class="inline-flex h-10 items-center gap-2 rounded-xl border border-neutral-200 px-3 text-sm font-semibold text-neutral-600 transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-600">
                            <i class="ri-expand-right-line"></i>
                            Открыть
                        </a>
                        <form method="POST"
                              action="{{ route('chats.destroy', $conversation) }}"
                              onsubmit="return confirm('Скрыть этот диалог из вашего списка?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-200 text-neutral-500 transition hover:border-danger-200 hover:bg-danger-50 hover:text-danger-600"
                                    title="Скрыть диалог">
                                <i class="ri-delete-bin-6-line"></i>
                            </button>
                        </form>
                    </header>

                    @include('chats.partials.product-context', ['conversation' => $conversation])

                    <div x-ref="thread" class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-gradient-to-b from-neutral-50 to-white px-5 py-4">
                        <div class="space-y-4">
                            @forelse($selectedMessages as $message)
                                @include('chats.partials.messages', ['conversation' => $conversation, 'messages' => collect([$message])])
                            @empty
                                <div class="flex min-h-[320px] flex-col items-center justify-center text-center">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-indigo-50 text-indigo-600">
                                        <i class="ri-sparkling-2-line text-3xl"></i>
                                    </div>
                                    <h2 class="mt-4 text-xl font-semibold text-slate-900">Начните разговор</h2>
                                    <p class="mt-2 max-w-sm text-sm text-slate-500">Спросите о товаре, доставке или условиях покупки.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <form method="POST"
                          action="{{ route('chats.messages.store', $conversation) }}"
                          enctype="multipart/form-data"
                          class="shrink-0 border-t border-neutral-100 bg-white p-3">
                        @csrf
                        <div class="flex min-w-0 items-end gap-3">
                            <label class="flex min-h-11 w-11 cursor-pointer items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-600 transition hover:border-brand-200 hover:text-brand-600">
                                <i class="ri-image-add-line text-lg"></i>
                                <input type="file"
                                       name="image"
                                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                       class="hidden">
                            </label>
                            <textarea name="body"
                                      rows="1"
                                      maxlength="2000"
                                      placeholder="Сообщение..."
                                      class="min-h-11 min-w-0 flex-1 resize-none rounded-xl border-neutral-200 bg-neutral-50 px-4 py-2.5 text-sm leading-6 focus:border-brand-300 focus:bg-white focus:ring-4 focus:ring-brand-100"></textarea>
                            <button class="flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 font-semibold text-white shadow-lg shadow-brand-500/20 transition hover:-translate-y-0.5 hover:bg-brand-600">
                                <i class="ri-send-plane-2-line"></i>
                                Отправить
                            </button>
                        </div>
                    </form>
                </section>
            @else
                <section class="relative hidden min-h-[420px] min-w-0 overflow-hidden rounded-2xl border border-neutral-200 bg-white p-8 lg:block">
                    <div class="absolute -right-16 -top-16 h-48 w-48 rounded-full bg-indigo-100 blur-3xl"></div>
                    <div class="relative flex min-h-[340px] flex-col items-center justify-center text-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-violet-600 text-white shadow-xl shadow-brand-500/20">
                            <i class="ri-chat-3-line text-3xl"></i>
                        </div>
                        <h2 class="mt-5 text-xl font-semibold text-neutral-900">Диалогов пока нет</h2>
                        <p class="mt-2 max-w-md text-sm text-neutral-500">Откройте страницу магазина или товара и нажмите «Написать», чтобы начать разговор.</p>
                    </div>
                </section>
            @endif
        </div>
    </div>

    @if(auth()->user()->isSeller())
        @include('layouts.mobile-bottom-seller-nav')
    @endif
</x-dynamic-component>
