{{-- resources/views/shop/product-show.blade.php --}}
<x-app-layout :title="$product->title">

<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

  <div class="grid md:grid-cols-2 gap-10">

    {{-- ====== Левая колонка: фото товара ====== --}}
    <div x-data="{ activeImage: '{{ $product->image ? asset('storage/'.$product->image) : '' }}' }" 
         class="flex flex-col md:flex-row gap-4">

      {{-- Галерея миниатюр --}}
      <div class="flex md:flex-col gap-2 md:w-20">
        @if($product->image)
          <img src="{{ asset('storage/'.$product->image) }}"
               class="w-20 h-20 object-cover rounded border cursor-pointer"
               :class="{ 'ring-2 ring-indigo-600': activeImage === '{{ asset('storage/'.$product->image) }}' }"
               @click="activeImage='{{ asset('storage/'.$product->image) }}'">
        @endif
        @foreach($product->gallery ?? [] as $img)
          <img src="{{ asset('storage/'.$img) }}"
               class="w-20 h-20 object-cover rounded border cursor-pointer"
               :class="{ 'ring-2 ring-indigo-600': activeImage === '{{ asset('storage/'.$img) }}' }"
               @click="activeImage='{{ asset('storage/'.$img) }}'">
        @endforeach
      </div>

      {{-- Главное фото --}}
      <div class="flex-1 bg-white rounded-xl border p-3 flex items-center justify-center">
        <template x-if="activeImage">
          <img :src="activeImage" class="max-h-[500px] w-auto rounded-lg"/>
        </template>
      </div>
    </div>

    {{-- ====== Правая колонка: информация ====== --}}
    <div>
      {{-- Хлебные крошки --}}
      <nav class="mb-2 text-sm text-gray-600">
        <a href="{{ route('home') }}" class="hover:underline">Главная</a>
        @if($product->category)
          @php
            $breadcrumbs = [];
            $cat = $product->category;
            while ($cat) {
                $breadcrumbs[] = $cat;
                $cat = $cat->parent;
            }
            $breadcrumbs = array_reverse($breadcrumbs);
          @endphp
          @foreach($breadcrumbs as $cat)
            › <a href="{{ route('category.show', $cat->slug) }}" class="hover:underline">{{ $cat->name }}</a>
          @endforeach
        @endif
      </nav>

{{-- Страна и город --}}
@if($product->city || $product->country)
  <div class="mt-6 bg-white border rounded-xl p-4">
    <div class="text-sm text-gray-500">Местоположение</div>
    <div class="font-medium text-gray-800">
      {{-- если у товара указана страна напрямую --}}
      @if($product->country)
        {{ $product->country->name }}
      {{-- если нет, то пробуем взять страну из города --}}
      @elseif($product->city && $product->city->country)
        {{ $product->city->country->name }}
      @else
        —
      @endif

      @if($product->city)
        , {{ $product->city->name }}
      @endif
    </div>
  </div>
