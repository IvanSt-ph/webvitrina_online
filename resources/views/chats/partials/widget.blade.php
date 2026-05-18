@php
    $other = $conversation->otherParticipant(auth()->user());
    $contextProduct = $contextProduct ?? $conversation->product;
@endphp

<div
    x-data="{
        open: true,
        expanded: false,
        hasOlderMessages: @js((bool) ($hasOlderMessages ?? false)),
        oldestMessageId: @js($oldestMessageId ?? null),
        latestMessageId: @js($latestMessageId ?? 0),
        latestReadOutgoingMessageId: @js($latestReadOutgoingMessageId ?? 0),
        loadingOlderMessages: false,
        emojiOpen: false,
        selectedImageName: '',
        unseenMessages: 0,
        poller: null,
        sendingMessage: false,
        sendError: '',
        quickReply(text) {
            this.$refs.composer.value = text
            this.resize()
            this.$refs.composer.focus()
        },
        resize() {
            const el = this.$refs.composer
            if (!el) return
            el.style.height = 'auto'
            el.style.height = Math.min(el.scrollHeight, 144) + 'px'
            el.style.overflowY = el.scrollHeight > 144 ? 'auto' : 'hidden'
        },
        scrollToBottom() {
            this.$refs.thread?.scrollTo({ top: this.$refs.thread.scrollHeight })
        },
        isNearBottom() {
            const thread = this.$refs.thread
            if (!thread) return true
            return thread.scrollHeight - thread.scrollTop - thread.clientHeight < 120
        },
        scrollToLatest() {
            this.scrollToBottom()
            this.unseenMessages = 0
        },
        insertEmoji(emoji) {
            const composer = this.$refs.composer
            const start = composer.selectionStart ?? composer.value.length
            const end = composer.selectionEnd ?? composer.value.length
            composer.value = composer.value.slice(0, start) + emoji + composer.value.slice(end)
            composer.focus()
            composer.setSelectionRange(start + emoji.length, start + emoji.length)
            this.resize()
            this.emojiOpen = false
        },
        updateSelectedImage(event) {
            this.selectedImageName = event.target.files?.[0]?.name ?? ''
        },
        async loadOlderMessages() {
            if (!this.hasOlderMessages || !this.oldestMessageId || this.loadingOlderMessages) return

            this.loadingOlderMessages = true

            const thread = this.$refs.thread
            const previousHeight = thread.scrollHeight
            const previousTop = thread.scrollTop

            const response = await fetch(@js(route('chats.messages.older', $conversation)) + '?before=' + this.oldestMessageId, {
                headers: { 'Accept': 'application/json' }
            })

            if (!response.ok) {
                this.loadingOlderMessages = false
                return
            }

            const payload = await response.json()
            this.$refs.messages.insertAdjacentHTML('afterbegin', payload.html)
            this.oldestMessageId = payload.oldest_message_id
            this.hasOlderMessages = payload.has_older_messages
            this.$nextTick(() => {
                thread.scrollTop = thread.scrollHeight - previousHeight + previousTop
            })
            this.loadingOlderMessages = false
        },
        async loadNewerMessages() {
            const response = await fetch(@js(route('chats.messages.newer', $conversation)) + '?after=' + (this.latestMessageId ?? 0), {
                headers: { 'Accept': 'application/json' }
            })

            if (!response.ok) return

            const payload = await response.json()
            if (!payload.count) return

            const shouldStickToBottom = this.isNearBottom()
            this.$refs.messages.insertAdjacentHTML('beforeend', payload.html)
            this.latestMessageId = payload.latest_message_id
            this.markReadOutgoingMessages(payload.latest_read_outgoing_message_id ?? 0)

            if (shouldStickToBottom) {
                this.$nextTick(() => this.scrollToBottom())
                this.unseenMessages = 0
            } else {
                this.unseenMessages += payload.count
            }
        },
        markReadOutgoingMessages(latestReadId) {
            if (!latestReadId || latestReadId <= this.latestReadOutgoingMessageId) return

            this.latestReadOutgoingMessageId = latestReadId

            this.$refs.messages
                .querySelectorAll('[data-message-id]')
                .forEach((message) => {
                    if (Number(message.dataset.messageId) > latestReadId) return

                    const status = message.querySelector('[data-read-status]')
                    if (!status) return

                    status.classList.add('is-read')
                    status.title = 'Прочитано'
                    status.setAttribute('aria-label', 'Прочитано')
                })
        },
        async sendMessage(event) {
            if (this.sendingMessage) return

            this.sendingMessage = true
            this.sendError = ''

            const form = event.target
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })

            if (!response.ok) {
                this.sendError = 'Не удалось отправить сообщение. Попробуйте ещё раз.'
                this.sendingMessage = false
                return
            }

            const payload = await response.json()
            this.$refs.messages.insertAdjacentHTML('beforeend', payload.html)
            this.latestMessageId = payload.latest_message_id
            this.markReadOutgoingMessages(payload.latest_read_outgoing_message_id ?? 0)
            this.$refs.composer.value = ''
            this.$refs.imageInput.value = ''
            this.selectedImageName = ''
            this.resize()
            this.$nextTick(() => this.scrollToBottom())
            this.sendingMessage = false
        },
        close() {
            this.open = false
            window.history.replaceState({}, '', @js($closeUrl))
        }
    }"
    x-init="$nextTick(() => { resize(); scrollToBottom(); poller = setInterval(() => loadNewerMessages(), 5000) })"
    @beforeunload.window="if (poller) clearInterval(poller)"
    x-show="open"
    x-cloak
    class="fixed z-[60]"
    :class="expanded ? 'inset-3 sm:inset-6' : 'bottom-4 right-4 left-4 sm:left-auto sm:w-[420px]'"
