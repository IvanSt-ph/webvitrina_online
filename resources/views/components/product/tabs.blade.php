@props(['product', 'reviews', 'myReview'])

@php
    $description = trim((string) $product->description);
    $descLength = mb_strlen(strip_tags($description));

    $details = collect();

    if ($product->sku) {
        $details->push(['label' => 'Артикул', 'value' => $product->sku]);
    }

    if ($product->category) {
        $details->push(['label' => 'Категория', 'value' => $product->category->name]);
    }

    if ($product->seller?->name) {
        $details->push(['label' => 'Продавец', 'value' => $product->seller->name]);
    }

    if ($product->city || $product->country) {
        $details->push([
            'label' => 'Местоположение',
            'value' => trim(($product->country->name ?? $product->city->country->name ?? '') . ($product->city ? ', ' . $product->city->name : ''), ', '),
        ]);
    }

    if ($product->stock !== null) {
        $details->push(['label' => 'Наличие', 'value' => $product->stock > 0 ? $product->stock . ' шт.' : 'Нет в наличии']);
    }

    foreach (($product->attributes ?? collect()) as $attr) {
        $value = $attr->pivot->value ?? null;

        if ($attr->type === 'color' && $value) {
            $value = $attr->colors->firstWhere('id', $value)?->name ?? $value;
        }

        if (filled($value)) {
            $details->push([
                'label' => $attr->name,
                'value' => trim($value . ($attr->unit ? ' ' . $attr->unit : '')),
            ]);
        }
    }

    $quickDetails = $details->take(5);
    $descriptionBullets = collect(preg_split('/\r\n|\r|\n/u', $description))
        ->map(fn ($line) => trim($line, " \t\n\r\0\x0B-•"))
        ->filter()
        ->values();
@endphp

<div
    class="mt-12 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6"
    x-data="{ tab: window.location.hash === '#reviews' ? 'reviews' : 'desc' }"
