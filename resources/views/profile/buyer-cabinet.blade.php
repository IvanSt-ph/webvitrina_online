<x-buyer-layout title="Личный кабинет">
  <div class="space-y-8">
    <h1 class="text-2xl font-semibold text-gray-800">Здравствуйте, {{ $user->name }} 👋</h1> 
    <p class="text-gray-500 text-sm">Добро пожаловать в ваш личный кабинет!</p>


    

      <!-- Статистика -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        @php
          $cards = [
            ['Мои заказы', 12, '+3 за месяц'],
            ['Избранное', 8, '2 новых'],
            ['Корзина', 3, 'Готово к оплате'],
            ['Бонусы', '₽245', '+₽15 за отзывы'],
          ];
        @endphp

        @foreach($cards as [$title, $value, $sub])
          <div class="bg-white rounded-xl p-5 border border-gray-100 hover:shadow-md transition hover:-translate-y-[2px]">
            <p class="text-sm text-gray-500">{{ $title }}</p>
            <div class="text-2xl font-semibold mt-2 text-gray-800">{{ $value }}</div>
            <p class="text-xs text-gray-400 mt-1">{{ $sub }}</p>
          </div>
        @endforeach
      </div>

      <!-- Основные блоки -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Левая зона -->
        <div class="lg:col-span-2 space-y-6">

          <!-- Заказы -->
          <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-800">Последние заказы</h2>
              <a href="{{ route('orders.index') }}" class="text-sm text-indigo-600 hover:underline">Все →</a>
            </div>

            <div class="divide-y divide-gray-100">
              @foreach([['#3042 • Смарт-часы TCL','09.10.2025','Доставлен'],
                        ['#3037 • Куртка мужская','06.10.2025','В пути'],
                        ['#3033 • Сумка женская','02.10.2025','Ожидает оплату']] as [$num,$date,$status])
              <div class="flex justify-between py-3">
                <div>
                  <p class="text-sm font-medium text-gray-800">{{ $num }}</p>
                  <p class="text-xs text-gray-400">от {{ $date }}</p>
                </div>
                <span class="text-xs font-medium text-gray-600">{{ $status }}</span>
              </div>
              @endforeach
            </div>

            <div class="mt-4 text-center">
              <a href="{{ route('orders.index') }}"
                 class="inline-block px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100 transition">
                Все заказы
              </a>
            </div>
          </div>

          <!-- Рекомендации -->
          <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-gray-800">Рекомендации для вас</h2>
              <a href="#" class="text-sm text-indigo-600 hover:underline">Обновить</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
              @foreach([1,2,3] as $i)
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-3 text-center hover:bg-white hover:shadow-sm transition">
                  <div class="h-24 bg-white rounded-lg flex items-center justify-center mb-3">
                    <i class="ri-image-2-line text-2xl text-gray-300"></i>
                  </div>
                  <p class="text-sm font-medium text-gray-800">Товар {{ $i }}</p>
                  <p class="text-xs text-gray-500 mt-1">₽{{ rand(800,2500) }}</p>
                </div>
              @endforeach
            </div>
          </div>
        </div>

        <!-- Правая зона -->
        <div class="space-y-6">

          <!-- Активность -->
          <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm hover:shadow-md transition">
            <h3 class="font-semibold text-lg mb-3 text-gray-800">Активность</h3>
            <ul class="space-y-3 text-sm text-gray-600">
              <li class="flex justify-between"><span>Добавлен отзыв</span> <span class="text-gray-400">08.10</span></li>
              <li class="flex justify-between"><span>Товар в избранное</span> <span class="text-gray-400">07.10</span></li>
              <li class="flex justify-between"><span>Оплачен заказ</span> <span class="text-gray-400">06.10</span></li>
            </ul>
          </div>

          <!-- Бонусы -->
          <div class="bg-gradient-to-br from-indigo-50 to-white rounded-xl p-6 border border-gray-100 shadow-sm hover:shadow-md transition">
            <h3 class="font-semibold text-lg mb-3 text-gray-800">Ваши бонусы</h3>
            <p class="text-sm text-gray-500 mb-4">Вы можете обменять бонусы на скидки или купоны.</p>
            <button class="w-full bg-indigo-600 text-white font-medium py-2 rounded-lg hover:bg-indigo-700 transition">
              Обменять бонусы
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
</x-app-layout>