>
    <section
        class="flex h-full max-h-[calc(100vh-1.5rem)] flex-col overflow-hidden border border-slate-200 bg-white shadow-2xl shadow-slate-900/15 transition-all duration-300"
        :class="expanded ? 'rounded-[2rem]' : 'rounded-[2rem] sm:h-[620px]'"
    >
        <header class="relative overflow-hidden border-b border-slate-100 bg-gradient-to-r from-indigo-600 via-indigo-600 to-violet-600 px-4 py-4 text-white">
            <div class="absolute -right-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>
            <div class="relative flex items-center gap-3">
                <img src="{{ $other->avatar_url }}" alt="{{ $other->name }}" class="h-12 w-12 rounded-2xl object-cover ring-2 ring-white/25">
                <div class="min-w-0 flex-1">
                    <div class="truncate font-semibold">{{ $other->name }}</div>
                    <div class="truncate text-sm text-indigo-100">
                        {{ $other->isSeller() ? ($other->shop?->name ?? 'Продавец') : 'Покупатель' }}
                    </div>
                </div>
                <a href="{{ route('chats.show', $conversation) }}"
                   class="flex h-9 w-9 items-center justify-center rounded-2xl bg-white/10 transition hover:bg-white/20"
                   title="Открыть отдельной страницей">
                    <i class="ri-external-link-line"></i>
                </a>
                <button type="button"
                        @click="expanded = !expanded; $nextTick(() => scrollToBottom())"
                        class="flex h-9 w-9 items-center justify-center rounded-2xl bg-white/10 transition hover:bg-white/20"
                        :title="expanded ? 'Свернуть' : 'Развернуть'">
                    <i :class="expanded ? 'ri-collapse-diagonal-line' : 'ri-expand-diagonal-line'"></i>
                </button>
                <button type="button"
                        @click="close()"
                        class="flex h-9 w-9 items-center justify-center rounded-2xl bg-white/10 transition hover:bg-white/20"
                        title="Закрыть">
                    <i class="ri-close-line text-lg"></i>
                </button>
            </div>
        </header>

        @if($contextProduct)
            <div class="border-b border-slate-100 bg-white px-4 py-3">
                <div class="flex items-center gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-2">
                    <img src="{{ $contextProduct->image_thumb_url }}"
                         alt="{{ $contextProduct->title }}"
                         class="h-12 w-12 rounded-xl object-cover">
                    <div class="min-w-0 flex-1">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-indigo-500">Диалог по товару</div>
                        <div class="truncate text-sm font-semibold text-slate-900">{{ $contextProduct->title }}</div>
                    </div>
                    <a href="{{ route('product.show', $contextProduct->slug) }}"
                       class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm"
                       title="Открыть товар">
                        <i class="ri-arrow-right-up-line"></i>
                    </a>
                </div>
            </div>
        @endif

        <div class="border-b border-slate-100 bg-slate-50/70 px-4 py-3">
            <div class="flex flex-wrap gap-2">
                @if($contextProduct)
                    <button type="button" @click="quickReply('Здравствуйте! Подскажите, этот товар сейчас в наличии?')"
                            class="rounded-full border border-indigo-100 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-indigo-200 hover:text-indigo-600">
                        Есть в наличии?
                    </button>
                    <button type="button" @click="quickReply('Здравствуйте! Подскажите, пожалуйста, по доставке этого товара.')"
                            class="rounded-full border border-indigo-100 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-indigo-200 hover:text-indigo-600">
                        Доставка по товару
                    </button>
                    <button type="button" @click="quickReply('Здравствуйте! Можно уточнить детали по этому товару?')"
                            class="rounded-full border border-indigo-100 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-indigo-200 hover:text-indigo-600">
                        Уточнить детали
                    </button>
                @else
                    <button type="button" @click="quickReply('Здравствуйте!')"
                            class="rounded-full border border-indigo-100 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-indigo-200 hover:text-indigo-600">
                        Поздороваться
                    </button>
                    <button type="button" @click="quickReply('Здравствуйте! Подскажите, пожалуйста, по доставке.')"
                            class="rounded-full border border-indigo-100 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-indigo-200 hover:text-indigo-600">
                        Уточнить доставку
                    </button>
                    <button type="button" @click="quickReply('Здравствуйте! Подскажите, пожалуйста, как с вами удобнее связаться?')"
                            class="rounded-full border border-indigo-100 bg-white px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-indigo-200 hover:text-indigo-600">
                        Как связаться?
                    </button>
                @endif
            </div>
        </div>

        <div x-ref="thread" class="flex-1 space-y-4 overflow-y-auto bg-gradient-to-b from-slate-50 to-white px-4 py-4">
            <div x-show="hasOlderMessages" class="flex justify-center">
                <button type="button"
                        @click="loadOlderMessages()"
                        :disabled="loadingOlderMessages"
                        class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-200 disabled:cursor-wait disabled:opacity-70">
                    <span x-show="!loadingOlderMessages">Показать предыдущие сообщения</span>
                    <span x-show="loadingOlderMessages">Загружаем…</span>
                </button>
            </div>
            <div x-ref="messages" class="space-y-4">
            @forelse($messages as $message)
                @include('chats.partials.messages', ['conversation' => $conversation, 'messages' => collect([$message])])
            @empty
                <div class="flex h-full min-h-[220px] flex-col items-center justify-center text-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-3xl bg-indigo-50 text-indigo-600">
                        <i class="ri-chat-smile-3-line text-2xl"></i>
                    </div>
                    <div class="mt-4 font-semibold text-slate-900">Начните разговор</div>
                    <div class="mt-1 max-w-xs text-sm text-slate-500">
                        {{ $contextProduct ? 'Можно уточнить наличие, доставку или детали именно по этому товару.' : 'Можно задать общий вопрос продавцу, уточнить доставку или способ связи.' }}
                    </div>
                </div>
            @endforelse
            </div>
        </div>

        <div x-show="unseenMessages > 0" x-cloak class="border-t border-slate-100 bg-white px-3 py-2">
            <button type="button"
                    @click="scrollToLatest()"
                    class="mx-auto flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">
                <i class="ri-arrow-down-line"></i>
                <span x-text="unseenMessages === 1 ? '1 новое сообщение' : unseenMessages + ' новых сообщений'"></span>
            </button>
        </div>

        <form method="POST"
              action="{{ route('chats.messages.store', $conversation) }}"
              enctype="multipart/form-data"
              @submit.prevent="sendMessage($event)"
              class="border-t border-slate-100 bg-white p-3 sm:p-4">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ $returnUrl }}">
            <div x-show="selectedImageName" class="mb-3 flex items-center justify-between rounded-2xl bg-indigo-50 px-3 py-2 text-sm text-indigo-700">
                <span class="truncate" x-text="selectedImageName"></span>
                <button type="button"
                        @click="$refs.imageInput.value = ''; selectedImageName = ''"
                        class="ml-3 text-indigo-500 hover:text-indigo-700">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            <div class="flex min-w-0 items-end gap-2">
                <div class="relative">
                    <button type="button"
                            @click="emojiOpen = !emojiOpen"
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[1.35rem] border border-slate-200 bg-slate-50 text-slate-600 transition hover:border-indigo-200 hover:text-indigo-600">
                        <i class="ri-emotion-happy-line text-lg"></i>
                    </button>
                    <div x-show="emojiOpen"
                         @click.outside="emojiOpen = false"
                         x-cloak
                         class="absolute bottom-[58px] left-0 z-20 grid w-56 grid-cols-6 gap-1 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                        @foreach(['😀','😁','😂','😊','😍','👍','🙏','🔥','❤️','🎉','🤝','📦'] as $emoji)
                            <button type="button"
                                    @click="insertEmoji('{{ $emoji }}')"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-lg hover:bg-slate-100">
                                {{ $emoji }}
                            </button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <input type="file"
                           name="image"
                           accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                           x-ref="imageInput"
                           @change="updateSelectedImage($event)"
                           class="hidden">
                    <button type="button"
                            @click="$refs.imageInput.click()"
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[1.35rem] border border-slate-200 bg-slate-50 text-slate-600 transition hover:border-indigo-200 hover:text-indigo-600">
                        <i class="ri-image-add-line text-lg"></i>
                    </button>
                </div>
                <textarea name="body"
                          rows="1"
                          maxlength="2000"
                          x-ref="composer"
                          @input="resize()"
                          @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $el.form.requestSubmit(); }"
                          placeholder="Сообщение…"
                          class="min-h-[48px] min-w-0 flex-1 resize-none overflow-y-hidden rounded-[1.35rem] border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 focus:border-indigo-300 focus:bg-white focus:ring-4 focus:ring-indigo-100">{{ old('body') }}</textarea>
                <button :disabled="sendingMessage"
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-[1.35rem] bg-indigo-600 text-white shadow-lg shadow-indigo-600/20 transition hover:-translate-y-0.5 hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-70">
                    <i class="ri-send-plane-2-line"></i>
                </button>
            </div>
            @error('body')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('image')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p x-show="sendError" x-text="sendError" class="mt-2 text-sm text-red-600"></p>
        </form>
    </section>
</div>

<style>
.wv-read-status {
    display: inline-flex;
    align-items: center;
    gap: 1px;
}
.wv-read-status span {
    display: block;
    width: 7px;
    height: 4px;
    border-left: 1.6px solid rgba(224,231,255,.95);
    border-bottom: 1.6px solid rgba(224,231,255,.95);
    transform: rotate(-45deg);
}
.wv-read-status span + span {
    margin-left: -4px;
}
.wv-read-status.is-read span {
    border-color: rgb(125 211 252);
}
</style>