>
    <div class="flex flex-wrap gap-2 border-b border-slate-100 pb-3 text-sm">
        <button
            type="button"
            @click="tab='desc'"
            :class="tab==='desc' ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
            class="rounded-xl px-4 py-2 font-semibold transition"
        >
            Описание
        </button>

        <button
            type="button"
            @click="tab='details'"
            :class="tab==='details' ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
            class="rounded-xl px-4 py-2 font-semibold transition"
        >
            Подробная информация
        </button>

        <button
            type="button"
            @click="tab='reviews'; history.replaceState(null, '', '#reviews')"
            :class="tab==='reviews' ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-100' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
            class="rounded-xl px-4 py-2 font-semibold transition"
        >
            Отзывы ({{ $product->reviews_count }})
        </button>
    </div>

    <div class="mt-6">
        <section x-show="tab==='desc'" x-transition.opacity.duration.250ms>
            <div class="space-y-6">
                <section class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:p-5">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 text-base font-bold text-slate-950">
                            <i class="ri-flashlight-line text-indigo-600"></i>
                            Главное о товаре
                        </div>
                        <button
                            type="button"
                            @click="tab='details'"
                            class="rounded-xl bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100 hover:bg-indigo-100"
                        >
                            Все детали
                        </button>
                    </div>

                    <div class="grid gap-x-10 gap-y-3 text-sm sm:grid-cols-[220px_minmax(0,1fr)]">
                        @forelse($quickDetails as $detail)
                            <div class="font-bold leading-6 text-slate-800">{{ $detail['label'] }}</div>
                            <div class="min-w-0 break-words leading-6 text-slate-700">{{ $detail['value'] }}</div>
                        @empty
                            <div class="sm:col-span-2 text-slate-500">Основные данные появятся после заполнения характеристик.</div>
                        @endforelse
                    </div>
                </section>

                <section x-data="{ expanded: false }" class="relative rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:p-5">
                    <div class="mb-4 flex items-center gap-2 text-base font-bold text-slate-950">
                        <i class="ri-file-text-line text-indigo-600"></i>
                        О товаре
                    </div>

                    @if($descriptionBullets->isNotEmpty())
                        <div x-bind:class="expanded ? '' : 'max-h-72 overflow-hidden'" class="relative">
                            <ul class="space-y-3 text-slate-700">
                                @foreach($descriptionBullets as $line)
                                    <li class="flex gap-3 leading-7">
                                        <span class="mt-3 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400"></span>
                                        <span class="break-words">{{ $line }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        @if($descLength > 260)
                            <div
                                x-show="!expanded"
                                class="pointer-events-none absolute bottom-14 left-0 right-0 h-16 bg-gradient-to-t from-white to-transparent"
                            ></div>
                            <button
                                type="button"
                                @click="expanded = !expanded"
                                class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-700 transition hover:text-indigo-800"
                            >
                                <i class="ri-arrow-down-s-line transition" :class="expanded ? 'rotate-180' : ''"></i>
                                <span x-text="expanded ? 'Свернуть' : 'Показать больше'"></span>
                            </button>
                        @endif
                    @elseif($description !== '')
                        <div
                            x-bind:class="expanded ? '' : 'max-h-44 overflow-hidden'"
                            class="text-slate-700"
                        >
                            <div class="whitespace-pre-wrap break-words leading-7">{{ $description }}</div>
                        </div>

                        @if($descLength > 260)
                            <div
                                x-show="!expanded"
                                class="pointer-events-none absolute bottom-14 left-0 right-0 h-16 bg-gradient-to-t from-white to-transparent"
                            ></div>
                            <button
                                type="button"
                                @click="expanded = !expanded"
                                class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-indigo-700 transition hover:text-indigo-800"
                            >
                                <i class="ri-arrow-down-s-line transition" :class="expanded ? 'rotate-180' : ''"></i>
                                <span x-text="expanded ? 'Свернуть' : 'Показать больше'"></span>
                            </button>
                        @endif
                    @else
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-6 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-xl text-slate-400">
                                <i class="ri-file-text-line"></i>
                            </div>
                            <h3 class="mt-3 font-semibold text-slate-900">Описание пока не заполнено</h3>
                            <p class="mt-1 text-sm text-slate-500">Можно уточнить детали у продавца через чат.</p>
                        </div>
                    @endif
                </section>

                @if($details->count() > $quickDetails->count())
                    <section class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm sm:p-5">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 text-base font-bold text-slate-950">
                                <i class="ri-list-check-3 text-indigo-600"></i>
                                Детали товара
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @foreach($details->skip($quickDetails->count())->take(5) as $detail)
                                <div class="grid gap-2 py-3 sm:grid-cols-[260px_minmax(0,1fr)]">
                                    <div class="font-semibold text-slate-500">{{ $detail['label'] }}</div>
                                    <div class="break-words text-slate-900">{{ $detail['value'] }}</div>
                                </div>
                            @endforeach
                        </div>

                        <button
                            type="button"
                            @click="tab='details'"
                            class="mt-4 inline-flex items-center justify-center rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700"
                        >
                            Смотреть все характеристики
                        </button>
                    </section>
                @endif
            </div>
        </section>

        <section x-show="tab==='details'" x-transition.opacity.duration.250ms>
            <div class="mb-5">
                <div>
                    <h2 class="text-lg font-bold text-slate-950">Подробная информация о товаре</h2>
                    <p class="mt-1 max-w-2xl text-sm text-slate-500">
                        Основные данные и характеристики товара в одном месте. Без лишних кнопок и дублей.
                    </p>
                </div>
            </div>

            @if($details->isNotEmpty())
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
                    <dl class="grid gap-2">
                        @foreach($details as $detail)
                            <div class="grid gap-2 rounded-2xl bg-white px-4 py-3 ring-1 ring-slate-100 sm:grid-cols-[minmax(180px,260px)_minmax(0,1fr)] sm:items-start sm:px-5">
                                <dt class="text-sm font-medium leading-6 text-slate-500">{{ $detail['label'] }}</dt>
                                <dd class="min-w-0 break-words text-sm font-semibold leading-6 text-slate-900">{{ $detail['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-xl text-slate-400">
                        <i class="ri-list-check-3"></i>
                    </div>
                    <h3 class="mt-3 font-semibold text-slate-900">Подробные данные ещё не заполнены</h3>
                    <p class="mt-1 text-sm text-slate-500">Когда продавец добавит характеристики, они появятся здесь.</p>
                </div>
            @endif
        </section>

        <section
            id="reviews"
            x-show="tab==='reviews'"
            x-cloak
            class="space-y-6"
            x-transition.opacity.duration.250ms
        >
            @auth
                <div
                    x-data="{
                        editing: {{ $myReview ? 'false' : 'true' }},
                        rating:  {{ $myReview->rating ?? 0 }},
                        hoverRating: 0
                    }"
                    class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm"
                >
                    <template x-if="!editing">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">Ваш отзыв</h3>
                                <p class="mt-1 text-slate-700">{{ $myReview->body ?? 'Без текста' }}</p>

                                @if ($myReview && $myReview->images->count())
                                    <div class="mt-3 flex flex-wrap gap-3">
                                        @foreach ($myReview->images as $img)
                                            <a href="{{ $img->url }}" target="_blank" rel="noopener noreferrer">
                                                <img src="{{ $img->thumb_url }}" class="h-24 w-24 rounded-xl border object-cover transition hover:scale-105" alt="">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <button
                                type="button"
                                @click="editing = true"
                                class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
                            >
                                <i class="ri-edit-line"></i>
                                Изменить
                            </button>
                        </div>
                    </template>

                    <template x-if="editing">
                        <form method="post" action="{{ route('review.store', $product) }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf

                            <div>
                                <h3 class="text-lg font-semibold text-slate-900">{{ $myReview ? 'Изменить отзыв' : 'Оставить отзыв' }}</h3>
                                <p class="mt-1 text-sm text-slate-500">Отзыв появится на странице после модерации.</p>
                            </div>

                            <div class="flex items-center gap-2" @mouseleave="hoverRating = 0">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button" @mouseover="hoverRating={{ $i }}" @click="rating={{ $i }}" class="transition">
                                        <svg
                                            :class="{
                                                'text-yellow-400 scale-110': {{ $i }} <= (hoverRating || rating),
                                                'text-gray-300': {{ $i }} > (hoverRating || rating)
                                            }"
                                            class="h-8 w-8 cursor-pointer transition-all duration-200"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.945a1 1 0 00.95.69h4.148c.969 0 1.371 1.24.588 1.81l-3.357 2.44a1 1 0 00-.364 1.118l1.286 3.945c.3.921-.755 1.688-1.54 1.118l-3.357-2.44a1 1 0 00-1.175 0l-3.357 2.44c-.784.57-1.839-.197-1.54-1.118l1.286-3.945a1 1 0 00-.364-1.118L2.075 9.372c-.783-.57-.38-1.81.588-1.81h4.148a1 1 0 00.95-.69l1.286-3.945z" />
                                        </svg>
                                    </button>
                                @endfor
                                <input type="hidden" name="rating" :value="rating">
                            </div>

                            <textarea
                                name="body"
                                rows="4"
                                placeholder="Расскажите, что понравилось, что не подошло, как товар выглядит в жизни..."
                                class="w-full rounded-xl border border-slate-200 p-3 text-sm focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                            >{{ $myReview->body ?? '' }}</textarea>

                            <div>
                                <input
                                    type="file"
                                    name="images[]"
                                    multiple
                                    accept="image/*"
                                    class="block w-full cursor-pointer rounded-xl border border-slate-200 p-2 text-sm text-slate-600 transition hover:border-indigo-300"
                                >
                                <p class="mt-1 text-xs text-slate-400">Можно добавить до 3 фото.</p>
                            </div>

                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                    <i class="ri-save-3-line"></i>
                                    {{ $myReview ? 'Сохранить изменения' : 'Отправить отзыв' }}
                                </button>

                                @if ($myReview)
                                    <button type="button" @click="editing = false" class="text-sm font-semibold text-slate-500 hover:text-slate-700">
                                        Отмена
                                    </button>
                                @endif
                            </div>
                        </form>
                    </template>
                </div>
            @endauth

            <div class="space-y-4">
                @forelse ($reviews as $r)
                    <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700">
                                    {{ mb_substr($r->user->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $r->user->name }}</div>
                                    <div class="flex text-sm">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <span class="{{ $i <= $r->rating ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                                        @endfor
                                    </div>
                                </div>
                            </div>

                            <div class="text-xs text-slate-400">{{ $r->created_at->diffForHumans() }}</div>
                        </div>

                        <div class="mt-3 border-t border-slate-100 pt-3 leading-7 text-slate-700">
                            {{ $r->body }}
                        </div>

                        @if ($r->images->count())
                            <div class="mt-3 flex flex-wrap gap-3">
                                @foreach ($r->images as $img)
                                    <a href="{{ $img->url }}" target="_blank" rel="noopener noreferrer">
                                        <img src="{{ $img->thumb_url }}" class="h-24 w-24 rounded-xl border object-cover transition hover:scale-105" alt="">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-xl text-slate-400">
                            <i class="ri-chat-smile-2-line"></i>
                        </div>
                        <h3 class="mt-3 font-semibold text-slate-900">Отзывов пока нет</h3>
                        <p class="mt-1 text-sm text-slate-500">После покупки покупатели смогут поделиться впечатлениями о товаре.</p>
                    </div>
                @endforelse

                <div class="mt-6">
                    {{ $reviews->links() }}
                </div>
            </div>
        </section>
    </div>
</div>
