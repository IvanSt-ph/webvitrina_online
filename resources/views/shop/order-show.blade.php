<x-buyer-layout title="Мой заказ #{{ $order->number }}">

  <!-- 🔙 Навигация -->
  <div class="flex items-center justify-between mb-6">
    <a href="{{ route('orders.index') }}" class="text-sm text-gray-600 hover:text-indigo-600 flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Назад к заказам
    </a>
    <span class="text-xs text-gray-400">
      Создан: {{ $order->created_at->format('d.m.Y H:i') }}
    </span>
  </div>

  <!-- 🧾 Карточка заказа -->
  <div class="bg-white shadow-sm border border-gray-100 rounded-2xl overflow-hidden">

    <!-- Заголовок -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between px-6 py-5 border-b border-gray-100 bg-gray-50">
      <div>
        <h1 class="text-xl font-semibold text-gray-900">Заказ {{ $order->number }}</h1>
        <p class="text-sm text-gray-500 mt-1">
          Сумма:
          <span class="font-medium text-gray-800">
            {{ number_format($order->total_price, 2, ',', ' ') }} ₽
          </span>
        </p>
      </div>

      @php
        $statuses = [
          'pending' => ['🕓 Ожидает оплаты', 'bg-yellow-100 text-yellow-800'],
          'paid' => ['💳 Оплачен', 'bg-green-100 text-green-800'],
          'shipped' => ['📦 Отправлен', 'bg-blue-100 text-blue-800'],
          'completed' => ['✅ Завершён', 'bg-gray-100 text-gray-700'],
          'canceled' => ['❌ Отменён', 'bg-red-100 text-red-800'],
        ];
        [$label, $color] = $statuses[$order->status] ?? ['Неизвестно', 'bg-gray-100 text-gray-700'];
      @endphp
      <span class="mt-3 sm:mt-0 inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium {{ $color }}">
        {{ $label }}
      </span>
    </div>

    <!-- Товары -->
    <div class="divide-y divide-gray-100">
      @foreach($order->items as $item)
        <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="flex items-center gap-4 flex-1">
            <img src="{{ asset('storage/'.$item->product->image) }}"
                 alt="{{ $item->product->title }}"
                 class="w-20 h-20 object-cover rounded-xl border border-gray-100">
            <div>
              <p class="font-medium text-gray-800">{{ $item->product->title ?? 'Товар удалён' }}</p>
              <p class="text-sm text-gray-500 mt-0.5">
                Цена: {{ number_format($item->price, 2, ',', ' ') }} ₽  
                <span class="mx-1">•</span> Кол-во: {{ $item->quantity }}
              </p>
            </div>
          </div>
          <div class="text-right">
            <p class="font-semibold text-gray-900 text-sm">
              {{ number_format($item->total, 2, ',', ' ') }} ₽
            </p>
          </div>
        </div>
      @endforeach
    </div>

    <!-- 📍 Адрес и итоги -->
    <div class="px-6 py-5 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row sm:justify-between sm:items-start gap-6">
      
      <!-- Адрес -->
      <div class="text-sm text-gray-700 flex-1">
        <p class="font-medium text-gray-800 mb-1">Адрес доставки:</p>
        @if($order->address)
          <p>
            {{ $order->address->country }}, г. {{ $order->address->city }}<br>
            {{ $order->address->street }} д. {{ $order->address->house }},
            кв. {{ $order->address->apartment }}
          </p>
          @if($order->address->comment)
            <p class="text-xs text-gray-500 mt-1">💬 {{ $order->address->comment }}</p>
          @endif
        @else
          <p class="text-gray-400">Адрес не указан</p>
        @endif
      </div>

      <!-- Итого -->
      <div class="text-right">
        <p class="text-gray-500 text-sm">Итого к оплате:</p>
        <p class="text-2xl font-semibold text-gray-900">
          {{ number_format($order->total_price, 2, ',', ' ') }} ₽
        </p>
      </div>
    </div>
  </div>

  <!-- Кнопки действий -->
  <div class="flex flex-wrap gap-3 mt-8">
    <a href="{{ route('orders.index') }}"
       class="inline-flex items-center gap-1 px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 transition">
      <i class="ri-arrow-left-line"></i> Назад к заказам
    </a>
    <a href="#"
       class="inline-flex items-center gap-1 px-5 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
      <i class="ri-shopping-bag-3-line"></i> Повторить заказ
    </a>
    <a href="#"
       class="inline-flex items-center gap-1 px-5 py-2.5 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
      <i class="ri-file-download-line"></i> Скачать чек (PDF)
    </a>
    <a href="#"
       class="inline-flex items-center gap-1 px-5 py-2.5 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
      <i class="ri-star-line"></i> Оставить отзыв
    </a>
  </div>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
</x-buyer-layout>
