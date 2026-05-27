@extends('admin.layout')
@section('title', 'Отзывы')

@section('content')
@php
    $statusMeta = [
        'all' => ['label' => 'Все', 'icon' => 'ri-inbox-2-line', 'class' => 'border-gray-200 bg-white text-gray-700'],
        \App\Models\Review::STATUS_PENDING => ['label' => 'На модерации', 'icon' => 'ri-time-line', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'],
        \App\Models\Review::STATUS_APPROVED => ['label' => 'Одобрено', 'icon' => 'ri-check-line', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'],
        \App\Models\Review::STATUS_REJECTED => ['label' => 'Отклонено', 'icon' => 'ri-close-line', 'class' => 'border-rose-200 bg-rose-50 text-rose-700'],
    ];

    $statusBadge = [
        \App\Models\Review::STATUS_PENDING => 'border-amber-200 bg-amber-50 text-amber-700',
        \App\Models\Review::STATUS_APPROVED => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        \App\Models\Review::STATUS_REJECTED => 'border-rose-200 bg-rose-50 text-rose-700',
    ];

    $rating = $rating ?? 'all';
    $q = $q ?? '';
@endphp

<div x-data="reviewPanel()" class="space-y-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Отзывы</h1>
            <p class="mt-1 text-sm text-gray-500">Модерация отзывов покупателей, фотографий и оценок товаров.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach(['all', \App\Models\Review::STATUS_PENDING, \App\Models\Review::STATUS_APPROVED, \App\Models\Review::STATUS_REJECTED] as $key)
                @php
                    $meta = $statusMeta[$key];
                    $active = $status === $key;
                    $href = route('admin.reviews.index', ['status' => $key, 'sort' => $sort, 'rating' => $rating, 'q' => $q]);
                    $count = $counters[$key] ?? 0;
                @endphp
                <a href="{{ $href }}"
                   class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition {{ $active ? $meta['class'] . ' ring-2 ring-indigo-100' : 'border-gray-200 bg-white text-gray-600 hover:border-indigo-200 hover:text-indigo-600' }}">
                    <i class="{{ $meta['icon'] }}"></i>
                    {{ $meta['label'] }}
                    <span class="rounded-full bg-white/80 px-1.5 text-xs">{{ $count }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <form method="GET" action="{{ route('admin.reviews.index') }}" class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
        <input type="hidden" name="status" value="{{ $status }}">

        <div class="grid gap-3 lg:grid-cols-[minmax(220px,1fr)_auto_auto_auto] lg:items-center">
            <label class="relative block">
                <i class="ri-search-line absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="search"
                       name="q"
                       value="{{ $q }}"
                       placeholder="Поиск по отзыву, товару, имени или email"
                       class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </label>

            <select name="rating" class="min-w-[11rem] rounded-lg border border-gray-300 px-3 py-2 pr-9 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="all" @selected($rating === 'all')>Все оценки</option>
                <option value="5" @selected($rating === '5')>5 звёзд</option>
                <option value="4" @selected($rating === '4')>4 звезды</option>
                <option value="3" @selected($rating === '3')>3 звезды</option>
                <option value="2" @selected($rating === '2')>2 звезды</option>
                <option value="1" @selected($rating === '1')>1 звезда</option>
            </select>

            <select name="sort" class="min-w-[12rem] rounded-lg border border-gray-300 px-3 py-2 pr-9 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="desc" @selected($sort === 'desc')>Сначала новые</option>
                <option value="asc" @selected($sort === 'asc')>Сначала старые</option>
            </select>

            <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white hover:bg-indigo-700">
                <i class="ri-filter-3-line"></i>
                Применить
            </button>
        </div>

        <div class="mt-3 flex flex-col gap-2 text-sm text-gray-500 sm:flex-row sm:items-center sm:justify-between">
            <span>Показано: {{ $reviews->firstItem() ?? 0 }}-{{ $reviews->lastItem() ?? 0 }} из {{ $reviews->total() }}</span>
            @if($q !== '' || $rating !== 'all')
                <a href="{{ route('admin.reviews.index', ['status' => $status, 'sort' => $sort]) }}" class="font-medium text-indigo-600 hover:text-indigo-700">
                    Сбросить поиск и оценку
                </a>
            @endif
        </div>
    </form>

    <div x-show="selected.length"
         x-cloak
         class="flex flex-col gap-3 rounded-lg border border-indigo-100 bg-indigo-50 p-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm font-medium text-indigo-800">
            Выбрано отзывов: <span x-text="selected.length"></span>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" @click="bulkAction('approve')" class="inline-flex h-9 items-center gap-1 rounded-lg bg-emerald-600 px-3 text-sm font-semibold text-white hover:bg-emerald-700">
                <i class="ri-check-line"></i> Одобрить
            </button>
            <button type="button" @click="bulkAction('reject')" class="inline-flex h-9 items-center gap-1 rounded-lg border border-amber-200 bg-white px-3 text-sm font-semibold text-amber-700 hover:bg-amber-50">
                <i class="ri-close-line"></i> Отклонить
            </button>
            <button type="button" @click="bulkAction('delete')" class="inline-flex h-9 items-center gap-1 rounded-lg border border-rose-200 bg-white px-3 text-sm font-semibold text-rose-600 hover:bg-rose-50">
                <i class="ri-delete-bin-line"></i> Удалить
            </button>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        @forelse($reviews as $review)
            @php
                $product = $review->product;
                $badgeClass = $statusBadge[$review->status] ?? 'border-gray-200 bg-gray-50 text-gray-700';
                $body = trim((string) $review->body);
            @endphp

            <article id="review-{{ $review->id }}"
                     class="grid gap-4 border-b border-gray-100 p-4 transition last:border-b-0 hover:bg-gray-50 xl:grid-cols-[52px_minmax(210px,.85fr)_minmax(280px,1.25fr)_auto]">
                <div class="flex flex-col items-start gap-1">
                    <span class="text-[11px] font-medium uppercase text-gray-400">Выбор</span>
                    <input type="checkbox"
                           value="{{ $review->id }}"
                           x-model.number="selected"
                           @click.stop
                           title="Выбрать для массовых действий"
                           class="mt-2 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                </div>

                <div class="min-w-0">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-indigo-50 font-semibold text-indigo-700">
                            {{ mb_substr($review->user->name ?? '?', 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <div class="truncate font-medium text-gray-900">{{ $review->user->name ?? 'Пользователь удалён' }}</div>
                            <div class="truncate text-xs text-gray-500">{{ $review->user->email ?? 'email недоступен' }}</div>
                            <div class="mt-2 inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium {{ $badgeClass }}">
                                <i class="{{ $statusMeta[$review->status]['icon'] ?? 'ri-information-line' }}"></i>
                                {{ $statusMeta[$review->status]['label'] ?? 'Неизвестно' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center text-amber-400" aria-label="Оценка {{ $review->rating }} из 5">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="{{ $i <= $review->rating ? 'ri-star-fill' : 'ri-star-line text-gray-300' }}"></i>
                            @endfor
                        </div>
                        <span class="text-xs text-gray-400">{{ $review->created_at->format('d.m.Y H:i') }}</span>
                    </div>

                    <p class="mt-2 text-sm leading-6 text-gray-700" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                        {{ $body !== '' ? $body : 'Без текстового комментария.' }}
                    </p>

                    @if($review->status === \App\Models\Review::STATUS_REJECTED && filled($review->rejection_reason))
                        <div class="mt-2 text-xs text-rose-600">
                            Причина: {{ \Illuminate\Support\Str::limit($review->rejection_reason, 120) }}
                        </div>
                    @endif

                    @if($review->images->isNotEmpty())
                        <div class="mt-2 flex gap-1.5">
                            @foreach($review->images->take(4) as $image)
                                <button type="button" class="h-9 w-9 overflow-hidden rounded-md border border-gray-200" @click.stop="openImage('{{ asset('storage/' . $image->path) }}')">
                                    <img src="{{ asset('storage/' . $image->path) }}" alt="Фото отзыва" class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-2 truncate text-sm text-gray-500">
                        <span class="text-gray-400">Товар:</span>
                        @if($product)
                            <a href="{{ route('product.show', $product->slug ?? $product->id) }}"
                               target="_blank"
                               title="{{ $product->title }}"
                               class="inline-block max-w-[min(100%,34rem)] truncate align-bottom font-medium text-gray-700 hover:text-indigo-600"
                               @click.stop>
                                {{ $product->title }}
                            </a>
                        @else
                            <span class="font-medium text-gray-700">Товар удалён</span>
                        @endif
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                    <button type="button" @click.stop="openModal({{ $review->toJson() }})" class="inline-flex h-9 items-center justify-center gap-1 rounded-lg border border-indigo-100 bg-indigo-50 px-3 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                        <i class="ri-eye-line"></i> Подробнее
                    </button>

                    @if($review->status !== \App\Models\Review::STATUS_APPROVED)
                        <button type="button" @click.stop="changeStatus({{ $review->id }}, 'approve')" class="inline-flex h-9 items-center justify-center gap-1 rounded-lg bg-emerald-600 px-3 text-sm font-semibold text-white hover:bg-emerald-700">
                            <i class="ri-check-line"></i> Одобрить
                        </button>
                    @endif

                    @if($review->status !== \App\Models\Review::STATUS_REJECTED)
                        <button type="button" @click.stop="changeStatus({{ $review->id }}, 'reject')" class="inline-flex h-9 items-center justify-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-3 text-sm font-semibold text-amber-700 hover:bg-amber-100">
                            <i class="ri-close-line"></i> Отклонить
                        </button>
                    @endif

                    <button type="button" @click.stop="confirmDelete({{ $review->id }})" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50" title="Удалить отзыв">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            </article>
        @empty
            <div class="px-4 py-14 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                    <i class="ri-chat-3-line text-2xl"></i>
                </div>
                <h2 class="text-base font-semibold text-gray-900">Отзывы не найдены</h2>
                <p class="mt-1 text-sm text-gray-500">Попробуйте изменить фильтры или поиск.</p>
            </div>
        @endforelse
    </div>

    <div>{{ $reviews->links() }}</div>

    <template x-teleport="body">
        <div x-show="modal.open" x-cloak x-transition.opacity.duration.200ms class="fixed inset-0 z-50 flex items-start justify-center bg-gray-950/55 p-0" @keydown.escape.window="modal.open = false">
            <div @click.away="modal.open = false" class="max-h-screen w-full max-w-4xl overflow-y-auto rounded-none bg-white shadow-2xl">
                <div class="sticky top-0 z-10 flex items-start justify-between border-b border-gray-100 bg-white px-5 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Просмотр отзыва</h2>
                        <p class="text-sm text-gray-500" x-text="modal.data.user?.email ?? 'email недоступен'"></p>
                    </div>
                    <button type="button" @click="modal.open = false" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700" title="Закрыть">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                <div class="space-y-5 px-5 py-5">
                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="rounded-lg border border-gray-200 p-4">
                            <div class="mb-3 text-xs font-semibold uppercase text-gray-400">Покупатель</div>
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-indigo-50 font-semibold text-indigo-700">
                                    <span x-text="modal.data.user?.name?.[0] ?? '?'"></span>
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-gray-900" x-text="modal.data.user?.name ?? 'Пользователь удалён'"></div>
                                    <div class="truncate text-sm text-gray-500" x-text="modal.data.user?.email ?? 'email недоступен'"></div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4">
                            <div class="mb-3 text-xs font-semibold uppercase text-gray-400">Оценка и дата</div>
                            <div class="flex items-center gap-2 text-amber-400">
                                <template x-for="star in 5" :key="star">
                                    <i :class="star <= (modal.data.rating || 0) ? 'ri-star-fill' : 'ri-star-line text-gray-300'"></i>
                                </template>
                                <span class="text-sm text-gray-500" x-text="(modal.data.rating || 0) + ' из 5'"></span>
                            </div>
                            <div class="mt-2 text-sm text-gray-500" x-text="formatDate(modal.data.created_at)"></div>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4">
                            <div class="mb-3 text-xs font-semibold uppercase text-gray-400">Модерация</div>
                            <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-sm font-medium" :class="statusClass(modal.data.status)" x-text="statusLabel(modal.data.status)"></span>
                            <div class="mt-3 space-y-1 text-sm text-gray-500">
                                <div x-show="modal.data.moderator">
                                    <span class="text-gray-400">Модератор:</span>
                                    <span x-text="modal.data.moderator?.name ?? modal.data.moderator?.email"></span>
                                </div>
                                <div x-show="modal.data.moderated_at">
                                    <span class="text-gray-400">Проверено:</span>
                                    <span x-text="formatDate(modal.data.moderated_at)"></span>
                                </div>
                                <div x-show="!modal.data.moderated_at">Пока ожидает решения.</div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                        <div class="mb-2 text-xs font-semibold uppercase text-gray-400">Полный текст отзыва</div>
                        <p class="whitespace-pre-line break-words text-sm leading-6 text-gray-800" x-text="modal.data.body || 'Без текстового комментария.'"></p>
                    </div>

                    <div class="rounded-lg border border-gray-200 p-4">
                        <div class="mb-2 text-xs font-semibold uppercase text-gray-400">Товар</div>
                        <a :href="productUrl(modal.data.product)" target="_blank" class="block break-words font-semibold leading-6 text-gray-900 hover:text-indigo-600" x-text="modal.data.product?.title ?? 'Товар удалён'"></a>
                        <div x-show="modal.data.product?.sku" class="mt-1 text-sm text-gray-500">
                            <span class="text-gray-400">SKU:</span>
                            <span x-text="modal.data.product?.sku"></span>
                        </div>
                    </div>

                    <div x-show="modal.data.status === 'rejected' && modal.data.rejection_reason" class="rounded-lg border border-rose-100 bg-rose-50 p-4 text-sm leading-6 text-rose-700">
                        <div class="mb-1 font-semibold">Причина отклонения</div>
                        <div x-text="modal.data.rejection_reason"></div>
                    </div>

                    <template x-if="modal.data.images && modal.data.images.length">
                        <div>
                            <div class="mb-2 text-sm font-semibold text-gray-900">Фото покупателя из отзыва</div>
                            <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                                <template x-for="img in modal.data.images" :key="img.id">
                                    <button type="button" class="aspect-square overflow-hidden rounded-lg border border-gray-200 bg-gray-50" @click.stop="openImage('/storage/' + img.path)">
                                        <img :src="'/storage/' + img.path" alt="Фото отзыва" class="h-full w-full object-cover">
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-wrap justify-end gap-2 border-t border-gray-100 px-5 py-4">
                    <button type="button" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="modal.open = false">Закрыть</button>

                    <template x-if="modal.data.status !== 'approved'">
                        <button type="button" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700" @click="changeStatus(modal.data.id, 'approve')">
                            <i class="ri-check-line"></i> Одобрить
                        </button>
                    </template>

                    <template x-if="modal.data.status !== 'rejected'">
                        <button type="button" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-4 text-sm font-semibold text-amber-700 hover:bg-amber-100" @click="changeStatus(modal.data.id, 'reject')">
                            <i class="ri-close-line"></i> Отклонить
                        </button>
                    </template>

                    <button type="button" class="inline-flex h-10 items-center justify-center gap-1 rounded-lg border border-rose-200 px-4 text-sm font-semibold text-rose-600 hover:bg-rose-50" @click="confirmDelete(modal.data.id)">
                        <i class="ri-delete-bin-line"></i> Удалить
                    </button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="reasonModal.open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-950/55 p-4">
            <div @click.away="reasonModal.open = false" class="w-full max-w-md rounded-lg bg-white p-5 shadow-2xl">
                <h2 class="text-lg font-semibold text-gray-900">Причина отклонения</h2>
                <p class="mt-1 text-sm text-gray-500">Она будет видна покупателю в разделе “Мои отзывы”.</p>
                <textarea x-model="reasonModal.reason" rows="4" class="mt-4 w-full resize-none rounded-lg border border-gray-300 p-3 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Например: отзыв содержит недопустимые выражения или не относится к товару"></textarea>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="reasonModal.open = false">Отмена</button>
                    <button type="button" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700" @click="submitRejection()">Отклонить</button>
                </div>
            </div>
        </div>
    </template>

    <div x-show="toast.show" x-cloak x-transition.opacity.duration.200ms class="fixed bottom-5 right-5 z-50">
        <div :class="toast.type === 'success' ? 'bg-gray-900' : 'bg-rose-600'" class="rounded-lg px-4 py-3 text-sm font-medium text-white shadow-lg">
            <span x-text="toast.message"></span>
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="lightbox.open" x-cloak x-transition.opacity.duration.200ms class="fixed inset-0 z-[9999] !mt-0 flex items-center justify-center bg-black/75 p-4" @click.self="lightbox.open = false" @keydown.escape.window="lightbox.open = false">
            <div class="relative max-h-full max-w-4xl">
                <button type="button" @click="lightbox.open = false" class="absolute right-2 top-2 z-10 inline-flex h-9 w-9 items-center justify-center rounded-lg bg-white/90 text-gray-700 hover:bg-white" title="Закрыть">
                    <i class="ri-close-line text-xl"></i>
                </button>
                <img :src="lightbox.url" alt="Фото отзыва" class="max-h-[86vh] rounded-lg bg-white object-contain">
            </div>
        </div>
    </template>
</div>

<script>
function reviewPanel() {
    return {
        filters: { status: @json($status), sort: @json($sort), rating: @json($rating), q: @json($q) },
        selected: [],
        toast: { show: false, message: '', type: 'success' },
        modal: { open: false, data: {} },
        reasonModal: { open: false, id: null, bulk: false, reason: '' },
        lightbox: { open: false, url: '' },

        async openModal(data) {
            try {
                const res = await fetch(`/admin/reviews/${data.id}`);
                this.modal.data = res.ok ? await res.json() : data;
            } catch {
                this.modal.data = data;
            }
            this.modal.open = true;
        },

        showToast(message, type = 'success') {
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            setTimeout(() => this.toast.show = false, 2400);
        },

        changeStatus(id, action) {
            if (action === 'reject') {
                this.reasonModal = { open: true, id, bulk: false, reason: '' };
                return;
            }

            this.sendStatus(id, action);
        },

        async sendStatus(id, action, reason = '') {
            try {
                const res = await fetch(`/admin/reviews/${id}/${action}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ reason })
                });

                if (!res.ok) throw new Error();

                this.showToast('Статус обновлён');
                setTimeout(() => window.location.reload(), 450);
            } catch {
                this.showToast('Не удалось обновить статус', 'error');
            }
        },

        bulkAction(action) {
            if (!this.selected.length) return;

            if (action === 'reject') {
                this.reasonModal = { open: true, id: null, bulk: true, reason: '' };
                return;
            }

            if (action === 'delete' && !confirm('Удалить выбранные отзывы безвозвратно?')) {
                return;
            }

            this.sendBulk(action);
        },

        async sendBulk(action, reason = '') {
            try {
                const res = await fetch('/admin/reviews/bulk', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ ids: this.selected, action, reason })
                });

                if (!res.ok) throw new Error();

                this.showToast('Действие выполнено');
                setTimeout(() => window.location.reload(), 450);
            } catch {
                this.showToast('Не удалось выполнить действие', 'error');
            }
        },

        submitRejection() {
            const reason = (this.reasonModal.reason || '').trim();
            if (!reason) {
                this.showToast('Укажите причину отклонения', 'error');
                return;
            }

            if (this.reasonModal.bulk) {
                this.sendBulk('reject', reason);
            } else {
                this.sendStatus(this.reasonModal.id, 'reject', reason);
            }

            this.reasonModal.open = false;
        },

        confirmDelete(id) {
            if (confirm('Удалить отзыв безвозвратно?')) {
                this.deleteReview(id);
            }
        },

        async deleteReview(id) {
            try {
                const res = await fetch(`/admin/reviews/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (!res.ok) throw new Error();

                this.showToast('Отзыв удалён');
                setTimeout(() => window.location.reload(), 450);
            } catch {
                this.showToast('Не удалось удалить отзыв', 'error');
            }
        },

        statusLabel(status) {
            switch (status) {
                case 'approved': return 'Одобрен';
                case 'rejected': return 'Отклонён';
                case 'pending': return 'На модерации';
                default: return 'Неизвестно';
            }
        },

        statusClass(status) {
            switch (status) {
                case 'approved': return 'border-emerald-200 bg-emerald-50 text-emerald-700';
                case 'rejected': return 'border-rose-200 bg-rose-50 text-rose-700';
                case 'pending': return 'border-amber-200 bg-amber-50 text-amber-700';
                default: return 'border-gray-200 bg-gray-50 text-gray-700';
            }
        },

        formatDate(dateString) {
            if (!dateString) return 'Дата неизвестна';
            return new Date(dateString).toLocaleString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        productUrl(product) {
            if (!product) return '#';
            return product.slug ? `/p/${product.slug}` : `/p/${product.id}`;
        },

        openImage(url) {
            this.lightbox.url = url;
            this.lightbox.open = true;
        },
    }
}
</script>
@endsection
