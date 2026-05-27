@if($conversation->product)
    <div class="flex shrink-0 items-center gap-3 border-b border-indigo-100 bg-indigo-50/70 px-4 py-3 sm:px-5">
        <img src="{{ $conversation->product->image_thumb_url }}"
             alt="{{ $conversation->product->title }}"
             class="h-12 w-12 shrink-0 rounded-xl border border-indigo-100 object-cover">
        <span class="min-w-0 flex-1">
            <span class="block text-xs font-semibold uppercase tracking-wide text-indigo-500">
                {{ $conversation->order ? 'Заказ ' . $conversation->order->number : 'Товар в этом диалоге' }}
            </span>
            <span class="mt-0.5 block truncate text-sm font-semibold text-slate-900">{{ $conversation->product->title }}</span>
        </span>
        <span class="flex shrink-0 flex-wrap justify-end gap-2">
            @if($conversation->order && auth()->id() === $conversation->order->user_id)
                <a href="{{ route('orders.show', $conversation->order) }}" class="text-xs font-semibold text-indigo-700 hover:underline">Заказ</a>
            @endif
            @if(! $conversation->product->trashed() && $conversation->product->status === 'active')
                <a href="{{ route('product.show', $conversation->product->slug) }}" class="text-xs font-semibold text-indigo-700 hover:underline">Товар</a>
            @endif
        </span>
    </div>
@endif
