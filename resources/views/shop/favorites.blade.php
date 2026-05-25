<x-buyer-layout title="Избранное">

  @php
      $addedId = (int) session('cart_added_id');
      $favoritesTotal = $items->sum(function ($item) {
          $product = $item->product;
          return $product ? ($product->price_for_current_currency['amount'] ?? 0) : 0;
      });
      $discountTotal = $items->sum(function ($item) {
          $product = $item->product;

          return $product && isset($product->old_price) && $product->old_price
              ? max(0, $product->old_price - $product->price)
              : 0;
      });
      $currencySymbol = \App\Models\Product::currencySymbol(session('currency', 'PRB'));
  @endphp

  <div class="favorites-mobile-safe w-full max-w-none overflow-x-hidden px-3 py-4 pb-[5.5rem] sm:px-6 sm:py-8 sm:pb-8">

    {{-- Header --}}
    <div class="mb-6 sm:mb-10">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm">
            <i class="ri-heart-3-line text-xl"></i>
          </div>
          <div>
          <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-gray-900">Избранное</h1>
          <p class="text-gray-500 text-xs sm:text-sm mt-0.5">{{ $items->isNotEmpty() ? $items->count() . ' сохранённых товара' : 'пусто' }}</p>
          </div>
        </div>

        @if($items->isNotEmpty())
        <div class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto sm:items-center">
          <form method="POST" action="{{ route('cart.addFavorites') }}" class="js-add-all-to-cart-form min-w-0">
            @csrf
            <x-action-button>
              <i class="ri-shopping-cart-2-line"></i>
              Добавить всё
            </x-action-button>
          </form>

          <x-secondary-action as="a" href="{{ route('cart.index') }}">
            <div class="relative">
              <i class="ri-shopping-cart-line text-sm sm:text-base" data-cart-icon></i>
              <span data-cart-count
                    class="absolute -top-1.5 -right-2 bg-amber-500 text-white text-[9px] font-bold min-w-[16px] h-4 px-1 rounded-full hidden items-center justify-center">
              </span>
            </div>
            <span>Корзина</span>
          </x-secondary-action>
        </div>
        @endif
      </div>
    </div>

    @if($items->isEmpty())

      <x-empty-state
        icon="ri-heart-3-line"
        title="Здесь пока пусто"
        description="Сохраняйте понравившиеся товары, чтобы быстро вернуться к ним."
      >
        <a href="{{ route('home') }}"
           class="relative overflow-hidden group inline-flex items-center justify-center gap-2 px-6 py-2.5 sm:px-8 sm:py-3 bg-indigo-500/90 hover:bg-indigo-600 text-white text-sm font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 backdrop-blur-sm border border-indigo-400/30">
          <span class="relative z-10 flex items-center gap-2">
            <i class="ri-arrow-left-s-line"></i>
            <span>В каталог</span>
          </span>
          <span class="absolute inset-0 bg-indigo-600 translate-y-full group-hover:translate-y-0 transition-transform duration-300"></span>
        </a>
      </x-empty-state>

    @else

      {{-- Favorites list --}}
      <div class="min-w-0 space-y-2 sm:space-y-3">
        @foreach($items as $f)
          @php
            $p = $f->product;
          @endphp
          @continue(! $p)
          @php
            $shortProductTitle = Str::limit($p->title, 18);
            $currentPrice = $p->price_for_current_currency;
            $price = $currentPrice['amount'] ?? $p->price;
            $itemCurrencySymbol = $currentPrice['symbol'] ?? $currencySymbol;
          @endphp

          <div class="fav-card group min-w-0 overflow-hidden bg-white rounded-xl sm:rounded-2xl border border-gray-100 transition-all duration-200 hover:shadow-md hover:border-gray-200"
               data-fav-card data-id="{{ $p->id }}">

            {{-- Мобильная версия: фото 50px, справа название+цена, внизу кнопки --}}
            <div class="block sm:hidden">
              <div class="p-3">
                {{-- Верхняя строка: фото + информация --}}
                <div class="grid min-w-0 grid-cols-[48px_minmax(0,1fr)_auto] gap-3">
                  {{-- Фото 50x50 --}}
                  <a href="{{ route('product.show', $p) }}"
                     class="relative flex-shrink-0 w-12 h-12 rounded-lg overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-100">
                    @if($p->image)
                      <img src="{{ $p->image_thumb_url }}"
                           class="w-full h-full object-cover"
                           alt="{{ $p->title }}">
                    @else
                      <div class="w-full h-full flex items-center justify-center text-lg text-gray-300">
                        <i class="ri-image-line"></i>
                      </div>
                    @endif

                    @if($addedId === (int) $p->id)
                      <div class="absolute -top-1 -right-1 bg-emerald-500 text-white text-[8px] font-medium px-1 py-0.5 rounded-full">
                        ✓
                      </div>
                    @endif
                  </a>

                  {{-- Название и цена --}}
                  <div class="flex-1 min-w-0">
                    <a href="{{ route('product.show', $p) }}"
                       class="text-sm font-medium text-gray-800 hover:text-indigo-600 transition line-clamp-2 break-words leading-snug"
                       style="overflow-wrap: anywhere;">
                      {{ $shortProductTitle }}
                    </a>
                    <div class="mt-1">
                      @if(isset($p->old_price) && $p->old_price)
                        <span class="text-[9px] text-gray-400 line-through mr-1">
                          {{ number_format($p->old_price, 0, ',', ' ') }} {{ $itemCurrencySymbol }}
                        </span>
                      @endif
                      <span class="text-sm font-bold text-gray-900">
                        {{ number_format($price, 0, ',', ' ') }}
                      </span>
                  <span class="text-[9px] text-gray-400">{{ $itemCurrencySymbol }}</span>
                      <span class="ml-1 text-[10px] text-gray-400">за шт.</span>
                    </div>
                  </div>

                  {{-- Discount badge если есть --}}
                  @if(isset($p->discount_percent) && $p->discount_percent)
                    <div class="flex-shrink-0">
                      <span class="bg-rose-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full">
                        -{{ $p->discount_percent }}%
                      </span>
                    </div>
                  @endif
                </div>

                {{-- Кнопки под информацией на всю ширину --}}
                <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_32px] items-center gap-2 mt-3">
                  <form method="POST" action="{{ route('cart.add', $p->id) }}" class="js-add-to-cart-form flex-1">
                    @csrf
                    <x-action-button size="sm" :full="true">
                      <i class="ri-shopping-cart-line text-sm"></i>
                      <span>В корзину</span>
                    </x-action-button>
                  </form>

                  <form method="POST" action="{{ route('checkout.quick', $p->id) }}" class="flex-1">
                    @csrf
                    <button class="w-full py-2 flex items-center justify-center gap-1.5
                                   border border-gray-200 hover:border-indigo-300 hover:text-indigo-600 
                                   text-gray-600 rounded-xl transition-all duration-200 hover:bg-indigo-50 text-xs font-semibold">
                      <i class="ri-flashlight-line text-sm"></i>
                      <span>Купить</span>
                    </button>
                  </form>

                  <form method="POST" action="{{ route('favorites.toggle', $p) }}" class="js-fav-remove-form">
                    @csrf
                    <button type="submit"
                      class="w-8 h-8 flex items-center justify-center text-rose-500 bg-rose-50 border border-rose-100 hover:bg-rose-100 transition-all duration-200 rounded-xl">
                      <i class="ri-delete-bin-6-line text-sm"></i>
                    </button>
                  </form>
                </div>
              </div>
            </div>

            {{-- Десктопная версия: строка --}}
            <div class="hidden sm:flex items-center gap-3 p-4">
              
              {{-- Product image --}}
              <a href="{{ route('product.show', $p) }}"
                 class="relative flex-shrink-0 w-20 h-20 rounded-xl overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-100">
                @if($p->image)
                  <img src="{{ $p->image_thumb_url }}"
                       class="w-full h-full object-cover transition-transform duration-400 group-hover:scale-105"
                       alt="{{ $p->title }}">
                @else
                  <div class="w-full h-full flex items-center justify-center text-2xl text-gray-300">
                    <i class="ri-image-line"></i>
                  </div>
                @endif

                @if($addedId === (int) $p->id)
                  <div class="absolute bottom-1 left-1 bg-emerald-500 text-white text-[8px] font-medium px-1.5 py-0.5 rounded-full">
                    ✓
                  </div>
                @endif

                @if(isset($p->discount_percent) && $p->discount_percent)
                  <div class="absolute -top-1 -right-1 bg-rose-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full shadow-sm">
                    -{{ $p->discount_percent }}%
                  </div>
                @endif
              </a>

              {{-- Product info --}}
              <div class="flex-1 min-w-0">
                <a href="{{ route('product.show', $p) }}"
                   class="text-base font-medium text-gray-800 hover:text-indigo-600 transition line-clamp-2 break-words leading-snug"
                   style="overflow-wrap: anywhere;">
                  {{ $p->title }}
                </a>
                
                @if($p->short_description)
                  <p class="text-xs text-gray-400 line-clamp-1 mt-0.5">
                    {{ Str::limit($p->short_description, 60) }}
                  </p>
                @endif

                <div class="mt-1">
                  @if(isset($p->old_price) && $p->old_price)
                    <span class="text-xs text-gray-400 line-through mr-1.5">
                      {{ number_format($p->old_price, 0, ',', ' ') }} {{ $itemCurrencySymbol }}
                    </span>
                  @endif
                  <span class="text-xl font-bold text-gray-900">
                    {{ number_format($price, 0, ',', ' ') }}
                  </span>
                  <span class="text-xs text-gray-400">{{ $itemCurrencySymbol }}</span>
                  <span class="ml-1 text-xs text-gray-400">за шт.</span>
                </div>
              </div>

              {{-- Actions --}}
              <div class="flex items-center gap-2 flex-shrink-0">
                <form method="POST" action="{{ route('cart.add', $p->id) }}" class="js-add-to-cart-form">
                  @csrf
                  <x-action-button size="sm">
                    <i class="ri-shopping-cart-line text-sm"></i>
                    <span>В корзину</span>
                  </x-action-button>
                </form>

                <form method="POST" action="{{ route('checkout.quick', $p->id) }}">
                  @csrf
                  <button class="px-3 py-2 flex items-center justify-center gap-1.5
                                 border border-gray-200 hover:border-indigo-300 hover:text-indigo-600 
                                 text-gray-600 rounded-xl transition-all duration-200 hover:bg-indigo-50 text-sm font-semibold">
                    <i class="ri-flashlight-line text-sm"></i>
                    <span>Купить</span>
                  </button>
                </form>

                <form method="POST" action="{{ route('favorites.toggle', $p) }}" class="js-fav-remove-form">
                  @csrf
                  <button type="submit"
                    class="w-9 h-9 flex items-center justify-center text-rose-500 bg-rose-50 border border-rose-100 hover:bg-rose-100 transition-all duration-200 rounded-xl">
                    <i class="ri-delete-bin-6-line text-base"></i>
                  </button>
                </form>
              </div>

            </div>
          </div>

        @endforeach
      </div>

      <div class="mt-5 rounded-xl border border-slate-200 bg-white/80 p-3 shadow-sm sm:mt-6 sm:rounded-2xl sm:p-4">
        <div class="grid min-w-0 gap-2 sm:grid-cols-3">
          <div class="min-w-0 rounded-xl bg-slate-50 px-3 py-3">
            <div class="text-xs text-gray-500">В избранном</div>
            <div class="mt-1 text-lg font-bold text-gray-900 break-words">{{ $items->count() }}</div>
          </div>

          <div class="min-w-0 rounded-xl bg-slate-50 px-3 py-3">
            <div class="text-xs text-gray-500">Если добавить по 1 шт.</div>
            <div class="mt-1 text-lg font-bold text-gray-900 break-words">{{ number_format($favoritesTotal, 0, ',', ' ') }} {{ $currencySymbol }}</div>
          </div>

          <div class="min-w-0 rounded-xl bg-indigo-50 px-3 py-3">
            <div class="text-xs text-indigo-700">Экономия по скидкам</div>
            <div class="mt-1 text-lg font-bold text-indigo-700 break-words">{{ number_format($discountTotal, 0, ',', ' ') }} {{ $currencySymbol }}</div>
          </div>
        </div>
      </div>

    @endif
  </div>

  <script>
