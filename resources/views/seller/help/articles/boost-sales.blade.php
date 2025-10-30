<x-seller-layout :title="$news['title']">


  <section class="pt-2 pb-10 space-y-10 px-4 sm:px-6 lg:px-8">

  @php
    $slug = last(explode('/', $news['url']));
    $image = asset("images/help/{$slug}.webp");
  @endphp

    <!-- 🖼️ Обложка -->
    <div class="relative rounded-3xl overflow-hidden shadow-md border border-gray-100 mb-10">
      <img src="{{ $image }}" alt="Как повысить продажи" class="w-full h-64 sm:h-96 object-cover">
      <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></div>
      <div class="absolute bottom-5 left-8 text-white">
        <h1 class="text-3xl sm:text-4xl font-bold drop-shadow-lg">{{ $news['title'] }}</h1>
        <p class="text-sm text-gray-200 mt-1">{{ $news['date'] }}</p>
      </div>
    </div>

    <!-- 📄 Контент -->
    <article class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-10 leading-relaxed text-gray-800 space-y-8">

      <p class="text-lg">
        Успех продаж на <strong>WebVitrina</strong> зависит не только от цены, но и от того, как вы
        представляете свой товар, работаете с клиентами и анализируете результаты.
        В этом материале — проверенные шаги, которые помогут увеличить обороты на площадке.
      </p>

      <h2 class="text-2xl font-semibold text-indigo-600">🔹 1. Улучшите карточки товаров</h2>
      <p>
        Карточка товара — это ваш главный продавец.  
        По данным внутренней аналитики одного из немало известных маркетплейсов, 73% отказов от покупки происходят
        из-за слабого описания или плохих фото.
      </p>

      <ul class="list-disc pl-6 space-y-2 text-gray-700">
        <li>Добавьте минимум 3–5 фото: общий вид, детали, упаковка.</li>
        <li>Используйте белый фон — он повышает CTR (CTR - Click-Through Rate, отношение кликов к показам) в выдаче на 12%.</li>
        <li>Опишите выгоды: “не ржавеет”, “удобно держать одной рукой”, “экономит место”.</li>
        <li>Разбейте описание на короткие абзацы с подзаголовками.</li>
      </ul>

      <div class="bg-green-50 border border-green-100 rounded-xl p-4 text-sm text-green-800">
        💡 <strong>Совет:</strong> добавляйте в название товара ключевые слова (“часы мужские стальные водонепроницаемые”) — 
        это повысит позиции в поиске без рекламы.
      </div>

      <img src="{{ asset('images/help/product-card-example.jpg') }}" alt="Пример карточки товара" class="rounded-2xl shadow-md my-6">

      <h2 class="text-2xl font-semibold text-indigo-600">🔹 2. Оптимизируйте цены и акции</h2>
      <p>
        Цена — один из главных факторов кликабельности.  
        Используйте функции “Сравнить с конкурентами” и “Рекомендованная цена” в личном кабинете.
      </p>
      <ul class="list-disc pl-6 text-gray-700 space-y-2">
        <li>Проверяйте, чтобы ваша цена была в топ-30% в категории.</li>
        <li>Создавайте краткосрочные скидки — даже -5% заметно повышают продажи.</li>
        <li>Комбинируйте акцию со скидкой на второй товар — “2 по цене 1”.</li>
      </ul>

      <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5 text-sm text-indigo-800">
        💡 <strong>Совет:</strong> скидки с таймером (“до конца дня”) дают рост продаж на 20–25%.
      </div>

      <h2 class="text-2xl font-semibold text-indigo-600">🔹 3. Анализируйте статистику</h2>
      <p>
        В разделе <strong>“Аналитика продавца”</strong> можно увидеть, какие товары чаще добавляют в избранное или корзину,
        но не покупают. Это сигнал: стоит обновить фото, цену или описание.
      </p>

      <img src="{{ asset('images/help/analytics-dashboard.webp') }}" alt="Аналитика WebVitrina" class="rounded-2xl shadow-md my-6">

      <ul class="list-disc pl-6 text-gray-700 space-y-2">
        <li>Следите за динамикой просмотров за 7 и 30 дней.</li>
        <li>Отслеживайте CTR (отношение просмотров к покупкам) — он должен быть выше 3%.</li>
        <li>Проверяйте, с каких регионов идут заказы — настройте акции под конкретные города.</li>
      </ul>

      <h2 class="text-2xl font-semibold text-indigo-600">🔹 4. Работайте с отзывами</h2>
      <p>
        Отзывы влияют не только на репутацию, но и на позиции в поиске.  
        Даже один новый отзыв в неделю повышает доверие и рейтинг магазина.
      </p>

      <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4 text-sm text-yellow-800">
        💡 <strong>Совет:</strong> предложите клиенту скидку на следующий заказ за отзыв — это увеличит обратную связь на 40%.
      </div>

      <h2 class="text-2xl font-semibold text-indigo-600">🔹 5. Оформите бренд магазина</h2>
      <p>
        Магазины с логотипом, баннером и описанием продают в среднем на 35% больше.  
        Используйте брендирование для узнаваемости.
      </p>

      <ul class="list-disc pl-6 text-gray-700 space-y-2">
        <li>Добавьте логотип и короткое описание “О нас”.</li>
        <li>Загрузите баннер с акцией или новинкой.</li>
        <li>Выделите ваши преимущества — доставка, гарантия, оригинальность.</li>
      </ul>

      <img src="{{ asset('images/help/shop-banner-example.jpg') }}" alt="Пример брендированного магазина" class="rounded-2xl shadow-md my-6">

      <h2 class="text-2xl font-semibold text-indigo-600">🔹 6. Продвигайте товары за пределами площадки</h2>
      <p>
        Размещайте ссылки на WebVitrina в соцсетях, мессенджерах и на визитках — 
        это не только увеличит продажи, но и улучшит органический рейтинг.
      </p>
      <ul class="list-disc pl-6 text-gray-700 space-y-1">
        <li>Делитесь новинками в Instagram и Telegram.</li>
        <li>Добавляйте QR-код с ссылкой на ваш магазин в упаковку.</li>
        <li>Запускайте короткие видеообзоры товаров.</li>
      </ul>

      <blockquote class="border-l-4 border-indigo-500 pl-4 text-gray-600 italic">
        “Каждый товар может продаваться лучше — главное знать, что именно улучшить.”
      </blockquote>

            <div class="relative aspect-video rounded-2xl overflow-hidden shadow-md my-6">
        <iframe class="w-full h-full" src="https://www.youtube.com/embed/aqz-KE-bpKQ"
                title="Как работать с отзывами и рейтингом"
                frameborder="0" allowfullscreen></iframe>
      </div>

    </article>

    <!-- 🔙 Навигация -->
    <div class="mt-10 flex items-center justify-between text-sm text-gray-500">
      <a href="{{ route('seller.cabinet') }}" class="hover:text-indigo-600 flex items-center gap-1">
        <i class="ri-arrow-left-line"></i> К панели продавца
      </a>
      <a href="{{ route('seller.help', ['slug' => 'product-optimization']) }}" class="hover:text-indigo-600 flex items-center gap-1">
        Следующая статья <i class="ri-arrow-right-line"></i>
      </a>
    </div>


  </section>

</x-seller-layout>
