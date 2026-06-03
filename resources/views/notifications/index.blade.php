@php
    $notificationLayout = auth()->user()->isSeller() ? 'seller-layout' : 'buyer-layout';
@endphp

<x-dynamic-component :component="$notificationLayout" title="Уведомления">
    <div class="w-full max-w-none space-y-5 bg-white px-3 py-4 pb-24 sm:px-6 sm:py-8">
        <header class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-indigo-600">
                    <i class="ri-notification-3-line"></i>
                    Центр действий
                </span>
                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Уведомления</h1>
                <p class="mt-2 text-sm text-slate-500">Заказы, чаты, отзывы, поддержка и важные системные события.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('notifications.settings') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    <i class="ri-settings-3-line"></i>
                    Настройки
                </a>
                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf
                    <button class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                        <i class="ri-check-double-line"></i>
                        Всё прочитано
                    </button>
                </form>
            </div>
        </header>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse($notifications as $notification)
                    @php
                        $isModerationAction = in_array($notification->type, ['product_hidden_by_report', 'review_rejected'], true);
                    @endphp
                    <form method="POST" action="{{ route('notifications.read', $notification) }}" class="block">
                        @csrf
                        <button class="grid w-full gap-2 px-4 py-4 text-left transition hover:bg-slate-50 sm:grid-cols-[1fr_auto] sm:items-center {{ $isModerationAction && !$notification->read_at ? 'bg-rose-50/60' : '' }}">
                            <span class="min-w-0">
                                <span class="flex items-center gap-2">
                                    @if(!$notification->read_at)
                                        <span class="h-2 w-2 shrink-0 rounded-full {{ $isModerationAction ? 'bg-rose-600' : 'bg-indigo-600' }}"></span>
                                    @endif
                                    @if($isModerationAction)
                                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                                            <i class="ri-alarm-warning-line"></i>
                                        </span>
                                    @endif
                                    <span class="font-semibold text-slate-900">{{ $notification->title }}</span>
                                </span>
                                @if($notification->body)
                                    <span class="mt-1 block text-sm text-slate-500">{{ $notification->body }}</span>
                                @endif
                                @if($isModerationAction && $notification->url)
                                    <span class="mt-2 inline-flex text-xs font-bold text-rose-700">Открыть и исправить</span>
                                @endif
                            </span>
                            <span class="text-xs font-medium text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                        </button>
                    </form>
                @empty
                    <x-empty-state
                        icon="ri-notification-off-line"
                        title="Уведомлений пока нет"
                        description="Когда появятся события по заказам, чатам или отзывам, они будут собраны здесь."
                        class="border-0 shadow-none rounded-none"
                    />
                @endforelse
            </div>
        </section>

        {{ $notifications->links() }}
    </div>
</x-dynamic-component>
