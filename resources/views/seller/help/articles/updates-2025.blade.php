<x-seller-layout :title="$news['title']">

  @php
    // Определяем slug статьи и картинку для обложки
    $slug = last(explode('/', $news['url']));
    $image = asset("images/help/{$slug}.webp");
  @endphp

  <section class="pt-2 pb-10 space-y-10 px-4 sm:px-6 lg:px-8">

    <!-- 🖼️ Обложка -->
    <div class="relative rounded-3xl overflow-hidden shadow-md border border-gray-100 mb-10">
      <img src="{{ $image }}" alt="Обновления WebVitrina 2025" class="w-full h-64 sm:h-96 object-cover">
      <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></div>
      <div class="absolute bottom-5 left-8 text-white">
        <h1 class="text-3xl sm:text-4xl font-bold drop-shadow-lg">{{ $news['title'] }}</h1>
        <p class="text-sm text-gray-200 mt-1">{{ $news['date'] }}</p>
      </div>
    </div>

    <!-- 📄 Контент -->
    <article class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-10 leading-relaxed text-gray-800 space-y-8">

      <p class="text-lg">
        В 2025 году <strong>WebVitrina</strong> выходит на новый уровень.  
        Мы обновили дизайн, улучшили аналитику, ускорили работу сайта и добавили полезные инструменты для продавцов.  
        Всё это — чтобы вам было проще продавать и зарабатывать.
      </p>

      <h2 class="text-2xl font-semibold text-indigo-600">🚀 Новый интерфейс и удобная навигация</h2>
      <p>
        Панель продавца стала проще и быстрее:  
        мы переработали меню, добавили быстрые кнопки действий и сделали интерфейс адаптивным для всех устройств.
      </p>

      <ul class="list-disc pl-6 space-y-2 text-gray-700">
        <li>Новая шапка и боковое меню;</li>
        <li>Тёмный режим (в бета-тесте);</li>
        <li>Адаптивный дизайн под смартфоны и планшеты;</li>
        <li>Мгновенные переходы между разделами.</li>
      </ul>

      <img src="{{ asset('images/help/new-dashboard.jpg') }}" alt="Новый интерфейс панели продавца" class="rounded-2xl shadow-md my-6">

      <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-sm text-indigo-800">
        💡 <strong>Совет:</strong> попробуйте закрепить часто используемые страницы — теперь панель поддерживает быстрые закладки!
      </div>

      <h2 class="text-2xl font-semibold text-indigo-600">📊 Новая аналитика продавца</h2>
      <p>
        Добавлены расширенные графики и фильтры, чтобы вы могли отслеживать эффективность своих товаров.  
        Теперь можно видеть динамику просмотров, добавлений в корзину и заказов за любой период.
      </p>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 my-6">
        <img src="{{ asset('images/help/analytics-dashboard.jpg') }}" alt="Аналитика продавца" class="rounded-2xl shadow-md">
        <img src="{{ asset('images/help/analytics-charts.jpg') }}" alt="Графики продаж" class="rounded-2xl shadow-md">
      </div>

      <ul class="list-disc pl-6 text-gray-700 space-y-1">
        <li>Фильтрация по дате, категории и городу;</li>
        <li>Показ динамики добавлений в корзину и заказов;</li>
        <li>Отображение популярности товаров в категории.</li>
      </ul>

      <div class="bg-green-50 border border-green-100 rounded-xl p-4 text-sm text-green-800">
        📈 <strong>Новое:</strong> теперь аналитика обновляется ежедневно, а не раз в неделю.
      </div>

      <h2 class="text-2xl font-semibold text-indigo-600">💬 Обновлённая система отзывов</h2>
      <p>
        Продавцы теперь могут отмечать отзывы как “решённые”, оставлять комментарии администраторам  
        и получать уведомления о новых оценках прямо в панели.
      </p>

      <ul class="list-disc pl-6 text-gray-700 space-y-1">
        <li>Пометка “Проблема решена”;</li>
        <li>Автоматическая статистика по отзывам;</li>
        <li>Уведомления о новых откликах клиентов.</li>
      </ul>

      <img src="{{ asset('images/help/reviews-dashboard.jpg') }}" alt="Новая система отзывов" class="rounded-2xl shadow-md my-6">

      <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4 text-sm text-yellow-800">
        💡 <strong>Совет:</strong> отвечайте на отзывы быстрее — алгоритм теперь учитывает активность продавца при показе товаров в поиске.
      </div>

      <h2 class="text-2xl font-semibold text-indigo-600">💰 Новая система подписок и бонусов</h2>
      <p>
        Мы добавили подписки для продавцов, чтобы дать больше гибкости в размещении товаров и продвижении.  
        Теперь можно выбрать оптимальный план и получить дополнительные возможности.
      </p>

      <ul class="list-disc pl-6 text-gray-700 space-y-2">
        <li><strong>Базовый:</strong> до 10 товаров, без комиссии.</li>
        <li><strong>Продвинутый:</strong> до 50 товаров, приоритет в поиске.</li>
        <li><strong>Премиум:</strong> неограниченно, доступ к аналитике PRO и рекламным инструментам.</li>
      </ul>

      <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-sm text-indigo-800">
        💎 <strong>Бонус:</strong> первые 30 дней на тарифе “Продвинутый” — бесплатно для всех новых продавцов.
      </div>

      <h2 class="text-2xl font-semibold text-indigo-600">🌐 Расширение географии</h2>
      <p>
        WebVitrina теперь официально работает в <strong>Молдове, Украине и Приднестровье</strong>.  
        Добавлены локальные валюты, языки и флаги в шапке сайта.
      </p>

      <img src="{{ asset('images/help/countries-update.jpg') }}" alt="Мультивалютность и регионы" class="rounded-2xl shadow-md my-6">

      <div class="bg-green-50 border border-green-100 rounded-xl p-4 text-sm text-green-800">
        🌍 <strong>Новое:</strong> автоматическое определение региона по IP и отображение цен в локальной валюте.
      </div>

      <h2 class="text-2xl font-semibold text-indigo-600">🎥 Обзор обновлений</h2>
      <div class="relative aspect-video rounded-2xl overflow-hidden shadow-md my-6">
        <iframe class="w-full h-full"
                src="https://www.youtube.com/embed/DXUAyRRkI6k"
                title="Обзор обновлений WebVitrina 2025"
                frameborder="0" allowfullscreen></iframe>
      </div>

      <blockquote class="border-l-4 border-indigo-500 pl-4 text-gray-600 italic">
        “WebVitrina развивается вместе с вами. Мы слушаем продавцов и делаем платформу лучше каждый месяц.”
      </blockquote>

    </article>

    <!-- 🔙 Навигация -->
    <div class="mt-10 flex items-center justify-between text-sm text-gray-500">
      <a href="{{ route('seller.help', ['slug' => 'product-optimization']) }}" class="hover:text-indigo-600 flex items-center gap-1">
        <i class="ri-arrow-left-line"></i> Предыдущая статья
      </a>
      <a href="{{ route('seller.help', ['slug' => 'reviews-and-rating']) }}" class="hover:text-indigo-600 flex items-center gap-1">
        Следующая статья <i class="ri-arrow-right-line"></i>
      </a>
    </div>

  </section>

</x-seller-layout>
