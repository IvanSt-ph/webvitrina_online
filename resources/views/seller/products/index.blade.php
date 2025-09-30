<x-app-layout title="Мои товары">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Мои товары</h1>
    <a href="{{ route('seller.products.create') }}"
       class="px-3 py-1.5 bg-indigo-600 text-white rounded">Добавить</a>
  </div>

  <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($products as $p)
      <div class="bg-white border rounded p-3">
        <div class="aspect-video bg-gray-100 rounded mb-2 overflow-hidden">
          @if($p->image)
            <img src="{{ asset('storage/'.$p->image) }}" class="w-full h-full object-cover"/>
          @endif
        </div>
        <div class="font-medium">{{ $p->title }}</div>
<div class="text-sm text-gray-600">
  Цена: {{ number_format($p->price, 2, ',', ' ') }} ₽, 
  Остаток: {{ $p->stock }}
</div>

        <div class="mt-2 flex gap-2">
          <a href="{{ route('seller.products.edit',$p) }}"
             class="px-2 py-1 border rounded">Изменить</a>
          <form method="post" action="{{ route('seller.products.destroy',$p) }}">
            @csrf @method('DELETE')
            <button class="px-2 py-1 border rounded text-red-600">Удалить</button>
          </form>
        </div>
      </div>
    @endforeach
  </div>

  <div class="mt-4">{{ $products->links() }}</div>
</x-app-layout>
