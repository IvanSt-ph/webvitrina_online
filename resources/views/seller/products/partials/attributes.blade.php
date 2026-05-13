@if(isset($attributes) && $attributes->count())
  <section class="seller-form-card">
    <div class="seller-section-head">
      <div>
        <p class="seller-section-kicker">03</p>
        <h2 class="seller-section-title">Характеристики</h2>
      </div>
      <p class="seller-section-hint">Поля меняются под выбранную категорию.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

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
                    class="seller-input">
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
                   class="seller-input">

          {{-- ================= BOOLEAN ================= --}}
          @elseif($attr->type === 'boolean')
            <label class="inline-flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-medium text-gray-700">
              <input type="checkbox"
                     name="attributes[{{ $attr->id }}]"
                     value="1"
                     class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
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
                  <label class="cursor-pointer color-option">

                    {{-- Кружочек цвета --}}
                    <div class="color-circle w-9 h-9 rounded-full border-2 flex items-center justify-center
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
                   class="seller-input">
          @endif

        </div>

      @endforeach

    </div>
  </section>

@else
  <div class="seller-empty-state">
    <div class="seller-empty-icon">⌁</div>
    <p class="font-semibold text-gray-800">Характеристики появятся после выбора категории</p>
    <p class="mt-1 text-sm text-gray-500">Так покупателям будет проще найти товар через фильтры.</p>
  </div>
@endif
