@foreach($messages as $message)
    @php
        $mine = $message->sender_id === auth()->id();
        $imageRouteName = $imageRouteName ?? 'chats.messages.image';
        $showSenderLabels = $showSenderLabels ?? false;
        $showInternalNotes = $showInternalNotes ?? false;
        $senderRole = match ($message->sender?->role) {
            'admin' => 'Администратор',
            'seller' => 'Продавец',
            default => 'Покупатель',
        };
        $relatedConversation = $message->related_conversation_id ? $message->relatedConversation : null;
        $relatedConversationUrl = null;
        $relatedBuyerUrl = null;
        $relatedSellerUrl = null;

        if ($relatedConversation && auth()->user()?->role === 'admin') {
            $relatedConversationUrl = route('admin.chats.show', $relatedConversation);
            $relatedBuyerUrl = $relatedConversation->buyer ? route('admin.users.show', $relatedConversation->buyer) : null;
            $relatedSellerUrl = $relatedConversation->seller?->shop?->slug
                ? route('seller.show', $relatedConversation->seller->shop->slug)
                : ($relatedConversation->seller ? route('admin.users.show', $relatedConversation->seller) : null);
        } elseif ($relatedConversation && $relatedConversation->includes(auth()->user())) {
            $relatedConversationUrl = route('chats.show', $relatedConversation);
        }
    @endphp
    @if($message->isInternalNote())
        @if(! $showInternalNotes)
            @continue
        @endif
        <div class="flex justify-center">
            <div class="max-w-[92%] rounded-2xl border border-violet-200 bg-violet-50 px-4 py-2 text-sm leading-5 text-violet-900 shadow-sm">
                <div class="mb-1 flex flex-wrap items-center justify-center gap-2 text-xs font-bold uppercase tracking-wide text-violet-600">
                    <span>Внутренняя заметка</span>
                    <span class="normal-case tracking-normal text-violet-500">{{ $message->sender?->name ?? 'Администратор' }}</span>
                </div>
                <div class="whitespace-pre-wrap break-words text-center">{{ $message->body }}</div>
                <div class="mt-1 text-center text-[11px] font-medium text-violet-500">
                    {{ $message->created_at->isToday() ? 'Сегодня, ' . $message->created_at->format('H:i') : $message->created_at->format('d.m H:i') }}
                </div>
            </div>
        </div>
        @continue
    @endif
    @if($message->isSystem())
        @if($relatedConversationUrl)
            <div class="flex justify-center">
                <div class="w-full max-w-[92%] overflow-hidden rounded-2xl border border-amber-200 bg-white text-left shadow-sm sm:max-w-[720px]">
                    <div class="flex items-start gap-3 rounded-t-2xl bg-amber-50 px-4 py-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white">
                            <i class="ri-customer-service-2-line text-xl"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-bold text-amber-950">Обращение в поддержку</span>
                                <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-bold text-amber-700 ring-1 ring-amber-200">Диалог #{{ $relatedConversation->id }}</span>
                            </div>
                            <div class="mt-1 text-xs font-medium text-amber-700">Откройте участников или исходный диалог через ссылки ниже.</div>
                        </div>
                    </div>

                    <div class="space-y-2 px-4 py-3 text-sm leading-6 text-slate-700">
                        @foreach(preg_split("/\r\n|\n|\r/", $message->body) as $line)
                            @if(trim($line) !== '')
                                @php
                                    [$label, $value] = str_contains($line, ': ')
                                        ? explode(': ', $line, 2)
                                        : [null, $line];
                                @endphp
                                <div class="{{ $label === 'Подробности' ? 'rounded-xl border border-amber-100 bg-amber-50 px-3 py-2' : '' }}">
                                    @if($label)
                                        <span class="font-bold text-slate-500">{{ $label }}:</span>
                                        <span class="break-words text-slate-800">{{ $value }}</span>
                                    @else
                                        <span class="break-words font-semibold text-slate-900">{{ $value }}</span>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="flex flex-wrap items-center gap-2 border-t border-amber-100 px-4 py-3 text-xs font-semibold">
                        @if($relatedBuyerUrl)
                            <a href="{{ $relatedBuyerUrl }}"
                               class="inline-flex h-8 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 text-slate-600 transition hover:border-indigo-200 hover:text-indigo-700">
                                <i class="ri-user-line"></i>
                                Покупатель
                            </a>
                        @endif
                        @if($relatedSellerUrl)
                            <a href="{{ $relatedSellerUrl }}"
                               class="inline-flex h-8 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 text-slate-600 transition hover:border-indigo-200 hover:text-indigo-700">
                                <i class="ri-store-2-line"></i>
                                Продавец
                            </a>
                        @endif
                        <a href="{{ $relatedConversationUrl }}"
                           class="inline-flex h-8 items-center gap-1.5 rounded-full bg-amber-600 px-3 text-white transition hover:bg-amber-700">
                            <i class="ri-arrow-right-up-line"></i>
                            Исходный диалог
                        </a>
                        <span class="ml-auto text-slate-500">
                        <span>{{ $message->created_at->isToday() ? 'Сегодня, ' . $message->created_at->format('H:i') : $message->created_at->format('d.m H:i') }}</span>
                        </span>
                    </div>
                </div>
            </div>
        @else
            <div class="flex justify-center">
                <div class="max-w-[92%] rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold leading-5 text-amber-900 shadow-sm">
                    <div class="whitespace-pre-wrap text-center">{{ $message->body }}</div>
                    <div class="mt-2 text-center text-[11px] font-medium text-amber-600">
                        {{ $message->created_at->isToday() ? 'Сегодня, ' . $message->created_at->format('H:i') : $message->created_at->format('d.m H:i') }}
                    </div>
                </div>
            </div>
        @endif
        @continue
    @endif
    <div class="flex w-full {{ $mine ? 'justify-end' : 'justify-start' }}"
         @if($mine) data-message-id="{{ $message->id }}" @endif>
        <div class="max-w-[92%] rounded-[1.4rem] px-4 py-3 shadow-sm sm:max-w-[84%]
                    {{ $mine ? 'rounded-br-md bg-indigo-600 text-white' : 'rounded-bl-md border border-slate-200 bg-white text-slate-800' }}">
            @if($showSenderLabels && ! $mine)
                <div class="mb-1 truncate text-xs font-semibold text-slate-500">
                    {{ $message->sender?->name ?? 'Пользователь' }} · {{ $senderRole }}
                </div>
            @endif
            @if($message->image_path)
                <a href="{{ route($imageRouteName, [$conversation, $message]) }}"
                   target="_blank"
                   rel="noopener"
                   class="mb-2 block overflow-hidden rounded-2xl bg-black/5">
                    <img src="{{ route($imageRouteName, [$conversation, $message]) }}"
                         alt="Фото в сообщении"
                         loading="lazy"
                         class="max-h-80 w-full object-cover">
                </a>
            @endif
            @if($message->body !== '')
                <div class="whitespace-pre-wrap break-words text-sm leading-6">{{ $message->body }}</div>
            @endif
            <div class="mt-1 flex items-center gap-1.5 text-[11px] {{ $mine ? 'text-indigo-100' : 'text-slate-400' }}">
                <span>
                    {{ $message->created_at->isToday() ? 'Сегодня, ' . $message->created_at->format('H:i') : $message->created_at->format('d.m H:i') }}
                </span>
                @if($mine)
                    <span class="wv-read-status {{ $message->read_at ? 'is-read' : '' }}"
                          data-read-status
                          title="{{ $message->read_at ? 'Прочитано' : 'Отправлено' }}"
                          aria-label="{{ $message->read_at ? 'Прочитано' : 'Отправлено' }}">
                        <span></span>
                        <span></span>
                    </span>
                @endif
            </div>
        </div>
    </div>
@endforeach
