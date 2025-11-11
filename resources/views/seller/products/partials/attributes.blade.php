@if(isset($attributes) && $attributes->count())
  <section class="bg-white border border-gray-200 rounded-xl shadow-sm p-6">
    <h2 class="text-lg font-semibold text-gray-700 mb-4">Характеристики товара</h2>

    <div class="space-y-5">
      @foreach($attributes as $attr)
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $attr->name }}
          </label>

          @php
            $val = old('attributes.'.$attr->id, $attr->value);
          @endphp

          @if($attr->type === 'select')
            <select name="attributes[{{ $attr->id }}]" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">
              <option value="">— не выбрано —</option>
              @foreach($attr->options as $option)
                <option value="{{ $option }}" @selected($val == $option)>{{ $option }}</option>
              @endforeach
            </select>

          @elseif($attr->type === 'number')
            <input type="number" name="attributes[{{ $attr->id }}]" value="{{ $val }}"
                   class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 text-sm">

          @elseif($attr->type === 'boolean')
            <label class="inline-flex items-center space-x-2">
              <input type="checkbox" name="attributes[{{ $attr->id }}]" value="1" @checked($val)>
              <span>Да / Нет</span>
            </label>

          @elseif($attr->type === 'color')
            <input type="color" name="attributes[{{ $attr->id }}]" value="{{ $val ?? '#ffffff' }}"
                   class="w-12 h-8 border-gray-300 rounded">

          @else
            <input type="text" name="attributes[{{ $attr->id }}]" value="{{ $val }}"
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
