@error('attributes')
  <p class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
@enderror
@if(isset($attributes) && $attributes->count())
  <section class="seller-form-card">
    <div class="seller-section-head">
      <div><p class="seller-section-kicker">03</p><h2 class="seller-section-title">Характеристики</h2></div>
      <p class="seller-section-hint">Поля меняются под выбранную категорию.</p>
    </div>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
      @foreach($attributes as $attr)
        @php
          $val = old('attributes.'.$attr->id, $attr->value);
          $fieldId = 'seller-attribute-'.$attr->id;
          $errorKey = 'attributes.'.$attr->id;
          $hasError = $errors->has($errorKey);
          $description = $fieldId.'-hint'.($hasError ? ' '.$fieldId.'-error' : '');
          $selectedColor = $attr->type === 'color' ? $attr->colors->first(fn ($color) => (string) $color->id === (string) $val) : null;
        @endphp
        <fieldset class="seller-attribute-field min-w-0 {{ $attr->type === 'color' ? 'sm:col-span-2' : '' }}"
                  data-attribute-definition="{{ json_encode(['id' => (string) $attr->id, 'type' => $attr->type, 'unit' => $attr->unit ?? null, 'options' => array_map('strval', $attr->options ?? []), 'colors' => $attr->type === 'color' ? $attr->colors->pluck('id')->map(fn ($id) => (string) $id)->values()->all() : []]) }}"
                  @if($attr->type === 'color') data-color-field @endif
                  aria-describedby="{{ $description }}" @if($hasError) aria-invalid="true" @endif>
          <legend class="mb-2 text-sm font-semibold text-neutral-800">
            @if($attr->type === 'color')
              {{ $attr->name }}{{ $attr->unit ? ' ('.$attr->unit.')' : '' }}
            @else
              <label for="{{ $fieldId }}">{{ $attr->name }}{{ $attr->unit ? ' ('.$attr->unit.')' : '' }}</label>
            @endif
          </legend>
          @if($attr->type === 'select')
            <select id="{{ $fieldId }}" name="attributes[{{ $attr->id }}]" class="seller-input"
                    aria-invalid="{{ $hasError ? 'true' : 'false' }}" aria-describedby="{{ $description }}">
              <option value="">Выберите значение</option>
              @foreach($attr->options ?? [] as $option)
                <option value="{{ $option }}" @selected((string) $val === (string) $option)>{{ $option }}</option>
              @endforeach
            </select>
            <p id="{{ $fieldId }}-hint" class="sr-only">Выберите значение из списка.</p>
          @elseif($attr->type === 'number')
            <input id="{{ $fieldId }}" type="number" step="any" name="attributes[{{ $attr->id }}]"
                   value="{{ $val }}" class="seller-input" placeholder="Введите число"
                   aria-invalid="{{ $hasError ? 'true' : 'false' }}" aria-describedby="{{ $description }}">
            <p id="{{ $fieldId }}-hint" class="sr-only">{{ $attr->unit ? 'Единица измерения: '.$attr->unit : 'Введите числовое значение.' }}</p>
          @elseif($attr->type === 'boolean')
            <input type="hidden" name="attributes[{{ $attr->id }}]" value="0">
            <label class="inline-flex items-center gap-3 text-sm text-neutral-700">
              <input id="{{ $fieldId }}" type="checkbox" name="attributes[{{ $attr->id }}]" value="1"
                     class="rounded border-neutral-300 text-brand-600 focus:ring-brand-500" @checked($val)
                     aria-invalid="{{ $hasError ? 'true' : 'false' }}" aria-describedby="{{ $description }}">
              <span>Да / Нет</span>
            </label>
            <p id="{{ $fieldId }}-hint" class="sr-only">Отметьте, если подходит вашему товару.</p>
          @elseif($attr->type === 'color')
            <input type="hidden" name="attributes[{{ $attr->id }}]" value="">
            <p id="{{ $fieldId }}-hint" class="mb-3 text-sm text-neutral-500">Выберите один цвет.</p>
            @if(!$attr->colors || $attr->colors->isEmpty())
              <p class="text-sm text-neutral-500">Цвета не настроены администратором.</p>
            @else
              <div class="seller-color-grid">
                @foreach($attr->colors as $color)
                  <label class="seller-color-option">
                    <input type="radio" name="attributes[{{ $attr->id }}]" value="{{ $color->id }}"
                           data-color-name="{{ $color->name }}" class="sr-only"
                           aria-invalid="{{ $hasError ? 'true' : 'false' }}" aria-describedby="{{ $description }}"
                           @checked((string) $val === (string) $color->id)>
                    <span class="seller-color-card">
                      <span class="seller-color-swatch" style="background-color: {{ $color->hex }}" aria-hidden="true"></span>
                      <span class="min-w-0 break-words">{{ $color->name }}</span>
                      <span class="seller-color-check" aria-hidden="true">✓</span>
                    </span>
                  </label>
                @endforeach
              </div>
            @endif
            <p class="mt-3 text-sm font-medium text-neutral-700" data-color-summary aria-live="polite">{{ $selectedColor ? 'Выбран: '.$selectedColor->name : 'Цвет не выбран' }}</p>
            <button type="button" data-color-reset @if(!$selectedColor) hidden @endif
                    class="mt-2 rounded text-sm font-medium text-brand-700 underline underline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600">Сбросить цвет</button>
          @else
            <input id="{{ $fieldId }}" type="text" name="attributes[{{ $attr->id }}]" value="{{ $val }}"
                   class="seller-input" placeholder="Введите значение"
                   aria-invalid="{{ $hasError ? 'true' : 'false' }}" aria-describedby="{{ $description }}">
            <p id="{{ $fieldId }}-hint" class="mt-2 text-xs text-neutral-500">Укажите значение для вашего товара.</p>
          @endif
          @error($errorKey)<p id="{{ $fieldId }}-error" class="mt-2 text-sm font-medium text-red-600" role="alert">{{ $message }}</p>@enderror
        </fieldset>
      @endforeach
    </div>
  </section>
@else
  <div class="seller-empty-state">
    <div class="seller-empty-icon">⌁</div>
    <p class="font-semibold text-neutral-800">Характеристики появятся после выбора категории</p>
    <p class="mt-1 text-sm text-neutral-500">Так покупателям будет проще найти товар через фильтры.</p>
  </div>
@endif
