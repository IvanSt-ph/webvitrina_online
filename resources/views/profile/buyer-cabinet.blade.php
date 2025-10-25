<x-app-layout title="Личный кабинет" :hideHeader="true">
  <div class="flex min-h-screen bg-neutral-50 text-gray-800">

    <!-- 🧭 Sidebar -->
    <aside class="hidden md:flex flex-col w-64 bg-white border-r border-gray-100 justify-between fixed left-0 top-0 bottom-0 shadow-sm">
      <div>
        <!-- Логотип -->
        <div class="flex items-center gap-2 px-6 py-6 border-b border-gray-100">
          <img src="{{ asset('images/logo.png') }}" alt="WebVitrina" class="w-8 h-8 rounded-lg shadow-sm">
          <span class="font-semibold text-gray-800 text-sm tracking-tight">WebVitrina</span>
        </div>

        <!-- Навигация -->
        <nav class="flex flex-col mt-6 text-[17px] font-normal text-gray-700">
          @php
            $active = 'bg-indigo-50 text-indigo-600 font-medium border-l-4 border-indigo-500';
            $link = 'flex items-center gap-2 px-6 py-3 rounded-r-lg transition-all duration-200 hover:bg-indigo-50 hover:text-indigo-600 hover:translate-x-[3px]';
          @endphp

          <a href="{{ route('home') }}" class="{{ $link }}">
            <i class="ri-arrow-left-line text-[22px]"></i>
            <span>На главную</span>
          </a>
          <a href="{{ route('cabinet') }}" class="{{ request()->routeIs('cabinet') ? $active : '' }} {{ $link }}">
            <i class="ri-home-5-line text-[22px]"></i>
            <span>Статистика</span>
          </a>
          <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.*') ? $active : '' }} {{ $link }}">
            <i class="ri-shopping-bag-3-line text-[22px]"></i>
            <span>Заказы</span>
          </a>
          <a href="{{ route('favorites.index') }}" class="{{ request()->routeIs('favorites.*') ? $active : '' }} {{ $link }}">
            <i class="ri-heart-line text-[22px]"></i>
            <span>Избранное</span>
          </a>
          <a href="{{ route('cart.index') }}" class="{{ request()->routeIs('cart.*') ? $active : '' }} {{ $link }}">
            <i class="ri-shopping-cart-2-line text-[22px]"></i>
            <span>Корзина</span>
          </a>
          <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.*') ? $active : '' }} {{ $link }}">
            <i class="ri-settings-3-line text-[22px]"></i>
            <span>Настройки</span>
          </a>
        </nav>
      </div>

      <!-- Нижняя панель -->
      <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between text-gray-400 text-lg">
        <a href="#" class="hover:text-indigo-600"><i class="ri-telegram-line"></i></a>
        <a href="#" class="hover:text-indigo-600"><i class="ri-instagram-line"></i></a>
        <a href="#" class="hover:text-indigo-600"><i class="ri-github-line"></i></a>
      </div>
    </aside>

    <!-- 🌤 Основная часть -->
    <main class="flex-1 md:ml-64 p-6 md:p-10 bg-neutral-50">

      <!-- Верх -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-10">
        <div>
          <h1 class="text-2xl font-semibold text-gray-800">Здравствуйте, {{ $user->name }} 👋</h1>
          <p class="text-gray-500 text-sm mt-1">Добро пожаловать в ваш личный кабинет</p>
        </div>

        <div class="flex items-center gap-3 mt-4 md:mt-0">
          <img src="{{ $user->avatar ? asset('storage/'.$user->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}"
               class="w-10 h-10 rounded-full border border-gray-200 shadow-sm" alt="Аватар">

          <button class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
            Мой профиль
          </button>

          <a href="{{ route('profile.edit') }}"
             class="md:hidden flex items-center gap-1 px-3 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 transition">
            <i class="ri-settings-3-line text-[18px]"></i>
            <span>Настройки</span>
          </a>
        </div>
      </div>

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
