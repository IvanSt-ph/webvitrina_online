<div class="lg:col-span-4 w-full max-w-xl mx-auto">

    <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 leading-snug break-words overflow-wrap-anywhere hyphens-auto">
        {{ $product->title }}
    </h1>

    {{-- Рейтинг / отзывы --}}
    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-gray-600">
        <div class="flex items-center gap-1">
            <span class="text-yellow-400">★</span>
            <span class="font-semibold">
                {{ number_format($product->reviews_avg_rating ?? 0, 1) }}
            </span>
            <span class="text-gray-400">
                ({{ $product->reviews_count }} отзывов)
            </span>
        </div>

        @if ($product->orders_count)
            <span class="text-gray-400">
                · {{ $product->orders_count }} заказов
            </span>
        @endif
    </div>

    {{-- Мини параметры --}}
    <div class="mt-5 space-y-3 text-sm text-gray-700">

        @if ($product->sku)
            <div class="flex items-center text-sm">
                <span class="text-gray-500 whitespace-nowrap">Артикул</span>
                <div class="flex-1 border-b border-dotted mx-2 border-gray-300"></div>
                <span class="font-medium break-words">{{ $product->sku }}</span>
            </div>
        @endif

        @if ($product->category)
            <div class="flex items-center text-sm">
                <span class="text-gray-500 whitespace-nowrap">Категория</span>
                <div class="flex-1 border-b border-dotted mx-2 border-gray-300"></div>
                <span class="font-medium break-words">{{ $product->category->name }}</span>
            </div>
        @endif

        @if ($product->city || $product->country)
            <div class="flex items-center text-sm">
                <span class="text-gray-500 whitespace-nowrap">Локация</span>
                <div class="flex-1 border-b border-dotted mx-2 border-gray-300"></div>
                <span class="font-medium break-words">
                    {{ $product->country->name ?? $product->city->country->name ?? '' }}
                    @if ($product->city)
                        , {{ $product->city->name }}
                    @endif
                </span>
            </div>
        @endif
    </div>

    <button 
        @click="$store.specs.open = true"
        class="mt-4 w-full text-left px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl hover:bg-gray-100 hover:shadow-sm transition font-medium text-indigo-600 flex items-center justify-between">
        <span>Характеристики товара</span>
        <i class="ri-list-settings-line text-lg text-gray-500"></i>
    </button>

    {{-- Продавец --}}
    @if ($product->seller)
        <div class="mt-6 bg-gray-50 border border-gray-100 rounded-xl p-4">
            <div class="text-xs uppercase tracking-wide text-gray-400 mb-1">Магазин</div>
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <div class="font-semibold text-gray-900 break-words">
                        {{ $product->seller->name }}
                    </div>
                    <div class="text-xs text-gray-600 mt-1">
                        ⭐ {{ number_format($product->seller->reviews_avg_rating ?? 0, 2) }}
                        · {{ $product->seller->reviews_count }} отзывов
                        @if($product->seller->salesOrders()->exists())
                            · {{ $product->seller->salesOrders()->count() }} заказов
                        @endif
                    </div>
                </div>
                @if($product->seller->shop?->slug)
                    <a href="{{ route('seller.show', $product->seller->shop->slug) }}"
                       class="text-xs font-medium text-indigo-600 hover:text-indigo-700 whitespace-nowrap flex-shrink-0">
                        Перейти →
                    </a>
                @endif
            </div>
        </div>

        <div class="mt-3 grid gap-2 rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-900">
            <div class="flex items-start gap-2">
                <i class="ri-shield-check-line mt-0.5 text-lg text-emerald-600"></i>
                <div>
                    <div class="font-semibold">Безопасная покупка через WebVitrina</div>
                    <div class="mt-1 text-xs leading-5 text-emerald-700">Заказ, чат, спор и история статусов сохраняются на площадке. Не передавайте пароли и SMS-коды продавцу.</div>
                </div>
            </div>
        </div>
    @endif

    @auth
        @if(auth()->id() !== $product->user_id)
            <details class="mt-3 rounded-xl border border-slate-200 bg-white p-3">
                <summary class="cursor-pointer text-sm font-semibold text-slate-600 hover:text-indigo-700">
                    Пожаловаться на товар
                </summary>
                <form method="POST" action="{{ route('products.report', $product) }}" class="mt-3 space-y-2">
                    @csrf
                    <select name="reason" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100">
                        <option value="">Выберите причину</option>
                        <option value="Неверное описание">Неверное описание</option>
                        <option value="Подозрительная цена">Подозрительная цена</option>
                        <option value="Запрещённый товар">Запрещённый товар</option>
                        <option value="Другое">Другое</option>
                    </select>
                    <textarea name="details" rows="2" maxlength="1000" placeholder="Коротко опишите проблему"
                              class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"></textarea>
                    <button class="inline-flex h-10 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-semibold text-rose-700 hover:bg-rose-100">
                        Отправить жалобу
                    </button>
                </form>
            </details>
        @endif
    @endauth

</div>
