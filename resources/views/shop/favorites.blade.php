<x-buyer-layout title="Избранное">

  @php
      $addedId = (int) session('cart_added_id');
  @endphp

  <div class="max-w-8xl mx-auto px-4 sm:px-6 py-4 sm:py-8">

    {{-- Header --}}
    <div class="mb-6 sm:mb-10">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <div class="w-7 h-7 sm:w-8 sm:h-8 bg-gradient-to-br from-indigo-100 to-indigo-200 rounded-xl flex items-center justify-center">
              <i class="ri-heart-3-line text-indigo-500 text-sm sm:text-base"></i>
            </div>
            <span class="text-[10px] sm:text-xs font-mono text-indigo-400 tracking-wider uppercase">Wishlist</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-light tracking-tight text-gray-900">Избранное</h1>
          <p class="text-gray-400 text-xs sm:text-sm mt-0.5">{{ $items->isNotEmpty() ? $items->count() . ' сохранённых товара' : 'пусто' }}</p>
        </div>

        @if($items->isNotEmpty())
        <a href="{{ route('cart.index') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-2 sm:px-5 sm:py-2.5 rounded-full border border-indigo-200 text-sm font-medium text-indigo-600 hover:bg-indigo-50 hover:border-indigo-300 transition-all duration-200">
          <div class="relative">
            <i class="ri-shopping-cart-line text-sm sm:text-base" data-cart-icon></i>
            <span data-cart-count
                  class="absolute -top-1.5 -right-2 bg-amber-500 text-white text-[9px] font-bold min-w-[16px] h-4 px-1 rounded-full hidden items-center justify-center">
            </span>
          </div>
          <span>Корзина</span>
          <i class="ri-arrow-right-s-line text-base opacity-0 -translate-x-1 group-hover:opacity-100 group-hover:translate-x-0 transition-all duration-200 hidden sm:inline"></i>
        </a>
        @endif
      </div>
    </div>

    @if($items->isEmpty())

      {{-- Empty state --}}
      <div class="text-center py-12 sm:py-20">
        <div class="mb-5">
          <div class="inline-flex items-center justify-center w-20 h-20 sm:w-24 sm:h-24 bg-indigo-50 rounded-2xl">
            <i class="ri-heart-3-line text-3xl sm:text-4xl text-indigo-300"></i>
          </div>
        </div>
        <h3 class="text-lg sm:text-xl font-light text-gray-700 mb-2">Здесь пока пусто</h3>
        <p class="text-gray-400 text-sm mb-6">Сохраняйте понравившиеся товары</p>
        <a href="{{ route('home') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 sm:px-8 sm:py-3 rounded-full text-sm transition-all duration-200 shadow-sm hover:shadow-md hover:-translate-y-0.5">
          <i class="ri-arrow-left-s-line"></i>
          <span>В каталог</span>
        </a>
      </div>

    @else

      {{-- Favorites list --}}
      <div class="space-y-2 sm:space-y-3">
        @foreach($items as $f)
          @php $p = $f->product; @endphp

          <div class="fav-card group bg-white rounded-xl sm:rounded-2xl border border-gray-100 transition-all duration-200 hover:shadow-md hover:border-gray-200"
               data-fav-card data-id="{{ $p->id }}">

            {{-- Мобильная версия: фото 50px, справа название+цена, внизу кнопки --}}
            <div class="block sm:hidden">
              <div class="p-3">
                {{-- Верхняя строка: фото + информация --}}
                <div class="flex gap-3">
                  {{-- Фото 50x50 --}}
                  <a href="{{ route('product.show', $p) }}"
                     class="relative flex-shrink-0 w-12 h-12 rounded-lg overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-100">
                    @if($p->image)
                      <img src="{{ asset('storage/'.$p->image) }}"
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
                       class="text-sm font-medium text-gray-800 hover:text-indigo-600 transition line-clamp-2 break-words leading-snug">
                      {{ $p->title }}
                    </a>
                    <div class="mt-1">
                      @if(isset($p->old_price) && $p->old_price)
                        <span class="text-[9px] text-gray-400 line-through mr-1">
                          {{ number_format($p->old_price, 0, ',', ' ') }} ₽
                        </span>
                      @endif
                      <span class="text-sm font-bold text-gray-900">
                        {{ number_format($p->price, 0, ',', ' ') }}
                      </span>
                      <span class="text-[9px] text-gray-400">₽</span>
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
                <div class="flex items-center gap-2 mt-3">
                  <form method="POST" action="{{ route('cart.add', $p->id) }}" class="js-add-to-cart-form flex-1">
                    @csrf
                    <button type="submit"
                      class="w-full py-2 flex items-center justify-center gap-1.5
                             bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg transition-all duration-200
                             text-xs font-medium">
                      <i class="ri-shopping-cart-line text-sm"></i>
                      <span>В корзину</span>
                    </button>
                  </form>

                  <form method="POST" action="{{ route('checkout.quick', $p->id) }}" class="flex-1">
                    @csrf
                    <button class="w-full py-2 flex items-center justify-center gap-1.5
                                   border border-gray-200 hover:border-indigo-300 hover:text-indigo-600 
                                   text-gray-600 rounded-lg transition-all duration-200 hover:bg-indigo-50 text-xs font-medium">
                      <i class="ri-flashlight-line text-sm"></i>
                      <span>Купить</span>
                    </button>
                  </form>

                  <form method="POST" action="{{ route('favorites.toggle', $p) }}" class="js-fav-remove-form">
                    @csrf
                    <button type="submit"
                      class="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-red-500 transition-all duration-200 rounded-lg hover:bg-red-50">
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
                  <img src="{{ asset('storage/'.$p->image) }}"
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
                   class="text-base font-medium text-gray-800 hover:text-indigo-600 transition line-clamp-2 break-words leading-snug">
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
                      {{ number_format($p->old_price, 0, ',', ' ') }} ₽
                    </span>
                  @endif
                  <span class="text-xl font-bold text-gray-900">
                    {{ number_format($p->price, 0, ',', ' ') }}
                  </span>
                  <span class="text-xs text-gray-400">₽</span>
                </div>
              </div>

              {{-- Actions --}}
              <div class="flex items-center gap-2 flex-shrink-0">
                <form method="POST" action="{{ route('cart.add', $p->id) }}" class="js-add-to-cart-form">
                  @csrf
                  <button type="submit"
                    class="px-3 py-2 flex items-center justify-center gap-1.5
                           bg-indigo-500 hover:bg-indigo-600 text-white rounded-xl transition-all duration-200
                           text-sm font-medium">
                    <i class="ri-shopping-cart-line text-sm"></i>
                    <span>В корзину</span>
                  </button>
                </form>

                <form method="POST" action="{{ route('checkout.quick', $p->id) }}">
                  @csrf
                  <button class="px-3 py-2 flex items-center justify-center gap-1.5
                                 border border-gray-200 hover:border-indigo-300 hover:text-indigo-600 
                                 text-gray-600 rounded-xl transition-all duration-200 hover:bg-indigo-50 text-sm font-medium">
                    <i class="ri-flashlight-line text-sm"></i>
                    <span>Купить</span>
                  </button>
                </form>

                <form method="POST" action="{{ route('favorites.toggle', $p) }}" class="js-fav-remove-form">
                  @csrf
                  <button type="submit"
                    class="w-9 h-9 flex items-center justify-center text-gray-400 hover:text-red-500 transition-all duration-200 rounded-xl hover:bg-red-50">
                    <i class="ri-delete-bin-6-line text-base"></i>
                  </button>
                </form>
              </div>

            </div>
          </div>

        @endforeach
      </div>

    @endif
  </div>

  <script>
document.addEventListener('DOMContentLoaded', () => {

    function showToast(text, type = 'success') {
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
        .catch(e => console.log('Cart count error:', e));
    }

    updateCartCount();

    document.querySelectorAll('.js-add-to-cart-form').forEach(form => {
      form.addEventListener('submit', function(e) {
        e.preventDefault();

        const btn = this.querySelector('button');
        const card = this.closest('[data-fav-card]');
        const img = card.querySelector('img');
        const cartIcon = document.querySelector('[data-cart-icon]');

        const originalContent = btn.innerHTML;
        btn.classList.add('loading');
        btn.disabled = true;

        showPlusOne(btn);
        card.classList.add('card-added');
        setTimeout(() => card.classList.remove('card-added'), 1000);
        if (img && cartIcon) flyToCart(img, cartIcon);

        btn.innerHTML = '<i class="ri-check-line text-sm"></i><span>Готово</span>';

        setTimeout(() => {
          btn.innerHTML = originalContent;
          btn.classList.remove('loading');
          btn.disabled = false;
        }, 1200);

        setTimeout(() => {
          this.submit();
          updateCartCount();
        }, 200);
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
    }
  </style>

</x-buyer-layout>