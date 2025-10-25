<x-app-layout :title="'Заказ #' . $order->id">

  <div class="max-w-4xl mx-auto px-4 py-8">
    <a href="{{ route('orders.index') }}"
       class="inline-flex items-center text-sm text-gray-500 hover:text-indigo-600 mb-5">
      <i class="ri-arrow-left-line mr-1"></i> К списку заказов
    </a>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
      <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6">
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">Заказ #{{ $order->id }}</h1>
          <p class="text-gray-500 text-sm">от {{ $order->created_at->format('d.m.Y H:i') }}</p>
        </div>
        @php
          $statuses = [
            'pending' => ['🕓 Ожидает', 'bg-yellow-100 text-yellow-800'],
            'paid' => ['💳 Оплачен', 'bg-green-100 text-green-800'],
            'shipped' => ['📦 Отправлен', 'bg-blue-100 text-blue-800'],
            'completed' => ['✅ Завершён', 'bg-gray-100 text-gray-700'],
            'canceled' => ['❌ Отменён', 'bg-red-100 text-red-800'],
          ];
          [$label, $color] = $statuses[$order->status] ?? ['Неизвестно', 'bg-gray-100 text-gray-700'];
        @endphp
        <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $color }}">
          {{ $label }}
        </span>
      </div>

      {{-- Товары --}}
      <div class="divide-y divide-gray-100">
        @foreach($order->items as $item)
          <div class="flex items-center gap-4 py-4">
            <img src="{{ asset('storage/'.$item->product->image) }}"
                 alt="{{ $item->product->title }}"
                 class="w-20 h-20 object-cover rounded-lg border border-gray-100" />
            <div class="flex-1">
              <div class="font-medium text-gray-800">{{ $item->product->title }}</div>
              <div class="text-sm text-gray-500">
                Кол-во: <span class="font-medium text-gray-700">{{ $item->quantity }}</span>
              </div>
            </div>
            <div class="text-right">
              <div class="text-gray-800 font-semibold">
                {{ number_format($item->price, 2, ',', ' ') }} ₽
              </div>
              <div class="text-sm text-gray-500">
                Всего: {{ number_format($item->total, 2, ',', ' ') }} ₽
              </div>
            </div>
          </div>
        @endforeach
      </div>

      {{-- Итого --}}
      <div class="mt-6 border-t pt-4 flex justify-between items-center">
        <span class="text-gray-700 font-medium">Итого к оплате:</span>
        <span class="text-xl font-semibold text-gray-900">
          {{ number_format($order->total_price, 2, ',', ' ') }} ₽
        </span>
      </div>
    </div>
  </div>

</x-app-layout>
