<x-buyer-layout title="Избранное">

  @php
      $addedId = (int) session('cart_added_id');
  @endphp

  <div class="space-y-10">

    <!-- 🔝 Заголовок -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">💖 Избранное</h1>
        <p class="text-gray-500 text-sm">
          Все понравившиеся вами товары. Можно добавить в корзину или купить сразу.
        </p>
      </div>

      @if($items->isNotEmpty())
        <a href="{{ route('cart.index') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium
                  rounded-xl shadow hover:bg-indigo-700 transition-all">

          <div class="relative">
            <i class="ri-shopping-cart-2-line text-lg" data-cart-icon></i>
<span data-cart-count
      class="absolute -top-1.5 -right-2 bg-red-600 text-white
             text-[10px] font-bold px-1.5 py-0.5 rounded-full
             opacity-0 transition-all duration-200 pointer-events-none">
</span>

          </div>

          Перейти в корзину
        </a>
      @endif
    </div>

    @if($items->isEmpty())
      <!-- 🕊 Пустое состояние -->
      <div class="text-center py-28">
        <div class="text-7xl mb-5 opacity-80">🛍️</div>
        <p class="text-lg font-semibold text-gray-800">У вас пока нет избранных товаров</p>
        <p class="text-sm text-gray-500 mt-1">
          Добавляйте понравившиеся товары, чтобы вернуться к ним позже.
        </p>

        <a href="{{ route('home') }}"
           class="mt-8 inline-block bg-indigo-600 text-white px-8 py-3.5 rounded-xl shadow hover:bg-indigo-700 transition">
          Перейти в каталог
        </a>
      </div>

    @else
      <!-- 🛍 Список избранного -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-7">
        @foreach($items as $f)
          @php $p = $f->product; @endphp

          <div class="fav-card bg-white rounded-2xl border border-gray-100 shadow-sm
                      flex flex-col group overflow-hidden"
               data-fav-card data-id="{{ $p->id }}">

            <!-- Фото -->
            <a href="{{ route('product.show', $p) }}"
               class="relative aspect-square bg-gray-50 overflow-hidden">

              @if($p->image)
                <img src="{{ asset('storage/'.$p->image) }}"
                     alt="{{ $p->title }}"
                     class="w-full h-full object-cover transition duration-500 group-hover:scale-110" />
              @else
                <div class="flex items-center justify-center h-full text-gray-300 text-4xl">🛒</div>
              @endif

              <!-- ❤️ Удалить из избранного (сердце) -->
              <form method="POST" action="{{ route('favorites.toggle', $p) }}"
                    class="absolute top-2 right-2 z-10 fav-toggle-heart">
                @csrf
                <button
                  class="w-9 h-9 flex items-center justify-center bg-white/90 backdrop-blur-md
                         rounded-full shadow-md hover:bg-red-100 transition">
                  <i class="ri-heart-fill text-red-500 text-lg"></i>
                </button>
              </form>

              {{-- 🟢 Мини-плашка "В корзине" --}}
              @if($addedId === (int) $p->id)
                <div class="in-cart-badge">В корзине</div>
              @endif

            </a>

            <!-- Информация -->
            <div class="flex-1 flex flex-col p-4">

              <a href="{{ route('product.show', $p) }}"
                 class="text-sm font-medium text-gray-900 hover:text-indigo-600 line-clamp-2 min-h-[40px] mb-2">
                {{ $p->title }}
              </a>

              @if($p->price)
                <p class="text-lg font-semibold text-gray-900 mb-4">
                  {{ number_format($p->price, 2, ',', ' ') }} ₽
                </p>
              @else
                <p class="text-sm text-gray-400 mb-4">Нет в наличии</p>
              @endif

              <!-- Кнопки -->
              <div class="mt-auto flex flex-col gap-2">

                <!-- Добавить в корзину -->
                <form method="POST"
                      action="{{ route('cart.add', $p->id) }}"
                      class="js-add-to-cart-form">
                  @csrf
                  <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-semibold
                           bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    <i class="ri-shopping-cart-line text-sm"></i>
                    В корзину
                  </button>
                </form>

                <!-- Купить сейчас -->
                <form method="POST" action="{{ route('checkout.quick', $p->id) }}">
                  @csrf
                  <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-medium
                           border border-gray-200 text-gray-700 rounded-lg hover:border-indigo-400 hover:text-indigo-600 transition">
                    <i class="ri-flashlight-line text-sm"></i>
                    Купить сейчас
                  </button>
                </form>

                <!-- Удалить (fade-out карточка) -->
                <form method="POST"
                      action="{{ route('favorites.toggle', $p) }}"
                      class="js-fav-remove-form">
                  @csrf
                  <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-medium
                           text-gray-500 border border-gray-200 rounded-lg
                           hover:text-red-600 hover:border-red-400 transition">
                    <i class="ri-delete-bin-6-line text-sm"></i>
                    Удалить
                  </button>
                </form>

              </div>

            </div>
          </div>

        @endforeach
      </div>
    @endif
  </div>


  <!-- =============================================== -->
  <!-- ⚡ SCRIPTS (всё собрано в один чистый блок) -->
  <!-- =============================================== -->
  <script>
