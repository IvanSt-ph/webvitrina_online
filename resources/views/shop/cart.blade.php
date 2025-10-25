<x-app-layout title="Моя корзина" :hideHeader="true">
  <div class="min-h-screen bg-gray-50 text-gray-800">
    <main class="max-w-7xl mx-auto px-6 pt-2 pb-10 space-y-10">

      <!-- Заголовок -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 flex items-center gap-2">
            <i class="ri-shopping-cart-2-line text-indigo-600 text-2xl"></i>
            Моя корзина
          </h1>
          <p class="text-sm text-gray-500 mt-1">Проверьте товары перед оформлением заказа</p>
        </div>
        <a href="{{ route('home') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-white border border-gray-200 text-gray-700 hover:text-indigo-600 hover:border-indigo-300 rounded-lg transition shadow-sm">
          <i class="ri-arrow-left-line text-lg"></i> Продолжить покупки
        </a>
      </div>

      @if($items->isEmpty())
        <!-- 🛒 Пустая корзина -->
        <section class="bg-white border border-gray-100 rounded-xl shadow-sm p-12 text-center">
          <i class="ri-shopping-cart-line text-6xl text-gray-300 mb-3"></i>
          <h2 class="text-lg font-semibold text-gray-800 mb-1">Ваша корзина пуста</h2>
          <p class="text-sm text-gray-500 mb-5">Добавьте товары и возвращайтесь сюда, чтобы оформить заказ</p>
          <a href="{{ route('home') }}"
             class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg transition">
            <i class="ri-store-2-line text-lg"></i> В каталог
          </a>
        </section>
      @else

        <!-- 🧾 Список товаров -->
        <section class="space-y-4">
          @foreach($items as $i)
            <div class="bg-white border border-gray-100 rounded-xl shadow-sm hover:shadow-md transition p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
              
              <!-- Фото и инфо -->
              <div class="flex items-center gap-4 flex-1">
                <div class="w-20 h-20 flex-shrink-0 bg-gray-50 border rounded-lg overflow-hidden flex items-center justify-center">
                  @if($i->product->image)
                    <img src="{{ asset('storage/'.$i->product->image) }}" alt="{{ $i->product->title }}"
                         class="object-contain w-full h-full">
                  @else
                    <i class="ri-image-line text-2xl text-gray-300"></i>
                  @endif
                </div>
                <div class="flex-1">
                  <a href="{{ route('product.show',$i->product) }}"
                     class="block font-medium text-gray-800 hover:text-indigo-600 transition truncate max-w-[250px]">
                     {{ $i->product->title }}
                  </a>
                  <div class="text-sm text-gray-500 mt-1">
                    {{ number_format($i->product->price/100,2,',',' ') }} ₽ / шт
                  </div>
                </div>
              </div>

              <!-- Количество и действия -->
              <div class="flex items-center gap-3 sm:justify-end w-full sm:w-auto">
                <form method="POST" action="{{ route('cart.update', $i) }}" class="flex items-center gap-2">@csrf @method('PATCH')
                  <input type="number" min="1" name="qty" value="{{ $i->qty }}"
                         class="w-20 border-gray-300 rounded-lg p-1.5 text-center text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                  <button class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition" title="Обновить">
                    <i class="ri-refresh-line"></i>
                  </button>
                </form>

                <form method="POST" action="{{ route('cart.remove', $i) }}">@csrf @method('DELETE')
                  <button class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition" title="Удалить">
                    <i class="ri-delete-bin-line text-lg"></i>
                  </button>
                </form>
              </div>
            </div>
          @endforeach
        </section>

        <!-- 💰 Итог -->
        <section class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 mt-10">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <div class="text-sm text-gray-500">Общая сумма заказа</div>
              <div class="text-3xl font-semibold text-gray-800 mt-1">
                {{ number_format($total/100,2,',',' ') }} ₽
              </div>
            </div>
            <form method="POST" action="{{ route('checkout') }}">@csrf
              <button class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-3 rounded-xl shadow-sm transition">
                <i class="ri-check-double-line text-lg"></i> Оформить заказ
              </button>
            </form>
          </div>
        </section>

      @endif

      

      <!-- FOOTER -->
      <footer class="text-center text-xs text-gray-400 pt-10 border-t">
        © {{ date('Y') }} WebVitrina — Корзина покупателя
      </footer>

    </main>
  </div>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
</x-app-layout>
