<x-app-layout title="Корзина">
  <h1 class="text-2xl font-bold mb-4">Корзина</h1>
  <div class="space-y-3">
    @foreach($items as $i)
      <div class="bg-white border rounded p-3 flex items-center gap-4">
        <div class="w-16 h-16 bg-gray-100 rounded overflow-hidden">
          @if($i->product->image)
            <img src="{{ asset('storage/'.$i->product->image) }}" class="w-full h-full object-cover"/>
          @endif
        </div>
        <div class="flex-1">
          <a href="{{ route('product.show',$i->product) }}" class="font-medium">{{ $i->product->title }}</a>
          <div class="text-sm text-gray-600">{{ number_format($i->product->price/100,2,',',' ') }} ₽</div>
        </div>
        <form method="post" action="{{ route('cart.update',$i) }}" class="flex items-center gap-2">@csrf @method('PATCH')
          <input type="number" min="1" name="qty" value="{{ $i->qty }}" class="w-20 border rounded p-1">
          <button class="px-2 py-1 border rounded">Обновить</button>
        </form>
        <form method="post" action="{{ route('cart.remove',$i) }}">@csrf @method('DELETE')
          <button class="px-2 py-1 border rounded text-red-600">Удалить</button>
        </form>
      </div>
    @endforeach
  </div>
  <div class="mt-4 flex items-center justify-between">
    <div class="text-xl font-bold">Итого: {{ number_format($total/100,2,',',' ') }} ₽</div>
    <form method="post" action="{{ route('checkout') }}">@csrf
      <button class="px-4 py-2 bg-emerald-600 text-white rounded">Оформить заказ</button>
    </form>
  </div>
</x-app-layout>
