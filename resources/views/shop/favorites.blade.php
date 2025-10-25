<x-buyer-layout title="Избранное">

  <div class="space-y-8">

    <!-- 🔝 Заголовок -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900">💖 Избранное</h1>
        <p class="text-gray-500 text-sm mt-1">
          Все понравившиеся вами товары. Можно добавить в корзину или купить сразу.
        </p>
      </div>

      @if($items->isNotEmpty())
        <a href="{{ route('cart.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
          <i class="ri-shopping-cart-2-line text-lg"></i> Перейти в корзину
        </a>
      @endif
    </div>

    @if($items->isEmpty())
      <!-- 🕊 Пустое состояние -->
      <div class="text-center py-24">
        <div class="text-6xl mb-3">🛍️</div>
        <p class="text-lg font-medium text-gray-700">У вас пока нет избранных товаров</p>
        <p class="text-sm text-gray-500 mt-1">Добавляйте понравившиеся товары, чтобы вернуться к ним позже.</p>
<a href="{{ route('home') }}"
   class="mt-6 inline-block bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition">
  Перейти в каталог
</a>

      </div>
    @else
      <!-- 🛍 Список избранного -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
        @foreach($items as $f)
          @php $p = $f->product; @endphp
          <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg transition-all duration-300 group flex flex-col overflow-hidden">

            <!-- Фото -->
            <a href="{{ route('product.show', $p) }}" class="relative aspect-square bg-gray-50 overflow-hidden">
              @if($p->image)
                <img src="{{ asset('storage/'.$p->image) }}"
                     alt="{{ $p->title }}"
                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
              @else
                <div class="flex items-center justify-center h-full text-gray-300 text-3xl">🛒</div>
              @endif

              <!-- Удалить из избранного -->
              <form method="POST" action="{{ route('favorites.toggle', $p) }}" class="absolute top-2 right-2 z-10">
                @csrf
                <button class="w-8 h-8 flex items-center justify-center bg-white/80 rounded-full shadow hover:bg-red-100 transition">
                  <i class="ri-heart-fill text-red-500 text-lg"></i>
                </button>
              </form>
            </a>

            <!-- Информация -->
            <div class="flex-1 flex flex-col p-4">
              <a href="{{ route('product.show', $p) }}"
                 class="text-sm font-medium text-gray-800 hover:text-indigo-600 line-clamp-2 min-h-[40px] mb-2">
                {{ $p->title }}
              </a>

              @if($p->price)
                <p class="text-base font-semibold text-gray-900 mb-3">
                  {{ number_format($p->price, 2, ',', ' ') }} ₽
                </p>
              @else
                <p class="text-sm text-gray-400 mb-3">Нет в наличии</p>
              @endif

              <div class="mt-auto flex flex-col gap-2">
                <!-- Добавить в корзину -->
                <form method="POST" action="{{ route('cart.add', $p->id) }}">
                  @csrf
                  <button type="submit"
                          class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    <i class="ri-shopping-cart-line text-sm"></i>
                    В корзину
                  </button>
                </form>

                <!-- Купить сейчас -->
                <form method="POST" action="{{ route('checkout.quick', $p->id) }}">
                  @csrf
                  <button type="submit"
                          class="w-full flex items-center justify-center gap-2 px-3 py-2 text-xs font-medium border border-gray-200 text-gray-700 rounded-lg hover:border-indigo-400 hover:text-indigo-600 transition">
                    <i class="ri-flashlight-line text-sm"></i>
                    Купить сейчас
                  </button>
                </form>

                <!-- Удалить -->
                <form method="POST" action="{{ route('favorites.toggle', $p) }}">
                  @csrf
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
    @endif
  </div>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
</x-buyer-layout>
