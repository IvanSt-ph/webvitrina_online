{{-- магазин продавца и его товары --}}


<x-app-layout :title="$user->name">
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-bold mb-4">Магазин {{ $user->name }}</h1>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($products as $p)
                <a href="{{ route('product.show',$p->slug) }}" class="bg-white border rounded-lg p-3 hover:shadow">
                    @if($p->image)
                        <img src="{{ asset('storage/'.$p->image) }}" class="w-full h-40 object-cover rounded mb-2"/>
                    @endif
                    <div class="text-sm font-medium line-clamp-2">{{ $p->title }}</div>
                    <div class="text-indigo-600 font-semibold mt-1">
                        {{ number_format($p->price,0,',',' ') }} ₽
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    </div>
</x-app-layout>
