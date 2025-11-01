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
        В 2026 году <strong>WebVitrina</strong> выходит на новый уровень: обновлён дизайн, ускорен сайт,
        улучшена аналитика и добавлены новые инструменты для продавцов.
      </p>

      {{-- Раздел --}}
      <x-help-section icon="ri-rocket-2-line" title="Новый интерфейс и навигация">
        <p>Панель продавца стала проще и быстрее — обновлённое меню, быстрые действия и адаптация под все устройства.</p>
        <ul class="list-disc pl-6 space-y-2 text-gray-700 mt-3">
          <li>Новая шапка и боковое меню</li>
          <li>Тёмный режим (бета)</li>
          <li>Мгновенные переходы между страницами</li>
        </ul>
        <img src="{{ asset('images/help/new-dashboard.jpg') }}" alt="Интерфейс панели" class="rounded-2xl shadow-md mt-6">
      </x-help-section>

      {{-- Совет --}}
      <x-help-tip color="indigo" icon="ri-lightbulb-flash-line">
        Попробуйте закрепить часто используемые страницы — теперь панель поддерживает быстрые закладки!
      </x-help-tip>

      <x-help-section icon="ri-bar-chart-box-line" title="Новая аналитика продавца">
        <p>Добавлены графики и фильтры для отслеживания просмотров, заказов и конверсии по любому периоду.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 my-6">
          <img src="{{ asset('images/help/analytics-dashboard.jpg') }}" class="rounded-2xl shadow-md">
          <img src="{{ asset('images/help/analytics-charts.jpg') }}" class="rounded-2xl shadow-md">
        </div>
      </x-help-section>

      <x-help-note color="green" icon="ri-line-chart-line">
        Теперь аналитика обновляется ежедневно, а не раз в неделю.
      </x-help-note>

      <x-help-section icon="ri-chat-1-line" title="Обновлённая система отзывов">
        <p>Добавлена возможность отмечать отзывы как решённые и получать уведомления прямо в панели продавца.</p>
        <img src="{{ asset('images/help/reviews-dashboard.jpg') }}" class="rounded-2xl shadow-md my-6">
      </x-help-section>

      <x-help-tip color="yellow" icon="ri-timer-flash-line">
        Отвечайте на отзывы быстрее — алгоритм теперь учитывает активность продавца при показе товаров в поиске.
      </x-help-tip>

      <x-help-section icon="ri-vip-crown-line" title="Новая система подписок и бонусов">
        <ul class="list-disc pl-6 space-y-2 text-gray-700">
          <li><strong>Базовый:</strong> до 10 товаров, без комиссии</li>
          <li><strong>Продвинутый:</strong> до 50 товаров, приоритет в поиске</li>
          <li><strong>Премиум:</strong> неограниченно, доступ к аналитике PRO</li>
        </ul>
      </x-help-section>

      <x-help-note color="indigo" icon="ri-gift-line">
        Первые 30 дней на тарифе “Продвинутый” — бесплатно для всех новых продавцов.
      </x-help-note>

      <x-help-section icon="ri-global-line" title="Расширение географии">
        <p>Теперь WebVitrina официально работает в <strong>Молдове, Украине и Приднестровье</strong>.</p>
        <img src="{{ asset('images/help/countries-update.jpg') }}" class="rounded-2xl shadow-md my-6">
      </x-help-section>

      <x-help-note color="green" icon="ri-map-pin-2-line">
        Автоматическое определение региона по IP и отображение цен в локальной валюте.
      </x-help-note>

      {{-- Видео --}}
      <x-help-section icon="ri-play-circle-line" title="Обзор обновлений">
        <div class="relative aspect-video rounded-2xl overflow-hidden shadow-md my-4">
          <iframe class="w-full h-full" src="https://www.youtube.com/embed/DXUAyRRkI6k" frameborder="0" allowfullscreen></iframe>
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