document.addEventListener('DOMContentLoaded', () => {

    function showToast(text, type = 'success') {
      if (window.showAppToast) {
        window.showAppToast(text, type);
        return;
      }

      const existing = document.querySelector('.toast');
      if (existing) existing.remove();
      
      const el = document.createElement('div');
      el.className = 'toast ' + (type === 'error' ? 'toast-error' : 'toast-success');
      el.innerHTML = `
        <div class="flex items-center gap-2">
          <i class="${type === 'error' ? 'ri-error-warning-line' : 'ri-checkbox-circle-line'} text-base"></i>
          <span>${text}</span>
        </div>
      `;
      document.body.appendChild(el);
      
      setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateX(20px)';
        setTimeout(() => el.remove(), 300);
      }, 2500);
    }

    @if(session('success'))
      showToast("{{ session('success') }}");
    @endif

    function showPlusOne(btn) {
      const plus = document.createElement('span');
      plus.className = 'plus-one';
      plus.innerText = '+1';
      btn.style.position = 'relative';
      btn.appendChild(plus);
      setTimeout(() => plus.remove(), 300);
    }

    function flyToCart(img, cartIcon) {
      if (!img || !cartIcon) return;
      
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
      clone.style.transition = 'all 0.6s cubic-bezier(0.34, 1.2, 0.64, 1)';
      clone.style.pointerEvents = 'none';

      document.body.appendChild(clone);

      requestAnimationFrame(() => {
        clone.style.left = end.left + 'px';
        clone.style.top = end.top + 'px';
        clone.style.width = '24px';
        clone.style.height = '24px';
        clone.style.opacity = '0.4';
        clone.style.transform = 'scale(0.3)';
      });

      setTimeout(() => clone.remove(), 600);
    }

    function updateCartCount() {
      fetch("/cart-count")
        .then(r => r.json())
        .then(data => {
          const badge = document.querySelector("[data-cart-count]");
          if (!badge) return;
          if (data.count > 0) {
            badge.classList.remove("hidden");
            badge.classList.add("inline-flex");
            badge.textContent = data.count;
          } else {
            badge.classList.add("hidden");
            badge.classList.remove("inline-flex");
          }
        })
        .catch(() => {});
    }

    updateCartCount();

    async function responseMessage(response, fallback) {
      try {
        const data = await response.json();

        if (data?.errors?.qty?.[0]) {
          return data.errors.qty[0];
        }

        if (data?.message) {
          return data.message;
        }
      } catch (error) {
        return fallback;
      }

      return fallback;
    }

    document.querySelectorAll('.js-add-to-cart-form').forEach(form => {
      form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const btn = this.querySelector('button');
        const card = this.closest('[data-fav-card]');
        const img = card?.querySelector('img');
        const cartIcon = document.querySelector('[data-cart-icon]');

        const originalContent = btn.innerHTML;
        btn.classList.add('loading');
        btn.disabled = true;
        let added = false;

        try {
          const response = await fetch(this.action, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': this.querySelector('input[name="_token"]')?.value || '{{ csrf_token() }}',
            },
            body: new FormData(this),
          });

          if (!response.ok) {
            showToast(await responseMessage(response, 'Не удалось добавить товар в корзину'), 'error');
            return;
          }

          const data = await response.json();

          showPlusOne(btn);
          showToast(data?.message || 'Товар добавлен в корзину');
          card?.classList.add('card-added');
          setTimeout(() => card?.classList.remove('card-added'), 1000);
          if (img && cartIcon) flyToCart(img, cartIcon);
          updateCartCount();

          btn.innerHTML = '<i class="ri-check-line text-sm"></i><span>Готово</span>';
          added = true;
        } catch (error) {
          console.error('Add to cart error:', error);
          showToast('Не удалось добавить товар в корзину', 'error');
        } finally {
          const restore = () => {
            btn.innerHTML = originalContent;
            btn.classList.remove('loading');
            btn.disabled = false;
          };

          added ? setTimeout(restore, 900) : restore();
        }
      });
    });

    document.querySelectorAll('.js-add-all-to-cart-form').forEach(form => {
      form.addEventListener('submit', function() {
        const btn = this.querySelector('button');
        if (!btn) return;

        btn.disabled = true;
        btn.classList.add('loading');
      });
    });

    document.querySelectorAll('.js-fav-remove-form').forEach(form => {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        const card = this.closest('[data-fav-card]');
        card.classList.add('fav-removing');
        setTimeout(() => this.submit(), 200);
      });
    });

  });
  </script>

  <style>
    .favorites-mobile-safe,
    .favorites-mobile-safe * {
      box-sizing: border-box;
    }

    .favorites-mobile-safe {
      max-width: 100vw;
    }

    .fav-card {
      transition: all 0.25s cubic-bezier(0.2, 0, 0, 1);
    }
    .fav-card:hover {
      transform: translateY(-2px);
    }

    .card-added {
      background: #ecfdf5 !important;
      border-color: #10b981 !important;
    }

    .fav-removing {
      opacity: 0 !important;
      transform: translateX(-12px);
      transition: all 0.2s ease-out;
    }

    .toast {
      position: fixed;
      right: 16px;
      top: 80px;
      padding: 10px 18px;
      background: #1e293b;
      color: white;
      border-radius: 40px;
      font-size: 13px;
      font-weight: 500;
      box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);
      animation: slideInRight 0.3s ease;
      z-index: 99999;
      backdrop-filter: blur(8px);
      background: rgba(30, 41, 59, 0.95);
    }
    .toast-success {
      border-left: 3px solid #10b981;
    }
    .toast-error {
      background: rgba(239, 68, 68, 0.95);
      border-left: 3px solid #fecaca;
    }
    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(30px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .plus-one {
      position: absolute;
      right: 4px;
      top: -4px;
      font-size: 9px;
      font-weight: 800;
      color: #10b981;
      text-shadow: 0 0 2px white;
      animation: floatUp 0.35s ease-out forwards;
      pointer-events: none;
      z-index: 10;
    }
    @keyframes floatUp {
      0% {
        opacity: 0;
        transform: translateY(4px) scale(0.6);
      }
      30% {
        opacity: 1;
        transform: translateY(-2px) scale(1.1);
      }
      100% {
        opacity: 0;
        transform: translateY(-14px) scale(0.9);
      }
    }

    button.loading {
      color: transparent !important;
      position: relative;
      pointer-events: none;
    }
    button.loading::after {
      content: "";
      position: absolute;
      width: 14px;
      height: 14px;
      top: 50%;
      left: 50%;
      margin-left: -7px;
      margin-top: -7px;
      border: 2px solid rgba(255,255,255,0.3);
      border-top-color: white;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    .line-clamp-1 {
      display: -webkit-box;
      -webkit-line-clamp: 1;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .line-clamp-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      word-break: break-word;
      overflow-wrap: anywhere;
    }
  </style>

</x-buyer-layout>
