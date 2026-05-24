@php
    $chatLayout = auth()->user()->isSeller() ? 'seller-layout' : 'buyer-layout';
@endphp

<x-dynamic-component :component="$chatLayout" title="Чаты" :chat-mode="true">
    <div class="mx-auto flex h-dvh w-full max-w-8xl min-w-0 flex-col overflow-hidden px-3 py-4 pb-24 sm:px-5 sm:py-6 lg:px-6 lg:pb-6">
        <div class="sticky top-0 z-20 mb-4 shrink-0 border-b border-slate-100 bg-neutral-50/95 pb-4 backdrop-blur sm:mb-5 sm:border-0 sm:bg-transparent sm:pb-0">
            @if(auth()->user()->isSeller())
                <a href="{{ route('seller.cabinet') }}"
                   class="mb-3 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-indigo-600 lg:hidden">
                    <i class="ri-arrow-left-line"></i>
                    Назад в кабинет
                </a>
            @endif
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-indigo-600">Сообщения</p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Чаты</h1>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500 sm:text-base">Обсуждайте детали покупки напрямую внутри WebVitrina.</p>
                </div>

                @if($conversations->isNotEmpty())
                    <div class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm">
                        <i class="ri-chat-3-line text-indigo-500"></i>
                        {{ $conversations->total() ?? $conversations->count() }}
                    </div>
                @endif
            </div>
        </div>

        <div class="grid min-h-0 min-w-0 flex-1 gap-4 overflow-hidden lg:grid-cols-[380px_minmax(0,1fr)]">
            <section class="min-h-0 min-w-0 overflow-y-auto rounded-2xl border border-slate-200/80 bg-slate-50/70 p-2 shadow-sm sm:rounded-[2rem] sm:p-3 lg:h-full">
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
                <section class="hidden min-h-0 min-w-0 overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm lg:flex lg:h-full lg:flex-col">
                    <header class="flex shrink-0 items-center gap-3 border-b border-slate-100 bg-white/95 px-4 py-4">
                        <img src="{{ $other->avatar_url }}" alt="{{ $other->name }}" class="h-12 w-12 rounded-2xl object-cover">
                        <div class="min-w-0 flex-1">
                            <div class="truncate font-semibold text-slate-900">{{ $other->name }}</div>
                            <div class="text-sm text-slate-500">
                                {{ $other->isSeller() ? ($other->shop?->name ?? 'Продавец') : 'Покупатель' }}
                            </div>
                        </div>
                        <a href="{{ route('chats.show', $conversation) }}"
                           class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-600 transition hover:border-indigo-200 hover:text-indigo-600">
                            <i class="ri-expand-right-line"></i>
                            Открыть
                        </a>
                        <form method="POST"
                              action="{{ route('chats.destroy', $conversation) }}"
                              onsubmit="return confirm('Скрыть этот диалог из вашего списка?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600"
                                    title="Скрыть диалог">
                                <i class="ri-delete-bin-6-line"></i>
                            </button>
                        </form>
                    </header>

                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-gradient-to-b from-slate-50 to-white px-6 py-5">
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
                          class="shrink-0 border-t border-slate-100 bg-white p-4">
                        @csrf
                        <div class="flex min-w-0 items-end gap-3">
                            <label class="flex min-h-[52px] w-[52px] cursor-pointer items-center justify-center rounded-[1.5rem] border border-slate-200 bg-slate-50 text-slate-600 transition hover:border-indigo-200 hover:text-indigo-600">
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
                                      class="min-h-[52px] min-w-0 flex-1 resize-none rounded-[1.5rem] border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 focus:border-indigo-300 focus:bg-white focus:ring-4 focus:ring-indigo-100"></textarea>
                            <button class="flex min-h-[52px] shrink-0 items-center justify-center gap-2 rounded-[1.5rem] bg-indigo-600 px-5 font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:-translate-y-0.5 hover:bg-indigo-700">
                                <i class="ri-send-plane-2-line"></i>
                                Отправить
                            </button>
                        </div>
                    </form>
                </section>
            @else
                <section class="relative hidden min-h-[520px] min-w-0 overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm lg:block">
                    <div class="absolute -right-16 -top-16 h-48 w-48 rounded-full bg-indigo-100 blur-3xl"></div>
                    <div class="relative flex min-h-[420px] flex-col items-center justify-center text-center">
                        <div class="flex h-20 w-20 items-center justify-center rounded-[1.75rem] bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-xl shadow-indigo-500/20">
                            <i class="ri-chat-3-line text-4xl"></i>
                        </div>
                        <h2 class="mt-6 text-2xl font-semibold text-slate-900">Диалогов пока нет</h2>
                        <p class="mt-2 max-w-md text-slate-500">Откройте страницу магазина или товара и нажмите «Написать», чтобы начать разговор.</p>
                    </div>
                </section>
            @endif
        </div>
    </div>

    @if(auth()->user()->isSeller())
        @include('layouts.mobile-bottom-seller-nav')
    @endif
</x-dynamic-component>
