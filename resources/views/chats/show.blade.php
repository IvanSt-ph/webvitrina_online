@php
    $chatLayout = auth()->user()->isSeller() ? 'seller-layout' : 'buyer-layout';
@endphp

<x-dynamic-component :component="$chatLayout" title="Чат" :chat-mode="true">
    @php
        $other = $conversation->otherParticipant(auth()->user());
    @endphp
    <div
        x-data="{
            hasOlderMessages: @js((bool) ($hasOlderMessages ?? false)),
            oldestMessageId: @js($oldestMessageId),
            latestMessageId: @js($latestMessageId ?? 0),
            latestReadOutgoingMessageId: @js($latestReadOutgoingMessageId ?? 0),
            loadingOlderMessages: false,
            emojiOpen: false,
            selectedImageName: '',
            unseenMessages: 0,
            poller: null,
            sendingMessage: false,
            sendError: '',
            resize() {
                const el = this.$refs.composer
                if (!el) return
                el.style.height = 'auto'
                el.style.height = Math.min(el.scrollHeight, 160) + 'px'
                el.style.overflowY = el.scrollHeight > 160 ? 'auto' : 'hidden'
            },
            scrollToBottom() {
                this.$refs.thread?.scrollTo({ top: this.$refs.thread.scrollHeight, behavior: 'smooth' })
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
            }
        }"
        x-init="$nextTick(() => { resize(); scrollToBottom(); poller = setInterval(() => loadNewerMessages(), 5000) })"
        @beforeunload.window="if (poller) clearInterval(poller)"
        class="mx-auto flex h-dvh w-full max-w-8xl min-w-0 flex-col overflow-hidden sm:h-auto sm:min-h-0 sm:overflow-visible sm:px-6 sm:py-8"
    >
        <div class="grid min-h-0 min-w-0 flex-1 gap-4 lg:grid-cols-[360px_minmax(0,1fr)]">
            <aside class="hidden min-h-0 min-w-0 overflow-hidden rounded-[2rem] border border-slate-200/80 bg-slate-50/70 p-3 lg:block">
                @include('chats.partials.list', ['currentConversation' => $conversation])
            </aside>

            <section class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden bg-white sm:rounded-[2rem] sm:border sm:border-slate-200 sm:shadow-sm">
                <header class="sticky top-0 z-10 flex shrink-0 items-center gap-3 border-b border-slate-100 bg-white/95 px-4 py-4 backdrop-blur">
                    <a href="{{ route('chats.index') }}" class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-600 lg:hidden">
                        <i class="ri-arrow-left-line text-lg"></i>
                    </a>
                    <img src="{{ $other->avatar_url }}" alt="{{ $other->name }}" class="h-12 w-12 rounded-2xl object-cover">
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-semibold text-slate-900">{{ $other->name }}</div>
                        <div class="text-sm text-slate-500">
                            {{ $other->isSeller() ? ($other->shop?->name ?? 'Продавец') : 'Покупатель' }}
                        </div>
                    </div>
                </header>

                <div x-ref="thread" class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-gradient-to-b from-slate-50 to-white px-4 py-5 sm:px-6 lg:max-h-[62vh] lg:min-h-[420px]">
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
                        <div class="flex min-h-full flex-col items-center justify-center text-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-indigo-50 text-indigo-600">
                                <i class="ri-sparkling-2-line text-3xl"></i>
                            </div>
                            <h2 class="mt-4 text-xl font-semibold text-slate-900">Начните разговор</h2>
                            <p class="mt-2 max-w-sm text-sm text-slate-500">Спросите о товаре, доставке или условиях покупки — всё останется внутри сайта.</p>
                        </div>
                    @endforelse
                    </div>
                </div>

                <div x-show="unseenMessages > 0" x-cloak class="border-t border-slate-100 bg-white px-4 py-3">
                    <button type="button"
                            @click="scrollToLatest()"
                            class="mx-auto flex items-center gap-2 rounded-full bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                        <i class="ri-arrow-down-line"></i>
                        <span x-text="unseenMessages === 1 ? '1 новое сообщение' : unseenMessages + ' новых сообщений'"></span>
                    </button>
                </div>

                <form method="POST"
                      action="{{ route('chats.messages.store', $conversation) }}"
                      enctype="multipart/form-data"
                      @submit.prevent="sendMessage($event)"
                      class="shrink-0 border-t border-slate-100 bg-white p-4 sm:p-5">
                    @csrf
                    <div x-show="selectedImageName" class="mb-3 flex items-center justify-between rounded-2xl bg-indigo-50 px-3 py-2 text-sm text-indigo-700">
                        <span class="truncate" x-text="selectedImageName"></span>
                        <button type="button"
                                @click="$refs.imageInput.value = ''; selectedImageName = ''"
                                class="ml-3 text-indigo-500 hover:text-indigo-700">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                    <div class="flex min-w-0 items-end gap-2 sm:gap-3">
                        <div class="relative">
                            <button type="button"
                                    @click="emojiOpen = !emojiOpen"
                                    class="flex min-h-[52px] w-[52px] items-center justify-center rounded-[1.5rem] border border-slate-200 bg-slate-50 text-slate-600 transition hover:border-indigo-200 hover:text-indigo-600">
                                <i class="ri-emotion-happy-line text-lg"></i>
                            </button>
                            <div x-show="emojiOpen"
                                 @click.outside="emojiOpen = false"
                                 x-cloak
                                 class="absolute bottom-[62px] left-0 z-20 grid w-56 grid-cols-6 gap-1 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
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
                                    class="flex min-h-[52px] w-[52px] items-center justify-center rounded-[1.5rem] border border-slate-200 bg-slate-50 text-slate-600 transition hover:border-indigo-200 hover:text-indigo-600">
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
                                  class="min-h-[52px] min-w-0 flex-1 resize-none overflow-y-hidden rounded-[1.5rem] border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 focus:border-indigo-300 focus:bg-white focus:ring-4 focus:ring-indigo-100">{{ old('body') }}</textarea>
                        <button :disabled="sendingMessage"
                                class="flex min-h-[52px] w-[52px] shrink-0 items-center justify-center gap-2 rounded-[1.5rem] bg-indigo-600 font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:-translate-y-0.5 hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-70 sm:w-auto sm:px-5">
                            <i class="ri-send-plane-2-line"></i>
                            <span class="hidden sm:inline">Отправить</span>
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
    </div>
</x-dynamic-component>

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
