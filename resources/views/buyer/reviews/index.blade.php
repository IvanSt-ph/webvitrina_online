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
            \App\Models\Review::STATUS_APPROVED => 'border-success-200 bg-success-50 text-success-700',
            \App\Models\Review::STATUS_PENDING => 'border-warning-200 bg-warning-50 text-warning-700',
            \App\Models\Review::STATUS_REJECTED => 'border-danger-200 bg-danger-50 text-danger-700',
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
        $ratingOptions = ['all' => 'Все оценки', '5' => '5 звёзд', '4' => '4 звезды', '3' => '3 звезды', '2' => '2 звезды', '1' => '1 звезда'];
        $sortOptions = ['new' => 'Сначала новые', 'old' => 'Сначала старые', 'high' => 'С высокой оценкой', 'low' => 'С низкой оценкой'];
    @endphp

    <div x-data="{ expanded: {} }" class="min-h-screen bg-white pb-24 text-neutral-800 md:pb-0">
        <header class="border-b border-neutral-200 bg-white">
            <div class="flex w-full flex-col gap-5 px-4 py-6 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <div class="min-w-0">
                    <div class="mb-2 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-brand-600">
                        <i class="ri-star-line text-base" aria-hidden="true"></i>
                        Покупки и впечатления
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight text-neutral-900 sm:text-[28px]">Мои отзывы</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-neutral-500">Оценки, фотографии и статусы модерации ваших отзывов.</p>
                </div>
                <x-action-button as="a" :href="route('orders.index')" class="shrink-0">
                    <i class="ri-shopping-bag-3-line" aria-hidden="true"></i>
                    Мои заказы
                </x-action-button>
            </div>
        </header>

        <main class="w-full space-y-8 px-4 py-8 sm:px-6 sm:py-10 lg:px-8 lg:py-12">
            <section class="grid overflow-hidden rounded-2xl border border-brand-100 bg-brand-50/50 lg:grid-cols-[minmax(0,1fr)_420px]">
                <div class="flex flex-col justify-center p-5 sm:p-6 lg:p-7">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-brand-600 shadow-sm ring-1 ring-brand-100">
                        <i class="ri-star-smile-line text-xl" aria-hidden="true"></i>
                    </div>
                    <h2 class="mt-4 text-xl font-semibold tracking-tight text-neutral-900 sm:text-2xl">Ваш опыт помогает другим</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-neutral-600">Следите за публикацией отзывов, возвращайтесь к своим оценкам и дополняйте впечатления после покупки.</p>
                </div>
                <div class="grid grid-cols-3 border-t border-brand-100 bg-white/60 lg:border-l lg:border-t-0">
                    <div class="flex flex-col justify-center border-r border-brand-100 p-4 sm:p-5">
                        <span class="text-2xl font-semibold tracking-tight text-neutral-900 sm:text-3xl">{{ $counters['all'] }}</span>
                        <span class="mt-2 text-xs font-medium text-neutral-500 sm:text-sm">Всего</span>
                    </div>
                    <div class="flex flex-col justify-center border-r border-brand-100 p-4 sm:p-5">
                        <span class="flex items-center gap-1 text-2xl font-semibold tracking-tight text-brand-600 sm:text-3xl">{{ number_format($avgRating, 1) }}<i class="ri-star-fill text-lg text-warning-400"></i></span>
                        <span class="mt-2 text-xs font-medium text-neutral-500 sm:text-sm">Оценка</span>
                    </div>
                    <div class="flex flex-col justify-center p-4 sm:p-5">
                        <span class="text-2xl font-semibold tracking-tight text-warning-600 sm:text-3xl">{{ $counters[\App\Models\Review::STATUS_PENDING] ?? 0 }}</span>
                        <span class="mt-2 text-xs font-medium text-neutral-500 sm:text-sm">На проверке</span>
                    </div>
                </div>
            </section>

            @if($counters['all'] > 0)
                <section class="space-y-5">
                    <div class="overflow-x-auto border-b border-neutral-200">
                        <nav class="flex min-w-max gap-7" aria-label="Статусы отзывов">
                            @foreach($tabs as $key => $label)
                                <a href="{{ route('reviews.index', ['status' => $key, 'rating' => $rating, 'sort' => $sort]) }}"
                                   class="relative inline-flex min-h-12 items-center gap-2 px-1 text-sm font-semibold transition {{ $status === $key ? 'text-brand-600' : 'text-neutral-500 hover:text-neutral-900' }}"
                                   @if($status === $key) aria-current="page" @endif>
                                    {{ $label }}
                                    <span class="rounded-full px-2 py-0.5 text-xs {{ $status === $key ? 'bg-brand-50 text-brand-700' : 'bg-neutral-100 text-neutral-500' }}">{{ $counters[$key] ?? 0 }}</span>
                                    @if($status === $key)<span class="absolute inset-x-0 bottom-0 h-[3px] rounded-t-full bg-brand-500"></span>@endif
                                </a>
                            @endforeach
                        </nav>
                    </div>

                    <form method="GET" action="{{ route('reviews.index') }}" class="flex flex-col gap-3 rounded-2xl border border-neutral-200 bg-neutral-50/60 p-4 sm:flex-row sm:items-end sm:justify-between">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
                            <label class="block min-w-0 sm:w-52">
                                <span class="mb-1.5 block text-xs font-semibold text-neutral-500">Оценка</span>
                                <select name="rating" class="h-11 w-full rounded-xl border-neutral-200 bg-white px-3 pr-9 text-sm text-neutral-700 shadow-sm focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                                    @foreach($ratingOptions as $key => $label)<option value="{{ $key }}" @selected($rating === $key)>{{ $label }}</option>@endforeach
                                </select>
                            </label>
                            <label class="block min-w-0 sm:w-60">
                                <span class="mb-1.5 block text-xs font-semibold text-neutral-500">Сортировка</span>
                                <select name="sort" class="h-11 w-full rounded-xl border-neutral-200 bg-white px-3 pr-9 text-sm text-neutral-700 shadow-sm focus:border-brand-400 focus:ring-4 focus:ring-brand-100">
                                    @foreach($sortOptions as $key => $label)<option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>@endforeach
                                </select>
                            </label>
                            <x-action-button type="submit">
                                <i class="ri-filter-3-line" aria-hidden="true"></i>
                                Применить
                            </x-action-button>
                        </div>
                        @if($rating !== 'all' || $sort !== 'new')
                            <x-secondary-action as="a" :href="route('reviews.index', ['status' => $status])" size="sm">
                                <i class="ri-refresh-line" aria-hidden="true"></i>
                                Сбросить фильтры
                            </x-secondary-action>
                        @endif
                    </form>
                </section>
            @endif

            @if(($counters[\App\Models\Review::STATUS_REJECTED] ?? 0) > 0)
                <div class="flex gap-3 rounded-2xl border border-danger-100 bg-danger-50 px-4 py-3 text-sm leading-6 text-danger-700">
                    <i class="ri-information-line mt-0.5 shrink-0 text-lg" aria-hidden="true"></i>
                    <p>Если отзыв отклонён, причина указана в его карточке. После редактирования отзыв снова отправится на модерацию.</p>
                </div>
            @endif
            @if(($counters[\App\Models\Review::STATUS_PENDING] ?? 0) > 0)
                <div class="flex gap-3 rounded-2xl border border-warning-100 bg-warning-50 px-4 py-3 text-sm leading-6 text-warning-800">
                    <i class="ri-time-line mt-0.5 shrink-0 text-lg" aria-hidden="true"></i>
                    <p>Отзывы на модерации пока видны только вам. После проверки они появятся на странице товара.</p>
                </div>
            @endif

            <section class="space-y-5" aria-label="Список отзывов">
                @forelse($reviews as $review)
                    @php
                        $product = $review->product;
                        $statusClass = $statusClasses[$review->status] ?? 'border-neutral-200 bg-neutral-50 text-neutral-700';
                        $body = trim((string) $review->body);
                        $isLongBody = mb_strlen($body) > 260;
                        $productUrl = $product ? route('product.show', $product->slug ?? $product->id) : null;
                    @endphp

                    <article class="group overflow-hidden rounded-2xl border border-neutral-200 bg-white transition duration-300 hover:border-brand-200 hover:shadow-xl hover:shadow-brand-100/40">
                        <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-[112px_minmax(0,1fr)_180px] lg:items-start">
                            <div class="flex gap-4 lg:block">
                                <div class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50 lg:h-28 lg:w-28">
                                    @if($product)
                                        <img data-image-candidates="{{ json_encode($product->image_thumb_candidates) }}" data-image-fallback="{{ asset('images/image-placeholder.svg') }}" src="{{ $product->image_thumb_url }}" alt="{{ $product->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    @else
                                        <div class="flex h-full w-full items-center justify-center text-neutral-300"><i class="ri-image-line text-3xl"></i></div>
                                    @endif
                                </div>
                            </div>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        @if($productUrl)
                                            <a href="{{ $productUrl }}" class="block break-words text-lg font-semibold leading-6 text-neutral-900 transition hover:text-brand-700">{{ $product->title }}</a>
                                        @else
                                            <span class="text-lg font-semibold text-neutral-900">Товар удалён</span>
                                        @endif
                                        <div class="mt-2 flex flex-wrap items-center gap-3">
                                            <div class="flex items-center text-warning-400" aria-label="Оценка {{ $review->rating }} из 5">
                                                @for($i = 1; $i <= 5; $i++)<i class="{{ $i <= $review->rating ? 'ri-star-fill' : 'ri-star-line text-neutral-300' }}"></i>@endfor
                                            </div>
                                            <span class="text-xs text-neutral-400">{{ $review->created_at->format('d.m.Y') }}</span>
                                            <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}"><i class="{{ $statusIcon[$review->status] ?? 'ri-information-line' }}"></i>{{ $statusText[$review->status] ?? 'Неизвестно' }}</span>
                                        </div>
                                    </div>
                                </div>

                                @if($review->status === \App\Models\Review::STATUS_REJECTED && filled($review->rejection_reason))
                                    <div class="mt-4 rounded-xl border border-danger-100 bg-danger-50 px-4 py-3 text-sm leading-6 text-danger-700"><span class="font-semibold">Причина отклонения:</span> {{ $review->rejection_reason }}</div>
                                @endif

                                <div class="mt-4 rounded-xl bg-neutral-50 px-4 py-3">
                                    <p class="break-words text-sm leading-6 text-neutral-700" :style="expanded[{{ $review->id }}] ? '' : 'display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;'">
                                        {{ $body !== '' ? $body : 'Без текстового комментария.' }}
                                    </p>
                                    @if($isLongBody)
                                        <button type="button" class="mt-2 text-sm font-semibold text-brand-600 hover:text-brand-700" @click="expanded[{{ $review->id }}] = !expanded[{{ $review->id }}]" x-text="expanded[{{ $review->id }}] ? 'Свернуть' : 'Показать полностью'"></button>
                                    @endif
                                </div>

                                @if($review->images->isNotEmpty())
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        @foreach($review->images as $image)
                                            <a href="{{ $image->url }}" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-xl ring-1 ring-neutral-200 transition hover:ring-2 hover:ring-brand-300">
                                                <img data-image-candidates="{{ json_encode($image->thumb_candidates) }}" data-image-fallback="{{ asset('images/image-placeholder.svg') }}" src="{{ $image->thumb_url }}" alt="Фото из вашего отзыва" class="h-14 w-14 object-cover">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            @if($productUrl)
                                <div class="flex flex-col gap-2 border-t border-neutral-100 pt-4 lg:border-l lg:border-t-0 lg:pl-5 lg:pt-0">
                                    <x-action-button as="a" :href="$productUrl" full size="sm">
                                        <i class="ri-external-link-line" aria-hidden="true"></i>
                                        Открыть товар
                                    </x-action-button>
                                    <x-secondary-action as="a" :href="$productUrl . '#reviews'" full size="sm">
                                        <i class="ri-edit-2-line" aria-hidden="true"></i>
                                        Изменить
                                    </x-secondary-action>
                                </div>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-neutral-300 bg-neutral-50/60 px-6 py-16 text-center sm:py-20">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-600 ring-8 ring-white"><i class="ri-star-smile-line text-3xl"></i></div>
                        <h2 class="mt-6 text-xl font-semibold text-neutral-900">{{ $counters['all'] > 0 ? 'В этом разделе отзывов нет' : 'У вас пока нет отзывов' }}</h2>
                        <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-neutral-500">{{ $counters['all'] > 0 ? 'Выберите другой статус или вернитесь ко всем отзывам.' : 'После получения заказа вы сможете оставить оценку на странице товара или из деталей заказа.' }}</p>
                        <div class="mt-7 flex justify-center">
                            <x-action-button as="a" :href="$counters['all'] > 0 ? route('reviews.index') : route('orders.index')">
                                <i class="{{ $counters['all'] > 0 ? 'ri-list-check' : 'ri-shopping-bag-3-line' }}" aria-hidden="true"></i>
                                {{ $counters['all'] > 0 ? 'Все отзывы' : 'Мои заказы' }}
                            </x-action-button>
                        </div>
                    </div>
                @endforelse
            </section>
        </main>
    </div>

    @include('layouts.mobile-bottom-nav')
</x-buyer-layout>
