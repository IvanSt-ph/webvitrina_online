<x-seller-layout title="Финансы продавца">
  <div class="min-h-screen w-full space-y-5 bg-white px-3 py-4 pb-28 text-neutral-900 sm:space-y-6 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600"><i class="ri-wallet-3-line"></i>Финансы</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[28px]">Деньги по заказам</h1>
        <p class="mt-1 max-w-2xl text-sm leading-6 text-neutral-500">
          Операционные суммы заказов магазина. Это не платёжный баланс и не выплата на карту.
        </p>
      </div>
        <x-action-button as="a" :href="route('seller.orders.index')" class="shrink-0">
          <i class="ri-shopping-bag-3-line"></i>
          К заказам
        </x-action-button>
    </header>

    <section class="grid gap-3 sm:grid-cols-3">
      @foreach([
        ['label' => 'Завершено / доставлено', 'value' => $completedTotal, 'icon' => 'ri-check-double-line', 'tone' => 'bg-emerald-50 text-emerald-700'],
        ['label' => 'В работе', 'value' => $inProgressTotal, 'icon' => 'ri-time-line', 'tone' => 'bg-amber-50 text-amber-700'],
        ['label' => 'Отменено', 'value' => $canceledTotal, 'icon' => 'ri-close-circle-line', 'tone' => 'bg-rose-50 text-rose-700'],
      ] as $card)
        <div class="rounded-2xl border border-neutral-200 bg-white p-4 sm:p-5">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-neutral-500">{{ $card['label'] }}</span>
            <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $card['tone'] }}"><i class="{{ $card['icon'] }} text-xl"></i></span>
          </div>
          <div class="mt-3 break-words text-xl font-bold tracking-tight text-neutral-950 sm:text-2xl">{{ number_format($card['value'], 2, ',', ' ') }} {{ $currency }}</div>
        </div>
      @endforeach
    </section>

    <section class="overflow-hidden rounded-2xl border border-neutral-200 bg-white">
      <div class="flex items-center justify-between gap-3 border-b border-neutral-100 px-4 py-4 sm:px-5">
        <div>
          <h2 class="font-semibold text-neutral-950">Последние заказы</h2>
          <p class="mt-1 text-xs text-neutral-500">Операционные суммы заказов, не подтверждение оплаты.</p>
        </div>
      </div>
      <div class="divide-y divide-neutral-100">
        @forelse($recentOrders as $order)
          <a href="{{ route('seller.orders.show', $order) }}" class="group grid gap-3 px-4 py-4 transition hover:bg-brand-50/40 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center sm:px-5">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-semibold text-neutral-900">#{{ $order->number }}</span>
                <x-status-badge :status="$order->status" :order="$order" />
              </div>
              <p class="mt-1 truncate text-sm text-neutral-500">{{ $order->buyer_name }} · {{ $order->created_at?->format('d.m.Y H:i') }}</p>
            </div>
            <div class="text-sm font-bold text-neutral-950">{{ $order->formatted_total_price }}</div>
            <i class="ri-arrow-right-s-line hidden text-xl text-neutral-300 transition group-hover:text-brand-500 sm:block"></i>
          </a>
        @empty
          <x-empty-state
            icon="ri-wallet-3-line"
            title="Финансовых событий пока нет"
            description="Когда появятся заказы, здесь будет видна сумма в работе, завершённые и отменённые заказы."
            class="rounded-none border-0 shadow-none"
          >
            <a href="{{ route('seller.products.create') }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">
              Добавить товар
            </a>
          </x-empty-state>
        @endforelse
      </div>
    </section>
  </div>

  @include('layouts.mobile-bottom-seller-nav')
</x-seller-layout>
