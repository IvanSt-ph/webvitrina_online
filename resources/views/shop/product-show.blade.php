{{-- resources/views/products/show.blade.php --}}
<x-app-layout :title="$product->title">

  @push('meta')
  <link rel="canonical" href="{{ url()->current() }}" />
@endpush


<div class="w-full max-w-7xl mx-auto pt-20 pb-10 px-4 sm:px-6 lg:px-8 overflow-x-hidden">


  {{-- ====== Основной контейнер ====== --}}
<div class="flex flex-col md:grid md:grid-cols-2 gap-6 bg-white border border-gray-200 rounded-2xl shadow-sm p-4 sm:p-6">





{{-- ====== Левая колонка: фото товара ====== --}}
<div 
  x-data="{
    activeImage: '{{ $product->image ? asset('storage/'.$product->image) : '' }}',
    images: [
      @if($product->image)
        '{{ asset('storage/'.$product->image) }}',
      @endif
      @foreach($product->gallery ?? [] as $img)
        '{{ asset('storage/'.$img) }}',
      @endforeach
    ],
    startIndex: 0,
    visibleCount: 6,
    get canScrollUp() { return this.startIndex > 0 },
    get canScrollDown() { return this.startIndex + this.visibleCount < this.images.length },
    scrollUp() { if (this.canScrollUp) this.startIndex-- },
    scrollDown() { if (this.canScrollDown) this.startIndex++ },
  }"
  class="flex flex-col md:flex-row gap-4 items-start"
>

{{-- 📱 Мобильная версия: главное фото со свайпом --}}
<div x-data="{
      startX: 0,
      handleTouchStart(e) { this.startX = e.touches[0].clientX },
      handleTouchEnd(e) {
        const diff = e.changedTouches[0].clientX - this.startX
        if (Math.abs(diff) > 50) {
          if (diff < 0 && this.images.indexOf(this.activeImage) < this.images.length - 1) {
            this.activeImage = this.images[this.images.indexOf(this.activeImage) + 1]
          } else if (diff > 0 && this.images.indexOf(this.activeImage) > 0) {
            this.activeImage = this.images[this.images.indexOf(this.activeImage) - 1]
          }
        }
      }
    }"
    @touchstart="handleTouchStart($event)"
    @touchend="handleTouchEnd($event)"
    class="md:hidden w-full bg-gray-50 border rounded-xl flex items-center justify-center aspect-square 
           h-[400px] sm:h-[460px] overflow-hidden relative select-none">

  <template x-if="activeImage">
    <img 
      :src="activeImage" 
      class="object-contain w-full h-full transition-transform duration-300 ease-in-out"
    />
  </template>

  {{-- 🔹 Индикаторы (точки) --}}
  <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2">
    <template x-for="(img, i) in images" :key="i">
      <div class="w-2.5 h-2.5 rounded-full"
           :class="activeImage === img ? 'bg-indigo-600' : 'bg-gray-300'"></div>
    </template>
  </div>
</div>


  {{-- 💻 Десктопная версия: миниатюры + стрелки --}}
  <div class="hidden md:flex gap-4 items-start w-full">
    
    {{-- Галерея миниатюр --}}
    <div class="relative flex md:flex-col items-center h-[640px]">

      {{-- 🔼 Стрелка вверх --}}
      <template x-if="canScrollUp && images.length > visibleCount">
        <button 
          @click="scrollUp()" 
          class="absolute -top-4 left-1/2 -translate-x-1/2 bg-white border border-gray-200 rounded-full
                 w-8 h-8 flex items-center justify-center shadow-sm hover:bg-indigo-50 hover:scale-105
                 transition-all duration-200 z-10">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7" />
          </svg>
        </button>
      </template>

      {{-- Миниатюры --}}
      <div class="flex md:flex-col gap-2 overflow-hidden w-full md:w-auto h-full relative">
        <div 
          class="flex md:flex-col gap-2 transition-transform duration-500 ease-in-out"
          :style="{
            transform: `translateY(-${startIndex * 108}px)`
          }">
          <template x-for="(img, i) in images" :key="i">
            <img 
              :src="img"
              @mouseover="activeImage = img"
              @click="activeImage = img"
              class="w-20 h-[6.3rem] object-cover rounded-lg border cursor-pointer transition-all duration-300 ease-out
                     hover:ring-2 hover:ring-indigo-600 hover:scale-[1.05] hover:opacity-90"
              :class="{ 'ring-2 ring-indigo-600': activeImage === img }">
          </template>
        </div>
      </div>

      {{-- 🔽 Стрелка вниз --}}
      <template x-if="canScrollDown && images.length > visibleCount">
        <button 
          @click="scrollDown()" 
          class="absolute -bottom-4 left-1/2 -translate-x-1/2 bg-white border border-gray-200 rounded-full
                 w-8 h-8 flex items-center justify-center shadow-sm hover:bg-indigo-50 hover:scale-105
                 transition-all duration-200 z-10">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
      </template>
    </div>

    {{-- Главное фото --}}
    <div class="flex-1 bg-gray-50 border rounded-xl flex items-center justify-center aspect-square 
                h-[550px] lg:h-[620px] xl:h-[640px] overflow-hidden w-full relative">
      <template x-if="activeImage">
        <img 
          :src="activeImage" 
          class="object-contain w-full h-full transition-transform duration-300 hover:scale-105"
        />
      </template>
    </div>
  </div>
