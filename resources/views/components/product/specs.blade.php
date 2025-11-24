@props(['product'])

<div class="space-y-4">

    {{-- Артикул --}}
    @if ($product->sku)
        <div>
            <div class="text-gray-500 text-sm">Артикул</div>
            <div class="font-medium">{{ $product->sku }}</div>
        </div>
    @endif

    {{-- Категория --}}
    @if ($product->category)
        <div>
            <div class="text-gray-500 text-sm">Категория</div>
            <div class="font-medium">{{ $product->category->name }}</div>
        </div>
    @endif

    {{-- Локация --}}
    @if ($product->city || $product->country)
        <div>
            <div class="text-gray-500 text-sm">Локация</div>
            <div class="font-medium">
                {{ $product->country->name ?? $product->city->country->name ?? '' }}
                @if ($product->city)
                    , {{ $product->city->name }}
                @endif
            </div>
        </div>
    @endif

    {{-- Адрес --}}
    @if ($product->address)
        <div>
            <div class="text-gray-500 text-sm">Адрес</div>
            <div class="font-medium">{{ $product->address }}</div>
        </div>
    @endif

    {{-- Атрибуты товара --}}
    @if ($product->attributes && count($product->attributes))
        <div class="pt-3 border-t">
            <h3 class="font-semibold text-gray-900 text-lg mb-2">Характеристики</h3>

            <div class="space-y-3">
                @foreach ($product->attributes as $attr)
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ $attr->name }}</span>
                        <span class="font-medium">{{ $attr->pivot->value ?? '-' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
