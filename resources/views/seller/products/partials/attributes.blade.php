@if(isset($attributes) && $attributes->count())
  <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">Характеристики товара</h2>

    <div class="space-y-6">

      @foreach($attributes as $attr)

        @php
          $val = old('attributes.'.$attr->id, $attr->value);
        @endphp

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ $attr->name }}
          </label>

          {{-- ================= SELECT ================= --}}
          @if($attr->type === 'select')
            <select name="attributes[{{ $attr->id }}]"
                    class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
              <option value="">— не выбрано —</option>
              @foreach($attr->options as $option)
                <option value="{{ $option }}" @selected($val == $option)>
                  {{ $option }}
                </option>
              @endforeach
            </select>

          {{-- ================= NUMBER ================= --}}
          @elseif($attr->type === 'number')
            <input type="number"
                   name="attributes[{{ $attr->id }}]"
                   value="{{ $val }}"
                   class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">

          {{-- ================= BOOLEAN ================= --}}
          @elseif($attr->type === 'boolean')
            <label class="inline-flex items-center space-x-2">
              <input type="checkbox"
                     name="attributes[{{ $attr->id }}]"
                     value="1"
                     @checked($val)>
              <span>Да / Нет</span>
            </label>

          {{-- ================= COLOR (САМЫЙ ВАЖНЫЙ БЛОК) ================= --}}
          @elseif($attr->type === 'color')

            @if(!$attr->colors || $attr->colors->isEmpty())
              <p class="text-gray-400 text-sm">Цвета не настроены администратором.</p>

            @else
              <div class="flex flex-wrap gap-3">

                @foreach($attr->colors as $color)
                  <label class="cursor-pointer">

                    {{-- Кружочек цвета --}}
                    <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center
                                transition
                                {{ (string)$val === (string)$color->id ? 'border-indigo-600 scale-110' : 'border-gray-300' }}"
                         style="background: {{ $color->hex }}">

                      {{-- Внутренняя точка при выборе --}}
                      @if((string)$val === (string)$color->id)
                        <div class="w-3 h-3 bg-white rounded-full shadow"></div>
                      @endif

                    </div>

                    <input type="radio"
                           name="attributes[{{ $attr->id }}]"
                           value="{{ $color->id }}"
                           class="hidden"
                           @checked((string)$val === (string)$color->id)>
                  </label>
                @endforeach

              </div>

              {{-- Подпись выбранного цвета --}}
              @if($val && $attr->colors->where('id', $val)->first())
                <p class="text-xs mt-2 text-gray-500">
                  Выбран: {{ $attr->colors->where('id',$val)->first()->name }}
                </p>
              @endif

            @endif

          {{-- ================= TEXT ================= --}}
          @else
            <input type="text"
                   name="attributes[{{ $attr->id }}]"
                   value="{{ $val }}"
                   class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
          @endif

        </div>

      @endforeach

    </div>
  </section>

@else
  <div class="text-gray-400 text-sm text-center py-4">
    Выберите категорию, чтобы увидеть характеристики
  </div>
@endif