@endif




      {{-- Название --}}
      <h1 class="text-3xl font-bold">{{ $product->title }}</h1>

      {{-- Рейтинг и заказы (берём из withAvg и withCount) --}}
      <div class="flex items-center gap-2 text-sm text-gray-600 mt-1">
        ⭐ {{ number_format($product->reviews_avg_rating ?? 0, 1) }}
        <span>· {{ $product->reviews_count }} отзывов</span>
        <span>· {{ $product->orders_count ?? 0 }} заказов</span>
      </div>

      {{-- Цвет --}}
      @if($product->color)
        <div class="mt-4">
          <div class="text-sm text-gray-600">Цвет:</div>
          <div class="flex gap-2 mt-1">
            <div class="w-8 h-8 rounded-full border-2 border-gray-300"
                 style="background: {{ $product->color }}"></div>
          </div>
        </div>
      @endif

      {{-- Размеры --}}
      @if($product->sizes ?? false)
        <div class="mt-4">
          <div class="text-sm text-gray-600">Размер:</div>
          <div class="flex flex-wrap gap-2 mt-1">
            @foreach($product->sizes as $size)
              <button class="px-3 py-1.5 border rounded hover:border-indigo-600">{{ $size }}</button>
            @endforeach
          </div>
        </div>
      @endif

      {{-- Цена + избранное --}}
      <div class="mt-6 bg-white border rounded-xl p-4 flex items-center justify-between">
        <div>
          <div class="text-3xl font-bold text-gray-900">
            {{ number_format($product->price, 0, ',', ' ') }} ₽
          </div>
          @if($product->old_price)
            <div class="text-sm text-gray-500 line-through">
              {{ number_format($product->old_price, 0, ',', ' ') }} ₽
            </div>
          @endif
        </div>

        <form method="post" action="{{ route('favorites.toggle',$product) }}">@csrf
          <button class="w-10 h-10 flex items-center justify-center border rounded-lg hover:bg-gray-50">
            ❤
          </button>
        </form>
      </div>

      {{-- Кнопка купить --}}
      <div class="mt-4">
        @auth
          <form method="post" action="{{ route('cart.add',$product) }}">@csrf
            <button class="w-full py-3 bg-indigo-600 text-white rounded-lg text-lg">Добавить в корзину</button>
          </form>
        @else
          <a href="{{ route('login') }}"
             class="block w-full py-3 bg-indigo-600 text-white rounded-lg text-center text-lg">
             Войти, чтобы купить
          </a>
        @endauth
      </div>

      {{-- Продавец --}}
      @if($product->seller)
        <div class="mt-6 bg-white border rounded-xl p-4">
          <div class="text-sm text-gray-500">Магазин</div>
          <div class="font-medium">{{ $product->seller->name }}</div>

          {{-- Рейтинг продавца (берём из withAvg и withCount) --}}
          <div class="text-sm text-gray-600">
            ⭐ {{ number_format($product->seller->reviews_avg_rating ?? 0, 1) }}
            ({{ $product->seller->reviews_count }} отзывов)
          </div>

          <a href="{{ route('seller.show',$product->seller) }}"
             class="mt-2 inline-block px-4 py-2 border rounded-lg text-sm hover:bg-gray-50">
             Перейти в магазин
          </a>
        </div>
      @endif

    </div>
  </div>

  {{-- ====== Вкладки (описание / размеры / характеристики / отзывы) ====== --}}
  <div class="mt-10" x-data="{ tab: 'desc' }">
    <div class="flex gap-6 border-b">
      <button @click="tab='desc'" :class="tab==='desc' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-600'" class="pb-2">Описание</button>
      <button @click="tab='sizes'" :class="tab==='sizes' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-600'" class="pb-2">Размеры</button>
      <button @click="tab='props'" :class="tab==='props' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-600'" class="pb-2">Характеристики</button>
      <button @click="tab='reviews'" :class="tab==='reviews' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-600'" class="pb-2">
        Отзывы ({{ $product->reviews_count }})
      </button>
    </div>

    <div class="mt-4">
      <div x-show="tab==='desc'">
        <p class="text-gray-700">{{ $product->description }}</p>
      </div>

      <div x-show="tab==='sizes'">
        <p class="text-gray-700">Таблица размеров (сюда можно вывести таблицу из БД).</p>
      </div>

      <div x-show="tab==='props'">
        <ul class="text-gray-700 list-disc pl-5">
          <li>Материал: {{ $product->material ?? 'Хлопок' }}</li>
          <li>Сезон: {{ $product->season ?? 'Всесезон' }}</li>
          <li>Бренд: {{ $product->brand->name ?? '—' }}</li>
        </ul>
      </div>

      <div x-show="tab==='reviews'">
        @auth
          <form method="post" action="{{ route('review.store',$product) }}" class="mb-4">@csrf
            <label class="block text-sm">Оценка</label>
            <select name="rating" class="border rounded p-2 mb-2">
              @for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ $i }}</option>@endfor
            </select>
            <textarea name="body" placeholder="Ваш отзыв" class="w-full border rounded p-2"></textarea>
            <button class="mt-2 px-3 py-1.5 bg-indigo-600 text-white rounded">Отправить</button>
          </form>
        @endauth

        <div class="space-y-3">
          @forelse($product->reviews as $r)
            <div class="bg-white border rounded p-3">
              <div class="font-medium">{{ $r->user->name }} — {{ $r->rating }}★</div>
              <div class="text-gray-700">{{ $r->body }}</div>
            </div>
          @empty
            <p class="text-gray-600">Пока нет отзывов.</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- ====== Похожие товары ====== --}}
  <div class="mt-12">
    <h2 class="text-xl font-semibold mb-4">Похожие товары</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      @foreach($related ?? [] as $item)
        <a href="{{ route('product.show',$item->slug) }}" class="bg-white border rounded-lg p-3 hover:shadow">
          @if($item->image)
            <img src="{{ asset('storage/'.$item->image) }}" class="w-full h-40 object-cover rounded mb-2"/>
          @endif
          <div class="text-sm font-medium line-clamp-2">{{ $item->title }}</div>
          <div class="text-indigo-600 font-semibold mt-1">{{ number_format($item->price,0,',',' ') }} ₽</div>
        </a>
      @endforeach
    </div>
  </div>

</div>
</x-app-layout>