</div>








    {{-- ====== Правая колонка: информация ====== --}}
    <div class="flex flex-col justify-between">

      {{-- Хлебные крошки --}}
      <nav class="mb-3 text-sm text-gray-500">
        <a href="{{ route('home') }}" class="hover:text-indigo-600">Главная</a>
        @if($product->category)
          @php
            $breadcrumbs = [];
            $cat = $product->category;
            while ($cat) { $breadcrumbs[] = $cat; $cat = $cat->parent; }
            $breadcrumbs = array_reverse($breadcrumbs);
          @endphp
          @foreach($breadcrumbs as $cat)
            › <a href="{{ route('category.show', $cat->slug) }}" class="hover:text-indigo-600">{{ $cat->name }}</a>
          @endforeach
        @endif
      </nav>

      {{-- Название --}}
      <h1 class="text-3xl font-bold text-gray-900 leading-tight">{{ $product->title }}</h1>
        {{-- 💵 Цена и скидка --}}
  <div class="flex items-end gap-3">
    

    <div class="text-3xl font-semibold text-gray-900 leading-none">
      {{ number_format($product->price, 0, ',', ' ') }} ₽
    </div>

    @if($product->old_price)
      <div class="text-gray-400 line-through text-base">
        {{ number_format($product->old_price, 0, ',', ' ') }} ₽
      </div>
      <div class="bg-blue-50 text-blue-600 text-xs font-medium px-2 py-0.5 rounded-lg border border-blue-100">
        -{{ round(100 - ($product->price / $product->old_price * 100)) }}%
      </div>
    @endif
  </div>

      {{-- Рейтинг и заказы --}}
<div class="flex items-center gap-2 text-sm text-gray-600 mt-4">

        ⭐ {{ number_format($product->reviews_avg_rating ?? 0, 1) }}
        <span>· {{ $product->reviews_count }} отзывов</span>
        <span>· {{ $product->orders_count ?? 0 }} заказов</span>
      </div>



{{-- 💰 Блок цены и кнопок — стиль WebVitrina --}}
<div class="mt-6  bg-white border border-gray-100 rounded-2xl shadow-sm p-5 space-y-5">



  {{-- 🛍 Кнопки действий --}}
  <div class="flex flex-col gap-3">
    @auth
      {{-- ⚡ Купить сейчас --}}
      <a href="{{ route('checkout.quick', $product) }}" 
         class="w-full py-3 rounded-xl text-base font-semibold text-white text-center
                bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98]
                shadow-sm hover:shadow transition-all duration-200">
        ⚡ Купить сейчас
      </a>

      {{-- 🛒 Добавить в корзину --}}
      <form method="post" action="{{ route('cart.add',$product) }}">@csrf
        <button class="w-full py-3 rounded-xl text-base font-medium text-gray-700 text-center
                       bg-gray-50 hover:bg-gray-100 active:scale-[0.98]
                       border border-gray-200 shadow-sm transition-all duration-200">
          🛒 Добавить в корзину
        </button>
      </form>
    @else
      <a href="{{ route('login') }}" 
         class="w-full py-3 rounded-xl text-base font-semibold text-white text-center
                bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98]
                shadow-sm transition-all duration-200">
        Войти, чтобы купить
      </a>
    @endauth
  </div>

{{-- ❤️ Избранное + Артикул + Копирование --}}
<div 
  x-data="{ copied: false }"
  class="flex items-center justify-between pt-2 border-t mt-2 text-sm text-gray-600 select-none"
