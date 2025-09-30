<x-app-layout :title="$category->name">
  <div class="max-w-7xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">{{ $category->name }}</h1>

    @if($products->count())
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($products as $product)
          <div class="border rounded p-3 bg-white">
            @if($product->image)
              <img src="{{ asset('storage/'.$product->image) }}" 
                   class="w-full h-40 object-cover mb-2" 
                   alt="{{ $product->title }}">
            @endif

            <div class="font-semibold">{{ $product->title }}</div>
            <div class="text-sm text-gray-500">
              {{ $product->category->name }}
            </div>
            <div class="mt-2 font-bold">{{ $product->price }} ₽</div>

            <a href="{{ route('product.show', $product) }}"
               class="mt-2 inline-block w-full text-center border rounded py-1 hover:bg-gray-50">
              Подробнее
            </a>
          </div>
        @endforeach
      </div>

      <div class="mt-6">
        {{ $products->links() }}
      </div>
    @else
      <p class="text-gray-500">В этой категории пока нет товаров.</p>
    @endif
  </div>
</x-app-layout>
