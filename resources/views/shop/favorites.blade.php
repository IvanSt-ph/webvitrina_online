<x-app-layout title="Избранное">
  <h1 class="text-2xl font-bold mb-4">Избранное</h1>
  <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    @foreach($items as $f)
      <div class="bg-white rounded-xl border p-3 flex flex-col">
        <a href="{{ route('product.show',$f->product) }}" class="aspect-square bg-gray-100 rounded mb-2 overflow-hidden">
          @if($f->product->image)
            <img src="{{ asset('storage/'.$f->product->image) }}" class="w-full h-full object-cover"/>
          @endif
        </a>
        <div class="font-medium line-clamp-2">{{ $f->product->title }}</div>
        <form method="post" action="{{ route('favorites.toggle',$f->product) }}" class="mt-auto">@csrf
          <button class="mt-2 px-3 py-1.5 border rounded">Убрать</button>
        </form>
      </div>
    @endforeach
  </div>
</x-app-layout>
