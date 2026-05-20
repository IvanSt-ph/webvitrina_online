@php
    $currentId = $currentConversation?->id ?? null;
    $inlineDesktop = $inlineDesktop ?? false;
@endphp

<div class="min-w-0 space-y-2">
    @if($conversations->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-center text-sm text-slate-500 sm:rounded-3xl">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                <i class="ri-chat-3-line text-2xl"></i>
            </div>
            <div class="font-semibold text-slate-800">У вас пока нет диалогов</div>
            <div class="mt-1 leading-6">Откройте товар или магазин и напишите продавцу.</div>
        </div>
    @else
    @foreach($conversations as $item)
        @php
            $other = $item->otherParticipant(auth()->user());
            $active = $currentId === $item->id;
            $otherProfileUrl = $other->isSeller() && $other->shop?->slug
                ? route('seller.show', $other->shop->slug)
                : null;
        @endphp
        <div class="group relative rounded-2xl border p-3 transition-all duration-200 sm:rounded-3xl
                    {{ $active ? 'border-indigo-200 bg-indigo-50 shadow-sm' : 'border-white bg-white hover:border-slate-200 hover:shadow-md hover:shadow-slate-900/5' }}">
            @if($inlineDesktop)
                <a href="{{ route('chats.show', $item) }}"
                   class="absolute inset-0 rounded-2xl sm:rounded-3xl lg:hidden"
                   aria-label="Открыть чат"></a>
                <a href="{{ route('chats.index', ['chat' => $item->id]) }}"
                   class="absolute inset-0 hidden rounded-2xl sm:rounded-3xl lg:block"
                   aria-label="Показать чат справа"></a>
            @else
                <a href="{{ route('chats.show', $item) }}"
                   class="absolute inset-0 rounded-2xl sm:rounded-3xl"
                   aria-label="Открыть чат"></a>
            @endif
            <div class="flex min-w-0 items-center gap-3">
                @if($item->product)
                    <a href="{{ route('product.show', $item->product->slug) }}"
                       class="relative z-10 shrink-0"
                       title="Открыть товар">
                        <img src="{{ $item->product->image_thumb_url }}"
                             alt="{{ $item->product->title }}"
                             class="h-12 w-12 rounded-2xl object-cover ring-1 ring-indigo-200 transition group-hover:ring-indigo-300">
                    </a>
                @else
                    <img src="{{ $other->avatar_url }}"
                         alt="{{ $other->name }}"
                         class="h-12 w-12 rounded-2xl object-cover ring-1 ring-slate-900/5">
                @endif
                <div class="min-w-0 flex-1">
                    <div class="flex min-w-0 items-center justify-between gap-2">
                        @if($otherProfileUrl)
                            <a href="{{ $otherProfileUrl }}"
                               class="relative z-10 min-w-0 truncate font-semibold text-slate-900 hover:text-indigo-700 hover:underline">
                                {{ $other->name }}
                            </a>
                        @else
                            <div class="relative z-10 truncate font-semibold text-slate-900">
                                {{ $other->name }}
                            </div>
                        @endif
                        @if($item->unread_count)
                            <span class="relative z-10 inline-flex min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 py-0.5 text-[11px] font-bold text-white">
                                {{ $item->unread_count }}
                            </span>
                        @endif
                    </div>
                    @if($item->product)
                        <div class="mt-0.5 max-w-[7rem] truncate text-[11px] font-medium text-indigo-600 sm:max-w-[10rem]"
                             title="{{ $item->product->title }}">
                            {{ $item->product->title }}
                        </div>
                    @else
                        <div class="mt-0.5 text-[11px] font-medium text-slate-400">
                            Общий диалог
                        </div>
                    @endif
                    <div class="mt-0.5 truncate text-sm text-slate-500">
                        {{ $item->lastMessage?->body !== ''
                            ? ($item->lastMessage?->body ?? 'Начните диалог')
                            : ($item->lastMessage?->image_path ? 'Фото' : 'Начните диалог') }}
                    </div>
                    @if($item->last_message_at)
                        <div class="mt-1 text-[11px] text-slate-400">
                            {{ $item->last_message_at->isToday() ? $item->last_message_at->format('H:i') : $item->last_message_at->diffForHumans() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
    @endif
</div>
