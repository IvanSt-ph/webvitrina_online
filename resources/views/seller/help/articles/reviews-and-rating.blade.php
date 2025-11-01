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
        Отзывы — это основа доверия на <strong>WebVitrina</strong>.  
        Покупатели читают их перед заказом, а алгоритмы используют оценки для ранжирования товаров.  
        Чем выше рейтинг и активность продавца, тем чаще его товары видны в поиске.
      </p>

      {{-- Раздел 1 --}}
      <x-help-section icon="ri-chat-1-line" title="Почему отзывы важны">
        <p>
          9 из 10 покупателей читают отзывы перед покупкой.  
          Даже один негативный отзыв без ответа способен снизить продажи.  
          Главное — реагировать быстро и корректно.
        </p>

        <img src="{{ asset('images/help/reviews-chart.jpg') }}" alt="Влияние отзывов" class="rounded-2xl shadow-md my-6">

        <x-help-note color="indigo" icon="ri-information-line">
          Товары с рейтингом выше 4.5 и более чем 10 отзывами продаются на <strong>28%</strong> лучше, чем товары без отзывов.
        </x-help-note>
      </x-help-section>

      {{-- Раздел 2 --}}
      <x-help-section icon="ri-tools-line" title="Как правильно отвечать на отзывы">
        <p>
          Ваш ответ видят все: покупатель, новые посетители и алгоритм площадки.  
          Отвечайте грамотно и позитивно — это формирует доверие.
        </p>

        <ul class="list-disc pl-6 space-y-2 text-gray-700 mt-3">
          <li><strong>Положительные отзывы:</strong> благодарите клиента и подчеркивайте заботу.</li>
          <li><strong>Негативные:</strong> отвечайте спокойно, предложите помощь или замену.</li>
          <li><strong>Нейтральные:</strong> уточняйте, что можно улучшить — это сигнал для роста.</li>
        </ul>

        <x-help-tip color="green" icon="ri-chat-smile-2-line">
          Пример:  
          “Спасибо, что выбрали нас! Мы ценим ваш отзыв и уже улучшили упаковку.  
          Ваше мнение помогает нам становиться лучше.”
        </x-help-tip>

        <img src="{{ asset('images/help/review-reply-example.jpg') }}" alt="Пример ответа" class="rounded-2xl shadow-md mt-6">
      </x-help-section>

      {{-- Раздел 3 --}}
      <x-help-section icon="ri-star-line" title="Как повысить рейтинг магазина">
        <p>Рейтинг магазина рассчитывается по четырём основным показателям:</p>

        <ul class="list-disc pl-6 text-gray-700 space-y-1 mt-3">
          <li>Средняя оценка по отзывам — 50% рейтинга;</li>
          <li>Скорость обработки заказов — 25%;</li>
          <li>Процент отмен и возвратов — 15%;</li>
          <li>Ответы на отзывы и сообщения — 10%.</li>
        </ul>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 my-6">
          <img src="{{ asset('images/help/reviews-dashboard.jpg') }}" alt="Панель отзывов" class="rounded-2xl shadow-md">
          <img src="{{ asset('images/help/review-statistics.jpg') }}" alt="Статистика рейтинга" class="rounded-2xl shadow-md">
        </div>

      <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
  <i class="ri-arrow-up-line text-green-500 text-xl"></i>
  Что помогает расти
</h3>

        <ul class="list-disc pl-6 text-gray-700 space-y-1">
          <li>Регулярное обновление ассортимента;</li>
          <li>Быстрая реакция на отзывы и чаты;</li>
          <li>Минимум возвратов (менее 3%).</li>
        </ul>

<h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2 mt-4">
  <i class="ri-arrow-down-line text-red-500 text-xl"></i>
  Что снижает рейтинг
</h3>

        <ul class="list-disc pl-6 text-gray-700 space-y-1">
          <li>Игнорирование отзывов;</li>
          <li>Долгая отправка заказов (3+ дней);</li>
          <li>Несоответствие описания и фото.</li>
        </ul>

        <x-help-note color="yellow" icon="ri-alert-line">
          Рейтинг обновляется ежедневно — любые улучшения быстро влияют на выдачу.
        </x-help-note>
      </x-help-section>

      {{-- Раздел 4 --}}
      <x-help-section icon="ri-thumb-up-line" title="Как получать больше отзывов">
        <p>Мотивируйте покупателей оставлять отзывы — это бесплатный способ продвижения.</p>

        <ul class="list-disc pl-6 text-gray-700 space-y-2 mt-3">
          <li>Кладите в посылку открытку с QR-кодом на страницу товара.</li>
          <li>Отправляйте сообщение после покупки: “Ваш отзыв помогает нам развиваться”.</li>
          <li>Предлагайте бонусы или скидку на следующий заказ.</li>
        </ul>

        <img src="{{ asset('images/help/review-card-example.jpg') }}" alt="Открытка с QR-кодом" class="rounded-2xl shadow-md my-6">

        <x-help-tip color="indigo" icon="ri-lightbulb-flash-line">
          Активные продавцы, получающие 5 отзывов в неделю, растут по выдаче в среднем на 15%.
        </x-help-tip>
      </x-help-section>

      <blockquote class="border-l-4 border-indigo-500 pl-4 text-gray-600 italic">
        “Ваш рейтинг — это не просто цифра, а отражение доверия покупателей.  
        Отвечайте с уважением — и клиенты обязательно вернутся.”
      </blockquote>

      <x-help-section icon="ri-play-circle-line" title="Видео: как работать с отзывами и рейтингом">
        <div class="relative aspect-video rounded-2xl overflow-hidden shadow-md my-4">
          <iframe class="w-full h-full"
                  src="https://www.youtube.com/embed/aqz-KE-bpKQ"
                  title="Как работать с отзывами и рейтингом"
                  frameborder="0" allowfullscreen></iframe>
        </div>
      </x-help-section>

    </article>

    {{-- 🔙 Навигация --}}
    <div class="mt-10 flex items-center justify-between text-sm text-gray-500">
      <a href="{{ route('seller.help', ['slug' => 'updates-2025']) }}" class="hover:text-indigo-600 flex items-center gap-1">
        <i class="ri-arrow-left-line"></i> Предыдущая статья
      </a>
      <a href="{{ route('seller.cabinet') }}" class="hover:text-indigo-600 flex items-center gap-1">
        К панели продавца <i class="ri-arrow-right-line"></i>
      </a>
    </div>

  </section>

</x-seller-layout>
