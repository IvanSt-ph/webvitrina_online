<x-seller-layout :title="$news['title']">

  @php
    $slug = last(explode('/', $news['url']));
    $image = asset("images/help/{$slug}.webp");
  @endphp

  <section class="pt-2 pb-10 space-y-10 px-4 sm:px-6 lg:px-8">

    <!-- 🖼️ Обложка -->
    <div class="relative rounded-3xl overflow-hidden shadow-md border border-gray-100 mb-10">
      <img src="{{ $image }}" alt="Отзывы и рейтинг" class="w-full h-64 sm:h-96 object-cover">
      <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></div>
      <div class="absolute bottom-5 left-8 text-white">
        <h1 class="text-3xl sm:text-4xl font-bold drop-shadow-lg">{{ $news['title'] }}</h1>
        <p class="text-sm text-gray-200 mt-1">{{ $news['date'] }}</p>
      </div>
    </div>

    <!-- 📄 Контент -->
    <article class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-10 leading-relaxed text-gray-800 space-y-8">

      <p class="text-lg">
        Отзывы — сердце доверия на <strong>WebVitrina</strong>.  
        Покупатели читают отзывы, чтобы понять, стоит ли вам доверять, а алгоритм площадки использует их, чтобы ранжировать товары.  
        Чем выше рейтинг и активность продавца, тем чаще его товары показываются в поиске.
      </p>

      <h2 class="text-2xl font-semibold text-indigo-600">💬 Почему отзывы важны</h2>
      <p>
        92% покупателей читают отзывы перед покупкой, и даже один отрицательный отзыв без ответа  
        способен уменьшить продажи. Главное — вовремя реагировать и сохранять вежливость.
      </p>

      <img src="{{ asset('images/help/reviews-chart.jpg') }}" alt="Влияние отзывов" class="rounded-2xl shadow-md my-6">

      <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-sm text-indigo-800">
        💡 <strong>Факт:</strong> товары с рейтингом выше 4.5 и более чем 10 отзывами  
        продаются на <strong>28%</strong> лучше, чем товары без отзывов.
      </div>

      <h2 class="text-2xl font-semibold text-indigo-600">🛠 Как правильно отвечать</h2>
      <p>Ваш ответ видят все — покупатель, посетители страницы и алгоритм. Пишите грамотно и позитивно.</p>
      <ul class="list-disc pl-6 space-y-2 text-gray-700">
        <li><strong>Положительные отзывы:</strong> благодарите и укрепляйте связь с клиентом.</li>
        <li><strong>Негативные:</strong> отвечайте спокойно, покажите готовность решить вопрос.</li>
        <li><strong>Нейтральные:</strong> уточняйте, что можно улучшить — это помогает развитию.</li>
      </ul>

      <div class="bg-green-50 border border-green-100 rounded-xl p-4 text-sm text-green-800">
        💬 <strong>Пример:</strong>  
        “Спасибо, что выбрали нас! Мы ценим ваш отзыв и уже улучшили упаковку. Ваше мнение помогает нам стать лучше ❤️”
      </div>

      <img src="{{ asset('images/help/review-reply-example.jpg') }}" alt="Пример ответа" class="rounded-2xl shadow-md my-6">

      <h2 class="text-2xl font-semibold text-indigo-600">⭐ Как повысить рейтинг магазина</h2>
      <p>Рейтинг магазина зависит от 4 факторов:</p>
      <ul class="list-disc pl-6 text-gray-700 space-y-1">
        <li>Средняя оценка по отзывам — 50% рейтинга;</li>
        <li>Скорость обработки заказов — 25%;</li>
        <li>Процент отмен и возвратов — 15%;</li>
        <li>Ответы на сообщения и отзывы — 10%.</li>
      </ul>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 my-6">
        <img src="{{ asset('images/help/reviews-dashboard.jpg') }}" alt="Панель отзывов" class="rounded-2xl shadow-md">
        <img src="{{ asset('images/help/review-statistics.jpg') }}" alt="Статистика рейтинга" class="rounded-2xl shadow-md">
      </div>

      <h3 class="text-lg font-semibold text-gray-800">🟢 Что помогает расти:</h3>
      <ul class="list-disc pl-6 text-gray-700 space-y-1">
        <li>Регулярно обновляйте ассортимент.</li>
        <li>Быстро отвечайте на отзывы и чаты.</li>
        <li>Держите процент возвратов ниже 3%.</li>
      </ul>

      <h3 class="text-lg font-semibold text-gray-800">🔴 Что снижает рейтинг:</h3>
      <ul class="list-disc pl-6 text-gray-700 space-y-1">
        <li>Невежливые или отсутствующие ответы на отзывы.</li>
        <li>Долгая отправка (3+ дней).</li>
        <li>Несоответствие описания и фото.</li>
      </ul>

      <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4 text-sm text-yellow-800">
        ⚠️ <strong>Важно:</strong> рейтинг обновляется ежедневно — любые улучшения быстро отражаются на выдаче.
      </div>

      <h2 class="text-2xl font-semibold text-indigo-600">📈 Как получать больше отзывов</h2>
      <ul class="list-disc pl-6 text-gray-700 space-y-2">
        <li>Кладите в посылку открытку с QR-кодом на страницу товара.</li>
        <li>Пишите клиенту: “Ваш отзыв помогает нам развиваться”.</li>
        <li>Предлагайте бонусы за отзыв (скидка, подарок).</li>
      </ul>

      <img src="{{ asset('images/help/review-card-example.jpg') }}" alt="Открытка с QR-кодом" class="rounded-2xl shadow-md my-6">

      <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-sm text-indigo-800">
        💡 <strong>Совет:</strong> активные продавцы, получающие 5 отзывов в неделю, растут по выдаче в среднем на 15%.
      </div>

      <blockquote class="border-l-4 border-indigo-500 pl-4 text-gray-600 italic">
        “Ваш рейтинг — это не просто цифра, а доверие покупателей.  
        Отвечайте с уважением, и они вернутся к вам снова.”
      </blockquote>

      <div class="relative aspect-video rounded-2xl overflow-hidden shadow-md my-6">
        <iframe class="w-full h-full"
                src="https://www.youtube.com/embed/aqz-KE-bpKQ"
                title="Как работать с отзывами и рейтингом"
                frameborder="0" allowfullscreen></iframe>
      </div>

    </article>

    <!-- 🔙 Навигация -->
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
