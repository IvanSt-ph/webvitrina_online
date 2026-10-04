@php
    $user = auth()->user();
    $isSeller = $user->isSeller();
    $notificationLayout = $isSeller ? 'seller-layout' : 'buyer-layout';
    $unreadCount = isset($unreadNotificationsCount)
        ? (int) $unreadNotificationsCount
        : $user->notifications()->whereNull('read_at')->count();
    $totalCount = $notifications->total();

    $notificationMeta = [
        'product_hidden_by_report' => ['ri-alarm-warning-line', 'bg-danger-50 text-danger-600', true],
        'review_rejected' => ['ri-chat-delete-line', 'bg-danger-50 text-danger-600', true],
        'order_dispute_opened' => ['ri-scales-3-line', 'bg-warning-50 text-warning-700', false],
        'order_status_updated' => ['ri-shopping-bag-3-line', 'bg-brand-50 text-brand-600', false],
        'new_message' => ['ri-chat-3-line', 'bg-info-50 text-info-600', false],
        'support_message' => ['ri-customer-service-2-line', 'bg-success-50 text-success-600', false],
        'review_approved' => ['ri-star-smile-line', 'bg-success-50 text-success-600', false],
    ];
@endphp

<x-dynamic-component :component="$notificationLayout" title="Уведомления">
    <div class="notifications-mobile-safe min-h-screen min-w-0 overflow-x-hidden bg-white pb-24 text-neutral-800 md:pb-0">
        <header class="border-b border-neutral-200 bg-white">
            <div class="flex w-full flex-col gap-5 px-4 py-6 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <div class="min-w-0">
                    <div class="mb-2 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                        <i class="ri-notification-3-line text-base" aria-hidden="true"></i>
                        Центр действий
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight text-neutral-900 sm:text-[28px]">Уведомления</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-neutral-500">Заказы, чаты, отзывы, поддержка и важные системные события.</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <x-secondary-action as="a" :href="route('notifications.settings')">
                        <i class="ri-settings-3-line" aria-hidden="true"></i>
                        Настройки
                    </x-secondary-action>
                    <form method="POST" action="{{ route('notifications.readAll') }}">
                        @csrf
                        <x-action-button type="submit" :disabled="$unreadCount === 0" full>
                            <i class="ri-check-double-line" aria-hidden="true"></i>
                            Всё прочитано
                        </x-action-button>
                    </form>
                </div>
            </div>
        </header>

        <main class="w-full space-y-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8 lg:py-12">
            <section class="grid overflow-hidden rounded-2xl border border-brand-100 bg-brand-50/50 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="flex flex-col justify-center p-5 sm:p-6 lg:p-7">
                    <div class="relative flex h-11 w-11 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm ring-1 ring-brand-100">
                        <i class="ri-notification-4-line text-xl" aria-hidden="true"></i>
                        @if($unreadCount > 0)<span class="absolute -right-1.5 -top-1.5 flex h-6 min-w-6 items-center justify-center rounded-full border-2 border-brand-50 bg-danger-500 px-1 text-[10px] font-bold text-white">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>@endif
                    </div>
                    <h2 class="mt-4 text-xl font-semibold tracking-tight text-neutral-900 sm:text-2xl">Всё важное — в одном месте</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-600">Открывайте уведомление, чтобы перейти к связанному заказу, сообщению или действию.</p>
                </div>
                <div class="grid grid-cols-2 border-t border-brand-100 bg-white/60 lg:border-l lg:border-t-0">
                    <div class="flex flex-col justify-center border-r border-brand-100 p-5 sm:p-6">
                        <span class="text-3xl font-semibold tracking-tight text-neutral-900">{{ $totalCount }}</span>
                        <span class="mt-2 text-sm font-medium text-neutral-500">Всего событий</span>
                    </div>
                    <div class="flex flex-col justify-center p-5 sm:p-6">
                        <span class="text-3xl font-semibold tracking-tight {{ $unreadCount > 0 ? 'text-brand-600' : 'text-success-600' }}">{{ $unreadCount }}</span>
                        <span class="mt-2 text-sm font-medium text-neutral-500">Непрочитанных</span>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="flex items-center gap-3 rounded-2xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700" role="status"><i class="ri-checkbox-circle-fill text-lg"></i><span>{{ session('success') }}</span></div>
            @endif

            <div class="flex flex-wrap items-end justify-between gap-3 border-b border-neutral-200 pb-4">
                <div>
                    <h2 class="text-lg font-semibold text-neutral-900">Последние события</h2>
                    <p class="mt-1 text-sm text-neutral-500">Нажмите на уведомление, чтобы отметить его прочитанным и открыть детали.</p>
                </div>
                @if($unreadCount > 0)
                    <span class="inline-flex items-center gap-2 rounded-full bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700"><span class="h-2 w-2 rounded-full bg-brand-500"></span>{{ $unreadCount }} новых</span>
                @endif
            </div>

            <section class="space-y-3" aria-label="Список уведомлений">
                @forelse($notifications as $notification)
                    @php
                        [$icon, $tone, $isModerationAction] = $notificationMeta[$notification->type] ?? ['ri-notification-3-line', 'bg-brand-50 text-brand-600', false];
                        $isUnread = !$notification->read_at;
                    @endphp
                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                        @csrf
                        <button type="submit" class="group grid w-full min-w-0 gap-4 rounded-2xl border p-4 text-left transition duration-200 sm:grid-cols-[48px_minmax(0,1fr)_auto] sm:items-center sm:p-5 {{ $isUnread ? ($isModerationAction ? 'border-danger-200 bg-danger-50/30 hover:bg-danger-50/60' : 'border-brand-200 bg-brand-50/30 hover:bg-brand-50/60') : 'border-neutral-200 bg-white hover:border-brand-200 hover:bg-neutral-50' }}">
                            <span class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $tone }}">
                                <i class="{{ $icon }} text-xl" aria-hidden="true"></i>
                                @if($isUnread)<span class="absolute -right-1 -top-1 h-3 w-3 rounded-full border-2 border-white {{ $isModerationAction ? 'bg-danger-500' : 'bg-brand-500' }}"></span>@endif
                            </span>

                            <span class="min-w-0">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="break-words text-sm {{ $isUnread ? 'font-semibold text-neutral-950' : 'font-medium text-neutral-800' }} sm:text-base">{{ $notification->title }}</span>
                                    @if($isUnread)<span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $isModerationAction ? 'text-danger-600 ring-1 ring-danger-100' : 'text-brand-600 ring-1 ring-brand-100' }}">Новое</span>@endif
                                </span>
                                @if($notification->body)<span class="mt-1 block break-words text-sm leading-6 text-neutral-500">{{ $notification->body }}</span>@endif
                                @if($isModerationAction && $notification->url)<span class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-danger-700">Открыть и исправить <i class="ri-arrow-right-line"></i></span>@endif
                            </span>

                            <span class="flex items-center justify-between gap-3 sm:flex-col sm:items-end">
                                <span class="whitespace-nowrap text-xs font-medium text-neutral-400">{{ $notification->created_at->diffForHumans() }}</span>
                                <span class="flex h-8 w-8 items-center justify-center rounded-full text-neutral-400 transition group-hover:bg-white group-hover:text-brand-600"><i class="ri-arrow-right-s-line text-xl"></i></span>
                            </span>
                        </button>
                    </form>
                @empty
                    <div class="rounded-3xl border border-dashed border-neutral-300 bg-neutral-50/60 px-6 py-16 text-center sm:py-20">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-600 ring-8 ring-white"><i class="ri-notification-off-line text-3xl"></i></div>
                        <h2 class="mt-6 text-xl font-semibold text-neutral-900">Уведомлений пока нет</h2>
                        <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-neutral-500">Когда появятся события по заказам, чатам или отзывам, они будут собраны здесь.</p>
                        <div class="mt-7 flex justify-center"><x-secondary-action as="a" :href="route('notifications.settings')"><i class="ri-settings-3-line"></i>Настроить уведомления</x-secondary-action></div>
                    </div>
                @endforelse
            </section>

            @if($notifications->hasPages())
                <div class="border-t border-neutral-200 pt-6">{{ $notifications->links() }}</div>
            @endif
        </main>
    </div>

    @unless($isSeller)
        @include('layouts.mobile-bottom-nav')
    @endunless

    <style>
        .notifications-mobile-safe, .notifications-mobile-safe * { box-sizing: border-box; }
        .notifications-mobile-safe { max-width: 100vw; }
    </style>
</x-dynamic-component>
