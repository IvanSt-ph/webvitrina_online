<x-seller-layout :title="$news['title']">

  @php
    $slug = last(explode('/', $news['url']));
    $image = asset("images/help/{$slug}.webp");
  @endphp

  <section class="pt-2 pb-16 space-y-10 px-4 sm:px-6 lg:px-8">

    {{-- 🖼️ Обложка --}}
    <div class="relative rounded-3xl overflow-hidden shadow-md border border-gray-100 mb-10">
      <img src="{{ $image }}" alt="{{ $news['title'] }}" class="w-full h-64 sm:h-96 object-cover">
      <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent"></div>
      <div class="absolute bottom-6 left-8 text-white">
        <h1 class="text-3xl sm:text-4xl font-bold drop-shadow-lg">{{ $news['title'] }}</h1>
        <p class="text-sm text-gray-200 mt-1">{{ $news['date'] }}</p>
      </div>
    </div>

    {{-- 📄 Контент --}}
    <article class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-10 leading-relaxed text-gray-800 space-y-10">

      <p class="text-lg">
        В <strong>2025 году</strong> WebVitrina вышла на новый уровень.  
        Мы обновили интерфейс, ускорили загрузку, улучшили аналитику и добавили новые инструменты для продавцов —  
        всё для того, чтобы вы продавали быстрее и удобнее.
      </p>

      {{-- Раздел 1 --}}
      <x-help-section icon="ri-rocket-2-line" title="Новый интерфейс и навигация">
        <p>Панель продавца стала быстрее и удобнее: обновлённое меню, быстрые кнопки и адаптация под все устройства.</p>

        <ul class="list-disc pl-6 space-y-2 text-gray-700 mt-3">
          <li>Новая шапка и боковое меню;</li>
          <li>Тёмный режим (в бета-тесте);</li>
          <li>Полная адаптация под смартфоны и планшеты;</li>
          <li>Мгновенные переходы между разделами.</li>
        </ul>

        <img src="{{ asset('images/help/new-dashboard.jpg') }}" alt="Интерфейс панели продавца" class="rounded-2xl shadow-md mt-6">

        <x-help-tip color="indigo" icon="ri-lightbulb-flash-line">
          Закрепляйте часто используемые страницы — теперь панель поддерживает быстрые закладки!
        </x-help-tip>
      </x-help-section>

      {{-- Раздел 2 --}}
      <x-help-section icon="ri-bar-chart-box-line" title="Новая аналитика продавца">
        <p>Добавлены детальные графики и фильтры — отслеживайте эффективность товаров по любому периоду.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 my-6">
          <img src="{{ asset('images/help/analytics-dashboard.jpg') }}" alt="Аналитика продавца" class="rounded-2xl shadow-md">
          <img src="{{ asset('images/help/analytics-charts.jpg') }}" alt="Графики продаж" class="rounded-2xl shadow-md">
        </div>

        <ul class="list-disc pl-6 text-gray-700 space-y-1">
          <li>Фильтрация по дате, категории и городу;</li>
          <li>Отслеживание добавлений в корзину и заказов;</li>
          <li>Отображение популярности товаров в категории.</li>
        </ul>

        <x-help-note color="green" icon="ri-line-chart-line">
          Теперь аналитика обновляется ежедневно, а не раз в неделю.
        </x-help-note>
      </x-help-section>

      {{-- Раздел 3 --}}
      <x-help-section icon="ri-chat-1-line" title="Обновлённая система отзывов">
        <p>Теперь продавцы могут помечать отзывы как решённые, оставлять комментарии и получать уведомления.</p>

        <ul class="list-disc pl-6 text-gray-700 space-y-1 mt-3">
          <li>Пометка “Проблема решена”;</li>
          <li>Автоматическая статистика по отзывам;</li>
          <li>Оповещения о новых оценках.</li>
        </ul>

        <img src="{{ asset('images/help/reviews-dashboard.jpg') }}" alt="Система отзывов" class="rounded-2xl shadow-md my-6">

        <x-help-tip color="yellow" icon="ri-timer-flash-line">
          Отвечайте на отзывы оперативно — активность продавца теперь влияет на позицию товара в поиске.
        </x-help-tip>
      </x-help-section>

      {{-- Раздел 4 --}}
      <x-help-section icon="ri-vip-crown-line" title="Система подписок и бонусов">
        <p>Добавлены новые тарифы для гибкости размещения и продвижения.</p>

        <ul class="list-disc pl-6 text-gray-700 space-y-2 mt-3">
          <li><strong>Базовый:</strong> до 10 товаров, без комиссии;</li>
          <li><strong>Продвинутый:</strong> до 50 товаров, приоритет в поиске;</li>
          <li><strong>Премиум:</strong> неограниченно, доступ к аналитике PRO и рекламе.</li>
        </ul>

        <x-help-note color="indigo" icon="ri-gift-line">
          Первые 30 дней тарифа “Продвинутый” — бесплатно для всех новых продавцов.
        </x-help-note>
      </x-help-section>

      {{-- Раздел 5 --}}
      <x-help-section icon="ri-global-line" title="Расширение географии">
        <p>
          WebVitrina теперь работает в <strong>Молдове</strong>, <strong>Украине</strong> и <strong>Приднестровье</strong>.  
          Добавлены локальные валюты, языки и флаги в интерфейсе.
        </p>

        <img src="{{ asset('images/help/countries-update.jpg') }}" alt="Мультивалютность и регионы" class="rounded-2xl shadow-md my-6">

        <x-help-note color="green" icon="ri-map-pin-2-line">
          Реализовано автоматическое определение региона по IP и показ цен в локальной валюте.
        </x-help-note>
      </x-help-section>

      {{-- Раздел 6 --}}
      <x-help-section icon="ri-play-circle-line" title="Обзор обновлений">
        <div class="relative aspect-video rounded-2xl overflow-hidden shadow-md my-4">
          <iframe class="w-full h-full"
                  src="https://www.youtube.com/embed/DXUAyRRkI6k"
                  title="Обзор обновлений WebVitrina 2025"
                  frameborder="0" allowfullscreen></iframe>
        </div>
      </x-help-section>

      <blockquote class="border-l-4 border-indigo-500 pl-4 text-gray-600 italic">
        “WebVitrina развивается вместе с вами. Мы слушаем продавцов и делаем платформу лучше каждый месяц.”
      </blockquote>

    </article>

    {{-- 🔙 Навигация --}}
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