>
  {{-- Артикул --}}
  @if($product->sku)
    <div class="flex items-center gap-2">
      <div class="flex items-center gap-1 text-gray-500">
        <span class="text-gray-400">Арт.</span>
        <span class="font-medium text-gray-700" id="sku-value">{{ $product->sku }}</span>
      </div>

      {{-- Кнопка копирования --}}
      <button 
        @click="navigator.clipboard.writeText('{{ $product->sku }}'); copied = true; setTimeout(() => copied = false, 1500)"
        class="text-gray-400 hover:text-indigo-600 transition"
        title="Скопировать артикул">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-2 8h4a2 2 0 002-2v-4a2 2 0 00-2-2h-4a2 2 0 00-2 2v4a2 2 0 002 2z" />
        </svg>
      </button>

      {{-- Уведомление при копировании --}}
      <span 
        x-show="copied"
        x-transition
        class="text-green-600 text-xs font-medium"
      >Скопировано!</span>
    </div>
  @endif

  {{-- Избранное --}}
  <form method="post" action="{{ route('favorites.toggle', $product) }}">@csrf
    <button 
      class="flex items-center gap-1 text-gray-500 hover:text-red-500 font-medium transition"
      title="Добавить в избранное"
    >
      ❤ В избранное
    </button>
  </form>
</div>


</div>







{{-- Продавец --}}
@if($product->seller)
  <div class="mt-8 bg-gray-50 border rounded-xl p-4">
    <div class="text-sm text-gray-500">Магазин</div>
    <div class="font-medium text-gray-900">{{ $product->seller->name }}</div>
    <div class="text-sm text-gray-600">
      ⭐ {{ number_format($product->seller->reviews_avg_rating ?? 0, 2) }}
      ({{ $product->seller->reviews_count }} отзывов)
    </div>
    <a href="{{ route('seller.show', $product->seller) }}"
       class="mt-2 inline-block text-indigo-600 text-sm hover:underline">
       Перейти в магазин →
    </a>
  </div>
@endif


      {{-- Местоположение и карта --}}
      @if($product->city || $product->country || $product->address)
        <div class="mt-8 bg-gray-50 border rounded-xl p-4">
          <div class="text-sm text-gray-500 mb-1">Местоположение</div>
          <div class="font-medium text-gray-800">
            @if($product->country)
              {{ $product->country->name }}
            @elseif($product->city && $product->city->country)
              {{ $product->city->country->name }}
            @endif
            @if($product->city)
              , {{ $product->city->name }}
            @endif
          </div>
          @if($product->address)
            <div class="mt-1 text-gray-700">{{ $product->address }}</div>
          @endif

          @if($product->latitude && $product->longitude)
            <div class="mt-3">
              <div id="map" class="w-full h-56 rounded-lg border"></div>
              <a href="https://www.google.com/maps/search/?api=1&query={{ $product->latitude }},{{ $product->longitude }}"
                 target="_blank"
                 class="mt-2 inline-block text-indigo-600 hover:underline text-sm">
                 📍 Открыть в Google Maps
              </a>
            </div>
          @endif
        </div>
      @endif

    </div>
  </div>



  {{-- ====== Вкладки (описание / размеры / характеристики / отзывы) ====== --}}
