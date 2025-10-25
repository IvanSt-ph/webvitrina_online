<x-buyer-layout title="Оформление заказа">

  <div class="max-w-4xl mx-auto px-4 py-10 space-y-6">

    <h1 class="text-2xl font-semibold text-gray-800 mb-6">Оформление заказа</h1>

    @if(empty($cart))
      <div class="text-center py-16">
        <p class="text-gray-600 text-lg">Ваша корзина пуста 😕</p>
        <a href="{{ route('shop.index') }}"
           class="mt-5 inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
          Перейти в каталог
        </a>
      </div>
    @else
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm divide-y">
        @foreach($cart as $item)
          <div class="p-5 flex items-center gap-4">
            <img src="{{ asset('storage/'.$item['image']) }}" class="w-20 h-20 rounded-lg border object-cover" />
            <div class="flex-1">
              <p class="font-medium text-gray-800">{{ $item['title'] }}</p>
              <p class="text-gray-500 text-sm">
                Кол-во: {{ $item['quantity'] }}
              </p>
            </div>
            <p class="font-semibold text-gray-900 text-lg">
              {{ number_format($item['price'] * $item['quantity'], 2, ',', ' ') }} ₽
            </p>
          </div>
        @endforeach
      </div>

      <div class="flex justify-between items-center mt-8">
        <div class="text-gray-700 font-medium">
          Итого: 
          <span class="text-xl font-semibold text-gray-900">
            {{ number_format(collect($cart)->sum(fn($i) => $i['price'] * $i['quantity']), 2, ',', ' ') }} ₽
          </span>
        </div>

        <form method="POST" action="#">
          @csrf
          <button class="px-6 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition">
            Подтвердить заказ
          </button>
        </form>
      </div>
    @endif
  </div>

</x-buyer-layout>
