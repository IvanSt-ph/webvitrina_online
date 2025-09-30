<x-app-layout title="Заказы">
  <h1 class="text-2xl font-bold mb-4">Мои заказы</h1>
  <div class="space-y-4">
    @foreach($orders as $o)
      <div class="bg-white border rounded p-3">
        <div class="font-semibold">Заказ #{{ $o->id }} — {{ number_format($o->total/100,2,',',' ') }} ₽ — {{ $o->status }}</div>
        <ul class="mt-2 list-disc list-inside text-sm text-gray-700">
          @foreach($o->items as $it)
            <li>{{ $it->product->title }} × {{ $it->qty }}</li>
          @endforeach
        </ul>
      </div>
    @endforeach
  </div>
</x-app-layout>