<div class="mt-12 bg-white border rounded-2xl shadow-sm p-6"
     x-data="{ tab: 'desc', rating: 0, hoverRating: 0 }"
     x-init="
        const observer = new IntersectionObserver(entries => {
            entries.forEach(el => {
                if (el.isIntersecting) {
                    el.target.classList.add('animate-fade-in-up');
                }
            });
        }, { threshold: 0.1 });
        document.querySelectorAll('.review-card').forEach(c => observer.observe(c));
     ">

  {{-- ====== Навигация вкладок ====== --}}
  <div class="flex flex-wrap gap-6 border-b pb-2">
    <button @click="tab='desc'"
            :class="tab==='desc' ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium' : 'text-gray-600'"
            class="pb-2 transition">Описание</button>
    <button @click="tab='sizes'"
            :class="tab==='sizes' ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium' : 'text-gray-600'"
            class="pb-2 transition">Размеры</button>
    <button @click="tab='props'"
            :class="tab==='props' ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium' : 'text-gray-600'"
            class="pb-2 transition">Характеристики</button>
    <button @click="tab='reviews'"
            :class="tab==='reviews' ? 'border-b-2 border-indigo-600 text-indigo-600 font-medium' : 'text-gray-600'"
            class="pb-2 transition">Отзывы ({{ $product->reviews_count }})</button>
  </div>

  {{-- ====== Контент вкладок ====== --}}
  <div class="mt-6">

    {{-- ===== Описание ===== --}}
    <div x-show="tab==='desc'" x-transition.opacity.duration.400ms>
      <p class="text-gray-700 leading-relaxed">{{ $product->description }}</p>
    </div>

    {{-- ===== Размеры ===== --}}
    <div x-show="tab==='sizes'" x-transition.opacity.duration.400ms>
      <p class="text-gray-700">Таблица размеров (сюда можно вывести таблицу из БД).</p>
    </div>

    {{-- ===== Характеристики ===== --}}
    <div x-show="tab==='props'" x-transition.opacity.duration.400ms>
      <ul class="text-gray-700 list-disc pl-5 space-y-1">
        <li>Материал: {{ $product->material ?? 'Хлопок' }}</li>
        <li>Сезон: {{ $product->season ?? 'Всесезон' }}</li>
        <li>Бренд: {{ $product->brand->name ?? '—' }}</li>
      </ul>
    </div>




    
{{-- ===== Отзывы ===== --}}
<div x-show="tab==='reviews'" x-cloak class="space-y-6" x-transition.opacity.duration.400ms">

  {{-- ===== Форма отзыва ===== --}}
  @auth
  @php
    $myReview = $product->reviews->firstWhere('user_id', auth()->id());
  @endphp

  <div 
    x-data="{ editing: {{ $myReview ? 'false' : 'true' }}, rating: {{ $myReview->rating ?? 0 }}, hoverRating: 0 }"
    class="bg-gray-50 border rounded-2xl p-5 shadow-sm space-y-3"
  >
    {{-- если отзыв уже есть --}}
    <template x-if="!editing">
      <div class="flex justify-between items-center">
        <div>
          <h3 class="text-lg font-semibold text-gray-800">Ваш отзыв</h3>
          <p class="text-gray-700 mt-1">{{ $myReview->body ?? 'Без текста' }}</p>

          {{-- показываем фото, если есть --}}
          @if($myReview && $myReview->images->count())
            <div class="mt-3 flex gap-3 flex-wrap">
              @foreach($myReview->images as $img)
                <a href="{{ asset('storage/'.$img->path) }}" target="_blank">
                  <img src="{{ asset('storage/'.$img->path) }}" 
                       class="w-24 h-24 object-cover rounded-lg border hover:scale-105 transition-transform duration-300">
                </a>
              @endforeach
            </div>
          @endif
        </div>
        <button @click="editing = true"
                class="px-3 py-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition">
          ✏️ Изменить
        </button>
      </div>
    </template>

    {{-- форма создания / редактирования --}}
    <template x-if="editing">
      <form method="post" action="{{ route('review.store',$product) }}" enctype="multipart/form-data" class="space-y-3">
        @csrf
        <h3 class="text-lg font-semibold text-gray-800">
          {{ $myReview ? 'Изменить отзыв' : 'Оставить отзыв' }}
        </h3>

        {{-- Звёзды --}}
        <div class="flex items-center gap-2" @mouseleave="hoverRating = 0">
          @for($i=1;$i<=5;$i++)
            <svg @mouseover="hoverRating={{ $i }}"
                 @click="rating={{ $i }}"
                 :class="{
                    'text-yellow-400 scale-110': {{ $i }} <= (hoverRating || rating),
                    'text-gray-300': {{ $i }} > (hoverRating || rating)
                 }"
                 class="w-8 h-8 cursor-pointer transition-all duration-200 transform"
                 fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.945a1 1 0 00.95.69h4.148c.969 0 1.371 1.24.588 1.81l-3.357 2.44a1 1 0 00-.364 1.118l1.286 3.945c.3.921-.755 1.688-1.54 1.118l-3.357-2.44a1 1 0 00-1.175 0l-3.357 2.44c-.784.57-1.839-.197-1.54-1.118l1.286-3.945a1 1 0 00-.364-1.118L2.075 9.372c-.783-.57-.38-1.81.588-1.81h4.148a1 1 0 00.95-.69l1.286-3.945z" />
            </svg>
          @endfor
          <input type="hidden" name="rating" :value="rating">
        </div>

        {{-- Поле текста --}}
        <textarea name="body" rows="3"
                  placeholder="Поделитесь своими впечатлениями..."
                  class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none">{{ $myReview->body ?? '' }}</textarea>

        {{-- Фото --}}
        <input type="file" name="images[]" multiple accept="image/*"
               class="block w-full text-sm text-gray-600 border rounded-lg p-2 cursor-pointer hover:border-indigo-500 transition">
        <p class="text-xs text-gray-400 mt-1">Можно добавить до 3 фото</p>

        <div class="flex justify-between items-center">
          <button type="submit"
                  class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow transition">
            💾 {{ $myReview ? 'Сохранить изменения' : 'Отправить' }}
          </button>
          @if($myReview)
            <button type="button" @click="editing = false" 
                    class="text-sm text-gray-500 hover:text-gray-700">Отмена</button>
          @endif
        </div>
      </form>
    </template>
  </div>
  @endauth

  {{-- ===== Список отзывов ===== --}}
  <div class="space-y-4">
    @forelse($product->reviews as $r)
      <div class="review-card opacity-0 translate-y-6 bg-white border rounded-2xl p-4 shadow-sm hover:shadow-md transition">
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
              {{ mb_substr($r->user->name, 0, 1) }}
            </div>
            <div>
              <div class="font-medium text-gray-800">{{ $r->user->name }}</div>
              <div class="flex text-yellow-400 text-sm">
                @for($i=1;$i<=5;$i++)
                  <span class="{{ $i <= $r->rating ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                @endfor
              </div>
            </div>
          </div>
          <div class="text-xs text-gray-500">
            {{ $r->created_at->diffForHumans() }}
          </div>
        </div>

        <div class="text-gray-700 leading-relaxed border-t pt-2">
          {{ $r->body }}
        </div>

        {{-- Фото под отзывом --}}
        @if($r->images->count())
          <div class="mt-3 flex gap-3 flex-wrap">
            @foreach($r->images as $img)
              <a href="{{ asset('storage/'.$img->path) }}" target="_blank">
                <img src="{{ asset('storage/'.$img->path) }}" 
                     class="w-24 h-24 object-cover rounded-lg border hover:scale-105 transition-transform duration-300">
              </a>
            @endforeach
          </div>
        @endif
      </div>
    @empty
      <div class="text-center text-gray-500 py-10">
        <p class="text-lg">Пока нет отзывов 😌</p>
        <p class="text-sm mt-1">Станьте первым, кто поделится мнением о товаре!</p>
      </div>
    @endforelse
  </div>