document.addEventListener('DOMContentLoaded', () => {

    /* ===============================
       🔥 Toast уведомление
    =============================== */
    function showToast(text, type = 'success') {
      const el = document.createElement('div');
      el.className = 'toast ' + (type === 'error' ? 'toast-error' : '');
      el.innerText = text;

      document.body.appendChild(el);
      setTimeout(() => el.style.opacity = '0', 2000);
      setTimeout(() => el.remove(), 2400);
    }

    @if(session('success'))
      showToast("{{ session('success') }}");
    @endif

    /* ===============================
       ➕ +1 над кнопкой
    =============================== */
    function showPlusOne(btn) {
        const plus = document.createElement('span');
        plus.className = 'plus-one';
        plus.innerText = '+1';
        btn.style.position = 'relative';
        btn.appendChild(plus);
        setTimeout(() => plus.remove(), 300);
    }

    /* ===============================
       🧲 Перелёт товара в корзину
    =============================== */
    function flyToCart(img, cartIcon) {
        const clone = img.cloneNode(true);
        const start = img.getBoundingClientRect();
        const end = cartIcon.getBoundingClientRect();

        clone.style.position = 'fixed';
        clone.style.left = start.left + 'px';
        clone.style.top = start.top + 'px';
        clone.style.width = start.width + 'px';
        clone.style.height = start.height + 'px';
        clone.style.borderRadius = '12px';
        clone.style.zIndex = 9999;
        clone.style.transition = 'all .7s cubic-bezier(.25,.46,.45,.94)';

        document.body.appendChild(clone);

        setTimeout(() => {
            clone.style.left = end.left + 'px';
            clone.style.top = end.top + 'px';
            clone.style.width = '20px';
            clone.style.height = '20px';
            clone.style.opacity = '0.3';
            clone.style.transform = 'scale(0.4)';
        }, 20);

        setTimeout(() => clone.remove(), 750);
    }

    /* ===============================
       🔢 Обновление счётчика
    =============================== */
function updateCartCount() {
    fetch("/cart-count")
        .then(r => r.json())
        .then(data => {
            const badge = document.querySelector("[data-cart-count]");
            if (!badge) return;

            if (data.count > 0) {
                badge.classList.remove("hidden");
                badge.textContent = data.count;
            } else {
                badge.classList.add("hidden");
            }
        });
}

updateCartCount();

    /* ===============================
       🛍 Добавление в корзину
    =============================== */
    document.querySelectorAll('.js-add-to-cart-form').forEach(form => {

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const btn = this.querySelector('button');
            const card = this.closest('[data-fav-card]');
            const img  = card.querySelector('img');
            const cartIcon = document.querySelector('[data-cart-icon]');

            // spinner
            btn.classList.add('loading');
            btn.disabled = true;

            // +1
            showPlusOne(btn);

            // подсветка
            card.classList.add('card-added');
            setTimeout(() => card.classList.remove('card-added'), 1200);

            // перелёт
            if (img && cartIcon) flyToCart(img, cartIcon);

            // ✓ В корзине
            const old = btn.innerHTML;
            btn.innerHTML = '<i class="ri-check-line"></i> В корзине';

            setTimeout(() => {
                btn.innerHTML = old;
                btn.classList.remove('loading');
                btn.disabled = false;
            }, 1200);

            // отправка
            setTimeout(() => {
                this.submit();
                updateCartCount();
            }, 250);
        });
    });

    /* ===============================
       🗑 Fade-out удаление из избранного
    =============================== */
    document.querySelectorAll('.js-fav-remove-form').forEach(form => {

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const card = this.closest('[data-fav-card]');
            card.classList.add('fav-removing');

            setTimeout(() => this.submit(), 200);
        });
    });

});
  </script>


  <!-- =============================================== -->
  <!-- 🎨 СТИЛИ -->
  <!-- =============================================== -->
  <style>
    /* Apple shadow */
    .fav-card {
      transition: .3s ease;
      box-shadow: 0 18px 48px rgba(0,0,0,.06);
    }
    .fav-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 28px 70px rgba(0,0,0,.11);
    }

    /* Подсветка */
    .card-added {
      outline: 3px solid #4ADE80;
      outline-offset: -3px;
    }

    /* Fade-out */
    .fav-removing {
      opacity: 0 !important;
      transform: translateY(8px) scale(.97);
      transition: .25s ease;
    }

    /* Badge "В корзине" */
    .in-cart-badge {
      position: absolute;
      bottom: 0.7rem;
      left: 0.7rem;
      background: linear-gradient(135deg,#16a34a,#22c55e);
      color: #fff;
      font-size: 11px;
      padding: 3px 10px;
      border-radius: 9999px;
      box-shadow: 0 8px 18px rgba(16,185,129,.4);
      font-weight: 600;
    }

    /* Toast */
    .toast {
      position: fixed;
      right: 20px;
      top: 20px;
      padding: 12px 16px;
      background: #fff;
      border-left: 4px solid #6366f1;
      border-radius: 10px;
      box-shadow: 0 10px 25px rgba(0,0,0,.12);
      font-size: 14px;
      animation: fadeSlide .4s;
      z-index: 99999;
    }
    .toast-error {
      border-left-color: #dc2626;
    }
    @keyframes fadeSlide {
      from { opacity: 0; transform: translateY(-10px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* +1 */
    .plus-one {
      position: absolute;
      right: 18px;
      top: 6px;
      font-size: 12px;
      font-weight: 700;
      color: #bbf7d0;
      text-shadow: 0 0 8px rgba(22,163,74,.9);
      animation: plusMove .25s ease-out forwards;
    }
    @keyframes plusMove {
      from { opacity: 0; transform: translateY(6px) scale(.9); }
      to   { opacity: 1; transform: translateY(-6px) scale(1.1); }
    }

    /* Spinner */
    button.loading {
      color: transparent !important;
      position: relative;
    }
    button.loading::after {
      content: "";
      position: absolute;
      width: 14px;
      height: 14px;
      border: 2px solid white;
      border-top-color: transparent;
      border-radius: 50%;
      animation: spin .6s linear infinite;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>

</x-buyer-layout>
