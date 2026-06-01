<x-buyer-layout title="Мои отзывы">
    @php
        $status = $status ?? 'all';
        $rating = $rating ?? 'all';
        $sort = $sort ?? 'new';
        $counters = $counters ?? [
            'all' => $reviews->count(),
            \App\Models\Review::STATUS_APPROVED => $reviews->where('status', \App\Models\Review::STATUS_APPROVED)->count(),
            \App\Models\Review::STATUS_PENDING => $reviews->where('status', \App\Models\Review::STATUS_PENDING)->count(),
            \App\Models\Review::STATUS_REJECTED => $reviews->where('status', \App\Models\Review::STATUS_REJECTED)->count(),
        ];
        $avgRating = isset($avgRating) ? round($avgRating, 1) : ($counters['all'] ? round($reviews->avg('rating'), 1) : 0);

        $statusClasses = [
            \App\Models\Review::STATUS_APPROVED => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            \App\Models\Review::STATUS_PENDING => 'border-amber-200 bg-amber-50 text-amber-700',
            \App\Models\Review::STATUS_REJECTED => 'border-rose-200 bg-rose-50 text-rose-700',
        ];

        $statusText = [
            \App\Models\Review::STATUS_APPROVED => 'Одобрен',
            \App\Models\Review::STATUS_PENDING => 'На модерации',
            \App\Models\Review::STATUS_REJECTED => 'Отклонён',
        ];

        $statusIcon = [
            \App\Models\Review::STATUS_APPROVED => 'ri-check-line',
            \App\Models\Review::STATUS_PENDING => 'ri-time-line',
            \App\Models\Review::STATUS_REJECTED => 'ri-close-line',
        ];

        $tabs = [
            'all' => 'Все',
            \App\Models\Review::STATUS_APPROVED => 'Одобренные',
            \App\Models\Review::STATUS_PENDING => 'На модерации',
            \App\Models\Review::STATUS_REJECTED => 'Отклонённые',
        ];

        $ratingOptions = [
            'all' => 'Все оценки',
            '5' => '5 звёзд',
            '4' => '4 звезды',
            '3' => '3 звезды',
            '2' => '2 звезды',
            '1' => '1 звезда',
        ];

        $sortOptions = [
            'new' => 'Сначала новые',
            'old' => 'Сначала старые',
            'high' => 'С высокой оценкой',
            'low' => 'С низкой оценкой',
        ];
    @endphp

    <div x-data="{ expanded: {} }" class="w-full max-w-none space-y-5 bg-white px-3 py-4 sm:space-y-6 sm:px-6 sm:py-8">
        <header class="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[minmax(0,1fr)_340px] lg:items-center sm:p-5">
            <div class="min-w-0">
                <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-600">
                    <i class="ri-star-smile-line"></i>
                    Отзывы
                </span>
                <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Мои отзывы</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Следите за модерацией отзывов, открывайте товар и быстро возвращайтесь к оценкам после покупки.
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl bg-white px-3 py-2 shadow-sm">
                        <p class="text-lg font-bold text-slate-950">{{ $counters['all'] }}</p>
                        <p class="text-[11px] font-medium text-slate-400">Всего</p>
                    </div>
                    <div class="rounded-xl bg-white px-3 py-2 shadow-sm">
                        <p class="text-lg font-bold text-slate-950">{{ number_format($avgRating, 1) }}</p>
                        <p class="text-[11px] font-medium text-slate-400">Оценка</p>
                    </div>
                    <div class="rounded-xl bg-white px-3 py-2 shadow-sm">
                        <p class="text-lg font-bold text-amber-600">{{ $counters[\App\Models\Review::STATUS_PENDING] ?? 0 }}</p>
                        <p class="text-[11px] font-medium text-slate-400">Модерация</p>
                    </div>
                </div>
            </div>
        </header>

        @if($counters['all'] > 0)
            <div class="space-y-3">
                <div class="border-b border-gray-200 overflow-x-auto">
                    <div class="flex gap-5 min-w-max">
                        @foreach($tabs as $key => $label)
                            <a href="{{ route('reviews.index', ['status' => $key, 'rating' => $rating, 'sort' => $sort]) }}"
                               class="pb-3 text-sm font-medium {{ $status === $key ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700' }}">
                                {{ $label }}
                                <span class="ml-1 text-xs text-gray-400">{{ $counters[$key] ?? 0 }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <form method="GET" action="{{ route('reviews.index') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <input type="hidden" name="status" value="{{ $status }}">

                    <div class="flex flex-wrap gap-2">
                        <select name="rating" class="min-w-[11rem] rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-sm text-gray-700 focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($ratingOptions as $key => $label)
                                <option value="{{ $key }}" @selected($rating === $key)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <select name="sort" class="min-w-[13rem] rounded-lg border border-gray-300 bg-white px-3 py-2 pr-9 text-sm text-gray-700 focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($sortOptions as $key => $label)
                                <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                            <i class="ri-filter-3-line"></i>
                            Применить
                        </button>
                    </div>

                    @if($rating !== 'all' || $sort !== 'new')
                        <a href="{{ route('reviews.index', ['status' => $status]) }}" class="text-sm font-medium text-gray-500 hover:text-indigo-600">
                            Сбросить фильтры
                        </a>
                    @endif
                </form>
            </div>
        @endif

        @if(($counters[\App\Models\Review::STATUS_REJECTED] ?? 0) > 0)
            <div class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-700">
                Если отзыв отклонён, причина будет показана прямо в карточке. После правки отзыва он снова отправится на модерацию.
            </div>
        @endif

        @if(($counters[\App\Models\Review::STATUS_PENDING] ?? 0) > 0)
            <div class="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-800">
                Отзывы на модерации пока видны только вам. После проверки они появятся на странице товара.
            </div>
        @endif

        <div class="bg-white border border-gray-200 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">
            @forelse($reviews as $review)
                @php
                    $product = $review->product;
                    $statusClass = $statusClasses[$review->status] ?? 'border-gray-200 bg-gray-50 text-gray-700';
                    $body = trim((string) $review->body);
                    $isLongBody = mb_strlen($body) > 260;
                    $productUrl = $product ? route('product.show', $product->slug ?? $product->id) : null;
                @endphp

                <article class="grid gap-3 border-b border-gray-100 p-4 last:border-b-0 sm:grid-cols-[84px_minmax(0,1fr)_auto] sm:gap-4 sm:p-5 hover:bg-gray-50/70 transition">
                    <div class="h-[84px] w-[84px] overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
                        @if($product)
                            <img src="{{ $product->image_thumb_url }}" alt="{{ $product->title }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full w-full items-center justify-center text-gray-300">
                                <i class="ri-image-line text-2xl"></i>
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0">
                        @if($productUrl)
                            <a href="{{ $productUrl }}"
                               title="{{ $product->title }}"
                               class="max-w-full break-words text-base font-semibold leading-6 text-gray-900 hover:text-indigo-600"
                               style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                {{ $product->title }}
                            </a>
                        @else
                            <span class="text-base font-semibold leading-6 text-gray-900">Товар удалён</span>
                        @endif

                        <div class="mt-1.5 flex flex-wrap items-center gap-2 text-sm">
                            <div class="flex items-center text-amber-400" aria-label="Оценка {{ $review->rating }} из 5">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="{{ $i <= $review->rating ? 'ri-star-fill' : 'ri-star-line text-gray-300' }}"></i>
                                @endfor
                            </div>
                            <span class="text-xs text-gray-400">{{ $review->created_at->format('d.m.Y') }}</span>
                            <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium {{ $statusClass }}">
                                <i class="{{ $statusIcon[$review->status] ?? 'ri-information-line' }}"></i>
                                {{ $statusText[$review->status] ?? 'Неизвестно' }}
                            </span>
                        </div>

                        @if($review->status === \App\Models\Review::STATUS_REJECTED && filled($review->rejection_reason))
                            <div class="mt-3 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-sm leading-6 text-rose-700">
                                <span class="font-semibold">Причина отклонения:</span>
                                {{ $review->rejection_reason }}
                            </div>
                        @endif

                        <div class="mt-3">
                            <div class="mb-1 text-xs font-semibold uppercase text-gray-400">Ваш отзыв:</div>
                            <p class="break-words text-sm leading-6 text-gray-700"
                               :style="expanded[{{ $review->id }}] ? '' : 'display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;'">
                                {{ $body !== '' ? $body : 'Без текстового комментария.' }}
                            </p>

                            @if($isLongBody)
                                <button type="button"
                                        class="mt-1 text-sm font-medium text-indigo-600 hover:text-indigo-700"
                                        @click="expanded[{{ $review->id }}] = !expanded[{{ $review->id }}]"
                                        x-text="expanded[{{ $review->id }}] ? 'Свернуть' : 'Показать полностью'"></button>
                            @endif
                        </div>

                        @if($review->images->isNotEmpty())
                            <div class="mt-3">
                                <div class="mb-2 text-xs font-semibold uppercase text-gray-400">Ваше фото из отзыва:</div>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($review->images as $image)
                                        <a href="{{ asset('storage/' . $image->path) }}" target="_blank" rel="noopener noreferrer" class="block">
                                            <img src="{{ asset('storage/' . $image->path) }}"
                                                 alt="Фото из вашего отзыва"
                                                 class="h-14 w-14 rounded-lg border border-gray-200 object-cover hover:border-indigo-300 transition">
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-start justify-end gap-2 sm:w-32">
                        @if($productUrl)
                            <a href="{{ $productUrl }}" class="inline-flex h-9 items-center justify-center gap-1 rounded-lg border border-gray-200 px-3 text-sm font-medium text-gray-600 hover:border-indigo-200 hover:text-indigo-600">
                                <i class="ri-external-link-line"></i>
                                Открыть
                            </a>
                            <a href="{{ $productUrl }}#reviews" class="inline-flex h-9 items-center justify-center gap-1 rounded-lg border border-indigo-100 bg-indigo-50 px-3 text-sm font-medium text-indigo-700 hover:bg-indigo-100">
                                <i class="ri-edit-2-line"></i>
                                Изменить
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="px-4 py-14 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-500">
                        <i class="ri-star-smile-line text-2xl"></i>
                    </div>
                    <h2 class="text-base font-semibold text-gray-900">
                        {{ $counters['all'] > 0 ? 'В этом разделе отзывов нет' : 'У вас пока нет отзывов' }}
                    </h2>
                    <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-gray-500">
                        {{ $counters['all'] > 0 ? 'Выберите другой статус или вернитесь ко всем отзывам.' : 'После получения заказа вы сможете оставить оценку на странице товара или из деталей заказа.' }}
                    </p>
                    <a href="{{ $counters['all'] > 0 ? route('reviews.index') : route('orders.index') }}"
                       class="mt-4 inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        <i class="{{ $counters['all'] > 0 ? 'ri-list-check' : 'ri-shopping-bag-3-line' }}"></i>
                        {{ $counters['all'] > 0 ? 'Все отзывы' : 'Мои заказы' }}
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</x-buyer-layout>
