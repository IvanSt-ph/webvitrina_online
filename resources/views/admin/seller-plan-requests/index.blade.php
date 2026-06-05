@extends('admin.layout')

@section('title', 'Заявки на уровень магазина')

@section('content')
    <div class="space-y-5">
        <header class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-950">Заявки на изменение уровня магазина</h1>
                <p class="mt-1 text-sm text-slate-500">Повышение и понижение проверяются вручную: при понижении каталог должен помещаться в новый лимит.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach(['pending' => 'Ожидают', 'approved' => 'Одобрено', 'rejected' => 'Отклонено'] as $key => $label)
                    <a href="{{ route('admin.seller-plan-requests.index', ['status' => $key]) }}"
                       class="rounded-lg border px-3 py-2 text-sm font-semibold transition {{ $status === $key ? 'border-indigo-200 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                        {{ $label }}
                        <span class="ml-1 rounded-full bg-white px-2 py-0.5 text-xs">{{ $counts[$key] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>
        </header>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>
        @endif

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="divide-y divide-slate-100">
                @forelse($requests as $item)
                    @php($context = $requestContext[$item->id] ?? ['profile' => null, 'assignable' => false, 'target_limit' => '—'])
                    <div class="grid gap-4 p-4 xl:grid-cols-[1fr_360px] xl:items-center">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.users.show', $item->user) }}" class="font-bold text-slate-950 hover:text-indigo-700">
                                    {{ $item->user?->name ?? 'Пользователь удалён' }}
                                </a>
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">ID {{ $item->user_id }}</span>
                                <span class="rounded-full px-2 py-1 text-xs font-bold {{ $item->status === 'approved' ? 'bg-emerald-50 text-emerald-700' : ($item->status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700') }}">
                                    {{ ['pending' => 'Ожидает', 'approved' => 'Одобрено', 'rejected' => 'Отклонено'][$item->status] ?? $item->status }}
                                </span>
                            </div>
                            <div class="mt-2 text-sm text-slate-600">
                                {{ $plans[$item->current_plan]['label'] ?? $item->current_plan }} -> <span class="font-bold text-indigo-700">{{ $plans[$item->requested_plan]['label'] ?? $item->requested_plan }}</span>
                            </div>
                            @if($context['profile'])
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-600">
                                        Каталог: {{ $context['profile']['used'] }} товаров
                                    </span>
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 font-semibold text-indigo-700">
                                        Новый лимит: {{ $context['target_limit'] }}
                                    </span>
                                    <span class="rounded-full px-2.5 py-1 font-semibold {{ $context['assignable'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        {{ $context['assignable'] ? 'Можно назначить' : 'Лимит не подходит' }}
                                    </span>
                                </div>
                            @endif
                            @if($item->message)
                                <p class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">{{ $item->message }}</p>
                            @endif
                            <div class="mt-2 text-xs text-slate-400">
                                Создана {{ $item->created_at->format('d.m.Y H:i') }}
                                @if($item->reviewed_at)
                                    · обработал {{ $item->reviewer?->name ?? 'администратор' }} {{ $item->reviewed_at->format('d.m.Y H:i') }}
                                @endif
                            </div>
                        </div>

                        @if($item->isPending())
                            <div class="grid gap-2 sm:grid-cols-2">
                                <form method="POST" action="{{ route('admin.seller-plan-requests.approve', $item) }}" class="space-y-2">
                                    @csrf
                                    <input name="admin_note" placeholder="Заметка" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm">
                                    <button @disabled(! $context['assignable']) class="h-10 w-full rounded-lg bg-indigo-600 text-sm font-bold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300">Одобрить</button>
                                </form>
                                <form method="POST" action="{{ route('admin.seller-plan-requests.reject', $item) }}" class="space-y-2">
                                    @csrf
                                    <input name="admin_note" placeholder="Причина отказа" class="h-10 w-full rounded-lg border border-slate-200 px-3 text-sm">
                                    <button class="h-10 w-full rounded-lg border border-rose-200 bg-rose-50 text-sm font-bold text-rose-700 hover:bg-rose-100">Отклонить</button>
                                </form>
                            </div>
                        @else
                            <div class="rounded-lg bg-slate-50 p-3 text-sm text-slate-500">
                                {{ $item->admin_note ?: 'Без заметки администратора.' }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="px-6 py-12 text-center text-sm text-slate-500">Заявок в этом статусе нет.</div>
                @endforelse
            </div>

            @if($requests->hasPages())
                <div class="border-t border-slate-100 p-4">{{ $requests->links() }}</div>
            @endif
        </section>
    </div>
@endsection
