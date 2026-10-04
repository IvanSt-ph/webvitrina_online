<x-seller-layout :title="$news['title']">

  @php
    $slug = last(explode('/', $news['url']));
    $image = asset("images/help/{$slug}.webp");
  @endphp

  <section class="w-full space-y-6 bg-white px-3 py-4 pb-28 sm:px-6 sm:py-6 sm:pb-28 lg:px-8 lg:py-8 lg:pb-8">

    {{-- 🖼️ Обложка --}}
    <div class="relative mb-6 overflow-hidden rounded-2xl border border-neutral-200">
      <img data-image-fallback="{{ asset('images/image-placeholder.svg') }}" src="{{ $image }}" alt="{{ $news['title'] }}" class="h-56 w-full object-cover sm:h-80">
      <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent"></div>
      <div class="absolute inset-x-0 bottom-0 p-5 text-white sm:p-7">
        <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ $news['title'] }}</h1>
        <p class="mt-1 text-sm text-neutral-200">{{ $news['date'] }}</p>
      </div>
    </div>

    {{-- 📄 Контент --}}
    <article class="space-y-8 rounded-2xl border border-neutral-200 bg-white p-5 leading-relaxed text-neutral-800 sm:p-7 lg:p-8">

      <p class="text-lg">
        Успех продаж на <strong>WebVitrina</strong> зависит не только от цены, но и от того, как вы представляете свой товар,
        работаете с клиентами и анализируете результаты.  
        Ниже — пошаговые советы, которые помогут увеличить ваши обороты.
      </p>

      {{-- Раздел 1 --}}
      <x-help-section icon="ri-focus-2-line" title="1. Улучшите карточки товаров">
        <p>
          Карточка товара — это ваш главный продавец.  
          По статистике маркетплейсов, до 70% отказов от покупки связаны с плохими фото или описанием.
        </p>

        <ul class="list-disc pl-6 space-y-2 text-gray-700 mt-3">
          <li>Добавьте минимум 3–5 фото: общий вид, детали, упаковка.</li>
          <li>Используйте белый фон — он повышает CTR в выдаче на 10–15%.</li>
          <li>Опишите выгоды: «не ржавеет», «удобно держать одной рукой», «экономит место».</li>
          <li>Разбейте описание на короткие абзацы с подзаголовками.</li>
        </ul>

        <x-help-tip color="green" icon="ri-lightbulb-flash-line">
          Добавляйте ключевые слова в заголовок (“часы мужские стальные водонепроницаемые”) —  
          это поднимет товар в поиске без рекламы.
        </x-help-tip>

        <img src="{{ asset('images/image-placeholder.svg') }}" alt="Пример карточки товара" class="rounded-2xl shadow-md mt-6">
      </x-help-section>

      {{-- Раздел 2 --}}
      <x-help-section icon="ri-currency-line" title="2. Оптимизируйте цены и акции">
        <p>
          Цена — один из главных факторов кликабельности.  
          Используйте разделы <strong>“Сравнить с конкурентами”</strong> и <strong>“Рекомендованная цена”</strong> в личном кабинете.
        </p>

        <ul class="list-disc pl-6 text-gray-700 space-y-2 mt-3">
          <li>Поддерживайте цену в пределах топ-30% в категории.</li>
          <li>Создавайте краткосрочные скидки — даже -5% дают ощутимый рост продаж.</li>
          <li>Комбинируйте акции: “2 по цене 1” или “-10% на второй товар”.</li>
        </ul>

        <x-help-tip color="indigo" icon="ri-timer-line">
          Скидки с таймером (“до конца дня”) повышают конверсию на 20–25%.
        </x-help-tip>
      </x-help-section>

      {{-- Раздел 3 --}}
      <x-help-section icon="ri-bar-chart-box-line" title="3. Анализируйте статистику">
        <p>
          В разделе <strong>“Аналитика продавца”</strong> можно увидеть, какие товары чаще добавляют в избранное или корзину, но не покупают.  
          Это сигнал: стоит обновить фото, цену или описание.
        </p>

        <img src="{{ asset('images/help/analytics-dashboard.webp') }}" alt="Аналитика WebVitrina" class="rounded-2xl shadow-md my-6">

        <ul class="list-disc pl-6 text-gray-700 space-y-2">
          <li>Следите за динамикой просмотров за 7 и 30 дней.</li>
          <li>Отслеживайте CTR (соотношение просмотров к покупкам) — он должен быть выше 3%.</li>
          <li>Анализируйте регионы заказов и запускайте акции по локации.</li>
        </ul>
      </x-help-section>

      {{-- Раздел 4 --}}
      <x-help-section icon="ri-chat-1-line" title="4. Работайте с отзывами">
        <p>
          Отзывы влияют и на доверие покупателей, и на позиции в поиске.  
          Даже один новый отзыв в неделю может поднять рейтинг магазина.
        </p>

        <x-help-tip color="yellow" icon="ri-feedback-line">
          Предложите клиенту небольшую скидку на следующий заказ за отзыв — это увеличит обратную связь почти на 40%.
        </x-help-tip>
      </x-help-section>

      {{-- Раздел 5 --}}
      <x-help-section icon="ri-store-2-line" title="5. Оформите бренд магазина">
        <p>
          Магазины с логотипом и баннером продают в среднем на 30–35% больше.  
          Это создаёт доверие и узнаваемость.
        </p>

        <ul class="list-disc pl-6 text-gray-700 space-y-2">
          <li>Добавьте логотип и короткое описание “О нас”.</li>
          <li>Разместите баннер с акцией или новинкой.</li>
          <li>Подчеркните ваши преимущества — доставка, гарантия, оригинальность.</li>
        </ul>

        <img data-image-fallback="{{ asset('images/image-placeholder.svg') }}" src="{{ asset('images/image-placeholder.svg') }}" alt="Бренд магазина" class="rounded-2xl shadow-md my-6">
      </x-help-section>

      {{-- Раздел 6 --}}
      <x-help-section icon="ri-megaphone-line" title="6. Продвигайте товары за пределами площадки">
        <p>
          Размещайте ссылки на WebVitrina в соцсетях, мессенджерах и на визитках.  
          Это повышает узнаваемость бренда и органический трафик.
        </p>

        <ul class="list-disc pl-6 text-gray-700 space-y-1">
          <li>Делитесь новинками в Instagram и Telegram.</li>
          <li>Добавляйте QR-код с ссылкой на ваш магазин в упаковку.</li>
          <li>Публикуйте короткие видеообзоры товаров.</li>
        </ul>

        <blockquote class="border-l-4 border-indigo-500 pl-4 text-gray-600 italic mt-4">
          “Каждый товар может продаваться лучше — главное знать, что именно улучшить.”
        </blockquote>

        <div class="relative aspect-video rounded-2xl overflow-hidden shadow-md my-6">
          <iframe class="w-full h-full" src="https://www.youtube.com/embed/aqz-KE-bpKQ"
                  title="Как работать с отзывами и рейтингом"
                  frameborder="0" allowfullscreen></iframe>
        </div>
      </x-help-section>

    </article>

    {{-- 🔙 Навигация --}}
    <div class="flex items-center justify-between gap-3 border-t border-neutral-200 pt-5 text-sm text-neutral-500">
      <a href="{{ route('seller.cabinet') }}" class="flex items-center gap-1 hover:text-brand-600">
        <i class="ri-arrow-left-line"></i> К панели продавца
      </a>
      <a href="{{ route('seller.help', ['slug' => 'product-optimization']) }}" class="flex items-center gap-1 text-right hover:text-brand-600">
        Следующая статья <i class="ri-arrow-right-line"></i>
      </a>
    </div>

  </section>

</x-seller-layout>
