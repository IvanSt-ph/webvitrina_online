@if($conversation->product)
    <div class="flex min-w-0 shrink-0 items-center gap-2.5 border-b border-brand-100 bg-brand-50/70 px-3 py-2.5 sm:gap-3 sm:px-5 sm:py-3">
        <img data-image-candidates="{{ json_encode($conversation->context_image_candidates) }}" data-image-fallback="{{ asset(\App\Models\Product::IMAGE_FALLBACK_ASSET) }}" src="{{ $conversation->context_image_url }}"
             alt="{{ $conversation->context_title }}"
             class="h-11 w-11 shrink-0 rounded-xl border border-brand-100 object-cover sm:h-12 sm:w-12">
        <span class="min-w-0 flex-1">
            <span class="block text-[11px] font-semibold uppercase tracking-wide text-brand-500 sm:text-xs">
                {{ $conversation->order ? 'Заказ ' . $conversation->order->number : 'Товар в этом диалоге' }}
            </span>
            <span class="mt-0.5 block truncate text-sm font-semibold text-neutral-900">{{ $conversation->context_title }}</span>
        </span>
        <span class="flex shrink-0 flex-wrap justify-end gap-1.5 sm:gap-2">
            @if($conversation->order && auth()->id() === $conversation->order->user_id)
                <a href="{{ route('orders.show', $conversation->order) }}"
                   class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-brand-200 bg-white text-brand-700 transition hover:bg-brand-100 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 sm:h-auto sm:w-auto sm:border-0 sm:bg-transparent sm:p-0 sm:text-xs sm:font-semibold sm:hover:bg-transparent sm:hover:underline"
                   aria-label="Открыть заказ">
                    <i class="ri-shopping-bag-3-line sm:hidden" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Заказ</span>
                </a>
            @endif
            @if(! $conversation->product->trashed() && $conversation->product->status === 'active')
                <a href="{{ route('product.show', $conversation->product->slug) }}"
                   class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-brand-200 bg-white text-brand-700 transition hover:bg-brand-100 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100 sm:h-auto sm:w-auto sm:border-0 sm:bg-transparent sm:p-0 sm:text-xs sm:font-semibold sm:hover:bg-transparent sm:hover:underline"
                   aria-label="Открыть товар">
                    <i class="ri-external-link-line sm:hidden" aria-hidden="true"></i>
                    <span class="hidden sm:inline">Товар</span>
                </a>
            @endif
        </span>
    </div>
@endif
