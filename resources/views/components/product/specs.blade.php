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

                @php
                    $value = $attr->pivot->value;

                    // если атрибут — цвет, пытаемся найти полноценный объект цвета
                    $colorObj = null;
                    if ($attr->type === 'color' && $value) {
                        $colorObj = $attr->colors->firstWhere('id', $value);
                    }
                @endphp

                <div class="flex justify-between items-center">

                    {{-- Название характеристики --}}
                    <span class="text-gray-500">{{ $attr->name }}</span>

                    {{-- ================== ЦВЕТ ================== --}}
                    @if ($colorObj)
                        <span class="flex items-center gap-2 font-medium">

                            {{-- Кружок цвета --}}
                            <span class="w-4 h-4 rounded-full border"
                                  style="background: {{ $colorObj->hex }}"></span>

                            {{-- Название --}}
                            {{ $colorObj->name }}

                        </span>

                    {{-- ================= Обычное значение ================= --}}
                    @else
                        <span class="font-medium">{{ $value ?? '-' }}</span>
                    @endif
                </div>

            @endforeach

        </div>
    </div>
@endif

</div>
