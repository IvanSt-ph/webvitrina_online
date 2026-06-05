<x-seller-layout title="Финансы продавца">
  <div class="space-y-5 px-3 py-4 pb-24 sm:px-5 sm:py-6 lg:px-6">
    <section class="rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm sm:p-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <p class="text-xs font-bold uppercase tracking-wide text-indigo-600">Финансы</p>
          <h1 class="mt-1 text-2xl font-bold text-slate-950">Деньги по заказам</h1>
          <p class="mt-2 max-w-2xl text-sm text-slate-500">
            Это не платёжный баланс и не выплата на карту: здесь показана операционная сумма заказов продавца.
          </p>
        </div>
        <a href="{{ route('seller.orders.index') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700">
          <i class="ri-shopping-bag-3-line"></i>
          К заказам
        </a>
      </div>
    </section>

    <section class="grid gap-3 md:grid-cols-3">
      @foreach([
        ['label' => 'Завершено / доставлено', 'value' => $completedTotal, 'icon' => 'ri-check-double-line', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-800'],
        ['label' => 'В работе', 'value' => $inProgressTotal, 'icon' => 'ri-time-line', 'class' => 'border-amber-200 bg-amber-50 text-amber-800'],
        ['label' => 'Отменено', 'value' => $canceledTotal, 'icon' => 'ri-close-circle-line', 'class' => 'border-rose-200 bg-rose-50 text-rose-800'],
      ] as $card)
        <div class="rounded-2xl border p-5 shadow-sm {{ $card['class'] }}">
          <div class="flex items-center justify-between">
            <span class="text-sm font-semibold opacity-80">{{ $card['label'] }}</span>
            <i class="{{ $card['icon'] }} text-xl"></i>
          </div>
          <div class="mt-3 text-2xl font-extrabold">{{ number_format($card['value'], 2, ',', ' ') }} {{ $currency }}</div>
        </div>
      @endforeach
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-5">
        <div>
          <h2 class="font-bold text-slate-950">Последние финансовые события</h2>
          <p class="mt-1 text-xs text-slate-500">Заказы, из которых складывается сумма выше.</p>
        </div>
      </div>
      <div class="divide-y divide-slate-100">
        @forelse($recentOrders as $order)
          <a href="{{ route('seller.orders.show', $order) }}" class="grid gap-2 px-4 py-3 transition hover:bg-slate-50 sm:grid-cols-[1fr_auto] sm:items-center sm:px-5">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-semibold text-slate-900">#{{ $order->number }}</span>
                <x-status-badge :status="$order->status" />
              </div>
              <p class="mt-1 truncate text-sm text-slate-500">{{ $order->user?->name ?? 'Покупатель' }} · {{ $order->created_at?->format('d.m.Y H:i') }}</p>
            </div>
            <div class="text-sm font-bold text-slate-900">{{ $order->formatted_total_price }}</div>
          </a>
        @empty
          <x-empty-state
            icon="ri-wallet-3-line"
            title="Финансовых событий пока нет"
            description="Когда появятся заказы, здесь будет видна сумма в работе, завершённые и отменённые заказы."
            class="border-0 shadow-none rounded-none"
          >
            <a href="{{ route('seller.products.create') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
              Добавить товар
            </a>
          </x-empty-state>
        @endforelse
      </div>
    </section>
  </div>

  @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