</div>


{{-- Анимация появления карточек отзывов --}}
<style>
@keyframes fade-in-up {
  0% { opacity: 0; transform: translateY(12px); }
  100% { opacity: 1; transform: translateY(0); }
}
.animate-fade-in-up {
  animation: fade-in-up 0.6s ease forwards;
}
svg {
  transition: transform 0.2s ease, color 0.2s ease;
}
svg:hover {
  transform: scale(1.15);
}

  /* 🧭 Исправление наложения карты */
  #map,
  #map * {
    z-index: 0 !important;
  }

  /* если хочешь — оставь подписи Leaflet видимыми */
  #map .leaflet-control-container {
    z-index: 1 !important;
  }
</style>


  
  {{-- ====== Похожие товары ====== --}}
  <div class="mt-12">
    <h2 class="text-xl font-semibold mb-4">Похожие товары</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
      @foreach($related ?? [] as $item)
        <a href="{{ route('product.show',$item->slug) }}" 
           class="bg-white border rounded-xl p-3 hover:shadow-lg transition group">
          @if($item->image)
            <img src="{{ asset('storage/'.$item->image) }}" 
                 class="w-full h-48 object-cover rounded-lg mb-2 group-hover:scale-105 transition-transform duration-300"/>
          @endif
          <div class="text-sm font-medium line-clamp-2">{{ $item->title }}</div>
          <div class="text-indigo-600 font-semibold mt-1">{{ number_format($item->price,0,',',' ') }} ₽</div>
        </a>
      @endforeach
    </div>
  </div>

</div>

{{-- Leaflet --}}
@if($product->latitude && $product->longitude)
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <style>
    #map .leaflet-control-attribution {
      font-size: 11px !important;
      color: #666 !important;
      background: rgba(255,255,255,.8) !important;
      border-radius: 6px !important;
      padding: 2px 6px !important;
    }
  </style>

  <script>
  document.addEventListener("DOMContentLoaded", function () {
    const lat = {{ $product->latitude }};
    const lng = {{ $product->longitude }};
    const map = L.map('map').setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    map.attributionControl.setPrefix(false);
    map.attributionControl.setPosition('bottomleft');
    L.marker([lat, lng]).addTo(map).bindPopup("{{ addslashes($product->title) }}");
  });
  </script>
@endif

@if(session('success'))
  <div 
    x-data="{ show: true }" 
    x-show="show"
    x-transition.duration.500ms
    x-init="setTimeout(() => show = false, 3000)"
    class="fixed bottom-6 right-6 z-50 bg-green-600 text-white px-5 py-3 rounded-xl shadow-lg flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg>
    <span>{{ session('success') }}</span>
  </div>
@endif



</x-app-layout>
