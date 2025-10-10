<x-app-layout title="Личный кабинет" :hideHeader="true">

  <div class="flex min-h-screen bg-white text-black">

    <!-- 🧭 Sidebar -->
    <aside class="hidden md:flex flex-col w-64 bg-white border-r border-gray-100 justify-between fixed left-0 top-0 bottom-0">
      <div>
        <!-- Логотип -->
        <div class="flex items-center gap-2 px-6 py-6 border-b border-gray-100">
          <img src="{{ asset('images/logo.png') }}" alt="WebVitrina" class="w-8 h-8">
          <span class="font-semibold text-black text-sm tracking-tight">WebVitrina</span>
        </div>

        <!-- Навигация -->
        <nav class="flex flex-col gap-0 mt-6 text-[18px] font-normal text-black">

          <!-- 🏠 На главную -->
          <a href="{{ route('home') }}"
             class="flex items-center gap-2 px-6 py-3 rounded-lg transition-all duration-200 hover:bg-gray-50 hover:translate-x-[3px]">
            <i class="ri-arrow-left-line text-[22px] text-gray-500"></i>
            <span class="tracking-tight">На главную</span>
          </a>

          <a href="{{ route('cabinet') }}"
             class="flex items-center gap-2 px-6 py-3 rounded-lg transition-all duration-200 hover:bg-gray-50 hover:translate-x-[3px]">
            <i class="ri-home-5-line text-[22px] text-gray-500"></i>
            <span class="tracking-tight">Статистика</span>
          </a>

          <a href="{{ route('orders.index') }}"
             class="flex items-center gap-2 px-6 py-3 rounded-lg transition-all duration-200 hover:bg-gray-50 hover:translate-x-[3px]">
            <i class="ri-shopping-bag-3-line text-[22px] text-gray-500"></i>
            <span class="tracking-tight">Заказы</span>
          </a>

          <a href="{{ route('favorites.index') }}"
             class="flex items-center gap-2 px-6 py-3 rounded-lg transition-all duration-200 hover:bg-gray-50 hover:translate-x-[3px]">
            <i class="ri-heart-line text-[22px] text-gray-500"></i>
            <span class="tracking-tight">Избранное</span>
          </a>

          <a href="{{ route('cart.index') }}"
             class="flex items-center gap-2 px-6 py-3 rounded-lg transition-all duration-200 hover:bg-gray-50 hover:translate-x-[3px]">
            <i class="ri-shopping-cart-2-line text-[22px] text-gray-500"></i>
            <span class="tracking-tight">Корзина</span>
          </a>

          <a href="{{ route('profile.edit') }}"
             class="flex items-center gap-2 px-6 py-3 rounded-lg transition-all duration-200 hover:bg-gray-50 hover:translate-x-[3px]">
            <i class="ri-settings-3-line text-[22px] text-gray-500"></i>
            <span class="tracking-tight">Настройки</span>
          </a>
        </nav>
      </div>

      <!-- Нижняя панель -->
      <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between text-gray-400 text-lg">
        <a href="#" class="hover:text-black"><i class="ri-telegram-line"></i></a>
        <a href="#" class="hover:text-black"><i class="ri-instagram-line"></i></a>
        <a href="#" class="hover:text-black"><i class="ri-github-line"></i></a>
      </div>
    </aside>

    <!-- 🌤 Основная часть -->
    <main class="flex-1 md:ml-64 p-6 md:p-10 bg-white">

      <!-- Верх -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-10">
        <div>
          <h1 class="text-2xl font-semibold text-black">Здравствуйте, {{ $user->name }} 👋</h1>
          <p class="text-gray-500 text-sm mt-1">Добро пожаловать в ваш личный кабинет</p>
        </div>

        <div class="flex items-center gap-2 mt-4 md:mt-0">
          <img src="{{ $user->avatar ? asset('storage/'.$user->avatar) : 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}"
               class="w-10 h-10 rounded-full border border-gray-200" alt="Аватар">

          <!-- Кнопка Профиль -->
          <button class="px-4 py-2 bg-black text-white text-sm rounded-lg hover:bg-gray-800 transition">
            Мой профиль
          </button>

          <!-- Кнопка Настройки (только на мобильных) -->
          <a href="{{ route('profile.edit') }}"
             class="md:hidden flex items-center gap-1 px-3 py-2 bg-gray-100 text-gray-800 text-sm rounded-lg hover:bg-gray-200 transition">
            <i class="ri-settings-3-line text-[18px]"></i>
            <span>Настройки</span>
          </a>
        </div>
      </div>

      <!-- Статистика -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100 hover:shadow-sm transition">
          <p class="text-sm text-gray-500">Мои заказы</p>
          <div class="text-2xl font-semibold mt-2 text-black">12</div>
          <p class="text-xs text-gray-400 mt-1">+3 за месяц</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100 hover:shadow-sm transition">
          <p class="text-sm text-gray-500">Избранное</p>
          <div class="text-2xl font-semibold mt-2 text-black">8</div>
          <p class="text-xs text-gray-400 mt-1">2 новых</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100 hover:shadow-sm transition">
          <p class="text-sm text-gray-500">Корзина</p>
          <div class="text-2xl font-semibold mt-2 text-black">3</div>
          <p class="text-xs text-gray-400 mt-1">Готово к оплате</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-5 border border-gray-100 hover:shadow-sm transition">
          <p class="text-sm text-gray-500">Бонусы</p>
          <div class="text-2xl font-semibold mt-2 text-black">₽245</div>
          <p class="text-xs text-gray-400 mt-1">+₽15 за отзывы</p>
        </div>
      </div>

      <!-- Основные блоки -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Левая зона -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Заказы -->
          <div class="bg-gray-50 rounded-xl border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-black">Последние заказы</h2>
              <a href="{{ route('orders.index') }}" class="text-sm text-gray-600 hover:text-black transition">Все →</a>
            </div>

            <div class="divide-y divide-gray-100">
              <div class="flex justify-between py-3">
                <div>
                  <p class="text-sm font-medium text-black">#3042 • Смарт-часы TCL</p>
                  <p class="text-xs text-gray-400">от 09.10.2025</p>
                </div>
                <span class="text-xs font-medium text-gray-700">Доставлен</span>
              </div>
              <div class="flex justify-between py-3">
                <div>
                  <p class="text-sm font-medium text-black">#3037 • Куртка мужская</p>
                  <p class="text-xs text-gray-400">от 06.10.2025</p>
                </div>
                <span class="text-xs font-medium text-gray-700">В пути</span>
              </div>
              <div class="flex justify-between py-3">
                <div>
                  <p class="text-sm font-medium text-black">#3033 • Сумка женская</p>
                  <p class="text-xs text-gray-400">от 02.10.2025</p>
                </div>
                <span class="text-xs font-medium text-gray-700">Ожидает оплату</span>
              </div>
            </div>

            <!-- Кнопка "Все заказы" -->
            <div class="mt-4 text-center">
              <a href="{{ route('orders.index') }}"
                 class="inline-block px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100 transition">
                Все заказы
              </a>
            </div>
          </div>

          <!-- Рекомендации -->
          <div class="bg-gray-50 rounded-xl border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-semibold text-black">Рекомендации для вас</h2>
              <a href="#" class="text-sm text-gray-600 hover:text-black transition">Обновить</a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
              @foreach([1,2,3] as $i)
                <div class="bg-white border border-gray-100 rounded-xl p-3 text-center hover:shadow-sm transition">
                  <div class="h-24 bg-gray-50 rounded-lg flex items-center justify-center mb-3">
                    <i class="ri-image-2-line text-2xl text-gray-300"></i>
                  </div>
                  <p class="text-sm font-medium text-black">Товар {{ $i }}</p>
                  <p class="text-xs text-gray-400 mt-1">₽{{ rand(800,2500) }}</p>
                </div>
              @endforeach
            </div>
          </div>
        </div>

        <!-- Правая зона -->
        <div class="space-y-6">
          <!-- Активность -->
          <div class="bg-gray-50 rounded-xl border border-gray-100 p-6">
            <h3 class="font-semibold text-lg mb-3 text-black">Активность</h3>
            <ul class="space-y-3 text-sm">
              <li class="flex justify-between"><span>Добавлен отзыв</span> <span class="text-gray-400">08.10</span></li>
              <li class="flex justify-between"><span>Товар в избранное</span> <span class="text-gray-400">07.10</span></li>
              <li class="flex justify-between"><span>Оплачен заказ</span> <span class="text-gray-400">06.10</span></li>
            </ul>
          </div>

          <!-- Бонусы -->
          <div class="bg-gray-50 rounded-xl p-6 border border-gray-100">
            <h3 class="font-semibold text-lg mb-3 text-black">Ваши бонусы</h3>
            <p class="text-sm text-gray-500 mb-4">Вы можете обменять бонусы на скидки или купоны.</p>
            <button class="w-full bg-black text-white font-medium py-2 rounded-lg hover:bg-gray-800 transition">
              Обменять бонусы
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>

  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.1.0/fonts/remixicon.css" rel="stylesheet">
</x-app-layout>
