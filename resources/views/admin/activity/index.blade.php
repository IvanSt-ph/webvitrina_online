@extends('admin.layout')

@section('title', 'Журнал действий')

@section('content')
    @php
        $actionLabels = [
            'order.status_updated' => ['Статус заказа', 'ri-shopping-bag-3-line'],
            'user.updated' => ['Пользователь изменён', 'ri-user-settings-line'],
            'user.created' => ['Пользователь создан', 'ri-user-add-line'],
            'user.deleted' => ['Пользователь удалён', 'ri-user-unfollow-line'],
            'chat.locked' => ['Чат заблокирован', 'ri-lock-line'],
            'chat.unlocked' => ['Чат разблокирован', 'ri-lock-unlock-line'],
            'chat.hidden' => ['Чат скрыт', 'ri-eye-off-line'],
            'seller_plan_request.approved' => ['Уровень магазина одобрен', 'ri-vip-crown-line'],
            'seller_plan_request.rejected' => ['Уровень магазина отклонён', 'ri-close-circle-line'],
            'profile.updated' => ['Профиль админа', 'ri-settings-3-line'],
            'review.approved' => ['Отзыв одобрен', 'ri-chat-check-line'],
            'review.rejected' => ['Отзыв отклонён', 'ri-chat-delete-line'],
            'review.deleted' => ['Отзыв удалён', 'ri-delete-bin-line'],
            'review.bulk_approve' => ['Отзывы одобрены', 'ri-chat-check-line'],
            'review.bulk_reject' => ['Отзывы отклонены', 'ri-chat-delete-line'],
            'review.bulk_deleted' => ['Отзывы удалены', 'ri-delete-bin-line'],
        ];
    @endphp
    <div class="space-y-5">
        <header class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-950">Журнал действий администратора</h1>
            <p class="mt-1 text-sm text-slate-500">Кто менял уровни магазинов, пользователей, чаты и другие чувствительные данные.</p>

            <form method="GET" class="mt-4 grid gap-2 lg:grid-cols-[minmax(220px,1fr)_210px_180px_150px_150px_auto]">
                <label class="relative flex-1">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Действие, описание, администратор"
                           class="h-11 w-full rounded-lg border border-slate-200 pl-10 pr-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                </label>
                <select name="action" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Все действия</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
                <select name="admin_id" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                    <option value="">Все админы</option>
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" @selected((string) request('admin_id') === (string) $admin->id)>{{ $admin->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="h-11 rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                <button class="h-11 rounded-lg bg-indigo-600 px-5 text-sm font-bold text-white hover:bg-indigo-700">Найти</button>
            </form>
            @if(request()->hasAny(['q', 'action', 'admin_id', 'date_from', 'date_to']))
                <a href="{{ route('admin.activity.index') }}" class="mt-3 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-800">Сбросить фильтры</a>
            @endif
        </header>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse($logs as $log)
                    @php($actionMeta = $actionLabels[$log->action] ?? [$log->action, 'ri-history-line'])
                    <article class="grid gap-3 p-4 lg:grid-cols-[220px_1fr_190px] lg:items-start">
                        <div>
                            <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-700">
                                <i class="{{ $actionMeta[1] }}"></i>{{ $actionMeta[0] }}
                            </div>
                            <div class="mt-1 text-xs text-slate-400">{{ $log->created_at->format('d.m.Y H:i:s') }}</div>
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-900">{{ $log->description ?: 'Без описания' }}</div>
                            @if($log->meta)
                                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                    @foreach($log->meta as $key => $value)
                                        @if(! is_array($value) && filled($value))
                                            <span class="rounded-lg bg-slate-50 px-2.5 py-1 text-slate-600">
                                                <span class="font-semibold">{{ ['from' => 'Было', 'to' => 'Стало', 'reason' => 'Причина', 'user_id' => 'User ID', 'requested_plan' => 'Уровень магазина', 'review_id' => 'Review ID', 'count' => 'Количество'][$key] ?? $key }}:</span>
                                                {{ $value }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="text-sm text-slate-500 lg:text-right">
                            <div class="font-semibold text-slate-700">{{ $log->admin?->name ?? 'Система' }}</div>
                            <div class="mt-1 text-xs">{{ $log->ip }}</div>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-12 text-center text-sm text-slate-500">Записей пока нет.</div>
                @endforelse
            </div>

            @if($logs->hasPages())
                <div class="border-t border-slate-100 p-4">{{ $logs->links() }}</div>
            @endif
        </section>
    </div>
@endsection
