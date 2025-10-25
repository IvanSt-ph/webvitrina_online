<x-app-layout title="Мои заказы">

  <div class="max-w-5xl mx-auto px-4 mt-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 mb-2">🧾 Мои заказы</h1>
    <p class="text-gray-500 mb-8">Здесь собраны все ваши покупки — от новых до доставленных.</p>

    @forelse($orders as $order)
      <div class="bg-white shadow-sm hover:shadow-md transition rounded-2xl p-5 mb-6 border border-gray-100">

        {{-- Верхняя строка --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
          <div>
            <div class="font-medium text-gray-800">
              Заказ <span class="font-semibold text-indigo-600">#{{ $order->id }}</span>
            </div>
            <div class="text-sm text-gray-500">
              от {{ $order->created_at->format('d.m.Y H:i') }}
            </div>
          </div>

          {{-- Статус + сумма --}}
          <div class="flex items-center gap-3">
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
            <span class="text-lg font-semibold text-gray-900">
              {{ number_format($order->total_price, 2, ',', ' ') }} ₽
            </span>
          </div>
        </div>

        {{-- Товары в заказе --}}
        <div class="divide-y divide-gray-100">
          @foreach($order->items as $item)
            <div class="flex items-center gap-4 py-4">
              <img src="{{ asset('storage/'.$item->product->image) }}"
                   alt="{{ $item->product->title }}"
                   class="w-20 h-20 object-cover rounded-lg border border-gray-100" />

              <div class="flex-1">
                <div class="font-medium text-gray-800">{{ $item->product->title }}</div>
                <div class="text-sm text-gray-500">
                  Кол-во: <span class="text-gray-700 font-medium">{{ $item->quantity }}</span>
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

        {{-- Нижняя панель --}}
        <div class="flex justify-end items-center gap-3 mt-5">
          <a href="{{ route('orders.show', $order->id ?? 0) }}"
             class="px-4 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:text-indigo-600 hover:border-indigo-400 transition">
            Подробнее
          </a>
          <a href="#"
             class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 transition">
            Купить снова
          </a>
        </div>
      </div>
    @empty
      <div class="text-center py-20">
        <div class="text-6xl mb-3">🛍️</div>
        <div class="text-gray-700 text-lg font-medium">У вас пока нет заказов</div>
        <a href="{{ route('shop.index') }}"
           class="mt-5 inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
          Перейти к покупкам
        </a>
      </div>
    @endforelse
  </div>

</x-app-layout>
