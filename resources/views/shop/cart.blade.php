{{-- resources/views/shop/cart.blade.php --}}
<x-buyer-layout title="Моя корзина">

  <div class="space-y-8">

    <!-- 🔝 Заголовок -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">🛒 Моя корзина</h1>
        <p class="text-gray-500 text-sm mt-1">
          Проверьте выбранные товары, измените количество или оформите заказ.
        </p>
      </div>

      @if($items->isNotEmpty())
        <form method="POST" action="{{ route('checkout') }}">
          @csrf
          <button
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            <i class="ri-check-double-line text-lg"></i> Перейти к оформлению
          </button>
        </form>
      @endif
    </div>

    @if($items->isEmpty())
      <!-- 🕊 Пустая корзина -->
      <div class="text-center py-24">
        <div class="text-6xl mb-3">🛍️</div>
        <p class="text-lg font-medium text-gray-700">Ваша корзина пуста</p>
        <p class="text-sm text-gray-500 mt-1">Добавьте товары, чтобы оформить заказ.</p>
        <a href="{{ route('home') }}"
           class="mt-6 inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
          Перейти в каталог
        </a>
      </div>
    @else
      <!-- 📦 Товары в корзине -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @foreach($items as $i)
          @php $p = $i->product; @endphp
          <div
            class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg transition-all duration-300 group flex flex-col overflow-hidden">

            <!-- Фото -->
            <a href="{{ route('product.show', $p) }}" class="relative aspect-square bg-gray-50 overflow-hidden">
              @if($p->image)
                <img src="{{ asset('storage/'.$p->image) }}"
                     alt="{{ $p->title }}"
                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
              @else
                <div class="flex items-center justify-center h-full text-gray-300 text-3xl">📦</div>
              @endif
            </a>

            <!-- Информация -->
            <div class="flex-1 flex flex-col p-4">
              <a href="{{ route('product.show', $p) }}"
                 class="text-sm font-medium text-gray-800 hover:text-indigo-600 line-clamp-2 min-h-[40px] mb-2">
                {{ $p->title }}
              </a>

              <div class="flex items-center justify-between mb-3">
                <p class="text-base font-semibold text-gray-900">
                  {{ number_format($p->price, 2, ',', ' ') }} ₽
                </p>
                <form method="POST" action="{{ route('cart.update', $i) }}" class="flex items-center gap-2">
                  @csrf
                  @method('PATCH')
                  <input type="number" min="1" name="qty" value="{{ $i->qty }}"
                         class="w-16 border-gray-300 rounded-lg p-1.5 text-center text-xs focus:ring-2 focus:ring-indigo-500 transition">
                </form>
              </div>

              <!-- Кнопки -->
              <div class="mt-auto flex flex-col gap-2">

                <!-- Купить сейчас -->
                <form method="POST" action="{{ route('checkout.quick', $p->id) }}">
                  @csrf
                  <button type="submit"
                          class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    <i class="ri-flashlight-line text-sm"></i>
                    Купить сейчас
                  </button>
                </form>

                <!-- В избранное -->
                <form method="POST" action="{{ route('favorites.toggle', $p) }}">
                  @csrf
                  <button type="submit"
                          class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium border border-gray-200 text-gray-700 rounded-lg hover:border-indigo-400 hover:text-indigo-600 transition">
                    <i class="ri-heart-line text-sm"></i>
                    В избранное
                  </button>
                </form>

                <!-- Удалить -->
                <form method="POST" action="{{ route('cart.remove', $i) }}">
                  @csrf
                  @method('DELETE')
                  <button type="submit"
                          class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium text-gray-500 border border-gray-200 rounded-lg hover:text-red-600 hover:border-red-400 transition">
                    <i class="ri-delete-bin-6-line text-sm"></i>
                    Удалить
                  </button>
                </form>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <!-- 💰 Итог -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="text-sm text-gray-500">Общая сумма заказа:</div>
          <div class="text-3xl font-semibold text-gray-900 mt-1">
            {{ number_format($total, 2, ',', ' ') }} ₽
          </div>
        </div>
        <form method="POST" action="{{ route('checkout') }}">
          @csrf
          <button
            class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            <i class="ri-check-double-line text-lg"></i> Оформить заказ
          </button>
        </form>
      </div>
    @endif
  </div>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
</x-buyer-layout>
