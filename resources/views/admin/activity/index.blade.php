@extends('admin.layout')

@section('title', 'Журнал действий')

@section('content')
    <div class="space-y-5">
        <header class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-950">Журнал действий администратора</h1>
            <p class="mt-1 text-sm text-slate-500">Кто менял тарифы, пользователей, чаты и другие чувствительные данные.</p>

            <form method="GET" class="mt-4 flex flex-col gap-2 sm:flex-row">
                <label class="relative flex-1">
                    <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Действие, описание, администратор"
                           class="h-11 w-full rounded-lg border border-slate-200 pl-10 pr-3 text-sm outline-none focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                </label>
                <button class="h-11 rounded-lg bg-indigo-600 px-5 text-sm font-bold text-white hover:bg-indigo-700">Найти</button>
            </form>
        </header>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse($logs as $log)
                    <article class="grid gap-3 p-4 lg:grid-cols-[220px_1fr_190px] lg:items-start">
                        <div>
                            <div class="font-mono text-xs font-bold text-indigo-700">{{ $log->action }}</div>
                            <div class="mt-1 text-xs text-slate-400">{{ $log->created_at->format('d.m.Y H:i:s') }}</div>
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-semibold text-slate-900">{{ $log->description ?: 'Без описания' }}</div>
                            @if($log->meta)
                                <pre class="mt-2 max-h-36 overflow-auto rounded-lg bg-slate-50 p-3 text-xs text-slate-600">{{ json_encode($log->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
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
