<x-buyer-layout title="Мои отзывы">
    <div class="p-4 space-y-4">

        @forelse($reviews as $review)
        
            <div class="bg-white p-4 rounded-xl border shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <div class="font-semibold">
                        {{ $review->product->title }}
                    </div>
                    <span class="text-yellow-500 text-sm">
                        ★ {{ $review->rating }}
                    </span>
                </div>

                <p class="text-gray-700 text-sm mb-2">
                    {{ $review->comment }}
                </p>

                <div class="text-xs text-gray-400">
                    {{ $review->created_at->format('d.m.Y') }}
                </div>
            </div>

        @empty
            <p class="text-center text-gray-500">У вас пока нет отзывов</p>
        @endforelse

    </div>
</x-buyer-layout>
