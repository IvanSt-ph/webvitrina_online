@extends('admin.layout')

@section('title', 'Чаты')

@section('content')
@php
    $isSupportMode = $mode === \App\Models\Conversation::TYPE_SUPPORT;
@endphp

<div class="admin-chats-page flex h-full min-h-0 min-w-0 flex-col gap-2 overflow-hidden"
     x-data="{
         sending: false,
         sendError: '',
         actionError: '',
         selectedImageName: '',
         moderationOpen: false,
         quickRepliesOpen: false,
         mobileListOpen: {{ request()->routeIs('admin.chats.show') ? 'false' : 'true' }},
         chatListWidth: Number(localStorage.getItem('adminChatListWidth') || 360),
         resizingChats: false,
         resizeStartX: 0,
         resizeStartWidth: 360,
         scrollMessagesToBottom(behavior = 'auto') {
             const el = this.$refs.messagesScroll;
             if (!el) return;
             el.scrollTo({ top: el.scrollHeight, behavior });
         },
         startChatResize(event) {
             this.resizingChats = true;
             this.resizeStartX = event.clientX;
             this.resizeStartWidth = this.chatListWidth;
             document.body.classList.add('select-none');
         },
         resizeChats(event) {
             if (!this.resizingChats) return;
             const maxWidth = Math.min(560, Math.floor(document.documentElement.clientWidth * 0.55));
             this.chatListWidth = Math.max(280, Math.min(maxWidth, this.resizeStartWidth + event.clientX - this.resizeStartX));
         },
         stopChatResize() {
             if (!this.resizingChats) return;
             this.resizingChats = false;
             document.body.classList.remove('select-none');
             localStorage.setItem('adminChatListWidth', String(this.chatListWidth));
         }
     }"
     x-init="$nextTick(() => { scrollMessagesToBottom(); setTimeout(() => scrollMessagesToBottom(), 80); setTimeout(() => scrollMessagesToBottom(), 260); })"
     @mousemove.window="resizeChats($event)"
     @mouseup.window="stopChatResize()"
     @mouseleave.window="stopChatResize()">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="flex shrink-0 flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm lg:flex-row lg:items-center lg:justify-between lg:gap-3 lg:p-2.5"
         :class="mobileListOpen ? '' : 'max-lg:hidden'">
        <div class="grid grid-cols-2 gap-2">
            <a href="{{ route('admin.chats.index', ['mode' => 'support']) }}"
               class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl px-3 text-xs font-bold transition sm:h-11 sm:gap-2 sm:px-4 sm:text-sm {{ $isSupportMode ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'bg-slate-100 text-slate-600 hover:bg-indigo-50 hover:text-indigo-700' }}">
                <i class="ri-customer-service-2-line"></i>
                Support-чаты
                @if($totals['support_unread'])
                    <span data-admin-support-unread="{{ $totals['support_unread'] }}" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full {{ $isSupportMode ? 'bg-white text-indigo-700' : 'bg-rose-500 text-white' }} px-1.5 text-[11px] font-bold">
                        {{ $totals['support_unread'] > 99 ? '99+' : $totals['support_unread'] }}
                    </span>
                @endif
            </a>
            <a href="{{ route('admin.chats.index', ['mode' => 'marketplace']) }}"
               class="inline-flex h-9 items-center justify-center gap-1.5 rounded-xl px-3 text-xs font-bold transition sm:h-11 sm:gap-2 sm:px-4 sm:text-sm {{ ! $isSupportMode ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'bg-slate-100 text-slate-600 hover:bg-indigo-50 hover:text-indigo-700' }}">
                <i class="ri-shield-check-line"></i>
                Marketplace
                @if($totals['marketplace_unread'])
                    <span data-admin-marketplace-unread="{{ $totals['marketplace_unread'] }}" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full {{ ! $isSupportMode ? 'bg-white text-indigo-700' : 'bg-rose-500 text-white' }} px-1.5 text-[11px] font-bold">
                        {{ $totals['marketplace_unread'] > 99 ? '99+' : $totals['marketplace_unread'] }}
                    </span>
                @endif
            </a>
        </div>

        <form method="GET" action="{{ route('admin.chats.index') }}" class="grid min-w-0 flex-1 grid-cols-[minmax(0,1fr)_44px] gap-2 sm:grid-cols-[minmax(0,1fr)_180px_auto] lg:grid-cols-[minmax(0,1fr)_220px_auto]">
            <input type="hidden" name="mode" value="{{ $mode }}">
            <label class="relative min-w-0">
                <i class="ri-search-line absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="search"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="Имя, email, магазин, товар или текст"
                       class="h-10 w-full rounded-xl border-slate-200 bg-slate-50 pl-11 pr-4 text-sm focus:border-indigo-300 focus:bg-white focus:ring-4 focus:ring-indigo-100 sm:h-11">
            </label>
            <select name="type"
                    class="hidden h-11 rounded-xl border-slate-200 bg-slate-50 px-4 text-sm font-medium text-slate-700 focus:border-indigo-300 focus:bg-white focus:ring-4 focus:ring-indigo-100 sm:block">
                <option value="">Все</option>
                @if(! $isSupportMode)
                    <option value="product" @selected(request('type') === 'product')>По товарам</option>
                    <option value="general" @selected(request('type') === 'general')>Общие</option>
                @endif
                <option value="priority" @selected(request('type') === 'priority')>Приоритетные</option>
                <option value="locked" @selected(request('type') === 'locked')>Заблокированные</option>
            </select>
            <button class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 sm:h-11 sm:px-5">
                <i class="ri-filter-3-line"></i>
                <span class="hidden sm:inline">Применить</span>
            </button>
        </form>
    </div>

    <div class="flex min-h-0 min-w-0 flex-1 overflow-hidden lg:grid lg:gap-0"
         :style="{ gridTemplateColumns: chatListWidth + 'px 10px minmax(0, 1fr)' }">
        <section class="min-h-0 min-w-0 flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/80 shadow-sm lg:flex lg:flex-col"
                 :class="mobileListOpen ? 'max-lg:flex max-lg:flex-col' : 'max-lg:hidden'">
            <div class="sticky top-0 z-10 border-b border-slate-200 bg-white px-4 py-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="font-semibold text-slate-900">{{ $isSupportMode ? 'Support-диалоги' : 'Marketplace-диалоги' }}</div>
                    <div class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-500">{{ $conversations->total() }}</div>
                </div>
            </div>

            <div class="h-full min-h-0 min-w-0 overflow-y-auto p-2">
                @forelse($conversations as $conversation)
                    @php
                        $active = $selectedConversation?->id === $conversation->id;
                        $conversationUrl = route('admin.chats.show', $conversation);
                        $buyerUrl = $conversation->buyer ? route('admin.users.show', $conversation->buyer) : null;
                        $sellerUrl = $conversation->seller?->shop?->slug
                            ? route('seller.show', $conversation->seller->shop->slug)
                            : ($conversation->seller ? route('admin.users.show', $conversation->seller) : null);
                        $buyerTrust = $conversation->buyer ? ($trustProfiles[$conversation->buyer->id] ?? null) : null;
                        $sellerTrust = $conversation->seller ? ($trustProfiles[$conversation->seller->id] ?? null) : null;
                        $preview = $conversation->lastMessage?->body !== ''
                            ? ($conversation->lastMessage?->body ?? 'Сообщений пока нет')
                            : ($conversation->lastMessage?->image_path ? 'Фото' : 'Сообщений пока нет');
                        $priority = $conversation->isLocked()
                            ? ['label' => 'Высокий', 'class' => 'bg-rose-50 text-rose-700 border-rose-100', 'reason' => 'заблокирован']
                            : ($conversation->unread_count > 0
                                ? ['label' => 'Высокий', 'class' => 'bg-amber-50 text-amber-700 border-amber-100', 'reason' => 'есть непрочитанные']
                                : ($conversation->isSupport()
                                    ? ['label' => 'Средний', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-100', 'reason' => 'support']
                                    : ['label' => 'Обычный', 'class' => 'bg-slate-50 text-slate-600 border-slate-100', 'reason' => 'marketplace']));
                    @endphp
                    <div class="group relative mb-2 rounded-2xl border p-3 transition
                                {{ $active ? 'border-indigo-200 bg-indigo-50 shadow-sm' : 'border-white bg-white hover:border-slate-200 hover:shadow-md hover:shadow-slate-900/5' }}">
                        <a href="{{ $conversationUrl }}"
                           class="absolute inset-0 z-0 rounded-2xl"
                           aria-label="Открыть чат #{{ $conversation->id }}"></a>
                        <div class="pointer-events-none relative z-10 flex min-w-0 gap-3">
                            <div class="relative shrink-0">
                                <img src="{{ $conversation->buyer?->avatar_url ?? 'https://ui-avatars.com/api/?name=User&color=7F9CF5&background=EBF4FF' }}"
                                     alt="{{ $conversation->buyer?->name ?? 'Пользователь' }}"
                                     class="h-11 w-11 rounded-2xl object-cover ring-1 ring-slate-900/5">
                                <span class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full border-2 border-white {{ $conversation->isLocked() ? 'bg-rose-500 text-white' : ($conversation->isSupport() ? 'bg-indigo-600 text-white' : 'bg-slate-700 text-white') }}">
                                    <i class="{{ $conversation->isLocked() ? 'ri-lock-2-line' : ($conversation->isSupport() ? 'ri-customer-service-2-line' : 'ri-shield-check-line') }} text-[11px]"></i>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex min-w-0 items-start gap-2">
                                    <div class="min-w-0 flex-1">
                                        @if($buyerUrl)
                                            <a href="{{ $buyerUrl }}"
                                               class="pointer-events-auto block truncate text-sm font-bold text-slate-900 hover:text-indigo-700 hover:underline"
                                               title="Открыть карточку покупателя">
                                                {{ $conversation->buyer?->name ?? 'Пользователь удалён' }}
                                            </a>
                                        @else
                                            <div class="truncate text-sm font-bold text-slate-900">Пользователь удалён</div>
                                        @endif
                                        @if($conversation->isSupport())
                                            <div class="truncate text-xs text-slate-500">Поддержка WebVitrina</div>
                                        @elseif($sellerUrl)
                                            <a href="{{ $sellerUrl }}"
                                               class="pointer-events-auto block truncate text-xs text-slate-500 hover:text-indigo-700 hover:underline"
                                               title="Открыть страницу продавца">
                                                {{ $conversation->seller?->shop?->name ?? $conversation->seller?->name ?? 'Продавец удалён' }}
                                            </a>
                                        @else
                                            <div class="truncate text-xs text-slate-500">Продавец удалён</div>
                                        @endif
                                    </div>
                                    @if($conversation->unread_count)
                                        <span class="ml-auto inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-rose-500 px-1.5 text-[11px] font-bold text-white">
                                            {{ $conversation->unread_count > 99 ? '99+' : $conversation->unread_count }}
                                        </span>
                                    @endif
                                    <span class="shrink-0 rounded-full border px-2 py-0.5 text-[11px] font-bold {{ $priority['class'] }}" title="{{ $priority['reason'] }}">
                                        {{ $priority['label'] }}
                                    </span>
                                </div>

                                @if($conversation->isLocked())
                                    <div class="mt-1 truncate text-xs font-semibold text-rose-600">Заблокирован</div>
                                @elseif($conversation->product)
                                    <div class="mt-1 truncate text-xs font-semibold text-indigo-600" title="{{ $conversation->product->title }}">
                                        {{ $conversation->product->title }}
                                    </div>
                                @else
                                    <div class="mt-1 text-xs font-semibold text-slate-400">{{ $conversation->isSupport() ? 'Support' : 'Общий диалог' }}</div>
                                @endif

                                <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px]">
                                    @if($buyerTrust)
                                        <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 font-bold {{ $buyerTrust['class'] }}" title="Покупатель: {{ $buyerTrust['label'] }} · {{ $buyerTrust['score'] }}%">
                                            <span>{{ $buyerTrust['icon'] }}</span>
                                            Покупатель {{ $buyerTrust['short_label'] }}
                                        </span>
                                    @endif
                                    @if(! $conversation->isSupport() && $sellerTrust)
                                        <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 font-bold {{ $sellerTrust['class'] }}" title="Продавец: {{ $sellerTrust['label'] }} · {{ $sellerTrust['score'] }}%">
                                            <span>{{ $sellerTrust['icon'] }}</span>
                                            Продавец {{ $sellerTrust['short_label'] }}
                                        </span>
                                    @endif
                                </div>

                                <div class="mt-1 truncate text-sm text-slate-500">{{ $preview }}</div>

                                <div class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px]">
                                    <span class="text-slate-400">{{ $conversation->last_message_at?->diffForHumans() ?? $conversation->updated_at->diffForHumans() }}</span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-500">ID {{ $conversation->id }}</span>
                                    @if($conversation->internal_notes_count)
                                        <span class="rounded-full bg-violet-50 px-2 py-0.5 font-semibold text-violet-700" title="Внутренние заметки админа">
                                            Заметки {{ $conversation->internal_notes_count }}
                                        </span>
                                    @endif
                                    @if($conversation->system_events_count)
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 font-semibold text-amber-700" title="Системные уведомления в диалоге">
                                            Системные {{ $conversation->system_events_count }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-8 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                            <i class="ri-chat-3-line text-2xl"></i>
                        </div>
                        <div class="mt-4 font-semibold text-slate-900">Диалоги не найдены</div>
                        <div class="mt-1 text-sm text-slate-500">Попробуйте изменить поиск или фильтр.</div>
                    </div>
                @endforelse
            </div>

            @if($conversations->hasPages())
                <div class="border-t border-slate-200 bg-white px-3 py-3">
                    {{ $conversations->links() }}
                </div>
            @endif
        </section>

        <button type="button"
                class="group hidden cursor-col-resize items-center justify-center lg:flex"
                @mousedown.prevent="startChatResize($event)"
                title="Изменить ширину списка чатов">
            <span class="h-16 w-1 rounded-full bg-slate-200 transition group-hover:bg-indigo-300"
                  :class="resizingChats ? 'bg-indigo-400' : ''"></span>
        </button>

        <section class="min-h-0 min-w-0 w-full flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:justify-center lg:h-full"
                 :class="mobileListOpen ? 'max-lg:hidden' : 'max-lg:flex'">
            @if($selectedConversation)
                @php
                    $locked = $selectedConversation->isLocked();
                    $selectedSupport = $selectedConversation->isSupport();
                    $selectedBuyerUrl = $selectedConversation->buyer ? route('admin.users.show', $selectedConversation->buyer) : null;
                    $selectedSellerUrl = $selectedConversation->seller?->shop?->slug
                        ? route('seller.show', $selectedConversation->seller->shop->slug)
                        : ($selectedConversation->seller ? route('admin.users.show', $selectedConversation->seller) : null);
                    $selectedBuyerTrust = $selectedConversation->buyer ? ($trustProfiles[$selectedConversation->buyer->id] ?? null) : null;
                    $selectedSellerTrust = $selectedConversation->seller ? ($trustProfiles[$selectedConversation->seller->id] ?? null) : null;
                @endphp
                <div class="flex h-full min-h-0 min-w-0 w-full flex-col">
                    <header class="shrink-0 border-b border-slate-100 bg-white px-3 py-2 sm:px-4">
                        <div class="flex min-w-0 items-start gap-2">
                            <button type="button"
                                    @click="mobileListOpen = true"
                                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-slate-200 lg:hidden"
                                    title="Вернуться к списку">
                                <i class="ri-arrow-left-s-line text-lg"></i>
                            </button>

                            <div class="min-w-0 flex-1">
                                <div class="flex min-w-0 items-center gap-2">
                                    <div class="min-w-0 flex-1 truncate text-sm font-bold text-slate-950 sm:text-lg">
                                    @if($selectedBuyerUrl)
                                        <a href="{{ $selectedBuyerUrl }}"
                                           class="hover:text-indigo-700 hover:underline"
                                           title="Открыть карточку покупателя">
                                            {{ $selectedConversation->buyer?->name ?? 'Пользователь удалён' }}
                                        </a>
                                    @else
                                        Пользователь удалён
                                    @endif
                                    <span class="text-slate-400">/</span>
                                    @if($selectedSupport)
                                        Поддержка WebVitrina
                                    @elseif($selectedSellerUrl)
                                        <a href="{{ $selectedSellerUrl }}"
                                           class="hover:text-indigo-700 hover:underline"
                                           title="Открыть страницу продавца">
                                            {{ $selectedConversation->seller?->shop?->name ?? $selectedConversation->seller?->name ?? 'Продавец удалён' }}
                                        </a>
                                    @else
                                        Продавец удалён
                                    @endif
                                    </div>
                                    <div class="hidden shrink-0 items-center gap-1.5 sm:flex">
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">ID {{ $selectedConversation->id }}</span>
                                        <span class="rounded-full {{ $selectedSupport ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1 text-xs font-semibold">
                                            {{ $selectedSupport ? 'Support' : 'Marketplace: только модерация' }}
                                        </span>
                                        @if($locked)
                                            <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">Блок</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-2 flex min-w-0 flex-wrap gap-1.5">
                                    @if($selectedBuyerTrust)
                                        <a href="{{ $selectedBuyerUrl ?? '#' }}"
                                           class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-bold {{ $selectedBuyerTrust['class'] }} {{ $selectedBuyerUrl ? 'hover:shadow-sm' : 'pointer-events-none' }}"
                                           title="Покупатель: {{ $selectedBuyerTrust['label'] }} · {{ $selectedBuyerTrust['score'] }}%">
                                            <span>{{ $selectedBuyerTrust['icon'] }}</span>
                                            Покупатель: {{ $selectedBuyerTrust['short_label'] }} {{ $selectedBuyerTrust['score'] }}%
                                        </a>
                                    @endif
                                    @if(! $selectedSupport && $selectedSellerTrust)
                                        <a href="{{ $selectedConversation->seller ? route('admin.users.show', $selectedConversation->seller) : '#' }}"
                                           class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-bold {{ $selectedSellerTrust['class'] }} {{ $selectedConversation->seller ? 'hover:shadow-sm' : 'pointer-events-none' }}"
                                           title="Продавец: {{ $selectedSellerTrust['label'] }} · {{ $selectedSellerTrust['score'] }}%">
                                            <span>{{ $selectedSellerTrust['icon'] }}</span>
                                            Продавец: {{ $selectedSellerTrust['short_label'] }} {{ $selectedSellerTrust['score'] }}%
                                        </a>
                                    @endif
                                </div>

                                <div class="mt-2 flex min-w-0 gap-1.5 overflow-x-auto pb-1 sm:mt-2 sm:flex-wrap sm:overflow-visible sm:pb-0">
                                @if(! $selectedSupport)
                                    <form method="POST" action="{{ route('admin.chats.support.start', $selectedConversation->buyer) }}" class="hidden sm:block">
                                        @csrf
                                        <input type="hidden" name="source_conversation_id" value="{{ $selectedConversation->id }}">
                                        <button class="inline-flex h-9 items-center gap-1.5 rounded-full border border-indigo-100 bg-indigo-50 px-3 text-xs font-bold text-indigo-700 transition hover:border-indigo-200">
                                            <i class="ri-user-voice-line"></i>
                                            Покупателю
                                        </button>
                                    </form>
                                    @if($selectedConversation->seller)
                                        <form method="POST" action="{{ route('admin.chats.support.start', $selectedConversation->seller) }}" class="hidden sm:block">
                                            @csrf
                                            <input type="hidden" name="source_conversation_id" value="{{ $selectedConversation->id }}">
                                            <button class="inline-flex h-9 items-center gap-1.5 rounded-full border border-indigo-100 bg-indigo-50 px-3 text-xs font-bold text-indigo-700 transition hover:border-indigo-200">
                                                <i class="ri-store-2-line"></i>
                                                Продавцу
                                            </button>
                                        </form>
                                    @endif
                                @endif

                                @if($locked)
                                    <form method="POST" action="{{ route('admin.chats.unlock', $selectedConversation) }}">
                                        @csrf
                                        <button class="inline-flex h-9 items-center gap-1.5 rounded-full border border-emerald-100 bg-emerald-50 px-3 text-xs font-bold text-emerald-700 transition hover:border-emerald-200">
                                            <i class="ri-lock-unlock-line"></i>
                                            Разблокировать
                                        </button>
                                    </form>
                                @endif

                                <button type="button"
                                        @click="moderationOpen = !moderationOpen"
                                        class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700"
                                        :class="moderationOpen ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : ''">
                                    <i class="ri-tools-line"></i>
                                    Модерация
                                </button>

                                @if($selectedConversation->product)
                                    <a href="{{ route('product.show', [
                                            'identifier' => $selectedConversation->product->slug,
                                            'admin_chat' => $selectedConversation->id,
                                        ]) }}"
                                       class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-full border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700">
                                        <i class="ri-arrow-right-up-line"></i>
                                        Товар
                                    </a>
                                @endif
                                    <form method="POST"
                                          action="{{ route('admin.chats.destroy', $selectedConversation) }}"
                                          onsubmit="return confirm('Скрыть этот диалог из админского списка? Сообщения останутся в базе.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-full border border-rose-100 bg-rose-50 px-3 text-xs font-bold text-rose-700 transition hover:border-rose-200">
                                            <i class="ri-delete-bin-6-line"></i>
                                            Удалить
                                        </button>
                                    </form>
                                    <span class="inline-flex h-9 shrink-0 items-center rounded-full bg-slate-100 px-2.5 text-xs font-bold text-slate-500 sm:hidden">ID {{ $selectedConversation->id }}</span>
                                    @if($locked)
                                        <span class="inline-flex h-9 shrink-0 items-center rounded-full bg-rose-50 px-2.5 text-xs font-bold text-rose-700 sm:hidden">Блок</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($locked)
                            <div class="mt-2 rounded-xl border border-rose-100 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                                <div class="font-bold">Диалог временно заблокирован.</div>
                                @if($selectedConversation->locked_reason)
                                    <div class="mt-1">{{ $selectedConversation->locked_reason }}</div>
                                @endif
                            </div>
                        @endif
                    </header>

                    <div x-ref="messagesScroll" class="min-h-0 w-full flex-1 overflow-y-auto bg-gradient-to-b from-slate-50 to-white px-2.5 py-2.5 sm:px-6 sm:py-4">
                        <div x-ref="messages" class="w-full space-y-2.5 sm:space-y-4">
                            @forelse($messages as $message)
                                @include('chats.partials.messages', [
                                    'conversation' => $selectedConversation,
                                    'messages' => collect([$message]),
                                    'imageRouteName' => 'admin.chats.messages.image',
                                    'showSenderLabels' => true,
                                    'showInternalNotes' => true,
                                ])
                            @empty
                                <div class="flex min-h-[320px] flex-col items-center justify-center text-center">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-indigo-50 text-indigo-600">
                                        <i class="ri-sparkling-2-line text-3xl"></i>
                                    </div>
                                    <h2 class="mt-4 text-xl font-semibold text-slate-900">Сообщений пока нет</h2>
                                    <p class="mt-2 max-w-sm text-sm text-slate-500">Support-чат можно начать первым, marketplace-диалог лучше не смешивать с ответами поддержки.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @if($selectedSupport)
                        <div class="shrink-0 border-t border-slate-100 bg-white p-2.5 sm:p-3">
                            @if($locked)
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-500">
                                    Support-чат заблокирован. Разблокируйте его, чтобы продолжить переписку.
                                </div>
                            @else
                                <form method="POST"
                                      action="{{ route('admin.chats.messages.store', $selectedConversation) }}"
                                      enctype="multipart/form-data"
                                      @submit.prevent="
                                          sending = true;
                                          sendError = '';
                                          fetch($event.target.action, {
                                              method: 'POST',
                                              body: new FormData($event.target),
                                              headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                                          })
                                              .then(async response => {
                                                  if (!response.ok) throw new Error('send');
                                                  return response.json();
                                              })
                                              .then(payload => {
                                                  $refs.messages.insertAdjacentHTML('beforeend', payload.html);
                                                  $event.target.reset();
                                                  selectedImageName = '';
                                                  $nextTick(() => $refs.messages.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'end' }));
                                              })
                                              .catch(() => sendError = 'Не удалось отправить сообщение. Проверьте текст или фото.')
                                              .finally(() => sending = false);
                                      "
                                      class="rounded-xl border border-slate-200 bg-slate-50 p-2">
                                    @csrf
                                    <div x-show="selectedImageName" x-cloak class="mb-2 flex items-center justify-between rounded-xl bg-indigo-50 px-3 py-2 text-sm text-indigo-700">
                                        <span class="truncate" x-text="selectedImageName"></span>
                                        <button type="button"
                                                @click="$refs.adminImage.value = ''; selectedImageName = ''"
                                                class="ml-3 text-indigo-500 hover:text-indigo-700">
                                            <i class="ri-close-line"></i>
                                        </button>
                                    </div>
                                    <div x-show="sendError" x-cloak class="mb-2 rounded-xl bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700" x-text="sendError"></div>
                                    <div class="relative mb-2">
                                        <button type="button"
                                                @click="quickRepliesOpen = !quickRepliesOpen"
                                                class="inline-flex h-9 items-center gap-2 rounded-xl border border-indigo-100 bg-white px-3 text-xs font-bold text-indigo-700 transition hover:border-indigo-200 hover:bg-indigo-50">
                                            <i class="ri-flashlight-line"></i>
                                            Быстрые ответы
                                            <i class="ri-arrow-down-s-line transition" :class="{ 'rotate-180': quickRepliesOpen }"></i>
                                        </button>
                                        <div x-show="quickRepliesOpen"
                                             x-cloak
                                             @click.outside="quickRepliesOpen = false"
                                             class="absolute bottom-10 left-0 z-20 w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                                            @foreach([
                                                'Здравствуйте! Уточните, пожалуйста, номер заказа или название товара, чтобы мы быстрее проверили ситуацию.',
                                                'Спасибо, мы уже проверяем ситуацию и вернёмся с ответом в этом чате.',
                                                'Передали вопрос продавцу. Как только будет ответ, сообщим вам здесь.',
                                                'Пожалуйста, не отправляйте личные данные в общий marketplace-диалог. Мы продолжим безопасно в support-чате.',
                                                'Мы видим обращение и проверим переписку. Пока не удаляйте сообщения и вложения по спорной ситуации.',
                                            ] as $template)
                                            <button type="button"
                                                    @click="$refs.adminBody.value = @js($template); $refs.adminBody.focus(); quickRepliesOpen = false"
                                                    class="block w-full rounded-xl px-3 py-2 text-left text-sm leading-5 text-slate-700 transition hover:bg-indigo-50 hover:text-indigo-700">
                                                {{ $template }}
                                            </button>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="flex min-w-0 items-end gap-2">
                                        <label class="flex min-h-[48px] w-[48px] shrink-0 cursor-pointer items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-600 transition hover:border-indigo-200 hover:text-indigo-600">
                                            <i class="ri-image-add-line text-lg"></i>
                                            <input x-ref="adminImage"
                                                   @change="selectedImageName = $event.target.files?.[0]?.name ?? ''"
                                                   type="file"
                                                   name="image"
                                                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                                   class="hidden">
                                        </label>
                                        <textarea name="body"
                                                  x-ref="adminBody"
                                                  rows="1"
                                                  maxlength="2000"
                                                  @keydown.enter="if (!$event.shiftKey) { $event.preventDefault(); $el.form.requestSubmit(); }"
                                                  placeholder="Ответить пользователю от поддержки..."
                                                  class="min-h-[48px] min-w-0 flex-1 resize-none rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm leading-6 focus:border-indigo-300 focus:bg-white focus:ring-4 focus:ring-indigo-100"></textarea>
                                        <button :disabled="sending"
                                                class="flex min-h-[48px] shrink-0 items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-4 font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-70">
                                            <i class="ri-send-plane-2-line"></i>
                                            <span class="hidden sm:inline" x-text="sending ? 'Отправляем...' : 'Отправить'">Отправить</span>
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endif

                    <div x-show="moderationOpen"
                         x-cloak
                         x-transition.opacity.duration.150ms
                         @keydown.escape.window="moderationOpen = false"
                         @click.self="moderationOpen = false"
                         class="fixed inset-0 z-[70] flex items-end justify-center bg-slate-950/45 p-2 sm:items-center sm:p-4">
                        <div class="max-h-[88vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl ring-1 ring-slate-950/10">
                            <div class="sticky top-0 z-10 border-b border-slate-100 bg-white/95 px-4 py-3 backdrop-blur">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-base font-bold text-slate-950">Модерация чата</div>
                                        <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs font-semibold text-slate-500">
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1">ID {{ $selectedConversation->id }}</span>
                                            <span class="rounded-full {{ $selectedSupport ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600' }} px-2.5 py-1">
                                                {{ $selectedSupport ? 'Support-чат' : 'Marketplace-диалог' }}
                                            </span>
                                            @if($locked)
                                                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-rose-700">Заблокирован</span>
                                            @endif
                                        </div>
                                    </div>
                                    <button type="button"
                                            @click="moderationOpen = false"
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 transition hover:bg-slate-200 hover:text-slate-900"
                                            title="Закрыть">
                                        <i class="ri-close-line text-lg"></i>
                                    </button>
                                </div>
                                @if(! $selectedSupport)
                                    <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm leading-5 text-slate-600">
                                        Это диалог покупателя и продавца. Для общения используйте отдельные кнопки «Покупателю» или «Продавцу», а здесь оставляйте только системные уведомления и модерационные действия.
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-3 p-3 sm:p-4">
                                <div x-show="actionError" x-cloak class="rounded-xl bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700" x-text="actionError"></div>

                                <form method="POST"
                                      action="{{ route('admin.chats.note', $selectedConversation) }}"
                                      @submit.prevent="
                                          actionError = '';
                                          fetch($event.target.action, {
                                              method: 'POST',
                                              body: new FormData($event.target),
                                              headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                                          })
                                              .then(async response => {
                                                  if (!response.ok) throw new Error('action');
                                                  return response.json();
                                              })
                                              .then(payload => {
                                                  $refs.messages.insertAdjacentHTML('beforeend', payload.html);
                                                  $event.target.reset();
                                                  moderationOpen = false;
                                                  $nextTick(() => $refs.messages.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'end' }));
                                              })
                                              .catch(() => actionError = 'Не удалось сохранить заметку. Проверьте текст.');
                                      "
                                      class="rounded-xl border border-violet-100 bg-violet-50 p-3">
                                    @csrf
                                    <label class="text-xs font-bold uppercase tracking-wide text-violet-700">Внутренняя заметка</label>
                                    <div class="mt-2 flex min-w-0 gap-2">
                                        <input name="body"
                                               maxlength="1000"
                                               placeholder="Видно только администраторам"
                                               class="h-11 min-w-0 flex-1 rounded-xl border-violet-200 bg-white px-3 text-sm focus:border-violet-300 focus:ring-4 focus:ring-violet-100">
                                        <button class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-violet-600 px-3 text-sm font-bold text-white transition hover:bg-violet-700">
                                            <i class="ri-sticky-note-line"></i>
                                            <span class="hidden sm:inline">Сохранить</span>
                                        </button>
                                    </div>
                                </form>

                                <div class="grid gap-3 lg:grid-cols-2">
                                    <form method="POST"
                                          action="{{ route('admin.chats.system', $selectedConversation) }}"
                                          @submit.prevent="
                                              actionError = '';
                                              fetch($event.target.action, {
                                                  method: 'POST',
                                                  body: new FormData($event.target),
                                                  headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                                              })
                                                  .then(async response => {
                                                      if (!response.ok) throw new Error('action');
                                                      return response.json();
                                                  })
                                                  .then(payload => {
                                                      $refs.messages.insertAdjacentHTML('beforeend', payload.html);
                                                      $event.target.reset();
                                                      moderationOpen = false;
                                                      $nextTick(() => $refs.messages.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'end' }));
                                                  })
                                                  .catch(() => actionError = 'Не удалось добавить системное уведомление. Проверьте текст.');
                                          "
                                          class="min-w-0 rounded-xl border border-amber-100 bg-amber-50 p-3">
                                        @csrf
                                        <label class="text-xs font-bold uppercase tracking-wide text-amber-700">Системное уведомление</label>
                                        <div class="mt-2 flex min-w-0 gap-2">
                                            <input name="body"
                                                   maxlength="500"
                                                   placeholder="Например: Поддержка проверяет этот диалог."
                                                   class="h-11 min-w-0 flex-1 rounded-xl border-amber-200 bg-white px-3 text-sm focus:border-amber-300 focus:ring-4 focus:ring-amber-100">
                                            <button class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-amber-500 px-3 text-sm font-bold text-white transition hover:bg-amber-600">
                                                <i class="ri-megaphone-line"></i>
                                                <span class="hidden sm:inline">Добавить</span>
                                            </button>
                                        </div>
                                    </form>

                                    @if(! $locked)
                                        <form method="POST"
                                              action="{{ route('admin.chats.lock', $selectedConversation) }}"
                                              class="min-w-0 rounded-xl border border-rose-100 bg-rose-50 p-3">
                                            @csrf
                                            <label class="text-xs font-bold uppercase tracking-wide text-rose-700">Блокировка</label>
                                            <div class="mt-2 flex min-w-0 gap-2">
                                                <input name="reason"
                                                       maxlength="500"
                                                       placeholder="Причина, видна в системном сообщении"
                                                       class="h-11 min-w-0 flex-1 rounded-xl border-rose-200 bg-white px-3 text-sm focus:border-rose-300 focus:ring-4 focus:ring-rose-100">
                                                <button class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-rose-600 px-3 text-sm font-bold text-white transition hover:bg-rose-700">
                                                    <i class="ri-lock-2-line"></i>
                                                    <span class="hidden sm:inline">Блок</span>
                                                </button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="rounded-xl border border-rose-100 bg-rose-50 p-3 text-sm text-rose-700">
                                            <div class="font-bold">Диалог уже заблокирован</div>
                                            <div class="mt-1 text-rose-600">Разблокировать можно кнопкой в шапке диалога.</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex min-h-[560px] flex-col items-center justify-center px-6 text-center">
                    <div class="flex h-20 w-20 items-center justify-center rounded-[1.75rem] bg-indigo-50 text-indigo-600">
                        <i class="ri-chat-3-line text-4xl"></i>
                    </div>
                    <h2 class="mt-5 text-2xl font-bold text-slate-900">Выберите диалог</h2>
                    <p class="mt-2 max-w-md text-sm leading-6 text-slate-500">Слева список чатов. Непрочитанные поднимаются вверх, а справа откроется выбранная переписка.</p>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
