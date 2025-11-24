@if(!empty($items))
    <div class="mt-12">
        <h2 class="text-xl font-semibold mb-4">Похожие товары</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach ($items as $item)
                <a href="{{ route('product.show', $item->slug) }}"
                   class="bg-white border rounded-xl p-3 hover:shadow-lg transition group">
                    @if ($item->image)
                        <img
                            src="{{ asset('storage/'.$item->image) }}"
                            class="w-full h-48 object-cover rounded-lg mb-2 group-hover:scale-105 transition-transform"
                        />
                    @endif

                    <div class="text-sm font-medium line-clamp-2">
                        {{ $item->title }}
                    </div>

                    <div class="text-indigo-600 font-semibold mt-1">
                        {{ number_format($item->price, 0, ',', ' ') }} ₽
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endif
